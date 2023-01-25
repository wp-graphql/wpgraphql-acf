<?php

namespace WPGraphQLAcf;

use Exception;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;

class FieldConfig {

	/**
	 * @var array
	 */
	protected $acf_field;

	/**
	 * @var array
	 */
	protected $acf_field_group;

	/**
	 * @var Registry
	 */
	protected $registry;

	public function __construct( array $acf_field, array $acf_field_group, Registry $registry ) {

		$this->acf_field = $acf_field;
		$this->acf_field_group = $acf_field_group;
		$this->registry = $registry;

	}

	/**
	 * Determine whether an ACF Field is supported by GraphQL
	 *
	 * @return bool
	 */
	protected function is_supported_field_type(): bool {

		$supported_types = apply_filters( 'graphql_acf_supported_fields', [
			'text',
			'textarea',
			'number',
			'range',
			'email',
			'url',
			'password',
			'image',
			'file',
			'wysiwyg',
			'oembed',
			'gallery',
			'select',
			'checkbox',
			'radio',
			'button_group',
			'true_false',
			'link',
			'post_object',
			'page_link',
			'relationship',
			'taxonomy',
			'user',
			'google_map',
			'date_picker',
			'date_time_picker',
			'time_picker',
			'color_picker',
			'group',
			'repeater',
			'flexible_content',
		] );

		return isset( $this->acf_field['type'] ) && in_array( $this->acf_field['type'], $supported_types, true );

	}

	/**
	 * @return array|null
	 * @throws Error
	 * @throws Exception
	 */
	public function get_graphql_field_config():?array {

		// if the field is explicitly set to not show in graphql, leave it out of the schema
		// if the field is explicitly set to not show in graphql, leave it out of the schema
		if ( isset( $this->acf_field['show_in_graphql'] ) && false === $this->acf_field['show_in_graphql'] ) {
			return null;
		}

		// if the field is not a supported type, don't add it to the schema
		if ( ! $this->is_supported_field_type() ) {
			return null;
		}

		if ( empty( $field_name = $this->registry->get_graphql_field_name( $this->acf_field ) ) ) {
			return null;
		}

		$field_config = [
			'type'            => 'String',
			'name'            => $field_name,
			'description'     => sprintf( __( 'Field added by WPGraphQL for ACF Redux %s', 'wp-graphql-acf' ), $this->registry->get_field_group_graphql_type_name( $this->acf_field_group ) ),
			'acf_field'       => $this->acf_field,
			'acf_field_group' => $this->acf_field_group,
			'resolve'         => function ( $root, $args, AppContext $context, ResolveInfo $info ) {
				return $this->registry->resolve_field( $root, $args, $context, $info );
			},
		];

		if ( ! empty( $this->acf_field['type'] ) ) {

			switch ( $this->acf_field['type'] ) {
				case 'group':
					$parent_type     = $this->registry->get_field_group_graphql_type_name( $this->acf_field_group );
					$field_name      = $this->registry->get_graphql_field_name( $this->acf_field );
					$sub_field_group = $this->acf_field;
					$type_name       = \WPGraphQL\Utils\Utils::format_field_name( $parent_type . ' ' . $field_name );

					$sub_field_group['graphql_field_name'] = $type_name;

					$this->registry->register_acf_field_groups_to_graphql( [
						$sub_field_group,
					] );

					$field_config['type'] = $type_name;
					break;

				case 'flexible_content':
					$parent_type             = $this->registry->get_field_group_graphql_type_name( $this->acf_field_group );
					$field_name              = $this->registry->get_graphql_field_name( $this->acf_field );
					$layout_interface_prefix = \WPGraphQL\Utils\Utils::format_type_name( $parent_type . ' ' . $field_name );
					$layout_interface_name   = $layout_interface_prefix . '_Layout';

					if ( empty( $this->registered_field_groups[ $layout_interface_name ] ) ) {

						register_graphql_interface_type( $layout_interface_name, [
							'eagerlyLoadType' => true,
							'description'     => sprintf( __( 'Layout of the "%1$s" Field of the "%2$s" Field Group Field', 'wp-graphql-acf' ), $field_name, $parent_type ),
							'fields'          => [
								'fieldGroupName' => [
									'type'              => 'String',
									'description'       => __( 'The name of the ACF Flex Field Layout', 'wp-graphql-acf' ),
									'deprecationReason' => __( 'Use __typename instead', 'wp-graphql-acf' ),
								],
							],
							'resolveType'     => function ( $object ) use ( $layout_interface_prefix ) {
								$layout = $object['acf_fc_layout'] ?? null;
								return \WPGraphQL\Utils\Utils::format_type_name( $layout_interface_prefix . ' ' . $layout );
							},
						] );

						$this->registered_field_groups[ $layout_interface_name ] = $layout_interface_name;

					}

					$layouts = [];
					foreach ( $this->acf_field['layouts'] as $layout ) {

						// Format the name of the group using the layout prefix + the layout name
						$layout_name = \WPGraphQL\Utils\Utils::format_type_name( $layout_interface_prefix . ' ' . $this->registry->get_field_group_graphql_type_name( $layout ) );

						// set the graphql_field_name using the $layout_name
						$layout['graphql_field_name'] = $layout_name;

						// Pass that the layout is a flexLayout (compared to a standard field group)
						$layout['isFlexLayout'] = true;

						// Get interfaces, including cloned field groups, for the layout
						$interfaces = $this->registry->get_field_group_interfaces( $layout );

						// Add the layout interface name as an interface. This is the type that is returned as a list of for accessing all layouts of the flex field
						$interfaces[]                 = $layout_interface_name;
						$layout['eagerlyLoadType']    = true;
						$layout['graphql_field_name'] = $layout_name;
						$layout['fields']             = $this->registry->get_fields_for_field_group( $layout );
						$layout['interfaces']         = $interfaces;
						$layouts[ $layout_name ]      = $layout;
					}

					if ( ! empty( $layouts ) ) {
						$this->registry->register_acf_field_groups_to_graphql( $layouts );
					}


					$field_config['type'] = [ 'list_of' => $layout_interface_name ];
					break;

				case 'repeater':
					$parent_type     = $this->registry->get_field_group_graphql_type_name( $this->acf_field_group );
					$field_name      = $this->registry->get_graphql_field_name( $this->acf_field );
					$sub_field_group = $this->acf_field;
					$type_name       = \WPGraphQL\Utils\Utils::format_type_name( $parent_type . ' ' . $field_name );

					$sub_field_group['graphql_field_name'] = $type_name;

					$this->registry->register_acf_field_groups_to_graphql( [
						$sub_field_group,
					] );

					$field_config['type'] = [ 'list_of' => $type_name ];
					break;
				default:
					break;
			}
		}

		return $field_config;
	}

}

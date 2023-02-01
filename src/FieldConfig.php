<?php

namespace WPGraphQLAcf;

use Exception;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection\PostObjectConnectionResolver;

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
	 * @var string|null
	 */
	protected $graphql_field_group_type_name;

	/**
	 * @var string
	 */
	protected $graphql_field_name;

	/**
	 * @var Registry
	 */
	protected $registry;

	/**
	 * @throws Error
	 */
	public function __construct( array $acf_field, array $acf_field_group, Registry $registry ) {

		$this->acf_field = $acf_field;
		$this->acf_field_group = $acf_field_group;
		$this->registry = $registry;
		$this->graphql_field_group_type_name = $this->registry->get_field_group_graphql_type_name( $this->acf_field_group );
		$this->graphql_field_name = $this->registry->get_graphql_field_name( $this->acf_field );

	}

	/**
	 * Determine whether an ACF Field is supported by GraphQL
	 *
	 * @return bool
	 */
	protected function is_supported_field_type(): bool {
		$supported_types = Utils::get_supported_field_types();
		return ! empty( $this->acf_field['type'] ) && in_array( $this->acf_field['type'], $supported_types, true );
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
				return $this->resolve_field( $root, $args, $context, $info );
			},
		];


		if ( ! empty( $this->acf_field['type'] ) ) {


			switch ( $this->acf_field['type'] ) {
				case 'number':
				case 'range':
					$field_config['type'] = 'Float';
					break;
				case 'true_false':
					$field_config['type'] = 'Boolean';
					break;
				case 'google_map':
					$field_config['type'] = 'AcfGoogleMap';
					break;
				case 'link':
					$field_config['type'] = 'AcfLink';
					break;
				case 'checkbox':
				case 'select':
					$field_config['type'] = [ 'list_of' => 'String' ];
					$field_config['resolve'] = function( $node, array $args, AppContext $context, ResolveInfo $info ) {
						$value = $this->resolve_field( $node, $args, $context, $info );
						if ( empty( $value ) && ! is_array( $value ) )  {
							return null;
						}

						return is_array( $value ) ? $value : [ $value ];
					};
					break;
				case 'file':
				case 'image':
					$field_config = null;

					$type_name = $this->graphql_field_group_type_name;
					$to_type = 'MediaItem';
					$connection_name = $this->get_connection_name( $type_name, $to_type, $this->graphql_field_name );

					register_graphql_connection( [
						'acf_field'       => $this->acf_field,
						'acf_field_group' => $this->acf_field_group,
						'fromType' => $type_name,
						'toType' => $to_type,
						'fromFieldName' => $this->graphql_field_name,
						'connectionTypeName' => $connection_name,
						'oneToOne' => true,
						'resolve' => function( $root, $args, AppContext $context, $info ) {

							$value = $this->resolve_field( $root, $args, $context, $info );

							if ( empty( $value ) || ! absint( $value ) ) {
								return null;
							}

							$resolver = new PostObjectConnectionResolver( $root, $args, $context, $info, 'attachment' );
							return $resolver
								->one_to_one()
								->set_query_arg( 'p', absint( $value ) )
								->get_connection();
						}
					]);

					break;
				case 'gallery':
					$field_config = null;

					$type_name = $this->graphql_field_group_type_name;
					$to_type = 'MediaItem';
					$connection_name = $this->get_connection_name( $type_name, $to_type, $this->graphql_field_name );

					register_graphql_connection( [
						'acf_field'       => $this->acf_field,
						'acf_field_group' => $this->acf_field_group,
						'fromType' => $type_name,
						'toType' => $to_type,
						'fromFieldName' => $this->graphql_field_name,
						'connectionTypeName' => $connection_name,
						'oneToOne' => false,
						'resolve' => function( $root, $args, AppContext $context, $info ) {

							$value = $this->resolve_field( $root, $args, $context, $info );

							if ( empty( $value ) || ! is_array( $value ) ) {
								return null;
							}

							$value = array_map( static function( $id ) {
								return absint( $id );
							}, $value );

							$resolver = new PostObjectConnectionResolver( $root, $args, $context, $info, 'attachment' );
							return $resolver
								->set_query_arg( 'post__in', $value )
								->set_query_arg( 'orderby', 'post__in' )
								->get_connection();
						}
					]);

					break;
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

					if ( ! $this->registry->has_registered_field_group( $layout_interface_name ) ) {
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

						$this->registry->register_field_group( $layout_interface_name, $layout_interface_name );

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

				case 'post_object':
				case 'page_link':

					$field_config = null;

					$type_name = $this->graphql_field_group_type_name;
					$to_type = 'ContentNode';
					$connection_name = $this->get_connection_name( $type_name, $to_type, $this->graphql_field_name );

					$connection_config = [
						'acf_field'       => $this->acf_field,
						'acf_field_group' => $this->acf_field_group,
						'fromType' => $type_name,
						'toType' => $to_type,
						'connectionTypeName' => $connection_name,
						'fromFieldName' => $this->graphql_field_name,
						'resolve' => function( $root, $args, AppContext $context, $info ) {
							$value = $this->resolve_field( $root, $args, $context, $info );

							if ( empty( $value ) || ! is_array( $value ) ) {
								return null;
							}

							$value = array_map(static function( $id ) {
								return absint( $id );
							}, $value );


							$resolver = new PostObjectConnectionResolver( $root, $args, $context, $info, 'any' );
							return $resolver
								// the relationship field doesn't require related things to be published
								// so we set the status to "any"
								->set_query_arg( 'post_status', 'any' )
								->set_query_arg( 'post__in', $value )
								->set_query_arg( 'orderby', 'post__in' )
								->get_connection();
						}
					];

					if ( ! isset( $this->acf_field['multiple'] ) || true !== (bool) $this->acf_field['multiple'] ) {
						$connection_name =  \WPGraphQL\Utils\Utils::format_type_name( $type_name ) . \WPGraphQL\Utils\Utils::format_type_name( $this->graphql_field_name ) . 'ToSingleContentNodeConnection';


						$connection_config['connectionTypeName'] = $connection_name;
						$connection_config['oneToOne'] = true;
						$connection_config['resolve'] = function( $root, $args, AppContext $context, $info ) {
							$value = $this->resolve_field( $root, $args, $context, $info );

							if ( empty( $value ) || ! absint( $value ) ) {
								return null;
							}

							$resolver = new PostObjectConnectionResolver( $root, $args, $context, $info, 'any' );
							return $resolver
								->one_to_one()
								->set_query_arg( 'p', absint( $value ) )
								->get_connection();
						};
					}

					register_graphql_connection( $connection_config );

					break;
				case 'relationship':

					$field_config = null;

					$type_name = $this->graphql_field_group_type_name;
					$to_type = 'ContentNode';
					$connection_name = $this->get_connection_name( $type_name, $to_type, $this->graphql_field_name );


					register_graphql_connection([
						'acf_field'       => $this->acf_field,
						'acf_field_group' => $this->acf_field_group,
						'fromType' => $type_name,
						'toType' => $to_type,
						'connectionTypeName' => $connection_name,
						'fromFieldName' => $this->graphql_field_name,
						'resolve' => function( $root, $args, AppContext $context, $info ) {
							$value = $this->resolve_field( $root, $args, $context, $info );

							if ( empty( $value ) || ! is_array( $value ) ) {
								return null;
							}

							$value = array_map(static function( $id ) {
								return absint( $id );
							}, $value );


							$resolver = new PostObjectConnectionResolver( $root, $args, $context, $info, 'any' );
							return $resolver
								// the relationship field doesn't require related things to be published
								// so we set the status to "any"
								->set_query_arg( 'post_status', 'any' )
								->set_query_arg( 'post__in', $value )
								->set_query_arg( 'orderby', 'post__in' )
								->get_connection();
						}
					]);

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
					$field_config['type'] = 'String';
					break;
			}
		}

		return $field_config;
	}

	/**
	 * Determine if the field should ask ACF to format the response when retrieving
	 * the field using get_field()
	 *
	 * @param string $field_type The ACF Field Type of field being resolved
	 *
	 * @return bool
	 */
	public function should_format_field_value( string $field_type ): bool {

		// @todo: filter this? And it should be done at the registry level once, not the per-field level
		$types_to_format = [
			'select',
			'wysiwyg',
		];

		return in_array( $field_type, $types_to_format, true );

	}

	/**
	 * @param mixed       $root
	 * @param array       $args
	 * @param AppContext  $context
	 * @param ResolveInfo $info
	 *
	 * @return mixed
	 */
	public function resolve_field( $root, array $args, AppContext $context, ResolveInfo $info ) {

		// @todo: Handle options pages??
		$field_config = $info->fieldDefinition->config['acf_field'] ?? $this->acf_field;
		$node         = $root['node'] ?: null;
		$node_id      = \WPGraphQLAcf\Utils::get_node_acf_id( $node ) ?: null;
		$field_key    = $field_config['cloned_key'] ?? ( $field_config['key'] ?: null );

		$should_format_value = $this->should_format_field_value( $field_config['type'] ?? null );

		if ( empty( $field_key ) ) {
			return null;
		}

		// If the root being passed down already has a value
		// for the field key, let's use it to resolve
		if ( ! empty( $root[ $field_key ] ) ) {
			return $this->prepare_acf_field_value( $root[ $field_key ], $node, $node_id, $field_config );
		}

		// If there's no node_id at this point, we can return null
		if ( empty( $node_id ) ) {
			return null;
		}

		/**
		 * Filter the field value before resolving.
		 *
		 * @param mixed            $value     The value of the ACF Field stored on the node
		 * @param mixed            $node      The object the field is connected to
		 * @param mixed|string|int $node_id   The ACF ID of the node to resolve the field with
		 * @param array            $acf_field The ACF Field config
		 * @param bool             $format    Whether to apply formatting to the field
		 */
		$value = apply_filters( 'graphql_acf_pre_resolve_acf_field', null, $root, $node_id, $field_config, $should_format_value );

		// If the filter has returned a value, we can return the value that was returned.
		if ( null !== $value ) {
			return $value;
		}

		// @phpstan-ignore-next-line
		$value = get_field( $field_key, $node_id, $should_format_value );
		$value = $this->prepare_acf_field_value( $value, $root, $node_id, $field_config );

		if ( empty( $value ) ) {
			$value = null;
		}

		/**
		 * Filter the value before returning
		 *
		 * @param mixed $value
		 * @param array $field_config The ACF Field Config for the field being resolved
		 * @param mixed $root The Root node or obect of the field being resolved
		 * @param mixed $node_id The ID of the node being resolved
		 */
		return apply_filters( 'graphql_acf_field_value', $value, $field_config, $root, $node_id );

	}

	/**
	 * Given a value of an ACF Field, this prepares it for response by applying formatting, etc based on
	 * the field type.
	 *
	 * @param mixed            $value   The value of the ACF field to return
	 * @param mixed            $root    The root node/object the field belongs to
	 * @param mixed|string|int $node_id The ID of the node the field belongs to
	 * @param array            $acf_field_config The ACF Field Config for the field being resolved
	 *
	 * @return mixed
	 */
	public function prepare_acf_field_value( $value, $root, $node_id, array $acf_field_config ) {

		if ( isset( $acf_field_config['new_lines'] ) ) {
			if ( 'wpautop' === $acf_field_config['new_lines'] ) {
				$value = wpautop( $value );
			}
			if ( 'br' === $acf_field_config['new_lines'] ) {
				$value = nl2br( $value );
			}
		}

		// @todo: This was ported over, but I'm not 💯 sure what this is solving and
		// why it's only applied on options pages and not other pages 🤔
		if ( is_array( $root ) && ! ( ! empty( $root['type'] ) && 'options_page' === $root['type'] ) ) {

			if ( isset( $root[ $acf_field_config['key'] ] ) ) {
				$value = $root[ $acf_field_config['key'] ];
				if ( 'wysiwyg' === $acf_field_config['type'] ) {
					$value = apply_filters( 'the_content', $value );
				}
			}
		}

		if ( ! empty( $acf_field_config['type'] ) && in_array( $acf_field_config['type'], [
				'date_picker',
				'time_picker',
				'date_time_picker',
			], true ) ) {

			if ( ! empty( $value ) && ! empty( $acf_field_config['return_format'] ) ) {
				$value = date( $acf_field_config['return_format'], strtotime( $value ) );
			}
		}

		if ( ! empty( $acf_field_config['type'] ) && in_array( $acf_field_config['type'], [ 'number', 'range' ], true ) ) {
			$value = (float) $value ?: null;
		}

		return $value;

	}

	/**
	 * @param string $from_type
	 * @param string $to_type
	 * @param string $from_field_name
	 *
	 * @return string
	 */
	public function get_connection_name( string $from_type, string $to_type, string $from_field_name  ) {
		// Create connection name using $from_type + To + $to_type + Connection.
		return  \WPGraphQL\Utils\Utils::format_type_name( ucfirst( $from_type ) . ucfirst( $from_field_name ) . 'To' . ucfirst( $to_type ) . 'Connection' );
	}

}

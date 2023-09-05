<?php
namespace WPGraphQL\Acf\FieldType;

use WPGraphQL\Utils\Utils;
use WPGraphQL\Acf\AcfGraphQLFieldType;
use WPGraphQL\Acf\FieldConfig;

class FlexibleContent {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type(
			'flexible_content',
			[
				'graphql_type' => static function ( FieldConfig $field_config, AcfGraphQLFieldType $acf_field_type ) {
					$parent_type             = $field_config->get_graphql_field_group_type_name();
					$field_name              = $field_config->get_graphql_field_name();
					$layout_interface_prefix = Utils::format_type_name( $parent_type . ' ' . $field_name );
					$layout_interface_name   = $layout_interface_prefix . '_Layout';
					$acf_field               = $field_config->get_acf_field();

					if ( ! $field_config->get_registry()->has_registered_field_group( $layout_interface_name ) ) {
						register_graphql_interface_type(
							$layout_interface_name,
							[
								'eagerlyLoadType' => true,
								// translators: %1$s is the name of the flexible field containing layouts. %2$s is the name of the field group the flexible content field belongs to.
								'description'     => sprintf( __( 'Layout of the "%1$s" Field of the "%2$s" Field Group Field', 'wp-graphql-acf' ), $field_name, $parent_type ),
								'fields'          => [
									'fieldGroupName' => [
										'type'        => 'String',
										'description' => __( 'The name of the ACF Flex Field Layout', 'wp-graphql-acf' ),
										'deprecationReason' => __( 'Use __typename instead', 'wp-graphql-acf' ),
									],
								],
								'resolveType'     => static function ( $object ) use ( $layout_interface_prefix ) {
									$layout = $object['acf_fc_layout'] ?? null;
									return Utils::format_type_name( $layout_interface_prefix . ' ' . $layout );
								},
							]
						);

						$field_config->get_registry()->register_field_group( $layout_interface_name, $layout_interface_name );
					}

					$layouts = [];
					if ( ! empty( $acf_field['layouts'] ) ) {
						foreach ( $acf_field['layouts'] as $layout ) {

							// Format the name of the group using the layout prefix + the layout name
							$layout_name = Utils::format_type_name( $layout_interface_prefix . ' ' . $field_config->get_registry()->get_field_group_graphql_type_name( $layout ) );

							// set the graphql_field_name using the $layout_name
							$layout['graphql_field_name'] = $layout_name;

							// Pass that the layout is a flexLayout (compared to a standard field group)
							$layout['isFlexLayout'] = true;

							// Get interfaces, including cloned field groups, for the layout
							$interfaces = $field_config->get_registry()->get_field_group_interfaces( $layout );

							// Add the layout interface name as an interface. This is the type that is returned as a list of for accessing all layouts of the flex field
							$interfaces[]                 = $layout_interface_name;
							$layout['eagerlyLoadType']    = true;
							$layout['graphql_field_name'] = $layout_name;
							$layout['fields']             = [
								'fieldGroupName' => [
									'type'              => 'String',
									'description'       => __( 'The name of the ACF Flex Field Layout', 'wp-graphql-acf' ),
									'deprecationReason' => __( 'Use __typename instead', 'wp-graphql-acf' ),
								],
							];
							$layout['interfaces']         = $interfaces;
							$layouts[ $layout_name ]      = $layout;
						}
					}

					if ( ! empty( $layouts ) ) {
						$field_config->get_registry()->register_acf_field_groups_to_graphql( $layouts );
					}

					return [ 'list_of' => $layout_interface_name ];
				},
			]
		);
	}

}

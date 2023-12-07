<?php
namespace WPGraphQL\Acf\FieldType;

use WPGraphQL\Acf\AcfGraphQLFieldType;
use WPGraphQL\Acf\FieldConfig;
use WPGraphQL\Utils\Utils;

class CloneField {

	/**
	 * @return void
	 */
	public static function register_field_type():void {
		register_graphql_acf_field_type(
			'clone',
			[
				'graphql_type' => static function ( FieldConfig $field_config, AcfGraphQLFieldType $acf_field_type ) {
					$sub_field_group = $field_config->get_raw_acf_field();
					$parent_type     = $field_config->get_parent_graphql_type_name( $sub_field_group );
					$field_name      = $field_config->get_graphql_field_name();

					$type_name = Utils::format_type_name( $parent_type . ' ' . $field_name );

					$cloned_groups = [];
					if ( ! empty( $sub_field_group['clone'] ) && is_array( $sub_field_group['clone'] ) ) {
						foreach ( $sub_field_group['clone'] as $cloned_from ) {
							if ( ! acf_get_field_group( $cloned_from ) ) {
								continue;
							}
							if ( ! in_array( $cloned_from, $cloned_groups, true ) ) {
								$cloned_groups[] = acf_get_field_group( $cloned_from );
							}
						}
					}

					$cloned_group_interfaces = [];

					if ( ! empty( $cloned_groups ) ) {
						foreach ( $cloned_groups as $cloned_group ) {
							$cloned_group_interfaces[] = $field_config->get_registry()->get_field_group_graphql_type_name( $cloned_group ) . '_Fields';
						}
					}

					if ( ! empty( $cloned_group_interfaces ) ) {

						// If a clone field clones all fields from another field group,
						// but has "prefix_name" false, implement the Interface on the parent group
						if ( false === (bool) $sub_field_group['prefix_name'] ) {
							$parent_group = acf_get_field_group( $sub_field_group['parent'] );

							if ( empty( $parent_group ) ) {
								$parent_field = acf_get_field( $sub_field_group['parent'] );
								$parent_group = ! empty( $parent_field ) ? acf_get_field_group( $parent_field['parent'] ) : false;
							}

							if ( ! empty( $parent_group ) ) {
								$parent_group_type_name = $field_config->get_registry()->get_field_group_graphql_type_name( $parent_group );

								if ( isset( $sub_field_group['isFlexLayoutField'] ) && true === (bool) $sub_field_group['isFlexLayoutField'] ) {
									$parent_type_name = $field_config->get_registry()->get_field_group_graphql_type_name( $sub_field_group['parent_layout_group'] ) ?? $type_name;
									register_graphql_interfaces_to_types( $cloned_group_interfaces, [ $parent_type_name ] );
								} else {
									register_graphql_interfaces_to_types( $cloned_group_interfaces, [ $parent_group_type_name ] );
								}
								return 'connection';
							}
							// If "prefix_name" is true, nest the cloned field group within another GraphQL object type to avoid
							// collisions with multiple instances of the field group being cloned
						} else {
							if ( ! empty( $type_name ) ) {
								// Register the cloned group interfaces to the type representing the cloned fields
								register_graphql_interfaces_to_types( $cloned_group_interfaces, [ $type_name ] );
							}
						}
					}


					$sub_field_group['graphql_type_name']  = $type_name;
					$sub_field_group['graphql_field_name'] = $type_name;
					$sub_field_group['parent']             = $sub_field_group['key'];

					$field_config->get_registry()->register_acf_field_groups_to_graphql(
						[
							$sub_field_group,
						]
					);

					return $type_name;
				},
				// The clone field adds its own settings field to display
				'admin_fields' => static function ( $default_admin_settings, $field, $config, \WPGraphQL\Acf\Admin\Settings $settings ) {

					// Return one GraphQL Field, ignoring the default admin settings
					return [
						'graphql_clone_field' => [
							'type'         => 'message',
							'label'        => __( 'GraphQL Settings for Clone Fields', 'wp-graphql-acf' ),
							'instructions' => __( 'Clone Fields will inherit their GraphQL settings from the field(s) being cloned. If all Fields from a Field Group are cloned, an Interface representing the cloned field Group will be applied to this field group.', 'wp-graphql-acf' ),
							'conditions'   => [],
						],
					];
				},
			]
		);
	}
}

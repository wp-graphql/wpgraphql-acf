<?php
namespace WPGraphQL\Acf\FieldType;

use WPGraphQL\Acf\AcfGraphQLFieldType;
use WPGraphQL\Acf\FieldConfig;
use WPGraphQL\Utils\Utils;

class Repeater {

	/**
	 * Register support for the "repeater" ACF field type
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type(
			'repeater',
			[
				'graphql_type' => static function ( FieldConfig $field_config, AcfGraphQLFieldType $acf_field_type ) {
					$sub_field_group = $field_config->get_acf_field();
					$parent_type     = $field_config->get_parent_graphql_type_name( $sub_field_group );
					$field_name      = $field_config->get_graphql_field_name();
					$type_name       = Utils::format_type_name( $parent_type . ' ' . $field_name );

					$sub_field_group['graphql_type_name']  = $type_name;
					$sub_field_group['graphql_field_name'] = $type_name;
					$sub_field_group['locations']          = null;

					if ( ! empty( $sub_field_group['__key'] ) ) {
						$cloned_from   = acf_get_field( $sub_field_group['__key'] );
						$cloned_parent = ! empty( $cloned_from ) ? $field_config->get_parent_graphql_type_name( $cloned_from ) : null;
						if ( ! empty( $cloned_parent ) ) {
							$type_name = Utils::format_type_name( $cloned_parent . ' ' . $field_name );
							return [ 'list_of' => $type_name ];
						}
					}


					$field_config->get_registry()->register_acf_field_groups_to_graphql(
						[
							$sub_field_group,
						]
					);

					return [ 'list_of' => $type_name ];
				},
			]
		);
	}
}

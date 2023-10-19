<?php
namespace WPGraphQL\Acf\FieldType;

use WPGraphQL\AppContext;
use WPGraphQL\Utils\Utils;
use WPGraphQL\Acf\AcfGraphQLFieldType;
use WPGraphQL\Acf\FieldConfig;

class Repeater {

	/**
	 * @return void
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


					$field_config->get_registry()->register_acf_field_groups_to_graphql(
						[
							$sub_field_group,
						]
					);

					return [ 'list_of' => $type_name ];
				},
				'resolve'      => static function ( $root, $args, AppContext $context, $info, $field_type, FieldConfig $field_config ) {
					$value = $field_config->resolve_field( $root, $args, $context, $info );
//
					codecept_debug( [
						'$repeater_value' => $value,
					]);

//					acf_setup_meta( $root['node']['attrs']['data'] );
//
//					$root['value']           = $value;
//					$root['acf_field_group'] = $field_config->get_acf_field();
//
//					$manual_value = get_field( $field_config->get_acf_field()['name'] );
//
////					wp_send_json( [
////						'$value' => $value,
////						'$manual_value' => $manual_value,
////					]);
////
////					codecept_debug( [
////						'$repeater_value' => $value,
////						'$get_field_value' => get_field( 'test_repeater' ),
////					]);

					return $value;
				},

			]
		);
	}

}

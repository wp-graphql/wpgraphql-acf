<?php
namespace WPGraphQL\Acf\FieldType;

use WPGraphQL\Acf\AcfGraphQLFieldType;
use WPGraphQL\Acf\FieldConfig;
use WPGraphQL\AppContext;
use WPGraphQL\Utils\Utils;

class Group {

	/**
	 * Register support for the "group" ACF field type
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type(
			'group',
			[
				'graphql_type' => static function ( FieldConfig $field_config, AcfGraphQLFieldType $acf_field_type ) {
					$sub_field_group = $field_config->get_acf_field();
					$parent_type     = $field_config->get_parent_graphql_type_name( $sub_field_group );
					$field_name      = $field_config->get_graphql_field_name();

					$type_name = Utils::format_type_name( $parent_type . ' ' . $field_name );

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
				'resolve'      => static function ( $root, $args, AppContext $context, $info, $field_type, FieldConfig $field_config ) {
					$value = $field_config->resolve_field( $root, $args, $context, $info );

					if ( ! empty( $value ) ) {
						return $value;
					}

					$root['value']           = $value;
					$root['acf_field_group'] = $field_config->get_acf_field();

					return $root;
				},
			]
		);
	}
}

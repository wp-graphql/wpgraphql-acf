<?php
namespace WPGraphQLAcf\FieldType;

use WPGraphQL\Utils\Utils;
use WPGraphQLAcf\AcfGraphQLFieldType;
use WPGraphQLAcf\FieldConfig;

class Group {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'group', [
			'graphql_type' => function ( FieldConfig $field_config, AcfGraphQLFieldType $acf_field_type ) {

				$parent_type     = $field_config->get_graphql_field_group_type_name();
				$field_name      = $field_config->get_graphql_field_name();
				$sub_field_group = $field_config->get_acf_field();
				$type_name       = Utils::format_type_name( $parent_type . ' ' . $field_name );

				$sub_field_group['graphql_field_name'] = $type_name;

				$field_config->get_registry()->register_acf_field_groups_to_graphql( [
					$sub_field_group,
				] );

				return $type_name;

			},
		] );

	}

}

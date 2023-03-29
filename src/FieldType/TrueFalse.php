<?php
namespace WPGraphQLAcf\FieldType;

use WPGraphQLAcf\AcfGraphQLFieldType;
use WPGraphQLAcf\FieldConfig;

class TrueFalse {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'true_false', [
			'graphql_type' => 'Boolean',
			'graphql_resolver' => function( $root, $args, $context, $info, FieldConfig $field_config, AcfGraphQLFieldType $field_type ) {

				$field_config->resolve_field( $root, $args, $context, $info );

			}
		] );

	}

}

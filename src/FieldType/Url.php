<?php
namespace WPGraphQLAcf\FieldType;

class Url {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'url', [
			'graphql_type' => 'String',
		] );

	}

}

<?php
namespace WPGraphQLAcf\FieldType;

class Email {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'email', [
			'graphql_type' => 'String',
		] );

	}

}

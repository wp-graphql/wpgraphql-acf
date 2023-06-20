<?php
namespace WPGraphQL\Acf\FieldType;

class Textarea {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'textarea' );

	}

}

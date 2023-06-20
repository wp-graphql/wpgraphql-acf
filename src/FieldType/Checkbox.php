<?php
namespace WPGraphQL\Acf\FieldType;

class Checkbox {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'checkbox', [
			'graphql_type' => [ 'list_of' => 'String' ],
		] );

	}

}

<?php
namespace WPGraphQLAcf\FieldType;

class Select {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'select', [
			'graphql_type' => [ 'list_of' => 'String' ],
		] );

	}

}

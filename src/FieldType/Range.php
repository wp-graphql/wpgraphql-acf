<?php
namespace WPGraphQLAcf\FieldType;

class Range {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'range', [
			'graphql_type' => 'Float',
		] );

	}

}

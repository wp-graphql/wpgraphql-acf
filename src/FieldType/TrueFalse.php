<?php
namespace WPGraphQLAcf\FieldType;

class TrueFalse {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'true_false', [
			'graphql_type' => 'Boolean',
		] );

	}

}

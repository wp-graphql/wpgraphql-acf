<?php
namespace WPGraphQL\Acf\FieldType;

class Password {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type(
			'password',
			[
				'graphql_type' => 'String',
			] 
		);
	}

}

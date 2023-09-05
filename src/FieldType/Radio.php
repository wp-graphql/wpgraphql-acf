<?php
namespace WPGraphQL\Acf\FieldType;

class Radio {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type(
			'radio',
			[
				'graphql_type' => 'String',
			] 
		);
	}

}

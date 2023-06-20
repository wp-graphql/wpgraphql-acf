<?php
namespace WPGraphQL\Acf\FieldType;

class ButtonGroup {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'button_group', [
			'graphql_type' => 'String',
		] );

	}

}

<?php
namespace WPGraphQLAcf\FieldType;

class ColorPicker {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'color_picker', [
			'graphql_type' => 'String',
		] );

	}

}

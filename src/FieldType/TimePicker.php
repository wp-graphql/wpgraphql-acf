<?php
namespace WPGraphQLAcf\FieldType;

class TimePicker {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'time_picker', [
			'graphql_type' => 'String',
		] );

	}

}

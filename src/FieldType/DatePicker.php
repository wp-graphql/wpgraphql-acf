<?php
namespace WPGraphQL\Acf\FieldType;

class DatePicker {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type(
			'date_picker',
			[
				'graphql_type' => 'String',
			] 
		);
	}

}

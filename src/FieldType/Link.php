<?php
namespace WPGraphQLAcf\FieldType;

class Link {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'link', [
			'graphql_type' => 'AcfLink',
		] );

	}

}

<?php
namespace WPGraphQLAcf\FieldType;

class GoogleMap {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'google_map', [
			'graphql_type' => 'AcfGoogleMap',
		] );

	}

}

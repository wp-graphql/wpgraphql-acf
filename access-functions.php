<?php

/**
 * @param string $acf_field_type
 * @param array  $config
 *
 * @return void
 */
function register_graphql_acf_field_type( string $acf_field_type, array $config ) {

	add_action( 'graphql_acf_registry_init', static function( \WPGraphQLAcf\FieldTypeRegistry $registry ) use ( $acf_field_type, $config ) {
		$registry->register_field_type( $acf_field_type, $config );
	} );

}

<?php
namespace WPGraphQL\Acf\FieldType;

use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection\TermObjectConnectionResolver;
use WPGraphQL\Acf\AcfGraphQLFieldType;
use WPGraphQL\Acf\FieldConfig;

class Taxonomy {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type(
			'taxonomy',
			[
				'exclude_admin_fields' => [ 'graphql_non_null' ],
				'graphql_type'         => static function ( FieldConfig $field_config, AcfGraphQLFieldType $acf_field_type ) {
					$connection_config = [
						'toType'  => 'TermNode',
						'resolve' => static function ( $root, $args, AppContext $context, $info ) use ( $field_config ) {
							$value = $field_config->resolve_field( $root, $args, $context, $info );

							if ( empty( $value ) ) {
								return null;
							}
							if ( is_array( $value ) ) {
								$value = array_map(
									static function ( $id ) {
										return absint( $id );
									},
									$value 
								);
							}

							$resolver = new TermObjectConnectionResolver( $root, $args, $context, $info );
							return $resolver
							// Set the query to include only the IDs passed in from the field
							// and orderby the ids
							->set_query_arg( 'include', $value )
							->set_query_arg( 'orderby', 'include' )
							->get_connection();
						},
					];

					$field_config->register_graphql_connections( $connection_config );

					// Return null because registering a connection adds it to the Schema for us
					return 'connection';
				},
			] 
		);
	}

}

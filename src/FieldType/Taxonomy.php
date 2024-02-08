<?php
namespace WPGraphQL\Acf\FieldType;

use WPGraphQL\Acf\AcfGraphQLFieldType;
use WPGraphQL\Acf\FieldConfig;
use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection\TermObjectConnectionResolver;

class Taxonomy {

	/**
	 * Register support for the "taxonomy" ACF field type
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

							$values = [];
							if ( ! is_array( $value ) ) {
								$values[] = $value;
							} else {
								$values = $value;
							}

							$ids = array_map(
								static function ( $id ) {
									return absint( $id );
								},
								$values
							);

							if ( empty( $ids ) ) {
								return null;
							}

							$args['where']['include'] = $ids;

							return ( new TermObjectConnectionResolver( $root, $args, $context, $info ) )->get_connection();
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

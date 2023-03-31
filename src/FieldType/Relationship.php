<?php
namespace WPGraphQLAcf\FieldType;

use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection\PostObjectConnectionResolver;
use WPGraphQL\Utils\Utils;
use WPGraphQLAcf\AcfGraphQLFieldType;
use WPGraphQLAcf\FieldConfig;

class Relationship {

	/**
	 * @return void
	 */
	public static function register_field_type():void {

		register_graphql_acf_field_type( 'relationship', [
			'graphql_type' => function ( FieldConfig $field_config, AcfGraphQLFieldType $acf_field_type ) {
				$connection_config = [
					'toType'  => 'ContentNode',
					'resolve' => static function ( $root, $args, AppContext $context, $info ) use ( $field_config ) {
						$value = $field_config->resolve_field( $root, $args, $context, $info );

						if ( empty( $value ) || ! is_array( $value ) ) {
							return null;
						}

						$value = array_map(static function ( $id ) {
							return absint( $id );
						}, $value );

						$resolver = new PostObjectConnectionResolver( $root, $args, $context, $info, 'any' );
						return $resolver
							// the relationship field doesn't require related things to be published
							// so we set the status to "any"
							->set_query_arg( 'post_status', 'any' )
							->set_query_arg( 'post__in', $value )
							->set_query_arg( 'orderby', 'post__in' )
							->get_connection();
					},
				];

				$field_config->register_graphql_connections( $connection_config );
			},
		]);
	}
}

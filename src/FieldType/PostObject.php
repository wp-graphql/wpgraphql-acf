<?php
namespace WPGraphQL\Acf\FieldType;

use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection\PostObjectConnectionResolver;
use WPGraphQL\Acf\AcfGraphQLFieldType;
use WPGraphQL\Acf\FieldConfig;

class PostObject {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type(
			'post_object',
			[
				'exclude_admin_fields' => [ 'graphql_non_null' ],
				'graphql_type'         => static function ( FieldConfig $field_config, AcfGraphQLFieldType $acf_field_type ) {
					$connection_config = [
						'toType'  => 'ContentNode',
						'resolve' => static function ( $root, $args, AppContext $context, $info ) use ( $field_config ) {
							$value = $field_config->resolve_field( $root, $args, $context, $info );

							$ids = [];

							if ( empty( $value ) ) {
								return null;
							}

							if ( is_array( $value ) ) {
								$ids = $value;
							}

							if ( is_object( $value ) && isset( $value->ID ) ) {
								$ids[] = $value->ID;
							}

							$ids = array_map(
								static function ( $id ) {
									if ( is_object( $id ) && isset( $id->ID ) ) {
										$id = $id->ID;
									}
									return absint( $id );
								},
								$ids
							);

							$resolver = new PostObjectConnectionResolver( $root, $args, $context, $info, 'any' );
							return $resolver
							// the relationship field doesn't require related things to be published
							// so we set the status to "any"
							->set_query_arg( 'post_status', 'any' )
							->set_query_arg( 'post__in', $ids )
							->set_query_arg( 'orderby', 'post__in' )
							->get_connection();
						},
					];

					$field_config->register_graphql_connections( $connection_config );

					return 'connection';
				},
			]
		);
	}

}

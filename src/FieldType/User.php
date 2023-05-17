<?php
namespace WPGraphQLAcf\FieldType;

use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection\UserConnectionResolver;
use WPGraphQLAcf\AcfGraphQLFieldType;
use WPGraphQLAcf\FieldConfig;

class User {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'user', [
			'exclude_admin_fields' => [ 'graphql_non_null' ],
			'graphql_type' => function ( FieldConfig $field_config, AcfGraphQLFieldType $acf_field_type ) {

				if ( empty( $field_config->get_graphql_field_group_type_name() ) || empty( $field_config->get_graphql_field_name() ) ) {
					return null;
				}

				$to_type = 'User';
				$field_config->register_graphql_connections([
					'toType'  => $to_type,
					'resolve' => function ( $root, $args, AppContext $context, ResolveInfo $info ) use ( $field_config ) {

						$value = $field_config->resolve_field( $root, $args, $context, $info );

						if ( empty( $value ) || ! absint( $value ) ) {
							return null;
						}

						$resolver = new UserConnectionResolver( $root, $args, $context, $info );
						return $resolver->set_query_arg( 'include', $value )->set_query_arg( 'orderby', 'include' )->get_connection();

					},
				]);

				// The connection will be registered to the Schema so we return null for the field type
				return 'connection';

			},
		] );

	}

}

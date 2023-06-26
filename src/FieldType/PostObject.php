<?php
namespace WPGraphQL\Acf\FieldType;

use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection\PostObjectConnectionResolver;
use WPGraphQL\Utils\Utils;
use WPGraphQL\Acf\AcfGraphQLFieldType;
use WPGraphQL\Acf\FieldConfig;

class PostObject {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'post_object', [
			'exclude_admin_fields' => [ 'graphql_non_null' ],
			'graphql_type'         => function ( FieldConfig $field_config, AcfGraphQLFieldType $acf_field_type ) {
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

				$acf_field = $field_config->get_acf_field();

				if ( ! isset( $acf_field['multiple'] ) || true !== (bool) $acf_field['multiple'] ) {

					if ( empty( $field_config->get_graphql_field_group_type_name() ) || empty( $field_config->get_graphql_field_name() ) ) {
						return null;
					}

					$connection_name = Utils::format_type_name( $field_config->get_graphql_field_group_type_name() ) . Utils::format_type_name( $field_config->get_graphql_field_name() ) . 'ToSingleContentNodeConnection';

					$connection_config['connectionTypeName'] = $connection_name;
					$connection_config['oneToOne']           = true;
					$connection_config['resolve']            = static function ( $root, $args, AppContext $context, $info ) use ( $field_config ) {
						$value = $field_config->resolve_field( $root, $args, $context, $info );

						if ( empty( $value ) || ! absint( $value ) ) {
							return null;
						}

						$resolver = new PostObjectConnectionResolver( $root, $args, $context, $info, 'any' );
						return $resolver
							->one_to_one()
							->set_query_arg( 'p', absint( $value ) )
							->get_connection();
					};
				}

				$field_config->register_graphql_connections( $connection_config );

				return 'connection';
			},
		] );

	}

}

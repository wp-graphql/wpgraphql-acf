<?php
namespace WPGraphQL\Acf\FieldType;

use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection\PostObjectConnectionResolver;
use WPGraphQL\Acf\AcfGraphQLFieldType;
use WPGraphQL\Acf\FieldConfig;

class Relationship {

	/**
	 * @param array                         $admin_fields Admin Fields to display in the GraphQL Tab when configuring an ACF Field within a Field Group
	 * @param array                         $field The
	 * @param array                         $config
	 * @param \WPGraphQL\Acf\Admin\Settings $settings
	 *
	 * @return mixed
	 */
	public static function get_admin_fields( $admin_fields, $field, $config, \WPGraphQL\Acf\Admin\Settings $settings ) {
		$admin_fields[] = [
			'type'          => 'select',
			'name'          => 'graphql_connection_type',
			'label'         => __( 'GraphQL Connection Type', 'wp-graphql-acf' ),
			'choices'       => [
				'one_to_one'  => __( 'One to One Connection', 'wp-graphql-acf' ),
				'one_to_many' => __( 'One to Many Connection', 'wp-graphql-acf' ),
			],
			'default_value' => 'one_to_many',
			'instructions'  => __( 'Select whether the field should be presented in the schema as a standard GraphQL "Connection" that can return 0, 1 or more nodes, or a "One to One" connection that can return exactly 0 or 1 node. Changing this field will change the GraphQL Schema and could cause breaking changes.', 'wp-graphql-acf' ),
			'conditions'    => [],
		];
		return $admin_fields;
	}

	/**
	 * @param \WPGraphQL\Acf\FieldConfig         $field_config
	 * @param \WPGraphQL\Acf\AcfGraphQLFieldType $acf_field_type
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function get_graphql_type( FieldConfig $field_config, AcfGraphQLFieldType $acf_field_type ): string {
		$acf_field = $field_config->get_acf_field();

		$connection_type = $acf_field['graphql_connection_type'] ?? 'one_to_many';
		$is_one_to_one   = 'one_to_one' === $connection_type;

		$connection_config = [
			'toType'   => 'ContentNode',
			'oneToOne' => $is_one_to_one,
			'resolve'  => static function ( $root, $args, AppContext $context, $info ) use ( $field_config, $is_one_to_one ) {
				$value = $field_config->resolve_field( $root, $args, $context, $info );

				$ids = [];

				if ( empty( $value ) ) {
					return null;
				}

				if ( ! is_array( $value ) ) {
					$ids[] = $value;
				} else {
					$ids = $value;
				}

				$ids = array_filter(
					array_map(
						static function ( $id ) {
							if ( is_object( $id ) && isset( $id->ID ) ) {
								$id = $id->ID;
							}
							// filter out values that are not IDs
							// this means that external urls or urls to things like
							// archive links will not resolve.
							return absint( $id ) ?: null;
						},
						$ids
					) 
				);

				$resolver = new PostObjectConnectionResolver( $root, $args, $context, $info, 'any' );


				if ( $is_one_to_one ) {
					$resolver = $resolver->one_to_one();
				}

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
	}

	/**
	 * @return void
	 */
	public static function register_field_type():void {
		register_graphql_acf_field_type(
			'relationship',
			[
				'exclude_admin_fields' => [ 'graphql_non_null' ],
				'admin_fields'         => static function ( $admin_fields, $field, $config, \WPGraphQL\Acf\Admin\Settings $settings ): array {
					return self::get_admin_fields( $admin_fields, $field, $config, $settings );
				},
				'graphql_type'         => static function ( FieldConfig $field_config, AcfGraphQLFieldType $acf_field_type ) {
					return self::get_graphql_type( $field_config, $acf_field_type );
				},
			]
		);
	}
}

<?php
namespace WPGraphQL\Acf\FieldType;

use WPGraphQL\Acf\AcfGraphQLFieldType;
use WPGraphQL\Acf\FieldConfig;
use WPGraphQL\AppContext;
use WPGraphQL\Model\Post;

class PageLink {

	/**
	 * Register support for the "page_link" ACF field type
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type(
			'page_link',
			[
				'exclude_admin_fields' => [ 'graphql_non_null' ],
				'admin_fields'         => static function ( $admin_fields, $field, $config, \WPGraphQL\Acf\Admin\Settings $settings ): array {
					$admin_fields[] = [
						'type'          => 'select',
						'name'          => 'graphql_resolve_as',
						'label'         => __( 'Resolve As', 'wpgraphql-acf' ),
						'choices'       => [
							'single' => __( 'String', 'wpgraphql-acf' ),
							'list'   => __( 'List of Strings', 'wpgraphql-acf' ),
						],
						'default_value' => 'list',
						'instructions'  => __( 'Select whether the field should be presented in the schema as a List of Strings that can return 0, 1 or more URLs, or a String that will return 1 URL or null. Changing this field will change the GraphQL Schema and could cause breaking changes.', 'wpgraphql-acf' ),
						'conditions'    => [],
					];

					return $admin_fields;
				},
				'graphql_type'         => static function ( FieldConfig $field_config, AcfGraphQLFieldType $acf_field_type ) {
					$acf_field    = $field_config->get_acf_field();
					$resolve_type = $acf_field['graphql_resolve_as'] ?? 'list';
					if ( 'single' === $resolve_type ) {
						return 'String';
					}
					return [ 'list_of' => 'String' ];
				},
				'resolve'              => static function ( $root, $args, AppContext $context, $info, $field_type, FieldConfig $field_config ) {
					$value        = $field_config->resolve_field( $root, $args, $context, $info );
					$acf_field    = $field_config->get_acf_field();
					$resolve_type = $acf_field['graphql_resolve_as'] ?? 'list';

					$urls = [];

					if ( is_array( $value ) ) {
						$urls = array_filter(
							array_map(
								static function ( $v ) {
									if ( is_numeric( $v ) ) {
										$post       = get_post( absint( $v ) );
										$post_model = $post instanceof \WP_Post ? new Post( $post ) : null;
										$v          = $post_model instanceof Post ? $post_model->link : null;
									}
									return self::get_relative_url( $v );
								},
								$value
							)
						);
					} elseif ( is_numeric( $value ) ) {
						$post       = get_post( absint( $value ) );
						$post_model = $post instanceof \WP_Post ? new Post( $post ) : null;
						$link       = $post_model instanceof Post ? $post_model->link : null;
						$urls[]     = self::get_relative_url( $link );
					} else {
						$urls[] = self::get_relative_url( $value );
					}

					if ( 'single' === $resolve_type ) {
						return $urls[0] ?? null;
					}

					return $urls;
				},
			]
		);
	}

	/**
	 * Get the relative URL from a full URL
	 *
	 * @param string|null $url The full URL
	 * @return string|null The relative URL or null if the URL is empty
	 */
	private static function get_relative_url( ?string $url ): ?string {
		if ( home_url() === $url ) {
			return '/';
		}
		return $url ? str_replace( home_url(), '', $url ) : null;
	}
}

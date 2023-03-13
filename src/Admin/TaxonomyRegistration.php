<?php

namespace WPGraphQLAcf\Admin;

use WPGraphQL\Utils\Utils;

class TaxonomyRegistration {

	/**
	 * @return void
	 */
	public function init(): void {

		// Add registration fields to the ACF Taxonomy output for exporting / saving as PHP
		add_filter( 'acf/taxonomy_args', [ $this, 'add_taxonomy_registration_fields' ], 10, 2 );

		// Add tha GraphQL Tab to the ACF Taxonomy registration screen
		add_filter( 'acf/taxonomy/additional_settings_tabs', [ $this, 'add_tabs' ] );

		// Render the graphql settings tab in the ACF Taxonomy registration screen
		add_action( 'acf/taxonomy/render_settings_tab/graphql', [ $this, 'render_settings_tab' ] );

		// Enqueue the scripts for the CPT registration screen to help with setting default values / validation
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ], 10, 1 );

	}

	/**
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function add_tabs( array $tabs ): array {
		$tabs['graphql'] = __( 'GraphQL', 'wp-graphql-acf' );
		return $tabs;
	}

	/**
	 * @param array $acf_taxonomy
	 *
	 * @return void
	 */
	public function render_settings_tab( array $acf_taxonomy ): void {

		// @phpstan-ignore-next-line
		acf_render_field_wrap(
			[
				'type'         => 'true_false',
				'name'         => 'show_in_graphql',
				'key'          => 'show_in_graphql',
				'prefix'       => 'acf_taxonomy',
				'value'        => isset( $acf_taxonomy['show_in_graphql'] ) && (bool) $acf_taxonomy['show_in_graphql'],
				'ui'           => true,
				'label'        => __( 'Show in GraphQL', 'wp-graphql-acf' ),
				'instructions' => __( 'Whether to show the Taxonomy in the WPGraphQL Schema.', 'wp-graphql-acf' ),
				'default'      => false,
			]
		);

		$graphql_single_name = $acf_taxonomy['graphql_single_name'] ?? '';

		if ( empty( $graphql_single_name ) ) {
			$graphql_single_name = ! empty( $acf_taxonomy['labels']['singular_name'] ) ? Utils::format_field_name( $acf_taxonomy['labels']['singular_name'], true ) : '';
		}

		$graphql_single_name = Utils::format_field_name( $graphql_single_name, true );

		// @phpstan-ignore-next-line
		acf_render_field_wrap(
			[
				'type'         => 'text',
				'name'         => 'graphql_single_name',
				'key'          => 'graphql_single_name',
				'prefix'       => 'acf_taxonomy',
				'value'        => $graphql_single_name,
				'label'        => __( 'GraphQL Single Name', 'wp-graphql-acf' ),
				'instructions' => __( 'How the type should be referenced in the GraphQL Schema.', 'wp-graphql-acf' ),
				'default'      => $graphql_single_name,
				'conditions'   => [
					'field'    => 'show_in_graphql',
					'operator' => '==',
					'value'    => '1',
				],
			],
			'div',
			'field'
		);

		$graphql_plural_name = $acf_taxonomy['graphql_plural_name'] ?? '';

		if ( empty( $graphql_plural_name ) ) {
			$graphql_plural_name = ! empty( $acf_taxonomy['labels']['name'] ) ? Utils::format_field_name( $acf_taxonomy['labels']['name'], true ) : '';
		}

		$graphql_plural_name = Utils::format_field_name( $graphql_plural_name, true );

		// @phpstan-ignore-next-line
		acf_render_field_wrap(
			[
				'type'         => 'text',
				'name'         => 'graphql_plural_name',
				'key'          => 'graphql_plural_name',
				'prefix'       => 'acf_taxonomy',
				'value'        => $graphql_plural_name,
				'label'        => __( 'GraphQL Plural Name', 'wp-graphql-acf' ),
				'instructions' => __( 'How the type should be referenced in the GraphQL Schema.', 'wp-graphql-acf' ),
				'default'      => $graphql_plural_name,
				'conditions'   => [
					'field'    => 'show_in_graphql',
					'operator' => '==',
					'value'    => '1',
				],
			],
			'div',
			'field'
		);

	}

	/**
	 * @param array $args
	 * @param array $taxonomy
	 *
	 * @return array
	 */
	public function add_taxonomy_registration_fields( array $args, array $taxonomy ): array {

		// respect the show_in_graphql value. If not set, use the value of $args['public'] to determine if the post type should be shown in graphql
		$args['show_in_graphql'] = isset( $args['show_in_graphql'] ) ? (bool) $args['show_in_graphql'] : true === $args['public'];

		$graphql_single_name = '';

		if ( isset( $args['graphql_single_name'] ) ) {
			$graphql_single_name = $args['graphql_single_name'];
		} elseif ( isset( $args['labels']['singular_name'] ) ) {
			$graphql_single_name = Utils::format_field_name( $args['labels']['singular_name'], true );
		}

		// if a graphql_single_name exists, use it, otherwise use the formatted version of the singular_name label
		$args['graphql_single_name'] = $graphql_single_name;

		$graphql_plural_name = '';

		if ( isset( $args['graphql_plural_name'] ) ) {
			$graphql_plural_name = $args['graphql_plural_name'];
		} elseif ( isset( $args['labels']['name'] ) ) {
			$graphql_plural_name = Utils::format_field_name( $args['labels']['name'], true );
		}

		// if the plural name exists, use it. Otherwise use the formatted version of the name.
		$args['graphql_plural_name'] = $graphql_plural_name;

		return $args;
	}

	/**
	 * @param string $screen
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts( string $screen ): void {

		global $post;

		// if the screen is not a new post / edit post screen, do nothing
		if ( ! ( 'post-new.php' === $screen || 'post.php' === $screen ) ) {
			return;
		}

		// if the global post is not set, or the post type is not "acf-taxonomy", do nothing
		if ( ! isset( $post->post_type ) || 'acf-taxonomy' !==  $post->post_type ) {
			return;
		}

		wp_enqueue_script( 'graphql-acf-taxonomy',
			plugins_url( '/assets/admin/js/taxonomy-settings.js', __DIR__ ),
			[
				'acf-internal-post-type',
			],
			WPGRAPHQL_FOR_ACF_VERSION,
			true
		);

	}

}

<?php

namespace WPGraphQLAcf\Admin;

use WPGraphQL\Utils\Utils;

class PostTypeRegistration {

	/**
	 * @return void
	 */
	public function init(): void {

		// Add registration fields to the ACF Post Type output for exporting / saving as PHP
		add_filter( 'acf/post_type_args', [ $this, 'add_cpt_registration_fields' ], 10, 2 );

		// Add tha GraphQL Tab to the ACF Post Type registration screen
		add_filter( 'acf/post_type/additional_settings_tabs', [ $this, 'add_tabs' ] );

		// Render the graphql settings tab in the ACF post type registration screen
		add_action( 'acf/post_type/render_settings_tab/graphql', [ $this, 'render_settings_tab' ] );

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
	 * @param array $acf_post_type
	 *
	 * @return void
	 */
	public function render_settings_tab( array $acf_post_type ): void {

		// @phpstan-ignore-next-line
		acf_render_field_wrap(
			[
				'type'         => 'true_false',
				'name'         => 'show_in_graphql',
				'key'          => 'show_in_graphql',
				'prefix'       => 'acf_post_type',
				'value'        => isset( $acf_post_type['show_in_graphql'] ) && (bool) $acf_post_type['show_in_graphql'],
				'ui'           => true,
				'label'        => __( 'Show in GraphQL', 'wp-graphql-acf' ),
				'instructions' => __( 'Whether to show the Post Type in the WPGraphQL Schema.', 'wp-graphql-acf' ),
				'default'      => false,
			]
		);

		$graphql_single_name = $acf_post_type['graphql_single_name'] ?? '';

		if ( empty( $graphql_single_name ) ) {
			$graphql_single_name = ! empty( $acf_post_type['labels']['singular_name'] ) ? Utils::format_field_name( $acf_post_type['labels']['singular_name'], true ) : '';
		}

		$graphql_single_name = Utils::format_field_name( $graphql_single_name, true );

		// @phpstan-ignore-next-line
		acf_render_field_wrap(
			[
				'type'         => 'text',
				'name'         => 'graphql_single_name',
				'key'          => 'graphql_single_name',
				'prefix'       => 'acf_post_type',
				'value'        => $graphql_single_name,
				'label'        => __( 'GraphQL Single Name', 'wp-graphql-acf' ),
				'instructions' => __( 'How the type should be referenced in the GraphQL Schema.', 'wp-graphql-acf' ),
				'default'      => $graphql_single_name,
				'required'     => 1,
				'conditions'   => [
					'field'    => 'show_in_graphql',
					'operator' => '==',
					'value'    => '1',
				],
			],
			'div',
			'field'
		);

		$graphql_plural_name = $acf_post_type['graphql_plural_name'] ?? '';

		if ( empty( $graphql_plural_name ) ) {
			$graphql_plural_name = ! empty( $acf_post_type['labels']['name'] ) ? Utils::format_field_name( $acf_post_type['labels']['name'], true ) : '';
		}

		$graphql_plural_name = Utils::format_field_name( $graphql_plural_name, true );

		// @phpstan-ignore-next-line
		acf_render_field_wrap(
			[
				'type'         => 'text',
				'name'         => 'graphql_plural_name',
				'key'          => 'graphql_plural_name',
				'prefix'       => 'acf_post_type',
				'value'        => $graphql_plural_name,
				'label'        => __( 'GraphQL Plural Name', 'wp-graphql-acf' ),
				'instructions' => __( 'How the type should be referenced in the GraphQL Schema.', 'wp-graphql-acf' ),
				'default'      => $graphql_plural_name,
				'required'     => 1,
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
	 * @param array $post_type
	 *
	 * @return array
	 */
	public function add_cpt_registration_fields( array $args, array $post_type ): array {

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

		// if the plural name exists, use it. Otherwie use the formatted version of the name.
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

		// if the global post is not set, or the post type is not "acf-post-type", do nothing
		if ( ! isset( $post->post_type ) || 'acf-post-type' !==  $post->post_type ) {
			return;
		}

		wp_enqueue_script( 'graphql-acf-post-type',
			plugins_url( '/assets/admin/js/post-type-settings.js', __DIR__ ),
			[
				'acf-internal-post-type',
			],
			WPGRAPHQL_FOR_ACF_VERSION,
			true
		);

	}

}

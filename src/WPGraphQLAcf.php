<?php

use WPGraphQL\Registry\TypeRegistry;
use WPGraphQL\Utils\Utils;
use WPGraphQLAcf\Admin\Settings;
use WPGraphQLAcf\Registry;

class WPGraphQLAcf {

	/**
	 * @var Settings
	 */
	protected $admin_settings;

	/**
	 * @return void
	 */
	public function init(): void {

		add_action( 'admin_init', [ $this, 'init_admin_settings' ] );
		add_action( 'graphql_register_types', [ $this, 'init_registry' ] );
		add_filter( 'acf/post_type_args', function ( array $args, array $post_type ) {

			// respect the show_in_graphql value. If not set, use the value of $args['public'] to determine if the post type should be shown in graphql
			$args['show_in_graphql'] = isset( $args['show_in_graphql'] ) ? (bool) $args['show_in_graphql'] : true === $args['public'];

			$graphql_single_name = '';

			if ( isset( $args['graphql_single_name'] ) ) {
				$graphql_single_name = $args['graphql_single_name'];
			} elseif ( isset( $args['labels']['singular_name'] ) ) {
				// @phpstan-ignore-next-line
				$graphql_single_name = Utils::format_field_name( $args['labels']['singular_name'], true );
			}

			// if a graphql_single_name exists, use it, otherwise use the formatted version of the singular_name label
			$args['graphql_single_name'] = $graphql_single_name;

			$graphql_plural_name = '';

			if ( isset( $args['graphql_plural_name'] ) ) {
				$graphql_plural_name = $args['graphql_plural_name'];
			} elseif ( isset( $args['labels']['name'] ) ) {
				// @phpstan-ignore-next-line
				$graphql_plural_name = Utils::format_field_name( $args['labels']['name'], true );
			}

			// if the plural name exists, use it. Otherwie use the formatted version of the name.
			$args['graphql_plural_name'] = $graphql_plural_name;

			return $args;

		}, 10, 2);

		add_filter( 'acf/taxonomy_args', function ( array $args, array $taxonomy ) {

			// respect the show_in_graphql value. If not set, use the value of $args['public'] to determine if the post type should be shown in graphql
			$args['show_in_graphql'] = isset( $args['show_in_graphql'] ) ? (bool) $args['show_in_graphql'] : true === $args['public'];

			$graphql_single_name = '';

			if ( isset( $args['graphql_single_name'] ) ) {
				$graphql_single_name = $args['graphql_single_name'];
			} elseif ( isset( $args['labels']['singular_name'] ) ) {
				// @phpstan-ignore-next-line
				$graphql_single_name = Utils::format_field_name( $args['labels']['singular_name'], true );
			}

			// if a graphql_single_name exists, use it, otherwise use the formatted version of the singular_name label
			$args['graphql_single_name'] = $graphql_single_name;

			$graphql_plural_name = '';

			if ( isset( $args['graphql_plural_name'] ) ) {
				$graphql_plural_name = $args['graphql_plural_name'];
			} elseif ( isset( $args['labels']['name'] ) ) {
				// @phpstan-ignore-next-line
				$graphql_plural_name = Utils::format_field_name( $args['labels']['name'], true );
			}

			// if the plural name exists, use it. Otherwise use the formatted version of the name.
			$args['graphql_plural_name'] = $graphql_plural_name;

			return $args;

		}, 10, 2);

		add_filter( 'acf/post_type/additional_settings_tabs', function ( $tabs ) {
			$tabs['graphql'] = __( 'GraphQL', 'wp-graphql-acf' );
			return $tabs;
		});

		add_filter( 'acf/taxonomy/additional_settings_tabs', function ( $tabs ) {
			$tabs['graphql'] = __( 'GraphQL', 'wp-graphql-acf' );
			return $tabs;
		});

		add_action( 'acf/post_type/render_settings_tab/graphql', function ( $acf_post_type ) {

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

		} );

		add_action( 'acf/taxonomy/render_settings_tab/graphql', function ( $acf_taxonomy ) {

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

		} );

	}

	/**
	 * @return void
	 */
	public function init_admin_settings(): void {
		$this->admin_settings = new WPGraphQLAcf\Admin\Settings();
		$this->admin_settings->init();
	}

	/**
	 * @param TypeRegistry $type_registry
	 *
	 * @return void
	 * @throws Exception
	 */
	public function init_registry( TypeRegistry $type_registry ): void {

		// Register general types that should be available to the Schema regardless
		// of the specific fields and field groups registered by ACF
		$registry = new Registry( $type_registry );
		$registry->register_initial_graphql_types();

		// Get the field groups that should be mapped to the Schema
		$acf_field_groups = $registry->get_acf_field_groups();

		// If there are no acf field groups to show in GraphQL, do nothing
		if ( empty( $acf_field_groups ) ) {
			return;
		}

		$registry->register_acf_field_groups_to_graphql( $acf_field_groups );

	}

}

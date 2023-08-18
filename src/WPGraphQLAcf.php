<?php

use WPGraphQL\Registry\TypeRegistry;

use WPGraphQL\Acf\Admin\PostTypeRegistration;
use WPGraphQL\Acf\Admin\TaxonomyRegistration;
use WPGraphQL\Acf\Registry;
use WPGraphQL\Acf\ThirdParty;

class WPGraphQLAcf {

	/**
	 * @var \WPGraphQL\Acf\Admin\Settings
	 */
	protected $admin_settings;

	/**
	 * @var array
	 */
	protected $plugin_load_error_messages = [];

	/**
	 * @return void
	 */
	public function init(): void {

		// If there are any plugin load error messages,
		// prevent the plugin from loading and show the messages
		if ( ! empty( $this->get_plugin_load_error_messages() ) ) {
			add_action( 'admin_init', [ $this, 'show_admin_notice' ] );
			add_action( 'graphql_init', [ $this, 'show_graphql_debug_messages' ] );
			return;
		}

		add_action( 'wpgraphql/acf/init', [ $this, 'init_third_party_support' ] );
		add_action( 'admin_init', [ $this, 'init_admin_settings' ] );
		add_action( 'after_setup_theme', [ $this, 'cpt_tax_registration' ] );
		add_action( 'graphql_register_types', [ $this, 'init_registry' ] );

		add_filter( 'graphql_data_loaders', [ $this, 'register_loaders' ], 10, 2 );
		add_filter( 'graphql_resolve_node_type', [ $this, 'resolve_acf_options_page_node' ], 10, 2 );

		do_action( 'wpgraphql/acf/init' );

	}

	/**
	 * @return void
	 */
	public function init_third_party_support(): void {
		$third_party = new ThirdParty();
		$third_party->init();
	}

	/**
	 * @return void
	 */
	public function init_admin_settings(): void {
		$this->admin_settings = new WPGraphQL\Acf\Admin\Settings();
		$this->admin_settings->init();


	}

	/**
	 * Add functionality to the Custom Post Type and Custom Taxonomy registration screens
	 * and underlying functionality (like exports, php code generation)
	 *
	 * @return void
	 */
	public function cpt_tax_registration(): void {
		$taxonomy_registration_screen = new TaxonomyRegistration();
		$taxonomy_registration_screen->init();

		$cpt_registration_screen = new PostTypeRegistration();
		$cpt_registration_screen->init();
	}

	/**
	 * @param \WPGraphQL\Registry\TypeRegistry $type_registry
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function init_registry( TypeRegistry $type_registry ): void {

		// Register general types that should be available to the Schema regardless
		// of the specific fields and field groups registered by ACF
		$registry = new Registry( $type_registry );
		$registry->register_initial_graphql_types();
		$registry->register_options_pages();
		$registry->register_blocks();

		// Get the field groups that should be mapped to the Schema
		$acf_field_groups = $registry->get_acf_field_groups();

		// If there are no acf field groups to show in GraphQL, do nothing
		if ( empty( $acf_field_groups ) ) {
			return;
		}

		$registry->register_acf_field_groups_to_graphql( $acf_field_groups );

	}

	/**
	 * Empty array if the plugin can load. Array of messages if the plugin cannot load.
	 *
	 * @return array
	 */
	public function get_plugin_load_error_messages(): array {

		if ( ! empty( $this->plugin_load_error_messages ) ) {
			return $this->plugin_load_error_messages;
		}

		// Is ACF active?
		if ( ! class_exists( 'ACF' ) ) {
			$this->plugin_load_error_messages[] = __( 'Advanced Custom Fields must be installed and activated', 'wp-graphql-acf' );
		}

		if ( class_exists( 'WPGraphQL\ACF\ACF' ) ) {
			$this->plugin_load_error_messages[] = __( 'Multiple versions of WPGraphQL for ACF cannot be active at the same time', 'wp-graphql-acf' );
		}

		// Have we met the minimum version requirement?
		if ( ! class_exists( 'WPGraphQL' ) || ! defined( 'WPGRAPHQL_VERSION' ) || empty( WPGRAPHQL_VERSION ) || true === version_compare( WPGRAPHQL_VERSION, WPGRAPHQL_FOR_ACF_VERSION_WPGRAPHQL_REQUIRED_MIN_VERSION, 'lt' ) ) {
			$this->plugin_load_error_messages[] = sprintf( __( 'WPGraphQL v%s or higher is required to be installed and active', 'wp-graphql-acf' ), WPGRAPHQL_FOR_ACF_VERSION_WPGRAPHQL_REQUIRED_MIN_VERSION );
		}

		return $this->plugin_load_error_messages;

	}

	/**
	 * Show admin notice to admins if this plugin is active but either ACF and/or WPGraphQL
	 * are not active
	 *
	 * @return void
	 */
	public function show_admin_notice(): void {

		$can_load_messages = $this->get_plugin_load_error_messages();

		/**
		 * For users with lower capabilities, don't show the notice
		 */
		if ( empty( $can_load_messages ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_action(
			'admin_notices',
			function () use ( $can_load_messages ) {
				?>
				<div class="error notice">
					<h3><?php echo esc_html( sprintf( __( 'WPGraphQL for Advanced Custom Fields v%s cannot load', 'wp-graphql-acf' ), WPGRAPHQL_FOR_ACF_VERSION ) ); ?></h3>
					<ol>
						<?php foreach ( $can_load_messages as $message ) : ?>
							<li><?php echo esc_html( $message ); ?></li>
						<?php endforeach; ?>
					</ol>
				</div>
				<?php
			}
		);
	}

	/**
	 * @param mixed $type The GraphQL Type to return based on the resolving node
	 * @param mixed $node The Node being resolved
	 *
	 * @return mixed
	 */
	public function resolve_acf_options_page_node( $type, $node ) {
		if ( $node instanceof \WPGraphQL\Acf\Model\AcfOptionsPage ) {
			return \WPGraphQL\Acf\Utils::get_field_group_name( $node->get_data() );
		}
		return $type;
	}

	/**
	 * @param array                 $loaders
	 * @param \WPGraphQL\AppContext $context
	 *
	 * @return array
	 */
	public function register_loaders( array $loaders, \WPGraphQL\AppContext $context ): array {
		$loaders['acf_options_page'] = new \WPGraphQL\Acf\Data\Loader\AcfOptionsPageLoader( $context );
		return $loaders;
	}


	/**
	 * Output graphql debug messages if the plugin cannot load properly.
	 *
	 * @return void
	 */
	public function show_graphql_debug_messages(): void {

		$messages = $this->get_plugin_load_error_messages();

		if ( empty( $messages ) ) {
			return;
		}

		$prefix = sprintf( 'WPGraphQL for Advanced Custom Fields v%s cannot load', WPGRAPHQL_FOR_ACF_VERSION );
		foreach ( $messages as $message ) {
			graphql_debug( $prefix . ' because ' . $message );
		}
	}

}

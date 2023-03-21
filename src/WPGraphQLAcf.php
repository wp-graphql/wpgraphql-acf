<?php

use WPGraphQL\Registry\TypeRegistry;
use WPGraphQL\Utils\Utils;
use WPGraphQLAcf\Admin\PostTypeRegistration;
use WPGraphQLAcf\Admin\Settings;
use WPGraphQLAcf\Admin\TaxonomyRegistration;
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
		add_action( 'after_setup_theme', [ $this, 'cpt_tax_registration' ] );
		add_action( 'graphql_register_types', [ $this, 'init_registry' ] );

	}

	/**
	 * @return void
	 */
	public function init_admin_settings(): void {
		$this->admin_settings = new WPGraphQLAcf\Admin\Settings();
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

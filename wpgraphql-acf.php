<?php
/**
 * Plugin Name: WPGraphQL for ACF
 * Description: Re-imagining the WPGraphQL for ACF plugin
 * Author: WPGraphQL, Jason Bahl
 * Author URI: https://www.wpgraphql.com
 * Version: 0.2.1-beta
 * Text Domain: wpgraphql-acf
 * Requires PHP: 7.3
 * Requires at least: 5.9
 * Tested up to: 6.1
 * License: GPL-3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPGraphQLAcf' ) ) {
	require_once __DIR__ . '/src/WPGraphQLAcf.php';
}

// If this file doesn't exist, the plugin was likely installed from Composer
// and the autoloader is included in the parent project
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! defined( 'WPGRAPHQL_FOR_ACF_VERSION' ) ) {
	define( 'WPGRAPHQL_FOR_ACF_VERSION', '0.2.0-beta' );
}

if ( ! defined( 'WPGRAPHQL_FOR_ACF_VERSION_WPGRAPHQL_REQUIRED_MIN_VERSION' ) ) {
	define( 'WPGRAPHQL_FOR_ACF_VERSION_WPGRAPHQL_REQUIRED_MIN_VERSION', '1.14.0' );
}

if ( ! defined( 'WPGRAPHQL_FOR_ACF_VERSION_PLUGIN_DIR' ) ) {
	define( 'WPGRAPHQL_FOR_ACF_VERSION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! function_exists( 'graphql_acf_init' ) ) {
	/**
	 * Function that instantiates the plugins main class
	 *
	 * @return void
	 */
	function graphql_acf_init() {
		$wp_graphql_acf = new \WPGraphQLAcf();
		add_action( 'plugins_loaded', [ $wp_graphql_acf, 'init' ] );
	}
}
graphql_acf_init();

add_action( 'graphql_acf_init', function () {

	// Registers the field type to show in the GraphQL Schema
	register_graphql_acf_field_type( 'post_object' );
	register_graphql_acf_field_type( 'page_link' );
	register_graphql_acf_field_type( 'relationship' );
	register_graphql_acf_field_type( 'taxonomy' );
	register_graphql_acf_field_type( 'repeater' );
	register_graphql_acf_field_type( 'flexible_content' );
	register_graphql_acf_field_type( 'clone', [
		'exclude_admin_fields' => [ 'show_in_graphql', 'graphql_field_name', 'graphql_description' ],
		'admin_fields'         => function ( $field, $config, \WPGraphQLAcf\Admin\Settings $settings ) {
			return [
				'graphql_clone' => [
					'admin_field' => [
						'type'         => 'message',
						'label'        => __( 'GraphQL Settings for Clone Fields', 'wp-graphql-acf' ),
						'instructions' => __( 'Clone Fields will inherit their GraphQL settings from the field(s) being cloned. If all Fields from a Field Group are cloned, an Interface representing the cloned field Group will be applied to this field group.', 'wp-graphql-acf' ),
						'conditions'   => [],
					],
				],
			];
		},
	] );

	// This field type is added support some legacy features of ACF versions lower than v6.1
	if ( ! defined( 'ACF_MAJOR_VERSION' ) || version_compare( ACF_MAJOR_VERSION, '6.1', '<=' ) ) {
		register_graphql_acf_field_type( '<6.1' );
	}

});

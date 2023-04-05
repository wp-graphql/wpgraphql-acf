<?php
/**
 * Plugin Name: WPGraphQL for Advanced Custom Fields
 * Description: Re-imagining the WPGraphQL for ACF plugin
 * Author: WPGraphQL, Jason Bahl
 * Author URI: https://www.wpgraphql.com
 * Version: 2.0.0-beta.2.0.5
 * Text Domain: wp-graphql-acf
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
	define( 'WPGRAPHQL_FOR_ACF_VERSION', '2.0.0-beta.2.0.5' );
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

		if ( ! empty( get_plugin_load_error_messages() ) ) {
			add_action( 'admin_init', __NAMESPACE__ . '\show_admin_notice' );
			add_action( 'graphql_init', __NAMESPACE__ . '\show_graphql_debug_messages' );
			return;
		}

		$wp_graphql_acf = new \WPGraphQLAcf();
		add_action( 'init', [ $wp_graphql_acf, 'init' ], 11 );
	}
}
graphql_acf_init();

/**
 * Empty array if the plugin can load. Array of messages if the plugin cannot load.
 *
 * @return array
 */
function get_plugin_load_error_messages(): array {

	$messages = [];

	// Is ACF active?
	if ( ! class_exists( 'ACF' ) ) {
		$messages[] = __( 'Advanced Custom Fields must be installed and activated', 'wp-graphql-acf' );
	}

	if ( class_exists( 'WPGraphQL\ACF\ACF' ) ) {
		$messages[] = __( 'Multiple versions of WPGraphQL for ACF cannot be active at the same time.', 'wp-graphql-acf' );
	}

	// Is WPGraphQL active?
	if ( ! class_exists( 'WPGraphQL' ) ) {
		$messages[] = __( 'WPGraphQL must be installed and activated.', 'wp-graphql-acf' );
	}

	// Have we met the minimum version requirement?
	if ( ! defined( 'WPGRAPHQL_VERSION' ) || empty( WPGRAPHQL_VERSION ) || true === version_compare( WPGRAPHQL_VERSION, WPGRAPHQL_FOR_ACF_VERSION_WPGRAPHQL_REQUIRED_MIN_VERSION, 'lt' ) ) {
		$messages[] = sprintf( __( 'WPGraphQL v%s or higher is required.', 'wp-graphql-acf' ), WPGRAPHQL_FOR_ACF_VERSION_WPGRAPHQL_REQUIRED_MIN_VERSION );
	}

	return $messages;

}

/**
 * Show admin notice to admins if this plugin is active but either ACF and/or WPGraphQL
 * are not active
 *
 * @return void
 */
function show_admin_notice(): void {

	$can_load_messages = get_plugin_load_error_messages();

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
 * Output graphql debug messages if the plugin cannot load properly.
 *
 * @return void
 */
function show_graphql_debug_messages(): void {

	$messages = get_plugin_load_error_messages();

	if ( empty( $messages ) ) {
		return;
	}

	$prefix = sprintf( 'WPGraphQL for Advanced Custom Fields v%s cannot load', WPGRAPHQL_FOR_ACF_VERSION );
	foreach ( $messages as $message ) {
		graphql_debug( $prefix . ' because ' . $message );
	}
}

/**
 * Initialize the plugin tracker
 *
 * @return void
 */
function graphql_acf_init_appsero_telemetry() {
	// If the class doesn't exist, or code is being scanned by PHPSTAN, move on.
	if ( ! class_exists( 'Appsero\Client' ) || defined( 'PHPSTAN' ) ) {
		return;
	}

	$client   = new Appsero\Client( '4988d797-77ee-4201-84ce-1d610379f843', 'WPGraphQL for Advanced Custom Fields', __FILE__ );
	$insights = $client->insights();

	// If the Appsero client has the add_plugin_data method, use it
	if ( method_exists( $insights, 'add_plugin_data' ) ) {
		// @phpstan-ignore-next-line
		$insights->add_plugin_data();
	}

	// @phpstan-ignore-next-line
	$insights->init();
}

graphql_acf_init_appsero_telemetry();

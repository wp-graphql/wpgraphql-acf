<?php

namespace WPGraphQLAcf\ThirdParty\WPGraphQLSmartCache;

use WPGraphQL\SmartCache\Cache\Invalidation;

class WPGraphQLSmartCache {

	/**
	 * @var Invalidation
	 */
	protected $invalidation;

	/**
	 * @return void
	 */
	public function init():void {

		/**
		 * Add support for WPGraphQL Smart Cache invalidation for ACF Option Pages
		 */
		add_action( 'graphql_cache_invalidation_init', [ $this, 'initialize_cache_invalidation' ], 10, 1 );

	}

	/**
	 * @param \WPGraphQL\SmartCache\Cache\Invalidation $invalidation
	 *
	 * @return void
	 */
	public function initialize_cache_invalidation( \WPGraphQL\SmartCache\Cache\Invalidation $invalidation ) {

			$this->invalidation = $invalidation;

			add_action( 'updated_option', [ $this, 'updated_acf_option_cb' ], 10, 4 );

	}

	/**
	 * Purge Cache after ACF Option Page is updated
	 *
	 * @param string $option The name of the option being updated
	 * @param mixed $value The value of the option being updated
	 * @param mixed $original_value The original / previous value of the option
	 *
	 * @return void
	 */
	public function updated_acf_option_cb( string $option, $value, $original_value ): void {

		if ( ! isset( $_POST['_acf_screen'] ) || 'options' !== $_POST['_acf_screen' ] ) {
			return;
		}

		$options_page = $_GET['page'] ?? null;

		if ( empty( $options_page ) ) {
			return;
		}

		$id = \GraphQLRelay\Relay::toGlobalId( 'acf_options_page', $options_page );

		$this->invalidation->purge( $id, sprintf( 'update_acf_options_page ( "%s" )', $options_page ) );

	}

}

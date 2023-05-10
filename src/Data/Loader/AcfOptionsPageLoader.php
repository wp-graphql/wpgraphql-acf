<?php
namespace WPGraphQLAcf\Data\Loader;

use WPGraphQL\Data\Loader\AbstractDataLoader;

class AcfOptionsPageLoader extends AbstractDataLoader {

	/**
	 * @param array $keys
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function loadKeys( array $keys ): array {
		if ( empty( $keys ) || ! function_exists( 'acf_get_options_pages' ) ) {
			return [];
		}

		$options_pages = acf_get_options_pages();

		if ( empty( $options_pages ) ) {
			return [];
		}

		$response = [];

		foreach ( $keys as $key ) {
			if  ( isset( $options_pages[ $key ] ) ) {
				$response[ $key ] = new \WPGraphQLAcf\Model\AcfOptionsPage(  $options_pages[ $key ] );
			}
		}

		return $response;

	}
}

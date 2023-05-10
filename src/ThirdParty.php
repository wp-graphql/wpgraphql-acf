<?php
namespace WPGraphQLAcf;

class ThirdParty {

	/**
	 * @return void
	 */
	public function init(): void {

		// initialize support for ACF Extended
		$acfe = new ThirdParty\AcfExtended\AcfExtended();
		$acfe->init();

		// Initialize support for WPGraphQL Smart Cache
		$smart_cache = new ThirdParty\WPGraphQLSmartCache\WPGraphQLSmartCache();
		$smart_cache->init();

	}

}

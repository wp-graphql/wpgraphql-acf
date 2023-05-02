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

	}

}

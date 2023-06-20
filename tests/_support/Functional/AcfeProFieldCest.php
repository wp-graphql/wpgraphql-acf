<?php

namespace Tests\WPGraphQL\Acf\Functional;

use FunctionalTester;

abstract class AcfeProFieldCest extends AcfFieldCest {

	public function _before( FunctionalTester $I ): void {
		$I->loginAsAdmin();
		$I->amOnPluginsPage();
		$active_plugins = $I->grabOptionFromDatabase( 'active_plugins' );
		codecept_debug( $active_plugins );

		// ACF Extended only works if ACF Pro is active
		if ( ! in_array( 'advanced-custom-fields-pro/acf.php', $active_plugins, true  ) ) {
			$I->markTestSkipped( 'ACF Pro is not active. ACF Extended features extend ACF PRO' );
		}

		parent::_before( $I ); // TODO: Change the autogenerated stub
	}

}

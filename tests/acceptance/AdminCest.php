<?php

class AdminCest
{
    public function _before(AcceptanceTester $I)
    {
    }

	public function seeFieldTypeHeaderTest( AcceptanceTester $I ) {
		$I->loginAsAdmin();
		$I->amOnPage('/wp-admin/edit.php?post_type=acf-field-group');

        $r = $I->grabTextFrom( "//thead/tr/th[@id='acf-wpgraphql-type']" );
        codecept_debug( $r );
		$I->see('WPGraphql Type', "//thead/tr/th[@id='acf-wpgraphql-type']");
		$I->see('WPGraphql Interface(s)', "//thead/tr/th[@id='acf-wpgraphql-interfaces']");
		$I->see('WPGraphql Location(s)', "//thead/tr/th[@id='acf-wpgraphql-locations']");

	}

}

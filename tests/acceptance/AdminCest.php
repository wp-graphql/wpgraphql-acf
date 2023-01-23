<?php

class AdminCest
{
    public function _before(AcceptanceTester $I)
    {
    }

	public function saveCacheTllExpirationTest( AcceptanceTester $I ) {
		$I->loginAsAdmin();
		$I->amOnPage('/wp-admin/admin.php?page=graphql-settings#graphql_cache_section');

		// Save and see the selection after form submit
		$I->fillField("//input[@type='number' and @name='graphql_cache_section[global_ttl]']", 30);
		$I->click('Save Changes');
		$I->seeInField("//input[@type='number' and @name='graphql_cache_section[global_ttl]']", 30);

		// Invalid value, negative, doesn't save.
		$I->fillField("//input[@type='number' and @name='graphql_cache_section[global_ttl]']", -1);
		$I->click('Save Changes');
		$I->seeInField("//input[@type='number' and @name='graphql_cache_section[global_ttl]']", '');

		// Invalid value, negative, doesn't save.
		$I->fillField("//input[@type='number' and @name='graphql_cache_section[global_ttl]']", 0);
		$I->click('Save Changes');
		$I->seeInField("//input[@type='number' and @name='graphql_cache_section[global_ttl]']", 0);
	}

}

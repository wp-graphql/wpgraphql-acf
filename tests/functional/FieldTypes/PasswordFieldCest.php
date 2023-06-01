<?php

class PasswordFieldCest extends \Tests\WPGraphQLAcf\Functional\AcfFieldCest {

	public function _getAcfFieldType(): string {
		return 'password';
	}

}

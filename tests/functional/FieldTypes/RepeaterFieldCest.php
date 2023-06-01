<?php


class RepeaterFieldCest extends \Tests\WPGraphQLAcf\Functional\AcfProFieldCest {

	public function _getAcfFieldType(): string {
		return 'repeater';
	}

}

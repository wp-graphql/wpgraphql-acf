<?php

class FlexibleContentFieldCest extends \Tests\WPGraphQLAcf\Functional\AcfProFieldCest {

	public function _getAcfFieldType(): string {
		return 'flexible_content';
	}

}

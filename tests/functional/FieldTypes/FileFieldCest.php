<?php

class FileFieldCest extends \Tests\WPGraphQLAcf\Functional\AcfFieldCest {

	public function _getAcfFieldType(): string {
		return 'file';
	}

}

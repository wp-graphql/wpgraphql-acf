<?php

class PostObjectFieldCest extends \Tests\WPGraphQLAcf\Functional\AcfFieldCest {

	public function _getAcfFieldType(): string {
		return 'post_object';
	}

}

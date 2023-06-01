<?php

class OembedFieldCest extends \Tests\WPGraphQLAcf\Functional\AcfFieldCest {

	public function _getAcfFieldType(): string {
		return 'oembed';
	}

}

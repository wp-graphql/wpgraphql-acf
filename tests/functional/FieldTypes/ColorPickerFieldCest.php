<?php

class ColorPickerFieldCest extends \Tests\WPGraphQLAcf\Functional\AcfFieldCest {

	public function _getAcfFieldType(): string {
		return 'color_picker';
	}

}

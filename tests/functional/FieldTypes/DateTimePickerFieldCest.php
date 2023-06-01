<?php

class DateTimePickerFieldCest extends \Tests\WPGraphQLAcf\Functional\AcfFieldCest {

	public function _getAcfFieldType(): string {
		return 'date_time_picker';
	}

}

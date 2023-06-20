<?php

namespace WPGraphQL\Acf\ThirdParty\AcfExtended\FieldType;

class AcfeAdvancedLink {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'acfe_advanced_link', [
			'graphql_type' => 'ACFE_AdvancedLink',
		]);
	}

}

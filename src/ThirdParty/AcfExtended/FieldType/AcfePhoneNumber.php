<?php

namespace WPGraphQL\Acf\ThirdParty\AcfExtended\FieldType;

class AcfePhoneNumber {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type( 'acfe_phone_number' );
	}

}

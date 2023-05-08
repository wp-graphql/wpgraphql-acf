<?php

namespace WPGraphQLAcf\ThirdParty\AcfExtended\FieldType;

class AcfeCodeEditor {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type( 'acfe_code_editor' );
	}

}

<?php

namespace WPGraphQLAcf\ThirdParty\AcfExtended\FieldType;

class AcfeMenuLocations {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type( 'acfe_menu_locations', [
			'graphql_type' => [ 'list_of' => 'MenuLocationEnum' ],
			'resolve'      => function ( $root, $args, $context, $info, $field_type, $field_config ) {
				$value = $field_config->resolve_field( $root, $args, $context, $info );

				if ( empty( $value ) ) {
					return null;
				}

				if ( ! is_array( $value ) ) {
					$value = [ $value ];
				}

				return $value;
			},
		] );
	}

}

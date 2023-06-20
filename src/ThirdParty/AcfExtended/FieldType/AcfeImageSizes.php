<?php

namespace WPGraphQL\Acf\ThirdParty\AcfExtended\FieldType;

class AcfeImageSizes {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'acfe_image_sizes', [
			'graphql_type' => [ 'list_of' => 'ACFE_Image_Size' ],
			'resolve'      => function ( $root, $args, $context, $info, $field_type, $field_config ) {
				$value = $field_config->resolve_field( $root, $args, $context, $info );

				if ( ! is_array( $value ) ) {
					$value = [ $value ];
				}

				if ( ! function_exists( 'acfe_get_registered_image_sizes' ) ) {
					return null;
				}

				return array_filter( array_map( static function ( $size ) {
					return acfe_get_registered_image_sizes( $size );
				}, $value ) );

			},
		] );
	}

}

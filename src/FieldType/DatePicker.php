<?php
namespace WPGraphQL\Acf\FieldType;

use WPGraphQL\Acf\FieldConfig;

class DatePicker {

	/**
	 * Register support for the "date_picker" ACF field type
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type(
			'date_picker',
			[
				'graphql_type' => 'String',
				'resolve'      => static function ( $root, $args, $context, $info, $field_type, FieldConfig $field_config ) {
					$value = $field_config->resolve_field( $root, $args, $context, $info );

					if ( empty( $value ) ) {
						return null;
					}

					$acf_field = $field_config->get_acf_field();

					// Get the return format from the ACF Field
					$return_format = $acf_field['return_format'] ?? null;

					if ( empty( $return_format ) ) {
						return $value;
					}

					$date_time = \DateTime::createFromFormat( $return_format . '|', $value );

					if ( empty( $date_time ) ) {
						return null;
					}

					// appending '|' to the format prevents the minutes and seconds from being determined from the current time
					return $date_time->format( \DateTimeInterface::RFC3339 );
				},
			]
		);
	}
}

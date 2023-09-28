<?php
namespace WPGraphQL\Acf\FieldType;

use WPGraphQL\Acf\FieldConfig;

class DatePicker {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type(
			'date_picker',
			[
				'graphql_type' => 'String',
				'resolve' => function( $root, $args, $context, $info, $field_type, FieldConfig $field_config ) {
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

					// appending '|' to the format prevents the minutes and seconds from being determined from the current time
					return \DateTime::createFromFormat( $return_format . '|', $value )->format( \DateTimeInterface::RFC3339 );
				}
			]
		);
	}

}

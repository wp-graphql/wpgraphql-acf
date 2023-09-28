<?php

namespace WPGraphQL\Acf\ThirdParty\AcfExtended\FieldType;

use WPGraphQL\Acf\FieldConfig;

class AcfeDateRangePicker {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type(
			'acfe_date_range_picker',
			[
				'graphql_type' => 'ACFE_Date_Range',
				'resolve'      => static function ( $root, $args, $context, $info, $field_type, FieldConfig $field_config ) {
					$acf_field  = $field_config->get_acf_field();
					$start_date = $field_config->resolve_field( $root, $args, $context, $info, [ 'name' => $acf_field['name'] . '_start' ] );
					$end_date   = $field_config->resolve_field( $root, $args, $context, $info, [ 'name' => $acf_field['name'] . '_end' ] );

					// @see: https://www.acf-extended.com/features/fields/date-range-picker#field-value
					// ACFE Date Range Picker returns unformatted value with Ymd format
					// NOTE: appending '|' to the format prevents the minutes and seconds from being determined from the current time
					return [
						'startDate' => ! empty( $start_date ) ? \DateTime::createFromFormat( 'Ymd|', $start_date )->format( \DateTimeInterface::RFC3339 ) : null,
						'endDate'   => ! empty( $end_date ) ? \DateTime::createFromFormat( 'Ymd|', $end_date )->format( \DateTimeInterface::RFC3339 ) : null,
					];
				},
			]
		);
	}

}

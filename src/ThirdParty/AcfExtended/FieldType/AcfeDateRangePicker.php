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
					$acf_field = $field_config->get_acf_field();
					$start_date = $field_config->resolve_field( $root, $args, $context, $info, [ 'name' => $acf_field['name'] . '_start' ] );
					$end_date = $field_config->resolve_field( $root, $args, $context, $info, [ 'name' => $acf_field['name'] . '_end' ] );

					return [
						'startDate' => ! empty( $start_date ) ? mysql_to_rfc3339( $start_date ) : null,
						'endDate'   => ! empty( $end_date ) ? mysql_to_rfc3339( $end_date ) : null,
					];
				},
			]
		);
	}

}

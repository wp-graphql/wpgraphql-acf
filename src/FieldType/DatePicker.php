<?php
namespace WPGraphQL\Acf\FieldType;

use DateTimeInterface;
use WPGraphQL\Acf\FieldConfig;

class DatePicker {

	/**
	 * Register support for the "date_picker" ACF field type
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type(
			'date_picker',
			[
				'graphql_type'              => 'String',
				// Apply a description to be appended to the field description.
				// @todo: consider removing when CustomScalar types are supported along with the @specifiedBy directive
				'graphql_description_after' => static function ( FieldConfig $field_config ) {
					$field_type = $field_config->get_acf_field()['type'] ?? null;

					// translators: The $s is the name of the acf field type that is returning a date string according to the RFC3339 spec.
					return '(' . sprintf( __( 'ACF Fields of the %s type return a date string according to the RFC3339 spec: https://datatracker.ietf.org/doc/html/rfc3339.', 'wpgraphql-acf' ), $field_type ) . ')';
				},
				'resolve'                   => static function ( $root, $args, $context, $info, $field_type, FieldConfig $field_config ) {
					$value = $field_config->resolve_field( $root, $args, $context, $info );

					$timestamp = strtotime( $value );

					if ( false === $timestamp ) {
						return null;
					}

					return gmdate( DateTimeInterface::RFC3339, $timestamp );
				},
			]
		);
	}
}

<?php

namespace WPGraphQLAcf;

class FieldTypeRegistry {

	/**
	 * @var array
	 */
	protected array $registered_field_types;

	/**
	 * @return mixed|void
	 */
	public function get_registered_field_types() {
		return apply_filters( 'graphql_acf_get_registered_field_types', $this->registered_field_types );
	}

	/**
	 * @param string $acf_field_type
	 * @param array  $config
	 *
	 * @return array|null
	 */
	public function register_field_type( string $acf_field_type, array $config ): ? array {

		if ( isset( $this->registered_field_types[ $acf_field_type ] ) ) {

			graphql_debug( __( 'The "%s" is already registered and cannot be registered multiple times', 'wp-graphql-acf' ), [
				'acf_field_type' => $acf_field_type,
				'acf_field_config' => $config
			] );

			return $this->registered_field_types[ $acf_field_type ];
		}

		return null;

	}

}

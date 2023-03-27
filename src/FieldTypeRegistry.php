<?php

namespace WPGraphQLAcf;

use WPGraphQLAcf\FieldType\Email;
use WPGraphQLAcf\FieldType\File;
use WPGraphQLAcf\FieldType\GoogleMap;
use WPGraphQLAcf\FieldType\Image;
use WPGraphQLAcf\FieldType\Link;
use WPGraphQLAcf\FieldType\Number;
use WPGraphQLAcf\FieldType\Password;
use WPGraphQLAcf\FieldType\Range;
use WPGraphQLAcf\FieldType\Text;
use WPGraphQLAcf\FieldType\Textarea;
use WPGraphQLAcf\FieldType\TrueFalse;
use WPGraphQLAcf\FieldType\Url;

class FieldTypeRegistry {

	/**
	 * @var array
	 */
	protected $registered_field_types = [];

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Initialize the Field Type Registry
		do_action( 'graphql_acf_registry_init', $this );

		// Register supported ACF Field Types
		$this->register_acf_field_types();

		// Initialize the Field Type Registry
		do_action( 'graphql_acf_register_field_types', $this );
	}


	/**
	 * @return void
	 */
	protected function register_acf_field_types(): void {

		Text::register_field_type();
		Textarea::register_field_type();
		Number::register_field_type();
		Range::register_field_type();
		Email::register_field_type();
		Url::register_field_type();
		Password::register_field_type();
		Image::register_field_type();
		File::register_field_type();
		GoogleMap::register_field_type();
		Link::register_field_type();
		TrueFalse::register_field_type();

	}

	/**
	 * Return the registered field types, names and config in an associative array.
	 *
	 * @return array
	 */
	public function get_registered_field_types(): array {
		return apply_filters( 'graphql_acf_get_registered_field_types', $this->registered_field_types );
	}

	/**
	 * Return an array of the names of the registered field types
	 *
	 * @return array
	 */
	public function get_registered_field_type_names(): array {
		return array_keys( $this->get_registered_field_types() );
	}

	/**
	 * Given an acf field type (i.e. text, textarea, etc) return the config for mapping
	 * the field type to GraphQL
	 *
	 * @param string $acf_field_type The type of field to get the config for
	 *
	 * @return AcfGraphQLFieldType|null
	 */
	public function get_field_type( string $acf_field_type ): ?AcfGraphQLFieldType {
		return $this->registered_field_types[ $acf_field_type ] ?? null;
	}

	/**
	 * Register an ACF Field Type
	 *
	 * @param string $acf_field_type The name of the ACF Field Type to map to the GraphQL Schema
	 * @param array|callable $config Config for mapping the ACF Field Type to the GraphQL Schema
	 *
	 * @return AcfGraphQLFieldType
	 */
	public function register_field_type( string $acf_field_type, $config = [] ): AcfGraphQLFieldType {

		if ( 'true_false' === $acf_field_type ) {
			wp_send_json( [
				'$acf_field_type' => $acf_field_type,
				'$config' => $config,
			]);
		}

		if ( isset( $this->registered_field_types[ $acf_field_type ] ) ) {

//			graphql_debug( __( 'The ACF Field Type "%s" is already registered as a supported field type and cannot be registered multiple times', 'wp-graphql-acf' ), [
//				'acf_field_type'   => $acf_field_type,
//				'acf_field_config' => $config,
//			] );

			return $this->registered_field_types[ $acf_field_type ];
		}

		$this->registered_field_types[ $acf_field_type ] = new AcfGraphQLFieldType( $acf_field_type, $config );

		return $this->registered_field_types[ $acf_field_type ];

	}

}

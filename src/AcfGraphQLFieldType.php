<?php
namespace WPGraphQLAcf;

use Codeception\PHPUnit\Constraint\Page;
use WPGraphQLAcf\Admin\Settings;

/**
 * Configures how an ACF Field Type should interact with WPGraphQL
 *
 * - Controls the Admin UI Field Settings for the field
 * - Controls how the field shows in the GraphQL Schema
 * - Controls how the field resolves in GraphQL Requests
 */
class AcfGraphQLFieldType {

	/**
	 * @var string
	 */
	private $acf_field_type;

	/**
	 * @var array|callable
	 */
	protected $config;

	/**
	 * @var array
	 */
	protected $admin_fields;

	/**
	 * @var AcfGraphQLFieldResolver
	 */
	protected $resolver;

	/**
	 * @var array
	 */
	protected $excluded_admin_field_settings = [];

	/**
	 * Constructor.
	 *
	 * @param string $acf_field_type The name of the ACF Field Type
	 * @param array|callable $config The config for how tha ACF Field Type should map to the WPGraphQL Schema and display Admin settings for the field.
	 */
	public function __construct( string $acf_field_type, $config = [] ) {
		$this->set_acf_field_type( $acf_field_type );
		$this->set_config( $config );
		$this->set_excluded_admin_field_settings();
		$this->resolver = new AcfGraphQLFieldResolver( $this );
	}

	/**
	 * @param array|callable $config The config for the ACF GraphQL Field Type
	 *
	 * @return void
	 */
	public function set_config( $config = [] ): void {

		if ( is_array( $config ) ) {
			$this->config = $config;
		} elseif ( is_callable( $config ) ) {
			$_config = $config( $this->get_acf_field_type(), $this );
			if ( is_array( $_config ) ) {
				$this->config = $_config;
			}
		}
	}

	/**
	 * Get the config for the Field Type
	 *
	 * @param string|null $setting_name The name of the setting to get the config for.
	 *
	 * @return mixed
	 */
	public function get_config( ?string $setting_name = null ) {

		if ( empty( $setting_name ) || ! is_array( $this->config ) ) {
			return $this->config;
		}

		return $this->config[ $setting_name ] ?? null;
	}

	/**
	 * Return Admin Field Settings for configuring GraphQL Behavior.
	 *
	 * @param array $field The Instance of the ACF Field the settings are for
	 * @param Settings $settings The Settings class
	 *
	 * @return mixed|void
	 */
	public function get_admin_field_settings( array $field, Settings $settings ) {

		$default_admin_settings = [];

		// If there's a description provided, use it.
		if ( ! empty( $field['graphql_description'] ) ) {
			$description = $field['graphql_description'];

			// fallback to the fields instructions
		} elseif ( ! empty( $field['instructions'] ) ) {
			$description = $field['instructions'];
		}

		$default_admin_settings['show_in_graphql'] = [
			'label'         => __( 'Show in GraphQL', 'wp-graphql-acf' ),
			'instructions'  => __( 'Whether the field should be queryable via GraphQL. NOTE: Changing this to false for existing field can cause a breaking change to the GraphQL Schema. Proceed with caution.', 'wp-graphql-acf' ),
			'name'          => 'show_in_graphql',
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'value'         => ! isset( $field['show_in_graphql'] ) || (bool) $field['show_in_graphql'],
			'conditions'    => [],
		];

		$default_admin_settings['graphql_description'] = [
			'label'         => __( 'GraphQL Description', 'wp-graphql-acf' ),
			'instructions'  => __( 'The description of the field, shown in the GraphQL Schema. Should not include any special characters.', 'wp-graphql-acf' ),
			'name'          => 'graphql_description',
			'type'          => 'text',
			'ui'            => true,
			'default_value' => null,
			'placeholder'   => __( 'Explanation of how this field should be used in the GraphQL Schema', 'wp-graphql-acf' ),
			'value'         => ! empty( $description ) ? $description : null,
			'conditions'    => [
				'field'    => 'show_in_graphql',
				'operator' => '==',
				'value'    => '1',
			],
		];

		$graphql_field_name = '';

		// If there's a graphql_field_name value, use it, allowing underscores
		if ( ! empty( $field['graphql_field_name'] ) ) {
			$graphql_field_name = \WPGraphQL\Utils\Utils::format_field_name( $field['graphql_field_name'], true );

			// Else, use the field's name, if it's not "new_field" and format it without underscores
		} elseif ( ! empty( $field['name'] ) && 'new_field' !== $field['name'] ) {
			$graphql_field_name = \WPGraphQL\Utils\Utils::format_field_name( $field['name'], false );
		}


		$default_admin_settings['graphql_field_name'] = [
			'label'         => __( 'GraphQL Field Name', 'wp-graphql-acf' ),
			'instructions'  => __( 'The name of the field in the GraphQL Schema. Should only contain numbers and letters. Must start with a letter. Recommended format is "snakeCase".', 'wp-graphql-acf' ),
			'name'          => 'graphql_field_name',
			'type'          => 'text',
			'ui'            => true,
			'required'      => true,
			// we don't allow underscores if the value is auto formatted
			'placeholder'   => __( 'newFieldName', 'wp-graphql-acf' ),
			'default_value' => '',
			// allow underscores if the user enters the value with underscores
			'value'         => $graphql_field_name,
			'conditions'    => [
				'field'    => 'show_in_graphql',
				'operator' => '==',
				'value'    => '1',
			],
		];

		// Get the admin fields for the field type
		$admin_fields = $this->get_admin_fields( $field, $settings );


		// Add additional fields to the defaults
		if ( ! empty( $admin_fields ) ) {
			foreach ( $admin_fields as $admin_field_key => $admin_field_config ) {
				if ( ! empty( $admin_field_config['admin_field'] ) && ! isset( $default_admin_settings[ $admin_field_key ] ) ) {
					$default_admin_settings[ $admin_field_key ] = $admin_field_config['admin_field'];
				}
			}
		}

		// Remove excluded fields
		if ( isset( $this->config['exclude_admin_fields'] ) && is_array( $this->config['exclude_admin_fields'] ) ) {
			foreach ( $this->config['exclude_admin_fields'] as $excluded ) {
				unset( $default_admin_settings[ $excluded ] );
			}
		}

		return apply_filters( 'graphql_acf_field_type_default_admin_settings', $default_admin_settings );

	}

	/**
	 * @param array $acf_field The ACF Field to get the settings for
	 * @param Settings $settings Instance of the Settings class
	 *
	 * @return array
	 */
	public function get_admin_fields( array $acf_field, Settings $settings ): array {

		if ( ! empty( $this->admin_fields ) ) {
			return $this->admin_fields;
		}

		$admin_fields = $this->get_config( 'admin_fields' );

		if ( is_array( $admin_fields ) ) {
			$this->admin_fields = $admin_fields;
		}

		if ( is_callable( $admin_fields ) ) {
			$this->admin_fields = $admin_fields( $acf_field, $this->config, $settings );
		}

		return $this->admin_fields;
	}

	/**
	 *
	 * @return string
	 */
	public function get_acf_field_type(): string {
		return $this->acf_field_type;
	}

	/**
	 * Set the ACF Field Type
	 *
	 * @param string $acf_field_type
	 *
	 * @return void
	 */
	protected function set_acf_field_type( string $acf_field_type ): void {
		$this->acf_field_type = $acf_field_type;
	}

	/**
	 * @return void
	 */
	protected function set_excluded_admin_field_settings():void {

		$this->excluded_admin_field_settings = [];

		if ( empty( $excluded_admin_fields = $this->get_config( 'exclude_admin_fields' ) ) ) {
			return;
		}

		if ( ! is_array( $excluded_admin_fields ) ) {
			return;
		}

		$this->excluded_admin_field_settings = $excluded_admin_fields;
	}

	/**
	 * @return array
	 */
	public function get_excluded_admin_field_settings(): array {
		return apply_filters( 'graphql_acf_excluded_admin_field_settings', $this->excluded_admin_field_settings );
	}

	/**
	 * @return array|string
	 */
	public function get_resolve_type( FieldConfig $field_config ) {

		$acf_field = $field_config->get_acf_field();

		$resolve_type = 'String';

		if ( isset( $acf_field['graphql_resolve_type'] ) ) {
			$resolve_type = $acf_field['graphql_resolve_type'];
		} elseif ( ! empty( $this->get_config( 'graphql_type' ) ) ) {

			if ( is_callable( $this->get_config( 'graphql_type' ) ) ) {
				$resolve_type = $this->get_config( 'graphql_type' )( $field_config, $this );
			} else {
				$resolve_type = $this->get_config( 'graphql_type' );
			}
		}

		return $resolve_type;
	}

}

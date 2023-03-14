<?php
/**
 * ACF extension for WP-GraphQL
 *
 * @package wp-graphql-acf
 */

namespace WPGraphQLAcf\Admin;

use GraphQL\Error\Error;
use WPGraphQLAcf\LocationRules;
use WPGraphQLAcf\Utils;
use WPGraphQLAcf\Registry;


/**
 * Class ACF_Settings
 *
 * @package WPGraphQL\ACF
 */
class Settings {

	/**
	 * @var bool
	 */
	protected $is_acf6_or_higher = false;

	/**
	 * @var Registry
	 */
	protected $registry;

	/**
	 * @return Registry
	 */
	protected function get_registry(): Registry {
		if ( ! $this->registry instanceof Registry ) {
			$this->registry = new Registry();
		}

		return $this->registry;
	}

	/**
	 * Initialize ACF Settings for the plugin
	 */
	public function init(): void {

		$this->is_acf6_or_higher = defined( 'ACF_MAJOR_VERSION' ) && version_compare( ACF_MAJOR_VERSION, '6', '>=' );


		/**
		 * Add settings to individual fields to allow each field granular control
		 * over how it's shown in the GraphQL Schema
		 */
		add_action( 'acf/render_field_settings', [ $this, 'add_field_settings' ], 10, 1 );

		// NOTE: when we add support for showing fields in tabs,
		// and support for different fields based on field type, we're going to
		// need to refactor this a bit.
		// we will need some conditions in place for ACF version
		// since ACF 6.1 will have tab support
		// And we'll need to have different callbacks per type, like:
		// acf/render_field_settings/type=$field_type.
		//
		// If we have one callback like we do now ("add_field_settings") with a switch statement
		// inside, things don't work as expected, so we actually need
		// to have different callbacks per field type, from my testing.
		// something like the following (pseudo):


		//     Get the supported field types
		//
		//     $supported_field_types = Utils::get_supported_field_types();
		//
		//	   if ( $this->is_acf6_or_higher ) {
		//          foreach ( $supported_field_types as $supported_field_type ) {
		//
		//				// if there's a valid callback, call it
		//				if ( is_callable( [ __CLASS__, 'add_' . $supported_field_type . '_settings' ] ) ) {
		//
		//					add_action( 'acf/render_field_general_settings/type=' . $supported_field_type, [
		//						__CLASS__,
		//						'add_' . $supported_field_type . '_settings'
		//					], 10, 1 );
		//				}
		//			}
		//
		//		} else {
		//			// fallback for v5 and older
		//          foreach ( $supported_field_types as $supported_field_type ) {
		//            if ( is_callable( [ __CLASS__, 'add_' . $supported_field_type . '_settings' ] ) ) {
		//			    add_action( 'acf/render_field_settings/type=' . $supported_field_type, [ __CLASS__, 'add_' . $supported_field_type . '_settings' ], 10, 1 );
		//            }
		//          }
		//		}

		/**
		 * Enqueue scripts to enhance the UI of the ACF Field Group Settings
		 */
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_graphql_acf_scripts' ], 10, 1 );

		/**
		 * Register meta boxes for the ACF Field Group Settings
		 */
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );

		/**
		 * Register an AJAX action and callback for converting ACF Location rules to GraphQL Types
		 */
		add_action( 'wp_ajax_get_acf_field_group_graphql_types', [ $this, 'graphql_types_ajax_callback' ] );

		add_filter( 'manage_acf-field-group_posts_columns', [ $this, 'wpgraphql_admin_table_column_headers' ], 11, 1 );

		add_action( 'manage_acf-field-group_posts_custom_column', [ $this, 'wpgraphql_admin_table_columns_html' ], 11, 2 );
	}

	/**
	 * Handle the AJAX callback for converting ACF Location settings to GraphQL Types
	 *
	 * @return void
	 */
	public function graphql_types_ajax_callback(): void {

		if ( ! isset( $_POST['data'] ) ) {
			echo esc_html( __( 'No location rules were found', 'wp-graphql-acf' ) );
			wp_die();
		}

		$form_data           = [];
		$sanitized_post_data = wp_strip_all_tags( $_POST['data'] );

		parse_str( $sanitized_post_data, $form_data );

		if ( empty( $form_data ) || ! isset( $form_data['acf_field_group'] ) ) {
			wp_send_json( __( 'No form data.', 'wp-graphql-acf' ) );
		}

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING ), 'wp_graphql_acf' ) ) {
			wp_send_json_error();
		}

		$field_group = $form_data['acf_field_group'];
		$rules       = new LocationRules( [ $field_group ] );
		$rules->determine_location_rules();

		$group_title = $field_group['title'] ?? '';
		$group_name  = $field_group['graphql_field_name'] ?? $group_title;
		$group_name  = \WPGraphQL\Utils\Utils::format_field_name( $group_name, true );

		$all_rules = $rules->get_rules();
		if ( isset( $all_rules[ $group_name ] ) ) {
			wp_send_json( [
				'graphql_types' => array_values( $all_rules[ $group_name ] ),
			] );
		}
		wp_send_json( [ 'graphql_types' => null ] );

	}

	/**
	 * Register the GraphQL Settings metabox for the ACF Field Group post type
	 *
	 * @return void
	 */
	public function register_meta_boxes() {
		add_meta_box( 'wpgraphql-acf-meta-box', __( 'GraphQL', 'wp-graphql-acf' ), [
			$this,
			'display_metabox',
		], [ 'acf-field-group' ] );
	}

	/**
	 * Display the GraphQL Settings Metabox on the Field Group admin page
	 *
	 * @param mixed $field_group_post_object
	 *
	 * @return void
	 * @throws Error
	 * @throws \Exception
	 */
	public function display_metabox( $field_group_post_object ): void {

		global $field_group;

		// Render a field in the Field Group settings to allow for a Field Group to be shown in GraphQL.
		// @phpstan-ignore-next-line
		acf_render_field_wrap(
			[
				'label'        => __( 'Show in GraphQL', 'wp-graphql-acf' ),
				'instructions' => __( 'If the field group is active, and this is set to show, the fields in this group will be available in the WPGraphQL Schema based on the respective Location rules. NOTE: Changing a field "show_in_graphql" to "false" could create breaking changes for client applications already querying for this field group.', 'wp-graphql-acf' ),
				'type'         => 'true_false',
				'name'         => 'show_in_graphql',
				'prefix'       => 'acf_field_group',
				'value'        => isset( $field_group['show_in_graphql'] ) && (bool) $field_group['show_in_graphql'],
				'ui'           => 1,
			],
			'div',
			'label',
			true
		);

		// Render a field in the Field Group settings to set the GraphQL field name for the field group.
		// @phpstan-ignore-next-line
		acf_render_field_wrap(
			[
				'label'        => __( 'GraphQL Type Name', 'wp-graphql-acf' ),
				'instructions' => __( 'The GraphQL Type name representing the field group in the GraphQL Schema. Must start with a letter. Can only contain Letters, Numbers and underscores. Best practice is to use "PascalCase" for GraphQL Types.', 'wp-graphql-acf' ),
				'type'         => 'text',
				'prefix'       => 'acf_field_group',
				'name'         => 'graphql_field_name',
				'required'     => isset( $field_group['show_in_graphql'] ) && (bool) $field_group['show_in_graphql'],
				'placeholder'  => __( 'FieldGroupTypeName', 'wp-graphql-acf' ),
				'value'        => ! empty( $field_group['graphql_field_name'] ) ? $field_group['graphql_field_name'] : '',
			],
			'div',
			'label',
			true
		);

		// @phpstan-ignore-next-line
		acf_render_field_wrap(
			[
				'label'        => __( 'Manually Set GraphQL Types for Field Group', 'wp-graphql-acf' ),
				'instructions' => __( 'By default, ACF Field groups are added to the GraphQL Schema based on the field group\'s location rules. Checking this box will let you manually control the GraphQL Types the field group should be shown on in the GraphQL Schema using the checkboxes below, and the Location Rules will no longer effect the GraphQL Types.', 'wp-graphql-acf' ),
				'type'         => 'true_false',
				'name'         => 'map_graphql_types_from_location_rules',
				'prefix'       => 'acf_field_group',
				'value'        => isset( $field_group['map_graphql_types_from_location_rules'] ) && (bool) $field_group['map_graphql_types_from_location_rules'],
				'ui'           => 1,
			],
			'div',
			'label',
			true
		);

		$choices = Utils::get_all_graphql_types();

		// @phpstan-ignore-next-line
		acf_render_field_wrap(
			[
				'label'        => __( 'GraphQL Types to Show the Field Group On', 'wp-graphql-acf' ),
				'instructions' => __( 'Select the Types in the WPGraphQL Schema to show the fields in this field group on', 'wp-graphql-acf' ),
				'type'         => 'checkbox',
				'prefix'       => 'acf_field_group',
				'name'         => 'graphql_types',
				'value'        => ! empty( $field_group['graphql_types'] ) ? $field_group['graphql_types'] : [],
				'toggle'       => true,
				'choices'      => $choices,
			],
			'div',
			'label',
			true
		);

		// Render a field in the Field Group settings to show interfaces for a Field Group to be shown in GraphQL.
		$interfaces            = $this->get_registry()->get_field_group_interfaces( $field_group );
		$field_group_type_name = $this->get_registry()->get_field_group_graphql_type_name( $field_group );


		// @phpstan-ignore-next-line
		acf_render_field_wrap(
			[
				'label'        => __( 'GraphQL Interfaces', 'wp-graphql-acf' ),
				'instructions' => sprintf( __( "These are the GraphQL Interfaces implemented by the '%s' GraphQL Type", 'wp-graphql-acf' ), $field_group_type_name ),
				'type'         => 'message',
				'name'         => 'graphql_interfaces',
				'prefix'       => 'acf_field_group',
				'message'      => ! empty( $interfaces ) ? $i = '<ul><li>' . implode( '</li><li>', $interfaces ) . '</li></ul>' : [],
				'readonly'     => true,
			],
			'div',
			'label',
			true
		);

		?>
		<div class="acf-hidden">
			<input
				type="hidden"
				name="acf_field_group[key]"
				value="<?php echo esc_attr( $field_group['key'] ); ?>"
			/>
		</div>
		<script type="text/javascript">
			if (typeof acf !== 'undefined') {
				acf.newPostbox({
					'id': 'wpgraphql-acf-meta-box',
					'label': <?php echo $this->is_acf6_or_higher ? 'top' : "'left'"; ?>
				});
			}
		</script>
		<?php

	}

	/**
	 * Add settings to each field to show in GraphQL
	 *
	 * @param array $field The field to add the setting to.
	 *
	 * @return void
	 */
	public function add_field_settings( array $field ): void {

		$supported_field_types = Utils::get_supported_acf_fields_types();

		/**
		 * If there are no supported fields, or the field is not supported, don't add a setting field.
		 */
		if ( empty( $supported_field_types ) || ! in_array( $field['type'], $supported_field_types, true ) ) {
			return;
		}

		// Render the "show_in_graphql" setting for the field.
		// @phpstan-ignore-next-line
		acf_render_field_setting(
			$field,
			[
				'label'         => __( 'Show in GraphQL', 'wp-graphql-acf' ),
				'instructions'  => __( 'Whether the field should be queryable via GraphQL. NOTE: Changing this to false for existing field can cause a breaking change to the GraphQL Schema. Proceed with caution.', 'wp-graphql-acf' ),
				'name'          => 'show_in_graphql',
				'type'          => 'true_false',
				'ui'            => 1,
				'default_value' => 1,
				'value'         => ! isset( $field['show_in_graphql'] ) || (bool) $field['show_in_graphql'],
			],
			true
		);

		// @phpstan-ignore-next-line
		acf_render_field_setting(
			$field,
			[
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
				'value'         => ! empty( $field['graphql_field_name'] ) ? \WPGraphQL\Utils\Utils::format_field_name( $field['graphql_field_name'], true ) : '',
				'conditions'   => [
					'field'    => 'show_in_graphql',
					'operator' => '==',
					'value'    => '1',
				],
			],
			true
		);

		// If there's a description provided, use it.
		if ( ! empty( $field['graphql_description'] ) ) {
			$description = $field['graphql_description'];

			// fallback to the fields instructions
		} elseif ( ! empty( $field['instructions'] ) ) {
			$description = $field['instructions'];
		}

		// @phpstan-ignore-next-line
		acf_render_field_setting(
			$field,
			[
				'label'         => __( 'GraphQL Description', 'wp-graphql-acf' ),
				'instructions'  => __( 'The description of the field, shown in the GraphQL Schema. Should not include any special characters.', 'wp-graphql-acf' ),
				'name'          => 'graphql_description',
				'type'          => 'text',
				'ui'            => true,
				'default_value' => null,
				'placeholder'   => __( 'Explanation of how this field should be used in the GraphQL Schema', 'wp-graphql-acf' ),
				'value'         => ! empty( $description ) ? $description : null,
				'conditions'   => [
					'field'    => 'show_in_graphql',
					'operator' => '==',
					'value'    => '1',
				],
			],
			true
		);

	}

	/**
	 * This enqueues admin script.
	 *
	 * @param string $screen The screen that scripts are being enqueued to
	 *
	 * @return void
	 */
	public function enqueue_graphql_acf_scripts( string $screen ): void {
		global $post;

		if ( ! ( 'post-new.php' === $screen || 'post.php' === $screen ) ) {
			return;
		}

		if ( ! isset( $post->post_type ) || 'acf-field-group' !== $post->post_type ) {
			return;
		}

		wp_enqueue_script(
			'graphql-acf',
			plugins_url( '/assets/admin/js/main.js', __DIR__ ),
			[
				'jquery',
				'acf-input',
				'acf-field-group',
			],
			WPGRAPHQL_FOR_ACF_VERSION,
			true
		);

		wp_localize_script( 'graphql-acf', 'wp_graphql_acf', [
			'nonce' => wp_create_nonce( 'wp_graphql_acf' ),
		]);

	}

	/**
	 * Add header to the field group admin page columns showing types and interfaces
	 *
	 * @param array $_columns The column headers to add the values to.
	 *
	 * @return array The column headers with the added wp-graphql columns
	 */
	public function wpgraphql_admin_table_column_headers( array $_columns ): array {

		$columns  = [];
		$is_added = false;

		foreach ( $_columns as $name => $value ) {
			$columns[ $name ] = $value;
			// After the location column, add the wpgraphql specific columns
			if ( 'acf-location' === $name ) {
				$columns['acf-wpgraphql-type']       = __( 'GraphQL Type', 'wp-graphql-acf' );
				$columns['acf-wpgraphql-interfaces'] = __( 'GraphQL Interfaces', 'wp-graphql-acf' );
				$columns['acf-wpgraphql-locations']  = __( 'GraphQL Locations', 'wp-graphql-acf' );
				$is_added                            = true;
			}
		}
		// If not added after the specific column, add to the end of the list
		if ( ! $is_added ) {
			$columns['acf-wpgraphql-type']       = __( 'GraphQL Type', 'wp-graphql-acf' );
			$columns['acf-wpgraphql-interfaces'] = __( 'GraphQL Interfaces', 'wp-graphql-acf' );
			$columns['acf-wpgraphql-locations']  = __( 'GraphQL Locations', 'wp-graphql-acf' );
		}

		return $columns;
	}

	/**
	 * Add values to the field group admin page columns showing types and interfaces
	 *
	 * @param string $column_name The column being processed.
	 * @param int    $post_id     The field group id being processed
	 *
	 * @return void
	 * @throws Error
	 */
	public function wpgraphql_admin_table_columns_html( string $column_name, int $post_id ): void {
		global $field_group;

		if ( empty( $post_id ) ) {
			echo null;
		}

		// @phpstan-ignore-next-line
		$field_group = acf_get_field_group( $post_id );

		if ( empty( $field_group ) ) {
			echo null;
		}

		switch ( $column_name ) {
			case 'acf-wpgraphql-type':
				$type_name = $this->get_registry()->get_field_group_graphql_type_name( $field_group );

				// @phpstan-ignore-next-line
				echo '<span class="acf-wpgraphql-type">' . acf_esc_html( $type_name ) . '</span>';
				break;
			case 'acf-wpgraphql-interfaces':
				$interfaces = $this->get_registry()->get_field_group_interfaces( $field_group );
				$html       = Utils::array_list_by_limit( $interfaces, 5 );

				// @phpstan-ignore-next-line
				echo '<span class="acf-wpgraphql-interfaces">' . acf_esc_html( $html ) . '</span>';
				break;
			case 'acf-wpgraphql-locations':
				$acf_field_groups = $this->get_registry()->get_acf_field_groups();
				$locations        = $this->get_registry()->get_graphql_locations_for_field_group( $field_group, $acf_field_groups );
				if ( $locations ) {
					$html = Utils::array_list_by_limit( $locations, 5 );

					// @phpstan-ignore-next-line
					echo '<span class="acf-wpgraphql-location-types">' . acf_esc_html( $html ) . '</span>';
				}
				break;
			default:
				echo null;
		}
	}

}

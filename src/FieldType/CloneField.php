<?php
namespace WPGraphQL\Acf\FieldType;

class CloneField {

	/**
	 * @return void
	 */
	public static function register_field_type():void {

		register_graphql_acf_field_type( 'clone', [

			// The clone field adds its own settings field to display
			'admin_fields' => function ( $default_admin_settings, $field, $config, \WPGraphQL\Acf\Admin\Settings $settings ) {

				// Return one GraphQL Field, ignoring the default admin settings
				return [
					'graphql_clone_field' => [
						'type'         => 'message',
						'label'        => __( 'GraphQL Settings for Clone Fields', 'wp-graphql-acf' ),
						'instructions' => __( 'Clone Fields will inherit their GraphQL settings from the field(s) being cloned. If all Fields from a Field Group are cloned, an Interface representing the cloned field Group will be applied to this field group.', 'wp-graphql-acf' ),
						'conditions'   => [],
					],
				];
			},
		]);

	}
}

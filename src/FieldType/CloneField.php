<?php
namespace WPGraphQLAcf\FieldType;

class CloneField {

	/**
	 * @return void
	 */
	public static function register_field_type():void {

		register_graphql_acf_field_type( 'clone', [

			// The clone field excludes the default admin fields
			'exclude_admin_fields' => [ 'show_in_graphql', 'graphql_field_name', 'graphql_description' ],

			// The clone field adds its own settings field to display
			'admin_fields'         => function ( $field, $config, \WPGraphQLAcf\Admin\Settings $settings ) {
				return [
					'graphql_clone' => [
						'admin_field' => [
							'type'         => 'message',
							'label'        => __( 'GraphQL Settings for Clone Fields', 'wp-graphql-acf' ),
							'instructions' => __( 'Clone Fields will inherit their GraphQL settings from the field(s) being cloned. If all Fields from a Field Group are cloned, an Interface representing the cloned field Group will be applied to this field group.', 'wp-graphql-acf' ),
							'conditions'   => [],
						],
					],
				];
			},
		]);

	}
}

<?php
namespace WPGraphQLAcf\FieldType;

class Select {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {

		register_graphql_acf_field_type( 'select', [
			'graphql_type' => [ 'list_of' => 'String' ],
			//          @todo: Explore adding fields for making the type non-null and changing how fields resolve (i.e. if the resolve type is changed from "list_of string" to "string" the resolver needs to respect that response).
			//          'admin_fields' => function( $field, $config, \WPGraphQLAcf\Admin\Settings $settings ) {
			//
			//              // @phpstan-ignore-next-line
			//              return [
			//                  'graphql_resolve_type' => [
			//                      'admin_field' => $settings->get_graphql_resolve_type_field_config(),
			//                      'before_resolve' => function() {},
			//                      'after_resolve' => function() {},
			//                  ],
			//                  'graphql_non_null' => [
			//                      'admin_field' => $settings->get_graphql_non_null_field_config(),
			//                      'before_resolve' => function() {},
			//                      'after_resolve' => function() {},
			//                  ],
			//              ];
			//
			//          },
		] );

	}

}

<?php
namespace WPGraphQLAcf\FieldType;

class Text {

	/**
	 * @return void
	 */
	public function register_field_type(): void {

		register_graphql_acf_field_type( 'text' );
	}

}

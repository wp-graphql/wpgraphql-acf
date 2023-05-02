<?php

class AcfeCodeEditorFieldTest extends \Tests\WPGraphQLAcf\WPUnit\AcfFieldTestCase {

	/**
	 * @return void
	 */
	public function setUp(): void {
		// fake like acfe is active so we can mock our tests
		$field_type = new \Tests\WPGraphQLAcf\WPUnit\AcfeFieldType( $this->get_field_type() );
		acf_register_field_type( $field_type );
		parent::setUp();
	}


	/**
	 * @return void
	 */
	public function tearDown(): void {
		add_filter( 'graphql_acf_is_acfe_active', '__return_false' );
		parent::tearDown();
	}

	public function get_field_type(): string {
		return 'acfe_code_editor';
	}

	public function get_expected_field_resolve_type(): ?string {
		return 'String';
	}

	public function testFieldExists(): void {
		$field_types = acf_get_field_types();
		$this->assertTrue( array_key_exists( $this->get_field_type(), $field_types ) );
	}

}

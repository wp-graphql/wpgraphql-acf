<?php

class AcfeCodeEditorFieldTest extends \Tests\WPGraphQLAcf\WPUnit\AcfFieldTestCase {

	/**
	 * @return void
	 */
	public function setUp(): void {
		$test_type = new \Tests\WPGraphQLAcf\WPUnit\AcfeFieldType( 'acfe_code_editor' );
		 acf_register_field_type( $test_type );
		parent::setUp();
	}


	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	public function get_field_type(): string {
		return 'acfe_code_editor';
	}

	public function get_expected_field_resolve_type(): ?string {
		return 'String';
	}

}

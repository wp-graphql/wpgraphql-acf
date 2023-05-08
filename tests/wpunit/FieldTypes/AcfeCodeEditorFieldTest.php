<?php

class AcfeCodeEditorFieldTest extends \Tests\WPGraphQLAcf\WPUnit\AcfeFieldTestCase {

	/**
	 * @return void
	 */
	public function setUp(): void {
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

<?php

class ColorPickerFieldTest extends \Tests\WPGraphQLAcf\TestCase\AcfFieldTestCase {

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
		return 'color_picker';
	}

	public function get_expected_field_resolve_type(): ?string {
		return 'String';
	}

}

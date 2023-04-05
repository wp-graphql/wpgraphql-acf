<?php

/**
 * Text Area Field Test
 *
 * Tests the behavior of "text_area" field mapping to the WPGraphQL Schema
 */
class TextAreaFieldTest extends \Tests\WPGraphQLAcf\WPUnit\AcfFieldTestCase {

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	public function get_field_type():string {
		return "textarea";
	}

	public function get_expected_field_resolve_type(): ?string {
		return 'String';
	}

}

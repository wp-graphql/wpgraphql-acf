<?php

/**
 * Text Area Field Test
 *
 * Tests the behavior of "text_area" field mapping to the WPGraphQL Schema
 */
class TextAreaTestFieldTest extends \Tests\WPGraphQLAcf\TestCase\AcfFieldTestCase {

	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	public function get_field_type():string {
		return "textarea";
	}

	public function testSomeUniqueBehaviorAboutTextAreaFields() {

	}

}

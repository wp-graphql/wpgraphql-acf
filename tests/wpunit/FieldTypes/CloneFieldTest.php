<?php

class CloneFieldTest extends \Tests\WPGraphQLAcf\TestCase\AcfFieldTestCase {

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
		return 'clone';
	}

	public function testFieldShowsInSchemaIfShowInGraphqlIsTrue() {
		$this->markTestIncomplete( 'Clone fields have different requirements, need to be tested differently' );
	}

	public function testFieldShowsInSchemaIfShowInGraphqlIsNull() {
		$this->markTestIncomplete( 'Clone fields have different requirements, need to be tested differently' );
	}

	public function testFieldDescriptionUsesInstructionsIfGraphqlDescriptionNotProvided(): void {
		$this->markTestIncomplete( 'Clone fields have different requirements, need to be tested differently' );
	}

	public function testFieldDescriptionUsesGraphqlDescriptionIfProvided(): void {
		$this->markTestIncomplete( 'Clone fields have different requirements, need to be tested differently' );
	}

	public function testFallbackDescriptionIsUsedIfGraphqlDescriptionAndInstructionsAreBothEmpty() {
		$this->markTestIncomplete( 'Clone fields have different requirements, need to be tested differently' );
	}

	public function testFieldShowsInSchemaWithGraphqlFieldNameIfPresent() {
		$this->markTestIncomplete( 'Clone fields have different requirements, need to be tested differently' );
	}

	public function testFieldShowsInSchemaWithGraphqlFieldNameHasUnderscores() {
		$this->markTestIncomplete( 'Clone fields have different requirements, need to be tested differently' );
	}

}

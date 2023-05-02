<?php

class AcfeMenuLocationsFieldTest extends \Tests\WPGraphQLAcf\WPUnit\AcfFieldTestCase {

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
		parent::tearDown();
	}

	public function get_field_type(): string {
		return 'acfe_menu_locations';
	}

	public function get_expected_field_resolve_kind(): ?string {
		return 'LIST';
	}

	public function get_expected_field_resolve_type(): ?string {
		return null;
	}

	public function get_expected_field_of_type(): ?array {
		return [
			'name' => 'MenuLocationEnum',
		];
	}

	public function testFieldExists(): void {
		$field_types = acf_get_field_types();
		$this->assertTrue( array_key_exists( $this->get_field_type(), $field_types ) );
	}

}

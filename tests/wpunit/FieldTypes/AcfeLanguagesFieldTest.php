<?php

class AcfeLanguagesFieldTest extends \Tests\WPGraphQLAcf\WPUnit\AcfFieldTestCase {

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
		return 'acfe_languages';
	}

	public function get_expected_field_resolve_kind(): ?string {
		return 'LIST';
	}

	public function get_expected_field_resolve_type(): ?string {
		return null;
	}

	public function get_expected_field_of_type(): ?array {
		return [
			'name' => 'ACFE_Language',
		];
	}

	public function testFieldExists(): void {
		$field_types = acf_get_field_types();
		if ( class_exists('ACFE_Pro') ) {
			$this->assertTrue( array_key_exists( $this->get_field_type(), $field_types ) );
		} else {
			$this->assertTrue( array_key_exists( $this->get_field_type(), $field_types ) );
		}
	}

}

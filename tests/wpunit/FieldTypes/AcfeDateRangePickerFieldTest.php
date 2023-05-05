<?php

class AcfeDateRangePickerFieldTest extends \Tests\WPGraphQLAcf\WPUnit\AcfFieldTestCase {

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
		return 'acfe_date_range_picker';
	}

	public function get_expected_field_resolve_kind(): ?string {
		return 'OBJECT';
	}

	public function get_expected_field_resolve_type(): ?string {
		return 'ACFE_Date_Range';
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

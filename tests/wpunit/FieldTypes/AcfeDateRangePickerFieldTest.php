<?php

class AcfeDateRangePickerFieldTest extends \Tests\WPGraphQL\Acf\WPUnit\AcfeFieldTestCase {

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

	public function get_block_query_fragment() {
		return '
		fragment BlockQueryFragment on AcfTestGroup {
		  testAcfeDateRangePicker {
		     startDate
		     endDate
		  }
		}
		';
	}

	public function get_block_data_to_store() {
		return false;
	}

	public function get_extra_block_data_to_store( $acf_field_key ): array {
		return [
			$this->get_field_name() . '_start' => '20230927',
			'_' . $this->get_field_name() . '_start' => $acf_field_key,
			$this->get_field_name() . '_end' => '20230929',
			'_' . $this->get_field_name() . '_end' => $acf_field_key,
		];
	}

	public function get_expected_block_fragment_response() {
		return [
			'startDate' => '2023-09-27T00:00:00+00:00',
			'endDate' => '2023-09-29T00:00:00+00:00',
		];
	}

	public function testFieldExists(): void {
		$field_types = acf_get_field_types();
		if ( class_exists('ACFE_Pro') ) {
			$this->assertTrue( array_key_exists( $this->get_field_type(), $field_types ) );
		} else {
			$this->assertFalse( array_key_exists( $this->get_field_type(), $field_types ) );
		}
	}

}

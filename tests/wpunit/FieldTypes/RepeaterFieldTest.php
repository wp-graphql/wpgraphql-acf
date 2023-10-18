<?php

class RepeaterFieldTest extends \Tests\WPGraphQL\Acf\WPUnit\AcfFieldTestCase {

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
		return 'repeater';
	}

	/**
	 * @param array $acf_field
	 * @param array $acf_field_group
	 *
	 * @return string
	 */
	public function register_acf_field( array $acf_field = [], array $acf_field_group = [] ): string {

		$repeater_key = uniqid( 'field_repeater_',false );

		// set defaults on the acf field
		// using helper methods from this class.
		// this allows test cases extending this class
		// to more easily make use of repeatedly registering
		// fields of the same type and testing them
		$acf_field = array_merge( [
			'name' => $this->get_field_name(),
			'type' => $this->get_field_type(),
			'key' => $repeater_key,
			'sub_fields' => [
				[
					'key' => 'field_test_nested_text',
					'label' => 'Nested Text Field',
					'name' => 'nested_text',
					'aria-label' => '',
					'type' => 'text',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'default_value' => '',
					'maxlength' => '',
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
					'show_in_graphql' => 1,
					'graphql_description' => '',
					'graphql_field_name' => 'nestedText',
					'parent_repeater' => $repeater_key,
				]
			]
		], $acf_field );

		return parent::register_acf_field( $acf_field, $acf_field_group );
	}

	public function get_expected_field_of_type(): ?array {
		return [
			'name' => 'AcfTestGroupTestRepeater',
		];
	}

	public function get_expected_field_resolve_kind(): ?string {
		return 'LIST';
	}

	public function get_expected_field_resolve_type(): ?string {
		return null;
	}
	
	public function get_block_query_fragment() {
		return '
		fragment BlockQueryFragment on AcfTestGroup {
		  testRepeater {
			nestedText
		  }
		}
		';
	}

	public function get_block_data_to_store() {
		return 1;
	}

	public function get_extra_block_data_to_store( $acf_field_key = '', $acf_field_name = '' ): array {
		return [
			'test_repeater_0_nested_text' => 'nested text field value...',
			'_test_repeater_0_nested_text' => 'field_test_nested_text'
		];
	}

	public function get_expected_block_fragment_response() {
		return [
			[
				'nestedText' => 'nested text field value...',
			]
		];
	}

}

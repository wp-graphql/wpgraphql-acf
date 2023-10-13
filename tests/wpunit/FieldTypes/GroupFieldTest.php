<?php

class GroupFieldTest extends \Tests\WPGraphQL\Acf\WPUnit\AcfFieldTestCase {

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
	}

	/**
	 * @param array $acf_field
	 * @param array $acf_field_group
	 *
	 * @return string
	 */
	public function register_acf_field( array $acf_field = [], array $acf_field_group = [] ): string {

		// set defaults on the acf field
		// using helper methods from this class.
		// this allows test cases extending this class
		// to more easily make use of repeatedly registering
		// fields of the same type and testing them
		$acf_field = array_merge( [
			'name' => $this->get_field_name(),
			'type' => $this->get_field_type(),
			'sub_fields' => [
				[
					'key' => 'field_64711a0b852e2',
					'label' => 'Nested Text Field',
					'name' => 'nested_text_field',
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
					'graphql_field_name' => 'nestedTextField',
				]
			]
		], $acf_field );

		return parent::register_acf_field( $acf_field, $acf_field_group );
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	public function get_field_type(): string {
		return 'group';
	}

	public function get_expected_field_resolve_kind(): ?string {
		return 'OBJECT';
	}

	public function get_expected_field_resolve_type(): ?string {
		return 'AcfTestGroupTestGroup';
	}

	public function get_block_query_fragment() {
		return '
		fragment BlockQueryFragment on AcfTestGroup {
		  testGroup {
			nestedTextField
		  }
		}
		';
	}

	public function get_block_data_to_store() {
		return '';
	}

	public function get_extra_block_data_to_store( $acf_field_key = '', $acf_field_name = '' ): array {
		return [ 'test_group_nested_text_field' => 'nested text field value...' ];
	}

	public function get_expected_block_fragment_response() {
		return [
			'nestedTextField' => 'nested text field value...',
		];
	}

}

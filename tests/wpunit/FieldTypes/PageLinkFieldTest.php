<?php

class PageLinkFieldTest extends \Tests\WPGraphQL\Acf\WPUnit\AcfFieldTestCase {

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
		return 'page_link';
	}

	public function get_expected_field_resolve_kind(): ?string {
		return 'LIST';
	}

	public function get_expected_field_of_type(): ?array {
		return [
			'name' => 'String',
		];
	}

	/**
	 * @return int
	 */
	public function get_clone_value_to_save(): int {
		return $this->published_post->ID;
	}

	/**
	 * @return string
	 */
	public function get_acf_clone_fragment(): string {
		return '
		fragment AcfTestGroupFragment on AcfTestGroup {
			clonedTestPageLink
		}
		';
	}

	/**
	 * @return array
	 */
	public function get_expected_clone_value(): array {
		return [
			'expected-url-goes-here'
		];
	}

}

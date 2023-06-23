<?php

class LinkFieldTest extends \Tests\WPGraphQL\Acf\WPUnit\AcfFieldTestCase {

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
		return 'link';
	}

	public function get_expected_field_resolve_type(): ?string {
		return 'AcfLink';
	}

	public function get_expected_field_resolve_kind(): ?string {
		return 'OBJECT';
	}

	public function get_acf_clone_fragment(): string {
		return '
		fragment AcfTestGroupFragment on AcfTestGroup {
		  clonedTestLink {
		    __typename
		    title
		    url
		    target
	      }
		}
		';
	}

	public function get_clone_value_to_save():array {
		return [
			'title' => 'test',
			'url' => 'https://example.com/test',
			'target' => "_blank",
		];
	}

	public function get_expected_clone_value():array {
		return [
			'__typename' => 'AcfLink',
			'title' => 'test',
			'url' => 'https://example.com/test',
			'target' => "_blank",
		];
	}

}

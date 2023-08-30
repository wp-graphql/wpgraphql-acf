<?php

class TaxonomyFieldTest extends \Tests\WPGraphQL\Acf\WPUnit\AcfFieldTestCase {

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
		return 'taxonomy';
	}

	public function get_expected_field_resolve_type(): ?string {
		return 'AcfTermNodeConnection';
	}

	public function get_expected_field_resolve_kind(): ?string {
		return 'OBJECT';
	}

	public function get_clone_value_to_save(): array {
		return [
			$this->category->term_id
		];
	}

	public function get_acf_clone_fragment(): string {
		return '
		fragment AcfTestGroupFragment on AcfTestGroup {
			clonedTestTaxonomy {
			  nodes {
			    __typename
			    databaseId
			  }
			}
		}
		';
	}

	public function get_expected_clone_value(): array {
		return [
			'nodes' => [
				[
					'__typename' => 'Category',
					'databaseId' => $this->category->term_id,
				]
			]
		];
	}

}

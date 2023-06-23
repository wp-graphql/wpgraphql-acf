<?php

class UserFieldTest extends \Tests\WPGraphQL\Acf\WPUnit\AcfFieldTestCase {

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
		return 'user';
	}

	public function get_expected_field_resolve_type(): ?string {
		return 'AcfTestGroupTestUserToUserConnection';
	}

	public function get_expected_field_resolve_kind(): ?string {
		return 'OBJECT';
	}

	public function get_data_to_store():string {
		return $this->admin->ID;
	}

	public function get_clone_value_to_save() {
		return $this->admin->ID;
	}

	public function get_expected_clone_value() {
		return [
			'nodes' => [
				[
					'__typename' => 'User',
					'databaseId' => $this->admin->ID,
				]
			]
		];
	}

	public function get_acf_clone_fragment(): string {
		return '
		fragment AcfTestGroupFragment on AcfTestGroup {
			clonedTestUser {
			  nodes {
			    __typename
			    databaseId
			  }
			}
		}
		';
	}


}

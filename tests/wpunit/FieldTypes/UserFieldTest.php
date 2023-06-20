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

	public function get_expectation() {
		return [
			$this->expectedNode( 'post.acfTestGroup.' . $this->get_formatted_clone_field_name() . '.nodes', [
				$this->expectedField(  '__typename', 'User' ),
				$this->expectedField( 'databaseId', $this->admin->ID )
			])
		];
	}

	public function get_cloned_field_query_fragment(): string {
		return '
		fragment CloneFieldQueryFragment on AcfTestGroup {
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

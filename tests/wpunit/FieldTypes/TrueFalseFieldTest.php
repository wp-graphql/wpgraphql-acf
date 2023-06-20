<?php



class TrueFalseFieldTest extends \Tests\WPGraphQLAcf\WPUnit\AcfFieldTestCase {

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
		return 'true_false';
	}

	public function get_expected_field_resolve_type(): ?string {
		return 'Boolean';
	}

	/**
	 * @return string
	 */
	public function get_cloned_field_query_fragment():string {
		return '
			fragment CloneFieldQueryFragment on AcfTestGroup {
				clonedTestTrueFalse
			}
		';
	}

	/**
	 * @return int
	 */
	public function get_data_to_store():int {
		return 123;
	}

}

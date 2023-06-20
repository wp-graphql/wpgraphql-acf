<?php

class AcfeAdvancedLinkFieldTest extends \Tests\WPGraphQL\Acf\WPUnit\AcfeFieldTestCase {

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
		return 'acfe_advanced_link';
	}

	public function get_expected_field_resolve_kind(): ?string {
		return 'INTERFACE';
	}

	public function get_expected_field_resolve_type(): ?string {
		return 'ACFE_AdvancedLink';
	}

	/**
	 * @return string
	 */
	public function get_cloned_field_query_fragment():string {
		return '
			fragment CloneFieldQueryFragment on AcfTestGroup {
				clonedTestAcfeAdvancedLink {
				  __typename
			      linkText
			      shouldOpenInNewWindow
			      ... on ACFE_AdvancedLink_Url {
			        url
			      }
			      ... on ACFE_AdvancedLink_TermNode {
			        term {
			          uri
			        }
			      }
			      ... on ACFE_AdvancedLink_ContentNode {
			        contentNode {
			          uri
			        }
			      }
				}
			}
		';
	}

	public function get_data_to_store() {
		return [
			'type'   => 'url',
			'url'    => 'https://wpgraphql.com',
			'title'  => 'WPGraphQL.com',
			'target' => 1
		];
	}

	public function get_expected_fragment_response() {
		return [
			"__typename"            => "ACFE_AdvancedLink_Url",
			"linkText"              => 'WPGraphQL.com',
			'shouldOpenInNewWindow' => true,
			'url'                   => 'https://wpgraphql.com'
		];
	}

}

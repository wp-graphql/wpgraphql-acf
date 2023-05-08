<?php

class AcfeAdvancedLinkFieldTest extends \Tests\WPGraphQLAcf\WPUnit\AcfeFieldTestCase {

	/**
	 * @return void
	 */
	public function setUp(): void {

		// if the plugin version is before 6.1, we're not testing this functionality
		if ( ! isset( $_ENV['ACF_PRO'] ) || true !== (bool) $_ENV['ACF_PRO'] || version_compare( $this->acf_plugin_version, '6.1', 'lt' ) ) {
			$this->markTestSkipped( sprintf( 'Version "%s" does not include the ability to register custom post types, so we do not need to test the extensions of the feature', $this->acf_plugin_version ) );
		}

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

}

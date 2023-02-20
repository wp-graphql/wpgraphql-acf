<?php
namespace Tests\WPGraphQLAcf\TestCase;

/**
 * Test Case for testing WPGraphQL for ACF Functionality
 *
 * @package Tests\WPGraphQL\Acf\TestCase
 */
class WPGraphQLAcfTestCase extends \Tests\WPGraphQL\TestCase\WPGraphQLTestCase {

	/**
	 * @var \WP_User
	 */
	public $admin;

	/**
	 * @var \WP_User
	 */
	public $editor;

	/**
	 * @var \WP_User
	 */
	public $author;

	/**
	 * @return void
	 */
	public function setUp(): void {

		parent::setUp();

		codecept_debug( getenv( 'ACF_PRO' ) );
		codecept_debug( [
			'plugins' => wp_get_active_and_valid_plugins()
		]);


		// create users for use within tests
		$this->admin = self::factory()->user->create_and_get( [ 'role' => 'administrator' ] );
		$this->editor = self::factory()->user->create_and_get( [ 'role' => 'editor' ] );
		$this->author = self::factory()->user->create_and_get( [ 'role' => 'author' ] );

	}

	/**
	 * @return void
	 */
	public function tearDown(): void {

		// Delete users
		wp_delete_user( $this->author->ID );
		wp_delete_user( $this->admin->ID );
		wp_delete_user( $this->editor->ID );

		parent::tearDown();

	}

}

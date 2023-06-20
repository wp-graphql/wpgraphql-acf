<?php
namespace Tests\WPGraphQL\Acf\WPUnit;

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
	 * @var \WP_Post
	 */
	public $published_post;

	/**
	 * @var \WP_Post
	 */
	public $published_page;

	/**
	 * @var \WP_Comment
	 */
	public $comment_on_post;

	/**
	 * @var \WP_Term
	 */
	public $tag;

	/**
	 * @var \WP_Term
	 */
	public $category;

	/**
	 * @var int
	 */
	public $menu_id;

	/**
	 * @var int
	 */
	public $menu_item_id;

	/**
	 * @var string
	 */
	public $menu_location_name;

	/**
	 * Weather ACF PRO is active or not
	 *
	 * @var bool
	 */
	public $is_acf_pro;

	/**
	 * @var string
	 */
	public $acf_field_group_key;

	/**
	 * @var string
	 */
	public $test_image;

	/**
	 * @return void
	 */
	public function setUp(): void {

		parent::setUp();

		$this->clearSchema();

		// Ensure the field group keys are unique to prevent
		// conflicts across tests
		$this->acf_field_group_key = uniqid(__CLASS__, true );

		$active_plugins = get_option( 'active_plugins' );

		// whether the tests are being run with ACF pro or not
		$this->is_acf_pro = in_array( 'advanced-custom-fields-pro/acf.php', $active_plugins, true );

		$this->test_image = dirname( __FILE__, 2 ) . '/_data/images/test.png';

		// create users for use within tests
		$this->admin = self::factory()->user->create_and_get( [ 'role' => 'administrator' ] );
		$this->editor = self::factory()->user->create_and_get( [ 'role' => 'editor' ] );
		$this->author = self::factory()->user->create_and_get( [ 'role' => 'author' ] );

		$this->category = self::factory()->category->create_and_get([ 'name' => 'Test Category' ] );
		$this->tag = self::factory()->tag->create_and_get([ 'name' => 'Test Tag' ] );

		$this->published_post = self::factory()->post->create_and_get([
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_author' => $this->admin->ID,
			'post_title' => 'Test post title',
			'tax_input' => [
				'post_tag' => [ $this->tag->term_id ],
				'category' => [ $this->category->term_id ],
			],
		]);

		$this->published_page = self::factory()->post->create_and_get([
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_author' => $this->admin->ID,
			'post_title' => 'Test post title',
			'tax_input' => [
				'post_tag' => [ $this->tag->term_id ],
				'category' => [ $this->category->term_id ],
			],
		]);

		$this->comment_on_post = self::factory()->comment->create_and_get([
			'comment_post_id' => $this->published_post->ID,
			'comment_approved' => true,
			'user_id' => $this->author->ID,
		]);

		$this->menu_location_name = 'test-location';
		add_theme_support( 'nav_menus' );
		register_nav_menu( $this->menu_location_name, 'test menu...' );
		$menu_slug = 'my-test-menu';
		$created_menu = wp_create_nav_menu( $menu_slug );
		if ( ! is_wp_error( $created_menu ) ) {
			$this->menu_id = $created_menu;

			$nav_menu_item_id = wp_update_nav_menu_item(
				$this->menu_id,
				0,
				[
					'menu-item-title'     => 'Menu item',
					'menu-item-object'    => 'post',
					'menu-item-object-id' => $this->published_post->ID,
					'menu-item-status'    => 'publish',
					'menu-item-type'      => 'post_type',
				]
			);

			if ( ! empty( $nav_menu_item_id ) && ! is_wp_error( $nav_menu_item_id ) ) {
				$this->menu_item_id = $nav_menu_item_id;
			}

		}

	}

	/**
	 * @return void
	 */
	public function tearDown(): void {

		// delete terms
		wp_delete_term( $this->tag->term_id, 'post_tag' );
		wp_delete_term( $this->category->term_id, 'category' );

		// delete posts
		wp_delete_post( $this->published_post->ID, true );
		wp_delete_post( $this->published_page->ID, true );

		// Delete comments
		wp_delete_comment( (int) $this->comment_on_post->comment_post_ID, true );

		// Delete users
		wp_delete_user( $this->author->ID );
		wp_delete_user( $this->admin->ID );
		wp_delete_user( $this->editor->ID );

		// clean up the menu
		wp_delete_nav_menu( $this->menu_id );
		unregister_nav_menu($this->menu_location_name );

		$this->remove_acf_field_groups();

		$this->clearSchema();

		parent::tearDown();

	}

	/**
	 * @return void
	 */
	public function remove_acf_field_groups(): void {

		// @phpstan-ignore-next-line
		$field_groups = acf_get_local_field_groups();
		if ( ! empty( $field_groups ) ) {
			foreach ( $field_groups as $field_group ) {

				if ( empty( $field_group['key'] ) ) {
					continue;
				}
				acf_remove_local_field_group( $field_group['key'] );
			}
		}
	}

	/**
	 * @param array $acf_field_group
	 *
	 * @return mixed|string|null
	 */
	public function register_acf_field_group( array $acf_field_group = [] ) {

		// merge the defaults with the passed in options
		$config = array_merge( [
			'key'                   => $this->acf_field_group_key,
			'title'                 => 'ACF Test Group',
			'fields'                => [],
			'location'              => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					],
				],
			],
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
			'show_in_graphql'       => 1,
			'graphql_field_name'    => 'acfTestGroup',
		], $acf_field_group );

		acf_add_local_field_group( $config );

		return ! empty( $config['key'] ) ? $config['key'] : null;

	}

	/**
	 * @param array $acf_field Config array to override the defaults
	 * @param array $acf_field_group Config array to override the defaults of the field group the field will be registered to
	 *
	 * @return string
	 */
	public function register_acf_field( array $acf_field = [], array $acf_field_group = [] ): string {

		$this->register_acf_field_group( $acf_field_group );
		$key = uniqid( 'acf_test',true );

		$config = array_merge( [
			'parent'            => $this->acf_field_group_key,
			'key'               => $key,
			'label'             => 'Text',
			'name'              => 'text',
			'type'              => 'text',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => array(
				'width' => '',
				'class' => '',
				'id'    => '',
			),
			'show_in_graphql'   => 1,
			'default_value'     => '',
			'placeholder'       => '',
			'prepend'           => '',
			'append'            => '',
			'maxlength'         => '',
		], $acf_field );

		acf_add_local_field( $config );

		return $key;

	}

}

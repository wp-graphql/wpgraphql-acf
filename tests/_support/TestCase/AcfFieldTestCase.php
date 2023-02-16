<?php

namespace Tests\WPGraphQLAcf\TestCase;

use WPGraphQL\Utils\Utils;

/**
 * Test Case for ACF Fields, for testing how they map to the GraphQL Schema
 * and how they resolve
 *
 * @package Tests\WPGraphQL\Acf\TestCase
 */
abstract class AcfFieldTestCase extends WPGraphQLAcfTestCase {

	/**
	 * @var string
	 */
	public $acf_field_group_key;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->acf_field_group_key = __CLASS__;
		$this->clearSchema();
		parent::setUp();
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {

		// remove all field groups added during testing
		$this->remove_acf_field_groups();
		$this->clearSchema();
		parent::tearDown();
	}

	/**
	 * Return the acf "field_type". ex. "text", "textarea", "flexible_content", etc
	 * @return string
	 */
	abstract function get_field_type();

	/**
	 * Return the acf "field_name". This is the name that's used to store data in meta.
	 * @return string
	 */
	abstract function get_field_name();

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
	 * @param $acf_field_group
	 *
	 * @return mixed|string|null
	 */
	public function register_acf_field_group( $acf_field_group = [] ) {

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
			'graphql_types'		    => ['Post']
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
			'name'              => $this->get_field_name(),
			'type'              => $this->get_field_type(),
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

	/**
	 * @throws Exception
	 */
	public function testFieldDescriptionUsesInstructionsIfGraphqlDescriptionNotProvided(): void {

		$instructions = 'these are the instructions';

		$field_key = $this->register_acf_field([
			'instructions'      => $instructions
		]);

		$query = '
		query GetType( $name: String! ) {
		  __type( name: $name ) {
		    fields {
		      name
		      description
		    }
		  }
		}
		';

		$actual = $this->graphql( [
			'query' => $query,
			'variables' => [
				'name' => 'AcfTestGroup',
			]
		]);

		codecept_debug( $actual );

		// the query should succeed
		self::assertQuerySuccessful( $actual, [
			// the instructions should be used for the description
			$this->expectedNode( '__type.fields', [
				'name' => Utils::format_field_name( $this->get_field_name() ),
				'description' => $instructions
			]),
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}


}

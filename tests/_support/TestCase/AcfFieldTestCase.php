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
	 * @return void
	 */
	public function setUp(): void {
		$this->clearSchema();
		parent::setUp();
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Return the acf "field_type". ex. "text", "textarea", "flexible_content", etc
	 * @return string
	 */
	abstract public function get_field_type(): string;

	/**
	 * Return the acf "field_name". This is the name that's used to store data in meta.
	 * @return string
	 */
	public function get_field_name(): string {
		return 'test_' . $this->get_field_type();
	}

	/**
	 * @param array $acf_field
	 * @param array $acf_field_group
	 *
	 * @return string
	 */
	public function register_acf_field( array $acf_field = [], array $acf_field_group = [] ): string {

		// set defaults on the acf field
		// using helper methods from this class.
		// this allows test cases extending this class
		// to more easily make use of repeatedly registering
		// fields of the same type and testing them
		$acf_field = array_merge( [
			'name' => $this->get_field_name(),
			'type' => $this->get_field_type()
		], $acf_field );

		return parent::register_acf_field( $acf_field, $acf_field_group );
	}

	/**
	 * Returns a GraphQL formatted version of the field name
	 *
	 * @return string
	 */
	public function get_formatted_field_name(): string {
		return Utils::format_field_name( $this->get_field_name() );
	}

	/**
	 * @throws \Exception
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

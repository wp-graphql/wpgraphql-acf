<?php

namespace Tests\WPGraphQLAcf\WPUnit;

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
	 * Return the expected GraphQL resolve type for the field in the schema. i.e. 'String' for text field or 'Int' for number field.
	 * @return string|null
	 */
	public function get_expected_field_resolve_type(): ?string {
		return null;
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


	public function testFieldShowsInSchemaIfShowInGraphqlIsTrue() {

		$field_key = $this->register_acf_field([
			'show_in_graphql' => true
		]);

		$query = '
		query GetType( $name: String! ) {
		  __type( name: $name ) {
		    fields {
		      name
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
				'name' => $this->get_formatted_field_name(),
			]),
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}

	public function testFieldShowsInSchemaWithExpectedResolveType() {

		if ( empty( $this->get_expected_field_resolve_type() ) ) {
			$this->markTestIncomplete( sprintf( "The '%s' test needs to define an expected resolve type by defining the 'get_expected_field_resolve_type' function with a return value", __CLASS__ ) );
		}

		$field_key = $this->register_acf_field([
			'show_in_graphql' => true
		]);

		$query = '
		query GetType( $name: String! ) {
		  __type( name: $name ) {
		    fields {
		      name
		      type {
		        kind
		        name
		      }
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
			$this->expectedObject( '__type.fields', [
				'name' => $this->get_formatted_field_name(),
				'type' => [
					'kind' => 'SCALAR',
					'name' => $this->get_expected_field_resolve_type(),
				],
			])
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}

	public function testFieldShowsInSchemaIfShowInGraphqlIsNull() {

		$field_key = $this->register_acf_field([
			'show_in_graphql' => null
		]);

		$query = '
		query GetType( $name: String! ) {
		  __type( name: $name ) {
		    fields {
		      name
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
				'name' => $this->get_formatted_field_name(),
			]),
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}

	public function testFieldDoesNotShowInSchemaIfShowInGraphqlIsFalse() {

		$field_key = $this->register_acf_field([
			'show_in_graphql' => false
		]);

		$query = '
		query GetType( $name: String! ) {
		  __type( name: $name ) {
		    name
		    fields {
		      name
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
			$this->not()->expectedNode( '__type.fields', [
				'name' => $this->get_formatted_field_name(),
			]),
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

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
			// if "graphql_description" is not provided
			$this->expectedNode( '__type.fields', [
				'name' => $this->get_formatted_field_name(),
				'description' => $instructions
			]),
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}

	/**
	 * @throws \Exception
	 */
	public function testFieldDescriptionUsesGraphqlDescriptionIfProvided(): void {

		$instructions = 'these are the instructions';
		$graphql_description = 'this is the graphql description';

		$field_key = $this->register_acf_field([
			'instructions'      => $instructions,
			'graphql_description' => $graphql_description,
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
			// if "graphql_description" is not provided
			$this->expectedNode( '__type.fields', [
				'name' => $this->get_formatted_field_name(),
				'description' => $graphql_description
			]),
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}

	public function testFallbackDescriptionIsUsedIfGraphqlDescriptionAndInstructionsAreBothEmpty() {

		$field_key = $this->register_acf_field([
			'instructions'      => '', // left empty intentionally
			'graphql_description' => '', // left empty intentionally
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

			$this->expectedNode( '__type.fields', [
				// the description field should NOT be an empty string
				// there should be a fallback description if both "graphql_description"
				// and "instructions" are not provided
				$this->not()->expectedField( 'description',  self::IS_FALSY ),
			]),
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}

	// Test that a field with no explicitly defined "graphql_field_name" will
	// show in the schema with a formatted version of the field's name/label
	public function testFieldShowsInSchemaWithFormattedFieldNameIfGraphqlFieldNameIsNotPresent() {

		$field_key = $this->register_acf_field();

		$query = '
		query GetType( $name: String! ) {
		  __type( name: $name ) {
		    fields {
		      name
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
				$this->expectedField( 'name', $this->get_formatted_field_name() ),
			]),
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}

	public function testFieldShowsInSchemaWithGraphqlFieldNameIfPresent() {

		$graphql_field_name = 'customFieldName';

		$field_key = $this->register_acf_field([
			'graphql_field_name' => $graphql_field_name
		]);

		$query = '
		query GetType( $name: String! ) {
		  __type( name: $name ) {
		    fields {
		      name
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
				$this->expectedField( 'name', $graphql_field_name ),
			]),
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}

	/**
	 * @skip WPGraphQL Core does not allow connection field names to have underscores, see: https://github.com/wp-graphql/wp-graphql/blob/develop/src/Type/WPConnectionType.php#L515, https://github.com/wp-graphql/wp-graphql/blob/develop/src/Registry/TypeRegistry.php#L1029
	 *
	 * @return void
	 */
	public function testFieldShowsInSchemaWithGraphqlFieldNameHasUnderscores() {

		/**
		 * Currently, WPGraphQL core passes connection field names through `register_field` which passes
		 * the name through \WPGraphQL\Utils::format_field_name() which
		 * removes underscores.
		 *
		 * I believe we can add a new argument
		 */
		$this->markTestIncomplete( 'WPGraphQL Core does not allow connection field names to have underscores, see: https://github.com/wp-graphql/wp-graphql/blob/develop/src/Type/WPConnectionType.php#L515, https://github.com/wp-graphql/wp-graphql/blob/develop/src/Registry/TypeRegistry.php#L1029' );

		$graphql_field_name = 'custom_field_name';

		$field_key = $this->register_acf_field([
			'graphql_field_name' => $graphql_field_name
		]);

		$query = '
		query GetType( $name: String! ) {
		  __type( name: $name ) {
		    fields {
		      name
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

		// the query should succeed
		self::assertQuerySuccessful( $actual, [
			// the instructions should be used for the description
			$this->expectedNode( '__type.fields', [
				$this->expectedField( 'name', $graphql_field_name ),
			]),
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}

	/**
	 * @return void
	 */
	public function testFieldDoesNotShowInSchemaIfGraphqlFieldNameStartsWithANumber():void {

		$graphql_field_name = '123fieldName';

		$field_key = $this->register_acf_field([
			'graphql_field_name' => $graphql_field_name
		]);

		$query = '
		query GetType( $name: String! ) {
		  __type( name: $name ) {
		    fields {
		      name
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

		// the query should succeed
		self::assertQuerySuccessful( $actual, [
			// the instructions should be used for the description
			$this->not()->expectedNode( '__type.fields', [
				$this->expectedField( 'name', $graphql_field_name ),
			]),
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}

	/**
	 * @todo: implement the below tests
	 */
//	abstract public function testQueryFieldOnPostReturnsExpectedValue();
//	abstract public function testQueryFieldOnPageReturnsExpectedValue();
//	abstract public function testQueryFieldOnCommentReturnsExpectedValue();
//	abstract public function testQueryFieldOnTagReturnsExpectedValue();
//	abstract public function testQueryFieldOnCategoryReturnsExpectedValue();
//	abstract public function testQueryFieldOnUserReturnsExpectedValue();
//	abstract public function testQueryFieldOnMenuItemReturnsExpectedValue();

}

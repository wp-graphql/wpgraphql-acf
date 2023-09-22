<?php

namespace Tests\WPGraphQL\Acf\WPUnit;

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
	public $acf_plugin_version;

	/**
	 * @return void
	 */
	public function setUp(): void {
		$this->acf_plugin_version = $_ENV['ACF_VERSION'] ?? 'latest';
		$this->clearSchema();
		\WPGraphQL\Acf\Utils::clear_field_type_registry();
		do_action( 'acf/init ');
		parent::setUp();
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		$this->clearSchema();
		parent::tearDown();
	}

	/**
	 * Return the acf "field_type". ex. "text", "textarea", "flexible_content", etc
	 * @return string
	 */
	abstract public function get_field_type(): string;

	/**
	 * @return mixed
	 */
	public function get_data_to_store() {
		return 'text value...';
	}

	/**
	 * Override this in the testing class to test the field against blocks
	 *
	 * @return null
	 */
	public function get_block_data_to_store() {
		return null;
	}

	/**
	 * Override this with the block query fragment to test against for a field
	 * @return null
	 */
	public function get_block_query_fragment() {
		return null;
	}

	public function get_expected_block_fragment_response() {
		return null;
	}

	/***
	 * @return string
	 */
	public function get_query_fragment(): string {
		return '
			fragment QueryFragment on AcfTestGroup {
				testText
			}
		';
	}

	/**
	 * @return string
	 */
	public function get_cloned_field_query_fragment():string {
		return '
			fragment CloneFieldQueryFragment on AcfTestGroup {
				clonedTestText
			}
		';
	}

	/**
	 * @return mixed
	 */
	public function get_expected_fragment_response() {
		return $this->get_data_to_store();
	}

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
		return 'undefined';
	}

	/**
	 * @return string|null
	 */
	public function get_expected_field_resolve_kind(): ?string {
		return 'SCALAR';
	}

	/**
	 * @return array|null
	 */
	public function get_expected_field_of_type(): ?array {
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
	 * @param array $acf_field Config array to override the defaults
	 * @param array $acf_field_group Config array to override the defaults of the field group the field will be registered to
	 * @param bool  $should_clone_field_only bool Weather to clone only the field instead of the entire field group
	 *
	 * @return string
	 */
	public function register_cloned_acf_field( array $acf_field = [], array $acf_field_group = [], $should_clone_field_only = false ): string {

		// set defaults on the acf field
		// using helper methods from this class.
		// this allows test cases extending this class
		// to more easily make use of repeatedly registering
		// fields of the same type and testing them
		$acf_field = array_merge( [
			'name' => 'cloned_' . $this->get_field_name(),
			'type' => $this->get_field_type()
		], $acf_field );

		return parent::register_cloned_acf_field( $acf_field, $acf_field_group, $should_clone_field_only );
	}



	/**
	 * Returns a GraphQL formatted version of the field name
	 *
	 * @param bool $allow_underscores Whether to allow underscores
	 *
	 * @return string
	 */
	public function get_formatted_field_name( bool $allow_underscores = false ): string {
		return \WPGraphQL\Utils\Utils::format_field_name( $this->get_field_name(), $allow_underscores );
	}

	public function get_formatted_clone_field_name( bool $allow_underscores = false ): string {
		return \WPGraphQL\Utils\Utils::format_field_name( 'cloned_' . $this->get_field_name(), $allow_underscores );
	}

	public function get_expectation() {
		return [
			$this->expectedField( 'post.acfTestGroup.' . $this->get_formatted_clone_field_name(), $this->get_expected_fragment_response() ),
		];
	}

	// Test that the field can be queried on an ACF Block
	public function testFieldOnAcfBlock() {

		// if ACF PRO is not active, skip the test
		if ( ! defined( 'ACF_PRO' ) ) {
			$this->markTestSkipped( 'ACF Pro is not active so this test will not run.' );
		}

		// If WPGraphQL Content Blocks couldn't be activated, skip
		if ( ! defined( 'WPGRAPHQL_CONTENT_BLOCKS_DIR' ) ) {
			$this->markTestSkipped( 'This test is skipped when WPGraphQL Content Blocks is not active' );
		}

		// register ACF Block
		acf_register_block_type([
			'name' => 'test_block',
			'title' => 'Test Block',
			'post_types' => [ 'post' ],
		]);

		// register Field + Field Group to Block
		$acf_field_key = $this->register_acf_field([], [
			'location' => [
				[
					[
						'param' => 'block',
						'operator' => '==',
						'value' => 'acf/test-block',
					]
				]
			],
			'graphql_types' => [ 'AcfTestBlock' ],
		]);

		// assert the block shows in the schema as expected
		$query = '
		query GetType( $name: String! ) {
		  __type( name: $name ) {
		    fields {
		      name
		    }
		    interfaces {
		      name
		    }
		  }
		}
		';

		$actual = $this->graphql( [
			'query' => $query,
			'variables' => [
				'name' => 'AcfTestBlock',
			]
		]);

		codecept_debug( [
			'$actual' => $actual,
		]);

		// Assert that the AcfBlock is in the Schema
		// Assert the field group shows on the block as expected
		self::assertQuerySuccessful( $actual, [
			$this->expectedNode( '__type.fields', [
				'name' => Utils::format_field_name( 'AcfTestGroup' ),
			]),
			$this->expectedNode( '__type.interfaces', [
				'name' => 'AcfBlock',
			]),
			// Should implement the With${FieldGroup} Interface
			$this->expectedNode( '__type.interfaces', [
				'name' => 'WithAcfAcfTestGroup',
			])
		]);

		$actual = $this->graphql( [
			'query' => $query,
			'variables' => [
				'name' => 'AcfTestGroup',
			]
		]);


		if ( empty( $this->get_block_query_fragment() ) ) {
			$this->markTestIncomplete( 'No block query fragment defined' );
		}

		if ( empty( $this->get_expected_block_fragment_response() ) ) {
			$this->markTestIncomplete( 'No expected block fragment response defined' );
		}

		if ( empty( $this->get_block_data_to_store() ) ) {
			$this->markTestIncomplete( 'No block data to store defined' );
		}

		// Save post with content including the block + field(s)
		$content = '
		<!-- wp:paragraph -->
		<p>Test paragraph</p>
		<!-- /wp:paragraph -->

		<!-- wp:acf/test-block {"name":"acf/test-block","data":{"'. $this->get_field_name() . '":"' . $this->get_block_data_to_store()  .'","_' . $this->get_field_name() . '":"'. $acf_field_key . '"},"align":"","mode":"edit"} /-->
		';


		$post = $this->factory()->post->create([
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_author' => $this->admin,
			'post_content' => $content
		]);



		$fragment = $this->get_block_query_fragment();

		// query for the post + editorBlocks + specific block
		$query = '
		query GetPost($id:ID!){
		  post( id:$id idType: DATABASE_ID ) {
		    databaseId
		    ...on NodeWithEditorBlocks {
		      editorBlocks {
		        __typename
		        ...on WithAcfAcfTestGroup {
		          acfTestGroup {
			        ...BlockQueryFragment
			      }
		        }
		      }
		    }
		  }
		}
		' . $fragment;

		$actual = $this->graphql([
			'query' => $query,
			'variables' => [
				'id' => $post,
			],
		]);

		// assert the data is returned as expected
		self::assertQuerySuccessful( $actual, [
			$this->expectedField( 'post.databaseId', $post ),
			$this->expectedNode( 'post.editorBlocks', [
				'__typename' => 'AcfTestBlock',
				'acfTestGroup' => [
					$this->get_formatted_field_name() => $this->get_expected_block_fragment_response()
				],
			])
		] );



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
				'name' => $this->get_formatted_field_name( ),
			]),
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}

	public function testFieldShowsInSchemaWithExpectedResolveType() {

		// if ACF PRO is not active, skip the test
		if ( ! defined( 'ACF_PRO' ) || ! defined( 'ACF_MAJOR_VERSION' ) || version_compare( 'ACF_MAJOR_VERSION', '6.0', 'lt' ) ) {
			$this->markTestSkipped( 'ACF Pro is not active so this test will not run.' );
		}

		if ( 'undefined' === $this->get_expected_field_resolve_type() ) {
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
		        ofType {
		          name
		        }
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
				// expect the fields to have the formatted field name
				'name' => $this->get_formatted_field_name(),
				'type' => [
					'kind' => $this->get_expected_field_resolve_kind(),
					// Ensure the fields return the expected resolve type
					'name' => $this->get_expected_field_resolve_type(),
					'ofType' => $this->get_expected_field_of_type(),
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

	public function testFieldWithNoGraphqlFieldNameAndNameThatStartsWithNumberDoesNotShowInSchema() {

		$name = '123_test';

		$field_key = $this->register_acf_field([
			'name' => $name
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
				$this->expectedField( 'name', $name ),
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


// clone field tests

// - test cloning the field and querying for it
// - test cloning all fields of a field group and querying for them

	/**
	 * @return void
	 */
	public function testClonedFieldExistsInSchema() {

		// if ACF PRO is not active, skip the test
		if ( ! defined( 'ACF_PRO' ) ) {
			$this->markTestSkipped( 'ACF Pro is not active so this test will not run.' );
		}

		$this->register_cloned_acf_field();

		$query = '
		query GetAcfFieldGroup ($name: String! ){
		  __type( name: $name ) {
		    name
		    interfaces {
		      name
		    }
		    fields {
		      name
		    }
		    possibleTypes {
		      name
		    }
		  }
		}
		';

		$actual = $this->graphql([
			'query' => $query,
			'variables' => [
				'name' => 'InactiveFieldGroup'
			]
		]);

		codecept_debug( [
			'$actual' => $actual,
		]);

		self::assertQuerySuccessful( $actual, [
			$this->expectedNode( '__type', [
				$this->expectedField( 'name', 'InactiveFieldGroup' ),
			]),
			$this->expectedNode( '__type.interfaces', [
				$this->expectedField( 'name', 'AcfFieldGroup' ),
				$this->expectedField( 'name', 'InactiveFieldGroup_Fields' )
			]),
		]);

		$actual = $this->graphql([
			'query' => $query,
			'variables' => [
				'name' => 'InactiveFieldGroup_Fields'
			]
		]);

		codecept_debug( [
			'$actual' => $actual,
		]);

		self::assertQuerySuccessful( $actual, [
			$this->expectedNode( '__type', [
				$this->expectedField( 'name', 'InactiveFieldGroup_Fields' ),
			]),
			$this->expectedNode( '__type.fields', [
				$this->expectedField( 'name', $this->get_formatted_clone_field_name() )
			])
		]);

	}

	/**
	 * @return string
	 */
	public function get_acf_clone_fragment(): string {
		return '';
	}

	/**
	 * @return mixed
	 */
	public function get_clone_value_to_save() {
		return 'test value, dood!';
	}

	/**
	 * @return mixed
	 */
	public function get_expected_clone_value() {
		return $this->get_clone_value_to_save();
	}

	/**
	 * @return void
	 */
	public function testQueryCloneFieldOnPost(): void {

		// if ACF PRO is not active, skip the test
		if ( ! defined( 'ACF_PRO' ) ) {
			$this->markTestSkipped( 'ACF Pro is not active so this test will not run.' );
		}

		if ( empty( $this->get_acf_clone_fragment() ) ) {
			$this->markTestIncomplete( 'Test needs to define a clone query fragment' );
		}

		$field_key = $this->register_cloned_acf_field();

		update_field( $field_key, $this->get_clone_value_to_save(), $this->published_post->ID );

		$fragment = $this->get_acf_clone_fragment();

		$query = '
		query GetPost($id: ID!) {
		  post( id: $id idType: DATABASE_ID ) {
		    id
		    databaseId
		    __typename
		    ...on WithAcfAcfTestGroup {
		      acfTestGroup {
		        ...AcfTestGroupFragment
		      }
		    }
		  }
		}' . $fragment;

		$actual = $this->graphql([
			'query' => $query,
			'variables' => [
				'id' => $this->published_post->ID
			]
		]);

		self::assertQuerySuccessful( $actual, [
			$this->expectedField( 'post.databaseId', $this->published_post->ID ),
			$this->expectedField( 'post.__typename', 'Post' ),
			$this->expectedField( 'post.acfTestGroup.' . $this->get_formatted_clone_field_name(), $this->get_expected_clone_value() )
		]);
	}
}

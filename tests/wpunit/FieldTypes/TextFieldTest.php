<?php

/**
 * Text Field Test
 *
 * Tests the behavior of text field mapping to the WPGraphQL Schema
 */
class TextFieldTest extends \Tests\WPGraphQLAcf\TestCase\AcfFieldTestCase {

	/**
	 * @var int
	 */
	public $post_id;

	/**
	 * @var string
	 */
	public $acf_field_group_key;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->clearSchema();
		$this->post_id = self::factory()->post->create([
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_title' => 'Test',
			'post_content' => 'test',
		]);

	}

	public function tearDown(): void {
		wp_delete_post( $this->post_id, true );

		parent::tearDown();
	}



	/**
	 * Register a text field
	 * update value for the text field
	 * query for the
	 */
	public function testAcfTextField(): void {

		$field_key = $this->register_acf_field([
			'name'              => 'text_field',
			'type'              => 'text',
		]);

		$expected_text_1 = 'Some Text';

		// update value for the field on the post
		update_field( 'text_field', $expected_text_1, $this->post_id );

		$query = '
		query getPostById( $id: ID! ) {
			post( id:$id idType:DATABASE_ID) {
				id
				postFields {
					__typename
					fieldGroupName
					textField
				}
			}
		}
		';

		$actual = graphql([
			'query' => $query,
			'variables' => [
				'id' => $this->post_id,
			],
		]);

		codecept_debug( $actual );

		self::assertQuerySuccessful( $actual, [
			$this->expectedField( 'post.postFields.textField', $expected_text_1 ),
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
				'name' => 'PostFields',
			]
		]);

		codecept_debug( $actual );

		// the query should succeed
		self::assertQuerySuccessful( $actual, [
			// the instructions should be used for the description
			$this->expectedNode( '__type.fields', [ 'name' => 'textField' ] )
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}

	/**
	 * @throws Exception
	 */
	public function testAcfTextFieldDescriptionUsesInstructionsIfGraphqlDescriptionNotProvided(): void {

		$instructions = 'these are the instructions';

		$field_key = $this->register_acf_field([
			'name'              => 'text_field',
			'type'              => 'text',
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
				'name' => 'PostFields',
			]
		]);

		codecept_debug( $actual );

		// the query should succeed
		self::assertQuerySuccessful( $actual, [
			// the instructions should be used for the description
			$this->expectedNode( '__type.fields', [
				'name' => 'textField',
				'description' => $instructions
			]),
		] );



		// remove the local field
		acf_remove_local_field( $field_key );

	}

	/**
	 * @throws Exception
	 */
	public function testAcfTextFieldDescriptionUsesGraphqlDescriptionIfProvided(): void {

		$graphql_description = 'this is the description of the field for display in the graphql schema';

		$field_key = $this->register_acf_field([
			'name'                => 'text_field',
			'type'                => 'text',
			'graphql_description' => $graphql_description,
			'instructions'        => 'instructions for the admin ui'
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
				'name' => 'PostFields',
			]
		]);

		codecept_debug( $actual );

		self::assertQuerySuccessful( $actual, [
			$this->expectedNode( '__type.fields', [
				'name' => 'textField',
				'description' => $graphql_description
			]),
		] );

		// remove the local field
		acf_remove_local_field( $field_key );

	}

	public function testFieldResolvesWithDefaultValueIfNoValueIsSaved() {

		$default_value = uniqid( 'test default value: ', true );

		$field_key = $this->register_acf_field([
			'name'                => 'text_field',
			'type'                => 'text',
			'default_value'       => $default_value
		]);

		$query = '
		query GetPost($id:ID!){
		  post( id: $id idType: DATABASE_ID ) {
		    databaseId
		    postFields {
		      textField
		    }
		  }
		}
		';

		$actual = $this->graphql([
			'query' => $query,
			'variables' => [
				'id' => $this->post_id,
			]
		]);


		self::assertQuerySuccessful( $actual, [
			$this->expectedField( 'post.databaseId', $this->post_id ),
			$this->expectedField( 'post.postFields.textField', $default_value ),
		]);

		acf_remove_local_field( $field_key );

	}


//	// leave graphql_description empty
//	// put a string of text in the "instructions" field
//	// assert that the instructions field is used as the description for the field in the schema
//	public function testInstructionsAreUsedAsDescriptionIfGraphqlDescriptionIsEmpty() {
//
//	}
//
//	// add a description to the graphql_description field on the acf_field
//	// assert that that is the description passed to the schema
//	public function testGraphqlDescription() {
//
//	}
//
//	// leave graphql_description and instructions fields empty
//	// assert that a fallback description is output as the description in the schema
//	public function testDefaultGraphqlDescriptionIfGraphqlDescriptionAndInstructionsAreEmpty() {
//
//	}
//
//	public function testGraphqlFieldName() {
//
//	}
//
//	public function testQueryFieldOnPost() {
//
//	}
//
//	public function testQueryFieldOnComment() {
//
//	}
//
//	public function testQueryFieldOnMenuItem() {
//
//	}


}

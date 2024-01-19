<?php

use WPGraphQL\Utils\Utils;

class TaxonomyFieldTest extends \Tests\WPGraphQL\Acf\WPUnit\AcfFieldTestCase {

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
		return 'taxonomy';
	}

	public function get_expected_field_resolve_type(): ?string {
		return 'AcfTermNodeConnection';
	}

	public function get_expected_field_resolve_kind(): ?string {
		return 'OBJECT';
	}

	public function get_clone_value_to_save(): array {
		return [
			$this->category->term_id
		];
	}

	public function get_acf_clone_fragment(): string {
		return '
		fragment AcfTestGroupFragment on AcfTestGroup {
			clonedTestTaxonomy {
			  nodes {
			    __typename
			    databaseId
			  }
			}
		}
		';
	}

	public function get_expected_clone_value(): array {
		return [
			'nodes' => [
				[
					'__typename' => 'Category',
					'databaseId' => $this->category->term_id,
				]
			]
		];
	}

	public function get_block_query_fragment() {
		return '
		fragment BlockQueryFragment on AcfTestGroup {
		  testTaxonomy {
		    nodes {
		      __typename
		      databaseId
		    }
		  }
		}
		';
	}

	public function get_block_data_to_store() {
		return [ $this->category->term_id, $this->tag->term_id ];
	}

	public function get_expected_block_fragment_response() {
		return [
			'nodes' => [
				[
					'__typename' => 'Category',
					'databaseId' => $this->category->term_id,
				],
				[
					'__typename' => 'Tag',
					'databaseId' => $this->tag->term_id,
				],
			]
		];
	}

	public function testQueryTaxononomyFieldOnBlock() {

		// if ACF PRO is not active, skip the test
		if ( ! defined( 'ACF_PRO' ) ) {
			$this->markTestSkipped( 'ACF Pro is not active so this test will not run.' );
		}

		// If WPGraphQL Content Blocks couldn't be activated, skip
		if ( ! defined( 'WPGRAPHQL_CONTENT_BLOCKS_DIR' ) ) {
			$this->markTestSkipped( 'This test is skipped when WPGraphQL Content Blocks is not active' );
		}
		
		acf_register_block_type([
			'name' => 'block_with_category_field',
			'title' => 'Block with Category Field',
			'post_types' => [ 'post' ],
		]);

		$field_key = $this->register_acf_field([
			'type' => 'taxonomy',
			'name' => 'Category Test',
			'show_in_graphql' => true,
			'graphql_field_name' => 'category',
			'required' => 1,
			'taxonomy' => 'category',
			'add_term' => 0,
			'save_terms' => 0,
			'load_terms' => 0,
			'return_format' => 'object',
			'field_type' => 'select',
			'multiple' => 0,
			'bidirectonal' => 0,
			'bidirectional_target' => [],
		], [
			'name' => 'Block Taxonomy Test',
			'graphql_field_name' => 'BlockTaxonomyTest',
			'location' => [
				[
					[
						'param' => 'block',
						'operator' => '==',
						'value' => 'acf/block-with-category-field',
					]
				]
			],
			'graphql_types' => [ 'AcfBlockWithCategoryField' ],
		]);

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
				'name' => 'AcfBlockWithCategoryField',
			]
		]);

		codecept_debug( [
			'$actual' => $actual,
		]);

		// Assert that the AcfBlock is in the Schema
		// Assert the field group shows on the block as expected
		self::assertQuerySuccessful( $actual, [
			$this->expectedNode( '__type.fields', [
				'name' => Utils::format_field_name( 'blockTaxonomyTest' ),
			]),
			$this->expectedNode( '__type.interfaces', [
				'name' => 'AcfBlock',
			]),
			// Should implement the With${FieldGroup} Interface
			$this->expectedNode( '__type.interfaces', [
				'name' => 'WithAcfBlockTaxonomyTest',
			])
		]);

		$category_id = self::factory()->category->create([
			'name' => uniqid( 'Test Category', true ),
		]);

		codecept_debug( [
			'$field_key' => $field_key,
			'$category_id' => $category_id,
		]);

		$content = '
		<!-- wp:acf/block-with-category-field {"name":"acf/block-with-category-field","data":{"' . $field_key . '":"' . $category_id . '"},"align":"","mode":"edit"} /-->
		';

		$post_id = self::factory()->post->create([
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_title' => 'Test Block With Taxonomy Field',
			'post_content' => $content,
		]);

		$query = '
		query GetPostWithBlocks( $postId: ID! ){
		  post(id:$postId idType:DATABASE_ID) {
		    id
		    title
		    ...Blocks
		  }
		}

		fragment Blocks on NodeWithEditorBlocks {
		  editorBlocks {
		    __typename
		    ...on AcfBlockWithCategoryField {
		      blockTaxonomyTest {
		        category {
		          nodes {
		            __typename
		            databaseId
		          }
		        }
		      }
		    }
		  }
		}
		';

		$variables = [
			'postId' => $post_id,
		];

		$actual = self::graphql([
			'query'     => $query,
			'variables' => $variables,
		]);

		codecept_debug( [
			'$actual' => $actual,
		]);

		self::assertQuerySuccessful( $actual, [
			$this->expectedNode( 'post.editorBlocks', [
				// Expect a block with the __typename AcfBlockWithCategoryField
				$this->expectedField('__typename', 'AcfBlockWithCategoryField' ),
				$this->expectedNode( 'blockTaxonomyTest.category.nodes', [
					'__typename' => 'Category',
					'databaseId' => $category_id,
				], 0 ),
			], 0 ),
		]);

		$category_2_id = self::factory()->category->create([
			'name' => uniqid( 'Test Category 2', true ),
		]);

		$content = '
		<!-- wp:acf/block-with-category-field {"name":"acf/block-with-category-field","data":{"' . $field_key . '":["' . $category_id . '", "' . $category_2_id . '"]},"align":"","mode":"edit"} /-->
		';

		$post_id = self::factory()->post->create([
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_title' => 'Test Block With Taxonomy Field',
			'post_content' => $content,
		]);

		$actual = self::graphql([
			'query'     => $query,
			'variables' => $variables,
		]);

		codecept_debug( [
			'$actual' => $actual,
		]);

		self::assertQuerySuccessful( $actual, [
			$this->expectedNode( 'post.editorBlocks', [
				// Expect a block with the __typename AcfBlockWithCategoryField
				$this->expectedField('__typename', 'AcfBlockWithCategoryField' ),
				$this->expectedNode( 'blockTaxonomyTest.category.nodes', [
					'__typename' => 'Category',
					'databaseId' => $category_id,
				], 0 ),
				$this->expectedNode( 'blockTaxonomyTest.category.nodes', [
					'__typename' => 'Category',
					'databaseId' => $category_2_id,
				], 0 ),
			], 0 ),
		]);

		wp_delete_post( $post_id );
		wp_delete_term( $category_id, 'category' );
		wp_delete_term( $category_2_id, 'category' );
	}
}

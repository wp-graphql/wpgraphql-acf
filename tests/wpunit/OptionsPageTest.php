<?php

class OptionsPageTest extends \Tests\WPGraphQLAcf\WPUnit\WPGraphQLAcfTestCase {


	public function setUp():void {
		if ( ! isset( $_ENV['ACF_PRO'] ) || true !== (bool) $_ENV['ACF_PRO'] ) {
			$I->markTestSkipped( 'Options Pages are an ACF PRO feature. Skipping tests.' );
		}

		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	public function registerOptionsPage( $title = 'My Options Page', $config = [] ) {
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			$this->markTestIncomplete( 'ACF Options Pages are not available in this test environment' );
		}

		// register options page
		acf_add_options_page(
			array_merge( [
				'page_title' => $title,
				'menu_title' => __( 'My Options Page' ),
				'menu_slug'  => 'my-options-page',
				'capability' => 'edit_posts',
				// options pages will show in the Schema unless set to false
				//          'show_in_graphql'   => false,
			], $config )
		);
	}

	public function testAcfOptionsPageIsQueryableInSchema() {

		$this->registerOptionsPage();

		$expected_value = uniqid( 'test', true );

		// Save a value to the ACF Option Field
		// see: https://www.advancedcustomfields.com/resources/update_field/#update-a-value-from-different-objects
		if ( function_exists( 'update_field' ) ) {
			update_field( 'text', $expected_value, 'option' );
		}

		$this->register_acf_field( [], [
			'graphql_field_name' => 'OptionsFields',
			'location' => [
				[
					[
						'param' => 'options_page',
						'operator' => '==',
						'value' => 'my-options-page',
					],
				],
			],
		]);

		$query = '
		{
		  myOptionsPage {
		    optionsFields {
		      text
		    }
		  }
		}
		';

		$actual = $this->graphql([
			'query' => $query,
		]);

		self::assertQuerySuccessful( $actual, [
			// the instructions should be used for the description
			$this->expectedField( 'myOptionsPage.optionsFields.text', $expected_value ),
		] );

		$query = '
		query GetType( $name: String! ) {
		  __type( name: $name ) {
		    name
		  }
		}
		';

		$actual = $this->graphql( [
			'query' => $query,
			'variables' => [
				'name' => 'MyOptionsPage',
			]
		]);

		self::assertQuerySuccessful( $actual, [
			$this->expectedField( '__type.name', 'MyOptionsPage' )
		]);

	}

	// @todo:
	// - options page not in Schema if "show_in_graphql" set to false
	/**
	 * @throws Exception
	 */
	public function testOptionsPageNotInSchemaIfShowInGraphqlIsFalse() {

		acf_add_options_page(
			[
				'page_title' => 'ShowInGraphQLFalse',
				'menu_title' => __( 'Show in GraphQL False' ),
				'menu_slug'  => 'show-in-graphql-false',
				'capability' => 'edit_posts',
				// options pages will show in the Schema unless set to false
				'show_in_graphql'   => false,
			]
		);

		$options_pages = acf_get_options_pages();

		codecept_debug( [
			'$pages' => $options_pages,
		]);

		$this->register_acf_field( [], [
			'graphql_field_name' => 'showInGraphqlFields',
			'location' => [
				[
					[
						'param' => 'options_page',
						'operator' => '==',
						'value' => 'show-in-graphql-false',
					],
				],
			],
		]);

		// Ensure the options page was registered to ACF Options Pages properly
		$this->assertTrue( array_key_exists( 'show-in-graphql-false', $options_pages ) );

		$query = '
		{
		  showInGraphQLFalse {
		    showInGraphqlFields {
		      text
		    }
		  }
		}
		';

		$actual = $this->graphql([
			'query' => $query,
		]);

		$this->assertArrayHasKey( 'errors', $actual );

		$query = '
		query GetType( $name: String! ) {
		  __type( name: $name ) {
		    name
		  }
		}
		';

		$actual = $this->graphql( [
			'query' => $query,
			'variables' => [
				'name' => 'ShowInGraphQLFalse',
			]
		]);

		$this->assertQueryError( $actual, [
			$this->expectedField( '__type', self::IS_NULL )
		]);

	}

	// - options page shows with different name if "graphql_field_name" is set
	public function testOptionsPageRespectsGraphqlFieldName() {
		acf_add_options_page(
			[
				'page_title' => 'CustomGraphqlName',
				'menu_title' => __( 'Show in GraphQL False' ),
				'menu_slug'  => 'custom-graphql-name',
				'capability' => 'edit_posts',
				// options pages will show in the Schema unless set to false
				'graphql_field_name'   => 'MyCustomOptionsName',
			]
		);

		$expected_value = uniqid( 'test', true );

		// Save a value to the ACF Option Field
		// see: https://www.advancedcustomfields.com/resources/update_field/#update-a-value-from-different-objects
		if ( function_exists( 'update_field' ) ) {
			update_field( 'text', $expected_value, 'option' );
		}

		$this->register_acf_field( [], [
			'graphql_field_name' => 'OptionsFields',
			'location' => [
				[
					[
						'param' => 'options_page',
						'operator' => '==',
						'value' => 'custom-graphql-name',
					],
				],
			],
		]);

		$query = '
		{
		  myCustomOptionsName {
		    optionsFields {
		      text
		    }
		  }
		}
		';

		$actual = $this->graphql([
			'query' => $query,
		]);

		self::assertQuerySuccessful( $actual, [
			// the instructions should be used for the description
			$this->expectedField( 'myCustomOptionsName.optionsFields.text', $expected_value ),
		] );

		$query = '
		query GetType( $name: String! ) {
		  __type( name: $name ) {
		    name
		  }
		}
		';

		$actual = $this->graphql( [
			'query' => $query,
			'variables' => [
				'name' => 'MyCustomOptionsName',
			]
		]);

		self::assertQuerySuccessful( $actual, [
			$this->expectedField( '__type.name', 'MyCustomOptionsName' )
		]);
	}

	public function testQueryOptionsPageAsNode() {

		acf_add_options_page(
			[
				'page_title' => 'OptionsPageNode',
				'menu_title' => __( 'Options Page Node' ),
				'menu_slug'  => 'options-page-node',
				'capability' => 'edit_posts',
				// options pages will show in the Schema unless set to false
				'graphql_field_name'   => 'OptionsPageNode',
			]
		);

		$query = '
		query GetOptionsPage($id: ID!) {
		  node(id:$id) {
		    id
		    __typename
		    ...on AcfOptionsPage {
		      menuTitle
		    }
		  }
        }
		';

		$id = \GraphQLRelay\Relay::toGlobalId( 'acf_options_page', 'options-page-node' );

		$actual = $this->graphql([
			'query' => $query,
			'variables' => [
				'id' => $id
			]
		]);

		self::assertQuerySuccessful( $actual, [
			$this->expectedField( 'node.__typename', 'OptionsPageNode' ),
			$this->expectedField( 'node.id', $id )
		]);

	}

}

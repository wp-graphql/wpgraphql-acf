<?php

class TestCloneFieldsCest {

	public function _before( FunctionalTester $I, \Codeception\Scenario $scenario ) {

		$inactive_group = 'acf-export-clone-repeater.json';
		$I->importJson( $inactive_group );

		$pro_fields = 'tests-acf-pro-kitchen-sink.json';
		$I->importJson( $pro_fields );

		if ( ! $I->haveAcfProActive() ) {
			$I->markTestSkipped( 'Skipping test. ACF Pro is required for clone fields' );
		}

	}

	public function testClonedFieldGroupAppliedAsInterface( FunctionalTester $I ) {

		$I->wantTo( 'Test Cloned Field Groups are applied as Interface' );

		$query = '
		query GetType($type: String!) {
		  __type(name: $type) {
		    name
		    kind
		    interfaces {
		      name
		    }
		    fields {
		      name
		      type {
	            name
	            kind
	            ofType {
	              name
	            }
	          }
		    }
		  }
		}
		';

		$I->haveHttpHeader( 'Content-Type', 'application/json' );
		$I->sendPost( '/graphql', json_encode([
			'query' => $query,
			'variables' => [
				'type' => 'Flowers'
			]
		]));

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = $I->grabResponse();
		$response = json_decode( $response, true );

		$I->assertNotEmpty( $response['data']['__type']['fields'] );
		$I->assertNotEmpty( $response['data']['__type']['interfaces'] );

		$fields = array_map( static function( $field ) {
			return $field['name'];
		}, $response['data']['__type']['fields'] );

		$interfaces = array_map( static function( $interface ) {
			return $interface['name'];
		}, $response['data']['__type']['interfaces'] );

		$I->assertTrue( in_array( 'AcfFieldGroup', $interfaces, true ) );
		$I->assertTrue( in_array( 'Flowers_Fields', $interfaces, true ) );

		$I->assertTrue( in_array( 'color', $fields, true ) );
		$I->assertTrue( in_array( 'datePicker', $fields, true ) );
		$I->assertTrue( in_array( 'avatar', $fields, true ) );
		$I->assertTrue( in_array( 'range', $fields, true ) );

		$I->haveHttpHeader( 'Content-Type', 'application/json' );
		$I->sendPost( '/graphql', json_encode([
			'query' => $query,
			'variables' => [
				'type' => 'Plants'
			]
		]));

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = $I->grabResponse();
		$response = json_decode( $response, true );

		$I->assertNotEmpty( $response['data']['__type']['fields'] );
		$I->assertNotEmpty( $response['data']['__type']['interfaces'] );

		$fields = array_map( static function( $field ) {
			return $field['name'];
		}, $response['data']['__type']['fields'] );

		$interfaces = array_map( static function( $interface ) {
			return $interface['name'];
		}, $response['data']['__type']['interfaces'] );

		$I->assertTrue( in_array( 'AcfFieldGroup', $interfaces, true ) );

		// Since the Plants Field Group clones the "Flowers" field group it implements the Flowers_Fields interface
		$I->assertTrue( in_array( 'Flowers_Fields', $interfaces, true ) );

		// The Field Group itself implements Plants_Fields
		$I->assertTrue( in_array( 'Plants_Fields', $interfaces, true ) );

		$I->assertTrue( in_array( 'color', $fields, true ) );
		$I->assertTrue( in_array( 'datePicker', $fields, true ) );
		$I->assertTrue( in_array( 'avatar', $fields, true ) );
		$I->assertTrue( in_array( 'range', $fields, true ) );

		// This field used to cause things to explode so this test ensures things
		// are working properly when cloning field groups that contain repeater fields
		$I->assertTrue( in_array( 'landMineRepeater', $fields, true ) );

		$field = $this->findField( 'landMineRepeater', $fields );

		$I->assertSame( 'LIST', $field['type']['kind'] );
		$I->assertSame( 'FlowersLandMineRepeater', $field['type']['ofType']['name'] );

		// Cloned Repeater is a prefixed clone field, we can assert that it returns a nested Object Type
		$I->assertTrue( in_array( 'clonedRepeater', $fields, true ) );

		// Find the clonedRepeaterField
		$field = $this->findField( 'clonedRepeater', $fields );

		$I->assertSame( 'OBJECT', $field['type']['kind'] );
		$I->assertNull( $field['type']['ofType']['name'] );
		$I->assertSame( 'PlantsClonedRepeater', $field['type']['name'] );

		// Find the cloneRoots field (clone of the "flowers" field group, but with "prefix_name" set)
		$field = $this->findField( 'cloneRoots', $fields );

		$I->assertSame( 'OBJECT', $field['type']['kind'] );
		$I->assertNull( $field['type']['ofType']['name'] );
		$I->assertSame( 'PlantsCloneRoots', $field['type']['name'] );

	}

	public function findField( $name, $fields ) {
		return array_filter( array_map( static function( $field ) use ( $name ) {
			return $field['name'] === $name ? $field : null;
		}, $fields ) );
	}


}

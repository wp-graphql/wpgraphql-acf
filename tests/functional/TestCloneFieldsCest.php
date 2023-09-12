<?php

class TestCloneFieldsCest {

	public function _before( FunctionalTester $I, \Codeception\Scenario $scenario ) {

		$inactive_group = 'tests-inactive-group-for-cloning.json';
		$I->importJson( $inactive_group );

		$pro_fields = 'tests-acf-pro-kitchen-sink.json';
		$I->importJson( $pro_fields );

	}

	public function testClonedFieldGroupAppliedAsInterface( FunctionalTester $I ) {

		$I->wantTo( 'Test Cloned Field Groups are applied as Interface' );

		$I->haveHttpHeader( 'Content-Type', 'application/json' );
		$I->sendPost( '/graphql', json_encode([
			'query' => '
			query GetType($type: String!) {
			  __type(name: $type) {
			    name
			    kind
			    interfaces {
			      name
			    }
			  }
			}
			',
			'variables' => [
				'type' => 'AcfProKitchenSink'
			]
		]));

		$I->seeResponseCodeIs( 200 );
		$I->seeResponseIsJson();
		$response = $I->grabResponse();
		$response = json_decode( $response, true );

		$I->assertNotEmpty( $response['data']['__type']['interfaces'] );

		$interfaces = array_map( static function( $interface ) {
			return $interface['name'];
		}, $response['data']['__type']['interfaces'] );

		$I->assertTrue( in_array( 'InactiveGroupForCloning_Fields', $interfaces, true ) );
	}

}

<?php

class BasicFieldsTest extends \Codeception\TestCase\WPTestCase {

	public function _setUp() {
		$this->delete_all_field_groups();
		$this->import_field_group( dirname( __FILE__, 2 ) . '/_data/acf-basic-fields.json' );

		parent::_setUp(); // TODO: Change the autogenerated stub

	}

	public function _tearDown() {
		return parent::_tearDown(); // TODO: Change the autogenerated stub
		$this->delete_all_field_groups();
	}

	public function delete_all_field_groups() {
		$acf_field_groups = acf_get_field_groups();

		codecept_debug( [
			'ALL_FIELD_GROUPS' => $acf_field_groups,
		]);

		foreach ( $acf_field_groups as $field_group ) {
			acf_delete_field_group( $field_group['key'] );
		}
	}

	public function import_field_group( $path ): void {

		if ( ! file_exists( $path ) ) {
			throw new Exception( sprintf( 'The path "%s" is not valid', $path ) );
		}

		$field_group_contents = json_decode( file_get_contents( $path ), true );



		$before = acf_get_field_groups();
		acf_import_field_group( (array) $field_group_contents );
		$after = acf_get_field_groups();
		wp_send_json([
			'contents' => $field_group_contents,
			'before' => $before,
			'after' => $after,
		]);
		WPGraphQL::clear_schema();
	}

	public function testImportFields() {

		$this->assertTrue( true );

//		$query = '
//		{
//		  posts {
//		    nodes {
//		      id
//		      title
//		      basicFields {
//		        basicText
//		      }
//		    }
//		  }
//		}
//		';
//
//		$actual = graphql([
//			'query' => $query,
//		]);
//
//		codecept_debug( $actual );
//
////		wp_send_json( [
////			'acfFieldGroups' => acf_get_field_groups(),
////			'actual' => $actual,
////		]);
//
//		$this->assertArrayNotHasKey( 'errors', $actual );

	}

}

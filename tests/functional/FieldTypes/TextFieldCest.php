<?php

class TextFieldCest {

	public function _before( FunctionalTester $I ) {

		$json_file = 'acf-export-2023-01-26.json';
		$I->importJson( $json_file );

	}

	public function seeShowInGraphqlField( FunctionalTester $I ) {
		$I->loginAsAdmin();
		$I->amOnPage( '/wp-admin/edit.php?post_type=acf-field-group' );

		$I->see( 'Foo Name' );

		$I->click( 'Foo Name' );


		$I->see( 'Edit Field Group' );
		$I->see( 'Field Type' );
		$I->see( 'Show in GraphQL' );

	}

	public function saveShowInGraphqlField( FunctionalTester $I ) {
		$I->loginAsAdmin();
		$I->amOnPage( '/wp-admin/edit.php?post_type=acf-field-group' );
		$I->see( 'Foo Name' );
		$I->click( 'Foo Name' );
		$I->see( 'Edit Field Group' );
		$I->see( 'Field Type' );
		$I->see( 'Show in GraphQL' );

		// ensure the description makes note about the possibility of breaking changes. The text might change, so we're not
		// testing exact message, just that the "show_in_graphql" description notes the possibility
		// of breaking changes
		$I->see( 'breaking change', '//div[@data-name="show_in_graphql"]//p[@class="description"]' );

		// default value is checked
		$I->canSeeCheckboxIsChecked( 'Show in GraphQL' );

		// Uncheck the option
		$I->uncheckOption( 'Show in GraphQL' );

		// submit the form
		$I->click('#submitpost button.acf-publish' );

		// make sure there's no errors
		$I->dontSeeElement( '#message.notice-error' );

		// Make sure the save succeeded
		$I->seeElement( '#message.notice-success' );

		// The checkbox should not be checked now
		$I->cantSeeCheckboxIsChecked( 'Show in GraphQL' );

	}

	/**
	 * Test saving the "graphql_field_name"
	 *
	 * @param \FunctionalTester $I
	 *
	 * @return void
	 */
	public function testSaveGraphqlFieldNameWorks( FunctionalTester $I ) {

		$I->loginAsAdmin();
		$I->amOnPage( '/wp-admin/edit.php?post_type=acf-field-group' );

		$I->see( 'Foo Name' );
		$I->click( 'Foo Name' );

		$I->see( 'Edit Field Group' );
		$I->see( 'Show in GraphQL' );
		$I->canSeeCheckboxIsChecked( 'Show in GraphQL' );

		// The fields label from import
		$I->see( 'Foo Label' );

		// Select the "text" field group type
		$I->selectOption( 'Field Type', 'text' );

		// The placeholder for the field should be the formatted
		// version of the field's label (Foo Label)
		$placeholder = $I->grabAttributeFrom( '//div[@data-name="graphql_field_name"]//input[@type="text"]', 'placeholder');
		$I->assertSame( $placeholder, 'fooLabel' );

		// Add a field label
		$I->fillField( 'Field Label', 'Text Field' );
		$I->fillField( 'Field Name', 'text_field' );

		// fill in GraphQL Field Name
		$I->fillField( 'GraphQL Field Name', 'textField' );

		// submit the form
		$I->click('#submitpost button.acf-publish' );

		// make sure there's no errors
		$I->dontSeeElement( '#message.notice-error' );

		// Make sure the save succeeded
		$I->seeElement( '#message.notice-success' );
		$I->see( 'Field group updated.' );
		$I->seeInField('GraphQL Field Name', 'textField');

	}

	/**
	 * Test saving the "graphql_field_name"
	 *
	 * @param \FunctionalTester $I
	 *
	 * @return void
	 */
	public function testSaveGraphqlDescriptionWorks( FunctionalTester $I ) {

		$I->loginAsAdmin();
		$I->amOnPage( '/wp-admin/edit.php?post_type=acf-field-group' );

		$I->see( 'Foo Name' );
		$I->click( 'Foo Name' );

		$I->see( 'Edit Field Group' );
		$I->see( 'GraphQL Description' );
		$I->canSeeCheckboxIsChecked( 'Show in GraphQL' );

		// Select the "text" field group type
		$I->selectOption( 'Field Type', 'text' );

		// Add a field label
		$I->fillField( 'Field Label', 'Text Field' );
		$I->fillField( 'Field Name', 'text_field' );

		// fill in GraphQL Description
		$I->fillField( 'GraphQL Description', 'test description' );

		// submit the form
		$I->click('#submitpost button.acf-publish' );

		// make sure there's no errors
		$I->dontSeeElement( '#message.notice-error' );

		// Make sure the save succeeded
		$I->seeElement( '#message.notice-success' );
		$I->see( 'Field group updated.' );
		$I->seeInField('GraphQL Description', 'test description' );

	}

	/**
	 * Test saving the "graphql_field_name" with a starting number leads to a validation error
	 *
	 * @param \FunctionalTester $I
	 *
	 * @return void
	 */
	public function testSaveGraphqlFieldNameWithStartingNumberShowsGraphqlDebugMessage( FunctionalTester $I ) {



	}

	public function testSaveGraphqlFieldNameWithSpacesIsReformatted() {

	}

	public function testSaveGraphqlFieldNameWithUnderscoresWorks() {

	}

}

// test that the text field in the admin UI has the following field settings
// - show_in_graphql
// - graphql_field_name
// - graphql_description (
// - graphql_non_null (true_false)

// Tes that the "text" field in the admin UI _DOES NOT_ have the following field settings (we don't want a regression accidentally adding these fields to unintended field types)
// - graphql_resolve_type

// Test the behavior of the following field settings
// - show_in_graphql
//   - ui should be a checkbox
//   - default is checked
//   - changing the value and saving shows the changed value when page reloads
//   - if changed from true to false, show validation message that the field was removed from the schema which could cause breaking changes to the schema
//
// - graphql_field_name
//   - ui should be a text field
//   - on save, validate the field
//     - if empty, set value to results of \WPGraphQL\Utils::format_field_name( $acf_field['name] );
//     - validate value
//     - field should be a valid GraphQL field name (https://spec.graphql.org/October2021/#sec-Names)
//       - underscores _should_ be allowed, even though they're not allowed by default in \WPGraphQL\Utils::format_field_name()
//     - valid field name should be saved
//     - invalid field name should not be saved, error message should be shown in the admin UI
//
// - graphql_non_null
//   - ui should be a checkbox
//   - default value should be unchecked
//   - field description should educate users about the impact of this change
//     - i.e. changing this field can cause breaking changes to behavior
//   - changing the value and saving shows the changed value when page reloads
//   -

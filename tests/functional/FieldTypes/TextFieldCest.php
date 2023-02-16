<?php

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

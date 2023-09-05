# Changelog

## 2.0.0-beta.5.0.0

### New Features

- [#81](https://github.com/wp-graphql/wpgraphql-acf/pull/81): feat: 🚀 ACF Blocks Support. Query ACF Blocks using WPGraphQL!! 🚀 (when [WPGraphQL Content Blocks v1.2.0+](https://github.com/wpengine/wp-graphql-content-blocks/releases/) is active)

### Chores / Bugfixes

- [#77](https://github.com/wp-graphql/wpgraphql-acf/pull/77): fix: js error when clone fields are added to a field group.
- [#80](https://github.com/wp-graphql/wpgraphql-acf/pull/81): ci: put the files in a subfolder when build zip in github


## v2.0.0-beta.4.0.0

### Breaking Changes

#### Hook / Filter Name Changes

This release contains breaking changes. Action and Filter names have been renamed to be more consistent across the codebase and to follow a "namespace" pattern similar to core ACF.

If you have custom code that was hooking into / filtering WPGraphQL for ACF, check your hook names.

There's a table documenting the name changes here: [https://github.com/wp-graphql/wpgraphql-acf/pull/67#issue-1818715865](https://github.com/wp-graphql/wpgraphql-acf/pull/67#issue-1818715865)


#### LocationRules namespace change

The LocationRules class has changed from `\WPGraphQL\Acf\LocationRules` to `\WPGraphQL\Acf\LocationRules\LocationRules`. It's unlikely that you have custom code referencing this class, but if you do, you'll need to update references.

### New Features

- [#67](https://github.com/wp-graphql/wpgraphql-acf/pull/67): [BREAKING] feat: introduce new hook and filter names to reduce chances of conflicting with custom code extending the previous version of the plugin.

### Chores / Bugfixes

- [#62](https://github.com/wp-graphql/wpgraphql-acf/pull/62): fix: prepare values when return_format is set to "array"
- [#68](https://github.com/wp-graphql/wpgraphql-acf/pull/68): fix: fieldGroupName was always returning null
- [#69](https://github.com/wp-graphql/wpgraphql-acf/pull/69): fix: graphql_description default value incorrect
- [#73](https://github.com/wp-graphql/wpgraphql-acf/pull/73): fix: replace filter_input with sanitize_text_field

## v2.0.0-beta.3.1.0

### Chores / Bugfixes

- [#60](https://github.com/wp-graphql/wpgraphql-acf/pull/60): fix: graphql_field_names were being set incorrectly when adding new fields.

## v2.0.0-beta.3.0.0

### New Features

- [#46](https://github.com/wp-graphql/wpgraphql-acf/pull/46): feat: add "graphql_non_null" setting for fields
- [#54](https://github.com/wp-graphql/wpgraphql-acf/pull/54): fix: change namespace from WPGraphQLAcf to WPGraphQL\ACF

### Chores / Bugfixes

- [#55](https://github.com/wp-graphql/wpgraphql-acf/pull/55): fix: cline fields not resolving properly
- [#47](https://github.com/wp-graphql/wpgraphql-acf/pull/47): fix: allow inactive field groups to show in the Schema (but prevent their location rules from being set)

## v2.0.0-beta.2.4.0

### New Features

- [#47](https://github.com/wp-graphql/wpgraphql-acf/pull/47) feat: Expose field groups that are not active, but set to "show_in_graphql" to allow inactive groups to be cloned and used in the schema.

## v2.0.0-beta.2.3.0

### New Features

- [#38](https://github.com/wp-graphql/wpgraphql-acf/pull/38) feat: ACF Options Page support

## 2.0.0-beta.2.2.0

### New Features

- [#38](https://github.com/wp-graphql/wpgraphql-acf/pull/38) feat: ACF Extended / ACF Extended Pro support

## 2.0.0-beta.2.1.0

- [#35](https://github.com/wp-graphql/wpgraphql-acf/pull/35) feat: add Appsero opt-in telemetry.
- feat: add checks for dependencies (WPGraphQL, ACF, min versions, etc) before loading functionality. Show admin notice and graphql_debug messages if pre-reqs aren't satisfied.

## 2.0.0-beta.2.0.1 to 2.0.0-beta.2.0.5

### Chores / Bugfixes:

- [#33](https://github.com/wp-graphql/wpgraphql-acf/pull/33) fix: failing github workflow for uploading the graphql schema artifact upon releases. No functional changes for users.
- chore: debugging github workflow for uploading Schema Artifact

## 2.0.0-beta.2

### Chores / Bugfixes:

- [#31](https://github.com/wp-graphql/wpgraphql-acf/pull/31) fix: Prevent fatal error when editing ACF Field Groups

## 2.0.0-beta-1

- Initial public release. "Quiet Beta".

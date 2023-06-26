# Changelog

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

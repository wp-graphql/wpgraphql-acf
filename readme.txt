=== WPGraphQL ACF ===
Contributors: jasonbahl, wpgraphql
Tags: GraphQL, ACF, API, NextJS, Faust, Headless, Decoupled, React, Vue, Svelte, JSON, REST
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable Tag: 2.0.0
License: GPL-3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Integrate ACF with GraphQL for powerful querying.

=== Description ===

WPGraphQL for Advanced Custom Fields is a free, open-source WordPress plugin that adds ACF Fields and Field Groups to the WPGraphQL Schema.

= Create ACF Field Groups =

Create ACF Field Groups and Fields using the ACF User Interface, register them with PHP, or leverage ACF local JSON. Each field group and the fields within it can be configured to "Show in GraphQL."

= Query your fields with GraphQL =

Once your field groups and fields are configured to "Show in GraphQL," they become available in the GraphQL Schema for querying.

= Supported Field Types =

WPGraphQL for ACF provides support for most built-in field types of ACF (free & PRO) and extends support to most field types from ACF Extended (free & PRO).

== Updating ==

For non-major version updates, automatic updates usually should work smoothly, but we still recommend you back up your site and test on a staging site.

Before updating WPGraphQL for ACF, review the release notes on [GitHub](https://github.com/wp-graphql/wpgraphql-acf/releases).

We follow Semantic Versioning (Semver) for meaningful releases:

- *MAJOR* version for incompatible API changes,
- *MINOR* version for backwards-compatible functionality additions,
- *PATCH* version for backwards-compatible bug fixes.

Learn more about Semver at [semver.org](https://semver.org).

== Privacy Policy ==

WPGraphQL for Advanced Custom Fields uses [Appsero](https://appsero.com) SDK to collect telemetry data upon user confirmation, helping us troubleshoot problems and improve the product.

The Appsero SDK **doesn't collect data by default** and only starts gathering basic telemetry data when a user allows it via the admin notice. No data is collected without user consent.

Learn more about how [Appsero collects and uses data](https://appsero.com/privacy-policy/).

== Upgrade Notice ==

= 2.0.0 =

This release is a complete re-architecture of WPGraphQL for ACF, introducing breaking changes to the GraphQL Schema and PHP API. Please read the [upgrade guide](https://acf.wpgraphql.com/upgrade-guide/) before upgrading.

== Changelog ==

= 2.0.0 =

- Initial release on WordPress.org. Complete re-architecture of WPGraphQL for ACF v0.6.*.
- For beta release notes leading up to v2.0.0, see the [Github Releases](https://github.com/wp-graphql/wpgraphql-acf/releases).

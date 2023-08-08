=== WPGraphQL for Advanced Custom Fields ===
Contributors: jasonbahl, wpgraphql
Tags: GraphQL, ACF, Advanced Custom Fields, API, NextJS, Faust, Headless, Decoupled, React, Vue, Svelte, Vue, Apollo, JSON, REST
Requires at least: 6.0
Tested up to: 6.3
Requires PHP: 7.1
Stable Tag: 2.0.0
License: GPL-3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

=== Description ===

WPGraphQL for Advanced Custom Fields is a free, open-source WordPress plugin that exposes ACF Field
Groups and Fields to the WPGraphQL Schema, allowing developers to access data managed by ACF using
GraphQL Queries and Fragments.

= Create your ACF Field Groups =

Create your ACF Field Groups and Fields, the same way you normally would, using the ACF User
Interface, registering your fields with PHP or using ACF local-json. Each field group and the
fields within it can be configured to "Show in GraphQL".

= Query your fields with GraphQL =

Once your field groups and fields have been configured to "Show in GraphQL", they will be available
in the GraphQL Schema and ready for querying!

= Supported Field Types =

WPGraphQL for ACF provides support for most built-in field types of ACF (free & PRO) and also has
support for most field types from ACF Extended (free & PRO).

= Upgrading =

It is recommended that anytime you want to update WPGraphQL for ACF that you get familiar with what's
changed in the release.

WPGraphQL for ACF publishes [release notes on Github](https://github.com/wp-graphql/wpgraphql-acf/releases).

WPGraphQL for ACF will use Semantic Versioning (Semver) to communicate meaning of releases.

The summary of Semver is as follows:

- *MAJOR* version when you make incompatible API changes,
- *MINOR* version when you add functionality in a backwards compatible manner, and
- *PATCH* version when you make backwards compatible bug fixes.

You can read more about the details of Semver at semver.org

== Privacy Policy ==

WPGraphQL for Advanced Custom Fields uses [Appsero](https://appsero.com) SDK to collect some telemetry data upon user's confirmation. This helps us to troubleshoot problems faster & make product improvements.

Appsero SDK **does not gather any data by default.** The SDK only starts gathering basic telemetry data **when a user allows it via the admin notice**. We collect the data to ensure a great user experience for all our users.

Integrating Appsero SDK **DOES NOT IMMEDIATELY** start gathering data, **without confirmation from users in any case.**

Learn more about how [Appsero collects and uses this data](https://appsero.com/privacy-policy/).

== Upgrade Notice ==

= 2.0.0 =

This release is a complete re-architecture of WPGraphQL for ACF v0.6.* and older.

There are many breaking changes to the GraphQL Schema and underlying PHP API changes.

It's recommended that you read the @todo (link to upgrade guide) before upgrading.

== Changelog ==

= 2.0.0 =

- Initial release on WordPress.org. Complete re-architecture of WPGraphQL for ACF v0.6.*.

# WPGraphQL for Advanced Custom Fields (BETA)

This plugin is a replacement for WPGraphQL for ACF, found here: https://github.com/wp-graphql/wp-graphql-acf

## Beta Notice

This is beta software. There will be breaking changes as we iterate toward a stable release.

## Upgrade Notice

If you were using the previous version of WPGraphQL for ACF (https://github.com/wp-graphql/wp-graphql-acf) this plugin is intended to replace it.

They are not intended to be active at the same time.

We will work on a more in-depth upgrade guide, but for now we recommend de-activating the previous plugin before activating this one, then testing your client application queries against the Schema and adjusting your queries accordingly.

There are breaking changes to both code under the hood and the GraphQL Schema that is generated, so be sure to test in environments that are ready to endure these changes.

Stay tuned for a more in-depth upgrade guide.

## Plugin Overview

### Installation and Activation

@todo

### How WPGraphQL for ACF maps Field Groups to the Schema

_@todo: expand this_

Each ACF Field Group is mapped to the GraphQL Schema with a GraphQL Interface Type and a GraphQL Object Type to represent the field group.

The Interface Type is added to the Schema with all the fields of the Field Group that are set to "Show in GraphQL", and that Interface Type is implemented by the Object Type.

When the Field Group is assigned to a location (i.e. "Post Type is equal to Post"), WPGraphQL will attempt to determine how to properly show the Field Group in the Schema based on the Location Rules. (There's admin UI settings to override the GraphQL Types a field group should show on, and this can be done in PHP/JSON registered field groups as well)

Each GraphQL Type that is connected to an ACF Field Group gets an interface applied to it with the name of "WithACf${GraphQLTypeName}"

To show how this works, let's start with a basic example:

Let's say we have an ACF Field Group named "My Field Group" that has a "text" field named "text", and has location rules set to show on the "Post" post type.

With WPGraphQL for ACF active, we can set the field group to "Show in GraphQL" in the Field Group's settings and set the "GraphQL Type Name" to "MyFieldGroup".

This will result in the following changes to the GraphQL Schema:

- A GraphQL Interface Type named "MyFieldGroup_Fields" will be registered to the Schema
  - This Interface Type will have a "text" field of the "String" type, representing the "text" field on the ACF Field Group
- A GraphQL Object Type named "MyFieldGroup" will be registered to the Schema
  - This Object Type will implement the "MyFieldGroup_Fields" Interface
  - This Object Type will implement the "AcfFieldGroup" interface (all ACF Field Groups implement this Interface)
- An Interface `WithAcfMyFieldGroup` will be added to the Schema
  - This interface will have a field `myFieldGroup` which resolves to the Type `MyFieldGroup`
- The "Post" Type will now implement the `WithAcfMyFieldGroup` interface
  - This is determined by the Location Rules on the field group. Since this field group was assigned to the "Post" post type, the "Post" Type implements the interface.

Now, we can write a fragment like so:

```graphql
fragment MyFieldGroup on WithAcfMyFieldGroup {
  myFieldGroup {
    text
  }
}
```

And we can use this fragment in various queries, for example:

```graphql
{
  nodeByUri(uri:"/my-test-page") {
    __typename
    id
    uri
    ...MyFieldGroup
  }
}
```

### ACF Post Type and Taxonomy Support

Advanced Custom Fields v6.1 added support for registering Post Types and Taxonomies from the WordPress dashboard.

WPGraphQL for ACF allow you to configure the Custom Post Types and Taxonomies to Show in GraphQL and allows you to customize the GraphQL Single Name and GraphQL Plural Name.

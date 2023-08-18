<?php
namespace WPGraphQL\Acf;

use Exception;
use WPGraphQL\Model\Comment;
use WPGraphQL\Model\Menu;
use WPGraphQL\Model\MenuItem;
use WPGraphQL\Model\Post;
use WPGraphQL\Model\Term;
use WPGraphQL\Model\User;
use WPGraphQL\Acf\Model\AcfOptionsPage;

class Utils {

	/**
	 * @var null|\WPGraphQL\Acf\FieldTypeRegistry
	 */
	protected static $type_registry;

	/**
	 * @param mixed $node
	 * @param array $acf_field_config The config of the ACF Field
	 *
	 * @return int|mixed|string
	 */
	public static function get_node_acf_id( $node, array $acf_field_config = [] ) {

		/**
		 * If a value is returned from this filter,
		 */
		$pre_get_node_acf_id = apply_filters( 'wpgraphql/acf/pre_get_node_acf_id', null, $node );

		if ( null !== $pre_get_node_acf_id ) {
			return $pre_get_node_acf_id;
		}

		if ( is_array( $node ) && isset( $node['node']->ID ) ) {
			return absint( $node['node']->ID );
		}

		switch ( true ) {
			case $node instanceof Term:
				$id = 'term_' . $node->term_id;
				break;
			case $node instanceof Post:
				$id = absint( $node->databaseId );
				break;
			case $node instanceof MenuItem:
				$id = absint( $node->menuItemId );
				break;
			case $node instanceof Menu:
				$id = 'term_' . $node->menuId;
				break;
			case $node instanceof User:
				$id = 'user_' . absint( $node->userId );
				break;
			case $node instanceof Comment:
				// @phpstan-ignore-next-line
				$id = 'comment_' . absint( $node->databaseId );
				break;
			case is_array( $node ) && isset( $node['post_id'] ) && 'options' === $node['post_id']:
				$id = $node['post_id'];
				break;
			case $node instanceof AcfOptionsPage:
				$id = $node->acfId;
				break;
			default:
				$id = 0;
				break;
		}

		return $id;
	}

	/**
	 * Clear the Type Registry for tests
	 *
	 * @return void
	 */
	public static function clear_field_type_registry(): void {
		self::$type_registry = null;
	}

	/**
	 * Return the Field Type Registry instance
	 *
	 * @return \WPGraphQL\Acf\FieldTypeRegistry
	 */
	public static function get_type_registry(): FieldTypeRegistry {

		if ( self::$type_registry instanceof FieldTypeRegistry ) {
			return self::$type_registry;
		}

		self::$type_registry = new FieldTypeRegistry();
		return self::$type_registry;

	}

	/**
	 * Given the name of an ACF Field Type (text, textarea, etc) return the AcfGraphQLFieldType definition
	 *
	 * @param string $acf_field_type The name of the ACF Field Type (text, textarea, etc)
	 *
	 * @return \WPGraphQL\Acf\AcfGraphQLFieldType|null
	 */
	public static function get_graphql_field_type( string $acf_field_type ): ?AcfGraphQLFieldType {

		return self::get_type_registry()->get_field_type( $acf_field_type );

	}

	/**
	 * Get a list of supported fields that WPGraphQL for ACF supports.
	 *
	 * This is helpful for determining whether UI should be output for the field, and whether
	 * the field should be added to the Schema.
	 *
	 * Some fields, such as "Accordion" are not supported currently.
	 *
	 * @return array
	 */
	public static function get_supported_acf_fields_types(): array {

		$registry               = self::get_type_registry();
		$registered_fields      = $registry->get_registered_field_types();
		$registered_field_names = array_keys( $registered_fields );

		/**
		 * Filter the supported fields
		 *
		 * @param array $supported_fields
		 */
		return apply_filters( 'wpgraphql/acf/supported_field_types', $registered_field_names );
	}

	/**
	 * Returns all available GraphQL Types
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function get_all_graphql_types(): array {
		$graphql_types = [];

		// Use GraphQL to get the Interface and the Types that implement them
		$query = '
		query GetPossibleTypes($name:String!){
			__type(name:$name){
				name
				description
				possibleTypes {
					name
					description
				}
			}
		}
		';

		$interfaces = [
			'ContentNode'     => [
				'label'        => __( 'Post Type', 'wp-graphql-acf' ),
				'plural_label' => __( 'All Post Types', 'wp-graphql-acf' ),
			],
			'TermNode'        => [
				'label'        => __( 'Taxonomy', 'wp-graphql-acf' ),
				'plural_label' => __( 'All Taxonomies', 'wp-graphql-acf' ),
			],
			'ContentTemplate' => [
				'label'        => __( 'Page Template', 'wp-graphql-acf' ),
				'plural_label' => __( 'All Templates Assignable to Content', 'wp-graphql-acf' ),
			],
			'AcfOptionsPage'  => [
				'label'        => __( 'ACF Options Page', 'wp-graphql-acf' ),
				'plural_label' => __( 'All Options Pages registered by ACF', 'wp-graphql-acf' ),
			],
			'AcfBlock'        => [
				'label'        => __( 'ACF Block', 'wp-graphql-acf' ),
				'plural_label' => __( 'All Gutenberg Blocks registered by ACF Blocks', 'wp-graphql-acf' ),
			],
		];

		foreach ( $interfaces as $interface_name => $config ) {

			$interface_query = graphql([
				'query'     => $query,
				'variables' => [
					'name' => $interface_name,
				],
			]);

			$possible_types = is_array( $interface_query ) && ! empty( $interface_query['data']['__type']['possibleTypes'] ) ? $interface_query['data']['__type']['possibleTypes'] : [];

			if ( empty( $possible_types ) ) {
				continue;
			}

			asort( $possible_types );

			// Intentionally not translating "ContentNode Interface" as this is part of the GraphQL Schema and should not be translated.
			$graphql_types[ $interface_name ] = '<span data-interface="' . $interface_name . '">' . $interface_name . ' Interface (' . $config['plural_label'] . ')</span>';
			$label                            = '<span data-implements="' . $interface_name . '"> (' . $config['label'] . ')</span>';
			foreach ( $possible_types as $type ) {
				$type_label = $type['name'] . '&nbsp;' . $label;
				$type_key   = $type['name'];

				$graphql_types[ $type_key ] = $type_label;
			}
		}

		/**
		 * Add comment to GraphQL types
		 */
		$graphql_types['Comment'] = __( 'Comment', 'wp-graphql-acf' );

		/**
		 * Add menu to GraphQL types
		 */
		$graphql_types['Menu'] = __( 'Menu', 'wp-graphql-acf' );

		/**
		 * Add menu items to GraphQL types
		 */
		$graphql_types['MenuItem'] = __( 'Menu Item', 'wp-graphql-acf' );

		/**
		 * Add users to GraphQL types
		 */
		$graphql_types['User'] = __( 'User', 'wp-graphql-acf' );

		return $graphql_types;
	}

	/**
	 * Whether the ACF Field Group should show in the GraphQL Schema
	 *
	 * @param array $acf_field
	 *
	 * @return bool
	 */
	public static function should_field_show_in_graphql( array $acf_field ): bool {
		return self::should_field_group_show_in_graphql( $acf_field );
	}

	/**
	 * Whether the ACF Field Group should show in the GraphQL Schema
	 *
	 * @param array $acf_field_group
	 *
	 * @return bool
	 */
	public static function should_field_group_show_in_graphql( array $acf_field_group ): bool {

		$should = true;


		$show_in_rest = $acf_field_group['show_in_rest'] ?? false;


		// if the field group was configured with no "show_in_graphql" value, default to the "show_in_rest" value
		// to determine if the group should be available in an API
		if (
			( isset( $acf_field_group['post_id'] ) && 'options' !== $acf_field_group['post_id'] ) &&
			! isset( $acf_field_group['show_in_graphql'] ) ) {
			$acf_field_group['show_in_graphql'] = $show_in_rest ?? false;
		}

		if ( isset( $acf_field_group['show_in_graphql'] ) && false === $acf_field_group['show_in_graphql'] ) {
			$should = false;
		}

		return (bool) apply_filters( 'wpgraphql/acf/should_field_group_show_in_graphql', $should, $acf_field_group );

	}

	/**
	 * Given a field group config, return the name of the field group to be used in the GraphQL
	 * Schema
	 *
	 * @param array $field_group The field group config array
	 *
	 * @return string
	 */
	public static function get_field_group_name( array $field_group ): string {

		$field_group_name = '';

		if ( ! empty( $field_group['graphql_field_name'] ) ) {
			$field_group_name = $field_group['graphql_field_name'];
			$field_group_name = preg_replace( '/[^0-9a-zA-Z_\s]/i', '', $field_group_name );
		} else {
			if ( ! empty( $field_group['name'] ) ) {
				$field_group_name = $field_group['name'];
			} elseif ( ! empty( $field_group['title'] ) ) {
				$field_group_name = $field_group['title'];
			} elseif ( ! empty( $field_group['label'] ) ) {
				$field_group_name = $field_group['label'];
			} elseif ( ! empty( $field_group['page_title'] ) ) {
				$field_group_name = $field_group['page_title'];
			}
			$field_group_name = preg_replace( '/[^0-9a-zA-Z_\s]/i', ' ', $field_group_name );
			// if the graphql_field_name isn't explicitly defined, we'll format it without underscores
			$field_group_name = \WPGraphQL\Utils\Utils::format_field_name( $field_group_name, false );
		}

		if ( empty( $field_group_name ) ) {
			return $field_group_name;
		}

		$starts_with_string = is_numeric( substr( $field_group_name, 0, 1 ) );

		if ( $starts_with_string ) {
			graphql_debug( __( 'The ACF Field or Field Group could not be added to the schema. GraphQL Field and Type names cannot start with a number', 'wp-graphql-acf' ), [
				'invalid' => $field_group,
			] );
			return '';
		}

		return $field_group_name;

	}

	/**
	 * Returns string of the items in the array list. Limit allows string to be limited length.
	 *
	 * @param array $list
	 * @param int $limit
	 *
	 * @return string
	 */
	public static function array_list_by_limit( array $list, int $limit = 5 ): string {
		$flat_list = '';
		$total     = count( $list );

		// Labels.
		$labels     = $list;
		$labels     = array_slice( $labels, 0, $limit );
		$flat_list .= implode( ', ', $labels );

		// More.
		if ( $total > $limit ) {
			$flat_list .= ', ...';
		}
		return $flat_list;
	}

}

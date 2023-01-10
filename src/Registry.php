<?php

namespace WPGraphQLAcf;

use Exception;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Utils\Utils;

class Registry {

	/**
	 * @var array
	 */
	protected static $registered_fields = [];

	/**
	 * @var array
	 */
	protected static $registered_field_groups;

	/**
	 * Whether the ACF Field Group should show in the GraphQL Schema
	 *
	 * @param array $acf_field_group
	 *
	 * @return bool
	 */
	public static function should_field_group_show_in_graphql( array $acf_field_group ): bool {

		$should = true;

		if ( ! isset( $acf_field_group['show_in_graphql'] ) && false === $acf_field_group['show_in_graphql'] ) {
			$should = false;
		}

		return (bool) apply_filters( 'graphql_acf_should_field_group_show_in_graphql', $should, $acf_field_group );
	}

	/**
	 * Get the ACF Field Groups that should be registered to the Schema
	 *
	 * @return array
	 */
	public static function get_acf_field_groups(): array {

		$all_acf_field_groups = acf_get_field_groups();
		$graphql_field_groups = [];
		foreach ( $all_acf_field_groups as $acf_field_group ) {

			// if a field group is explicitly set to NOT show in GraphQL, we'll leave
			// the field group out of the Schema.
			if ( ! self::should_field_group_show_in_graphql( $acf_field_group ) || ! isset( $acf_field_group['key'] ) ) {
				continue;
			}

			$graphql_field_groups[ $acf_field_group['key'] ] = $acf_field_group;
		}

		// @phpstan-ignore-next-line
		return $graphql_field_groups;

	}

	/**
	 * Register Initial Types to the Schema
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function register_initial_graphql_types(): void {

		register_graphql_interface_type( 'AcfFieldGroup', [
			'fields' => [
				'fieldGroupName' => [
					'type' => 'String',
					'description' => __( 'The name of the field group', 'wp-graphql-acf' ),
					'deprecationReason' => __( 'Use __typename instead', 'wp-graphql-acf' ),
				]
			],
		] );

		register_graphql_interface_type( 'AcfFieldGroupFields', [
			'fields' => [
				'fieldGroupName' => [
					'type' => 'String',
					'description' => __( 'The name of the field group', 'wp-graphql-acf' ),
					'deprecationReason' => __( 'Use __typename instead', 'wp-graphql-acf' ),
				]
			],
		] );
	}

	/**
	 * Given an ACF Field Group config, return the interface names that it should apply
	 *
	 * @param array $acf_field_group The ACF Field Group config
	 *
	 * @return array
	 * @throws Error
	 */
	public static function get_field_group_interfaces( array $acf_field_group ): array {

		$fields_interface = self::get_field_group_graphql_type_name( $acf_field_group ) . '_Fields';
		$interfaces       = isset( $acf_field_group['interfaces'] ) && is_array( $acf_field_group['interfaces'] ) ? $acf_field_group['interfaces'] : [];
		$interfaces[]     = 'AcfFieldGroup';
		$interfaces[]     = $fields_interface;

		// @phpstan-ignore-next-line
		$fields = $acf_field_group['sub_fields'] ?? acf_get_fields( $acf_field_group );

		foreach ( $fields as $field ) {
			if ( ! empty( $field['_clone'] ) && ! empty( $field['__key'] ) ) {
				// @phpstan-ignore-next-line
				$cloned_from = acf_get_field( $field['__key'] );

				if ( ! empty( $cloned_from ) ) {

					// @phpstan-ignore-next-line
					$interfaces[ $field['key'] ] = self::get_field_group_graphql_type_name( acf_get_field_group( $cloned_from['parent'] ) ) . '_Fields';
				}
			}
		}

		$interfaces = array_unique( array_values( $interfaces ) );

		return array_unique( $interfaces );

	}

	/**
	 * @param array $acf_field_group
	 *
	 * @return array
	 * @throws Error
	 */
	public static function get_fields_for_field_group( array $acf_field_group ): array {

		$graphql_fields['fieldGroupName'] = [
			'type'        => 'String',
			'description' => __( 'The name of the field group', 'wp-graphql-acf' ),
			'deprecationReason' => __( 'Use __typename instead', 'wp-graphql-acf' ),
		];

		// @phpstan-ignore-next-line
		$fields = $acf_field_group['sub_fields'] ?? acf_get_fields( $acf_field_group );

		foreach ( $fields as $acf_field ) {

			// if it's a cloned field, skip it, it will be applied by the interface
			if ( ! empty( $acf_field['_clone'] ) ) {
				continue;
			}

			// if the field is explicitly set to not show in graphql, leave it out of the schema
			if ( isset( $acf_field['show_in_graphql'] ) && false === $acf_field['show_in_graphql'] ) {
				continue;
			}

			// if the field is not a supported type, don't add it to the schema
			if ( ! self::is_supported_field_type( $acf_field ) ) {
				continue;
			}

			$graphql_field_name = self::get_graphql_field_name( $acf_field );
			$graphql_fields[ $graphql_field_name ] = self::map_acf_field_to_graphql( $acf_field, $acf_field_group );

		}

		return $graphql_fields;

	}

	/**
	 * Determine whether an ACF Field is supported by GraphQL
	 *
	 * @param array $acf_field The ACF Field Config
	 *
	 * @return bool
	 */
	public static function is_supported_field_type( array $acf_field ): bool {

		$supported_types = apply_filters( 'graphql_acf_supported_fields', [
			'text',
			'textarea',
			'number',
			'range',
			'email',
			'url',
			'password',
			'image',
			'file',
			'wysiwyg',
			'oembed',
			'gallery',
			'select',
			'checkbox',
			'radio',
			'button_group',
			'true_false',
			'link',
			'post_object',
			'page_link',
			'relationship',
			'taxonomy',
			'user',
			'google_map',
			'date_picker',
			'date_time_picker',
			'time_picker',
			'color_picker',
			'group',
			'repeater',
			'flexible_content',
		] );

		return isset( $acf_field['type'] ) && in_array( $acf_field['type'], $supported_types, true  );

	}

	/**
	 * @param array $acf_field
	 * @param array $acf_field_group
	 *
	 * @return array
	 * @throws Error
	 * @throws Exception
	 */
	public static function map_acf_field_to_graphql( array $acf_field, array $acf_field_group ): array {

		$field_config = [
			'type'            => 'String',
			'name'            => self::get_graphql_field_name( $acf_field ),
			'description'     => sprintf( __( 'Field added by WPGraphQL for ACF Redux %s', 'wp-graphql-acf' ), Registry::get_field_group_graphql_type_name( $acf_field_group ) ),
			'acf_field'       => $acf_field,
			'acf_field_group' => $acf_field_group,
			'resolve' => function( $root, $args, AppContext $context, ResolveInfo $info ) {
				return self::resolve_field( $root, $args, $context, $info );
			}
		];

		if ( ! empty( $acf_field['type'] ) ) {

			switch ( $acf_field['type'] ) {
				case 'group':

					$parent_type     = self::get_field_group_graphql_type_name( $acf_field_group );
					$field_name      = self::get_graphql_field_name( $acf_field );
					$sub_field_group = $acf_field;
					$type_name       = Utils::format_field_name($parent_type . ' ' . $field_name );

					$sub_field_group['graphql_field_name'] = $type_name;

					self::register_acf_field_groups_to_graphql( [
						$sub_field_group,
					] );

					$field_config['type'] = $type_name;
					break;


				case 'flexible_content':

					$parent_type     = self::get_field_group_graphql_type_name( $acf_field_group );
					$field_name      = self::get_graphql_field_name( $acf_field );
					$layout_interface_prefix = Utils::format_type_name( $parent_type . ' ' . $field_name );
					$layout_interface_name =  $layout_interface_prefix . '_Layout';

					if ( empty( self::$registered_field_groups[ $layout_interface_name ] ) ) {

						register_graphql_interface_type( $layout_interface_name, [
							'eagerlyLoadType' => true,
							'description'     => sprintf( __( 'Layout of the "%1$s" Field of the "%2$s" Field Group Field', 'wp-graphql-acf' ), $field_name, $parent_type ),
							'fields'          => [
								'fieldGroupName' => [
									'type'              => 'String',
									'description'       => __( 'The name of the ACF Flex Field Layout', 'wp-graphql-acf' ),
									'deprecationReason' => __( 'Use __typename instead', 'wp-graphql-acf' ),
								],
							],
							'resolveType'     => function( $object ) use ( $layout_interface_prefix ) {

								$layout = $object['acf_fc_layout'] ?? null;

								return Utils::format_type_name( $layout_interface_prefix . ' ' . $layout );

							}
						] );

						self::$registered_field_groups[ $layout_interface_name ] = $layout_interface_name;

					}

					$layouts = [];
					foreach ( $acf_field['layouts'] as $layout ) {
						$layout_name = Utils::format_type_name( $layout_interface_prefix . ' ' . self::get_graphql_field_name( $layout ) );

						$interfaces                   = self::get_field_group_interfaces( $layout );
						$interfaces[]                 = $layout_interface_name;

						$layout['eagerlyLoadType']    = true;
						$layout['graphql_field_name'] = $layout_name;
						$layout['fields']             = self::get_fields_for_field_group( $layout );
						$layout['interfaces']         = $interfaces;

						$layouts[ $layout_name ]      = $layout;

					}

					if ( ! empty( $layouts ) ) {
						self::register_acf_field_groups_to_graphql( $layouts );
					}


					$field_config['type'] = [ 'list_of' => $layout_interface_name ];
					break;

				case 'repeater':
					$parent_type     = self::get_field_group_graphql_type_name( $acf_field_group );
					$field_name      = self::get_graphql_field_name( $acf_field );
					$sub_field_group = $acf_field;
					$type_name       = Utils::format_type_name($parent_type . ' ' . $field_name );

					$sub_field_group['graphql_field_name'] = $type_name;

					self::register_acf_field_groups_to_graphql( [
						$sub_field_group,
					] );

					$field_config['type'] = [ 'list_of' => $type_name ];
					break;
				default:
					break;
			}
		}

		return $field_config;
	}

	/**
	 * Determine if the field should ask ACF to format the response when retrieving
	 * the field using get_field()
	 *
	 * @param string $field_type The ACF Field Type of field being resolved
	 *
	 * @return bool
	 */
	public static function should_format_field_value( string $field_type ): bool {

		// @todo: filter this? And it should be done at the registry level once, not the per-field level
		$types_to_format = [
			'select',
			'wysiwyg'
		];

		return in_array( $field_type, $types_to_format, true );

	}

	/**
	 * @param             $root
	 * @param array       $args
	 * @param AppContext  $context
	 * @param ResolveInfo $info
	 *
	 * @return mixed
	 */
	public static function resolve_field( $root, array $args, AppContext $context, ResolveInfo $info ) {

		// @todo: Handle options pages??
		$field_config = $info->fieldDefinition->config['acf_field'] ?? [];
		$node = $root['node'] ?: null;
		$node_id = \WPGraphQLAcf\Utils::get_node_acf_id( $node ) ?: null;

		if ( ! empty( $field_config['_clone'] ) ) {
			$field_key = $field_config['__key'];
		} else {
			$field_key = $field_config['key'];
		}

		if ( empty( $field_key ) ) {
			return null;
		}

		if ( 'colour' === $field_config['name'] )  {
			wp_send_json( [
				'$field_config' => $field_config,
				'$root' => $root,
				'$node' => $node,
				'$node_id' => $node_id,
			]);
		}

		// If the root being passed down already has a value
		// for the field key, let's use it to resolve
		if ( ! empty( $root[ $field_key ] ) ) {
			return self::prepare_acf_field_value( $root[ $field_key ], $node, $node_id, $field_config );
		}

		// If there's no node_id at this point, we can return null
		if ( empty( $node_id ) ) {
			return null;
		}

		$should_format_value = self::should_format_field_value( $field_config['type'] );

		/**
		 * Filter the field value before resolving.
		 *
		 * @param mixed            $value     The value of the ACF Field stored on the node
		 * @param mixed            $node      The object the field is connected to
		 * @param mixed|string|int $node_id   The ACF ID of the node to resolve the field with
		 * @param array            $acf_field The ACF Field config
		 * @param bool             $format    Whether to apply formatting to the field
		 */
		$value = apply_filters( 'graphql_acf_pre_resolve_acf_field', null, $root, $node_id, $field_config, $should_format_value );

		// If the filter has returned a value, we can return the value that was returned.
		if ( null !== $value ) {
			return $value;
		}

		$value = get_field( $field_key, $node_id, $should_format_value );
		$value = self::prepare_acf_field_value( $value, $root, $node_id, $field_config );

		if ( empty( $value ) ) {
			$value = null;
		}

		/**
		 * Filter the value before returning
		 *
		 * @param mixed $value
		 * @param array $field_config The ACF Field Config for the field being resolved
		 * @param mixed $root The Root node or object of the field being resolved
		 * @param mixed $node_id The ID of the node being resolved
		 */
		return apply_filters( 'graphql_acf_field_value', $value, $field_config, $root, $node_id );

	}

	/**
	 * Given a value of an ACF Field, this prepares it for response by applying formatting, etc based on
	 * the field type.
	 *
	 * @param mixed            $value   The value of the ACF field to return
	 * @param mixed            $root    The root node/object the field belongs to
	 * @param mixed|string|int $node_id The ID of the node the field belongs to
	 * @param array            $acf_field_config The ACF Field Config for the field being resolved
	 *
	 * @return mixed
	 */
	public static function prepare_acf_field_value( $value, $root, $node_id, array $acf_field_config ) {

		if ( isset( $field_config['new_lines'] ) ) {
			if ( 'wpautop' === $field_config['new_lines'] ) {
				$value = wpautop( $value );
			}
			if ( 'br' === $field_config['new_lines'] ) {
				$value = nl2br( $value );
			}
		}

		// @todo: This was ported over, but I'm not 💯 sure what this is solving and
		// why it's only applied on options pages and not other pages 🤔
		if ( is_array( $root ) && ! ( ! empty( $root['type'] ) && 'options_page' === $root['type'] ) ) {

			if ( isset( $root[ $field_config['key'] ] ) ) {
				$value = $root[ $field_config['key'] ];
				if ( 'wysiwyg' === $field_config['type'] ) {
					$value = apply_filters( 'the_content', $value );
				}
			}
		}

		if ( ! empty( $field_config['type'] ) && in_array( $field_config['type'], [
			'date_picker',
			'time_picker',
			'date_time_picker'
		], true ) ) {

			if ( ! empty( $value ) && ! empty( $field_config['return_format'] ) ) {
				$value = date( $field_config['return_format'], strtotime( $value ) );
			}
		}

		if ( ! empty( $field_config['type'] ) && in_array( $field_config['type'], [ 'number', 'range' ], true ) ) {
			$value = (float) $value ?: null;
		}

		return $value;

	}

	/**
	 * Given a field group config, return the name of the field group to be used in the GraphQL Schema
	 *
	 * @param array $field_group The field group config array
	 *
	 * @return string
	 */
	public static function get_field_group_name( array $field_group ): string {

		$field_group_name = '';

		if ( ! empty( $field_group['graphql_field_name'] ) ) {
			$field_group_name = $field_group['graphql_field_name'];
		} else if ( ! empty( $field_group['name'] ) ) {
			$field_group_name = $field_group['name'];
		} else if ( ! empty( $field_group['title'] ) ) {
			$field_group_name = $field_group['title'];
		} else if ( ! empty( $field_group['label'] ) )  {
			$field_group_name = $field_group['label'];
		}

		return $field_group_name;
	}

	/**
	 * @param array $acf_field The ACF Field config
	 *
	 * @return string
	 * @throws Error
	 */
	public static function get_graphql_field_name( array $acf_field ): string {
		$name = self::get_field_group_name( $acf_field );

		$replaced = preg_replace( '/[\W_]+/u', ' ', $name );

		if ( empty( $replaced ) ) {

			throw new Error( sprintf( __( 'The graphql field name %s is not a valid name and cannot be added to the GraphQL Schema', 'wp-graphql-acf' ), $name ) );
		}

		return Utils::format_field_name( $replaced );
	}

	/**
	 * @param array $field_group
	 *
	 * @return string
	 * @throws Error
	 */
	public static function get_field_group_graphql_type_name( array $field_group ): string {
		$name = self::get_field_group_name( $field_group );
		$replaced = preg_replace( '/[\W_]+/u', ' ', $name );

		if ( empty( $replaced ) ) {
			throw new Error( sprintf( __( 'The graphql field name %s is not a valid name and cannot be added to the GraphQL Schema', 'wp-graphql-acf' ), $name ) );
		}

		return Utils::format_type_name( $replaced );
	}

	/**
	 * Gets the location rules
	 *
	 * @param array $acf_field_groups
	 *
	 * @return array
	 */
	public static function get_location_rules( array $acf_field_groups = [] ): array {

		$field_groups = $acf_field_groups;
		$rules        = [];

		// Each field group that doesn't have GraphQL Types explicitly set should get the location
		// rules interpreted.
		foreach ( $field_groups as $field_group ) {
			if ( ! isset( $field_group['graphql_types'] ) || ! is_array( $field_group['graphql_types'] ) ) {
				$rules[] = $field_group;
			}
		}

		if ( empty( $rules ) ) {
			return [];
		}

		// If there are field groups with no graphql_types field set, inherit the rules from
		// ACF Location Rules
		$rules = new \WPGraphQLAcf\LocationRules();
		$rules->determine_location_rules();

		return $rules->get_rules();
	}

	/**
	 * Get the GraphQL Types a Field Group should be registered to show on
	 *
	 * @param array $field_group The ACF Field Group config to determine the Types for
	 * @param array $acf_field_groups
	 *
	 * @return array
	 */
	public static function get_graphql_locations_for_field_group( array $field_group, array $acf_field_groups ): array {

		$graphql_types = $field_group['graphql_types'] ?? [];

		$field_group_name = $field_group['graphql_field_name'] ?? $field_group['title'];
		$field_group_name = Utils::format_field_name( $field_group_name );

		$manually_set_graphql_types = isset( $field_group['map_graphql_types_from_location_rules'] ) && (bool) $field_group['map_graphql_types_from_location_rules'];

		if ( false === $manually_set_graphql_types || empty( $graphql_types ) ) {
			if ( empty( $field_group['graphql_types'] ) ) {
				$location_rules = self::get_location_rules( $acf_field_groups );
				if ( isset( $location_rules[ $field_group_name ] ) ) {
					$graphql_types = $location_rules[ $field_group_name ];
				}
			}
		}

		return ! empty( $graphql_types ) && is_array( $graphql_types ) ? array_unique( array_filter( $graphql_types ) ) : [];

	}

	/**
	 * Given an array of Acf Field Groups, add them to the Schema
	 *
	 * @param array $acf_field_groups ACF Field Groups to register to the WPGraphQL Schema
	 * @return void
	 * @throws Exception
	 */
	public static function register_acf_field_groups_to_graphql( array $acf_field_groups = [] ): void {

		if ( empty( $acf_field_groups ) ) {
			return;
		}

		// Iterate over the field groups and add them to the Schema
		foreach ( $acf_field_groups as $acf_field_group ) {

			$type_name  = self::get_field_group_graphql_type_name( $acf_field_group );
			$locations  = self::get_graphql_locations_for_field_group( $acf_field_group, $acf_field_groups );
			$fields     = self::get_fields_for_field_group( $acf_field_group );
			$interfaces = self::get_field_group_interfaces( $acf_field_group );

			// If there's no fields or type name, we can't register the type to the Schema
			if ( empty( $fields ) || empty( $type_name ) ) {
				continue;
			}

			// If there are locations assigned to the field group,
			// register the Interface that signifies the Type supports the ACF Field Group
			// then register the interface to the type
			if ( ! empty( $locations ) ) {
				$with_field_group_interface_name = 'WithAcf' . $type_name;

				$field_name = Utils::format_field_name( $type_name );

				if ( empty( self::$registered_field_groups[ $with_field_group_interface_name ] ) ) {

					register_graphql_interface_type( $with_field_group_interface_name, [
						'eagerlyLoadType' => true,
						'description'     => sprintf( __( 'Provides access to fields of the "%1$s" ACF Field Group via the "%2$s" field', 'wp-graphql-acf' ), $type_name, $field_name ),
						'fields'          => [
							$field_name => [
								'type'        => $type_name,
								'description' => sprintf( __( 'Fields of the %s ACF Field Group', 'wp-graphql-acf' ), $type_name ),
								'resolve'     => function( $node ) use ( $acf_field_group ) {

									// Pass the $root node and the $acf_field_group down
									// to the resolving field
									return [
										'node'            => $node,
										'acf_field_group' => $acf_field_group,
									];
								}
							],
						],
					] );

					self::$registered_field_groups[ $with_field_group_interface_name ] = $with_field_group_interface_name;

				}

				// If the field group has locations defined (Types to be added to)
				// Add the
				register_graphql_interfaces_to_types( [ $with_field_group_interface_name ], $locations );
			}

			if ( empty( self::$registered_field_groups[ $type_name . '_Fields' ] ) ) {

				// Add an Interface to the Schema representing the Fields of the ACF Field Group
				register_graphql_interface_type( $type_name . '_Fields', [
					'kind'            => 'interface',
					'eagerlyLoadType' => true,
					'name'            => $type_name . '_Fields',
					'description'     => sprintf( __( 'Interface representing fields of the ACF "%s" Field Group', 'wp-graphql-acf' ), $type_name ),
					'interfaces'      => [ 'AcfFieldGroupFields' ],
					'fields'          => $fields,
					'locations'       => $locations,
					'acf_field_group' => $acf_field_group,
				] );

				self::$registered_field_groups[ $type_name . '_Fields' ] = $acf_field_group;

			}

			if ( empty( self::$registered_field_groups[ $type_name ] ) ) {

				// Add an object type to the Schema representing the Field Group, implementing interfaces
				// of any cloned field groups
				register_graphql_object_type( $type_name, [
					'kind'            => 'object',
					'eagerlyLoadType' => empty( $locations ),
					'name'            => $type_name,
					'description'     => sprintf( __( 'Added by WPGraphQL for ACF Redux', 'wp-graphql-acf' ), $type_name ),
					'interfaces'      => $interfaces,
					'fields'          => $fields,
					'locations'       => $locations,
					'acf_field_group' => $acf_field_group,
				] );

				self::$registered_field_groups[ $type_name ] = $acf_field_group;

			}

		}

	}

}

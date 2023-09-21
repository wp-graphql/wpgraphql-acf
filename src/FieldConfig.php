<?php

namespace WPGraphQL\Acf;

use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection\PostObjectConnectionResolver;

class FieldConfig {

	/**
	 * @var array
	 */
	protected $acf_field;

	/**
	 * @var array
	 */
	protected $acf_field_group;

	/**
	 * @var string|null
	 */
	protected $graphql_field_group_type_name;

	/**
	 * @var string
	 */
	protected $graphql_field_name;

	/**
	 * @var \WPGraphQL\Acf\AcfGraphQLFieldType|null
	 */
	protected $graphql_field_type;

	/**
	 * @var \WPGraphQL\Acf\Registry
	 */
	protected $registry;

	/**
	 * @var string
	 */
	protected $graphql_parent_type_name;

	/**
	 * @throws \GraphQL\Error\Error
	 */
	public function __construct( array $acf_field, array $acf_field_group, Registry $registry ) {
		$this->acf_field                     = $acf_field;
		$this->acf_field_group               = $acf_field_group;
		$this->registry                      = $registry;
		$this->graphql_field_group_type_name = $this->registry->get_field_group_graphql_type_name( $this->acf_field_group );
		$this->graphql_field_name            = $this->registry->get_graphql_field_name( $this->acf_field );
		$this->graphql_field_type            = Utils::get_graphql_field_type( $this->acf_field['type'] );
	}

	/**
	 * @return \WPGraphQL\Acf\Registry
	 */
	public function get_registry(): Registry {
		return $this->registry;
	}

	/**
	 * @return string|null
	 */
	public function get_graphql_field_group_type_name(): ?string {
		return $this->graphql_field_group_type_name;
	}

	/**
	 * @return \WPGraphQL\Acf\AcfGraphQLFieldType|null
	 */
	public function get_graphql_field_type(): ?AcfGraphQLFieldType {
		return $this->graphql_field_type;
	}

	/**
	 * @param array       $acf_field
	 * @param string|null $prepend
	 *
	 * @return string
	 * @throws \GraphQL\Error\Error
	 */
	public function get_parent_graphql_type_name( array $acf_field, ?string $prepend = '' ): string {
		$type_name = '';

		if ( ! empty( $acf_field['parent'] ) ) {
			$parent_field = acf_get_field( $acf_field['parent'] );
			$parent_group = acf_get_field_group( $acf_field['parent'] );
			if ( ! empty( $parent_field ) ) {
				$type_name = $this->registry->get_field_group_graphql_type_name( $parent_field );
				$type_name = $this->get_parent_graphql_type_name( $parent_field, $type_name );
			} elseif ( ! empty( $parent_group ) ) {
				$type_name = $this->registry->get_field_group_graphql_type_name( $parent_group );
				$type_name = $this->get_parent_graphql_type_name( $parent_group, $type_name );
			} else {
				$type_name = $this->get_parent_graphql_type_name( $acf_field, '' );
			}
		}

		return $type_name . $prepend;
	}

	/**
	 * @return string
	 */
	public function get_graphql_field_name(): string {
		return $this->graphql_field_name;
	}

	/**
	 * Determine whether an ACF Field is supported by GraphQL
	 *
	 * @return bool
	 */
	protected function is_supported_field_type(): bool {
		$supported_types = Utils::get_supported_acf_fields_types();
		return ! empty( $this->acf_field['type'] ) && in_array( $this->acf_field['type'], $supported_types, true );
	}

	/**
	 * Get the description of the field for the GraphQL Schema
	 *
	 * @return string
	 * @throws \GraphQL\Error\Error
	 */
	public function get_field_description(): string {

		// Use the explicit graphql_description, if set
		if ( ! empty( $this->acf_field['graphql_description'] ) ) {
			$description = $this->acf_field['graphql_description'];

			// else use the instructions, if set
		} elseif ( ! empty( $this->acf_field['instructions'] ) ) {
			$description = $this->acf_field['instructions'];
		} else {
			// Fallback description
			// translators: %s is the name of the ACF Field Group
			$description = sprintf( __( 'Field added to the schema as part of the "%s" Field Group', 'wp-graphql-acf' ), $this->registry->get_field_group_graphql_type_name( $this->acf_field_group ) );
		}

		return $description;
	}

	/**
	 * @return array
	 */
	public function get_acf_field(): array {
		return $this->acf_field;
	}

	/**
	 * @return array
	 */
	public function get_acf_field_group(): array {
		return $this->acf_field_group;
	}

	/**
	 * @return array|null
	 * @throws \GraphQL\Error\Error
	 * @throws \Exception
	 */
	public function get_graphql_field_config():?array {

		// if the field is explicitly set to not show in graphql, leave it out of the schema
		// if the field is explicitly set to not show in graphql, leave it out of the schema
		if ( isset( $this->acf_field['show_in_graphql'] ) && false === $this->acf_field['show_in_graphql'] ) {
			return null;
		}

		// if the field is not a supported type, don't add it to the schema
		if ( ! $this->is_supported_field_type() ) {
			return null;
		}

		if ( empty( $this->graphql_field_group_type_name ) || empty( $this->graphql_field_name ) ) {
			return null;
		}

		$field_config = [
			'type'            => 'String',
			'name'            => $this->graphql_field_name,
			'description'     => $this->get_field_description(),
			'acf_field'       => $this->get_acf_field(),
			'acf_field_group' => $this->acf_field_group,
			'resolve'         => function ( $root, $args, AppContext $context, ResolveInfo $info ) {
				return $this->resolve_field( $root, $args, $context, $info );
			},
		];


		if ( ! empty( $this->acf_field['type'] ) ) {
			$graphql_field_type = $this->get_graphql_field_type();

			// If the field type overrode the resolver, use it
			if ( null !== $graphql_field_type && method_exists( $graphql_field_type, 'get_resolver' ) ) {
				$resolver                = function ( $root, $args, AppContext $context, ResolveInfo $info ) use ( $graphql_field_type ) {
					return $graphql_field_type->get_resolver( $root, $args, $context, $info, $graphql_field_type, $this );
				};
				$field_config['resolve'] = $resolver;
			}

			if ( $graphql_field_type instanceof AcfGraphQLFieldType ) {
				$field_type = $graphql_field_type->get_resolve_type( $this );
			}

			if ( empty( $field_type ) ) {
				$field_type = 'String';
			}

			// if the field type returns a connection,
			// bail and let the connection handle registration to the schema
			// and resolution
			if ( 'connection' === $field_type ) {
				return null;
			}

			switch ( $this->acf_field['type'] ) {
				case 'color_picker':
				case 'number':
				case 'range':
				case 'group':
				case 'wysiwyg':
				case 'google_map':
				case 'link':
				case 'oembed':
				case 'radio':
				case 'date_picker':
				case 'date_time_picker':
				case 'time_picker':
				case 'flexible_content':
				case 'button_group':
				case 'repeater':
					$field_config['type'] = $field_type;
					break;
				case 'true_false':
					$field_config['type']    = $field_type;
					$field_config['resolve'] = function ( $node, array $args, AppContext $context, ResolveInfo $info ) {
						$value = $this->resolve_field( $node, $args, $context, $info );
						return (bool) $value;
					};
					break;
				case 'checkbox':
				case 'select':
					$field_config['type']    = $field_type;
					$field_config['resolve'] = function ( $node, array $args, AppContext $context, ResolveInfo $info ) {
						$value = $this->resolve_field( $node, $args, $context, $info );

						if ( empty( $value ) && ! is_array( $value ) ) {
							return null;
						}

						return is_array( $value ) ? $value : [ $value ];
					};
					break;

				default:
					$field_config['type'] = $field_type;
					break;
			}
		}

		/**
		 * Filter the graphql_field_config for an ACF Field
		 *
		 * @param array|null                 $field_config   The field config array passed to the schema registration
		 * @param \WPGraphQL\Acf\FieldConfig $instance       Instance of the FieldConfig class
		 */
		return apply_filters( 'wpgraphql/acf/get_graphql_field_config', $field_config, $this );
	}

	/**
	 * Determine if the field should ask ACF to format the response when retrieving
	 * the field using get_field()
	 *
	 * @param string $field_type The ACF Field Type of field being resolved
	 *
	 * @return bool
	 */
	public function should_format_field_value( string $field_type ): bool {

		// @todo: filter this? And it should be done at the registry level once, not the per-field level
		$types_to_format = [
			'select',
			'wysiwyg',
		];

		return in_array( $field_type, $types_to_format, true );
	}

	/**
	 * @param mixed       $root
	 * @param array       $args
	 * @param \WPGraphQL\AppContext  $context
	 * @param \GraphQL\Type\Definition\ResolveInfo $info
	 *
	 * @return mixed
	 */
	public function resolve_field( $root, array $args, AppContext $context, ResolveInfo $info ) {
		$field_config = $info->fieldDefinition->config['acf_field'] ?? $this->acf_field;
		$node         = $root['node'] ?? null;
		$node_id      = $node ? Utils::get_node_acf_id( $node ) : null;

		$field_key = null;
		$is_cloned = false;

		if ( ! empty( $field_config['__key'] ) ) {
			$field_key = $field_config['__key'];
			$is_cloned = true;
		} elseif ( ! empty( $field_config['key'] ) ) {
			$field_key = $field_config['key'];
		}

		if ( empty( $field_key ) ) {
			return null;
		}

		if ( $is_cloned ) {
			if ( isset( $field_config['_name'] ) && ! empty( $node_id ) ) {
				$field_key = $field_config['_name'];
			} elseif ( ! empty( $field_config['__key'] ) ) {
				$field_key = $field_config['__key'];
			}
			$cloned_field_config = acf_get_field( $field_key );
			$field_config        = ! empty( $cloned_field_config ) ? $cloned_field_config : $field_config;
		}

		$should_format_value = false;

		if ( ! empty( $field_config['type'] ) && $this->should_format_field_value( $field_config['type'] ) ) {
			$should_format_value = true;
		}

		if ( empty( $field_key ) ) {
			return null;
		}

		// if the field_config is empty or not an array, set it as an empty array as a fallback
		$field_config = ! empty( $field_config ) && is_array( $field_config ) ? $field_config : [];

		// If the root being passed down already has a value
		// for the field key, let's use it to resolve
		if ( ! empty( $root[ $field_key ] ) ) {
			return $this->prepare_acf_field_value( $root[ $field_key ], $node, $node_id, $field_config );
		}

		/**
		 * Filter the field value before resolving.
		 *
		 * @param mixed            $value     The value of the ACF Field stored on the node
		 * @param mixed            $node      The object the field is connected to
		 * @param mixed|string|int $node_id   The ACF ID of the node to resolve the field with
		 * @param array            $acf_field The ACF Field config
		 * @param bool             $format    Whether to apply formatting to the field
		 * @param string           $field_key The key of the field being resolved
		 */
		$pre_value = apply_filters( 'wpgraphql/acf/pre_resolve_acf_field', null, $root, $node_id, $field_config, $should_format_value, $field_key );

		// If the filter has returned a value, we can return the value that was returned.
		if ( null !== $pre_value ) {
			return $pre_value;
		}

		// resolve block field
		if ( is_array( $node ) && isset( $node['blockName'] ) ) {

			if ( ! empty( $node['attrs']['data'] ) ) {
				$fields = acf_setup_meta( $node['attrs']['data'], 0, true );
				acf_prepare_block( $node['attrs'] );
				$value = $fields[ $field_config['name'] ] ?? null;
			} else if ( ! empty( $node['attrs'] ) ) {
				acf_prepare_block( $node['attrs'] );
				$value = get_field( $field_config['name'] );
				acf_reset_meta();
			}

			return $value ?? null;
		}

		// If there's no node_id at this point, we can return null
		if ( empty( $node_id ) ) {
			return null;
		}

		$value = get_field( $field_key, $node_id, $should_format_value );
		$value = $this->prepare_acf_field_value( $value, $root, $node_id, $field_config );

		if ( empty( $value ) ) {
			$value = null;
		}

		/**
		 * Filter the value before returning
		 *
		 * @param mixed $value
		 * @param array $field_config The ACF Field Config for the field being resolved
		 * @param mixed $root The Root node or obect of the field being resolved
		 * @param mixed $node_id The ID of the node being resolved
		 */
		return apply_filters( 'wpgraphql/acf/field_value', $value, $field_config, $root, $node_id );
	}

	/**
	 * Given a value of an ACF Field, this prepares it for response by applying formatting, etc based on
	 * the field type.
	 *
	 * @param mixed            $value   The value of the ACF field to return
	 * @param mixed            $root    The root node/object the field belongs to
	 * @param mixed|string|int $node_id The ID of the node the field belongs to
	 * @param ?array           $acf_field_config The ACF Field Config for the field being resolved
	 *
	 * @return mixed
	 */
	public function prepare_acf_field_value( $value, $root, $node_id, ?array $acf_field_config = [] ) {
		if ( empty( $acf_field_config ) || ! is_array( $acf_field_config ) ) {
			return $value;
		}

		// if the value is an array and the field return format is set to array,
		// map over the array to get the return values
		if ( isset( $acf_field_config['return_format'] ) && 'array' === $acf_field_config['return_format'] && is_array( $value ) ) {
			$value = array_map(
				static function ( $opt ) {
					if ( ! is_array( $opt ) ) {
						return $opt;
					}

					return $opt['value'] ?? null;
				},
				$value
			);
		}

		if ( isset( $acf_field_config['new_lines'] ) ) {
			if ( 'wpautop' === $acf_field_config['new_lines'] ) {
				$value = wpautop( $value );
			}
			if ( 'br' === $acf_field_config['new_lines'] ) {
				$value = nl2br( $value );
			}
		}

		// @todo: This was ported over, but I'm not 💯 sure what this is solving and
		// why it's only applied on options pages and not other pages 🤔
		if ( isset( $acf_field_config['key'], $root[ $acf_field_config['key'] ] ) && is_array( $root ) && ! ( ! empty( $root['type'] ) && 'options_page' === $root['type'] ) ) {
			$value = $root[ $acf_field_config['key'] ];
			if ( 'wysiwyg' === $acf_field_config['type'] ) {
				$value = apply_filters( 'the_content', $value );
			}
		}

		if ( ! empty( $acf_field_config['type'] ) && in_array(
			$acf_field_config['type'],
			[
				'date_picker',
				'time_picker',
				'date_time_picker',
			],
			true
		) ) {
			if ( ! empty( $value ) && ! empty( $acf_field_config['return_format'] ) ) {
				$value = gmdate( $acf_field_config['return_format'], strtotime( $value ) );
			}
		}

		if ( ! empty( $acf_field_config['type'] ) && in_array( $acf_field_config['type'], [ 'number', 'range' ], true ) ) {
			$value = (float) $value ?: null;
		}

		return $value;
	}

	/**
	 * @param string $to_type
	 *
	 * @return string
	 */
	public function get_connection_name( string $to_type ): string {
		return \WPGraphQL\Utils\Utils::format_type_name( 'Acf' . ucfirst( $to_type ) . 'Connection' );
	}

	/**
	 * @param array $config The Connection Config to use
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function register_graphql_connections( array $config ): void {
		$type_name = $this->get_graphql_field_group_type_name();
		$to_type   = $config['toType'] ?? null;

		// If there's no to_type or type_name, we can't proceed
		if ( empty( $to_type ) || empty( $type_name ) ) {
			return;
		}

		$connection_name = $this->get_connection_name( $to_type );

		$connection_config = array_merge(
			[
				'description'           => $this->get_field_description(),
				'acf_field'             => $this->get_acf_field(),
				'acf_field_group'       => $this->get_acf_field_group(),
				'fromType'              => $type_name,
				'toType'                => $to_type,
				'connectionTypeName'    => $connection_name,
				'fromFieldName'         => $this->get_graphql_field_name(),
				'allowFieldUnderscores' => true,
			],
			$config
		);

		if ( $this->registry->has_registered_field_group( $connection_name ) ) {
			return;
		}

		// Register the connection to the Field Group Type
		 register_graphql_connection( $connection_config );

		// Register the connection to the Field Group Fields Interface
		register_graphql_connection( array_merge( $connection_config, [ 'fromType' => $type_name . '_Fields' ] ) );

		$this->registry->register_field_group( $connection_name, $connection_config );

	}

}

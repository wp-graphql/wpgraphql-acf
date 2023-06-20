<?php

namespace WPGraphQL\Acf\ThirdParty\AcfExtended\FieldType;

use GraphQL\Deferred;
use WPGraphQL\Model\Taxonomy;

class AcfeTaxonomies {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type( 'acfe_taxonomies', [
			'graphql_type' => [ 'list_of' => 'Taxonomy' ],
			'resolve'      => function ( $root, $args, $context, $info, $field_type, $field_config ) {
				$value = $field_config->resolve_field( $root, $args, $context, $info );

				if ( empty( $value ) ) {
					return null;
				}

				if ( ! is_array( $value ) ) {
					$value = [ $value ];
				}

				return new Deferred( function () use ( $value, $context ) {
					return array_filter( array_map( static function ( $taxonomy_name ) use ( $context ) {
						return $context->get_loader( 'taxonomy' )->load( $taxonomy_name );
					}, $value ) );
				});

			},
		] );
	}

}

<?php

namespace WPGraphQLAcf\ThirdParty\AcfExtended\FieldType;

use GraphQL\Deferred;

class AcfeMenus {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type( 'acfe_menus', [
			'graphql_type' => [ 'list_of' => 'Menu' ],
			'resolve'      => function ( $root, $args, $context, $info, $field_type, $field_config ) {
				$value = $field_config->resolve_field( $root, $args, $context, $info );

				if ( empty( $value ) ) {
					return null;
				}

				if ( ! is_array( $value ) ) {
					$value = [ $value ];
				}

				return new Deferred( function () use ( $value, $context ) {
					return array_filter( array_map( static function ( $id ) use ( $context ) {
						$nav_menu = wp_get_nav_menu_object( $id );
						if ( empty( $nav_menu->term_id ) ) {
							return null;
						}
						return $context->get_loader( 'term' )->load( $nav_menu->term_id );
					}, $value ) );
				});
			},
		] );
	}

}

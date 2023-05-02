<?php

namespace WPGraphQLAcf\ThirdParty\AcfExtended\FieldType;

use GraphQL\Deferred;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Model\UserRole;

class AcfeUserRoles {

	/**
	 * @return void
	 */
	public static function register_field_type(): void {
		register_graphql_acf_field_type( 'acfe_user_roles', [
			'graphql_type' => [ 'list_of' => 'UserRole' ],
			'resolve'      => function ( $root, $args, AppContext $context, ResolveInfo $info, $field_type, $field_config ) {
				$value = $field_config->resolve_field( $root, $args, $context, $info );
				if ( empty( $value ) ) {
					return null;
				}

				if ( ! is_array( $value ) ) {
					$value = [ $value ];
				}

				return new Deferred( function () use ( $value, $context ) {
					return array_filter( array_map( static function ( $user_role ) use ( $context ) {
						return $context->get_loader( 'user_role' )->load( $user_role );
					}, $value ) );
				});

			},
		] );
	}

}

<?php
namespace WPGraphQL\Acf;

/**
 * Handles the resolution of an ACF Field in the GraphQL Schema
 */
class AcfGraphQLFieldResolver {

	/**
	 * @var AcfGraphQLFieldType
	 */
	protected $acf_graphql_field_type;

	/**
	 * @param AcfGraphQLFieldType $acf_graphql_field_type
	 */
	public function __construct( AcfGraphQLFieldType $acf_graphql_field_type ) {
		$this->acf_graphql_field_type = $acf_graphql_field_type;
	}

	/**
	 * @return AcfGraphQLFieldType
	 */
	public function get_acf_graphql_field_type(): AcfGraphQLFieldType {
		return $this->acf_graphql_field_type;
	}

}

<?php
namespace WPGraphQLAcf;

/**
 * Handles the resolution of an ACF Field in the GraphQL Schema
 */
class AcfGraphQLFieldResolver {

	/**
	 * @var \WPGraphQLAcf\AcfGraphQLFieldType
	 */
	protected $acf_graphql_field_type;

	/**
	 * @param \WPGraphQLAcf\AcfGraphQLFieldType $acf_graphql_field_type
	 */
	public function __construct( AcfGraphQLFieldType $acf_graphql_field_type ) {
		$this->acf_graphql_field_type = $acf_graphql_field_type;
	}

	/**
	 * @return \WPGraphQLAcf\AcfGraphQLFieldType
	 */
	public function get_acf_graphql_field_type(): AcfGraphQLFieldType {
		return $this->acf_graphql_field_type;
	}

}

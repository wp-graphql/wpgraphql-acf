<?php
namespace WPGraphQL\Acf\ThirdParty\WPGraphQLContentBlocks;

class WPGraphQLContentBlocks {

	public function init(): void {

		// @see: https://github.com/wpengine/wp-graphql-content-blocks/pull/148
		add_filter( 'wpgraphql_content_blocks_should_apply_post_type_editor_blocks_interfaces', [ $this, 'filter_editor_block_interfaces' ], 10, 7 );

	}

	/**
	 * @param bool $should Whether the Block should apply the ${PostTypeName}EditorBlock Interface
	 * @param string $block_name The name of the block Interfaces are being applied to
	 * @param \WP_Block_Editor_Context $block_editor_context The Block Editor Context
	 * @param \WP_Post_Type $post_type The Post Type the block could be connected with
	 * @param array $all_registered_blocks All blocks registered to Gutenberg
	 * @param array $supported_blocks_for_post_type_context The blocks supported for the context, after "allowed_blocks_all" filter is applied
	 * @param array $block_and_graphql_enabled_post_types Post Types that support Gutenberg and WPGraphQL
	 *
	 * @return bool
	 */
	public function filter_editor_block_interfaces( bool $should, $block_name, $block_editor_context, $post_type, $all_registered_blocks, $supported_blocks_for_post_type_context, $block_and_graphql_enabled_post_types ) {
		if ( ! empty( $all_registered_blocks[ $block_name ]->post_types ) && ! in_array( $post_type->name, $all_registered_blocks[ $block_name ]->post_types, true ) ) {
			return false;
		}
		return $should;
	}

}

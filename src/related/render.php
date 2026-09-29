<?php
/**
 * Server render callback for the related block.
 *
 * @package SCI\EditorialBlocks
 */

defined( 'ABSPATH' ) || exit;

$post_id   = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : 0;
$post_type = isset( $block->context['postType'] ) && is_string( $block->context['postType'] )
	? $block->context['postType']
	: '';

if ( $post_id < 1 || 'post' !== $post_type ) {
	return;
}

$source = get_post( $post_id );
if ( ! $source instanceof WP_Post || 'post' !== $source->post_type || 'publish' !== $source->post_status ) {
	return;
}

$candidate = \SCI\EditorialBlocks\related_candidate_for_post( $post_id );
if ( ! $candidate instanceof WP_Post ) {
	return;
}

$permalink = get_permalink( $candidate );
$title     = get_the_title( $candidate );

if ( ! is_string( $permalink ) || '' === $permalink || ! is_string( $title ) || '' === trim( $title ) ) {
	return;
}

?>
<aside <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<span class="sci-related-label"><?php echo esc_html__( 'Relacionado', 'sci-editorial-blocks' ); ?></span>
	<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
</aside>

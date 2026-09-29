<?php
/**
 * Server render callback for the reading-time block.
 *
 * @package SCI\EditorialBlocks
 */

defined( 'ABSPATH' ) || exit;

$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : 0;

// Single templates render top-level blocks without a parent block context.
// Use the queried singular post as the equivalent Core request context there.
if ( $post_id < 1 && is_singular() ) {
	$post_id = absint( get_queried_object_id() );
}

if ( $post_id < 1 ) {
	return;
}

$post = get_post( $post_id );
if ( ! $post instanceof WP_Post ) {
	return;
}

$minutes = \SCI\EditorialBlocks\reading_time_minutes_for_post( $post_id );

if ( $minutes < 1 ) {
	return;
}

$label = sprintf(
	_n( '%s min de lectura', '%s min de lectura', $minutes, 'sci-editorial-blocks' ),
	number_format_i18n( $minutes )
);

?>
<span <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $label ); ?></span>

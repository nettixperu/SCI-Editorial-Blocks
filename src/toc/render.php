<?php
/**
 * Server-side render for SCI TOC.
 *
 * @package SCI\EditorialBlocks
 */

defined( 'ABSPATH' ) || exit;

$post_id = \SCI\EditorialBlocks\toc_post_id( $block );
if ( ! $post_id || ! \SCI\EditorialBlocks\toc_is_required( $post_id ) ) {
	return;
}

$plan  = \SCI\EditorialBlocks\toc_anchor_plan( $post_id );
$items = array();
foreach ( $plan as $entry ) {
	if ( '' === $entry['label'] ) {
		continue;
	}
	$fragment = str_starts_with( $entry['id'], '#' ) ? $entry['id'] : '#' . $entry['id'];
	$items[]  = '<li><a href="' . esc_attr( $fragment ) . '">' . esc_html( $entry['label'] ) . '</a></li>';
}

if ( empty( $items ) ) {
	return;
}

$aria_label = esc_attr__( 'En este artículo', 'sci-editorial-blocks' );
$visible    = esc_html__( 'En este artículo', 'sci-editorial-blocks' );
$item_gap = isset( $attributes['itemGap'] ) && is_string( $attributes['itemGap'] ) ? $attributes['itemGap'] : '0.75rem';
$item_gap = preg_match( '/^(?:0|(?:0|[1-9][0-9]*)(?:\\.[0-9]+)?)(?:px|rem|em)$/', $item_gap ) ? $item_gap : '0.75rem';
$accent_color = isset( $attributes['accentColor'] ) && is_string( $attributes['accentColor'] ) ? sanitize_hex_color( $attributes['accentColor'] ) : '#000000';
$accent_color = $accent_color ? $accent_color : '#000000';
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'wp-block-sci-editorial-toc',
		'style' => '--sci-toc-item-gap:' . esc_attr( $item_gap ) . ';--sci-toc-accent-color:' . esc_attr( $accent_color ) . ';',
	)
);
echo '<nav aria-label="' . $aria_label . '" ' . $wrapper_attributes . '><p class="wp-block-sci-editorial-toc__label">' . $visible . '</p><ul>' . implode( '', $items ) . '</ul></nav>';

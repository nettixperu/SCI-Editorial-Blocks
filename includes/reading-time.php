<?php
/**
 * Reading time calculation for the SCI — Tiempo de lectura block.
 *
 * @package SCI\EditorialBlocks
 */

namespace SCI\EditorialBlocks;

defined( 'ABSPATH' ) || exit;

/**
 * Extract direct HTML fragments from a parsed block without visiting children.
 *
 * @param array $block Parsed block.
 * @return string
 */
function reading_time_direct_html( array $block ): string {
	if ( isset( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
		return implode( ' ', array_filter( $block['innerContent'], 'is_string' ) );
	}

	return isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] )
		? $block['innerHTML']
		: '';
}

/**
 * Collect allowed editorial text from parsed Gutenberg blocks.
 *
 * Unknown and dynamic blocks are skipped with their descendants. Only known
 * static layout containers are traversed to reach allowed editorial blocks.
 *
 * @param array $blocks Parsed blocks.
 * @param bool  $inside_list Whether list-item is currently a valid structural child.
 * @param bool  $inside_callout Whether traversal is restricted to Callout content.
 * @return string[]
 */
function reading_time_collect_block_text( array $blocks, bool $inside_list = false, bool $inside_callout = false ): array {
	$fragments = array();

	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$name     = isset( $block['blockName'] ) && is_string( $block['blockName'] ) ? $block['blockName'] : null;
		$children = isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ? $block['innerBlocks'] : array();

		if ( 'sci-editorial/callout' === $name && ! $inside_callout ) {
			$fragments = array_merge( $fragments, reading_time_collect_block_text( $children, false, true ) );
			continue;
		}

		if ( $inside_callout && ! in_array( $name, array( 'core/paragraph', 'core/list', 'core/list-item' ), true ) ) {
			continue;
		}

		if ( in_array( $name, array( 'core/paragraph', 'core/heading', 'core/quote' ), true ) ) {
			$fragments[] = reading_time_direct_html( $block );

			// Quotes can contain paragraph blocks. Do not descend into text blocks,
			// which would otherwise risk counting content represented in parent HTML.
			if ( 'core/quote' === $name ) {
				$fragments = array_merge( $fragments, reading_time_collect_block_text( $children, false, $inside_callout ) );
			}

			continue;
		}

		if ( 'core/list' === $name ) {
			$fragments[] = reading_time_direct_html( $block );
			$fragments = array_merge( $fragments, reading_time_collect_block_text( $children, true, $inside_callout ) );
			continue;
		}

		if ( 'core/list-item' === $name && $inside_list ) {
			$fragments[] = reading_time_direct_html( $block );
			$fragments = array_merge( $fragments, reading_time_collect_block_text( $children, true, $inside_callout ) );
			continue;
		}

		if ( ! $inside_callout && in_array( $name, array( 'core/group', 'core/columns', 'core/column' ), true ) ) {
			$fragments = array_merge( $fragments, reading_time_collect_block_text( $children ) );
		}
	}

	return $fragments;
}

/**
 * Count Unicode words in text using the feature's approved tokenization rule.
 *
 * @param string $text Text to count.
 * @return int
 */
function reading_time_word_count( string $text ): int {
	if ( 1 !== preg_match( '//u', $text ) ) {
		return 0;
	}

	$result = preg_match_all( "~[\\p{L}\\p{N}][\\p{L}\\p{M}\\p{N}]*(?:['’\\-][\\p{L}\\p{N}][\\p{L}\\p{M}\\p{N}]*)*~u", $text );

	return false === $result ? 0 : $result;
}

/**
 * Extract normalized editorial text from serialized post content.
 *
 * @param string $post_content Serialized Gutenberg content.
 * @return string
 */
function reading_time_extract_text( string $post_content ): string {
	$blocks    = parse_blocks( $post_content );
	$fragments = reading_time_collect_block_text( is_array( $blocks ) ? $blocks : array() );
	$html      = implode( ' ', $fragments );
	$html      = str_ireplace(
		array(
			'</p>',
			'</h1>',
			'</h2>',
			'</h3>',
			'</h4>',
			'</h5>',
			'</h6>',
			'</li>',
			'</blockquote>',
			'<br>',
			'<br/>',
			'<br />',
		),
		' ',
		$html
	);
	$text = wp_strip_all_tags( $html, true );

	return html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}

/**
 * Calculate whole reading minutes for a post, memoized for this request.
 *
 * @param int $post_id Post identifier.
 * @return int
 */
function reading_time_minutes_for_post( int $post_id ): int {
	static $minutes_by_post = array();

	if ( $post_id < 1 ) {
		return 0;
	}

	if ( array_key_exists( $post_id, $minutes_by_post ) ) {
		return $minutes_by_post[ $post_id ];
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof \WP_Post || ! is_string( $post->post_content ) || '' === $post->post_content ) {
		$minutes_by_post[ $post_id ] = 0;
		return 0;
	}

	$text  = reading_time_extract_text( $post->post_content );
	$words = reading_time_word_count( $text );

	$minutes_by_post[ $post_id ] = $words > 0 ? (int) ceil( $words / 220 ) : 0;
	return $minutes_by_post[ $post_id ];
}

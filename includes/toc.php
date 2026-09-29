<?php
/**
 * Request-local TOC anchor planning and post-content integration.
 *
 * @package SCI\EditorialBlocks
 */

namespace SCI\EditorialBlocks;

defined( 'ABSPATH' ) || exit;

const TOC_BLOCK_NAME = 'sci-editorial/toc';
const TOC_HEADING_MARKER = 'data-sci-toc-heading';

function toc_content_render_scope( string $action, int $post_id = 0 ): array {
	static $contexts = array();

	if ( 'begin' === $action ) {
		$contexts[] = array(
			'post_id' => $post_id,
			'marker'  => wp_generate_uuid4(),
		);
		return end( $contexts );
	}

	if ( 'end' === $action ) {
		return (array) array_pop( $contexts );
	}

	return empty( $contexts ) ? array() : end( $contexts );
}

function toc_begin_post_content_render( $pre_render, array $parsed_block, ?\WP_Block $parent_block = null ) {
	if ( null !== $pre_render || 'core/post-content' !== ( $parsed_block['blockName'] ?? '' ) ) {
		return $pre_render;
	}

	$post_id = absint( get_the_ID() );
	if ( $post_id && toc_is_required( $post_id ) ) {
		toc_content_render_scope( 'begin', $post_id );
	}

	return $pre_render;
}

function toc_mark_heading_provenance( string $content, array $parsed_block, \WP_Block $instance ): string {
	$post_id = toc_post_id( $instance );
	$level   = isset( $parsed_block['attrs']['level'] ) ? (int) $parsed_block['attrs']['level'] : 2;
	$scope   = toc_content_render_scope( 'current' );

	if (
		2 !== $level ||
		! $post_id ||
		$post_id !== ( $scope['post_id'] ?? 0 ) ||
		! toc_is_required( $post_id ) ||
		! class_exists( 'WP_HTML_Tag_Processor' )
	) {
		return $content;
	}

	$processor = new \WP_HTML_Tag_Processor( $content );
	if ( $processor->next_tag( 'H2' ) ) {
		$processor->set_attribute( TOC_HEADING_MARKER, $scope['marker'] );
	}

	return $processor->get_updated_html();
}

function toc_post_id( \WP_Block $block ): int {
	if ( ! empty( $block->context['postId'] ) ) {
		return absint( $block->context['postId'] );
	}

	return absint( get_the_ID() );
}

function toc_collect_h2( array $blocks, array &$records ): void {
	foreach ( $blocks as $parsed_block ) {
		if ( 'core/heading' === ( $parsed_block['blockName'] ?? '' ) ) {
			$attributes = is_array( $parsed_block['attrs'] ?? null ) ? $parsed_block['attrs'] : array();
			$level      = isset( $attributes['level'] ) ? (int) $attributes['level'] : 2;

			if ( 2 === $level ) {
				$records[] = array(
					'label'  => trim( wp_strip_all_tags( (string) ( $parsed_block['innerHTML'] ?? '' ) ) ),
					'anchor' => isset( $attributes['anchor'] ) ? (string) $attributes['anchor'] : '',
				);
			}
		}

		if ( ! empty( $parsed_block['innerBlocks'] ) && is_array( $parsed_block['innerBlocks'] ) ) {
			toc_collect_h2( $parsed_block['innerBlocks'], $records );
		}
	}
}

function toc_anchor_plan( int $post_id ): array {
	static $cache = array();

	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof \WP_Post ) {
		return $cache[ $post_id ] = array();
	}

	$records = array();
	toc_collect_h2( parse_blocks( (string) $post->post_content ), $records );

	$reserved = array();
	foreach ( $records as $record ) {
		if ( '' !== $record['anchor'] ) {
			$reserved[ $record['anchor'] ] = true;
		}
	}

	$plan = array();
	foreach ( $records as $record ) {
		if ( '' !== $record['anchor'] ) {
			$plan[] = array(
				'label'  => $record['label'],
				'id'     => $record['anchor'],
				'source' => 'manual',
			);
			continue;
		}

		$base = sanitize_title( $record['label'] );
		// Core may percent-encode emoji instead of returning an empty slug.
		// Treat labels with no accent-normalized ASCII letter/digit as empty so
		// the contract remains stable across supported Core versions.
		$slug_source = remove_accents( $record['label'] );
		if ( '' === $base || false === strpbrk( $slug_source, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' ) ) {
			$base = 'section';
		}
		$base = 'sci-toc-' . $base;
		$id   = $base;
		$suffix = 2;
		while ( isset( $reserved[ $id ] ) ) {
			$id = $base . '-' . $suffix;
			$suffix++;
		}
		$reserved[ $id ] = true;

		$plan[] = array(
			'label'  => $record['label'],
			'id'     => $id,
			'source' => 'generated',
		);
	}

	return $cache[ $post_id ] = $plan;
}

function toc_current_template_content(): string {
	global $_wp_current_template_content, $_wp_current_template_id;

	if ( isset( $_wp_current_template_content ) && is_string( $_wp_current_template_content ) ) {
		return $_wp_current_template_content;
	}

	if ( isset( $_wp_current_template_id ) && function_exists( 'get_block_template' ) ) {
		$template = get_block_template( $_wp_current_template_id, 'wp_template' );
		if ( $template instanceof \WP_Block_Template ) {
			return (string) $template->content;
		}
	}

	return '';
}

function toc_is_required( int $post_id ): bool {
	static $cache = array();

	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof \WP_Post ) {
		return $cache[ $post_id ] = false;
	}

	$required = has_block( TOC_BLOCK_NAME, $post->post_content );
	if ( ! $required ) {
		$required = has_block( TOC_BLOCK_NAME, toc_current_template_content() );
	}

	return $cache[ $post_id ] = $required;
}

function toc_filter_the_content( string $content ): string {
	$scope = toc_content_render_scope( 'current' );
	if ( empty( $scope ) ) {
		return $content;
	}

	try {
		return toc_inject_ids_for_post( $content, (int) ( $scope['post_id'] ?? 0 ), (string) ( $scope['marker'] ?? '' ) );
	} finally {
		toc_content_render_scope( 'end' );
	}
}

function toc_inject_ids_for_post( string $content, int $post_id, string $marker_value ): string {
	if ( ! $post_id || '' === $marker_value || ! toc_is_required( $post_id ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $content;
	}

	$plan = toc_anchor_plan( $post_id );
	$processor = new \WP_HTML_Tag_Processor( $content );
	$index     = 0;
	while ( $processor->next_tag( 'H2' ) ) {
		$marker = $processor->get_attribute( TOC_HEADING_MARKER );
		if ( null === $marker ) {
			continue;
		}

		$processor->remove_attribute( TOC_HEADING_MARKER );
		if ( $marker_value !== $marker || ! isset( $plan[ $index ] ) ) {
			continue;
		}

		$entry = $plan[ $index ];
		if ( 'generated' === $entry['source'] && null === $processor->get_attribute( 'id' ) ) {
			$processor->set_attribute( 'id', $entry['id'] );
		}
		$index++;
	}

	return $processor->get_updated_html();
}

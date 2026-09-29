<?php
/**
 * Plugin Name: SCI Editorial Blocks
 * Description: Editorial Gutenberg blocks for WordPress.
 * Version: 0.4.0
 * Requires at least: 7.0
 * Requires PHP: 8.2
 * Text Domain: sci-editorial-blocks
 *
 * @package SCI\EditorialBlocks
 */

namespace SCI\EditorialBlocks;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/toc.php';
require_once __DIR__ . '/includes/reading-time.php';
require_once __DIR__ . '/includes/related.php';

add_action( 'init', __NAMESPACE__ . '\\register_blocks' );
add_action( 'init', __NAMESPACE__ . '\\register_patterns', 20 );
add_filter( 'pre_render_block', __NAMESPACE__ . '\\toc_begin_post_content_render', 10, 3 );
add_filter( 'render_block_core/heading', __NAMESPACE__ . '\\toc_mark_heading_provenance', 10, 3 );
add_filter( 'the_content', __NAMESPACE__ . '\\toc_filter_the_content', 20 );

/**
 * Register the block types from their built metadata.
 *
 * @return void
 */
function register_blocks(): void {
	register_block_type_from_metadata( __DIR__ . '/build/toc' );
	register_block_type_from_metadata( __DIR__ . '/build/callout' );
	register_block_type_from_metadata( __DIR__ . '/build/reading-time' );
	register_block_type_from_metadata( __DIR__ . '/build/related' );
	register_block_type_from_metadata( __DIR__ . '/build/sources' );
}

/**
 * Register Gutenberg-native patterns supplied by the plugin.
 *
 * @return void
 */
function register_patterns(): void {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	$image_url        = esc_url( plugins_url( 'assets/icons/summary.svg', __FILE__ ) );
	$image_attributes = wp_json_encode(
		array(
			'url'             => $image_url,
			'alt'             => '',
			'width'           => 24,
			'height'          => 24,
			'sizeSlug'        => 'thumbnail',
			'linkDestination' => 'none',
		)
	);

	$content = '<!-- wp:group -->' . "\n";
	$content .= '<div class="wp-block-group">' . "\n";
	$content .= '<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->' . "\n";
	$content .= '<div class="wp-block-group">' . "\n";
	$content .= '<!-- wp:image ' . $image_attributes . ' -->' . "\n";
	$content .= '<figure class="wp-block-image size-thumbnail is-resized"><img src="' . esc_attr( $image_url ) . '" alt="" width="24" height="24"/></figure>' . "\n";
	$content .= '<!-- /wp:image -->' . "\n";
	$content .= '<!-- wp:paragraph -->' . "\n";
	$content .= '<p>' . esc_html__( 'En resumen', 'sci-editorial-blocks' ) . '</p>' . "\n";
	$content .= '<!-- /wp:paragraph -->' . "\n";
	$content .= '</div>' . "\n";
	$content .= '<!-- /wp:group -->' . "\n\n";
	$content .= '<!-- wp:post-excerpt /-->' . "\n";
	$content .= '</div>' . "\n";
	$content .= '<!-- /wp:group -->';

	register_block_pattern(
		'sci-editorial/automatic-summary',
		array(
			'title'      => __( 'SCI — En resumen automático', 'sci-editorial-blocks' ),
			'description' => __( 'Sección editorial que muestra el extracto nativo de la entrada.', 'sci-editorial-blocks' ),
			'categories' => array( 'featured' ),
			'content'    => $content,
		)
	);
}

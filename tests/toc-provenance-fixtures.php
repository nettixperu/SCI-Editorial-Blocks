<?php
/**
 * TOC provenance regression fixtures. Run with WP-CLI eval-file.
 */

use SCI\EditorialBlocks as TOC;

$GLOBALS['toc_fixture_failures'] = array();
$GLOBALS['toc_fixture_created_posts'] = array();

function toc_fixture_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		$GLOBALS['toc_fixture_failures'][] = $message;
		echo "FAIL: {$message}\n";
		return;
	}

	echo "PASS: {$message}\n";
}

function toc_fixture_block( string $name, array $attrs = array(), string $html = '', array $inner_blocks = array() ): array {
	$inner_content = empty( $inner_blocks ) ? array( $html ) : array_fill( 0, count( $inner_blocks ), null );

	return array(
		'blockName'    => $name,
		'attrs'        => $attrs,
		'innerBlocks'  => $inner_blocks,
		'innerHTML'    => $html,
		'innerContent' => $inner_content,
	);
}

function toc_fixture_heading( int $level, string $text, string $anchor = '' ): array {
	$attrs = array( 'level' => $level );
	if ( '' !== $anchor ) {
		$attrs['anchor'] = $anchor;
	}
	$id_attribute = '' !== $anchor ? ' id="' . esc_attr( $anchor ) . '"' : '';
	$html         = sprintf( '<h%d class="wp-block-heading"%s>%s</h%d>', $level, $id_attribute, $text, $level );
	return toc_fixture_block( 'core/heading', $attrs, $html );
}

function toc_fixture_raw_h2( string $text, string $marker = '' ): array {
	$marker_attribute = '' !== $marker ? ' data-sci-toc-heading="' . esc_attr( $marker ) . '"' : '';
	return toc_fixture_block( 'core/html', array(), '<h2' . $marker_attribute . '>' . esc_html( $text ) . '</h2>' );
}

function toc_fixture_render_post( array $blocks, string $suffix ): string {
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => 'SCI TOC provenance ' . $suffix,
			'post_name'    => 'sci-toc-provenance-' . sanitize_title( $suffix ) . '-' . wp_generate_password( 6, false, false ),
			'post_content' => serialize_blocks( $blocks ),
		)
	);
	if ( is_wp_error( $post_id ) ) {
		throw new RuntimeException( $post_id->get_error_message() );
	}
	$GLOBALS['toc_fixture_created_posts'][] = (int) $post_id;

	$post = get_post( $post_id );
	$GLOBALS['post'] = $post;
	setup_postdata( $post );
	$source_content = $post->post_content;
	$post_content_block = parse_blocks( '<!-- wp:post-content /-->' )[0];
	$html = render_block( $post_content_block );
	toc_fixture_assert( $source_content === get_post_field( 'post_content', $post_id ), 'Rendering leaves post_content unchanged' );
	wp_reset_postdata();
	return $html;
}

try {
	$blocks = array(
		toc_fixture_raw_h2( 'Raw before first Core heading', 'spoofed-value' ),
		toc_fixture_heading( 1, 'Ignored H1' ),
		toc_fixture_heading( 2, 'Manual <em>Section</em>', 'manual-section' ),
		toc_fixture_raw_h2( 'Unrelated HTML H2' ),
		toc_fixture_raw_h2( 'Another unrelated HTML H2' ),
		toc_fixture_heading( 3, 'Ignored H3' ),
		toc_fixture_heading( 2, 'Generated Section' ),
		toc_fixture_raw_h2( 'Raw after final Core heading' ),
		toc_fixture_heading( 2, 'Generated Section' ),
		toc_fixture_heading( 2, 'Collision' ),
		toc_fixture_heading( 2, 'Manual duplicate one', 'same-manual-id' ),
		toc_fixture_heading( 2, 'Manual duplicate two', 'same-manual-id' ),
		toc_fixture_heading( 2, 'Manual collision', 'sci-toc-collision' ),
		toc_fixture_heading( 2, 'Collision' ),
		toc_fixture_block( 'core/group', array(), '', array( toc_fixture_heading( 2, 'Nested Group Heading' ) ) ),
		toc_fixture_block( TOC\TOC_BLOCK_NAME ),
	);
	$html = toc_fixture_render_post( $blocks, 'mixed-html' );

	toc_fixture_assert( false !== strpos( $html, 'Raw before first Core heading' ) && false === strpos( $html, '<h2 id="sci-toc-raw-before-first-core-heading"' ), 'Non-Core H2 before a Core heading is preserved and does not consume the plan' );
	toc_fixture_assert( false !== strpos( $html, '<h2>Unrelated HTML H2</h2>' ) && false !== strpos( $html, '<h2>Another unrelated HTML H2</h2>' ), 'Interleaved non-Core H2 headings are preserved' );
	toc_fixture_assert( false !== strpos( $html, '<h2>Raw after final Core heading</h2>' ), 'Non-Core H2 after the final Core heading is preserved' );
	toc_fixture_assert( false !== strpos( $html, 'id="manual-section"' ), 'Manual Core H2 anchor remains unchanged' );
	toc_fixture_assert( false !== strpos( $html, 'id="sci-toc-generated-section"' ), 'First generated Core H2 receives the matching planned ID' );
	toc_fixture_assert( false !== strpos( $html, 'id="sci-toc-generated-section-2"' ), 'Duplicate generated headings receive deterministic suffixes' );
	toc_fixture_assert( false !== strpos( $html, 'id="sci-toc-collision-2"' ), 'Manual/generated collision reserves the manual anchor' );
	toc_fixture_assert( 2 === substr_count( $html, 'id="same-manual-id"' ), 'Duplicate manual anchors are preserved without rewriting' );
	toc_fixture_assert( false !== strpos( $html, 'href="#manual-section"' ) && false !== strpos( $html, 'href="#sci-toc-generated-section"' ), 'TOC hrefs use the exact manual and generated plan IDs' );
	toc_fixture_assert( false !== strpos( $html, '<a href="#manual-section">Manual Section</a>' ), 'Inline Core formatting is stripped from the TOC label while preserving the heading' );
	toc_fixture_assert( false !== strpos( $html, '<h1 class="wp-block-heading">Ignored H1</h1>' ) && false !== strpos( $html, '<h3 class="wp-block-heading">Ignored H3</h3>' ), 'H1 and H3 remain outside the TOC plan' );
	toc_fixture_assert( false !== strpos( $html, 'id="sci-toc-nested-group-heading"' ), 'Nested Core H2 receives its planned ID' );
	toc_fixture_assert( false === strpos( $html, TOC\TOC_HEADING_MARKER ), 'Temporary provenance marker is absent from final frontend HTML' );

	$outside = render_block( parse_blocks( serialize_blocks( array( toc_fixture_heading( 2, 'Template heading' ) ) ) )[0] );
	toc_fixture_assert( false === strpos( $outside, TOC\TOC_HEADING_MARKER ), 'Core H2 rendered outside core/post-content scope is not marked' );

	$toc_first = toc_fixture_render_post(
		array(
			toc_fixture_block( TOC\TOC_BLOCK_NAME ),
			toc_fixture_raw_h2( 'Unrelated before heading' ),
			toc_fixture_heading( 2, 'Order independent heading' ),
		),
		'toc-first'
	);
	toc_fixture_assert( false !== strpos( $toc_first, 'href="#sci-toc-order-independent-heading"' ) && false !== strpos( $toc_first, 'id="sci-toc-order-independent-heading"' ), 'A TOC rendered before Post Content headings shares the same plan and ID' );

	$without_toc = toc_fixture_render_post(
		array( toc_fixture_raw_h2( 'Unindexed HTML H2' ), toc_fixture_heading( 2, 'No TOC heading' ) ),
		'without-toc'
	);
	toc_fixture_assert( false === strpos( $without_toc, TOC\TOC_HEADING_MARKER ) && false === strpos( $without_toc, 'id="sci-toc-no-toc-heading"' ), 'Post without a TOC receives no markers or generated IDs' );
} finally {
	foreach ( array_reverse( $GLOBALS['toc_fixture_created_posts'] ) as $post_id ) {
		wp_delete_post( $post_id, true );
	}
	wp_reset_postdata();
}

echo 'FAILURES=' . count( $GLOBALS['toc_fixture_failures'] ) . "\n";
exit( empty( $GLOBALS['toc_fixture_failures'] ) ? 0 : 1 );

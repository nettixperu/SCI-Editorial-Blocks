<?php
use SCI\EditorialBlocks as Related;

$GLOBALS['failures'] = array();
$created_posts = array();
$created_terms = array();
$prefix = 'sci-related-qa-' . wp_generate_password( 8, false, false );

function related_qa_assert( $condition, $message ) {
	global $failures;
	if ( ! $condition ) {
		$GLOBALS['failures'][] = $message;
		echo "FAIL: {$message}\n";
	} else {
		echo "PASS: {$message}\n";
	}
}

function related_qa_term( $taxonomy, $slug ) {
	global $created_terms;
	$term = wp_insert_term( $slug, $taxonomy, array( 'slug' => $slug ) );
	if ( is_wp_error( $term ) ) {
		$existing = get_term_by( 'slug', $slug, $taxonomy );
		$term_id = $existing ? (int) $existing->term_id : 0;
	} else {
		$term_id = (int) $term['term_id'];
	}
	$created_terms[] = array( $term_id, $taxonomy );
	return $term_id;
}

function related_qa_post( $title, $status = 'publish', $date = '2026-09-01 00:00:00', $password = '' ) {
	global $created_posts, $prefix;
	$post_id = wp_insert_post(
		array(
			'post_title'    => $title,
			'post_name'     => sanitize_title( $prefix . '-' . $title . '-' . wp_generate_password( 4, false, false ) ),
			'post_type'     => 'post',
			'post_status'   => $status,
			'post_date'     => $date,
			'post_date_gmt' => get_gmt_from_date( $date ),
			'post_content'  => 'fixture content',
			'post_password' => $password,
		));
	$created_posts[] = (int) $post_id;
	return (int) $post_id;
}

function related_qa_set_terms( $post_id, $taxonomy, $term_ids ) {
	wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
}

try {
	$cat_a = related_qa_term( 'category', $prefix . '-cat-a' );
	$cat_b = related_qa_term( 'category', $prefix . '-cat-b' );
	$cat_c = related_qa_term( 'category', $prefix . '-cat-c' );
	$cat_d = related_qa_term( 'category', $prefix . '-cat-d' );
	$cat_e = related_qa_term( 'category', $prefix . '-cat-e' );
	$cat_f = related_qa_term( 'category', $prefix . '-cat-f' );
	$tag_a = related_qa_term( 'post_tag', $prefix . '-tag-a' );
	$tag_b = related_qa_term( 'post_tag', $prefix . '-tag-b' );
	$tag_c = related_qa_term( 'post_tag', $prefix . '-tag-c' );

	$source_tokens = Related\related_title_tokens( 'Árbol ZFS iSCSI 2026' );
	$case_tokens = Related\related_title_tokens( 'árbol SAN iscsi' );
	related_qa_assert( Related\related_titles_share_token( $source_tokens, $case_tokens ), 'Unicode title tokens compare case-insensitively' );
	$category_score = Related\related_score( array( 1, 2 ), array(), array(), array( 1, 2 ), array(), array() );
	related_qa_assert( 3 === $category_score, 'A: one or multiple shared categories add +3 once' );
	related_qa_assert( 2 === Related\related_score( array(), array( 10 ), array(), array(), array( 10 ), array() ), 'B: one shared tag scores 2' );
	related_qa_assert( 4 === Related\related_score( array(), array( 10, 11 ), array(), array(), array( 10, 11 ), array() ), 'C: two shared tags score 4' );
	related_qa_assert( 5 === Related\related_score( array( 1 ), array( 10 ), array(), array( 1 ), array( 10 ), array() ), 'D: category plus one tag scores 5' );
	related_qa_assert( 7 === Related\related_score( array( 1 ), array( 10, 11, 12 ), array(), array( 1 ), array( 10, 11, 12 ), array() ), 'E: category plus tags caps tag contribution at 4' );
	related_qa_assert( 8 === Related\related_score( array( 1 ), array( 10, 11 ), array( 'Árbol', 'ZFS' ), array( 1 ), array( 10, 11 ), array( 'árbol', 'ZFS' ) ), 'F: category, two tags and title match score 8' );
	related_qa_assert( 1 === ( Related\related_score( array(), array(), array( 'Árbol', 'ZFS', 'iSCSI' ), array(), array(), array( 'árbol', 'zfs', 'iscsi' ) ) ), 'G: multiple shared title tokens add only +1 total' );

	$cat_source = related_qa_post( 'Fuente Categoría' );
	$cat_candidate = related_qa_post( 'Destino Categoría sin coincidencia' );
	related_qa_set_terms( $cat_source, 'category', array( $cat_a ) );
	related_qa_set_terms( $cat_candidate, 'category', array( $cat_a ) );
	$category_selected = Related\related_candidate_for_post( $cat_source );
	related_qa_assert( $category_selected instanceof WP_Post && $cat_candidate === (int) $category_selected->ID, 'A: category-sharing candidate is selected' );

	$one_tag_source = related_qa_post( 'Fuente tag único alpha' );
	$one_tag_candidate = related_qa_post( 'Destino unrelated beta' );
	related_qa_set_terms( $one_tag_source, 'category', array() );
	related_qa_set_terms( $one_tag_candidate, 'category', array() );
	related_qa_set_terms( $one_tag_source, 'post_tag', array( $tag_a ) );
	related_qa_set_terms( $one_tag_candidate, 'post_tag', array( $tag_a ) );
	related_qa_assert( false === Related\related_candidate_for_post( $one_tag_source ), 'B: a lone shared tag without title match is below threshold' );

	$two_tag_source = related_qa_post( 'Fuente tags alpha beta' );
	$two_tag_candidate = related_qa_post( 'Destino unrelated gamma' );
	related_qa_set_terms( $two_tag_source, 'category', array() );
	related_qa_set_terms( $two_tag_candidate, 'category', array() );
	related_qa_set_terms( $two_tag_source, 'post_tag', array( $tag_a, $tag_b ) );
	related_qa_set_terms( $two_tag_candidate, 'post_tag', array( $tag_a, $tag_b ) );
	related_qa_assert( $two_tag_candidate === (int) Related\related_candidate_for_post( $two_tag_source )->ID, 'C: two shared tags meet threshold' );

	$empty_source = related_qa_post( 'Fuente sin taxonomías' );
	related_qa_set_terms( $empty_source, 'category', array() );
	related_qa_set_terms( $empty_source, 'post_tag', array() );
	$before_empty = $GLOBALS['wpdb']->num_queries;
	$empty_result = Related\related_candidate_for_post( $empty_source );
	$empty_queries = $GLOBALS['wpdb']->num_queries - $before_empty;
	related_qa_assert( false === $empty_result, 'L: title similarity alone does not discover a candidate' );
	related_qa_assert( $empty_queries <= 2, 'L: no-taxonomy source avoids candidate WP_Query (term lookups observed ' . $empty_queries . ' SQL queries)' );

	$rank_source = related_qa_post( 'Fuente ranking core' );
	$rank_low = related_qa_post( 'Destino puntuación base', 'publish', '2026-09-10 00:00:00' );
	$rank_high = related_qa_post( 'Destino puntuación superior', 'publish', '2026-09-09 00:00:00' );
	$rank_same_score_old = related_qa_post( 'Destino mismo marcador antiguo', 'publish', '2026-09-08 00:00:00' );
	$rank_draft = related_qa_post( 'Destino borrador', 'draft' );
	$rank_private = related_qa_post( 'Destino privado', 'private' );
	$rank_password = related_qa_post( 'Destino protegido', 'publish', '2026-09-11 00:00:00', 'secret' );
	foreach ( array( $rank_source, $rank_low, $rank_high, $rank_same_score_old, $rank_draft, $rank_private, $rank_password ) as $id ) {
		related_qa_set_terms( $id, 'category', array( $cat_b ) );
		related_qa_set_terms( $id, 'post_tag', array( $tag_c ) );
	}
	// Upgrade one candidate's relevance; title overlap adds one point only.
	wp_update_post( array( 'ID' => $rank_high, 'post_title' => 'Fuente ranking core mejor' ) );
	$ranked = Related\related_candidate_for_post( $rank_source );
	related_qa_assert( $rank_high === (int) $ranked->ID, 'M: highest score wins over a newer lower-score candidate' );
	related_qa_assert( ! in_array( (int) $ranked->ID, array( $rank_draft, $rank_private, $rank_password ), true ), 'H/I/J: non-published and password-protected posts are excluded' );

	$tie_source = related_qa_post( 'Fuente desempate date ID' );
	$tie_older = related_qa_post( 'Destino desempate antiguo', 'publish', '2026-09-01 00:00:00' );
	$tie_newer = related_qa_post( 'Destino desempate reciente', 'publish', '2026-09-02 00:00:00' );
	foreach ( array( $tie_source, $tie_older, $tie_newer ) as $id ) {
		related_qa_set_terms( $id, 'category', array( $cat_c ) );
		related_qa_set_terms( $id, 'post_tag', array() );
	}
	$tie_selected = Related\related_candidate_for_post( $tie_source );
	related_qa_assert( $tie_selected instanceof WP_Post && $tie_newer === (int) $tie_selected->ID, 'N: equal-score candidate uses newest publication date' );

	$id_source = related_qa_post( 'Fuente desempate ID' );
	$id_low = related_qa_post( 'Destino mismo tiempo bajo ID', 'publish', '2026-09-03 00:00:00' );
	$id_high = related_qa_post( 'Destino mismo tiempo alto ID', 'publish', '2026-09-03 00:00:00' );
	foreach ( array( $id_source, $id_low, $id_high ) as $id ) {
		related_qa_set_terms( $id, 'category', array( $cat_d ) );
		related_qa_set_terms( $id, 'post_tag', array() );
	}
	$id_selected = Related\related_candidate_for_post( $id_source );
	related_qa_assert( $id_selected instanceof WP_Post && $id_high === (int) $id_selected->ID, 'O: equal-score/equal-date candidate uses highest post ID' );

	$self_source = related_qa_post( 'Fuente excluida actual' );
	related_qa_set_terms( $self_source, 'category', array( $cat_e ) );
	// Current posts are excluded by post__not_in; no other candidate has this term.
	$self_selected = Related\related_candidate_for_post( $self_source );
	related_qa_assert( false === $self_selected, 'K: current post is excluded from its own candidate query' );

	$bounded_source = related_qa_post( 'Fuente límite cincuenta' );
	related_qa_set_terms( $bounded_source, 'category', array( $cat_f ) );
	$bounded_candidate_ids = array();
	for ( $i = 0; $i < 51; ++$i ) {
		$id = related_qa_post( 'Candidato límite ' . $i, 'publish', sprintf( '2026-08-%02d 00:00:00', 1 + ( $i % 28 ) ) );
		$bounded_candidate_ids[] = $id;
		related_qa_set_terms( $id, 'category', array( $cat_f ) );
	}
	$captured_sql = '';
	$capture = static function ( $sql, $query ) use ( &$captured_sql ) {
		if ( isset( $query->query_vars['tax_query'] ) && isset( $query->query_vars['posts_per_page'] ) && 50 === (int) $query->query_vars['posts_per_page'] ) {
			$captured_sql = $sql;
		}
		return $sql;
	};
	add_filter( 'posts_request', $capture, 9999, 2 );
	$bounded_selected = Related\related_candidate_for_post( $bounded_source );
	remove_filter( 'posts_request', $capture, 9999 );
	related_qa_assert( '' !== $captured_sql && false !== strpos( strtoupper( $captured_sql ), 'LIMIT 0, 50' ), 'P: candidate SQL is bounded to 50 posts' );
	related_qa_assert( $bounded_selected instanceof WP_Post && in_array( (int) $bounded_selected->ID, $bounded_candidate_ids, true ), 'P: a candidate in the bounded candidate set is selected' );

	// Confirm repeated resolution is served from request-local memoization.
	$before_repeat = $GLOBALS['wpdb']->num_queries;
	Related\related_candidate_for_post( $bounded_source );
	$repeat_queries = $GLOBALS['wpdb']->num_queries - $before_repeat;
	related_qa_assert( 0 === $repeat_queries, 'Two Related instances for the same source reuse the request-local result' );
} finally {
	foreach ( array_reverse( $created_posts ) as $post_id ) {
		wp_delete_post( $post_id, true );
	}
	foreach ( array_reverse( $created_terms ) as $term_data ) {
		wp_delete_term( $term_data[0], $term_data[1] );
	}
}

echo 'FAILURES=' . count( $GLOBALS['failures'] ) . "\n";
exit( empty( $GLOBALS['failures'] ) ? 0 : 1 );

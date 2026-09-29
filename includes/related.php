<?php
/**
 * Automatic related-post selection for SCI — Relacionado.
 *
 * @package SCI\EditorialBlocks
 */

namespace SCI\EditorialBlocks;

defined( 'ABSPATH' ) || exit;

/**
 * Read unique term IDs for a post and taxonomy.
 *
 * @param int    $post_id Post ID.
 * @param string $taxonomy Taxonomy name.
 * @return int[]
 */
function related_term_ids( int $post_id, string $taxonomy ): array {
	$terms = get_the_terms( $post_id, $taxonomy );

	if ( ! is_array( $terms ) ) {
		return array();
	}

	$ids = array();
	foreach ( $terms as $term ) {
		if ( $term instanceof \WP_Term ) {
			$ids[] = (int) $term->term_id;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Split a title into deterministic Unicode letter/number tokens.
 *
 * @param string $title Title text.
 * @return string[]
 */
function related_title_tokens( string $title ): array {
	$text = html_entity_decode( wp_strip_all_tags( $title, true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$count = preg_match_all( '~[\p{L}\p{N}][\p{L}\p{M}\p{N}]*~u', $text, $matches );

	if ( false === $count || 0 === $count ) {
		return array();
	}

	return array_values( array_unique( $matches[0] ) );
}

/**
 * Return whether two token sets share at least one Unicode case-insensitive token.
 *
 * @param string[] $source_tokens Source title tokens.
 * @param string[] $candidate_tokens Candidate title tokens.
 * @return bool
 */
function related_titles_share_token( array $source_tokens, array $candidate_tokens ): bool {
	foreach ( $source_tokens as $source_token ) {
		$quoted = preg_quote( $source_token, '~' );
		foreach ( $candidate_tokens as $candidate_token ) {
			if ( 1 === preg_match( '~\A' . $quoted . '\z~iu', $candidate_token ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Calculate the approved score from shared direct categories, tags, and title tokens.
 *
 * @param int[]    $source_categories Source category IDs.
 * @param int[]    $source_tags Source tag IDs.
 * @param string[] $source_title_tokens Source title tokens.
 * @param int[]    $candidate_categories Candidate category IDs.
 * @param int[]    $candidate_tags Candidate tag IDs.
 * @param string[] $candidate_title_tokens Candidate title tokens.
 * @return int
 */
function related_score( array $source_categories, array $source_tags, array $source_title_tokens, array $candidate_categories, array $candidate_tags, array $candidate_title_tokens ): int {
	$shared_categories = array_intersect( $source_categories, $candidate_categories );
	$shared_tags       = array_intersect( $source_tags, $candidate_tags );
	$score             = ! empty( $shared_categories ) ? 3 : 0;
	$score            += min( 4, 2 * count( array_unique( $shared_tags ) ) );

	if ( related_titles_share_token( $source_title_tokens, $candidate_title_tokens ) ) {
		++$score;
	}

	return $score;
}

/**
 * Select and memoize the highest ranked eligible related post for a source post.
 *
 * @param int $source_post_id Current post ID.
 * @return \WP_Post|false
 */
function related_candidate_for_post( int $source_post_id ) {
	static $candidate_by_post = array();

	if ( $source_post_id < 1 ) {
		return false;
	}

	if ( array_key_exists( $source_post_id, $candidate_by_post ) ) {
		return $candidate_by_post[ $source_post_id ];
	}

	$source = get_post( $source_post_id );
	if ( ! $source instanceof \WP_Post || 'post' !== $source->post_type || 'publish' !== $source->post_status ) {
		$candidate_by_post[ $source_post_id ] = false;
		return false;
	}

	$source_categories = related_term_ids( $source_post_id, 'category' );
	$source_tags       = related_term_ids( $source_post_id, 'post_tag' );
	if ( empty( $source_categories ) && empty( $source_tags ) ) {
		$candidate_by_post[ $source_post_id ] = false;
		return false;
	}

	$tax_query = array( 'relation' => 'OR' );
	if ( ! empty( $source_categories ) ) {
		$tax_query[] = array(
			'taxonomy'         => 'category',
			'field'            => 'term_id',
			'terms'            => $source_categories,
			'include_children' => false,
		);
	}
	if ( ! empty( $source_tags ) ) {
		$tax_query[] = array(
			'taxonomy'         => 'post_tag',
			'field'            => 'term_id',
			'terms'            => $source_tags,
			'include_children' => false,
		);
	}

	$query = new \WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'has_password'           => false,
			'post__not_in'           => array( $source_post_id ),
			'posts_per_page'         => 50,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'orderby'                => array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			),
			'update_post_term_cache' => true,
			'update_post_meta_cache' => false,
			'tax_query'              => $tax_query,
		)
	);

	$source_title_tokens = related_title_tokens( get_the_title( $source ) );
	$ranked_candidates   = array();

	foreach ( $query->posts as $candidate ) {
		if ( ! $candidate instanceof \WP_Post
			|| (int) $candidate->ID === $source_post_id
			|| 'post' !== $candidate->post_type
			|| 'publish' !== $candidate->post_status
			|| '' !== $candidate->post_password
			|| ! is_post_publicly_viewable( $candidate )
		) {
			continue;
		}

		$permalink = get_permalink( $candidate );
		if ( ! is_string( $permalink ) || '' === $permalink ) {
			continue;
		}

		$candidate_categories = related_term_ids( (int) $candidate->ID, 'category' );
		$candidate_tags       = related_term_ids( (int) $candidate->ID, 'post_tag' );
		$score                = related_score(
			$source_categories,
			$source_tags,
			$source_title_tokens,
			$candidate_categories,
			$candidate_tags,
			related_title_tokens( get_the_title( $candidate ) )
		);

		if ( $score < 3 ) {
			continue;
		}

		$ranked_candidates[] = array(
			'post'  => $candidate,
			'score' => $score,
			'date'  => $candidate->post_date,
			'id'    => (int) $candidate->ID,
		);
	}

	usort(
		$ranked_candidates,
		static function ( array $left, array $right ): int {
			if ( $left['score'] !== $right['score'] ) {
				return $right['score'] <=> $left['score'];
			}

			if ( $left['date'] !== $right['date'] ) {
				return strcmp( $right['date'], $left['date'] );
			}

			return $right['id'] <=> $left['id'];
		}
	);

	$candidate_by_post[ $source_post_id ] = ! empty( $ranked_candidates )
		? $ranked_candidates[0]['post']
		: false;

	return $candidate_by_post[ $source_post_id ];
}

<?php
/**
 * Product search relevance + Banglish / Bangla synonyms.
 *
 * WordPress' default search matches title, excerpt AND content, so "helmet"
 * returned gloves, shoes and vests whose descriptions mention helmets. For the
 * storefront product search this module:
 *
 *  - matches only the product title, SKU (incl. variation SKUs) and product
 *    category / tag names;
 *  - maps how people in Bangladesh actually type ("jota", "জুতা", "chosma",
 *    "হেলমেট", "gumboot"…) to the words used in product titles;
 *  - ranks title matches first (whole phrase > every word > some words);
 *  - falls back to WordPress' normal search when the strict match finds
 *    nothing, so a query never dead-ends on an empty page.
 *
 * Filters: `safestore_search_synonyms`, `safestore_search_stopwords`,
 * `safestore_product_search_enabled`.
 *
 * @package safestore-minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Synonym groups: every alias (left) searches for all of the "match" terms.
 * Match terms are substrings (SQL LIKE %term%), so "boot" also finds "Gumboot".
 */
function safestore_search_synonyms() {
	$groups = array(
		array(
			'aliases' => array( 'jota', 'juta', 'joota', 'জুতা', 'জুতো', 'shoe', 'shoes', 'safety shoe', 'safety shoes', 'work shoe', 'work shoes' ),
			'match'   => array( 'shoe' ),
		),
		array(
			'aliases' => array( 'helmet', 'helmets', 'হেলমেট', 'hard hat', 'hardhat', 'hard hats', 'safety cap', 'topi', 'টুপি' ),
			'match'   => array( 'helmet', 'hard hat' ),
		),
		array(
			'aliases' => array( 'glove', 'gloves', 'গ্লাভস', 'গ্লাভ', 'hand gloves', 'hand glove', 'হ্যান্ড গ্লাভস' ),
			'match'   => array( 'glove' ),
		),
		array(
			'aliases' => array( 'chosma', 'choshma', 'chashma', 'chasma', 'চশমা', 'goggle', 'goggles', 'glasses', 'safety glass', 'safety glasses', 'spectacle', 'spectacles' ),
			'match'   => array( 'goggle', 'glass', 'spectacle' ),
		),
		array(
			'aliases' => array( 'gumboot', 'gum boot', 'gumboots', 'gamboot', 'boot', 'boots', 'বুট', 'গামবুট', 'rain boot' ),
			'match'   => array( 'boot' ),
		),
		array(
			'aliases' => array( 'vest', 'vests', 'জ্যাকেট', 'jacket', 'reflective jacket', 'reflective vest', 'hi vis', 'hi-vis', 'কটি' ),
			'match'   => array( 'vest', 'jacket' ),
		),
		array(
			'aliases' => array( 'mask', 'masks', 'মাস্ক', 'respirator' ),
			'match'   => array( 'mask', 'respirator' ),
		),
		array(
			'aliases' => array( 'harness', 'safety belt', 'হারনেস', 'full body harness' ),
			'match'   => array( 'harness', 'belt' ),
		),
	);
	return (array) apply_filters( 'safestore_search_synonyms', $groups );
}

/** Words that carry no product meaning in a store search. */
function safestore_search_stopwords() {
	return (array) apply_filters(
		'safestore_search_stopwords',
		array( 'price', 'prices', 'dam', 'দাম', 'in', 'bd', 'bangladesh', 'best', 'buy', 'online', 'for', 'the', 'and', 'of', 'a', 'an', 'with', 'er', 'এর', 'কিনুন', 'shop', 'store', 'cheap', 'low' )
	);
}

function safestore_search_lower( $s ) {
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $s, 'UTF-8' ) : strtolower( $s );
}

/**
 * Turn a query into AND-ed groups of OR-ed LIKE terms.
 *
 * "safety jota price in bd" → [ ['shoe'] ]
 * "3m helmet"               → [ ['3m'], ['helmet', 'hard hat'] ]
 *
 * @param string $raw Search string.
 * @return array[] List of term lists.
 */
function safestore_search_parse( $raw ) {
	$q = safestore_search_lower( trim( preg_replace( '/\s+/u', ' ', (string) $raw ) ) );
	if ( '' === $q ) {
		return array();
	}

	$groups   = array();
	$synonyms = safestore_search_synonyms();

	// Multi-word aliases first ("hard hat", "safety shoes"), longest first.
	$phrases = array();
	foreach ( $synonyms as $i => $group ) {
		foreach ( (array) $group['aliases'] as $alias ) {
			if ( false !== strpos( $alias, ' ' ) ) {
				$phrases[ safestore_search_lower( $alias ) ] = $i;
			}
		}
	}
	uksort(
		$phrases,
		static function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		}
	);
	foreach ( $phrases as $phrase => $i ) {
		if ( preg_match( '/(^|\s)' . preg_quote( $phrase, '/' ) . '(\s|$)/u', $q ) ) {
			$groups[ 'syn' . $i ] = $synonyms[ $i ]['match'];
			$q                    = trim( preg_replace( '/(^|\s)' . preg_quote( $phrase, '/' ) . '(\s|$)/u', ' ', $q ) );
		}
	}

	$alias_map = array();
	foreach ( $synonyms as $i => $group ) {
		foreach ( (array) $group['aliases'] as $alias ) {
			$alias_map[ safestore_search_lower( $alias ) ] = $i;
		}
	}

	$words     = preg_split( '/[\s,]+/u', $q, -1, PREG_SPLIT_NO_EMPTY );
	$stopwords = array_flip( safestore_search_stopwords() );
	$has_other = count( $groups ) > 0 || count( $words ) > 1;

	foreach ( $words as $word ) {
		if ( isset( $stopwords[ $word ] ) ) {
			continue;
		}
		// "safety" alone is a fine search; next to another word it is noise.
		if ( 'safety' === $word && $has_other ) {
			continue;
		}
		if ( isset( $alias_map[ $word ] ) ) {
			$i                    = $alias_map[ $word ];
			$groups[ 'syn' . $i ] = $synonyms[ $i ]['match'];
			continue;
		}
		// Light plural folding: helmets → helmet, gloves → glove (not "glass").
		if ( strlen( $word ) > 3 && preg_match( '/[^s]s$/', $word ) ) {
			$word = substr( $word, 0, -1 );
		}
		if ( strlen( $word ) < 2 && ! preg_match( '/\d/', $word ) ) {
			continue;
		}
		$groups[ 'w:' . $word ] = array( $word );
	}

	return array_values( $groups );
}

function safestore_product_search_applies( $query ) {
	if ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() || ! $query->is_search() ) {
		return false;
	}
	$pt = $query->get( 'post_type' );
	$is_product = ( 'product' === $pt ) || ( is_array( $pt ) && array( 'product' ) === array_values( $pt ) );
	return (bool) apply_filters( 'safestore_product_search_enabled', $is_product, $query );
}

/**
 * SQL condition for one group (any of its terms in title, SKU or category/tag).
 *
 * @param string[] $terms Terms.
 * @return string
 */
function safestore_search_group_sql( $terms ) {
	global $wpdb;
	$or = array();
	foreach ( $terms as $term ) {
		$like = '%' . $wpdb->esc_like( $term ) . '%';
		$or[] = $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", $like );
		if ( ! empty( $wpdb->wc_product_meta_lookup ) ) {
			$or[] = $wpdb->prepare(
				"{$wpdb->posts}.ID IN ( SELECT IF( sp.post_parent > 0, sp.post_parent, sp.ID ) FROM {$wpdb->wc_product_meta_lookup} sl INNER JOIN {$wpdb->posts} sp ON sp.ID = sl.product_id WHERE sl.sku LIKE %s )",
				$like
			);
		}
		$or[] = $wpdb->prepare(
			"{$wpdb->posts}.ID IN ( SELECT str.object_id FROM {$wpdb->term_relationships} str INNER JOIN {$wpdb->term_taxonomy} stt ON stt.term_taxonomy_id = str.term_taxonomy_id INNER JOIN {$wpdb->terms} st ON st.term_id = stt.term_id WHERE stt.taxonomy IN ( 'product_cat', 'product_tag' ) AND st.name LIKE %s )",
			$like
		);
	}
	return '( ' . implode( ' OR ', $or ) . ' )';
}

// Decide once per request whether the strict search has results.
add_action(
	'pre_get_posts',
	static function ( $query ) {
		if ( ! safestore_product_search_applies( $query ) ) {
			return;
		}
		$groups = safestore_search_parse( (string) $query->get( 's' ) );
		if ( ! $groups ) {
			return;
		}

		global $wpdb;
		$where = implode( ' AND ', array_map( 'safestore_search_group_sql', $groups ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- every piece of $where is prepared above.
		$hits = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE {$wpdb->posts}.post_type = 'product' AND {$wpdb->posts}.post_status = 'publish' AND {$where}" );
		if ( $hits > 0 ) {
			$query->set( 'sft_search_groups', $groups );
		}
	},
	20
);

add_filter(
	'posts_search',
	static function ( $search, $query ) {
		$groups = $query instanceof WP_Query ? $query->get( 'sft_search_groups' ) : null;
		if ( empty( $groups ) || ! is_array( $groups ) ) {
			return $search;
		}
		return ' AND ' . implode( ' AND ', array_map( 'safestore_search_group_sql', $groups ) ) . ' ';
	},
	20,
	2
);

// Title matches first: whole phrase, then all words in the title, then some.
add_filter(
	'posts_search_orderby',
	static function ( $orderby, $query ) {
		$groups = $query instanceof WP_Query ? $query->get( 'sft_search_groups' ) : null;
		if ( empty( $groups ) || ! is_array( $groups ) ) {
			return $orderby;
		}
		// Respect an explicit sort (price, date, popularity…).
		$requested = $query->get( 'orderby' );
		if ( ! empty( $requested ) && 'relevance' !== $requested ) {
			return $orderby;
		}
		global $wpdb;

		$phrase = trim( (string) $query->get( 's' ) );
		$parts  = array();
		if ( '' !== $phrase ) {
			$parts[] = $wpdb->prepare( "( {$wpdb->posts}.post_title LIKE %s ) * 4", '%' . $wpdb->esc_like( $phrase ) . '%' );
		}
		foreach ( $groups as $terms ) {
			$or = array();
			foreach ( $terms as $term ) {
				$or[] = $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like( $term ) . '%' );
			}
			$parts[] = '( ' . implode( ' OR ', $or ) . ' )';
		}
		return '( ' . implode( ' + ', $parts ) . " ) DESC, {$wpdb->posts}.menu_order ASC, {$wpdb->posts}.post_date DESC";
	},
	20,
	2
);

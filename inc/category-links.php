<?php
/**
 * Product category links.
 *
 * The homepage templates used to hardcode `/product-category/<slug>/` URLs.
 * The real WooCommerce slugs are singular (safety-shoe, protective-helmet …)
 * while the templates carried plural ones, so every one of those links 404'd —
 * WordPress's fuzzy URL guessing masked it for some of them, which is why the
 * breakage was easy to miss.
 *
 * Resolving the term and asking WordPress for its permalink removes the whole
 * class of bug: rename a category in the admin and these links follow it.
 *
 * @package safestore-minimal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Permalink for a product category, resolved from the term.
 *
 * @param string|string[] $slug     One slug, or candidates tried in order
 *                                  (lets a link survive a singular/plural rename).
 * @param string          $fallback URL to use when no candidate exists.
 *                                  Defaults to the shop page, so a missing
 *                                  category degrades to a useful destination
 *                                  rather than a dead link.
 * @return string
 */
function safestore_category_url( $slug, $fallback = '' ) {
	if ( taxonomy_exists( 'product_cat' ) ) {
		foreach ( (array) $slug as $candidate ) {
			$candidate = sanitize_title( (string) $candidate );
			if ( '' === $candidate ) {
				continue;
			}

			$term = get_term_by( 'slug', $candidate, 'product_cat' );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}

			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				return $link;
			}
		}
	}

	if ( '' !== $fallback ) {
		return $fallback;
	}

	return function_exists( 'wc_get_page_permalink' )
		? wc_get_page_permalink( 'shop' )
		: home_url( '/' );
}

/**
 * The category slugs the homepage links to.
 *
 * Singular is the live slug; the plural is kept as a fallback so the link
 * keeps working if a category is ever renamed back.
 *
 * @param string $key helmet|vest|glove|goggle|shoe.
 * @return string
 */
function safestore_home_category_url( $key ) {
	$map = array(
		'helmet' => array( 'protective-helmet', 'protective-helmets' ),
		'vest'   => array( 'safety-vest', 'safety-vests' ),
		'glove'  => array( 'safety-glove', 'safety-gloves' ),
		'goggle' => array( 'safety-goggle', 'safety-goggles' ),
		'shoe'   => array( 'safety-shoe', 'safety-shoes' ),
	);

	$candidates = isset( $map[ $key ] ) ? $map[ $key ] : array( $key );

	return safestore_category_url( apply_filters( 'safestore_home_category_slugs', $candidates, $key ) );
}

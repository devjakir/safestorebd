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
 * The live slug of a product category, resolved from a candidate list.
 *
 * Use this anywhere a slug string is needed rather than a URL — a tax_query,
 * a get_term_by() lookup, a cache key. Same contract as
 * safestore_category_url(): the caller names the category, not its spelling.
 *
 * @param string|string[] $slug     One slug, or candidates tried in order.
 * @param string          $fallback Returned when none of them exists.
 *                                  Defaults to the first candidate, so the
 *                                  return value is always a usable slug.
 * @return string
 */
function safestore_category_slug( $slug, $fallback = '' ) {
	$candidates = array();
	foreach ( (array) $slug as $candidate ) {
		$candidate = sanitize_title( (string) $candidate );
		if ( '' !== $candidate ) {
			$candidates[] = $candidate;
		}
	}

	if ( taxonomy_exists( 'product_cat' ) ) {
		foreach ( $candidates as $candidate ) {
			$term = get_term_by( 'slug', $candidate, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				return $candidate;
			}
		}
	}

	if ( '' !== $fallback ) {
		return $fallback;
	}

	return isset( $candidates[0] ) ? $candidates[0] : '';
}

/**
 * Candidate slugs for a homepage category.
 *
 * Singular is the live slug; the plural is kept as a fallback so a link
 * keeps working if a category is ever renamed back. Single source of truth
 * for both safestore_home_category_url() and safestore_home_category_slug().
 *
 * @param string $key helmet|vest|glove|goggle|shoe.
 * @return string[]
 */
function safestore_home_category_slug_candidates( $key ) {
	$map = array(
		'helmet' => array( 'protective-helmet', 'protective-helmets' ),
		'vest'   => array( 'safety-vest', 'safety-vests' ),
		'glove'  => array( 'safety-glove', 'safety-gloves' ),
		'goggle' => array( 'safety-goggle', 'safety-goggles' ),
		'shoe'   => array( 'safety-shoe', 'safety-shoes' ),
	);

	$candidates = isset( $map[ $key ] ) ? $map[ $key ] : array( $key );

	return (array) apply_filters( 'safestore_home_category_slugs', $candidates, $key );
}

/**
 * Permalink for a homepage category.
 *
 * @param string $key helmet|vest|glove|goggle|shoe.
 * @return string
 */
function safestore_home_category_url( $key ) {
	return safestore_category_url( safestore_home_category_slug_candidates( $key ) );
}

/**
 * Live slug for a homepage category.
 *
 * Needed by anything that queries the term rather than linking to it — the
 * hero overlay cards look up the category by slug to pull a real product's
 * price and rating, and silently render nothing when the slug is stale.
 *
 * @param string $key helmet|vest|glove|goggle|shoe.
 * @return string
 */
function safestore_home_category_slug( $key ) {
	return safestore_category_slug( safestore_home_category_slug_candidates( $key ) );
}

/**
 * 301 a product-category URL whose slug was renamed to the surviving term.
 *
 * Renaming the categories (safety-shoes -> safety-shoe) turned every archive
 * URL already out in the world into a hard 404: Google's index, old campaign
 * links, and links written into product descriptions. WordPress's fuzzy URL
 * guessing does not cover this case — /product-category/safety-shoes/ returns
 * a plain 404 rather than redirecting — so the hop has to be explicit.
 *
 * Runs only on a request that has already 404'd, and only redirects once the
 * target term is confirmed to exist, so it can never send a visitor to
 * another dead end or shadow a legitimate URL.
 */
function safestore_redirect_renamed_category_urls() {
	if ( is_admin() || ! is_404() || ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}

	$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$path    = (string) wp_parse_url( (string) $request, PHP_URL_PATH );
	if ( '' === $path ) {
		return;
	}

	// Respect a custom category base (WooCommerce -> Permalinks).
	$base       = 'product-category';
	$permalinks = get_option( 'woocommerce_permalinks' );
	if ( is_array( $permalinks ) && ! empty( $permalinks['category_base'] ) ) {
		$base = trim( (string) $permalinks['category_base'], '/' );
	}

	$segments = array_values( array_filter( explode( '/', $path ), 'strlen' ) );
	$index    = array_search( $base, $segments, true );
	if ( false === $index || ! isset( $segments[ $index + 1 ] ) ) {
		return;
	}

	$requested = sanitize_title( $segments[ $index + 1 ] );
	if ( '' === $requested || get_term_by( 'slug', $requested, 'product_cat' ) ) {
		return; // Slug is fine; something else caused the 404.
	}

	// Only singular/plural drift is guessed at — anything else is a real 404.
	$alternates = 's' === substr( $requested, -1 )
		? array( substr( $requested, 0, -1 ) )
		: array( $requested . 's' );

	/**
	 * Filter the slugs tried when a category URL 404s.
	 *
	 * @param string[] $alternates Candidate slugs.
	 * @param string   $requested  The slug that was asked for.
	 */
	$alternates = (array) apply_filters( 'safestore_renamed_category_alternates', $alternates, $requested );

	foreach ( $alternates as $alternate ) {
		$alternate = sanitize_title( (string) $alternate );
		if ( '' === $alternate ) {
			continue;
		}

		$term = get_term_by( 'slug', $alternate, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}

		$target = get_term_link( $term );
		if ( is_wp_error( $target ) ) {
			continue;
		}

		// Keep anything below the category (child term, /page/2/, …).
		$tail = array_slice( $segments, $index + 2 );
		if ( $tail ) {
			$target = trailingslashit( $target ) . implode( '/', array_map( 'sanitize_title', $tail ) ) . '/';
		}

		$query = (string) wp_parse_url( (string) $request, PHP_URL_QUERY );
		if ( '' !== $query ) {
			$target .= ( false === strpos( $target, '?' ) ? '?' : '&' ) . $query;
		}

		wp_safe_redirect( $target, 301 );
		exit;
	}
}
add_action( 'template_redirect', 'safestore_redirect_renamed_category_urls' );

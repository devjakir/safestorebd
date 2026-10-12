<?php

defined( 'ABSPATH' ) || exit;

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

function safestore_home_category_url( $key ) {
	return safestore_category_url( safestore_home_category_slug_candidates( $key ) );
}

function safestore_home_category_slug( $key ) {
	return safestore_category_slug( safestore_home_category_slug_candidates( $key ) );
}

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

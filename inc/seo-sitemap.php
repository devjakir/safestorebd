<?php
/**
 * SafeStoreBD — sitemap / crawl hygiene helpers.
 *
 * Complements Rank Math: keep WooCommerce utility pages out of the XML
 * sitemap, reinforce noindex, and stop edge caches from serving stale
 * sitemap responses after product/content updates.
 *
 * @package safestore-minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce page IDs that should never be indexed or listed in XML sitemaps.
 *
 * @return int[]
 */
function safestore_seo_utility_page_ids() {
	$ids = array();
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return $ids;
	}

	foreach ( array( 'cart', 'checkout', 'myaccount' ) as $page ) {
		$id = (int) wc_get_page_id( $page );
		if ( $id > 0 ) {
			$ids[] = $id;
		}
	}

	/**
	 * Filter utility page IDs excluded from sitemaps / indexing.
	 *
	 * @param int[] $ids Page IDs.
	 */
	return array_values( array_unique( array_filter( array_map( 'intval', apply_filters( 'safestore_seo_utility_page_ids', $ids ) ) ) ) );
}

/**
 * Rank Math: merge utility pages into posts excluded from the XML sitemap.
 *
 * @param int[] $exclude Existing excluded IDs.
 * @return int[]
 */
function safestore_seo_rank_math_posts_to_exclude( $exclude ) {
	if ( ! is_array( $exclude ) ) {
		$exclude = array();
	}
	return array_values( array_unique( array_merge( array_map( 'intval', $exclude ), safestore_seo_utility_page_ids() ) ) );
}
add_filter( 'rank_math/sitemap/posts_to_exclude', 'safestore_seo_rank_math_posts_to_exclude' );
add_filter( 'rank_math/sitemap/exclude_post_ids', 'safestore_seo_rank_math_posts_to_exclude' );

/**
 * Fallback exclusion if an older Rank Math build ignores exclude_post_ids.
 *
 * @param array|false $url    Sitemap URL entry.
 * @param string      $type   Entry type.
 * @param object      $object Object being added.
 * @return array|false
 */
function safestore_seo_rank_math_sitemap_entry( $url, $type, $object ) {
	if ( false === $url || 'post' !== $type || empty( $object->ID ) ) {
		return $url;
	}
	if ( in_array( (int) $object->ID, safestore_seo_utility_page_ids(), true ) ) {
		return false;
	}
	return $url;
}
add_filter( 'rank_math/sitemap/entry', 'safestore_seo_rank_math_sitemap_entry', 10, 3 );

/**
 * Reinforce noindex on cart / checkout / account (and Rank Math robots array).
 *
 * @param array $robots Robots directives.
 * @return array
 */
function safestore_seo_rank_math_robots( $robots ) {
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		$robots['index']  = 'noindex';
		$robots['follow'] = 'follow';
	}
	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'safestore_seo_rank_math_robots' );

/**
 * Core wp_robots fallback when Rank Math is inactive.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function safestore_seo_wp_robots( $robots ) {
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		$robots['noindex'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'safestore_seo_wp_robots' );

/**
 * Send no-cache headers for Rank Math / WP sitemap responses.
 */
function safestore_seo_sitemap_nocache_headers() {
	if ( is_admin() || headers_sent() ) {
		return;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	if ( ! preg_match( '#/(sitemap_index\.xml|[a-z0-9_-]+-sitemap([0-9]+)?\.xml|wp-sitemap(-[a-z0-9_-]+)?\.xml)/?(\?|$)#i', $request_uri ) ) {
		return;
	}

	nocache_headers();
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true );
	header( 'Pragma: no-cache', true );
	header( 'X-LiteSpeed-Cache-Control: no-cache', true );
}
add_action( 'template_redirect', 'safestore_seo_sitemap_nocache_headers', 0 );

/**
 * Purge common Hostinger / LiteSpeed / Rank Math caches when catalog content changes.
 *
 * @param int $post_id Post ID.
 */
function safestore_seo_purge_caches_on_content_change( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}

	$post_type = get_post_type( $post_id );
	if ( ! in_array( $post_type, array( 'product', 'page', 'post', 'product_variation' ), true ) ) {
		return;
	}

	if ( function_exists( 'rank_math_clear_cache' ) ) {
		rank_math_clear_cache();
	} elseif ( class_exists( '\\RankMath\\Helper' ) && method_exists( '\\RankMath\\Helper', 'clear_cache' ) ) {
		\RankMath\Helper::clear_cache();
	}

	do_action( 'litespeed_purge_all' );
	do_action( 'litespeed_purge_url', home_url( '/sitemap_index.xml' ) );
	do_action( 'litespeed_purge_url', home_url( '/product-sitemap.xml' ) );
	do_action( 'litespeed_purge_url', home_url( '/page-sitemap.xml' ) );

	if ( function_exists( 'wp_cache_flush' ) ) {
		wp_cache_flush();
	}
}
add_action( 'save_post_product', 'safestore_seo_purge_caches_on_content_change', 20 );
add_action( 'save_post_page', 'safestore_seo_purge_caches_on_content_change', 20 );
add_action( 'woocommerce_update_product', 'safestore_seo_purge_caches_on_content_change', 20 );
add_action( 'woocommerce_product_set_stock', function ( $product ) {
	if ( is_object( $product ) && method_exists( $product, 'get_id' ) ) {
		safestore_seo_purge_caches_on_content_change( $product->get_id() );
	}
}, 20 );
add_action( 'woocommerce_variation_set_stock', function ( $product ) {
	if ( is_object( $product ) && method_exists( $product, 'get_id' ) ) {
		safestore_seo_purge_caches_on_content_change( $product->get_id() );
	}
}, 20 );

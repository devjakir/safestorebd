<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hello instanceof WP_Post ) {
		$ids[] = (int) $hello->ID;
	}

	$compare = get_page_by_path( 'compare' );
	if ( $compare instanceof WP_Post ) {
		$ids[] = (int) $compare->ID;
	}

	return array_values( array_unique( array_filter( array_map( 'intval', apply_filters( 'safestore_seo_utility_page_ids', $ids ) ) ) ) );
}

function safestore_seo_rank_math_posts_to_exclude( $exclude ) {
	if ( ! is_array( $exclude ) ) {
		$exclude = array();
	}
	return array_values( array_unique( array_merge( array_map( 'intval', $exclude ), safestore_seo_utility_page_ids() ) ) );
}
add_filter( 'rank_math/sitemap/posts_to_exclude', 'safestore_seo_rank_math_posts_to_exclude' );
add_filter( 'rank_math/sitemap/exclude_post_ids', 'safestore_seo_rank_math_posts_to_exclude' );

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

function safestore_seo_should_noindex_request() {
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		return true;
	}
	if ( function_exists( 'is_search' ) && is_search() ) {
		return true;
	}
	if ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
		return true;
	}
	if ( is_page( 'compare' ) ) {
		return true;
	}
	if ( is_singular( 'post' ) ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		if ( 'hello-world' === $slug ) {
			return true;
		}
	}
	return false;
}

function safestore_seo_rank_math_robots( $robots ) {
	if ( safestore_seo_should_noindex_request() ) {
		$robots['index']  = 'noindex';
		$robots['follow'] = 'follow';
	}
	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'safestore_seo_rank_math_robots' );

function safestore_seo_wp_robots( $robots ) {
	if ( safestore_seo_should_noindex_request() ) {
		$robots['noindex'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'safestore_seo_wp_robots' );

function safestore_seo_is_junk_items_request() {
	$qs = isset( $_SERVER['QUERY_STRING'] ) ? (string) wp_unslash( $_SERVER['QUERY_STRING'] ) : '';
	if ( '' === $qs ) {
		return false;
	}

	// Decode twice so single- and double-encoded slashes (%2F, %252F) both surface.
	$decoded = rawurldecode( rawurldecode( $qs ) );

	// Original campaign prefix — kept explicit for clarity in logs/greps.
	if ( preg_match( '#(?:^|&)items/#i', $decoded ) ) {
		return true;
	}

	// Generic junk: a slash anywhere in the query string with no "=" at all.
	if ( false === strpos( $decoded, '=' ) && false !== strpos( $decoded, '/' ) ) {
		return true;
	}

	// "?cate-4", "?cate-1-108" style spam (GSC Oct 2026): a bare word-number
	// key with no "=" and no slash. Real WP/Woo query args always carry "=".
	if ( false === strpos( $decoded, '=' ) && preg_match( '#^[a-z]{2,20}(?:-\d+)+/?$#i', $decoded ) ) {
		return true;
	}

	return false;
}

/**
 * Path-style crawl spam, e.g. /shop/pg/58602333-4 (GSC 404 + 5xx reports).
 * These never existed on the site; answer 410 so Google drops them faster.
 */
function safestore_seo_is_junk_path_request() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '' !== $base && '/' !== $base && 0 === strpos( $path, $base ) ) {
		$path = '/' . ltrim( substr( $path, strlen( $base ) ), '/' );
	}

	return (bool) preg_match( '#^/shop/pg/\d+-\d+/?$#i', $path );
}

function safestore_seo_disable_canonical_for_junk_items( $redirect_url ) {
	if ( safestore_seo_is_junk_items_request() ) {
		return false;
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', 'safestore_seo_disable_canonical_for_junk_items', 0 );

function safestore_seo_gone_junk_item_urls() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( ! safestore_seo_is_junk_items_request() && ! safestore_seo_is_junk_path_request() ) {
		return;
	}

	status_header( 410 );
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow', true );
	header( 'Content-Type: text/plain; charset=UTF-8', true );
	echo 'Gone';
	exit;
}
add_action( 'init', 'safestore_seo_gone_junk_item_urls', 0 );

function safestore_seo_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}

	// Robots.txt Disallow rules for /*?items/ removed (2026-08-20).
	// Blocking crawl prevented Googlebot from seeing the 410 responses,
	// so the spam URLs could never drop out of the index (GSC validation
	// kept failing). The 410 handler above is the real fix - crawling of
	// these URLs must remain allowed until they fall out of the index.

	return $output;
}
add_filter( 'robots_txt', 'safestore_seo_robots_txt', 99, 2 );

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

function safestore_seo_home_structured_data() {
	if ( is_admin() || ! ( is_front_page() || is_page_template( 'page-home.php' ) ) ) {
		return;
	}

	if ( defined( 'RANK_MATH_VERSION' ) && ! apply_filters( 'safestore_seo_force_home_schema', false ) ) {
		// Rank Math typically owns Organization / WebSite when configured.
		return;
	}

	$site_name = wp_strip_all_tags( get_bloginfo( 'name' ) );
	$site_url  = home_url( '/' );
	$logo      = get_template_directory_uri() . '/assets/images/logo/safe-store-bd.webp';
	if ( ! file_exists( get_template_directory() . '/assets/images/logo/safe-store-bd.webp' ) ) {
		$logo = get_template_directory_uri() . '/assets/images/logo/safe-store-bd.png';
	}

	$graph = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			array(
				'@type' => 'Organization',
				'@id'   => trailingslashit( $site_url ) . '#organization',
				'name'  => $site_name,
				'url'   => $site_url,
				'logo'  => array(
					'@type' => 'ImageObject',
					'url'   => $logo,
				),
				'sameAs' => array(
					'https://www.facebook.com/safestorebd',
					'https://www.instagram.com/safestorebd/',
					'https://www.linkedin.com/in/safestorebd/',
					'https://www.youtube.com/@SafeStoreBD',
				),
			),
			array(
				'@type'           => 'WebSite',
				'@id'             => trailingslashit( $site_url ) . '#website',
				'url'             => $site_url,
				'name'            => $site_name,
				'publisher'       => array( '@id' => trailingslashit( $site_url ) . '#organization' ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => home_url( '/?s={search_term_string}&post_type=product' ),
					),
					'query-input' => 'required name=search_term_string',
				),
			),
		),
	);

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}
add_action( 'wp_head', 'safestore_seo_home_structured_data', 20 );

/**
 * Archive/term/comment feeds (/product-category/x/feed/, /brand/x/feed/ …)
 * only burn crawl budget on a shop ("Crawled - currently not indexed" in GSC).
 * Stop advertising them and 301 each one to the page it belongs to.
 * The main site feed (/feed/) is left alone.
 */
remove_action( 'wp_head', 'feed_links_extra', 3 );

function safestore_seo_redirect_secondary_feeds() {
	if ( ! is_feed() || is_admin() ) {
		return;
	}
	if ( ! ( is_archive() || is_singular() || is_comment_feed() || is_search() ) ) {
		return;
	}

	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$clean = preg_replace( '#/feed(?:/(?:rss2?|atom|rdf))?/?$#i', '/', $path );
	if ( null === $clean || $clean === $path ) {
		return;
	}

	$home   = wp_parse_url( home_url( '/' ) );
	$origin = ( isset( $home['scheme'] ) ? $home['scheme'] : 'https' ) . '://' . $home['host'] . ( isset( $home['port'] ) ? ':' . $home['port'] : '' );

	wp_safe_redirect( $origin . $clean, 301, 'SafeStoreBD SEO' );
	exit;
}
add_action( 'template_redirect', 'safestore_seo_redirect_secondary_feeds', 1 );

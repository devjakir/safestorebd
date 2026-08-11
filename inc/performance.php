<?php
/**
 * SafeStoreBD — front-end performance helpers.
 *
 * Focus: mobile Core Web Vitals (LCP, FCP, CLS, TBT) without changing
 * branding, URLs, or storefront functionality.
 *
 * @package safestore-minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Strip emoji detection scripts/styles (unused on this theme).
 */
function safestore_perf_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'safestore_perf_disable_emojis' );

/**
 * Drop unused front-end chrome that adds request/parse cost.
 */
function safestore_perf_clean_head() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
}
add_action( 'after_setup_theme', 'safestore_perf_clean_head' );

/**
 * Hide Dashicons for anonymous visitors (admins still need them).
 */
function safestore_perf_dequeue_dashicons() {
	if ( is_admin() || is_user_logged_in() ) {
		return;
	}
	wp_deregister_style( 'dashicons' );
}
add_action( 'wp_enqueue_scripts', 'safestore_perf_dequeue_dashicons', 100 );

/**
 * Dequeue WooCommerce *styles* on templates that never render shop UI.
 *
 * Cart-fragment scripts stay loaded so the header badge can sync.
 * Home, shop, product, cart, checkout, and account keep shop CSS.
 */
function safestore_perf_maybe_dequeue_woocommerce_styles() {
	if ( is_admin() || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$needs_wc_styles = is_woocommerce()
		|| is_cart()
		|| is_checkout()
		|| is_account_page()
		|| is_page_template( 'page-home.php' );

	/**
	 * Force-keep WooCommerce styles on specific requests.
	 *
	 * @param bool $needs_wc_styles Whether WC styles should stay enqueued.
	 */
	$needs_wc_styles = (bool) apply_filters( 'safestore_perf_needs_woocommerce_styles', $needs_wc_styles );

	if ( $needs_wc_styles ) {
		return;
	}

	$style_handles = array(
		'woocommerce-general',
		'woocommerce-layout',
		'woocommerce-smallscreen',
		'woocommerce-blocktheme',
		'wc-blocks-style',
		'wc-blocks-vendors-style',
	);
	foreach ( $style_handles as $handle ) {
		wp_dequeue_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'safestore_perf_maybe_dequeue_woocommerce_styles', 99 );

/**
 * Theme script handles that are safe to defer (no document.write / sync deps).
 *
 * @return string[]
 */
function safestore_perf_defer_script_handles() {
	return array(
		'safestore-minimal-header-search-cat',
		'safestore-minimal-hero-slider',
		'safestore-minimal-shop-cards',
		'safestore-minimal-pdp-gallery',
		'safestore-copy-to-clipboard',
		'safestore-whatsapp-chat',
		'safestore-cart-toast',
		'safestore-footwear-sizing',
		'safestore-pdp-shoe-size',
		'safestore-pdp-actions',
	);
}

/**
 * Mark theme scripts as deferred to cut TBT / improve FCP.
 *
 * @param string $tag    Script tag HTML.
 * @param string $handle Script handle.
 * @param string $src    Script URL.
 * @return string
 */
function safestore_perf_script_loader_tag( $tag, $handle, $src ) {
	unset( $src );

	if ( is_admin() ) {
		return $tag;
	}

	if ( ! in_array( $handle, safestore_perf_defer_script_handles(), true ) ) {
		return $tag;
	}

	if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, " strategy='defer'" ) || false !== strpos( $tag, 'strategy="defer"' ) ) {
		return $tag;
	}

	return str_replace( ' src', ' defer src', $tag );
}
add_filter( 'script_loader_tag', 'safestore_perf_script_loader_tag', 10, 3 );

/**
 * Prefer WP 6.3+ defer strategy when available (cleaner than tag filtering).
 *
 * @param string|array|bool $args Enqueue args / in_footer flag.
 * @return array
 */
function safestore_perf_script_args( $args = true ) {
	if ( ! is_array( $args ) ) {
		$args = array( 'in_footer' => (bool) $args );
	}
	if ( version_compare( get_bloginfo( 'version' ), '6.3', '>=' ) ) {
		$args['strategy'] = 'defer';
	}
	return $args;
}

/**
 * Preload the LCP candidate: hero WebP on home, header logo elsewhere.
 */
function safestore_perf_preload_lcp() {
	if ( is_admin() ) {
		return;
	}

	if ( is_page_template( 'page-home.php' ) || is_front_page() ) {
		$hero = get_template_directory_uri() . '/assets/images/' . rawurlencode( 'sf-helmet-category.webp' );
		printf(
			'<link rel="preload" as="image" href="%s" type="image/webp" fetchpriority="high" />' . "\n",
			esc_url( $hero )
		);
		return;
	}

	$logo = get_template_directory_uri() . '/assets/images/logo/safe-store-bd.png';
	printf(
		'<link rel="preload" as="image" href="%s" />' . "\n",
		esc_url( $logo )
	);
}
add_action( 'wp_head', 'safestore_perf_preload_lcp', 2 );

/**
 * Ensure product loop / thumbnail images carry CLS-safe attrs on mobile.
 *
 * @param array        $attr       Image attributes.
 * @param WP_Post      $attachment Attachment post.
 * @param string|int[] $size       Image size.
 * @return array
 */
function safestore_perf_attachment_image_attributes( $attr, $attachment, $size ) {
	unset( $attachment, $size );

	if ( empty( $attr['decoding'] ) ) {
		$attr['decoding'] = 'async';
	}

	// First product image on a PDP can stay eager; loops should lazy-load.
	if ( empty( $attr['loading'] ) ) {
		if ( function_exists( 'is_product' ) && is_product() && empty( $GLOBALS['safestore_perf_pdp_main_image_done'] ) ) {
			$attr['loading']       = 'eager';
			$attr['fetchpriority'] = 'high';
			$GLOBALS['safestore_perf_pdp_main_image_done'] = true;
		} else {
			$attr['loading'] = 'lazy';
		}
	}

	if ( empty( $attr['sizes'] ) && ! empty( $attr['srcset'] ) ) {
		// Two-column shop cards on phones ≈ 50vw; single column under 480px handled by CSS.
		$attr['sizes'] = '(max-width: 480px) 92vw, (max-width: 820px) 46vw, (max-width: 1080px) 30vw, 280px';
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'safestore_perf_attachment_image_attributes', 20, 3 );

/**
 * Soften WooCommerce cart-fragment polling pressure on non-cart pages.
 *
 * Keeps fragments available (header badge sync + toast) but stops the
 * constant cart.js heartbeat that hurts TBT on mobile.
 *
 * @param array|false $params Cart fragment params.
 * @return array|false
 */
function safestore_perf_cart_fragment_params( $params ) {
	if ( ! is_array( $params ) ) {
		return $params;
	}
	// Default WC uses a short refresh; stretching it reduces background work.
	$params['request_timeout'] = isset( $params['request_timeout'] ) ? max( (int) $params['request_timeout'], 5000 ) : 5000;
	return $params;
}
add_filter( 'woocommerce_get_script_data', function ( $params, $handle ) {
	if ( 'wc-cart-fragments' === $handle && is_array( $params ) ) {
		return safestore_perf_cart_fragment_params( $params );
	}
	return $params;
}, 20, 2 );

/**
 * Reduce motion cost for users who prefer it (also helps INP on low-end phones).
 */
function safestore_perf_reduced_motion_hint() {
	echo "<style id=\"sft-perf-reduced-motion\">@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important;scroll-behavior:auto!important}}</style>\n";
}
add_action( 'wp_head', 'safestore_perf_reduced_motion_hint', 1 );

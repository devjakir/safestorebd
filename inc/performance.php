<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

function safestore_perf_clean_head() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
}
add_action( 'after_setup_theme', 'safestore_perf_clean_head' );

function safestore_perf_dequeue_dashicons() {
	if ( is_admin() || is_user_logged_in() ) {
		return;
	}
	wp_deregister_style( 'dashicons' );
}
add_action( 'wp_enqueue_scripts', 'safestore_perf_dequeue_dashicons', 100 );

function safestore_perf_maybe_dequeue_woocommerce_styles() {
	if ( is_admin() || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$needs_wc_styles = is_woocommerce()
		|| is_cart()
		|| is_checkout()
		|| is_account_page()
		|| is_page_template( 'page-home.php' );

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

function safestore_perf_async_style_handles() {
	$handles = array(
		'safestore-whatsapp-chat',
		'safestore-cart-toast',
		'safestore-copy-to-clipboard',
		'safestore-product-compare',
		'wc-blocks-style',
		'wc-blocks-vendors-style',
		'woocommerce-blocktheme',
	);

	$needs_sync_wc = class_exists( 'WooCommerce' )
		&& ( is_cart() || is_checkout() || is_account_page() );

	if ( ! $needs_sync_wc ) {
		$handles = array_merge(
			$handles,
			array(
				'woocommerce-general',
				'woocommerce-layout',
				'woocommerce-smallscreen',
			)
		);
	}

	return array_values( array_unique( (array) apply_filters( 'safestore_perf_async_style_handles', $handles ) ) );
}

function safestore_perf_style_loader_tag( $html, $handle, $href, $media ) {
	unset( $media );

	if ( is_admin() || is_feed() ) {
		return $html;
	}

	if ( ! in_array( $handle, safestore_perf_async_style_handles(), true ) ) {
		return $html;
	}

	if ( false !== strpos( $html, 'onload=' ) ) {
		return $html;
	}

	$async = preg_replace(
		'/\smedia=(["\'])[^"\']*\1/i',
		' media="print" onload="this.media=\'all\'"',
		$html,
		1
	);

	if ( ! is_string( $async ) || $async === $html ) {
		$async = str_replace(
			"rel='stylesheet'",
			"rel='stylesheet' media=\"print\" onload=\"this.media='all'\"",
			$html
		);
		if ( $async === $html ) {
			$async = str_replace(
				'rel="stylesheet"',
				'rel="stylesheet" media="print" onload="this.media=\'all\'"',
				$html
			);
		}
	}

	$noscript = sprintf(
		'<noscript><link rel="stylesheet" href="%s" /></noscript>' . "\n",
		esc_url( $href )
	);

	return $async . $noscript;
}
add_filter( 'style_loader_tag', 'safestore_perf_style_loader_tag', 10, 4 );

function safestore_perf_jquery_loading() {
	if ( is_admin() ) {
		return;
	}

	$scripts = wp_scripts();
	if ( isset( $scripts->registered['jquery'] ) ) {
		$scripts->registered['jquery']->deps = array_values(
			array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) )
		);
	}

	wp_dequeue_script( 'jquery-migrate' );
	wp_deregister_script( 'jquery-migrate' );

	// Filter pages: HUSKY's ~20 scripts are jQuery plugins printed blocking. They
	// cannot simply be deferred — front.js binds on jQuery ready, which can fire
	// between deferred scripts (before checkbox.js / radio.js define their
	// helpers) — so jQuery stays blocking there. Everywhere else HUSKY is
	// dequeued (inc/shop-filter.php) and jQuery really defers.
	if ( function_exists( 'safestore_minimal_husky_assets_needed' ) && safestore_minimal_husky_assets_needed() && shortcode_exists( 'woof' ) ) {
		return;
	}

	// Only jquery-core carries a src. `jquery` is a dependency-only alias, and
	// WP 6.3+ emits a _doing_it_wrong notice if a strategy is set on it.
	if ( version_compare( get_bloginfo( 'version' ), '6.3', '>=' ) ) {
		wp_script_add_data( 'jquery-core', 'strategy', 'defer' );
	}
}
add_action( 'wp_enqueue_scripts', 'safestore_perf_jquery_loading', 1 );

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
		'safestore-pdp-actions',
		'safestore-product-compare',
	);
}

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

function safestore_perf_script_args( $args = true ) {
	if ( ! is_array( $args ) ) {
		$args = array( 'in_footer' => (bool) $args );
	}
	if ( version_compare( get_bloginfo( 'version' ), '6.3', '>=' ) ) {
		$args['strategy'] = 'defer';
	}
	return $args;
}

function safestore_perf_preload_lcp() {
	if ( is_admin() ) {
		return;
	}

	if ( is_page_template( 'page-home.php' ) || is_front_page() ) {
		// Exactly the image slide 1 renders (overlay product or theme artwork) —
		// templates/home/hero-slider.php reads the same helper.
		$hero = function_exists( 'safestore_hero_first_slide_image' ) ? safestore_hero_first_slide_image() : null;
		if ( is_array( $hero ) && ! empty( $hero['src'] ) ) {
			printf(
				'<link rel="preload" as="image" href="%1$s" fetchpriority="high"%2$s%3$s />' . "\n",
				esc_url( $hero['src'] ),
				! empty( $hero['srcset'] ) ? ' imagesrcset="' . esc_attr( $hero['srcset'] ) . '"' : '',
				( ! empty( $hero['srcset'] ) && ! empty( $hero['sizes'] ) ) ? ' imagesizes="' . esc_attr( $hero['sizes'] ) . '"' : ''
			);
			return;
		}
	}

	// Product pages: the main gallery image is the LCP element, not the logo.
	if ( function_exists( 'is_product' ) && is_product() ) {
		$product  = wc_get_product( get_queried_object_id() );
		$image_id = $product ? (int) $product->get_image_id() : 0;
		if ( $image_id ) {
			$size   = 'woocommerce_single';
			$src    = wp_get_attachment_image_src( $image_id, $size );
			$srcset = wp_get_attachment_image_srcset( $image_id, $size );
			$sizes  = wp_get_attachment_image_sizes( $image_id, $size );
			if ( is_array( $src ) && ! empty( $src[0] ) ) {
				printf(
					'<link rel="preload" as="image" href="%1$s" fetchpriority="high"%2$s%3$s />' . "\n",
					esc_url( $src[0] ),
					$srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '"' : '',
					( $srcset && $sizes ) ? ' imagesizes="' . esc_attr( $sizes ) . '"' : ''
				);
				return;
			}
		}
	}

	$logo = safestore_brand_logo_svg();
	printf(
		'<link rel="preload" as="image" href="%s" type="image/svg+xml" />' . "\n",
		esc_url( $logo['url'] )
	);
}
add_action( 'wp_head', 'safestore_perf_preload_lcp', 2 );

function safestore_perf_defer_vendor_scripts() {
	if ( is_admin() || version_compare( get_bloginfo( 'version' ), '6.3', '<' ) ) {
		return;
	}

	$handles = array(
		'sourcebuster-js',
		'wc-order-attribution',
		'js-cookie',
		'jquery-blockui',
		'jquery-cookie',
		'wc-add-to-cart',
		'wc-cart-fragments',
		'woocommerce',
		// Only needed by wc-add-to-cart-variation (already deferred) on product
		// pages; they were the last render-blocking scripts in <head>. WordPress
		// keeps them blocking automatically if any dependent is blocking.
		'underscore',
		'wp-util',
	);

	$scripts = wp_scripts();
	foreach ( $handles as $handle ) {
		// Aliases (no src, e.g. WC 10+'s js-cookie / jquery-blockui shims) cannot
		// take a strategy — WordPress logs a _doing_it_wrong notice for them.
		if ( wp_script_is( $handle, 'registered' ) && ! empty( $scripts->registered[ $handle ]->src ) ) {
			wp_script_add_data( $handle, 'strategy', 'defer' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'safestore_perf_defer_vendor_scripts', 100 );

/**
 * PixelYourSite ships its own js-cookie 2.1.3 (js-cookie-pys) next to
 * WooCommerce's 2.1.4 (wc-js-cookie) — same library and API (window.Cookies),
 * downloaded twice. Turn the PYS handle into an alias of WooCommerce's copy so
 * anything that depends on it still gets window.Cookies, from one file.
 */
function safestore_perf_dedupe_js_cookie() {
	if ( is_admin() ) {
		return;
	}
	$scripts = wp_scripts();
	if ( empty( $scripts->registered['js-cookie-pys'] ) || empty( $scripts->registered['wc-js-cookie'] ) ) {
		return;
	}
	$pys       = $scripts->registered['js-cookie-pys'];
	$pys->src  = false; // alias: prints nothing itself
	$pys->deps = array_values( array_unique( array_merge( array( 'wc-js-cookie' ), (array) $pys->deps ) ) );
}
add_action( 'wp_enqueue_scripts', 'safestore_perf_dedupe_js_cookie', 999 );

/**
 * PixelYourSite registers its scripts without a loading strategy. A single
 * blocking dependent makes WordPress print jQuery (and wc-js-cookie) blocking
 * in <head> for the whole page, so defer PYS too: public.js only binds on
 * DOM ready / jQuery events, and its pysOptions inline data is printed before
 * the tag regardless of strategy.
 */
function safestore_perf_defer_pys_scripts() {
	if ( is_admin() || version_compare( get_bloginfo( 'version' ), '6.3', '<' ) ) {
		return;
	}
	$scripts = wp_scripts();
	foreach ( array( 'pys', 'jquery-bind-first', 'js-sha256' ) as $handle ) {
		if ( wp_script_is( $handle, 'registered' ) && ! empty( $scripts->registered[ $handle ]->src ) ) {
			wp_script_add_data( $handle, 'strategy', 'defer' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'safestore_perf_defer_pys_scripts', 100 );


function safestore_perf_is_delayed_third_party_src( $src ) {
	if ( ! is_string( $src ) || '' === $src ) {
		return false;
	}

	return (bool) preg_match(
		'#connect\.facebook\.net|fbevents\.js|facebook\.com/tr|googletagmanager\.com|google-analytics\.com|gtag/js|static\.cloudflareinsights\.com/beacon#i',
		$src
	);
}

function safestore_perf_delay_third_party_script_tag( $tag, $handle, $src ) {
	unset( $handle );

	if ( is_admin() || ! safestore_perf_is_delayed_third_party_src( $src ) ) {
		return $tag;
	}

	if ( false !== strpos( $tag, 'data-sft-delay' ) ) {
		return $tag;
	}

	return sprintf(
		'<script type="text/plain" data-sft-delay data-src="%s"></script>' . "\n",
		esc_url( $src )
	);
}
add_filter( 'script_loader_tag', 'safestore_perf_delay_third_party_script_tag', 40, 3 );

function safestore_perf_delay_third_party_boot() {
	if ( is_admin() ) {
		return;
	}
	?>
<script id="sft-delay-third-party">
(function(){
	var loaded=false;
	function boot(){
		if(loaded){return;}
		loaded=true;
		var nodes=document.querySelectorAll('script[type="text/plain"][data-sft-delay]');
		for(var i=0;i<nodes.length;i++){
			var s=nodes[i], n=document.createElement('script');
			n.async=true;
			if(s.getAttribute('data-src')){n.src=s.getAttribute('data-src');}
			else{n.text=s.textContent;}
			document.body.appendChild(n);
		}
	}
	var events=['scroll','pointerdown','keydown','touchstart'];
	for(var e=0;e<events.length;e++){
		window.addEventListener(events[e],boot,{once:true,passive:true});
	}
	if('requestIdleCallback' in window){
		requestIdleCallback(boot,{timeout:4500});
	}else{
		setTimeout(boot,4500);
	}
})();
</script>
	<?php
}
add_action( 'wp_footer', 'safestore_perf_delay_third_party_boot', 1 );

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

function safestore_perf_reduced_motion_hint() {
	echo "<style id=\"sft-perf-reduced-motion\">@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important;scroll-behavior:auto!important}}</style>\n";
}
add_action( 'wp_head', 'safestore_perf_reduced_motion_hint', 1 );

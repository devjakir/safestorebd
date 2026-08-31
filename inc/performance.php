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
 * Stylesheets that are safe to load non-blocking (widgets / below-fold / WC
 * sheets the theme already overrides for first paint).
 *
 * Cart, checkout, and account keep WooCommerce CSS render-blocking so forms
 * do not flash unstyled.
 *
 * @return string[]
 */
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

	/**
	 * Filter async (non-render-blocking) style handles.
	 *
	 * @param string[] $handles Style handles.
	 */
	return array_values( array_unique( (array) apply_filters( 'safestore_perf_async_style_handles', $handles ) ) );
}

/**
 * Load selected stylesheets without blocking first paint.
 *
 * Uses media="print" + onload swap (with noscript fallback). Visual styles
 * still apply; only the critical path is shortened.
 *
 * @param string $html   Link tag HTML.
 * @param string $handle Style handle.
 * @param string $href   Stylesheet URL.
 * @param string $media  Media attribute.
 * @return string
 */
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

/**
 * Drop jquery-migrate and defer jquery-core on the storefront.
 *
 * PSI listed both as render-blocking. Theme + modern WooCommerce scripts do
 * not need migrate; defer keeps add-to-cart / fragments working via WP 6.3+
 * script strategies and dependency order.
 */
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

	// Only jquery-core carries a src. `jquery` is a dependency-only alias, and
	// WP 6.3+ emits a _doing_it_wrong notice if a strategy is set on it.
	if ( version_compare( get_bloginfo( 'version' ), '6.3', '>=' ) ) {
		wp_script_add_data( 'jquery-core', 'strategy', 'defer' );
	}
}
add_action( 'wp_enqueue_scripts', 'safestore_perf_jquery_loading', 1 );

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
		'safestore-product-compare',
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
 * Preload the LCP candidate: hero 720w WebP on home, logo WebP elsewhere.
 */
function safestore_perf_preload_lcp() {
	if ( is_admin() ) {
		return;
	}

	if ( is_page_template( 'page-home.php' ) || is_front_page() ) {
		$hero = get_template_directory_uri() . '/assets/images/' . rawurlencode( 'sf-helmet-category-720w.webp' );
		$images_dir = get_template_directory() . '/assets/images/';
		if ( ! file_exists( $images_dir . 'sf-helmet-category-720w.webp' ) ) {
			$hero = get_template_directory_uri() . '/assets/images/' . rawurlencode( 'sf-helmet-category.webp' );
		}
		printf(
			'<link rel="preload" as="image" href="%s" type="image/webp" fetchpriority="high" imagesrcset="%s" imagesizes="%s" />' . "\n",
			esc_url( $hero ),
			esc_attr(
				implode(
					', ',
					array(
						get_template_directory_uri() . '/assets/images/' . rawurlencode( 'sf-helmet-category-480w.webp' ) . ' 480w',
						get_template_directory_uri() . '/assets/images/' . rawurlencode( 'sf-helmet-category-720w.webp' ) . ' 720w',
						get_template_directory_uri() . '/assets/images/' . rawurlencode( 'sf-helmet-category.webp' ) . ' 900w',
					)
				)
			),
			esc_attr( '(max-width: 900px) 62vw, 42vw' )
		);
		return;
	}

	$logo_webp = get_template_directory() . '/assets/images/logo/safe-store-bd.webp';
	$logo      = file_exists( $logo_webp )
		? get_template_directory_uri() . '/assets/images/logo/safe-store-bd.webp'
		: get_template_directory_uri() . '/assets/images/logo/safe-store-bd.png';
	printf(
		'<link rel="preload" as="image" href="%s" />' . "\n",
		esc_url( $logo )
	);
}
add_action( 'wp_head', 'safestore_perf_preload_lcp', 2 );

/**
 * Defer heavy WooCommerce / cookie helpers that are not needed for first paint.
 */
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
	);

	foreach ( $handles as $handle ) {
		if ( wp_script_is( $handle, 'registered' ) ) {
			wp_script_add_data( $handle, 'strategy', 'defer' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'safestore_perf_defer_vendor_scripts', 100 );

/**
 * Whether a script URL looks like delayed marketing / analytics.
 *
 * @param string $src Script URL.
 * @return bool
 */
function safestore_perf_is_delayed_third_party_src( $src ) {
	if ( ! is_string( $src ) || '' === $src ) {
		return false;
	}

	return (bool) preg_match(
		'#connect\.facebook\.net|fbevents\.js|facebook\.com/tr|googletagmanager\.com|google-analytics\.com|gtag/js|static\.cloudflareinsights\.com/beacon#i',
		$src
	);
}

/**
 * Rewrite marketing pixels to type=text/plain so they do not compete with LCP.
 *
 * @param string $tag    Script tag.
 * @param string $handle Handle.
 * @param string $src    Source URL.
 * @return string
 */
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

/**
 * Boot loader: inject delayed marketing scripts after idle / first interaction.
 */
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

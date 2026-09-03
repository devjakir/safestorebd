<?php
/**
 * Page-scoped stylesheet loading for SafeStoreBD.
 *
 * style.css holds the GLOBAL shell: header, nav, offcanvas, typography and
 * shared commerce components (product card, stars, badges) that render on
 * many templates. Everything genuinely page-bound lives in css/page-*.css
 * and is enqueued only where the coverage audit proved it is used.
 */
function safestore_page_css( $handle, $file ) {
	static $done = array();
	if ( isset( $done[ $file ] ) ) {
		return;                       // never enqueue the same sheet twice
	}
	$path = get_template_directory() . '/css/' . $file;
	if ( ! file_exists( $path ) ) {
		return;
	}
	$done[ $file ] = true;
	wp_enqueue_style(
		'safestore-' . $handle,
		get_template_directory_uri() . '/css/' . $file,
		array( 'safestore-minimal-style' ),   // always after the global shell
		(string) filemtime( $path )
	);
}

add_action( 'wp_enqueue_scripts', function () {

	$wc = function_exists( 'is_woocommerce' );

	// --- commerce ---------------------------------------------------------
	if ( $wc && is_product() ) {
		safestore_page_css( 'product', 'page-product.css' );
	}

	// Shop chrome. woocommerce.php wraps EVERY WooCommerce page — shop,
	// taxonomy archives AND single products — in .sft-shop-main > .sft-shop,
	// so single products need this sheet too: the related-products grid and
	// the PDP's own container are both styled through .sft-shop selectors.
	// is_woocommerce() = is_shop() || is_product_taxonomy() || is_product().
	if ( $wc && is_woocommerce() ) {
		safestore_page_css( 'shop', 'page-shop.css' );
	} elseif ( is_search() || is_post_type_archive( 'product' ) ) {
		safestore_page_css( 'shop', 'page-shop.css' );
	}

	// Cart holds the generic WooCommerce table / form styling, so it is also
	// needed on checkout and on the My Account screens (orders, addresses).
	if ( $wc && ( is_cart() || is_checkout() || is_account_page() ) ) {
		safestore_page_css( 'cart', 'page-cart.css' );
	}
	if ( $wc && is_checkout() ) {
		safestore_page_css( 'checkout', 'page-checkout.css' );
	}

	// --- homepage ---------------------------------------------------------
	if ( is_front_page() || is_home() || is_page_template( 'page-home.php' ) ) {
		safestore_page_css( 'home', 'page-home.css' );
	}

	// --- content pages ----------------------------------------------------
	$map = array(
		'page-about.php'            => 'about',
		'page-contact.php'          => 'contact',
		'page-faq.php'              => 'faq',
		'page-careers.php'          => 'careers',
		'page-shipping.php'         => 'shipping',
		'page-track-order.php'      => 'track-order',
		'page-sitemap.php'          => 'sitemap',
		'page-legal.php'            => 'policy',
		'page-privacy-policy.php'   => 'policy',
		'page-refund-policy.php'    => 'policy',
		'page-terms-of-service.php' => 'policy',
	);
	foreach ( $map as $template => $module ) {
		if ( is_page_template( $template ) ) {
			safestore_page_css( 'pages-shared', 'page-pages-shared.css' );
			safestore_page_css( $module, 'page-' . $module . '.css' );
			break;
		}
	}
}, 20 );

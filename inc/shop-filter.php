<?php
/**
 * Shop filter bar — render the HUSKY / WOOF product filter above the grid.
 *
 * Ported from the live Code Snippets entry "Show product filter (HUSKY) above
 * shop grid". Once this file is deployed that snippet MUST be deactivated in
 * wp-admin → Snippets, or both copies run and the bar renders twice.
 * See SNIPPETS-MIGRATION.md.
 *
 * Requires the plugin "HUSKY – Products Filter for WooCommerce"
 * (woocommerce-products-filter). Everything here is inert without it —
 * shortcode_exists( 'woof' ) is the guard.
 *
 * Filter *configuration* is not in code: it lives in wp_options under
 * woof_settings and friends (brands, categories, price slider, in-stock,
 * on-sale, ajax/autosubmit). A fresh install needs it set up by hand —
 * SNIPPETS-MIGRATION.md section C is the recipe.
 *
 * HUSKY's own "insert filter automatically" option (woof_set_automatically) is
 * off on purpose, because this file owns placement.
 *
 * @package SafeStore_Minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the filter bar should render on this request.
 *
 * Matches the live snippet's CSS gate exactly: the main shop page plus product
 * category and product tag archives. Brand archives (product_brand) are not
 * included — swap in is_product_taxonomy() if you want them covered.
 *
 * @return bool
 */
function safestore_minimal_shop_filter_enabled() {
	if ( ! function_exists( 'is_shop' ) ) {
		return false;
	}

	return is_shop() || is_product_category() || is_product_tag();
}

/**
 * Load the filter bar stylesheet on shop and product archives only.
 *
 * Replaces the inline <style> block the snippet printed into wp_head, so the
 * rules are cacheable and live with the rest of the theme CSS.
 */
function safestore_minimal_shop_filter_assets() {
	if ( ! safestore_minimal_shop_filter_enabled() ) {
		return;
	}

	$css_path = get_template_directory() . '/css/shop-filter.css';
	if ( ! file_exists( $css_path ) ) {
		return;
	}

	wp_enqueue_style(
		'safestore-shop-filter',
		get_template_directory_uri() . '/css/shop-filter.css',
		array( 'safestore-minimal-style' ),
		(string) filemtime( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'safestore_minimal_shop_filter_assets', 24 );

/**
 * Render the [woof] filter inline above the product grid.
 *
 * The static guard stops a second bar if HUSKY's own auto-insert option is ever
 * switched on, or if the hook fires twice on one request.
 */
function safestore_minimal_shop_filter_render() {
	static $rendered = false;

	if ( $rendered ) {
		return;
	}

	if ( ! safestore_minimal_shop_filter_enabled() ) {
		return;
	}

	if ( ! shortcode_exists( 'woof' ) ) {
		return;
	}

	$rendered = true;

	echo '<div class="sft-shop-filter">' . do_shortcode( '[woof]' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'woocommerce_before_shop_loop', 'safestore_minimal_shop_filter_render', 15 );

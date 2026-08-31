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
 * Load the toolbar behaviour script (mobile toggle + stray-bar dedupe).
 */
function safestore_minimal_shop_filter_script() {
	if ( ! safestore_minimal_shop_filter_enabled() ) {
		return;
	}

	$js_path = get_template_directory() . '/js/shop-filter.js';
	if ( ! file_exists( $js_path ) ) {
		return;
	}

	wp_enqueue_script(
		'safestore-shop-filter',
		get_template_directory_uri() . '/js/shop-filter.js',
		array(),
		(string) filemtime( $js_path ),
		function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true
	);
}
add_action( 'wp_enqueue_scripts', 'safestore_minimal_shop_filter_script', 25 );

/**
 * WooCommerce core prints the shop page content / term description again via
 * woocommerce_archive_description (the ".page-description" block), duplicating
 * the styled intro the theme renders in its own shop header. Remove both core
 * callbacks — the theme owns the description.
 */
function safestore_minimal_remove_core_archive_description() {
	remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
	remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
}
add_action( 'init', 'safestore_minimal_remove_core_archive_description' );

/**
 * URL that clears every active filter for the current catalog view.
 *
 * @return string
 */
function safestore_minimal_shop_filter_reset_url() {
	if ( is_product_taxonomy() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				return $link;
			}
		}
	}

	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
}

/**
 * Render the [woof] filter as a compact toolbar above the product grid.
 *
 * The static guard stops a second bar if HUSKY's own auto-insert option is ever
 * switched on, or if the hook fires twice on one request. A stray copy printed
 * by the legacy Code Snippets entry is additionally hidden by CSS
 * (.sft-shop-filter ~ .sft-shop-filter) and removed by js/shop-filter.js.
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

	$has_active_filters = ! empty( $_GET['swoof'] ) || ! empty( $_GET['min_price'] ) || ! empty( $_GET['max_price'] ) || ! empty( $_GET['stock'] ) || ! empty( $_GET['sales'] ) || ! empty( $_GET['product_brand'] ) || ! empty( $_GET['product_cat'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<div class="sft-shop-filter<?php echo $has_active_filters ? ' sft-shop-filter--active' : ''; ?>">
		<div class="sft-shop-filter__head">
			<button type="button" class="sft-shop-filter__toggle" aria-expanded="false" aria-controls="sft-shop-filter-panel">
				<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M2 4.5h16v1.8l-6.2 6.2v4.6l-3.6-1.8v-2.8L2 6.3z"/></svg>
				<span><?php esc_html_e( 'Filters', 'safestore-minimal' ); ?></span>
			</button>
			<span class="sft-shop-filter__label" aria-hidden="true">
				<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M2 4.5h16v1.8l-6.2 6.2v4.6l-3.6-1.8v-2.8L2 6.3z"/></svg>
				<?php esc_html_e( 'Filter', 'safestore-minimal' ); ?>
			</span>
			<?php if ( $has_active_filters ) : ?>
				<a class="sft-shop-filter__reset" href="<?php echo esc_url( safestore_minimal_shop_filter_reset_url() ); ?>"><?php esc_html_e( 'Clear all', 'safestore-minimal' ); ?></a>
			<?php endif; ?>
		</div>
		<div class="sft-shop-filter__panel" id="sft-shop-filter-panel">
			<?php echo do_shortcode( '[woof]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_before_shop_loop', 'safestore_minimal_shop_filter_render', 15 );

/**
 * Full SEO welcome text below the product grid (shop page 1 only).
 *
 * The shop header now shows a short tagline; the complete shop-page content
 * (edited in wp-admin on the Shop page) moves under the grid where it serves
 * SEO without pushing products below the fold.
 */
function safestore_minimal_shop_seo_content() {
	if ( ! is_shop() || is_paged() || is_search() ) {
		return;
	}

	$shop_page_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;
	if ( $shop_page_id <= 0 ) {
		return;
	}

	$content = get_post_field( 'post_content', $shop_page_id );
	if ( '' === trim( (string) $content ) ) {
		return;
	}

	echo '<section class="sft-shop-seo">' . wp_kses_post( apply_filters( 'the_content', $content ) ) . '</section>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'woocommerce_after_shop_loop', 'safestore_minimal_shop_seo_content', 20 );

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
 * StarTech-style sidebar display types, kept in code so a deploy carries them.
 *
 * Only *how* a filter is drawn changes; which filters exist, AJAX and autosubmit
 * stay in wp-admin. Brands: multi-select drop-down → checkbox list (still
 * multi-select). Categories: drop-down → radio list (still single-select).
 * Price slider gains HUSKY's own from/to number inputs (synced by HUSKY's JS).
 * wp-admin screens read the stored values, so the HUSKY settings page is untouched.
 *
 * @param mixed $settings Stored woof_settings.
 * @return mixed
 */
function safestore_minimal_woof_display_settings( $settings ) {
	if ( ! is_array( $settings ) || ( is_admin() && ! wp_doing_ajax() ) ) {
		return $settings;
	}

	if ( ! empty( $settings['tax']['product_brand'] ) ) {
		$settings['tax_type']['product_brand'] = 'checkbox';
	}
	if ( ! empty( $settings['tax']['product_cat'] ) ) {
		$settings['tax_type']['product_cat'] = 'radio';
	}
	if ( isset( $settings['by_price'] ) && is_array( $settings['by_price'] ) ) {
		$settings['by_price']['show_text_input'] = 1;
	}

	return $settings;
}
add_filter( 'option_woof_settings', 'safestore_minimal_woof_display_settings' );

// HUSKY caches woof_settings in its constructor (before the theme loads), so patch the cached copy too — before its own init (priority 1).
function safestore_minimal_woof_patch_cached_settings() {
	if ( function_exists( 'woof' ) && is_object( woof() ) && isset( woof()->settings ) ) {
		woof()->settings = safestore_minimal_woof_display_settings( woof()->settings );
	}
}
add_action( 'init', 'safestore_minimal_woof_patch_cached_settings', 0 );

/**
 * Whether HUSKY's front-end assets are needed on this request.
 *
 * HUSKY enqueues its JS/CSS on every page. Off the filter pages jQuery is
 * deferred (inc/performance.php), so those scripts ran first and threw
 * "jQuery is not defined" on every page — and nothing there uses them.
 * A page/post that embeds [woof] in its content keeps them.
 *
 * @return bool
 */
function safestore_minimal_husky_assets_needed() {
	if ( is_admin() || safestore_minimal_shop_filter_enabled() ) {
		return true;
	}

	$post = is_singular() ? get_post() : null;
	return $post instanceof WP_Post && has_shortcode( $post->post_content, 'woof' );
}

// Drop HUSKY's script/style tags where no filter renders (tags are filtered at print time, so late enqueues are caught too).
function safestore_minimal_strip_husky_tag( $tag, $handle, $src ) {
	unset( $handle );
	if ( false === strpos( (string) $src, '/plugins/woocommerce-products-filter/' ) || safestore_minimal_husky_assets_needed() ) {
		return $tag;
	}
	return '';
}
add_filter( 'script_loader_tag', 'safestore_minimal_strip_husky_tag', 20, 3 );

function safestore_minimal_strip_husky_style_tag( $tag, $handle, $href ) {
	return safestore_minimal_strip_husky_tag( $tag, $handle, $href );
}
add_filter( 'style_loader_tag', 'safestore_minimal_strip_husky_style_tag', 20, 3 );

/**
 * The archive term the visitor is browsing, even on HUSKY's /swoof/ filter URLs.
 *
 * WordPress resolves /product-category/x/swoof/.../ as the shop page, so
 * is_product_taxonomy() is false there; HUSKY still knows the real term.
 *
 * @return WP_Term|null
 */
function safestore_minimal_shop_current_term() {
	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		$term = get_queried_object();
		return $term instanceof WP_Term ? $term : null;
	}

	if ( function_exists( 'woof' ) && is_object( woof() ) && method_exists( woof(), 'is_really_current_term_exists' ) && woof()->is_really_current_term_exists() ) {
		$term = woof()->get_really_current_term();
		return $term instanceof WP_Term ? $term : null;
	}

	return null;
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
	// Also needed on brand archives: woocommerce.php clamps every archive intro and this script adds its Read more toggle.
	if ( ! safestore_minimal_shop_filter_enabled() && ! ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) ) {
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
 * Whether woocommerce.php should use the sidebar layout and render the filter.
 *
 * @return bool
 */
function safestore_minimal_shop_filter_available() {
	return safestore_minimal_shop_filter_enabled() && shortcode_exists( 'woof' );
}

/**
 * Render the [woof] filter as the left sidebar card (called from woocommerce.php).
 *
 * Active filters and "Clear all" live in HUSKY's own chip panel above the grid
 * (.woof_products_top_panel), which updates on AJAX — so the card has no reset link.
 * The static guard stops a second card if this is ever called twice; a stray copy
 * printed by the legacy Code Snippets entry is hidden by CSS and removed by
 * js/shop-filter.js.
 */
function safestore_minimal_shop_filter_render() {
	static $rendered = false;

	if ( $rendered || ! safestore_minimal_shop_filter_available() ) {
		return;
	}

	$rendered = true;
	?>
	<div class="sft-shop-filter">
		<div class="sft-shop-filter__head">
			<button type="button" class="sft-shop-filter__toggle" aria-expanded="false" aria-controls="sft-shop-filter-panel">
				<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M2 4.5h16v1.8l-6.2 6.2v4.6l-3.6-1.8v-2.8L2 6.3z"/></svg>
				<span><?php esc_html_e( 'Filters', 'safestore-minimal' ); ?></span>
				<svg class="sft-shop-filter__chev" viewBox="0 0 12 8" width="12" height="8" aria-hidden="true" focusable="false"><path fill="currentColor" d="M1.4.6 6 5.2 10.6.6 12 2 6 8 0 2z"/></svg>
			</button>
			<h2 class="sft-shop-filter__label">
				<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M2 4.5h16v1.8l-6.2 6.2v4.6l-3.6-1.8v-2.8L2 6.3z"/></svg>
				<?php esc_html_e( 'Filters', 'safestore-minimal' ); ?>
			</h2>
		</div>
		<div class="sft-shop-filter__panel" id="sft-shop-filter-panel">
			<?php echo do_shortcode( '[woof]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
	<?php
}

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

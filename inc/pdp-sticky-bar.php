<?php
/**
 * Mobile (≤768px) sticky add-to-cart bar on product pages.
 *
 * Mirrors the main form: price (variation price once a size is picked), the
 * selected size, Add to cart and Buy now. The buttons drive the real form
 * buttons, so validation, the AJAX add (js/add-to-cart-toast.js) and the Buy
 * now redirect behave exactly as in the page. It slides in once the main
 * Add to cart button scrolls out of view and hides the WhatsApp float while
 * it is up. Script: js/pdp-sticky-bar.js, styles: css/pdp-sticky-bar.css.
 *
 * @package safestore-minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Product that should get the bar, or null. */
function safestore_sticky_atc_product() {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return null;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return null;
	}
	if ( ! $product->is_type( array( 'simple', 'variable' ) ) ) {
		return null;
	}
	return (bool) apply_filters( 'safestore_sticky_atc_enabled', true, $product ) ? $product : null;
}

function safestore_sticky_atc_enqueue() {
	if ( ! safestore_sticky_atc_product() ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	if ( file_exists( $dir . '/css/pdp-sticky-bar.css' ) ) {
		wp_enqueue_style( 'safestore-pdp-sticky-bar', $uri . '/css/pdp-sticky-bar.css', array( 'safestore-minimal-style' ), (string) filemtime( $dir . '/css/pdp-sticky-bar.css' ) );
	}
	if ( file_exists( $dir . '/js/pdp-sticky-bar.js' ) ) {
		wp_enqueue_script(
			'safestore-pdp-sticky-bar',
			$uri . '/js/pdp-sticky-bar.js',
			array( 'jquery' ),
			(string) filemtime( $dir . '/js/pdp-sticky-bar.js' ),
			function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'safestore_sticky_atc_enqueue', 30 );

function safestore_sticky_atc_markup() {
	$product = safestore_sticky_atc_product();
	if ( ! $product ) {
		return;
	}
	$is_variable = $product->is_type( 'variable' );
	?>
	<div class="sft-sticky-atc" data-sft-sticky-atc data-variable="<?php echo $is_variable ? '1' : '0'; ?>" data-size-prefix="<?php esc_attr_e( 'Size:', 'safestore-minimal' ); ?>" data-select-label="<?php esc_attr_e( 'Select a size', 'safestore-minimal' ); ?>" role="region" aria-label="<?php esc_attr_e( 'Quick buy', 'safestore-minimal' ); ?>" hidden>
		<div class="sft-sticky-atc__info">
			<span class="sft-sticky-atc__price" data-sft-sticky-price><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
			<?php if ( $is_variable ) : ?>
				<span class="sft-sticky-atc__size" data-sft-sticky-size><?php esc_html_e( 'Select a size', 'safestore-minimal' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="sft-sticky-atc__actions">
			<button type="button" class="sft-sticky-atc__btn sft-sticky-atc__btn--cart" data-sft-sticky-add><?php esc_html_e( 'Add to cart', 'safestore-minimal' ); ?></button>
			<button type="button" class="sft-sticky-atc__btn sft-sticky-atc__btn--buy" data-sft-sticky-buy><?php esc_html_e( 'Buy now', 'safestore-minimal' ); ?></button>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'safestore_sticky_atc_markup', 6 );

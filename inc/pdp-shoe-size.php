<?php
/**
 * PDP shoe size selector (Safety Shoes, EU 39–44)
 *
 * Works for simple products in the Safety Shoes category by injecting a
 * size button grid above Add to cart and storing the choice as cart /
 * order line-item meta. Variable products with pa_size keep using the
 * standard WooCommerce variation flow (see footwear-sizing.php).
 *
 * Hooks:
 * - woocommerce_before_add_to_cart_button
 * - woocommerce_add_to_cart_validation
 * - woocommerce_add_cart_item_data / get_item_data
 * - woocommerce_checkout_create_order_line_item
 *
 * @package SafeStore_Minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field name posted with the cart form.
 */
define( 'SAFESTORE_SHOE_SIZE_FIELD', 'safestore_shoe_size' );

/**
 * Whether this product should use the simple-product size meta UI.
 * Variable shoes with pa_size are handled by the variations form instead.
 *
 * @param WC_Product|null $product Product.
 * @return bool
 */
function safestore_pdp_needs_shoe_size_meta( $product = null ) {
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! $product || ! function_exists( 'safestore_is_footwear_product' ) ) {
		return false;
	}
	if ( ! safestore_is_footwear_product( $product ) ) {
		return false;
	}
	// Variable + Size attribute → native variations / swatches.
	if ( $product->is_type( 'variable' ) ) {
		$attrs = $product->get_variation_attributes();
		if ( ! empty( $attrs['pa_size'] ) || ! empty( $attrs['size'] ) ) {
			return false;
		}
	}
	return true;
}

/**
 * 1) Inject size selector above Add to cart for Safety Shoes only.
 */
function safestore_pdp_render_shoe_size_selector() {
	global $product;

	if ( ! safestore_pdp_needs_shoe_size_meta( $product ) ) {
		return;
	}

	$sizes = function_exists( 'safestore_footwear_allowed_sizes' )
		? safestore_footwear_allowed_sizes()
		: array( '39', '40', '41', '42', '43', '44' );

	$selected = isset( $_REQUEST[ SAFESTORE_SHOE_SIZE_FIELD ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		? sanitize_text_field( wp_unslash( $_REQUEST[ SAFESTORE_SHOE_SIZE_FIELD ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';
	?>
	<div class="sft-pdp-shoe-size" data-sft-shoe-size-panel>
		<div class="sft-pdp-shoe-size__header">
			<span class="sft-pdp-shoe-size__label" id="sft-pdp-shoe-size-label"><?php esc_html_e( 'Size', 'safestore-minimal' ); ?></span>
			<span class="sft-pdp-shoe-size__hint"><?php esc_html_e( 'Select a size', 'safestore-minimal' ); ?></span>
		</div>

		<div class="sft-pdp-shoe-size__swatches" role="listbox" aria-labelledby="sft-pdp-shoe-size-label">
			<?php foreach ( $sizes as $size ) : ?>
				<?php
				$is_active = ( (string) $selected === (string) $size );
				$classes   = 'sft-pdp-shoe-size__swatch';
				if ( $is_active ) {
					$classes .= ' is-selected active';
				}
				?>
				<button
					type="button"
					class="<?php echo esc_attr( $classes ); ?>"
					role="option"
					aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
					data-size="<?php echo esc_attr( $size ); ?>"
				><?php echo esc_html( $size ); ?></button>
			<?php endforeach; ?>
		</div>

		<input
			type="hidden"
			name="<?php echo esc_attr( SAFESTORE_SHOE_SIZE_FIELD ); ?>"
			id="safestore-shoe-size"
			value="<?php echo esc_attr( $selected ); ?>"
			required
			autocomplete="off"
		>

		<p class="sft-pdp-shoe-size__error" data-sft-shoe-size-error hidden>
			<?php esc_html_e( 'Please select a size', 'safestore-minimal' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'woocommerce_before_add_to_cart_button', 'safestore_pdp_render_shoe_size_selector', 10 );

/**
 * 2a) Server-side: require a valid size for Safety Shoes simple products.
 *
 * @param bool $passed     Validation state.
 * @param int  $product_id Product ID.
 * @param int  $quantity   Qty.
 * @return bool
 */
function safestore_pdp_validate_shoe_size( $passed, $product_id, $quantity ) {
	unset( $quantity );

	if ( ! $passed ) {
		return $passed;
	}

	$product = wc_get_product( $product_id );
	if ( ! safestore_pdp_needs_shoe_size_meta( $product ) ) {
		return $passed;
	}

	$size = isset( $_REQUEST[ SAFESTORE_SHOE_SIZE_FIELD ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		? sanitize_text_field( wp_unslash( $_REQUEST[ SAFESTORE_SHOE_SIZE_FIELD ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';

	$allowed = function_exists( 'safestore_footwear_allowed_sizes' )
		? safestore_footwear_allowed_sizes()
		: array( '39', '40', '41', '42', '43', '44' );

	if ( '' === $size || ! in_array( $size, $allowed, true ) ) {
		wc_add_notice( __( 'Please select a size', 'safestore-minimal' ), 'error' );
		return false;
	}

	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'safestore_pdp_validate_shoe_size', 20, 3 );

/**
 * 2b) Attach size to cart item data.
 *
 * @param array $cart_item_data Cart item data.
 * @param int   $product_id     Product ID.
 * @param int   $variation_id   Variation ID.
 * @return array
 */
function safestore_pdp_add_shoe_size_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
	unset( $variation_id );

	$product = wc_get_product( $product_id );
	if ( ! safestore_pdp_needs_shoe_size_meta( $product ) ) {
		return $cart_item_data;
	}

	$size = isset( $_REQUEST[ SAFESTORE_SHOE_SIZE_FIELD ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		? sanitize_text_field( wp_unslash( $_REQUEST[ SAFESTORE_SHOE_SIZE_FIELD ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';

	$allowed = function_exists( 'safestore_footwear_allowed_sizes' )
		? safestore_footwear_allowed_sizes()
		: array( '39', '40', '41', '42', '43', '44' );

	if ( '' === $size || ! in_array( $size, $allowed, true ) ) {
		return $cart_item_data;
	}

	$cart_item_data[ SAFESTORE_SHOE_SIZE_FIELD ] = $size;
	// Different sizes = separate cart lines; same size merges quantity.
	$cart_item_data['unique_key'] = $product_id . '_size_' . $size;

	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'safestore_pdp_add_shoe_size_cart_item_data', 20, 3 );

/**
 * Show size under the product name in cart / checkout.
 *
 * @param array $item_data Item data rows.
 * @param array $cart_item Cart item.
 * @return array
 */
function safestore_pdp_display_shoe_size_cart( $item_data, $cart_item ) {
	if ( empty( $cart_item[ SAFESTORE_SHOE_SIZE_FIELD ] ) ) {
		return $item_data;
	}

	$item_data[] = array(
		'key'   => __( 'Size', 'safestore-minimal' ),
		'value' => wc_clean( $cart_item[ SAFESTORE_SHOE_SIZE_FIELD ] ),
	);

	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'safestore_pdp_display_shoe_size_cart', 20, 2 );

/**
 * Persist size on the order line item.
 *
 * @param WC_Order_Item_Product $item          Order item.
 * @param string                $cart_item_key Cart key.
 * @param array                 $values        Cart values.
 */
function safestore_pdp_save_shoe_size_order_item( $item, $cart_item_key, $values ) {
	unset( $cart_item_key );

	if ( empty( $values[ SAFESTORE_SHOE_SIZE_FIELD ] ) ) {
		return;
	}

	$item->add_meta_data(
		__( 'Size', 'safestore-minimal' ),
		wc_clean( $values[ SAFESTORE_SHOE_SIZE_FIELD ] ),
		true
	);
}
add_action( 'woocommerce_checkout_create_order_line_item', 'safestore_pdp_save_shoe_size_order_item', 20, 3 );

/**
 * Enqueue PDP size JS/CSS on single product when needed.
 */
function safestore_pdp_shoe_size_assets() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product = wc_get_product( get_the_ID() );
	if ( ! safestore_pdp_needs_shoe_size_meta( $product ) && ! ( function_exists( 'safestore_is_footwear_product' ) && safestore_is_footwear_product( $product ) ) ) {
		return;
	}

	$css = get_template_directory() . '/css/footwear-sizing.css';
	$js  = get_template_directory() . '/js/pdp-shoe-size.js';

	if ( file_exists( $css ) ) {
		wp_enqueue_style(
			'safestore-footwear-sizing',
			get_template_directory_uri() . '/css/footwear-sizing.css',
			array( 'safestore-minimal-style' ),
			(string) filemtime( $css )
		);
	}

	if ( file_exists( $js ) && safestore_pdp_needs_shoe_size_meta( $product ) ) {
		wp_enqueue_script(
			'safestore-pdp-shoe-size',
			get_template_directory_uri() . '/js/pdp-shoe-size.js',
			array( 'jquery' ),
			(string) filemtime( $js ),
			true
		);

		wp_localize_script(
			'safestore-pdp-shoe-size',
			'safestorePdpShoeSize',
			array(
				'field' => SAFESTORE_SHOE_SIZE_FIELD,
				'i18n'  => array(
					'selectSize' => __( 'Please select a size', 'safestore-minimal' ),
				),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'safestore_pdp_shoe_size_assets', 35 );

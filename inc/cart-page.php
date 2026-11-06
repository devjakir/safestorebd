<?php
/**
 * Cart page (classic [woocommerce_cart]).
 *
 *  - − / + quantity buttons for woocommerce/cart/cart.php;
 *  - js/cart-page.js: buttons + auto "Update cart" when a quantity changes;
 *  - short reassurance notes under "Proceed to checkout".
 *
 * Styling lives in css/page-cart.css.
 *
 * @package safestore-minimal
 */

defined( 'ABSPATH' ) || exit;

function safestore_cart_qty_minus() {
	echo '<button type="button" class="sft-cart-qty-btn sft-cart-qty-btn--minus" aria-label="' . esc_attr__( 'Decrease quantity', 'safestore-minimal' ) . '" tabindex="-1">'
		. '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M5 12h14"/></svg>'
		. '</button>';
}

function safestore_cart_qty_plus() {
	echo '<button type="button" class="sft-cart-qty-btn sft-cart-qty-btn--plus" aria-label="' . esc_attr__( 'Increase quantity', 'safestore-minimal' ) . '" tabindex="-1">'
		. '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M12 5v14M5 12h14"/></svg>'
		. '</button>';
}

/**
 * Called by woocommerce/cart/cart.php around one row's woocommerce_quantity_input(),
 * so the buttons never leak into other quantity inputs (mini cart, PDP).
 */
function safestore_cart_qty_stepper_on() {
	add_action( 'woocommerce_before_quantity_input_field', 'safestore_cart_qty_minus' );
	add_action( 'woocommerce_after_quantity_input_field', 'safestore_cart_qty_plus' );
}

function safestore_cart_qty_stepper_off() {
	remove_action( 'woocommerce_before_quantity_input_field', 'safestore_cart_qty_minus' );
	remove_action( 'woocommerce_after_quantity_input_field', 'safestore_cart_qty_plus' );
}

function safestore_cart_page_enqueue() {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}

	$path = get_template_directory() . '/js/cart-page.js';
	if ( ! file_exists( $path ) ) {
		return;
	}

	wp_enqueue_script(
		'safestore-cart-page',
		get_template_directory_uri() . '/js/cart-page.js',
		array( 'jquery', 'wc-cart' ),
		(string) filemtime( $path ),
		function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true
	);
}
add_action( 'wp_enqueue_scripts', 'safestore_cart_page_enqueue', 20 );

/**
 * Notes under the checkout button. Same facts the footer already states
 * (payment methods, genuine stock); the delivery line only shows when the
 * cart ships, because the courier charge follows the district / thana
 * entered at checkout.
 */
function safestore_cart_totals_notes() {
	$notes = array();

	if ( WC()->cart && WC()->cart->needs_shipping() ) {
		$notes[] = array(
			'icon' => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="17.5" r="1.6"/><circle cx="17" cy="17.5" r="1.6"/>',
			'text' => __( 'Delivery charge is confirmed at checkout from your district and thana.', 'safestore-minimal' ),
		);
	}
	$notes[] = array(
		'icon' => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/>',
		'text' => __( 'Cash on delivery, bKash or Nagad.', 'safestore-minimal' ),
	);
	$notes[] = array(
		'icon' => '<path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z"/><path d="M9 12l2 2 4-4"/>',
		'text' => __( '100% genuine imported stock.', 'safestore-minimal' ),
	);

	/**
	 * Filters the notes printed under the cart's checkout button.
	 *
	 * @param array[] $notes Each: icon (SVG inner markup), text.
	 */
	$notes = apply_filters( 'safestore_cart_totals_notes', $notes );
	if ( ! $notes ) {
		return;
	}

	echo '<ul class="sft-cart-notes">';
	foreach ( $notes as $note ) {
		printf(
			'<li><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%1$s</svg><span>%2$s</span></li>',
			wp_kses( $note['icon'], array(
				'path'   => array( 'd' => true ),
				'circle' => array( 'cx' => true, 'cy' => true, 'r' => true ),
				'rect'   => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true ),
			) ),
			esc_html( $note['text'] )
		);
	}
	echo '</ul>';
}
add_action( 'woocommerce_after_cart_totals', 'safestore_cart_totals_notes' );

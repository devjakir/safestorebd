<?php
/**
 * Order received (thank-you) page + Bangladesh price format.
 *
 * The page itself is woocommerce/checkout/thankyou.php; this file holds its
 * helpers, its stylesheet, and the site-wide "৳5,130" price format.
 *
 * @package safestore-minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Price format — BD marketplace style: symbol first, no ".00".
 *
 *   before: 5,130.00৳    after: ৳5,130
 *
 * Matches the theme's own copy ("From ৳80") and how Daraz, Chaldal etc. show
 * prices. Done in code so every environment matches live; it overrides the
 * "Currency position" setting in WooCommerce → Settings → General.
 * Trimming zeros is display-only: totals are still calculated with the store's
 * decimals, and a real fraction (৳99.50) still shows.
 * ---------------------------------------------------------------------- */
add_filter(
	'pre_option_woocommerce_currency_pos',
	static function () {
		return 'left';
	}
);
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );

/** True on /checkout/order-received/{id}/. */
function safestore_is_order_received() {
	return function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' );
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( safestore_is_order_received() && function_exists( 'safestore_page_css' ) ) {
			safestore_page_css( 'order-received', 'page-order-received.css' );
		}
	},
	20 // After inc/page-css.php has queued the cart / checkout sheets.
);

/** Body class so the page can drop the generic checkout title card. */
add_filter(
	'body_class',
	static function ( $classes ) {
		if ( safestore_is_order_received() ) {
			$classes[] = 'sft-order-received';
		}
		return $classes;
	}
);

/** Plain-text price ("৳5,130") for sentences and WhatsApp messages. */
function safestore_ty_plain_price( $amount, $order ) {
	return html_entity_decode(
		wp_strip_all_tags( wc_price( $amount, array( 'currency' => $order->get_currency() ) ) ),
		ENT_QUOTES,
		'UTF-8'
	);
}

/** "01761-655555" from whatever the customer typed. */
function safestore_ty_format_phone( $raw ) {
	$digits = preg_replace( '/\D/', '', (string) $raw );
	if ( 13 === strlen( $digits ) && 0 === strpos( $digits, '880' ) ) {
		$digits = substr( $digits, 2 );
	}
	if ( 11 === strlen( $digits ) && '0' === $digits[0] ) {
		return substr( $digits, 0, 5 ) . '-' . substr( $digits, 5 );
	}
	return trim( (string) $raw );
}

/** Customer name for the greeting; fixes all-lowercase input ("abdur rahman"). */
function safestore_ty_customer_name( $order ) {
	$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	if ( '' !== $name && function_exists( 'mb_convert_case' ) && mb_strtolower( $name, 'UTF-8' ) === $name ) {
		$name = mb_convert_case( $name, MB_CASE_TITLE, 'UTF-8' );
	}
	return $name;
}

/**
 * How the customer pays: 'mobile' (bKash / Nagad / Rocket, paid after a
 * WhatsApp hand-off), 'cod', or 'other' (any other gateway — its own
 * instructions are shown as they are).
 */
function safestore_ty_payment_type( $order ) {
	$id    = (string) $order->get_payment_method();
	$title = (string) $order->get_payment_method_title();

	if ( 'cod' === $id ) {
		$type = 'cod';
	} elseif ( preg_match( '/bkash|nagad|rocket|upay/i', $id . ' ' . $title ) ) {
		$type = 'mobile';
	} else {
		$type = 'other';
	}

	return apply_filters( 'safestore_ty_payment_type', $type, $order );
}

/** True for Dhaka district / division orders (state code BD-13 or "Dhaka"). */
function safestore_ty_is_dhaka( $order ) {
	$state = (string) ( $order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state() );
	return 'BD-13' === $state || 0 === stripos( $state, 'dhaka' );
}

/** Store hotline digits (the main WhatsApp line). */
function safestore_ty_hotline() {
	$digits = function_exists( 'safestore_wa_number' ) ? safestore_wa_number() : '';
	return '' !== $digits ? $digits : '8801811892291';
}

/**
 * Order totals rows for the summary card, with customer-friendly labels.
 * Payment method is shown elsewhere, and "via Flat rate" means nothing to a
 * shopper, so both are dropped here (emails and admin are untouched).
 */
function safestore_ty_total_rows( $order ) {
	$hide_via = static function () {
		return '';
	};
	add_filter( 'woocommerce_order_shipping_to_display_shipped_via', $hide_via );
	$rows = $order->get_order_item_totals();
	remove_filter( 'woocommerce_order_shipping_to_display_shipped_via', $hide_via );

	unset( $rows['payment_method'] );

	$labels = array(
		'cart_subtotal' => __( 'Subtotal', 'safestore-minimal' ),
		'shipping'      => __( 'Delivery charge', 'safestore-minimal' ),
		'discount'      => __( 'Discount', 'safestore-minimal' ),
		'order_total'   => __( 'Total', 'safestore-minimal' ),
	);

	foreach ( $rows as $key => $row ) {
		$rows[ $key ]['label'] = isset( $labels[ $key ] ) ? $labels[ $key ] : rtrim( trim( (string) $row['label'] ), ':' );
	}

	return $rows;
}

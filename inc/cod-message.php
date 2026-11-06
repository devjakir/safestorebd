<?php
/**
 * Cash on delivery: checkout message.
 *
 * Replaces the COD description saved in WooCommerce → Settings → Payments
 * with theme copy, so it is version-controlled and can change with the
 * order size. Orders at or above the "large order" threshold get an extra
 * note: we confirm by phone / WhatsApp before dispatch (no advance asked).
 *
 * Threshold: filter `safestore_cod_large_order_threshold` (default ৳10,000).
 *
 * @package SafeStoreBD
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cart total (BDT) at which an order counts as "large" for COD.
 *
 * @return float
 */
function safestore_cod_large_order_threshold() {
	return (float) apply_filters( 'safestore_cod_large_order_threshold', 10000 );
}

/**
 * Whether an amount counts as a large COD order.
 *
 * @param float $total Order / cart total.
 * @return bool
 */
function safestore_cod_is_large_order( $total ) {
	return (float) $total >= safestore_cod_large_order_threshold();
}

/**
 * Note shown for large COD orders (checkout + thank-you page).
 *
 * @return string HTML.
 */
function safestore_cod_large_order_note() {
	return __( '<strong>Large order:</strong> we will call or WhatsApp you to confirm the items, sizes and delivery date before we ship. Buying for a company? Ask us for an invoice or bank transfer.', 'safestore-minimal' );
}

add_filter(
	'woocommerce_gateway_description',
	function ( $description, $gateway_id ) {
		if ( 'cod' !== $gateway_id ) {
			return $description;
		}

		$description = __( 'Pay the courier in cash when your parcel arrives. Check the parcel first; if it is damaged or wrong, refuse it and WhatsApp us.', 'safestore-minimal' );

		$total = ( function_exists( 'WC' ) && WC()->cart ) ? (float) WC()->cart->get_total( 'edit' ) : 0;
		if ( $total && safestore_cod_is_large_order( $total ) ) {
			$description .= "\n\n" . safestore_cod_large_order_note();
		}

		return $description;
	},
	20,
	2
);

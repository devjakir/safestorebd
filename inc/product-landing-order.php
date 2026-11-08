<?php
/**
 * Product landing → order in one step (no cart page, no checkout page).
 *
 * The order form on page-templates/template-product-landing.php POSTs to the
 * page itself. inc/product-landing.php checks the product / size / quantity and
 * calls safestore_pl_place_order(), which puts only that line in the cart (a
 * category landing passes several lines) and
 * runs WooCommerce's own checkout (WC_Checkout::process_checkout). So the thana
 * check, phone check, Steadfast charge, COD, stock, emails and order
 * attribution work exactly as on /checkout/.
 *
 * - JS posts with spl_ajax=1 and gets WooCommerce's JSON back:
 *   { result: success, redirect } or { result: failure, messages }.
 * - Without JS the browser is redirected: thank-you page, or back to the form
 *   with WooCommerce's error notices.
 *
 * Also here: the form's field list, Bangla district names, the courier data
 * for the live delivery charge, and Bangla thank-you copy for landing orders.
 *
 * Abuse guards (public form, no nonce on purpose — landing pages are cached and
 * a cached nonce goes stale): honeypot, max orders per IP per hour, and a
 * per-visit token so a retried request returns the first order, not a second.
 */

defined( 'ABSPATH' ) || exit;

define( 'SAFESTORE_PL_MAX_ORDERS_PER_IP', 10 ); // per hour; mobile networks share IPs, keep it loose

/**
 * Fields of the landing order form: WooCommerce billing key => label, control,
 * placeholder, error. Labels / placeholders / errors are English; shoppers can
 * type in Bangla or English (Bangla digits in the phone number are accepted).
 */
function safestore_pl_form_fields() {
	return array(
		'billing_first_name' => array( 'label' => 'Your name', 'type' => 'text', 'auto' => 'name', 'ph' => 'e.g. Md. Rakib Hasan', 'err' => 'Please enter your name' ),
		'billing_phone'      => array( 'label' => 'Mobile number', 'type' => 'tel', 'auto' => 'tel', 'ph' => '01XXXXXXXXX', 'err' => 'Enter an 11-digit mobile number, e.g. 01712345678' ),
		'billing_state'      => array( 'label' => 'District', 'type' => 'select', 'auto' => 'address-level1', 'ph' => 'Select district', 'err' => 'Please select your district' ),
		'billing_city'       => array( 'label' => 'Thana / Upazila', 'type' => 'select', 'auto' => 'address-level2', 'ph' => 'Select district first', 'err' => 'Please select your thana / upazila' ),
		'billing_address_1'  => array( 'label' => 'Full address', 'type' => 'textarea', 'auto' => 'street-address', 'ph' => 'House / road / area', 'err' => 'Please enter house, road and area' ),
	);
}

/**
 * WooCommerce BD state code => district name (English, as at checkout).
 * Dhaka, Gazipur, Narayanganj, Chattogram first (inc/checkout-thana.php), then A–Z.
 * Values posted stay BD-xx.
 */
function safestore_pl_districts() {
	$states = function_exists( 'WC' ) && WC()->countries ? (array) WC()->countries->get_states( 'BD' ) : array();
	return apply_filters( 'safestore_pl_districts', $states );
}

/**
 * What the form needs to show the delivery charge before the order: the
 * courier's lane prices, per-unit parcel weight, and which places are not
 * "outside Dhaka". Computed with the courier's own functions, so the page
 * and the order agree. null while the courier method is not live.
 */
function safestore_pl_ship_data( $product ) {
	if ( ! function_exists( 'safestorebd_courier_public_rates' ) || ! function_exists( 'safestorebd_courier_lane' ) ) {
		return null;
	}
	$rates = safestorebd_courier_public_rates();
	if ( ! $rates ) {
		return null;
	}
	$kg    = safestore_lp_product_kg( $product );
	$lanes = array();
	foreach ( array_keys( safestore_pl_districts() ) as $code ) {
		$lane = safestorebd_courier_lane( $code, '' );
		if ( 'outside' !== $lane ) {
			$lanes[ $code ] = $lane;
		}
	}
	$suburbs = array();
	foreach ( safestorebd_thanas_for( 'BD-13' ) as $thana ) {
		if ( 'suburb' === safestorebd_courier_lane( 'BD-13', $thana ) ) {
			$suburbs[] = $thana;
		}
	}
	return array( 'rates' => $rates, 'kg' => $kg, 'lanes' => $lanes, 'sub' => $suburbs );
}

/**
 * Delivery charge when the Steadfast method is not live (the live site still
 * uses WooCommerce Flat rate: ৳80 Dhaka, ৳130 elsewhere). Asks WooCommerce what
 * its own zones / methods charge for this product in every district — the same
 * calculation checkout runs — at quantity 1 and 2, so the form can price any
 * quantity: first + (qty − 1) × extra. One calculation per shipping zone, cached
 * until the product price / weight or any shipping setting changes.
 *
 * @param WC_Product $product Landing product.
 * @return array|null BD-xx => array( first, extra ), or null when nothing ships.
 */
function safestore_pl_wc_ship_data( $product ) {
	if ( ! function_exists( 'WC' ) || ! class_exists( 'WC_Shipping_Zones' ) || ! WC()->shipping() ) {
		return null;
	}
	$item = $product;
	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( $child && $child->is_purchasable() && $child->is_in_stock() ) {
				$item = $child;
				break;
			}
		}
	}
	$version = class_exists( 'WC_Cache_Helper' ) ? WC_Cache_Helper::get_transient_version( 'shipping' ) : '';
	$key     = 'spl_ship_' . md5( $item->get_id() . '|' . $item->get_price() . '|' . $item->get_weight() . '|' . $version );
	$cached  = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached ? $cached : null;
	}

	$out     = array();
	$by_zone = array();
	foreach ( array_keys( safestore_pl_districts() ) as $code ) {
		$zone = WC_Shipping_Zones::get_zone_matching_package( array( 'destination' => array( 'country' => 'BD', 'state' => $code, 'postcode' => '' ) ) );
		$zid  = $zone ? (int) $zone->get_id() : 0;
		if ( ! array_key_exists( $zid, $by_zone ) ) {
			$one             = safestore_pl_package_cost( $item, 1, $code );
			$two             = null === $one ? null : safestore_pl_package_cost( $item, 2, $code );
			$by_zone[ $zid ] = null === $one ? null : array( $one, null === $two ? 0.0 : max( 0.0, $two - $one ) );
		}
		if ( null !== $by_zone[ $zid ] ) {
			$out[ $code ] = $by_zone[ $zid ];
		}
	}
	set_transient( $key, $out, 12 * HOUR_IN_SECONDS );
	return $out ? $out : null;
}

/**
 * What WooCommerce charges to deliver $qty × $item to a district, or null.
 * Pickup methods are skipped (the landing form always delivers).
 */
function safestore_pl_package_cost( $item, $qty, $state ) {
	$line    = (float) $item->get_price() * $qty;
	$package = array(
		'contents'        => array(
			'spl' => array(
				'key'               => 'spl',
				'product_id'        => $item->get_parent_id() ? $item->get_parent_id() : $item->get_id(),
				'variation_id'      => $item->is_type( 'variation' ) ? $item->get_id() : 0,
				'variation'         => array(),
				'quantity'          => $qty,
				'data'              => $item,
				'line_total'        => $line,
				'line_tax'          => 0,
				'line_subtotal'     => $line,
				'line_subtotal_tax' => 0,
			),
		),
		'contents_cost'   => $line,
		'applied_coupons' => array(),
		'user'            => array( 'ID' => get_current_user_id() ),
		'destination'     => array( 'country' => 'BD', 'state' => $state, 'postcode' => '', 'city' => '', 'address' => '', 'address_1' => '', 'address_2' => '' ),
		'cart_subtotal'   => $line,
	);
	$result = WC()->shipping()->calculate_shipping_for_package( $package );
	foreach ( isset( $result['rates'] ) ? (array) $result['rates'] : array() as $rate ) {
		if ( in_array( $rate->get_method_id(), array( 'local_pickup', 'pickup_location' ), true ) ) {
			continue;
		}
		return (float) $rate->get_cost() + (float) array_sum( (array) $rate->get_taxes() );
	}
	return null;
}

/** bKash / Nagad gateway is switched on (WooCommerce → Payments), so the form can offer "Pay now". */
function safestore_pl_mfs_available() {
	if ( ! function_exists( 'safestore_mfs_gateway_id' ) || ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
		return false;
	}
	$gateways = WC()->payment_gateways()->payment_gateways();
	$id       = safestore_mfs_gateway_id();
	return isset( $gateways[ $id ] ) && 'yes' === $gateways[ $id ]->enabled;
}

/** Rate id of the courier for the cart's current destination, so checkout never picks pickup by default. */
function safestore_pl_courier_rate_id( $state, $thana ) {
	if ( ! defined( 'SAFESTOREBD_COURIER_ID' ) ) {
		return '';
	}
	WC()->customer->set_billing_location( 'BD', $state, '', $thana );
	WC()->customer->set_shipping_location( 'BD', $state, '', $thana );
	WC()->cart->calculate_shipping();
	foreach ( WC()->shipping()->get_packages() as $package ) {
		foreach ( (array) $package['rates'] as $id => $rate ) {
			if ( SAFESTOREBD_COURIER_ID === $rate->get_method_id() ) {
				return (string) $id;
			}
		}
	}
	return '';
}

/**
 * Place the order for the checked cart line(s) and end the request.
 *
 * @param int      $page_id Landing page.
 * @param array    $lines   One line from safestore_pl_resolve_line(), or a list of
 *                          lines (category landing: several products in one order).
 * @param bool     $ajax    JSON answer instead of redirects.
 * @param callable $fail    Sends the error for a code and exits.
 */
function safestore_pl_place_order( $page_id, $lines, $ajax, $fail ) {
	$lines = isset( $lines['product_id'] ) ? array( $lines ) : array_values( (array) $lines );
	// phpcs:disable WordPress.Security.NonceVerification -- public order form, see the guards in the file header.
	if ( ! empty( $_POST['spl_website'] ) ) {
		$fail( 'cart' ); // honeypot filled: a bot
	}

	$ip_key = 'spl_ip_' . md5( class_exists( 'WC_Geolocation' ) ? WC_Geolocation::get_ip_address() : '' );
	if ( (int) get_transient( $ip_key ) >= SAFESTORE_PL_MAX_ORDERS_PER_IP ) {
		$fail( 'busy' );
	}

	// Same visit sent twice (double tap, flaky network): answer with the first order.
	$token   = isset( $_POST['spl_token'] ) ? sanitize_key( wp_unslash( $_POST['spl_token'] ) ) : '';
	$tok_key = '' !== $token ? 'spl_tok_' . md5( $token ) : '';
	$done    = $tok_key ? get_transient( $tok_key ) : '';
	if ( $done ) {
		if ( $ajax ) {
			wp_send_json( array( 'result' => 'success', 'redirect' => $done ) );
		}
		wp_safe_redirect( $done );
		exit;
	}

	// Only these lines go into the order; whatever was in the cart comes back afterwards.
	$saved = WC()->cart->get_cart();
	WC()->cart->empty_cart( false );
	if ( $saved ) {
		add_action(
			'shutdown',
			static function () use ( $saved ) {
				WC()->cart->empty_cart( false );
				foreach ( $saved as $item ) {
					WC()->cart->add_to_cart( $item['product_id'], $item['quantity'], $item['variation_id'], $item['variation'] );
				}
				WC()->cart->calculate_totals();
			},
			0 // before WooCommerce saves the session (20)
		);
	}
	foreach ( $lines as $line ) {
		if ( ! safestore_pl_cart_line( $line ) ) {
			$fail( 'cart' );
		}
	}
	safestore_pl_mark_session( $page_id, $lines[0] );

	$state = isset( $_POST['billing_state'] ) ? wc_clean( wp_unslash( $_POST['billing_state'] ) ) : '';
	$thana = isset( $_POST['billing_city'] ) ? wc_clean( wp_unslash( $_POST['billing_city'] ) ) : '';
	$rate  = safestore_pl_courier_rate_id( $state, $thana );

	// What /checkout/ would post on its own. Payment: Cash on Delivery, or bKash /
	// Nagad ("Pay now") — then inc/mfs-payment.php validates and stores the
	// optional wallet / sender / TrxID fields (sft_mfs_*) exactly as on /checkout/.
	$pay                      = isset( $_POST['spl_payment'] ) ? sanitize_key( wp_unslash( $_POST['spl_payment'] ) ) : 'cod';
	$_POST['billing_country'] = 'BD';
	$_POST['payment_method']  = ( 'mfs' === $pay && safestore_pl_mfs_available() ) ? safestore_mfs_gateway_id() : 'cod';
	if ( '' !== $rate ) {
		$_POST['shipping_method'] = array( $rate );
	}
	if ( function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) && wc_terms_and_conditions_checkbox_enabled() ) {
		$_POST['terms']       = 'on'; // the form says ordering accepts the terms
		$_POST['terms-field'] = 1;
	}
	$_POST['woocommerce-process-checkout-nonce']    = wp_create_nonce( 'woocommerce-process_checkout' );
	$_REQUEST['woocommerce-process-checkout-nonce'] = $_POST['woocommerce-process-checkout-nonce'];
	// phpcs:enable

	add_action(
		'woocommerce_checkout_order_processed',
		static function ( $order_id ) use ( $ip_key, $tok_key ) {
			set_transient( $ip_key, (int) get_transient( $ip_key ) + 1, HOUR_IN_SECONDS );
			$order = wc_get_order( $order_id );
			if ( $tok_key && $order ) {
				set_transient( $tok_key, $order->get_checkout_order_received_url(), DAY_IN_SECONDS );
			}
		}
	);
	add_filter( 'woocommerce_add_error', 'safestore_pl_bn_notice' );
	if ( $ajax ) {
		add_filter( 'wp_doing_ajax', '__return_true' ); // WooCommerce answers JSON instead of redirecting
	}

	WC()->checkout()->process_checkout(); // success: redirect / JSON + exit

	$fail( 'cart' ); // only reached without JS when validation failed; the notices are shown on the form
}

/* -------------------------------------------------------------------------
 * Thank-you page in Bangla for orders placed on a landing page
 * ---------------------------------------------------------------------- */

add_action(
	'template_redirect',
	static function () {
		if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		$order = wc_get_order( absint( get_query_var( 'order-received' ) ) );
		$key   = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $order || ! hash_equals( $order->get_order_key(), $key ) || ! $order->get_meta( '_spl_landing_url' ) ) {
			return;
		}
		add_filter( 'gettext_safestore-minimal', 'safestore_pl_ty_bn', 10, 2 );
		add_filter(
			'ngettext_safestore-minimal',
			static function ( $translation, $single ) {
				return '%d item' === $single ? '%dটি পণ্য' : $translation;
			},
			10,
			2
		);
		add_filter(
			'woocommerce_order_get_payment_method_title',
			static function ( $title, $o ) {
				return 'cod' === $o->get_payment_method() ? 'ক্যাশ অন ডেলিভারি' : $title;
			},
			10,
			2
		);
	}
);

/** Bangla for the thank-you template's strings (woocommerce/checkout/thankyou.php, inc/order-received.php, inc/cod-message.php). */
function safestore_pl_ty_bn( $translation, $text ) {
	static $bn = null;
	if ( null === $bn ) {
		$bn = array(
			'Thank you! Your order has been received.'  => 'ধন্যবাদ! আপনার অর্ডার পেয়েছি।',
			'Thank you, %s! Your order is placed.'      => 'ধন্যবাদ, %s! আপনার অর্ডার হয়েছে।',
			'Thank you! Your order is placed.'          => 'ধন্যবাদ! আপনার অর্ডার হয়েছে।',
			'We will contact you shortly to confirm it. You pay when it arrives.' => 'শিগগির আমরা ফোন করে অর্ডারটি নিশ্চিত করব। পণ্য হাতে পেয়ে টাকা দেবেন।',
			'We will contact you shortly to confirm it.' => 'শিগগির আমরা ফোন করে অর্ডারটি নিশ্চিত করব।',
			'Order number'                              => 'অর্ডার নম্বর',
			'Date'                                      => 'তারিখ',
			'Total'                                     => 'মোট',
			'Payment'                                   => 'পেমেন্ট',
			'Save your order number — you will need it to track or ask about this order.' => 'অর্ডার নম্বরটি রেখে দিন — অর্ডার ট্র্যাক করতে বা কিছু জানতে লাগবে।',
			'What happens next'                         => 'এরপর যা হবে',
			'Pay on delivery'                           => 'ডেলিভারিতে দেবেন',
			'Amount to pay'                             => 'পরিশোধ করবেন',
			'Order placed'                              => 'অর্ডার হয়েছে',
			'(done)'                                    => '(সম্পন্ন)',
			'We confirm your order'                     => 'আমরা অর্ডার নিশ্চিত করব',
			'We call or WhatsApp you on %s. Please keep your phone on.' => '%s নম্বরে ফোন বা WhatsApp করব। ফোন খোলা রাখুন।',
			'We call or WhatsApp you. Please keep your phone on.' => 'আমরা ফোন বা WhatsApp করব। ফোন খোলা রাখুন।',
			'We ship your order'                        => 'আমরা পণ্য পাঠাব',
			'Packed and handed to the courier after confirmation.' => 'নিশ্চিত হওয়ার পর প্যাক করে কুরিয়ারে দেওয়া হবে।',
			'Pay %s cash on delivery'                   => 'ডেলিভারিতে %s ক্যাশ দিন',
			'Check the parcel, then pay the delivery person.' => 'পার্সেল দেখে নিয়ে তারপর ডেলিভারিম্যানকে টাকা দিন।',
			'Questions about your order? We are happy to help.' => 'অর্ডার নিয়ে কোনো প্রশ্ন? আমরা সাহায্য করতে প্রস্তুত।',
			'Wrong phone number (%s)? Message us now so we can reach you.' => 'মোবাইল নম্বর (%s) ভুল হয়েছে? এখনই আমাদের মেসেজ দিন।',
			'WhatsApp us'                               => 'WhatsApp-এ মেসেজ দিন',
			'Call %s'                                   => 'কল করুন %s',
			'Delivery address'                          => 'ডেলিভারি ঠিকানা',
			'Order summary'                             => 'অর্ডারের বিবরণ',
			'Qty %1$d × %2$s'                           => 'পরিমাণ %1$d × %2$s',
			'Subtotal'                                  => 'পণ্যের দাম',
			'Delivery charge'                           => 'ডেলিভারি চার্জ',
			'Discount'                                  => 'ছাড়',
			'Continue shopping'                         => 'আরও পণ্য দেখুন',
			'Track this order'                          => 'অর্ডার ট্র্যাক করুন',
			'Your note:'                                => 'আপনার নোট:',
			'Pay the courier in cash when your parcel arrives. Check the parcel first; if it is damaged or wrong, refuse it and WhatsApp us.' => 'পার্সেল এলে কুরিয়ারকে ক্যাশ দিন। আগে পার্সেল দেখে নিন; ভাঙা বা ভুল হলে নেবেন না, WhatsApp-এ জানান।',
			'<strong>Large order:</strong> we will call or WhatsApp you to confirm the items, sizes and delivery date before we ship. Buying for a company? Ask us for an invoice or bank transfer.' => '<strong>বড় অর্ডার:</strong> পাঠানোর আগে পণ্য, সাইজ ও ডেলিভারির তারিখ নিশ্চিত করতে ফোন বা WhatsApp করব। কোম্পানির জন্য কিনলে ইনভয়েস বা ব্যাংক ট্রান্সফারের কথা বলুন।',
		);
	}
	return isset( $bn[ $text ] ) ? $bn[ $text ] : $translation;
}

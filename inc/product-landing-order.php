<?php
/**
 * Product landing → order in one step (no cart page, no checkout page).
 *
 * The order form on page-templates/template-product-landing.php POSTs to the
 * page itself. inc/product-landing.php checks the product / size / quantity and
 * calls safestore_pl_place_order(), which puts only that line in the cart and
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

/** Fields of the landing order form: WooCommerce billing key => label, control, placeholder, error. */
function safestore_pl_form_fields() {
	return array(
		'billing_first_name' => array( 'label' => 'আপনার নাম', 'type' => 'text', 'auto' => 'name', 'ph' => 'যেমন: মো. রাকিব হাসান', 'err' => 'আপনার নাম লিখুন' ),
		'billing_phone'      => array( 'label' => 'মোবাইল নম্বর', 'type' => 'tel', 'auto' => 'tel', 'ph' => '০১XXXXXXXXX', 'err' => '১১ সংখ্যার মোবাইল নম্বর দিন, যেমন ০১৭১২৩৪৫৬৭৮' ),
		'billing_state'      => array( 'label' => 'জেলা', 'type' => 'select', 'auto' => 'address-level1', 'ph' => 'জেলা বেছে নিন', 'err' => 'জেলা বেছে নিন' ),
		'billing_city'       => array( 'label' => 'থানা / উপজেলা', 'type' => 'select', 'auto' => 'address-level2', 'ph' => 'আগে জেলা বেছে নিন', 'err' => 'থানা বা উপজেলা বেছে নিন' ),
		'billing_address_1'  => array( 'label' => 'পূর্ণ ঠিকানা', 'type' => 'textarea', 'auto' => 'street-address', 'ph' => 'বাড়ি / রোড / এলাকা', 'err' => 'বাড়ি, রোড ও এলাকার নাম লিখুন' ),
	);
}

/** WooCommerce BD state code => Bangla district name; Dhaka first, then Bangla order. Values posted stay BD-xx. */
function safestore_pl_districts() {
	return apply_filters(
		'safestore_pl_districts',
		array(
			'BD-13' => 'ঢাকা', 'BD-11' => 'কক্সবাজার', 'BD-26' => 'কিশোরগঞ্জ', 'BD-28' => 'কুড়িগ্রাম', 'BD-08' => 'কুমিল্লা',
			'BD-30' => 'কুষ্টিয়া', 'BD-29' => 'খাগড়াছড়ি', 'BD-27' => 'খুলনা', 'BD-19' => 'গাইবান্ধা', 'BD-18' => 'গাজীপুর',
			'BD-17' => 'গোপালগঞ্জ', 'BD-10' => 'চট্টগ্রাম', 'BD-09' => 'চাঁদপুর', 'BD-45' => 'চাঁপাইনবাবগঞ্জ', 'BD-12' => 'চুয়াডাঙ্গা',
			'BD-24' => 'জয়পুরহাট', 'BD-21' => 'জামালপুর', 'BD-25' => 'ঝালকাঠি', 'BD-23' => 'ঝিনাইদহ', 'BD-63' => 'টাঙ্গাইল',
			'BD-64' => 'ঠাকুরগাঁও', 'BD-14' => 'দিনাজপুর', 'BD-48' => 'নওগাঁ', 'BD-43' => 'নড়াইল', 'BD-42' => 'নরসিংদী',
			'BD-44' => 'নাটোর', 'BD-40' => 'নারায়ণগঞ্জ', 'BD-46' => 'নীলফামারী', 'BD-41' => 'নেত্রকোনা', 'BD-47' => 'নোয়াখালী',
			'BD-52' => 'পঞ্চগড়', 'BD-51' => 'পটুয়াখালী', 'BD-49' => 'পাবনা', 'BD-50' => 'পিরোজপুর', 'BD-15' => 'ফরিদপুর',
			'BD-16' => 'ফেনী', 'BD-03' => 'বগুড়া', 'BD-02' => 'বরগুনা', 'BD-06' => 'বরিশাল', 'BD-05' => 'বাগেরহাট',
			'BD-01' => 'বান্দরবান', 'BD-04' => 'ব্রাহ্মণবাড়িয়া', 'BD-07' => 'ভোলা', 'BD-34' => 'ময়মনসিংহ', 'BD-37' => 'মাগুরা',
			'BD-36' => 'মাদারীপুর', 'BD-33' => 'মানিকগঞ্জ', 'BD-35' => 'মুন্সিগঞ্জ', 'BD-39' => 'মেহেরপুর', 'BD-38' => 'মৌলভীবাজার',
			'BD-22' => 'যশোর', 'BD-55' => 'রংপুর', 'BD-56' => 'রাঙ্গামাটি', 'BD-53' => 'রাজবাড়ী', 'BD-54' => 'রাজশাহী',
			'BD-31' => 'লক্ষ্মীপুর', 'BD-32' => 'লালমনিরহাট', 'BD-62' => 'শরীয়তপুর', 'BD-57' => 'শেরপুর', 'BD-58' => 'সাতক্ষীরা',
			'BD-59' => 'সিরাজগঞ্জ', 'BD-60' => 'সিলেট', 'BD-61' => 'সুনামগঞ্জ', 'BD-20' => 'হবিগঞ্জ',
		)
	);
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
	$kg = (float) $product->get_weight();
	if ( $kg > 0 ) {
		$kg = (float) wc_get_weight( $kg, 'kg' );
	} elseif ( class_exists( 'SafeStoreBD_Shipping_Courier' ) ) {
		$kg = max( 0.0, (float) ( new SafeStoreBD_Shipping_Courier() )->get_option( 'default_weight' ) );
	}
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
 * Place the order for one checked cart line and end the request.
 *
 * @param int      $page_id Landing page.
 * @param array    $line    From safestore_pl_resolve_line().
 * @param bool     $ajax    JSON answer instead of redirects.
 * @param callable $fail    Sends the error for a code and exits.
 */
function safestore_pl_place_order( $page_id, $line, $ajax, $fail ) {
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

	// Only this line goes into the order; whatever was in the cart comes back afterwards.
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
	if ( ! safestore_pl_cart_line( $line ) ) {
		$fail( 'cart' );
	}
	safestore_pl_mark_session( $page_id, $line );

	$state = isset( $_POST['billing_state'] ) ? wc_clean( wp_unslash( $_POST['billing_state'] ) ) : '';
	$thana = isset( $_POST['billing_city'] ) ? wc_clean( wp_unslash( $_POST['billing_city'] ) ) : '';
	$rate  = safestore_pl_courier_rate_id( $state, $thana );

	// What /checkout/ would post on its own.
	$_POST['billing_country'] = 'BD';
	$_POST['payment_method']  = 'cod';
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

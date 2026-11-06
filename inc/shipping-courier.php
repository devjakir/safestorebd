<?php
/**
 * Steadfast Courier delivery charge — weight-based, by delivery lane.
 *
 * Lanes follow Steadfast's own rate card (pickup from Dhaka City):
 *   inside  — District Dhaka, any thana except the six suburban upazilas
 *   suburb  — Savar, Ashulia, Keraniganj, Dhamrai, Dohar, Nawabganj, and all
 *             of Gazipur and Narayanganj (optional price; charged as outside
 *             when not set)
 *   outside — every other district
 *
 * District is billing_state (BD-xx); the thana is billing_city, which the
 * checkout Thana / Upazila field (inc/checkout-thana.php) fills.
 *
 * The method class is in inc/class-safestorebd-shipping-courier.php; setup
 * steps for WooCommerce admin are in SHIPPING-SETUP.md.
 *
 * @package safestore-minimal
 */

defined( 'ABSPATH' ) || exit;

const SAFESTOREBD_COURIER_ID = 'safestorebd_courier';

/**
 * Default settings. Steadfast public rates were ৳75 / ৳105 / ৳135 for the
 * first kg and +৳20 per extra kg (Nov 2026); the store's own prices are
 * ৳70 inside Dhaka City and ৳135 outside.
 *
 * @return array<string, string>
 */
function safestorebd_courier_defaults() {
	return array(
		'title'          => __( 'Steadfast Courier', 'safestore-minimal' ),
		'inside_first'   => '70',
		'inside_extra'   => '20',
		'outside_first'  => '135',
		'outside_extra'  => '20',
		'suburb_first'   => '',
		'suburb_extra'   => '',
		'default_weight' => '0',
	);
}

/**
 * Billable weight: rounded up to whole kg, never below 1 kg. Grams under
 * 10 g past a whole kg are ignored (scale noise, float sums).
 *
 * @param float $kg Parcel weight in kg.
 * @return int
 */
function safestorebd_courier_billable_kg( $kg ) {
	return max( 1, (int) ceil( round( (float) $kg, 2 ) ) );
}

/**
 * The standard courier formula: first kg + every extra started kg.
 *
 * @param int   $billable_kg Whole kg, at least 1.
 * @param float $first       Price of the first kg.
 * @param float $extra       Price of each extra kg.
 * @return float
 */
function safestorebd_courier_charge( $billable_kg, $first, $extra ) {
	return (float) $first + max( 0, (int) $billable_kg - 1 ) * (float) $extra;
}

/**
 * Whether a Dhaka-district thana is one of the six upazilas outside the city
 * (Steadfast's "Dhaka sub-urban"). Spelling-tolerant, so "Keranigonj",
 * "South Keraniganj" or "Savar Upazila" all match.
 *
 * @param string $thana Thana name.
 * @return bool
 */
function safestorebd_courier_is_dhaka_suburb( $thana ) {
	$key = str_replace( 'gonj', 'ganj', strtolower( (string) preg_replace( '/[^a-z]/i', '', (string) $thana ) ) );
	if ( '' === $key ) {
		return false;
	}
	foreach ( array( 'savar', 'ashulia', 'keraniganj', 'dhamrai', 'dohar', 'nawabganj' ) as $suburb ) {
		if ( false !== strpos( $key, $suburb ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Delivery lane for a District (WooCommerce state code) and Thana.
 *
 * In Dhaka district every thana except the six suburban upazilas is Inside
 * Dhaka City — including "not picked yet", so the first price a Dhaka
 * customer sees is the city price.
 *
 * @param string $state WooCommerce state code, e.g. BD-13.
 * @param string $thana Thana as stored in billing_city / shipping city.
 * @return string inside | suburb | outside
 */
function safestorebd_courier_lane( $state, $thana ) {
	$state = strtoupper( trim( (string) $state ) );

	if ( 'BD-13' === $state ) {
		return safestorebd_courier_is_dhaka_suburb( $thana ) ? 'suburb' : 'inside';
	}
	if ( 'BD-18' === $state || 'BD-40' === $state ) { // Gazipur, Narayanganj.
		return 'suburb';
	}
	return 'outside';
}

/**
 * Customer-facing name of a lane.
 *
 * @param string $lane inside | suburb | outside.
 * @return string
 */
function safestorebd_courier_lane_label( $lane ) {
	switch ( $lane ) {
		case 'inside':
			return __( 'Inside Dhaka City', 'safestore-minimal' );
		case 'suburb':
			return __( 'Dhaka suburbs', 'safestore-minimal' );
		default:
			return __( 'Outside Dhaka City', 'safestore-minimal' );
	}
}

function safestorebd_courier_load_class() {
	if ( ! class_exists( 'SafeStoreBD_Shipping_Courier' ) && class_exists( 'WC_Shipping_Method' ) ) {
		require_once __DIR__ . '/class-safestorebd-shipping-courier.php';
	}
}
add_action( 'woocommerce_shipping_init', 'safestorebd_courier_load_class' );

add_filter(
	'woocommerce_shipping_methods',
	static function ( $methods ) {
		safestorebd_courier_load_class();
		if ( class_exists( 'SafeStoreBD_Shipping_Courier' ) ) {
			$methods[ SAFESTOREBD_COURIER_ID ] = 'SafeStoreBD_Shipping_Courier';
		}
		return $methods;
	}
);

/**
 * Give a checkout field WooCommerce's own update_totals_on_change class, so a
 * change re-prices the order straight away.
 *
 * @param array  $fields Checkout fields.
 * @param string $key    Billing field key.
 * @return array
 */
function safestorebd_courier_refresh_on_change( $fields, $key ) {
	if ( ! isset( $fields['billing'][ $key ] ) ) {
		return $fields;
	}
	$class = isset( $fields['billing'][ $key ]['class'] ) ? (array) $fields['billing'][ $key ]['class'] : array();
	if ( ! in_array( 'update_totals_on_change', $class, true ) ) {
		$class[] = 'update_totals_on_change';
	}
	$fields['billing'][ $key ]['class'] = $class;
	return $fields;
}

/**
 * Re-price the order the moment the delivery area changes.
 *
 * - District: WooCommerce only refreshes totals for an address field once
 *   every required address input is filled, so while Full Address was empty
 *   the summary kept the previous district's charge (Cumilla showing Dhaka's
 *   ৳80). Applies with any shipping method.
 * - Thana: decides Inside Dhaka City vs suburbs, so only once the courier is
 *   live. Runs after inc/checkout-thana.php builds the field (priority 30).
 */
add_filter(
	'woocommerce_checkout_fields',
	static function ( $fields ) {
		$fields = safestorebd_courier_refresh_on_change( $fields, 'billing_state' );
		if ( safestorebd_courier_is_live() ) {
			$fields = safestorebd_courier_refresh_on_change( $fields, 'billing_city' );
		}
		return $fields;
	},
	40
);

/*
 * ---------------------------------------------------------------------------
 * Site copy (shipping page, product page, meta description)
 * ---------------------------------------------------------------------------
 * Prices in copy are read from the courier settings, but only once the method
 * is switched on in a shipping zone. Until then every caller keeps its old
 * wording, so the site never advertises a price checkout does not charge.
 */

/**
 * Whether the courier method is enabled in at least one shipping zone.
 * Cached; the cache follows WooCommerce's shipping version and is cleared
 * whenever a zone method is added, removed or toggled.
 *
 * @return bool
 */
function safestorebd_courier_is_live() {
	static $live = null;
	if ( null !== $live ) {
		return $live;
	}
	if ( ! class_exists( 'WC_Shipping_Zones' ) || ! class_exists( 'WC_Cache_Helper' ) ) {
		return $live = false;
	}

	$version = WC_Cache_Helper::get_transient_version( 'shipping' );
	$cached  = get_transient( 'safestorebd_courier_live' );
	if ( is_array( $cached ) && isset( $cached['v'], $cached['live'] ) && $cached['v'] === $version ) {
		return $live = (bool) $cached['live'];
	}

	$live    = false;
	$zones   = WC_Shipping_Zones::get_zones( 'admin' );
	$zones[] = array( 'shipping_methods' => ( new WC_Shipping_Zone( 0 ) )->get_shipping_methods( false, 'admin' ) );

	foreach ( $zones as $zone ) {
		foreach ( (array) $zone['shipping_methods'] as $method ) {
			if ( SAFESTOREBD_COURIER_ID === $method->id && $method->is_enabled() ) {
				$live = true;
				break 2;
			}
		}
	}

	set_transient( 'safestorebd_courier_live', array( 'v' => $version, 'live' => $live ), DAY_IN_SECONDS );
	return $live;
}

function safestorebd_courier_flush_live_cache() {
	delete_transient( 'safestorebd_courier_live' );
}
add_action( 'woocommerce_shipping_zone_method_added', 'safestorebd_courier_flush_live_cache' );
add_action( 'woocommerce_shipping_zone_method_deleted', 'safestorebd_courier_flush_live_cache' );
add_action( 'woocommerce_shipping_zone_method_status_toggled', 'safestorebd_courier_flush_live_cache' );
add_action( 'woocommerce_delete_shipping_zone', 'safestorebd_courier_flush_live_cache' );

/**
 * Courier prices for site copy, or null while the method is not live.
 *
 * @return array|null See SafeStoreBD_Shipping_Courier::get_lane_rates().
 */
function safestorebd_courier_public_rates() {
	if ( ! safestorebd_courier_is_live() ) {
		return null;
	}
	safestorebd_courier_load_class();
	if ( ! class_exists( 'SafeStoreBD_Shipping_Courier' ) ) {
		return null;
	}
	return ( new SafeStoreBD_Shipping_Courier() )->get_lane_rates();
}

/**
 * "৳70" — whole taka without decimals, as in the rest of the site copy.
 *
 * @param float $amount Amount in taka.
 * @return string
 */
function safestorebd_courier_money( $amount ) {
	$amount = (float) $amount;
	return '৳' . number_format_i18n( $amount, floor( $amount ) === $amount ? 0 : 2 );
}

/**
 * "From ৳70 · +৳20 per extra kg" for one lane.
 *
 * @param array{first: float, extra: float} $rate Lane prices.
 * @return string
 */
function safestorebd_courier_from_text( $rate ) {
	if ( $rate['extra'] > 0 ) {
		/* translators: 1: first-kg price, 2: extra-kg price */
		return sprintf( __( 'From %1$s · +%2$s per extra kg', 'safestore-minimal' ), safestorebd_courier_money( $rate['first'] ), safestorebd_courier_money( $rate['extra'] ) );
	}
	/* translators: %s: first-kg price */
	return sprintf( __( 'From %s', 'safestore-minimal' ), safestorebd_courier_money( $rate['first'] ) );
}

/**
 * Shipping & Delivery page table (safestore_minimal_get_shipping_zones()).
 * Rows are matched by their label; anything else is left as it is.
 */
add_filter(
	'safestore_minimal_shipping_zones',
	static function ( $zones ) {
		$rates = safestorebd_courier_public_rates();
		if ( ! $rates || ! is_array( $zones ) ) {
			return $zones;
		}
		foreach ( $zones as $i => $row ) {
			$label = isset( $row['zone'] ) ? $row['zone'] : '';
			if ( __( 'Inside Dhaka', 'safestore-minimal' ) === $label ) {
				$zones[ $i ]['zone'] = __( 'Inside Dhaka City', 'safestore-minimal' );
				$zones[ $i ]['cost'] = safestorebd_courier_from_text( $rates['inside'] );
			} elseif ( __( 'Outside Dhaka', 'safestore-minimal' ) === $label ) {
				$zones[ $i ]['zone'] = __( 'Outside Dhaka City', 'safestore-minimal' );
				$zones[ $i ]['cost'] = safestorebd_courier_from_text( $rates['outside'] );
			}
		}
		return $zones;
	}
);

/**
 * Product page delivery options (inc/pdp-fulfillment.php): the "Dhaka" and
 * "Nationwide" chips and their detail lines.
 */
add_filter(
	'safestore_minimal_pdp_fulfillment_items',
	static function ( $items ) {
		$rates = safestorebd_courier_public_rates();
		if ( ! $rates || ! is_array( $items ) ) {
			return $items;
		}
		foreach ( $items as $i => $item ) {
			$id = isset( $item['id'] ) ? $item['id'] : '';
			if ( 'dhaka' === $id ) {
				$rate                  = $rates['inside'];
				$items[ $i ]['title']  = __( 'Courier — inside Dhaka City', 'safestore-minimal' );
				$items[ $i ]['hint']   = sprintf( __( 'From %s', 'safestore-minimal' ), safestorebd_courier_money( $rate['first'] ) );
				$items[ $i ]['detail'] = $rate['extra'] > 0
					/* translators: 1: first-kg price, 2: extra-kg price */
					? sprintf( __( 'Typical handover within ~24 hours. %1$s up to 1 kg, +%2$s per extra kg.', 'safestore-minimal' ), safestorebd_courier_money( $rate['first'] ), safestorebd_courier_money( $rate['extra'] ) )
					/* translators: %s: courier price */
					: sprintf( __( 'Typical handover within ~24 hours. %s per order.', 'safestore-minimal' ), safestorebd_courier_money( $rate['first'] ) );
			} elseif ( 'nationwide' === $id ) {
				$rate                  = $rates['outside'];
				$items[ $i ]['hint']   = sprintf( __( 'From %s', 'safestore-minimal' ), safestorebd_courier_money( $rate['first'] ) );
				$items[ $i ]['detail'] = $rate['extra'] > 0
					/* translators: 1: first-kg price, 2: extra-kg price */
					? sprintf( __( '2–3 days typical. %1$s up to 1 kg, +%2$s per extra kg.', 'safestore-minimal' ), safestorebd_courier_money( $rate['first'] ), safestorebd_courier_money( $rate['extra'] ) )
					/* translators: %s: courier price */
					: sprintf( __( '2–3 days typical. %s per order.', 'safestore-minimal' ), safestorebd_courier_money( $rate['first'] ) );
			}
		}
		return $items;
	}
);

/*
 * ---------------------------------------------------------------------------
 * Admin: say what the Weight field is used for, right next to it.
 * ---------------------------------------------------------------------------
 */
add_action(
	'woocommerce_product_options_dimensions',
	static function () {
		if ( ! safestorebd_courier_is_live() ) {
			return; // Flat rates ignore weight; don't claim otherwise.
		}
		safestorebd_courier_load_class();
		if ( ! class_exists( 'SafeStoreBD_Shipping_Courier' ) ) {
			return;
		}
		$method   = new SafeStoreBD_Shipping_Courier();
		$rates    = $method->get_lane_rates();
		$fallback = max( 0.0, (float) $method->get_option( 'default_weight' ) );
		$unit     = get_option( 'woocommerce_weight_unit', 'kg' );

		$text = sprintf(
			/* translators: 1: weight unit, 2: inside first-kg price, 3: outside first-kg price, 4: inside extra-kg price, 5: outside extra-kg price */
			__( 'Delivery charge uses this weight (%1$s, per unit): first 1 kg %2$s Inside Dhaka City / %3$s outside, then +%4$s / +%5$s per extra kg.', 'safestore-minimal' ),
			$unit,
			safestorebd_courier_money( $rates['inside']['first'] ),
			safestorebd_courier_money( $rates['outside']['first'] ),
			safestorebd_courier_money( $rates['inside']['extra'] ),
			safestorebd_courier_money( $rates['outside']['extra'] )
		);
		$text .= ' ' . ( $fallback > 0
			/* translators: %s: weight in kg */
			? sprintf( __( 'Left empty, each unit counts as %s kg.', 'safestore-minimal' ), wc_format_localized_decimal( $fallback ) )
			: __( 'Left empty, it adds no weight.', 'safestore-minimal' ) );

		printf( '<p class="form-field"><span class="description">%s</span></p>', esc_html( $text ) );
	}
);

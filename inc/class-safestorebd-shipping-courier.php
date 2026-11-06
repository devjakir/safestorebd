<?php
/**
 * Shipping method: Steadfast Courier, priced by parcel weight and delivery lane.
 *
 * Prices live in ONE place — WooCommerce → Settings → Shipping → Steadfast
 * Courier — and every shipping zone the method is added to uses them, so the
 * Dhaka zone and the rest-of-Bangladesh zone can never drift apart.
 *
 * Charge = first-kg price + (billable kg − 1) × extra-kg price, where billable
 * kg is the cart weight rounded up to whole kg (minimum 1). The lane comes from
 * District + Thana: see safestorebd_courier_lane().
 *
 * Loaded on woocommerce_shipping_init (inc/shipping-courier.php).
 *
 * @package safestore-minimal
 */

defined( 'ABSPATH' ) || exit;

class SafeStoreBD_Shipping_Courier extends WC_Shipping_Method {

	/**
	 * @param int $instance_id Shipping zone instance ID (0 = global settings).
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = SAFESTOREBD_COURIER_ID;
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Steadfast Courier', 'safestore-minimal' );
		$this->method_description = __( 'Weight-based courier charge: Inside Dhaka City, optional Dhaka suburbs, and Outside Dhaka City. Prices are set once under WooCommerce → Settings → Shipping → Steadfast Courier and apply in every zone this method is added to.', 'safestore-minimal' );
		$this->supports           = array( 'shipping-zones', 'settings' );

		$this->init();

		if ( ! $this->instance_id ) {
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		}
	}

	/**
	 * Load settings. Done in the constructor (like Flat rate) so the zone's
	 * enabled flag, set right after construction, is never overwritten.
	 */
	public function init() {
		$this->init_form_fields();
		$this->init_settings();

		$title            = trim( (string) $this->get_option( 'title' ) );
		$this->title      = '' !== $title ? $title : safestorebd_courier_defaults()['title'];
		$this->tax_status = $this->get_option( 'tax_status' );
	}

	public function init_form_fields() {
		$d = safestorebd_courier_defaults();

		$this->form_fields = array(
			'title'           => array(
				'title'       => __( 'Name at checkout', 'safestore-minimal' ),
				'type'        => 'text',
				'description' => __( 'Customers see this name followed by the delivery zone, e.g. “Steadfast Courier — Inside Dhaka City”.', 'safestore-minimal' ),
				'default'     => $d['title'],
				'desc_tip'    => true,
			),
			'tax_status'      => array(
				'title'   => __( 'Tax status', 'safestore-minimal' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => array(
					'taxable' => __( 'Taxable', 'safestore-minimal' ),
					'none'    => _x( 'None', 'Tax status', 'safestore-minimal' ),
				),
			),

			'inside_heading'  => array(
				'title'       => __( 'Inside Dhaka City', 'safestore-minimal' ),
				'type'        => 'title',
				'description' => __( 'District Dhaka with any thana except the six suburban upazilas below (so Mirpur, Uttara, Gulshan, Motijheel…). Also used while a Dhaka customer has not picked a thana yet.', 'safestore-minimal' ),
			),
			'inside_first'    => array(
				'title'   => __( 'First 1 kg (৳)', 'safestore-minimal' ),
				'type'    => 'price',
				'default' => $d['inside_first'],
			),
			'inside_extra'    => array(
				'title'   => __( 'Each extra kg (৳)', 'safestore-minimal' ),
				'type'    => 'price',
				'default' => $d['inside_extra'],
			),

			'outside_heading' => array(
				'title'       => __( 'Outside Dhaka City', 'safestore-minimal' ),
				'type'        => 'title',
				'description' => __( 'Every other district. Dhaka suburbs are charged this rate too, unless you set a suburbs rate below.', 'safestore-minimal' ),
			),
			'outside_first'   => array(
				'title'   => __( 'First 1 kg (৳)', 'safestore-minimal' ),
				'type'    => 'price',
				'default' => $d['outside_first'],
			),
			'outside_extra'   => array(
				'title'   => __( 'Each extra kg (৳)', 'safestore-minimal' ),
				'type'    => 'price',
				'default' => $d['outside_extra'],
			),

			'suburb_heading'  => array(
				'title'       => __( 'Dhaka suburbs (optional)', 'safestore-minimal' ),
				'type'        => 'title',
				'description' => __( 'Steadfast’s “Dhaka sub-urban” lane: Savar, Ashulia, Keraniganj, Dhamrai, Dohar and Nawabganj in Dhaka district, plus all of Gazipur and Narayanganj. Leave “First 1 kg” empty to charge them the Outside Dhaka City rate.', 'safestore-minimal' ),
			),
			'suburb_first'    => array(
				'title'   => __( 'First 1 kg (৳)', 'safestore-minimal' ),
				'type'    => 'price',
				'default' => $d['suburb_first'],
			),
			'suburb_extra'    => array(
				'title'       => __( 'Each extra kg (৳)', 'safestore-minimal' ),
				'type'        => 'price',
				'default'     => $d['suburb_extra'],
				'description' => __( 'Empty = same as Outside Dhaka City.', 'safestore-minimal' ),
			),

			'weight_heading'  => array(
				'title'       => __( 'Parcel weight', 'safestore-minimal' ),
				'type'        => 'title',
				'description' => __( 'Each product’s Weight (Product data → Shipping) × quantity, added up and rounded up to whole kg. Up to 1 kg pays only the first-kg price.', 'safestore-minimal' ),
			),
			'default_weight'  => array(
				'title'       => __( 'Products with no weight count as (kg)', 'safestore-minimal' ),
				'type'        => 'decimal',
				'default'     => $d['default_weight'],
				'description' => __( 'Per unit. 0 = they add no weight, so they never raise the charge above the first-kg price.', 'safestore-minimal' ),
			),
		);
	}

	/**
	 * A price option as a non-negative number, or null when left empty.
	 *
	 * @param string $key Option key.
	 * @return float|null
	 */
	protected function price_option( $key ) {
		$value = $this->get_option( $key );
		if ( null === $value || '' === trim( (string) $value ) ) {
			return null;
		}
		return max( 0.0, (float) $value );
	}

	/**
	 * A required price: an emptied Inside / Outside box falls back to the
	 * default instead of silently becoming free delivery.
	 *
	 * @param string $key Option key.
	 * @return float
	 */
	protected function required_price( $key ) {
		$value = $this->price_option( $key );
		return null === $value ? (float) safestorebd_courier_defaults()[ $key ] : $value;
	}

	/**
	 * The configured prices per lane. 'suburb' is null when it is not set
	 * (those addresses then pay the outside rate).
	 *
	 * @return array{inside: array{first: float, extra: float}, outside: array{first: float, extra: float}, suburb: array{first: float, extra: float}|null}
	 */
	public function get_lane_rates() {
		$outside = array(
			'first' => $this->required_price( 'outside_first' ),
			'extra' => $this->required_price( 'outside_extra' ),
		);

		$suburb_first = $this->price_option( 'suburb_first' );
		$suburb_extra = $this->price_option( 'suburb_extra' );

		return array(
			'inside'  => array(
				'first' => $this->required_price( 'inside_first' ),
				'extra' => $this->required_price( 'inside_extra' ),
			),
			'outside' => $outside,
			'suburb'  => null === $suburb_first ? null : array(
				'first' => $suburb_first,
				'extra' => null === $suburb_extra ? $outside['extra'] : $suburb_extra,
			),
		);
	}

	/**
	 * Total weight of the shippable items in a package, in kg.
	 *
	 * @param array $package WooCommerce shipping package.
	 * @return float
	 */
	public function get_package_weight( $package ) {
		$fallback = max( 0.0, (float) $this->get_option( 'default_weight' ) );
		$total    = 0.0;

		foreach ( isset( $package['contents'] ) ? (array) $package['contents'] : array() as $item ) {
			$product = isset( $item['data'] ) ? $item['data'] : null;
			if ( ! $product instanceof WC_Product || ! $product->needs_shipping() ) {
				continue;
			}
			$weight   = (float) $product->get_weight();
			$per_unit = $weight > 0 ? (float) wc_get_weight( $weight, 'kg' ) : $fallback;
			$total   += $per_unit * max( 0, (int) $item['quantity'] );
		}

		return $total;
	}

	/**
	 * @param array $package WooCommerce shipping package.
	 */
	public function calculate_shipping( $package = array() ) {
		$destination = isset( $package['destination'] ) ? (array) $package['destination'] : array();
		$lane        = safestorebd_courier_lane(
			isset( $destination['state'] ) ? $destination['state'] : '',
			isset( $destination['city'] ) ? $destination['city'] : ''
		);

		$rates = $this->get_lane_rates();
		if ( 'suburb' === $lane && null === $rates['suburb'] ) {
			$lane = 'outside';
		}

		$weight   = $this->get_package_weight( $package );
		$billable = safestorebd_courier_billable_kg( $weight );
		$cost     = safestorebd_courier_charge( $billable, $rates[ $lane ]['first'], $rates[ $lane ]['extra'] );

		$zone  = safestorebd_courier_lane_label( $lane );
		$label = $this->title . ' — ' . $zone;
		if ( $billable > 1 ) {
			/* translators: 1: method name and zone, 2: billable weight in whole kg */
			$label = sprintf( __( '%1$s, %2$d kg', 'safestore-minimal' ), $label, $billable );
		}

		$rate = array(
			'id'        => $this->get_rate_id(),
			'label'     => $label,
			'cost'      => $cost,
			'package'   => $package,
			// Shown on the order's shipping line in admin — book the parcel with this weight.
			'meta_data' => array(
				__( 'Delivery zone', 'safestore-minimal' )   => $zone,
				__( 'Billable weight', 'safestore-minimal' ) => $billable . ' kg',
			),
		);

		/**
		 * Filters the courier rate before it is offered, e.g. for a free-delivery promo.
		 *
		 * @param array  $rate    Arguments for WC_Shipping_Method::add_rate().
		 * @param string $lane    inside | suburb | outside.
		 * @param float  $weight  Parcel weight in kg (before rounding).
		 * @param array  $package Shipping package.
		 */
		$this->add_rate( apply_filters( 'safestorebd_courier_rate', $rate, $lane, $weight, $package ) );
	}
}

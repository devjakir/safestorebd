<?php
/**
 * Checkout: Thana / Upazila dropdown that follows the District field.
 *
 * The field is WooCommerce's own billing_city, so the thana is stored on the
 * order as the address city and shows up in the admin order screen, emails,
 * invoices and courier exports without any extra meta. The options come from
 * inc/bd-thanas-data.php, keyed by the BD-xx code the District select posts.
 *
 * Also hides the empty "additional information" card that WooCommerce still
 * prints after the order-notes field is removed.
 *
 * @package safestore-minimal
 */

defined( 'ABSPATH' ) || exit;

/**
 * District code => list of thana / upazila names.
 *
 * @return array<string, string[]>
 */
function safestorebd_bd_thanas() {
	static $thanas = null;
	if ( null === $thanas ) {
		$thanas = require __DIR__ . '/bd-thanas-data.php';
		if ( ! is_array( $thanas ) ) {
			$thanas = array();
		}
	}
	return $thanas;
}

/**
 * Thanas for one district code; empty when the code is unknown.
 *
 * @param string $district BD-xx state code.
 * @return string[]
 */
function safestorebd_thanas_for( $district ) {
	$thanas = safestorebd_bd_thanas();
	return isset( $thanas[ $district ] ) ? $thanas[ $district ] : array();
}

function safestorebd_thana_i18n() {
	return array(
		'label'         => __( 'Thana / Upazila', 'safestore-minimal' ),
		'choose'        => __( 'Select thana / upazila', 'safestore-minimal' ),
		'districtFirst' => __( 'Select your district first', 'safestore-minimal' ),
		'invalid'       => __( 'Please choose a thana / upazila from the list for your district.', 'safestore-minimal' ),
	);
}

/**
 * Turn billing_city into the thana select. Runs after the theme's own
 * checkout field filters (priorities 10 and 20 in functions.php).
 */
add_filter(
	'woocommerce_checkout_fields',
	static function ( $fields ) {
		if ( ! isset( $fields['billing'] ) || ! isset( $fields['billing']['billing_state'] ) ) {
			return $fields;
		}

		$i18n     = safestorebd_thana_i18n();
		$district = function_exists( 'WC' ) && WC()->checkout() ? (string) WC()->checkout()->get_value( 'billing_state' ) : '';
		$list     = safestorebd_thanas_for( $district );

		// The blank option must exist even with no district picked:
		// woocommerce_form_field() prints nothing for a select with no options.
		$options = array( '' => $list ? $i18n['choose'] : $i18n['districtFirst'] );
		foreach ( $list as $thana ) {
			$options[ $thana ] = $thana;
		}

		$fields['billing']['billing_city'] = array(
			'type'              => 'select',
			'label'             => $i18n['label'],
			'required'          => true,
			// No "address-field" class: that would make every thana change
			// re-run the order-review AJAX, and shipping does not depend on it.
			'class'             => array( 'form-row-wide', 'sft-thana-field' ),
			'input_class'       => array( 'sft-thana-select' ),
			'priority'          => 55,
			'autocomplete'      => 'address-level2',
			'placeholder'       => $options[''],
			'options'           => $options,
			'custom_attributes' => $list ? array() : array( 'disabled' => 'disabled' ),
		);

		return $fields;
	},
	30
);

/**
 * Keep WooCommerce's locale script (address-i18n.js) from relabelling the
 * field "Town / City" if it ever runs for the billing form.
 */
add_filter(
	'woocommerce_get_country_locale',
	static function ( $locale ) {
		$i18n                        = safestorebd_thana_i18n();
		$locale['BD']['city']        = array(
			'label'    => $i18n['label'],
			'required' => true,
		);
		$locale['BD']['state']['label'] = __( 'District', 'safestore-minimal' );
		return $locale;
	}
);

/**
 * The posted thana must belong to the posted district.
 */
add_action(
	'woocommerce_after_checkout_validation',
	static function ( $data, $errors ) {
		$thana = isset( $data['billing_city'] ) ? (string) $data['billing_city'] : '';
		if ( '' === $thana ) {
			return; // WooCommerce already reports the empty required field.
		}
		$list = safestorebd_thanas_for( isset( $data['billing_state'] ) ? (string) $data['billing_state'] : '' );
		if ( $list && ! in_array( $thana, $list, true ) ) {
			$i18n = safestorebd_thana_i18n();
			$errors->add( 'billing_city_validation', $i18n['invalid'], array( 'id' => 'billing_city' ) );
		}
	},
	10,
	2
);

function safestorebd_checkout_thana_enqueue() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) ) {
		return;
	}

	$path = get_template_directory() . '/js/checkout-thana.js';
	if ( ! file_exists( $path ) ) {
		return;
	}

	$deps = array( 'jquery' );
	if ( wp_script_is( 'selectWoo', 'registered' ) ) {
		$deps[] = 'selectWoo';
	}

	wp_enqueue_script(
		'safestore-checkout-thana',
		get_template_directory_uri() . '/js/checkout-thana.js',
		$deps,
		(string) filemtime( $path ),
		function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true
	);

	wp_localize_script(
		'safestore-checkout-thana',
		'sftThana',
		array(
			'map'  => safestorebd_bd_thanas(),
			'i18n' => safestorebd_thana_i18n(),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'safestorebd_checkout_thana_enqueue', 20 );

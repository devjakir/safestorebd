<?php
/**
 * Product origin badge — Made in Bangladesh.
 *
 * Admin: checkbox on Product Data → General (`_safestore_origin_bd`).
 * Storefront: compact loop pill + PDP trust line. Unchecked products show
 * nothing by default; `safestore_product_origin` can opt in other origins.
 *
 * @package safestore-minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SAFESTORE_ORIGIN_META = '_safestore_origin_bd';

/**
 * Whether a product is marked Made in Bangladesh.
 *
 * @param WC_Product|int|null $product Product, ID, or current loop product.
 * @return bool
 */
function safestore_product_is_made_in_bd( $product = null ) {
	$product = safestore_origin_resolve_product( $product );
	if ( ! $product ) {
		return false;
	}

	return 'yes' === $product->get_meta( SAFESTORE_ORIGIN_META );
}

/**
 * Origin slug for a product: `bd` when the checkbox is on, otherwise empty
 * (or a filter-provided value such as `imported`).
 *
 * @param WC_Product|int|null $product Product, ID, or current loop product.
 * @return string
 */
function safestore_product_origin( $product = null ) {
	$product = safestore_origin_resolve_product( $product );
	if ( ! $product ) {
		return '';
	}

	$origin = safestore_product_is_made_in_bd( $product ) ? 'bd' : '';

	/**
	 * Filter the origin slug shown on cards / PDP.
	 * Return '' to hide, or e.g. 'imported' for a China/Imported badge.
	 *
	 * @param string     $origin  Origin slug.
	 * @param WC_Product $product Product.
	 */
	return (string) apply_filters( 'safestore_product_origin', $origin, $product );
}

/**
 * @param WC_Product|int|null $product Product or ID.
 * @return WC_Product|null
 */
function safestore_origin_resolve_product( $product = null ) {
	if ( $product instanceof WC_Product ) {
		return $product;
	}

	if ( is_numeric( $product ) && $product > 0 && function_exists( 'wc_get_product' ) ) {
		$resolved = wc_get_product( (int) $product );
		return $resolved instanceof WC_Product ? $resolved : null;
	}

	global $product;
	return $product instanceof WC_Product ? $product : null;
}

/**
 * Product Data → General: Made in Bangladesh checkbox.
 */
function safestore_origin_admin_field() {
	if ( ! function_exists( 'woocommerce_wp_checkbox' ) ) {
		return;
	}

	echo '<div class="options_group safestore-origin-field">';

	woocommerce_wp_checkbox(
		array(
			'id'          => SAFESTORE_ORIGIN_META,
			'label'       => __( 'Made in Bangladesh', 'safestore-minimal' ),
			'description' => __( 'Show a Made in Bangladesh origin badge on shop cards and the product page.', 'safestore-minimal' ),
			'desc_tip'    => true,
		)
	);

	wp_nonce_field( 'safestore_save_origin_bd', 'safestore_origin_bd_nonce' );

	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'safestore_origin_admin_field' );

/**
 * Persist `_safestore_origin_bd` as yes/no.
 *
 * @param int $post_id Product ID.
 */
function safestore_origin_save_field( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['safestore_origin_bd_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['safestore_origin_bd_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'safestore_save_origin_bd' ) ) {
		return;
	}

	$raw   = isset( $_POST[ SAFESTORE_ORIGIN_META ] ) ? sanitize_text_field( wp_unslash( $_POST[ SAFESTORE_ORIGIN_META ] ) ) : '';
	$value = in_array( $raw, array( 'yes', '1' ), true ) ? 'yes' : 'no';

	update_post_meta( $post_id, SAFESTORE_ORIGIN_META, $value );
}
add_action( 'woocommerce_process_product_meta', 'safestore_origin_save_field' );

/**
 * Shop / loop card badge. Hooked for default WC loops; the theme card also
 * calls this directly because it does not fire title hooks.
 */
function safestore_origin_badge_loop() {
	$origin = safestore_product_origin();
	if ( 'bd' !== $origin ) {
		return;
	}

	echo '<span class="origin-badge badge-bd">';
	echo '<span class="origin-badge__flag" aria-hidden="true">🇧🇩</span> ';
	echo esc_html__( 'Made in BD', 'safestore-minimal' );
	echo '</span>';
}
add_action( 'woocommerce_after_shop_loop_item_title', 'safestore_origin_badge_loop', 6 );

/**
 * Single product trust line — after price (7), before rating (11).
 */
function safestore_origin_badge_single() {
	$origin = safestore_product_origin();
	if ( 'bd' !== $origin ) {
		return;
	}

	echo '<div class="single-origin-badge bd">';
	echo '<span>' . esc_html__( 'Origin:', 'safestore-minimal' ) . '</span> ';
	echo esc_html__( 'Bangladesh', 'safestore-minimal' );
	echo ' <span class="single-origin-badge__flag" aria-hidden="true">🇧🇩</span>';
	echo '</div>';
}
add_action( 'woocommerce_single_product_summary', 'safestore_origin_badge_single', 8 );

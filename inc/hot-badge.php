<?php
// HOT product badge — "Hot product" checkbox in Product Data → General, shown on shop cards and the product gallery.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SAFESTORE_HOT_META = '_safestore_hot';

// Whether a product is marked hot; `safestore_product_is_hot` can switch it on/off programmatically.
function safestore_product_is_hot( $product = null ) {
	$product = function_exists( 'safestore_origin_resolve_product' ) ? safestore_origin_resolve_product( $product ) : null;
	if ( ! $product ) {
		return false;
	}

	$is_hot = 'yes' === $product->get_meta( SAFESTORE_HOT_META );

	return (bool) apply_filters( 'safestore_product_is_hot', $is_hot, $product );
}

// Badge markup shared by cards and the product page.
function safestore_hot_badge_html( $extra_class = '' ) {
	// Heroicons "fire" (mini, MIT).
	$icon = '<svg class="sft-hot-badge__icon" viewBox="0 0 20 20" width="12" height="12" aria-hidden="true" focusable="false"><path fill="currentColor" fill-rule="evenodd" d="M13.5 4.938a7 7 0 1 1-9.006 1.737c.202-.257.59-.218.793.039.278.352.594.672.943.954.332.269.786-.049.773-.476a5.977 5.977 0 0 1 .572-2.759 6.026 6.026 0 0 1 2.486-2.665c.247-.14.55-.016.677.238A6.967 6.967 0 0 0 13.5 4.938ZM14 12a4 4 0 0 1-4 4c-1.913 0-3.52-1.398-3.91-3.182-.093-.429.44-.643.814-.413a4.043 4.043 0 0 0 1.601.564c.303.038.531-.24.51-.544a5.975 5.975 0 0 1 1.315-4.192.447.447 0 0 1 .431-.16A4.001 4.001 0 0 1 14 12Z" clip-rule="evenodd"/></svg>';

	return sprintf(
		'<span class="sft-hot-badge%1$s">%2$s<span class="sft-hot-badge__text">%3$s</span></span>',
		$extra_class ? ' ' . esc_attr( $extra_class ) : '',
		$icon,
		esc_html__( 'HOT', 'safestore-minimal' )
	);
}

function safestore_hot_admin_field() {
	if ( ! function_exists( 'woocommerce_wp_checkbox' ) ) {
		return;
	}

	echo '<div class="options_group safestore-hot-field">';

	woocommerce_wp_checkbox(
		array(
			'id'          => SAFESTORE_HOT_META,
			'label'       => __( 'Hot product', 'safestore-minimal' ),
			'description' => __( 'Show a HOT badge on shop cards and the product page.', 'safestore-minimal' ),
			'desc_tip'    => true,
		)
	);

	wp_nonce_field( 'safestore_save_hot', 'safestore_hot_nonce' );

	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'safestore_hot_admin_field' );

function safestore_hot_save_field( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['safestore_hot_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['safestore_hot_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'safestore_save_hot' ) ) {
		return;
	}

	$raw   = isset( $_POST[ SAFESTORE_HOT_META ] ) ? sanitize_text_field( wp_unslash( $_POST[ SAFESTORE_HOT_META ] ) ) : '';
	$value = in_array( $raw, array( 'yes', '1' ), true ) ? 'yes' : 'no';

	update_post_meta( $post_id, SAFESTORE_HOT_META, $value );
}
add_action( 'woocommerce_process_product_meta', 'safestore_hot_save_field' );

// `sft-is-hot` on the card <li> and the PDP wrapper lets CSS make room for the badge.
function safestore_hot_post_class( $classes, $product ) {
	if ( $product instanceof WC_Product && safestore_product_is_hot( $product ) ) {
		$classes[] = 'sft-is-hot';
	}
	return $classes;
}
add_filter( 'woocommerce_post_class', 'safestore_hot_post_class', 10, 2 );

// Product page: badge over the gallery, printed just before WooCommerce's sale flash (10).
function safestore_hot_badge_single() {
	if ( ! safestore_product_is_hot() ) {
		return;
	}
	echo safestore_hot_badge_html( 'sft-hot-badge--pdp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'woocommerce_before_single_product_summary', 'safestore_hot_badge_single', 9 );

<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function safestore_minimal_products_comments_open( $open, $post_id ) {
	if ( 'product' === get_post_type( $post_id ) ) {
		return true;
	}

	return $open;
}
add_filter( 'comments_open', 'safestore_minimal_products_comments_open', 20, 2 );

function safestore_minimal_restore_reviews_tab( $tabs ) {
	if ( isset( $tabs['reviews'] ) ) {
		return $tabs;
	}

	global $product;

	$count = ( $product instanceof WC_Product ) ? $product->get_review_count() : 0;

	$tabs['reviews'] = array(
		/* translators: %d: number of reviews on the product. */
		'title'    => sprintf( __( 'Reviews (%d)', 'safestore-minimal' ), $count ),
		'priority' => 40,
		'callback' => 'comments_template',
	);

	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'safestore_minimal_restore_reviews_tab', 98 );

<?php
/**
 * Product reviews — keep the WooCommerce Reviews tab available on the PDP.
 *
 * Ported from the live Code Snippets entry "Re-add WooCommerce Reviews tab
 * (custom theme)". Once this file is deployed that snippet MUST be deactivated
 * in wp-admin → Snippets, or both copies run and the tab renders twice.
 * See SNIPPETS-MIGRATION.md.
 *
 * Why the tab went missing: WooCommerce adds it in
 * woocommerce_default_product_tabs() (filter priority 10) only when
 * comments_open() is true. Imported products carry comment_status = closed, so
 * the tab never gets created. safestore_minimal_products_comments_open() is the
 * actual fix; safestore_minimal_restore_reviews_tab() is a late fallback for the
 * case where something else strips the tab after core has added it.
 *
 * Settings that stay in the database (not code): WooCommerce → Settings →
 * Products → Reviews (enable reviews, star ratings, verified-owner rules).
 *
 * @package SafeStore_Minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force comments open on products so reviews can be read and submitted.
 *
 * Products only — posts and pages keep whatever wp-admin says.
 *
 * @param bool $open    Whether comments are open.
 * @param int  $post_id Post ID.
 * @return bool
 */
function safestore_minimal_products_comments_open( $open, $post_id ) {
	if ( 'product' === get_post_type( $post_id ) ) {
		return true;
	}

	return $open;
}
add_filter( 'comments_open', 'safestore_minimal_products_comments_open', 20, 2 );

/**
 * Re-add the Reviews tab if it is missing by the time the tab list is built.
 *
 * Normally a no-op — core has already added it once comments_open() is true.
 *
 * Filter priority 98 matters: WooCommerce registers woocommerce_sort_product_tabs
 * at 99 (wc-template-hooks.php), so anything added at 99 or later lands after the
 * sort and its 'priority' value is ignored. 98 keeps the tab inside the sort.
 * The theme's safestore_minimal_product_tabs_labels also sits at 98 — harmless,
 * it only rewrites titles on keys this function does not touch.
 *
 * Tab priority 40 mirrors the live snippet. Core uses 30; either value sorts
 * the tab last, after description (10), specifications (20) and delivery (25).
 *
 * @param array<string, array<string, mixed>> $tabs Tabs.
 * @return array<string, array<string, mixed>>
 */
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

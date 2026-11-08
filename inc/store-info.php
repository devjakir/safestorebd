<?php
/**
 * Store facts used in several templates — one source of truth.
 *
 *  - Opening hours: safestore_store_hours(). Override with
 *    define( 'SFT_STORE_HOURS', '…' ); in wp-config.php or the
 *    `safestore_store_hours` filter.
 *  - Social profile URLs: safestore_social_url( 'linkedin' ). Override with
 *    the `safestore_social_urls` filter.
 *
 * @package safestore-minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** "Sat–Thu, 9am–8pm (Friday closed)". */
function safestore_store_hours() {
	$hours = defined( 'SFT_STORE_HOURS' ) ? (string) SFT_STORE_HOURS : __( 'Sat–Thu, 9am–8pm (Friday closed)', 'safestore-minimal' );
	return (string) apply_filters( 'safestore_store_hours', $hours );
}

/** Official profiles (footer icons + Organization schema sameAs). */
function safestore_social_urls() {
	return (array) apply_filters(
		'safestore_social_urls',
		array(
			'facebook'  => 'https://www.facebook.com/safestorebd',
			'x'         => 'https://x.com/safestorebd',
			'instagram' => 'https://www.instagram.com/safestorebd/',
			'pinterest' => 'https://www.pinterest.com/safestorebd/',
			'linkedin'  => 'https://www.linkedin.com/company/safestorebd/',
			'youtube'   => 'https://www.youtube.com/@SafeStoreBD',
		)
	);
}

function safestore_social_url( $key ) {
	$urls = safestore_social_urls();
	return isset( $urls[ $key ] ) ? (string) $urls[ $key ] : '';
}

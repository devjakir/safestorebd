<?php
/**
 * Product content hygiene for SEO (render-time only; the stored content is
 * not changed).
 *
 *  - One H1 per product page: the product title. Any <h1> inside the
 *    description / short description becomes <h2>.
 *  - Strips attributes left behind by copy-pasting from ChatGPT
 *    (data-start, data-end, data-section-id, data-is-last-node,
 *    class="PDq2pG_selectionAnchorContainer" …). They add weight and noise
 *    to the HTML Google parses.
 *
 * @package safestore-minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clean one chunk of product HTML.
 *
 * @param string $html Content.
 * @return string
 */
function safestore_seo_clean_product_html( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}

	// <h1 …> → <h2 …> (the product title is the page's only H1).
	if ( false !== stripos( $html, '<h1' ) ) {
		$html = preg_replace( '#<h1(\s[^>]*)?>#i', '<h2$1>', $html );
		$html = preg_replace( '#</h1\s*>#i', '</h2>', $html );
	}

	// Copy-paste residue from the ChatGPT web UI.
	if ( false !== strpos( $html, 'data-start' ) || false !== strpos( $html, 'data-section-id' ) || false !== strpos( $html, '_selection' ) ) {
		$html = preg_replace( '#\sdata-(?:start|end|section-id|is-last-node|is-only-node)="[^"]*"#i', '', $html );
		// Generated class names such as PDq2pG_selectionAnchorContainer / TyagGW_tableContainer.
		$html = preg_replace( '#\sclass="[A-Za-z0-9]{6}_[A-Za-z]+(?:Container|Anchor[A-Za-z]*)"#', '', $html );
	}

	return $html;
}

add_filter(
	'the_content',
	static function ( $content ) {
		return ( function_exists( 'is_product' ) && is_product() ) ? safestore_seo_clean_product_html( $content ) : $content;
	},
	30
);

add_filter( 'woocommerce_short_description', 'safestore_seo_clean_product_html', 30 );

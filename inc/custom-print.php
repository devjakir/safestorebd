<?php

// Custom logo / name printing on vests and helmets: quote-based, so nothing here touches the cart.
// Shown on vest + helmet category pages, their product descriptions, and the Bulk Orders #custom-print block.

defined( 'ABSPATH' ) || exit;

/** Category slugs (singular + plural drift) that can be printed, by kind. */
function safestore_custom_print_slugs() {
	$slugs = array(
		'vest'   => array( 'safety-vest', 'safety-vests' ),
		'helmet' => array( 'protective-helmet', 'protective-helmets' ),
	);
	return (array) apply_filters( 'safestore_custom_print_slugs', $slugs );
}

/** 'vest' | 'helmet' | '' for a product_cat term. */
function safestore_custom_print_kind_for_term( $term ) {
	if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) {
		return '';
	}
	foreach ( safestore_custom_print_slugs() as $kind => $slugs ) {
		if ( in_array( $term->slug, (array) $slugs, true ) ) {
			return (string) $kind;
		}
	}
	return '';
}

/** 'vest' | 'helmet' | '' for a product. */
function safestore_custom_print_kind_for_product( $product_id ) {
	foreach ( safestore_custom_print_slugs() as $kind => $slugs ) {
		if ( has_term( (array) $slugs, 'product_cat', $product_id ) ) {
			return (string) $kind;
		}
	}
	return '';
}

/** Bulk Orders page, scrolled to the custom print block. */
function safestore_custom_print_url() {
	$page = get_page_by_path( 'bulk-orders' );
	$url  = $page ? get_permalink( $page ) : home_url( '/bulk-orders/' );
	return $url . '#custom-print';
}

/** What can be printed, per kind. */
function safestore_custom_print_label( $kind ) {
	return 'helmet' === $kind
		? __( 'your text or logo', 'safestore-minimal' )
		: __( 'your logo and company name', 'safestore-minimal' );
}

/** 1. Category page: one line under the intro. Called from woocommerce.php. */
function safestore_custom_print_category_note( $term ) {
	$kind = safestore_custom_print_kind_for_term( $term );
	if ( '' === $kind ) {
		return;
	}
	$text = 'helmet' === $kind
		? __( 'Need helmets with your text or logo printed on them?', 'safestore-minimal' )
		: __( 'Need vests with your logo and company name printed on them?', 'safestore-minimal' );
	?>
	<p class="sft-print-note">
		<?php echo esc_html( $text ); ?>
		<a href="<?php echo esc_url( safestore_custom_print_url() ); ?>"><?php esc_html_e( 'Custom printing', 'safestore-minimal' ); ?> &rarr;</a>
	</p>
	<?php
}

/** 2. Product page: section appended to the description tab. */
function safestore_custom_print_description_section( $content ) {
	if ( ! function_exists( 'is_product' ) || ! is_product() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$product_id = get_the_ID();
	if ( ! $product_id || (int) get_queried_object_id() !== (int) $product_id ) {
		return $content;
	}
	$kind = safestore_custom_print_kind_for_product( $product_id );
	if ( '' === $kind ) {
		return $content;
	}

	$wa_text = sprintf(
		/* translators: %s: product name */
		__( 'Hello SafeStoreBD, I need custom printing on: %s', 'safestore-minimal' ),
		get_the_title( $product_id )
	);
	$wa_href = function_exists( 'safestore_primary_wa_link' ) ? safestore_primary_wa_link( $wa_text ) : '';

	$intro = sprintf(
		/* translators: %s: what gets printed, e.g. "your logo and company name" */
		__( 'We can supply this item with %s printed on it. It is a made-to-order service, priced by quotation.', 'safestore-minimal' ),
		safestore_custom_print_label( $kind )
	);

	ob_start();
	?>
	<div class="sft-print-section" id="custom-print">
		<h3><?php esc_html_e( 'Custom logo and name printing', 'safestore-minimal' ); ?></h3>
		<p><?php echo esc_html( $intro ); ?></p>
		<ul>
			<li><?php esc_html_e( 'Send us your logo or the text, the quantity and the colour you want.', 'safestore-minimal' ); ?></li>
			<li><?php esc_html_e( 'We reply with the price, the minimum quantity and the delivery time.', 'safestore-minimal' ); ?></li>
			<li><?php esc_html_e( 'Printing starts after you approve the design.', 'safestore-minimal' ); ?></li>
		</ul>
		<p class="sft-print-section__links">
			<?php if ( '' !== $wa_href ) : ?>
				<a href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Send your logo on WhatsApp', 'safestore-minimal' ); ?></a>
				<span aria-hidden="true">&middot;</span>
			<?php endif; ?>
			<a href="<?php echo esc_url( safestore_custom_print_url() ); ?>"><?php esc_html_e( 'Request a quotation', 'safestore-minimal' ); ?></a>
		</p>
	</div>
	<?php
	return $content . ob_get_clean();
}
add_filter( 'the_content', 'safestore_custom_print_description_section', 30 );

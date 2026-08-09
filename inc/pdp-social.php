<?php
/**
 * PDP social support, action bar (compare / wishlist / share), and OG meta.
 *
 * Lightweight: inline SVG, one tiny deferred script, no third-party SDKs.
 *
 * @package SafeStore_Minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Brand / support profile URLs used on the product page.
 *
 * @return array{messenger:string,telegram:string,facebook:string,instagram:string,pinterest:string}
 */
function safestore_pdp_social_profiles() {
	$profiles = array(
		'messenger' => 'https://m.me/safestorebd',
		'telegram'  => 'https://t.me/safestorebd',
		'facebook'  => 'https://www.facebook.com/safestorebd',
		'instagram' => 'https://www.instagram.com/safestorebd/',
		'pinterest' => 'https://www.pinterest.com/safestorebd/',
	);

	/**
	 * Filter PDP messaging / social profile URLs.
	 * Return an empty string for a key to hide that button.
	 *
	 * @param array $profiles Profile URLs keyed by network.
	 */
	return apply_filters( 'safestore_pdp_social_profiles', $profiles );
}

/**
 * Current product share payload (URL, title, image).
 *
 * @return array{url:string,title:string,image:string,id:int}|null
 */
function safestore_pdp_share_payload() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return null;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! $product ) {
		return null;
	}

	$url   = get_permalink( $product->get_id() );
	$title = $product->get_name();
	$image = '';

	$image_id = $product->get_image_id();
	if ( $image_id ) {
		$src = wp_get_attachment_image_src( $image_id, 'full' );
		if ( is_array( $src ) && ! empty( $src[0] ) ) {
			$image = $src[0];
		}
	}
	if ( $image === '' ) {
		$image = wc_placeholder_img_src( 'full' );
	}

	return array(
		'id'    => (int) $product->get_id(),
		'url'   => $url,
		'title' => $title,
		'image' => $image,
	);
}

/**
 * Enqueue the PDP action-bar script on single products only.
 */
function safestore_pdp_actions_enqueue() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$path = get_template_directory() . '/js/pdp-actions.js';
	if ( ! file_exists( $path ) ) {
		return;
	}

	$share = safestore_pdp_share_payload();
	if ( ! $share ) {
		return;
	}

	wp_enqueue_script(
		'safestore-pdp-actions',
		get_template_directory_uri() . '/js/pdp-actions.js',
		array(),
		(string) filemtime( $path ),
		true
	);

	wp_script_add_data( 'safestore-pdp-actions', 'strategy', 'defer' );

	wp_localize_script(
		'safestore-pdp-actions',
		'sftPdpActions',
		array(
			'productId' => $share['id'],
			'i18n'      => array(
				'wishlistAdd'     => __( 'Add to wishlist', 'safestore-minimal' ),
				'wishlistRemove'  => __( 'Remove from wishlist', 'safestore-minimal' ),
				'wishlistAdded'   => __( 'Added to wishlist', 'safestore-minimal' ),
				'wishlistRemoved' => __( 'Removed from wishlist', 'safestore-minimal' ),
				'compareAdd'      => __( 'Compare', 'safestore-minimal' ),
				'compareRemove'   => __( 'Remove from compare', 'safestore-minimal' ),
				'compareAdded'    => __( 'Added to compare', 'safestore-minimal' ),
				'compareRemoved'  => __( 'Removed from compare', 'safestore-minimal' ),
				'compareFull'     => __( 'Compare list is full (max 4)', 'safestore-minimal' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'safestore_pdp_actions_enqueue', 25 );

/**
 * PDP action bar: Compare + Wishlist (left), Share icon + menu (right).
 *
 * Replaces the old full-width share button row with a standard e-commerce
 * toolbar pattern. Social targets live in a disclosure menu to stay light.
 */
function safestore_minimal_pdp_action_bar() {
	$share = safestore_pdp_share_payload();
	if ( ! $share ) {
		return;
	}

	$fb_url = add_query_arg(
		array( 'u' => $share['url'] ),
		'https://www.facebook.com/sharer/sharer.php'
	);

	$x_url = add_query_arg(
		array(
			'url'  => $share['url'],
			'text' => $share['title'],
		),
		'https://twitter.com/intent/tweet'
	);

	$li_url = add_query_arg(
		array(
			'url'   => $share['url'],
			'title' => $share['title'],
		),
		'https://www.linkedin.com/sharing/share-offsite/'
	);

	$pin_url = add_query_arg(
		array(
			'url'         => $share['url'],
			'media'       => $share['image'],
			'description' => $share['title'],
		),
		'https://www.pinterest.com/pin/create/button/'
	);

	$wa_text  = sprintf(
		/* translators: 1: product name, 2: product URL */
		__( 'Check out %1$s — %2$s', 'safestore-minimal' ),
		$share['title'],
		$share['url']
	);
	$wa_share = 'https://wa.me/?text=' . rawurlencode( $wa_text );

	$mail_url = 'mailto:?subject=' . rawurlencode( $share['title'] ) . '&body=' . rawurlencode( $share['title'] . "\n\n" . $share['url'] );

	$panel_id = 'sft-pdp-share-panel-' . $share['id'];
	?>
	<nav class="sft-pdp-actions" aria-label="<?php esc_attr_e( 'Product tools', 'safestore-minimal' ); ?>">
		<div class="sft-pdp-actions__left">
			<button
				type="button"
				class="sft-pdp-actions__link"
				data-sft-compare
				aria-pressed="false"
				aria-label="<?php esc_attr_e( 'Compare', 'safestore-minimal' ); ?>"
			>
				<svg class="sft-pdp-actions__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
					<path d="M16 3h5v5"/><path d="M8 21H3v-5"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/>
				</svg>
				<span class="sft-pdp-actions__label"><?php esc_html_e( 'Compare', 'safestore-minimal' ); ?></span>
			</button>

			<button
				type="button"
				class="sft-pdp-actions__link"
				data-sft-wishlist
				aria-pressed="false"
				aria-label="<?php esc_attr_e( 'Add to wishlist', 'safestore-minimal' ); ?>"
			>
				<svg class="sft-pdp-actions__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
					<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/>
				</svg>
				<span class="sft-pdp-actions__label"><?php esc_html_e( 'Add to wishlist', 'safestore-minimal' ); ?></span>
			</button>
		</div>

		<div class="sft-pdp-actions__right" data-sft-share>
			<button
				type="button"
				class="sft-pdp-actions__share-trigger"
				data-sft-share-trigger
				aria-haspopup="true"
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( $panel_id ); ?>"
				aria-label="<?php esc_attr_e( 'Share this product', 'safestore-minimal' ); ?>"
			>
				<svg class="sft-pdp-actions__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
					<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
					<path d="M8.6 13.5 15.4 17.5"/><path d="M15.4 6.5 8.6 10.5"/>
				</svg>
			</button>

			<div
				id="<?php echo esc_attr( $panel_id ); ?>"
				class="sft-pdp-actions__share-panel"
				data-sft-share-panel
				role="menu"
				aria-label="<?php esc_attr_e( 'Share options', 'safestore-minimal' ); ?>"
				hidden
			>
				<a class="sft-pdp-actions__share-item" role="menuitem" href="<?php echo esc_url( $fb_url ); ?>" target="_blank" rel="noopener noreferrer">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 8.5V6.8c0-.8.5-.9.8-.9h2.1V2.5h-2.9C10.5 2.5 9 4.8 9 8v.5H6.8v3.6H9v9.4h4.5v-9.4h2.8l.4-3.6h-3.2z"/></svg>
					<span><?php esc_html_e( 'Facebook', 'safestore-minimal' ); ?></span>
				</a>
				<a class="sft-pdp-actions__share-item" role="menuitem" href="<?php echo esc_url( $x_url ); ?>" target="_blank" rel="noopener noreferrer">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.9 3h2.9l-6.3 7.2L23 21h-5.9l-4.6-6-5.3 6H4.3l6.8-7.8L1 3h6l4.2 5.5L18.9 3zm-1 16.3h1.6L6.1 4.6H4.4z"/></svg>
					<span><?php esc_html_e( 'X', 'safestore-minimal' ); ?></span>
				</a>
				<a class="sft-pdp-actions__share-item" role="menuitem" href="<?php echo esc_url( $mail_url ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="m4 7 8 6 8-6"/></svg>
					<span><?php esc_html_e( 'Email', 'safestore-minimal' ); ?></span>
				</a>
				<a class="sft-pdp-actions__share-item" role="menuitem" href="<?php echo esc_url( $li_url ); ?>" target="_blank" rel="noopener noreferrer">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5.4 8.1H2V21h3.4V8.1zM3.7 2.7A2 2 0 1 0 3.7 6.7 2 2 0 0 0 3.7 2.7zM22 13.6c0-3.4-1.8-5.9-5.2-5.9-2.4 0-3.4 1.3-4 2.2V8.1H9.4V21h3.4v-6.4c0-1.7.3-3.4 2.4-3.4s2.1 1.9 2.1 3.5V21H22v-7.4z"/></svg>
					<span><?php esc_html_e( 'LinkedIn', 'safestore-minimal' ); ?></span>
				</a>
				<a class="sft-pdp-actions__share-item" role="menuitem" href="<?php echo esc_url( $wa_share ); ?>" target="_blank" rel="noopener noreferrer">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.148-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
					<span><?php esc_html_e( 'WhatsApp', 'safestore-minimal' ); ?></span>
				</a>
				<a class="sft-pdp-actions__share-item" role="menuitem" href="<?php echo esc_url( $pin_url ); ?>" target="_blank" rel="noopener noreferrer">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.5 2 2 6.5 2 12c0 4.2 2.6 7.8 6.3 9.3-.1-.8-.2-2 0-2.9.2-.8 1.2-5 1.2-5s-.3-.6-.3-1.5c0-1.4.8-2.4 1.8-2.4.9 0 1.3.6 1.3 1.4 0 .9-.5 2.1-.8 3.3-.2 1 .5 1.8 1.5 1.8 1.8 0 3.1-1.9 3.1-4.6 0-2.4-1.7-4.1-4.2-4.1-2.8 0-4.5 2.1-4.5 4.3 0 .9.3 1.8.7 2.3.1.1.1.2.1.3l-.3 1.1c0 .2-.1.2-.3.1-1.2-.6-2-2.4-2-3.9 0-3.2 2.3-6.1 6.6-6.1 3.5 0 6.2 2.5 6.2 5.8 0 3.4-2.2 6.2-5.2 6.2-1 0-2-.5-2.3-1.1l-.6 2.4c-.2.9-.8 2-1.2 2.6.9.3 1.9.4 3 .4 5.5 0 10-4.5 10-10S17.5 2 12 2z"/></svg>
					<span><?php esc_html_e( 'Pinterest', 'safestore-minimal' ); ?></span>
				</a>
				<button
					type="button"
					class="sft-pdp-actions__share-item"
					role="menuitem"
					data-sft-copy="<?php echo esc_attr( $share['url'] ); ?>"
					data-sft-copy-done="<?php esc_attr_e( 'Link copied', 'safestore-minimal' ); ?>"
					data-sft-copy-title="<?php esc_attr_e( 'Copy link', 'safestore-minimal' ); ?>"
					aria-label="<?php esc_attr_e( 'Copy link', 'safestore-minimal' ); ?>"
				>
					<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
						<path d="M12 7.2a4.8 4.8 0 1 0 0 9.6 4.8 4.8 0 0 0 0-9.6zm0 7.9a3.1 3.1 0 1 1 0-6.2 3.1 3.1 0 0 1 0 6.2z"/>
						<path d="M18.1 2.5H5.9A3.4 3.4 0 0 0 2.5 5.9v12.2a3.4 3.4 0 0 0 3.4 3.4h12.2a3.4 3.4 0 0 0 3.4-3.4V5.9a3.4 3.4 0 0 0-3.4-3.4zm1.7 15.6c0 .9-.8 1.7-1.7 1.7H5.9c-.9 0-1.7-.8-1.7-1.7V5.9c0-.9.8-1.7 1.7-1.7h12.2c.9 0 1.7.8 1.7 1.7v12.2z"/>
						<circle cx="17.4" cy="6.6" r="1.1"/>
					</svg>
					<span><?php esc_html_e( 'Copy link', 'safestore-minimal' ); ?></span>
				</button>
			</div>
		</div>
	</nav>
	<?php
}

/**
 * Back-compat alias — older hooks / docs referenced the share row.
 */
function safestore_minimal_pdp_share_row() {
	safestore_minimal_pdp_action_bar();
}

/**
 * Whether a dedicated SEO plugin already owns social meta.
 *
 * @return bool
 */
function safestore_pdp_seo_plugin_handles_meta() {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) ) {
		return true;
	}
	if ( defined( 'THE_SEO_FRAMEWORK_VERSION' ) || class_exists( 'SQ_Classes_ObjController', false ) ) {
		return true;
	}
	/**
	 * Force-disable theme OG output (e.g. when another plugin owns the tags).
	 *
	 * @param bool $skip Whether to skip theme social meta.
	 */
	return (bool) apply_filters( 'safestore_pdp_skip_social_meta', false );
}

/**
 * Open Graph + Twitter Card tags for single products.
 */
function safestore_pdp_social_meta() {
	if ( is_admin() || safestore_pdp_seo_plugin_handles_meta() ) {
		return;
	}
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_queried_object_id() );
	}
	if ( ! $product ) {
		return;
	}

	$url         = get_permalink( $product->get_id() );
	$title       = wp_strip_all_tags( $product->get_name() );
	$description = $product->get_short_description();
	if ( $description === '' ) {
		$description = $product->get_description();
	}
	$description = wp_trim_words( wp_strip_all_tags( $description ), 40, '…' );
	if ( $description === '' ) {
		$description = sprintf(
			/* translators: %s: product name */
			__( 'Buy %s from SafeStoreBD — industrial safety gear in Bangladesh.', 'safestore-minimal' ),
			$title
		);
	}

	$image    = '';
	$image_id = $product->get_image_id();
	if ( $image_id ) {
		$src = wp_get_attachment_image_src( $image_id, 'full' );
		if ( is_array( $src ) && ! empty( $src[0] ) ) {
			$image = $src[0];
		}
	}
	if ( $image === '' ) {
		$image = wc_placeholder_img_src( 'full' );
	}

	$price    = wc_get_price_to_display( $product );
	$currency = get_woocommerce_currency();
	$site     = wp_strip_all_tags( get_bloginfo( 'name' ) );

	echo "\n<!-- SafeStoreBD product social meta -->\n";
	printf( '<meta property="og:type" content="product" />' . "\n" );
	printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( $site ) );
	printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $description ) );
	printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $url ) );
	if ( $image ) {
		printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
		printf( '<meta property="og:image:alt" content="%s" />' . "\n", esc_attr( $title ) );
	}
	if ( $price !== '' && $price !== null ) {
		printf( '<meta property="product:price:amount" content="%s" />' . "\n", esc_attr( wc_format_decimal( $price, wc_get_price_decimals() ) ) );
		printf( '<meta property="product:price:currency" content="%s" />' . "\n", esc_attr( $currency ) );
		printf( '<meta property="og:price:amount" content="%s" />' . "\n", esc_attr( wc_format_decimal( $price, wc_get_price_decimals() ) ) );
		printf( '<meta property="og:price:currency" content="%s" />' . "\n", esc_attr( $currency ) );
	}
	if ( $product->is_in_stock() ) {
		echo '<meta property="product:availability" content="in stock" />' . "\n";
	} else {
		echo '<meta property="product:availability" content="out of stock" />' . "\n";
	}

	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $title ) );
	printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $description ) );
	if ( $image ) {
		printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $image ) );
	}
	echo "<!-- /SafeStoreBD product social meta -->\n";
}
add_action( 'wp_head', 'safestore_pdp_social_meta', 5 );

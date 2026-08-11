<?php
/**
 * Product comparison — floating bar, compare page data API, page seed.
 *
 * Complements the PDP Compare toggle in pdp-social.php / pdp-actions.js.
 * List lives in localStorage (`sft_compare`); this module resolves IDs to
 * product cards and renders the compare UI.
 *
 * @package SafeStore_Minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Max products in a comparison set. */
define( 'SAFESTORE_COMPARE_MAX', 4 );

/**
 * Whether compare assets should load on this request.
 *
 * @return bool
 */
function safestore_compare_enabled() {
	$enabled = class_exists( 'WooCommerce' ) && ! is_admin();

	/**
	 * Toggle the product compare feature.
	 *
	 * @param bool $enabled Whether compare loads on this request.
	 */
	return (bool) apply_filters( 'safestore_compare_enabled', $enabled );
}

/**
 * Permalink for the Compare page (falls back to /compare/).
 *
 * @return string
 */
function safestore_compare_page_url() {
	$page = get_page_by_path( 'compare' );
	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}
	return home_url( '/compare/' );
}

/**
 * Whether the current request is the Compare page template.
 *
 * @return bool
 */
function safestore_is_compare_page() {
	return is_page_template( 'page-compare.php' ) || is_page( 'compare' );
}

/**
 * Build a public payload for one product (safe for REST/JS).
 *
 * @param WC_Product $product Product.
 * @return array<string,mixed>|null
 */
function safestore_compare_product_payload( $product ) {
	if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() ) {
		return null;
	}

	$image = '';
	$image_id = $product->get_image_id();
	if ( $image_id ) {
		$src = wp_get_attachment_image_src( $image_id, 'woocommerce_thumbnail' );
		if ( is_array( $src ) && ! empty( $src[0] ) ) {
			$image = $src[0];
		}
	}
	if ( $image === '' ) {
		$image = wc_placeholder_img_src( 'woocommerce_thumbnail' );
	}

	$categories = array();
	$terms      = get_the_terms( $product->get_id(), 'product_cat' );
	if ( is_array( $terms ) ) {
		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term && (int) $term->parent === 0 ) {
				$categories[] = $term->name;
			}
		}
		if ( ! $categories ) {
			foreach ( $terms as $term ) {
				if ( $term instanceof WP_Term ) {
					$categories[] = $term->name;
				}
			}
		}
	}

	$attributes = array();
	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute instanceof WC_Product_Attribute ) {
			continue;
		}
		if ( ! $attribute->get_visible() ) {
			continue;
		}
		$label = wc_attribute_label( $attribute->get_name() );
		if ( $attribute->is_taxonomy() ) {
			$names = wc_get_product_terms(
				$product->get_id(),
				$attribute->get_name(),
				array( 'fields' => 'names' )
			);
			$value = is_array( $names ) ? implode( ', ', $names ) : '';
		} else {
			$value = implode( ', ', $attribute->get_options() );
		}
		if ( $value === '' ) {
			continue;
		}
		$attributes[] = array(
			'name'  => $label,
			'value' => $value,
		);
	}

	$price_display = wc_get_price_to_display( $product );

	return array(
		'id'           => (int) $product->get_id(),
		'name'         => $product->get_name(),
		'url'          => get_permalink( $product->get_id() ),
		'image'        => $image,
		'priceHtml'    => $product->get_price_html(),
		'price'        => $price_display !== '' && $price_display !== null
			? (float) $price_display
			: null,
		'onSale'       => $product->is_on_sale(),
		'inStock'      => $product->is_in_stock(),
		'stockStatus'  => $product->is_in_stock()
			? __( 'In stock', 'safestore-minimal' )
			: __( 'Out of stock', 'safestore-minimal' ),
		'sku'          => $product->get_sku(),
		'categories'   => $categories,
		'attributes'   => $attributes,
		'type'         => $product->get_type(),
		'addToCartUrl' => $product->add_to_cart_url(),
		'addToCartText'=> $product->add_to_cart_text(),
	);
}

/**
 * REST: GET /wp-json/safestore/v1/compare?ids=1,2,3
 */
function safestore_compare_register_rest() {
	register_rest_route(
		'safestore/v1',
		'/compare',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => '__return_true',
			'args'                => array(
				'ids' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
			'callback'            => 'safestore_compare_rest_callback',
		)
	);
}
add_action( 'rest_api_init', 'safestore_compare_register_rest' );

/**
 * REST callback — resolve product IDs for the compare UI.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function safestore_compare_rest_callback( WP_REST_Request $request ) {
	$raw = (string) $request->get_param( 'ids' );
	$parts = preg_split( '/[,\s]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
	$ids   = array();
	if ( is_array( $parts ) ) {
		foreach ( $parts as $part ) {
			$id = absint( $part );
			if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}
			if ( count( $ids ) >= SAFESTORE_COMPARE_MAX ) {
				break;
			}
		}
	}

	$products = array();
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );
		$payload = safestore_compare_product_payload( $product );
		if ( $payload ) {
			$products[] = $payload;
		}
	}

	return rest_ensure_response(
		array(
			'products' => $products,
			'max'      => SAFESTORE_COMPARE_MAX,
		)
	);
}

/**
 * Enqueue compare assets site-wide (bar) and denser assets on the compare page.
 */
function safestore_compare_enqueue() {
	if ( ! safestore_compare_enabled() ) {
		return;
	}

	// Skip cart/checkout to avoid cluttering conversion flows.
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
		return;
	}
	// Assets still load on the compare page; the floating bar is omitted there.

	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();

	$css_path = $theme_dir . '/css/product-compare.css';
	if ( file_exists( $css_path ) ) {
		wp_enqueue_style(
			'safestore-product-compare',
			$theme_uri . '/css/product-compare.css',
			array( 'safestore-minimal-style' ),
			(string) filemtime( $css_path )
		);
	}

	$js_path = $theme_dir . '/js/product-compare.js';
	if ( ! file_exists( $js_path ) ) {
		return;
	}

	wp_enqueue_script(
		'safestore-product-compare',
		$theme_uri . '/js/product-compare.js',
		array(),
		(string) filemtime( $js_path ),
		function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true
	);

	wp_localize_script(
		'safestore-product-compare',
		'sftCompare',
		array(
			'restUrl'    => esc_url_raw( rest_url( 'safestore/v1/compare' ) ),
			'compareUrl' => esc_url_raw( safestore_compare_page_url() ),
			'shopUrl'    => esc_url_raw( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ),
			'max'        => SAFESTORE_COMPARE_MAX,
			'isPage'     => safestore_is_compare_page(),
			'productId'  => ( function_exists( 'is_product' ) && is_product() ) ? (int) get_queried_object_id() : 0,
			'i18n'       => array(
				'compare'          => __( 'Compare', 'safestore-minimal' ),
				'compareProducts'  => __( 'Compare products', 'safestore-minimal' ),
				'viewComparison'   => __( 'View comparison', 'safestore-minimal' ),
				'clearAll'         => __( 'Clear all', 'safestore-minimal' ),
				'remove'           => __( 'Remove', 'safestore-minimal' ),
				'addMore'          => __( 'Add 1 more to compare', 'safestore-minimal' ),
				'addMoreN'         => __( 'Add %d more to compare', 'safestore-minimal' ),
				'full'             => __( 'Compare list is full (max 4)', 'safestore-minimal' ),
				'emptyTitle'       => __( 'No products to compare', 'safestore-minimal' ),
				'emptyText'        => __( 'Add products from a product page using Compare, then come back here.', 'safestore-minimal' ),
				'browseShop'       => __( 'Browse shop', 'safestore-minimal' ),
				'needMoreTitle'    => __( 'Add one more product', 'safestore-minimal' ),
				'needMoreText'     => __( 'Comparison works best with at least two products. Keep shopping and tap Compare on another item.', 'safestore-minimal' ),
				'loading'          => __( 'Loading comparison…', 'safestore-minimal' ),
				'loadError'        => __( 'Could not load products. Please try again.', 'safestore-minimal' ),
				'price'            => __( 'Price', 'safestore-minimal' ),
				'availability'     => __( 'Availability', 'safestore-minimal' ),
				'sku'              => __( 'SKU', 'safestore-minimal' ),
				'category'         => __( 'Category', 'safestore-minimal' ),
				'actions'          => __( 'Actions', 'safestore-minimal' ),
				'added'            => __( 'Added to compare', 'safestore-minimal' ),
				'removed'          => __( 'Removed from compare', 'safestore-minimal' ),
				'slotEmpty'        => __( 'Add product', 'safestore-minimal' ),
				'countLabel'       => __( '%d of %d', 'safestore-minimal' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'safestore_compare_enqueue', 24 );

/**
 * Floating compare bar shell (filled by JS).
 */
function safestore_compare_bar_markup() {
	if ( ! safestore_compare_enabled() ) {
		return;
	}
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
		return;
	}
	// Compare page has its own table UI — skip the floating bar.
	if ( safestore_is_compare_page() ) {
		return;
	}
	?>
	<div
		id="sft-compare-bar"
		class="sft-compare-bar"
		hidden
		data-sft-compare-bar
		role="region"
		aria-label="<?php esc_attr_e( 'Product comparison', 'safestore-minimal' ); ?>"
	>
		<div class="sft-compare-bar__inner">
			<div class="sft-compare-bar__meta">
				<span class="sft-compare-bar__title"><?php esc_html_e( 'Compare', 'safestore-minimal' ); ?></span>
				<span class="sft-compare-bar__count" data-sft-compare-count aria-live="polite"></span>
			</div>
			<ul class="sft-compare-bar__slots" data-sft-compare-slots></ul>
			<div class="sft-compare-bar__actions">
				<button type="button" class="sft-compare-bar__clear" data-sft-compare-clear>
					<?php esc_html_e( 'Clear all', 'safestore-minimal' ); ?>
				</button>
				<a class="sft-compare-bar__cta" data-sft-compare-cta href="<?php echo esc_url( safestore_compare_page_url() ); ?>">
					<?php esc_html_e( 'Compare', 'safestore-minimal' ); ?>
				</a>
			</div>
			<button
				type="button"
				class="sft-compare-bar__close"
				data-sft-compare-dismiss
				aria-label="<?php esc_attr_e( 'Hide comparison bar', 'safestore-minimal' ); ?>"
			>
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
			</button>
		</div>
	</div>
	<div id="sft-compare-live" class="sft-compare-live" role="status" aria-live="polite" aria-atomic="true"></div>
	<?php
}
add_action( 'wp_footer', 'safestore_compare_bar_markup', 25 );

/**
 * Seed /compare/ page with the Compare template.
 */
function safestore_seed_compare_page() {
	if ( get_option( 'safestore_compare_page_v1' ) ) {
		return;
	}

	$slug = 'compare';
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => __( 'Compare products', 'safestore-minimal' ),
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'page-compare.php' );
		}
	} elseif ( $page instanceof WP_Post ) {
		update_post_meta( $page->ID, '_wp_page_template', 'page-compare.php' );
	}

	update_option( 'safestore_compare_page_v1', 1, false );
}
add_action( 'after_switch_theme', 'safestore_seed_compare_page' );
add_action(
	'init',
	static function () {
		if ( ! get_option( 'safestore_compare_page_v1' ) ) {
			safestore_seed_compare_page();
		}
	},
	20
);

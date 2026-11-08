<?php
/**
 * Wishlist: /wishlist/ page, product lookup endpoint and account sync.
 *
 * Storage: guests keep product IDs in localStorage ("sft_wishlist", written by
 * js/pdp-actions.js). Logged-in shoppers also get the list saved in user meta,
 * so it follows them across devices (js/wishlist.js merges on load and saves
 * after every change).
 *
 * Switch the whole feature off (header icon, mobile chip, PDP button, page
 * routes) with  define( 'SFT_ENABLE_WISHLIST', false );  in wp-config.php.
 *
 * @package safestore-minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SFT_ENABLE_WISHLIST' ) ) {
	define( 'SFT_ENABLE_WISHLIST', true );
}

/** Max products kept in a wishlist. */
const SAFESTORE_WISHLIST_MAX = 60;

/** User meta key for logged-in shoppers. */
const SAFESTORE_WISHLIST_META = '_sft_wishlist';

function safestore_wishlist_enabled() {
	return (bool) apply_filters( 'safestore_wishlist_enabled', SFT_ENABLE_WISHLIST && class_exists( 'WooCommerce' ) );
}

function safestore_wishlist_page_url() {
	$page = get_page_by_path( 'wishlist' );
	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		return get_permalink( $page );
	}
	return home_url( '/wishlist/' );
}

function safestore_is_wishlist_page() {
	return is_page_template( 'page-wishlist.php' ) || is_page( 'wishlist' );
}

/**
 * Clean a list of product IDs: positive ints, unique, capped.
 *
 * @param mixed $raw Array or comma/space separated string.
 * @return int[]
 */
function safestore_wishlist_clean_ids( $raw ) {
	if ( is_string( $raw ) ) {
		$raw = preg_split( '/[,\s]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
	}
	$ids = array();
	foreach ( (array) $raw as $part ) {
		$id = absint( $part );
		if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
			$ids[] = $id;
		}
		if ( count( $ids ) >= SAFESTORE_WISHLIST_MAX ) {
			break;
		}
	}
	return $ids;
}

/** Card data for one product, or null when it should not be shown. */
function safestore_wishlist_product_payload( $product ) {
	if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() || ! $product->is_visible() ) {
		return null;
	}

	$image_id = $product->get_image_id();
	$image    = $image_id ? wp_get_attachment_image_src( $image_id, 'woocommerce_thumbnail' ) : false;

	$simple_ajax = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() && $product->supports( 'ajax_add_to_cart' );

	return array(
		'id'          => (int) $product->get_id(),
		'name'        => $product->get_name(),
		'url'         => get_permalink( $product->get_id() ),
		'image'       => is_array( $image ) && ! empty( $image[0] ) ? $image[0] : wc_placeholder_img_src( 'woocommerce_thumbnail' ),
		'imageSrcset' => $image_id ? (string) wp_get_attachment_image_srcset( $image_id, 'woocommerce_thumbnail' ) : '',
		'priceHtml'   => $product->get_price_html(),
		'inStock'     => $product->is_in_stock(),
		'ajaxAdd'     => $simple_ajax,
		'cartUrl'     => $product->add_to_cart_url(),
		'cartText'    => $simple_ajax ? __( 'Add to cart', 'safestore-minimal' ) : ( $product->is_in_stock() ? __( 'Choose size', 'safestore-minimal' ) : __( 'View product', 'safestore-minimal' ) ),
	);
}

/* -------------------------------------------------------------------------- */
/* REST                                                                       */
/* -------------------------------------------------------------------------- */

function safestore_wishlist_register_rest() {
	if ( ! safestore_wishlist_enabled() ) {
		return;
	}

	// Public product lookup for the IDs a browser holds (read-only, published only).
	register_rest_route(
		'safestore/v1',
		'/wishlist/products',
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
			'callback'            => static function ( WP_REST_Request $request ) {
				$products = array();
				foreach ( safestore_wishlist_clean_ids( (string) $request->get_param( 'ids' ) ) as $id ) {
					$payload = safestore_wishlist_product_payload( wc_get_product( $id ) );
					if ( $payload ) {
						$products[] = $payload;
					}
				}
				return rest_ensure_response( array( 'products' => $products ) );
			},
		)
	);

	// Account copy for logged-in shoppers. "merge" on page load, "replace" after a change.
	register_rest_route(
		'safestore/v1',
		'/wishlist/sync',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'permission_callback' => static function () {
				return is_user_logged_in();
			},
			'args'                => array(
				'ids'  => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'mode' => array(
					'type'    => 'string',
					'default' => 'merge',
					'enum'    => array( 'merge', 'replace' ),
				),
			),
			'callback'            => static function ( WP_REST_Request $request ) {
				$user_id = get_current_user_id();
				$posted  = safestore_wishlist_clean_ids( (string) $request->get_param( 'ids' ) );
				if ( 'replace' === $request->get_param( 'mode' ) ) {
					$ids = $posted;
				} else {
					$saved = safestore_wishlist_clean_ids( get_user_meta( $user_id, SAFESTORE_WISHLIST_META, true ) );
					$ids   = safestore_wishlist_clean_ids( array_merge( $saved, $posted ) );
				}
				update_user_meta( $user_id, SAFESTORE_WISHLIST_META, $ids );
				return rest_ensure_response( array( 'ids' => array_map( 'strval', $ids ) ) );
			},
		)
	);
}
add_action( 'rest_api_init', 'safestore_wishlist_register_rest' );

/* -------------------------------------------------------------------------- */
/* Assets                                                                     */
/* -------------------------------------------------------------------------- */

function safestore_wishlist_enqueue() {
	if ( ! safestore_wishlist_enabled() || is_admin() ) {
		return;
	}

	$on_page = safestore_is_wishlist_page();
	// Product pages only need the script to save changes to the account.
	$on_pdp = is_user_logged_in() && function_exists( 'is_product' ) && is_product();
	if ( ! $on_page && ! $on_pdp ) {
		return;
	}

	$dir = get_template_directory();
	$uri = get_template_directory_uri();

	if ( $on_page && file_exists( $dir . '/css/wishlist.css' ) ) {
		wp_enqueue_style( 'safestore-wishlist', $uri . '/css/wishlist.css', array( 'safestore-minimal-style' ), (string) filemtime( $dir . '/css/wishlist.css' ) );
	}

	if ( ! file_exists( $dir . '/js/wishlist.js' ) ) {
		return;
	}
	wp_enqueue_script(
		'safestore-wishlist',
		$uri . '/js/wishlist.js',
		array(),
		(string) filemtime( $dir . '/js/wishlist.js' ),
		function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true
	);
	wp_localize_script(
		'safestore-wishlist',
		'sftWishlist',
		array(
			'productsUrl' => esc_url_raw( rest_url( 'safestore/v1/wishlist/products' ) ),
			'syncUrl'     => is_user_logged_in() ? esc_url_raw( rest_url( 'safestore/v1/wishlist/sync' ) ) : '',
			'nonce'       => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
			'shopUrl'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
			'i18n'        => array(
				'emptyTitle' => __( 'Your wishlist is empty', 'safestore-minimal' ),
				'emptyText'  => __( 'Tap “Add to wishlist” on any product to save it here for later.', 'safestore-minimal' ),
				'shop'       => __( 'Browse safety equipment', 'safestore-minimal' ),
				'remove'     => __( 'Remove', 'safestore-minimal' ),
				/* translators: %s: product name */
				'removeName' => __( 'Remove %s from wishlist', 'safestore-minimal' ),
				'removed'    => __( 'Removed from wishlist', 'safestore-minimal' ),
				'outOfStock' => __( 'Out of stock', 'safestore-minimal' ),
				'error'      => __( 'Could not load your wishlist. Please refresh the page.', 'safestore-minimal' ),
				/* translators: %d: number of saved products */
				'count'      => __( '%d saved products', 'safestore-minimal' ),
				'countOne'   => __( '1 saved product', 'safestore-minimal' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'safestore_wishlist_enqueue', 26 );

/* -------------------------------------------------------------------------- */
/* SEO: the page is per-visitor, never index it                               */
/* -------------------------------------------------------------------------- */

add_filter(
	'wp_robots',
	static function ( $robots ) {
		if ( safestore_wishlist_enabled() && safestore_is_wishlist_page() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'], $robots['max-image-preview'] );
		}
		return $robots;
	},
	20
);

add_filter(
	'rank_math/frontend/robots',
	static function ( $robots ) {
		if ( safestore_wishlist_enabled() && safestore_is_wishlist_page() ) {
			$robots['index']  = 'noindex';
			$robots['follow'] = 'follow';
		}
		return $robots;
	},
	20
);

// Keep the page out of the XML sitemap.
add_filter(
	'rank_math/sitemap/exclude_post',
	static function ( $exclude, $post ) {
		return ( $post instanceof WP_Post && 'page' === $post->post_type && 'wishlist' === $post->post_name ) ? true : $exclude;
	},
	10,
	2
);

/* -------------------------------------------------------------------------- */
/* Feature flag off → drop the mobile chip                                    */
/* -------------------------------------------------------------------------- */

add_filter(
	'safestore_mnav_quicklinks',
	static function ( $links ) {
		if ( safestore_wishlist_enabled() ) {
			return $links;
		}
		return array_values(
			array_filter(
				(array) $links,
				static function ( $link ) {
					return empty( $link['url'] ) || false === strpos( (string) $link['url'], '/wishlist' );
				}
			)
		);
	}
);

/* -------------------------------------------------------------------------- */
/* Page seed (same pattern as the compare page)                               */
/* -------------------------------------------------------------------------- */

function safestore_seed_wishlist_page() {
	if ( get_option( 'safestore_wishlist_page_v1' ) || ! safestore_wishlist_enabled() ) {
		return;
	}

	$page = get_page_by_path( 'wishlist' );
	if ( ! $page ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => __( 'Wishlist', 'safestore-minimal' ),
				'post_name'    => 'wishlist',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'page-wishlist.php' );
		}
	} elseif ( $page instanceof WP_Post ) {
		update_post_meta( $page->ID, '_wp_page_template', 'page-wishlist.php' );
	}

	update_option( 'safestore_wishlist_page_v1', 1, false );
}
add_action( 'after_switch_theme', 'safestore_seed_wishlist_page' );
add_action(
	'init',
	static function () {
		if ( ! get_option( 'safestore_wishlist_page_v1' ) ) {
			safestore_seed_wishlist_page();
		}
	},
	20
);

// Feature off: send old /wishlist/ links to the shop instead of an empty page.
add_action(
	'template_redirect',
	static function () {
		if ( ! safestore_wishlist_enabled() && class_exists( 'WooCommerce' ) && is_page( 'wishlist' ) ) {
			wp_safe_redirect( wc_get_page_permalink( 'shop' ), 302 );
			exit;
		}
	}
);

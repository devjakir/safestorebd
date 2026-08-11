<?php
/**
 * Footwear sizing — Safety Shoes (EU 39–44)
 *
 * Storefront logic:
 * - Restrict Size options to 39–44 for footwear products
 * - Show Size archive filters only on footwear categories
 * - Validate cart quantity against per-size (variation) stock
 * - Retail vs B2B quantity limits for Bangladesh buyers
 *
 * Admin conversion / attribute creation stays in size-variations-setup.php.
 *
 * @package SafeStore_Minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allowed EU sizes for Safety Shoes.
 *
 * @return string[]
 */
function safestore_footwear_allowed_sizes() {
	$sizes = function_exists( 'safestore_size_terms' )
		? safestore_size_terms()
		: array( '39', '40', '41', '42', '43', '44' );

	/**
	 * Filter allowed footwear sizes.
	 *
	 * @param string[] $sizes Size labels/slugs.
	 */
	return apply_filters( 'safestore_footwear_allowed_sizes', $sizes );
}

/**
 * Category slugs treated as footwear (show size UI / filters).
 *
 * @return string[]
 */
function safestore_footwear_category_slugs() {
	$slugs = array( 'safety-shoes' );

	/**
	 * Filter footwear category slugs.
	 *
	 * @param string[] $slugs Product category slugs.
	 */
	return apply_filters( 'safestore_footwear_category_slugs', $slugs );
}

/**
 * Whether a product belongs to a footwear category.
 *
 * @param int|WC_Product|null $product Product or ID.
 * @return bool
 */
function safestore_is_footwear_product( $product = null ) {
	if ( null === $product ) {
		$product = wc_get_product( get_the_ID() );
	} elseif ( is_numeric( $product ) ) {
		$product = wc_get_product( (int) $product );
	}

	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	if ( ! $id ) {
		return false;
	}

	foreach ( safestore_footwear_category_slugs() as $slug ) {
		if ( has_term( $slug, 'product_cat', $id ) ) {
			return true;
		}
	}

	return (bool) apply_filters( 'safestore_is_footwear_product', false, $product );
}

/**
 * Whether the current archive is a footwear category (or child of one).
 *
 * @return bool
 */
function safestore_is_footwear_archive() {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return false;
	}

	$term = get_queried_object();
	if ( ! $term || is_wp_error( $term ) || empty( $term->slug ) ) {
		return false;
	}

	$footwear = safestore_footwear_category_slugs();
	if ( in_array( $term->slug, $footwear, true ) ) {
		return true;
	}

	// Ancestors (e.g. safety-shoes/steel-toe).
	foreach ( get_ancestors( (int) $term->term_id, 'product_cat' ) as $ancestor_id ) {
		$ancestor = get_term( (int) $ancestor_id, 'product_cat' );
		if ( $ancestor && ! is_wp_error( $ancestor ) && in_array( $ancestor->slug, $footwear, true ) ) {
			return true;
		}
	}

	return (bool) apply_filters( 'safestore_is_footwear_archive', false, $term );
}

/**
 * Whether the current customer should get B2B quantity limits.
 *
 * @return bool
 */
function safestore_is_b2b_customer() {
	$user = wp_get_current_user();
	$roles = (array) $user->roles;

	$b2b_roles = apply_filters(
		'safestore_b2b_roles',
		array( 'wholesale_customer', 'b2b', 'shop_manager', 'administrator' )
	);

	$is_b2b = (bool) array_intersect( $roles, $b2b_roles );

	/**
	 * Filter B2B customer detection (Bangladesh wholesale / corporate buyers).
	 *
	 * @param bool    $is_b2b Whether B2B rules apply.
	 * @param WP_User $user   Current user.
	 */
	return (bool) apply_filters( 'safestore_is_b2b_customer', $is_b2b, $user );
}

/**
 * Quantity limits for a purchasable product/variation.
 *
 * @param WC_Product $product Product or variation.
 * @return array{min: int, max: int, step: int}
 */
function safestore_footwear_quantity_limits( $product ) {
	$is_b2b = safestore_is_b2b_customer();

	$min  = 1;
	$step = 1;
	$max  = $is_b2b
		? (int) apply_filters( 'safestore_b2b_max_qty', 500, $product )
		: (int) apply_filters( 'safestore_retail_max_qty', 24, $product );

	if ( $product instanceof WC_Product && $product->managing_stock() && ! $product->backorders_allowed() ) {
		$stock = $product->get_stock_quantity();
		if ( null !== $stock ) {
			$max = min( $max, max( 0, (int) $stock ) );
		}
	}

	if ( $product instanceof WC_Product && ! $product->is_in_stock() ) {
		$max = 0;
	}

	return array(
		'min'  => $min,
		'max'  => $max,
		'step' => $step,
	);
}

/**
 * Normalize a size option to a comparable slug/label.
 *
 * @param mixed $option Term ID, slug, or name.
 * @return string
 */
function safestore_footwear_normalize_size_option( $option ) {
	$option = is_string( $option ) || is_numeric( $option ) ? (string) $option : '';
	if ( '' === $option ) {
		return '';
	}

	if ( is_numeric( $option ) && taxonomy_exists( 'pa_size' ) ) {
		$term = get_term( (int) $option, 'pa_size' );
		if ( $term && ! is_wp_error( $term ) ) {
			return (string) $term->slug;
		}
	}

	return sanitize_title( $option );
}

/**
 * Whether a size value is in the allowed 39–44 set.
 *
 * @param mixed $option Size option.
 * @return bool
 */
function safestore_footwear_is_allowed_size( $option ) {
	$normalized = safestore_footwear_normalize_size_option( $option );
	$allowed    = array_map( 'sanitize_title', safestore_footwear_allowed_sizes() );

	return in_array( $normalized, $allowed, true )
		|| in_array( (string) $option, safestore_footwear_allowed_sizes(), true );
}

/* --------------------------------------------------------------------------
 * 1) Category display — prefer a clean primary category on cards
 * -------------------------------------------------------------------------- */

/**
 * Plain category label for product cards (prefers footwear / primary term).
 *
 * @param int $product_id Product ID.
 * @return string Category name or empty.
 */
function safestore_get_product_category_label( $product_id ) {
	$product_id = (int) $product_id;
	if ( ! $product_id ) {
		return '';
	}

	$primary_id = 0;
	if ( class_exists( 'WPSEO_Primary_Term' ) ) {
		$primary    = new WPSEO_Primary_Term( 'product_cat', $product_id );
		$primary_id = (int) $primary->get_primary_term();
	}

	$terms = get_the_terms( $product_id, 'product_cat' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return '';
	}

	if ( $primary_id ) {
		foreach ( $terms as $term ) {
			if ( (int) $term->term_id === $primary_id ) {
				return $term->name;
			}
		}
	}

	foreach ( $terms as $term ) {
		if ( in_array( $term->slug, safestore_footwear_category_slugs(), true ) ) {
			return $term->name;
		}
	}

	return $terms[0]->name;
}

/* --------------------------------------------------------------------------
 * 2) Restrict Size options to 39–44 on footwear products
 * -------------------------------------------------------------------------- */

/**
 * Limit variation dropdown options to allowed sizes for footwear.
 *
 * @param array $args Dropdown args.
 * @return array
 */
function safestore_footwear_restrict_dropdown_options( $args ) {
	if ( empty( $args['attribute'] ) || 'pa_size' !== $args['attribute'] ) {
		return $args;
	}

	$product = isset( $args['product'] ) ? $args['product'] : null;
	if ( ! safestore_is_footwear_product( $product ) ) {
		return $args;
	}

	if ( empty( $args['options'] ) || ! is_array( $args['options'] ) ) {
		return $args;
	}

	$args['options'] = array_values(
		array_filter(
			$args['options'],
			'safestore_footwear_is_allowed_size'
		)
	);

	return $args;
}
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', 'safestore_footwear_restrict_dropdown_options', 20 );

/**
 * Hide variations whose size is outside 39–44 for footwear.
 *
 * @param bool                 $active    Whether variation is active.
 * @param WC_Product_Variation $variation Variation.
 * @return bool
 */
function safestore_footwear_variation_is_active( $active, $variation ) {
	if ( ! $active || ! $variation instanceof WC_Product_Variation ) {
		return $active;
	}

	$parent = wc_get_product( $variation->get_parent_id() );
	if ( ! safestore_is_footwear_product( $parent ) ) {
		return $active;
	}

	$attrs = $variation->get_attributes();
	if ( empty( $attrs['pa_size'] ) ) {
		return $active;
	}

	return safestore_footwear_is_allowed_size( $attrs['pa_size'] );
}
add_filter( 'woocommerce_variation_is_active', 'safestore_footwear_variation_is_active', 10, 2 );

/**
 * Enrich available variation payload with stock + quantity limits.
 *
 * @param array                $data      Variation data for JS.
 * @param WC_Product_Variable  $product   Parent product.
 * @param WC_Product_Variation $variation Variation.
 * @return array
 */
function safestore_footwear_available_variation( $data, $product, $variation ) {
	if ( ! safestore_is_footwear_product( $product ) ) {
		return $data;
	}

	$attrs = $variation->get_attributes();
	if ( ! empty( $attrs['pa_size'] ) && ! safestore_footwear_is_allowed_size( $attrs['pa_size'] ) ) {
		$data['variation_is_active']   = false;
		$data['variation_is_visible']  = false;
		$data['is_purchasable']        = false;
		$data['is_in_stock']           = false;
		$data['max_qty']               = 0;
		return $data;
	}

	$limits = safestore_footwear_quantity_limits( $variation );

	$data['min_qty'] = $limits['min'];
	$data['max_qty'] = $limits['max'];
	$data['step']    = $limits['step'];

	$sku = $variation->get_sku();
	$data['safestore_sku'] = $sku ? $sku : '';
	/* translators: %s: variation SKU */
	$data['safestore_sku_html'] = $sku
		? sprintf( __( 'SKU: %s', 'safestore-minimal' ), $sku )
		: '';

	if ( $variation->managing_stock() ) {
		$stock = $variation->get_stock_quantity();
		$data['safestore_stock_qty'] = null === $stock ? null : (int) $stock;
		if ( null === $stock ) {
			$data['safestore_stock_html'] = '';
		} elseif ( (int) $stock <= 0 ) {
			$data['safestore_stock_html'] = __( 'Out of stock for this size', 'safestore-minimal' );
		} else {
			/* translators: %d: units in stock for this size */
			$data['safestore_stock_html'] = sprintf( __( '%d in stock for this size', 'safestore-minimal' ), (int) $stock );
		}
	} else {
		$data['safestore_stock_qty']  = $variation->is_in_stock() ? null : 0;
		$data['safestore_stock_html'] = $variation->is_in_stock()
			? __( 'In stock for this size', 'safestore-minimal' )
			: __( 'Out of stock for this size', 'safestore-minimal' );
	}

	$size_slug = ! empty( $attrs['pa_size'] ) ? safestore_footwear_normalize_size_option( $attrs['pa_size'] ) : '';
	$data['safestore_size'] = $size_slug;

	return $data;
}
add_filter( 'woocommerce_available_variation', 'safestore_footwear_available_variation', 20, 3 );

/**
 * Render Size as button swatches (39–44) on footwear PDPs.
 *
 * @param string $html HTML.
 * @param array  $args Args.
 * @return string
 */
/**
 * Keep WooCommerce's native Size <select> in the DOM (required for orders),
 * but mark it so CSS can hide it. Swatches are built in JS from its options.
 *
 * @param string $html Dropdown HTML.
 * @param array  $args Args.
 * @return string
 */
function safestore_footwear_size_swatches_html( $html, $args ) {
	$attribute_key = isset( $args['attribute'] ) ? sanitize_title( $args['attribute'] ) : '';
	if ( ! in_array( $attribute_key, array( 'pa_size', 'size' ), true ) ) {
		return $html;
	}

	$product = isset( $args['product'] ) ? $args['product'] : null;
	if ( ! safestore_is_footwear_product( $product ) ) {
		return $html;
	}

	// Ensure the native select is identifiable for hide + JS swatch builder.
	if ( false !== strpos( $html, 'class="' ) ) {
		$html = preg_replace( '/class="/', 'class="sft-size-dropdown ', $html, 1 );
	} else {
		$html = preg_replace( '/<select\b/', '<select class="sft-size-dropdown"', $html, 1 );
	}

	return $html;
}
add_filter( 'woocommerce_dropdown_variation_attribute_options_html', 'safestore_footwear_size_swatches_html', 20, 2 );

/**
 * Mark footwear PDPs for CSS/JS targeting.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function safestore_footwear_body_class( $classes ) {
	if ( function_exists( 'is_product' ) && is_product() && safestore_is_footwear_product() ) {
		$classes[] = 'sft-pdp-footwear';
	}
	return $classes;
}
add_filter( 'body_class', 'safestore_footwear_body_class' );

/* --------------------------------------------------------------------------
 * 3) Archive Size filter — removed (PDP-only size selection)
 * -------------------------------------------------------------------------- */

/**
 * Selected size filters from the request.
 *
 * @return string[]
 */
function safestore_footwear_requested_sizes() {
	$raw = array();

	if ( isset( $_GET['filter_size'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw = wp_unslash( $_GET['filter_size'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} elseif ( isset( $_GET['pa_size'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw = wp_unslash( $_GET['pa_size'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	if ( is_string( $raw ) ) {
		$raw = false !== strpos( $raw, ',' ) ? explode( ',', $raw ) : array( $raw );
	}

	if ( ! is_array( $raw ) ) {
		return array();
	}

	$allowed = array_map( 'sanitize_title', safestore_footwear_allowed_sizes() );
	$out     = array();

	foreach ( $raw as $size ) {
		$size = sanitize_title( (string) $size );
		if ( $size && in_array( $size, $allowed, true ) ) {
			$out[] = $size;
		}
	}

	return array_values( array_unique( $out ) );
}

/**
 * Apply pa_size tax query on footwear archives when filters are present.
 *
 * @param WP_Query $query Query.
 */
function safestore_footwear_product_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( ! safestore_is_footwear_archive() ) {
		return;
	}

	$sizes = safestore_footwear_requested_sizes();
	if ( ! $sizes ) {
		return;
	}

	$tax_query   = (array) $query->get( 'tax_query' );
	$tax_query[] = array(
		'taxonomy' => 'pa_size',
		'field'    => 'slug',
		'terms'    => $sizes,
		'operator' => 'IN',
	);
	$query->set( 'tax_query', $tax_query );
}

// Archive size-filter UI + query hook removed — size is selected on the PDP only.
// Helpers above remain available if a filter UI is reintroduced later.

/**
 * Hide WooCommerce layered-nav / attribute filter widgets for Size on non-footwear views.
 *
 * @param array $instance Widget instance.
 * @param array $widget   Widget settings.
 * @return array|false
 */
function safestore_footwear_hide_size_widgets( $instance, $widget ) {
	if ( empty( $instance ) || ! is_array( $instance ) ) {
		return $instance;
	}

	$attribute = '';
	if ( ! empty( $instance['attribute'] ) ) {
		$attribute = sanitize_title( $instance['attribute'] );
	} elseif ( ! empty( $widget['classname'] ) && false !== strpos( (string) $widget['classname'], 'pa_size' ) ) {
		$attribute = 'size';
	}

	if ( 'size' !== $attribute && 'pa_size' !== $attribute ) {
		return $instance;
	}

	if ( safestore_is_footwear_archive() ) {
		return $instance;
	}

	// Hide Size filter widgets outside footwear archives (shop home, helmets, etc.).
	return false;
}
add_filter( 'widget_display_callback', 'safestore_footwear_hide_size_widgets', 10, 2 );

/* --------------------------------------------------------------------------
 * 4) Size-wise stock validation (cart / checkout)
 * -------------------------------------------------------------------------- */

/**
 * Resolve the product used for stock checks (variation preferred).
 *
 * @param int   $product_id   Parent ID.
 * @param int   $variation_id Variation ID.
 * @param array $variation    Attribute array.
 * @return WC_Product|false
 */
function safestore_footwear_resolvable_product( $product_id, $variation_id = 0, $variation = array() ) {
	if ( $variation_id ) {
		$product = wc_get_product( $variation_id );
		if ( $product ) {
			return $product;
		}
	}

	$product = wc_get_product( $product_id );
	if ( $product && $product->is_type( 'variable' ) && ! empty( $variation['attribute_pa_size'] ) ) {
		$data_store   = WC_Data_Store::load( 'product' );
		$found_id     = $data_store->find_matching_product_variation( $product, $variation );
		if ( $found_id ) {
			return wc_get_product( $found_id );
		}
	}

	return $product ? $product : false;
}

/**
 * Validate add-to-cart quantity against per-size stock + retail/B2B caps.
 *
 * @param bool  $passed       Whether validation passed.
 * @param int   $product_id   Product ID.
 * @param int   $quantity     Quantity.
 * @param int   $variation_id Variation ID.
 * @param array $variations   Variation attributes.
 * @return bool
 */
function safestore_footwear_add_to_cart_validation( $passed, $product_id, $quantity, $variation_id = 0, $variations = array() ) {
	if ( ! $passed ) {
		return $passed;
	}

	$product = safestore_footwear_resolvable_product( $product_id, $variation_id, $variations );
	if ( ! $product || ! safestore_is_footwear_product( $product ) ) {
		return $passed;
	}

	if ( $product->is_type( 'variable' ) && ! $variation_id ) {
		wc_add_notice( __( 'Please choose a size (39–44) before adding to cart.', 'safestore-minimal' ), 'error' );
		return false;
	}

	if ( $product->is_type( 'variation' ) ) {
		$attrs = $product->get_attributes();
		if ( ! empty( $attrs['pa_size'] ) && ! safestore_footwear_is_allowed_size( $attrs['pa_size'] ) ) {
			wc_add_notice( __( 'That size is not available for Safety Shoes. Choose EU 39–44.', 'safestore-minimal' ), 'error' );
			return false;
		}
	}

	$limits = safestore_footwear_quantity_limits( $product );
	$qty    = (int) $quantity;

	if ( $limits['max'] <= 0 || ! $product->is_in_stock() ) {
		wc_add_notice( __( 'This size is out of stock.', 'safestore-minimal' ), 'error' );
		return false;
	}

	// Include quantity already in cart for the same variation line.
	$in_cart = 0;
	if ( WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $item ) {
			$item_id = ! empty( $item['variation_id'] ) ? (int) $item['variation_id'] : (int) $item['product_id'];
			if ( $item_id === (int) $product->get_id() ) {
				$in_cart += (int) $item['quantity'];
			}
		}
	}

	if ( ( $in_cart + $qty ) > $limits['max'] ) {
		$remaining = max( 0, $limits['max'] - $in_cart );
		wc_add_notice(
			sprintf(
				/* translators: 1: max qty, 2: remaining */
				__( 'Only %1$d available for this size. You can add %2$d more.', 'safestore-minimal' ),
				$limits['max'],
				$remaining
			),
			'error'
		);
		return false;
	}

	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'safestore_footwear_add_to_cart_validation', 20, 5 );

/**
 * Validate cart updates against per-size stock.
 *
 * @param bool   $passed         Passed.
 * @param string $cart_item_key  Cart key.
 * @param array  $values         Cart item.
 * @param int    $quantity       New quantity.
 * @return bool
 */
function safestore_footwear_update_cart_validation( $passed, $cart_item_key, $values, $quantity ) {
	if ( ! $passed ) {
		return $passed;
	}

	$product_id   = isset( $values['product_id'] ) ? (int) $values['product_id'] : 0;
	$variation_id = isset( $values['variation_id'] ) ? (int) $values['variation_id'] : 0;
	$product      = safestore_footwear_resolvable_product( $product_id, $variation_id, isset( $values['variation'] ) ? $values['variation'] : array() );

	if ( ! $product || ! safestore_is_footwear_product( $product ) ) {
		return $passed;
	}

	$limits = safestore_footwear_quantity_limits( $product );
	if ( (int) $quantity > $limits['max'] ) {
		wc_add_notice(
			sprintf(
				/* translators: %d: max qty for size */
				__( 'Maximum quantity for this size is %d.', 'safestore-minimal' ),
				$limits['max']
			),
			'error'
		);
		return false;
	}

	return $passed;
}
add_filter( 'woocommerce_update_cart_validation', 'safestore_footwear_update_cart_validation', 20, 4 );

/**
 * Quantity input args — variation-aware max for footwear.
 *
 * @param array      $args    Input args.
 * @param WC_Product $product Product.
 * @return array
 */
function safestore_footwear_quantity_input_args( $args, $product ) {
	if ( ! $product instanceof WC_Product || ! safestore_is_footwear_product( $product ) ) {
		return $args;
	}

	// On variable parent, wait until a variation is chosen (JS updates max).
	if ( $product->is_type( 'variable' ) ) {
		$args['min_value'] = 1;
		$args['step']      = 1;
		return $args;
	}

	$limits            = safestore_footwear_quantity_limits( $product );
	$args['min_value'] = $limits['min'];
	$args['max_value'] = $limits['max'] > 0 ? $limits['max'] : '';
	$args['step']      = $limits['step'];

	if ( isset( $args['input_value'] ) && $limits['max'] > 0 ) {
		$args['input_value'] = min( (int) $args['input_value'], $limits['max'] );
	}

	return $args;
}
add_filter( 'woocommerce_quantity_input_args', 'safestore_footwear_quantity_input_args', 20, 2 );

/**
 * Show size in cart line item name for clarity.
 *
 * @param string $name Name HTML.
 * @param array  $cart_item Cart item.
 * @return string
 */
function safestore_footwear_cart_item_name( $name, $cart_item ) {
	if ( empty( $cart_item['variation_id'] ) ) {
		return $name;
	}

	$variation = wc_get_product( $cart_item['variation_id'] );
	if ( ! $variation || ! safestore_is_footwear_product( $variation ) ) {
		return $name;
	}

	$attrs = $variation->get_attributes();
	if ( empty( $attrs['pa_size'] ) ) {
		return $name;
	}

	$size_label = $attrs['pa_size'];
	$term       = get_term_by( 'slug', $attrs['pa_size'], 'pa_size' );
	if ( $term && ! is_wp_error( $term ) ) {
		$size_label = $term->name;
	}

	$name .= ' <span class="sft-cart-size">(' . esc_html( sprintf( __( 'Size %s', 'safestore-minimal' ), $size_label ) ) . ')</span>';
	return $name;
}
add_filter( 'woocommerce_cart_item_name', 'safestore_footwear_cart_item_name', 20, 2 );

/* --------------------------------------------------------------------------
 * 5) B2B multi-size quantity matrix on footwear PDPs
 * -------------------------------------------------------------------------- */

/**
 * Output a size × quantity matrix for B2B / bulk footwear orders.
 */
function safestore_footwear_b2b_size_matrix() {
	global $product;

	if ( ! $product instanceof WC_Product_Variable || ! safestore_is_footwear_product( $product ) ) {
		return;
	}

	// Always show a compact matrix for footwear; B2B gets higher caps via limits.
	$variations = array();
	foreach ( $product->get_children() as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation || ! $variation->exists() ) {
			continue;
		}
		$attrs = $variation->get_attributes();
		if ( empty( $attrs['pa_size'] ) || ! safestore_footwear_is_allowed_size( $attrs['pa_size'] ) ) {
			continue;
		}
		$variations[ safestore_footwear_normalize_size_option( $attrs['pa_size'] ) ] = $variation;
	}

	if ( ! $variations ) {
		return;
	}

	$order = array_map( 'sanitize_title', safestore_footwear_allowed_sizes() );
	uksort(
		$variations,
		static function ( $a, $b ) use ( $order ) {
			$ai = array_search( $a, $order, true );
			$bi = array_search( $b, $order, true );
			return ( false === $ai ? 99 : $ai ) <=> ( false === $bi ? 99 : $bi );
		}
	);

	$is_b2b = safestore_is_b2b_customer();
	?>
	<div class="sft-size-matrix" data-product-id="<?php echo (int) $product->get_id(); ?>">
		<div class="sft-size-matrix__head">
			<strong><?php echo $is_b2b ? esc_html__( 'Bulk order by size', 'safestore-minimal' ) : esc_html__( 'Quick add by size', 'safestore-minimal' ); ?></strong>
			<span><?php esc_html_e( 'Enter qty per size — inventory is reserved per size, not from a shared pool.', 'safestore-minimal' ); ?></span>
		</div>
		<table class="sft-size-matrix__table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Size', 'safestore-minimal' ); ?></th>
					<th><?php esc_html_e( 'Stock', 'safestore-minimal' ); ?></th>
					<th><?php esc_html_e( 'Qty', 'safestore-minimal' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $variations as $slug => $variation ) : ?>
					<?php
					$limits = safestore_footwear_quantity_limits( $variation );
					$stock  = $variation->managing_stock() ? $variation->get_stock_quantity() : null;
					$label  = $slug;
					$term   = get_term_by( 'slug', $slug, 'pa_size' );
					if ( $term && ! is_wp_error( $term ) ) {
						$label = $term->name;
					}
					$disabled = ! $variation->is_in_stock() || $limits['max'] <= 0;
					?>
					<tr class="<?php echo $disabled ? 'is-oos' : ''; ?>">
						<td><?php echo esc_html( $label ); ?></td>
						<td>
							<?php
							if ( $disabled ) {
								esc_html_e( 'Out of stock', 'safestore-minimal' );
							} elseif ( null !== $stock ) {
								echo esc_html( (string) (int) $stock );
							} else {
								esc_html_e( 'In stock', 'safestore-minimal' );
							}
							?>
						</td>
						<td>
							<input
								type="number"
								class="sft-size-matrix__qty"
								min="0"
								max="<?php echo esc_attr( (string) max( 0, $limits['max'] ) ); ?>"
								step="<?php echo esc_attr( (string) $limits['step'] ); ?>"
								value="0"
								inputmode="numeric"
								data-variation-id="<?php echo (int) $variation->get_id(); ?>"
								data-size="<?php echo esc_attr( $slug ); ?>"
								aria-label="<?php echo esc_attr( sprintf( __( 'Quantity for size %s', 'safestore-minimal' ), $label ) ); ?>"
								<?php disabled( $disabled ); ?>
							>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<button type="button" class="button alt sft-size-matrix__submit">
			<?php echo $is_b2b ? esc_html__( 'Add sizes to cart', 'safestore-minimal' ) : esc_html__( 'Add selected sizes', 'safestore-minimal' ); ?>
		</button>
		<p class="sft-size-matrix__note">
			<?php
			if ( $is_b2b ) {
				esc_html_e( 'B2B limits apply (higher per-size max). For factory lots, WhatsApp us for a quote.', 'safestore-minimal' );
			} else {
				esc_html_e( 'Retail max applies per size. Need a larger site order? Use Bulk / corporate.', 'safestore-minimal' );
			}
			?>
		</p>
	</div>
	<?php
}
add_action( 'woocommerce_after_single_product_summary', 'safestore_footwear_b2b_size_matrix', 6 );

/**
 * AJAX: add multiple size variations in one request.
 */
function safestore_footwear_ajax_add_sizes() {
	check_ajax_referer( 'safestore_footwear_sizing', 'nonce' );

	if ( empty( $_POST['items'] ) || ! is_array( $_POST['items'] ) ) {
		wp_send_json_error( array( 'message' => __( 'No sizes selected.', 'safestore-minimal' ) ), 400 );
	}

	$added   = 0;
	$errors  = array();
	$items   = wp_unslash( $_POST['items'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	foreach ( $items as $item ) {
		$variation_id = isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0;
		$quantity     = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 0;

		if ( ! $variation_id || $quantity < 1 ) {
			continue;
		}

		$variation = wc_get_product( $variation_id );
		if ( ! $variation || ! $variation->is_type( 'variation' ) || ! safestore_is_footwear_product( $variation ) ) {
			$errors[] = __( 'Invalid size variation.', 'safestore-minimal' );
			continue;
		}

		$parent_id = $variation->get_parent_id();
		$attrs     = array();
		foreach ( $variation->get_variation_attributes() as $key => $value ) {
			$attrs[ $key ] = $value;
		}

		$passed = apply_filters(
			'woocommerce_add_to_cart_validation',
			true,
			$parent_id,
			$quantity,
			$variation_id,
			$attrs
		);

		if ( ! $passed ) {
			$errors[] = sprintf(
				/* translators: %s: size */
				__( 'Could not add size %s.', 'safestore-minimal' ),
				isset( $attrs['attribute_pa_size'] ) ? $attrs['attribute_pa_size'] : (string) $variation_id
			);
			continue;
		}

		$result = WC()->cart->add_to_cart( $parent_id, $quantity, $variation_id, $attrs );
		if ( $result ) {
			$added++;
		}
	}

	if ( ! $added ) {
		wp_send_json_error(
			array(
				'message' => $errors ? implode( ' ', $errors ) : __( 'Nothing was added to the cart.', 'safestore-minimal' ),
			),
			400
		);
	}

	wp_send_json_success(
		array(
			/* translators: %d: number of size lines added */
			'message'    => sprintf( __( 'Added %d size line(s) to your cart.', 'safestore-minimal' ), $added ),
			'cart_count' => WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
			'fragments'  => apply_filters(
				'woocommerce_add_to_cart_fragments',
				array()
			),
		)
	);
}
add_action( 'wp_ajax_safestore_add_sizes', 'safestore_footwear_ajax_add_sizes' );
add_action( 'wp_ajax_nopriv_safestore_add_sizes', 'safestore_footwear_ajax_add_sizes' );

/* --------------------------------------------------------------------------
 * Assets
 * -------------------------------------------------------------------------- */

/**
 * Enqueue footwear sizing CSS/JS when relevant.
 */
function safestore_footwear_enqueue_assets() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$need = is_product() || safestore_is_footwear_archive() || is_cart();
	if ( ! $need ) {
		return;
	}

	$css = get_template_directory() . '/css/footwear-sizing.css';
	$js  = get_template_directory() . '/js/footwear-sizing.js';

	if ( file_exists( $css ) ) {
		wp_enqueue_style(
			'safestore-footwear-sizing',
			get_template_directory_uri() . '/css/footwear-sizing.css',
			array( 'safestore-minimal-style' ),
			(string) filemtime( $css )
		);
	}

	if ( file_exists( $js ) && ( is_product() || is_cart() ) ) {
		if ( is_product() ) {
			wp_enqueue_script( 'wc-add-to-cart-variation' );
		}

		wp_enqueue_script(
			'safestore-footwear-sizing',
			get_template_directory_uri() . '/js/footwear-sizing.js',
			array( 'jquery', 'wc-add-to-cart-variation' ),
			(string) filemtime( $js ),
			function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true
		);

		wp_localize_script(
			'safestore-footwear-sizing',
			'safestoreFootwear',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'safestore_footwear_sizing' ),
				'i18n'    => array(
					'selectSize' => __( 'Please select a size first', 'safestore-minimal' ),
					'noQty'      => __( 'Enter a quantity for at least one size.', 'safestore-minimal' ),
					'adding'     => __( 'Adding…', 'safestore-minimal' ),
					'stockHint'  => __( '%d in stock for this size', 'safestore-minimal' ),
					'inStock'    => __( 'In stock for this size', 'safestore-minimal' ),
					'outOfStock' => __( 'Out of stock for this size', 'safestore-minimal' ),
					'skuPrefix'  => __( 'SKU: %s', 'safestore-minimal' ),
				),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'safestore_footwear_enqueue_assets', 30 );

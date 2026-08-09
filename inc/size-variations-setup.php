<?php
/**
 * Safety Shoes size variations setup
 *
 * Admin tool (WooCommerce → Size Variations) that:
 * 1. Creates the global Size attribute (pa_size)
 * 2. Adds terms 39–44
 * 3. Converts simple products in the Safety Shoes category into
 *    variable products with one variation per size
 *
 * @package SafeStore_Minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Size values used for Safety Shoes.
 *
 * @return string[]
 */
function safestore_size_terms() {
	return array( '39', '40', '41', '42', '43', '44' );
}

/**
 * Category slug for Safety Shoes.
 *
 * @return string
 */
function safestore_size_category_slug() {
	return 'safety-shoes';
}

/**
 * Register admin submenu under WooCommerce.
 */
function safestore_size_admin_menu() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_submenu_page(
		'woocommerce',
		__( 'Size Variations', 'safestore-minimal' ),
		__( 'Size Variations', 'safestore-minimal' ),
		'manage_woocommerce',
		'safestore-size-variations',
		'safestore_size_admin_page'
	);
}
add_action( 'admin_menu', 'safestore_size_admin_menu' );

/**
 * Ensure pa_size exists and register it for the current request.
 *
 * @return int|WP_Error Attribute ID on success.
 */
function safestore_size_ensure_attribute() {
	if ( ! function_exists( 'wc_create_attribute' ) ) {
		return new WP_Error( 'no_wc', __( 'WooCommerce is not active.', 'safestore-minimal' ) );
	}

	$attribute_id = wc_attribute_taxonomy_id_by_name( 'size' );

	if ( ! $attribute_id ) {
		$attribute_id = wc_create_attribute(
			array(
				'name'         => 'Size',
				'slug'         => 'size',
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);

		if ( is_wp_error( $attribute_id ) ) {
			return $attribute_id;
		}
	}

	$taxonomy = 'pa_size';

	if ( ! taxonomy_exists( $taxonomy ) ) {
		register_taxonomy(
			$taxonomy,
			apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy, array( 'product' ) ),
			apply_filters(
				'woocommerce_taxonomy_args_' . $taxonomy,
				array(
					'labels'       => array(
						'name' => __( 'Size', 'safestore-minimal' ),
					),
					'hierarchical' => false,
					'show_ui'      => false,
					'query_var'    => true,
					'rewrite'      => false,
				)
			)
		);
	}

	delete_transient( 'wc_attribute_taxonomies' );
	if ( class_exists( 'WC_Cache_Helper' ) ) {
		WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
	}

	return (int) $attribute_id;
}

/**
 * Ensure Size terms 39–44 exist.
 *
 * @return array{term_ids: int[], created: string[], existing: string[]}|WP_Error
 */
function safestore_size_ensure_terms() {
	$attr = safestore_size_ensure_attribute();
	if ( is_wp_error( $attr ) ) {
		return $attr;
	}

	$taxonomy = 'pa_size';
	$term_ids = array();
	$created  = array();
	$existing = array();

	foreach ( safestore_size_terms() as $size ) {
		$found = term_exists( $size, $taxonomy );

		if ( ! $found ) {
			$result = wp_insert_term( $size, $taxonomy );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$term_ids[] = (int) $result['term_id'];
			$created[]  = $size;
			continue;
		}

		$term_ids[] = (int) ( is_array( $found ) ? $found['term_id'] : $found );
		$existing[] = $size;
	}

	return array(
		'term_ids' => $term_ids,
		'created'  => $created,
		'existing' => $existing,
	);
}

/**
 * Resolve Size term slugs (creates missing terms first).
 *
 * @return string[]|WP_Error
 */
function safestore_size_get_term_slugs() {
	$ensured = safestore_size_ensure_terms();
	if ( is_wp_error( $ensured ) ) {
		return $ensured;
	}

	$slugs = array();
	foreach ( safestore_size_terms() as $size ) {
		$term = get_term_by( 'name', $size, 'pa_size' );
		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error(
				'missing_term',
				sprintf(
					/* translators: %s: size label */
					__( 'Could not resolve Size term “%s”.', 'safestore-minimal' ),
					$size
				)
			);
		}
		$slugs[] = $term->slug;
	}

	return $slugs;
}

/**
 * Product IDs in the Safety Shoes category.
 *
 * @return int[]
 */
function safestore_size_get_safety_shoe_product_ids() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => safestore_size_category_slug(),
				),
			),
		)
	);

	return array_map( 'intval', $ids );
}

/**
 * Whether a product already has Size used for variations with all expected terms.
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function safestore_size_product_has_size_variations( $product ) {
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return false;
	}

	$attributes = $product->get_attributes();
	if ( empty( $attributes['pa_size'] ) || ! $attributes['pa_size']->get_variation() ) {
		return false;
	}

	$options = array_map( 'strval', (array) $attributes['pa_size']->get_options() );
	$needed  = safestore_size_get_term_slugs();
	if ( is_wp_error( $needed ) ) {
		return false;
	}

	// Options may be term IDs or slugs depending on how they were saved.
	$normalized = array();
	foreach ( $options as $option ) {
		if ( is_numeric( $option ) ) {
			$term = get_term( (int) $option, 'pa_size' );
			if ( $term && ! is_wp_error( $term ) ) {
				$normalized[] = $term->slug;
			}
		} else {
			$normalized[] = $option;
		}
	}

	foreach ( $needed as $slug ) {
		if ( ! in_array( $slug, $normalized, true ) ) {
			return false;
		}
	}

	$children = $product->get_children();
	return count( $children ) >= count( $needed );
}

/**
 * Convert a simple (or incomplete variable) product to Size variations.
 *
 * @param int $product_id Product ID.
 * @return array{product_id: int, variation_ids: int[], skipped?: bool, message: string}|WP_Error
 */
function safestore_size_convert_product( $product_id ) {
	$product_id = (int) $product_id;
	$product    = wc_get_product( $product_id );

	if ( ! $product ) {
		return new WP_Error( 'not_found', __( 'Product not found.', 'safestore-minimal' ) );
	}

	if ( safestore_size_product_has_size_variations( $product ) ) {
		return array(
			'product_id'     => $product_id,
			'variation_ids'  => $product->get_children(),
			'skipped'        => true,
			'message'        => __( 'Already has Size variations — skipped.', 'safestore-minimal' ),
		);
	}

	if ( $product->is_type( 'grouped' ) || $product->is_type( 'external' ) ) {
		return new WP_Error(
			'unsupported_type',
			sprintf(
				/* translators: %s: product type */
				__( 'Unsupported product type “%s”.', 'safestore-minimal' ),
				$product->get_type()
			)
		);
	}

	$slugs = safestore_size_get_term_slugs();
	if ( is_wp_error( $slugs ) ) {
		return $slugs;
	}

	$attribute_id = wc_attribute_taxonomy_id_by_name( 'size' );
	if ( ! $attribute_id ) {
		return new WP_Error( 'no_attribute', __( 'Size attribute is missing.', 'safestore-minimal' ) );
	}

	// Preserve pricing / stock from the current product (or first variation).
	$regular_price = $product->get_regular_price();
	$sale_price    = $product->get_sale_price();
	$sku           = $product->get_sku();
	$stock_status  = $product->get_stock_status();
	$parent_qty    = $product->managing_stock() ? $product->get_stock_quantity() : null;

	if ( $product->is_type( 'variable' ) ) {
		$children = $product->get_children();
		if ( ! empty( $children ) ) {
			$first = wc_get_product( $children[0] );
			if ( $first ) {
				if ( $regular_price === '' || $regular_price === null ) {
					$regular_price = $first->get_regular_price();
				}
				if ( $sale_price === '' || $sale_price === null ) {
					$sale_price = $first->get_sale_price();
				}
				if ( ! $stock_status ) {
					$stock_status = $first->get_stock_status();
				}
			}
		}
	}

	// Default per-size stock: split parent qty across sizes, else filterable default.
	$size_count       = max( 1, count( $slugs ) );
	$default_size_qty = (int) apply_filters( 'safestore_default_size_stock', 10, $product_id );
	if ( null !== $parent_qty && (int) $parent_qty > 0 ) {
		$default_size_qty = max( 1, (int) floor( (int) $parent_qty / $size_count ) );
	}

	wp_set_object_terms( $product_id, $slugs, 'pa_size' );

	$attribute = new WC_Product_Attribute();
	$attribute->set_id( $attribute_id );
	$attribute->set_name( 'pa_size' );
	$attribute->set_options( $slugs );
	$attribute->set_position( 0 );
	$attribute->set_visible( true );
	$attribute->set_variation( true );

	// Keep non-variation attributes; replace / add pa_size.
	$attributes = array();
	foreach ( $product->get_attributes() as $key => $existing_attr ) {
		if ( 'pa_size' === $key || 'size' === $key ) {
			continue;
		}
		$attributes[ $key ] = $existing_attr;
	}
	$attributes['pa_size'] = $attribute;

	if ( ! $product->is_type( 'variable' ) ) {
		wp_set_object_terms( $product_id, 'variable', 'product_type' );
	}

	$variable = new WC_Product_Variable( $product_id );
	$variable->set_attributes( $attributes );
	// Stock lives on each size variation — disable shared parent pool.
	$variable->set_manage_stock( false );
	$variable->save();

	// Map existing variations by size slug so we update instead of duplicating.
	$existing_by_slug = array();
	foreach ( $variable->get_children() as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			continue;
		}
		$attrs = $variation->get_attributes();
		if ( ! empty( $attrs['pa_size'] ) ) {
			$existing_by_slug[ $attrs['pa_size'] ] = (int) $variation_id;
		}
	}

	$variation_ids = array();
	$price         = ( $regular_price !== '' && $regular_price !== null ) ? $regular_price : '0';

	foreach ( $slugs as $slug ) {
		if ( isset( $existing_by_slug[ $slug ] ) ) {
			$variation = new WC_Product_Variation( $existing_by_slug[ $slug ] );
		} else {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product_id );
		}

		$variation->set_attributes( array( 'pa_size' => $slug ) );
		$variation->set_status( 'publish' );
		$variation->set_regular_price( $price );

		if ( $sale_price !== '' && $sale_price !== null ) {
			$variation->set_sale_price( $sale_price );
		} else {
			$variation->set_sale_price( '' );
		}

		if ( $sku ) {
			$candidate = $sku . '-' . strtoupper( $slug );
			$owner     = wc_get_product_id_by_sku( $candidate );
			if ( ! $owner || (int) $owner === (int) $variation->get_id() ) {
				$variation->set_sku( $candidate );
			}
		}

		// Inventory is tracked per size (variation), not as a shared parent pool.
		$existing_managed = $variation->get_id() && $variation->managing_stock();
		$existing_qty     = $existing_managed ? $variation->get_stock_quantity() : null;

		$variation->set_manage_stock( true );
		if ( null !== $existing_qty && (int) $existing_qty >= 0 ) {
			$variation->set_stock_quantity( (int) $existing_qty );
		} else {
			$variation->set_stock_quantity( max( 0, (int) $default_size_qty ) );
		}
		$variation->set_stock_status(
			( (int) $variation->get_stock_quantity() > 0 )
				? 'instock'
				: ( $stock_status ? $stock_status : 'outofstock' )
		);
		$variation->save();

		$variation_ids[] = $variation->get_id();
	}

	WC_Product_Variable::sync( $product_id );
	wc_delete_product_transients( $product_id );

	return array(
		'product_id'    => $product_id,
		'variation_ids' => $variation_ids,
		'message'       => sprintf(
			/* translators: 1: product ID, 2: variation count */
			__( 'Converted product #%1$d — %2$d size variations ready.', 'safestore-minimal' ),
			$product_id,
			count( $variation_ids )
		),
	);
}

/**
 * Enable managed stock on existing Size variations (idempotent).
 *
 * @param int $product_id Parent product ID.
 * @return array{product_id: int, updated: int, message: string}|WP_Error
 */
function safestore_size_enable_variation_stock( $product_id ) {
	$product_id = (int) $product_id;
	$product    = wc_get_product( $product_id );

	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return new WP_Error( 'not_variable', __( 'Product must be a variable product.', 'safestore-minimal' ) );
	}

	$default_qty = (int) apply_filters( 'safestore_default_size_stock', 10, $product_id );
	$updated     = 0;

	$product->set_manage_stock( false );
	$product->save();

	foreach ( $product->get_children() as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			continue;
		}

		$attrs = $variation->get_attributes();
		if ( empty( $attrs['pa_size'] ) ) {
			continue;
		}

		$qty = $variation->managing_stock() ? $variation->get_stock_quantity() : null;
		$variation->set_manage_stock( true );
		if ( null === $qty || '' === $qty ) {
			$variation->set_stock_quantity( max( 0, $default_qty ) );
		}
		$variation->set_stock_status( ( (int) $variation->get_stock_quantity() > 0 ) ? 'instock' : 'outofstock' );
		$variation->save();
		$updated++;
	}

	WC_Product_Variable::sync( $product_id );
	wc_delete_product_transients( $product_id );

	return array(
		'product_id' => $product_id,
		'updated'    => $updated,
		'message'    => sprintf(
			/* translators: 1: product ID, 2: variation count */
			__( 'Enabled per-size stock on product #%1$d (%2$d variations).', 'safestore-minimal' ),
			$product_id,
			$updated
		),
	);
}

/**
 * Handle admin form actions.
 */
function safestore_size_handle_actions() {
	if ( ! is_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	if ( empty( $_POST['safestore_size_action'] ) ) {
		return;
	}

	check_admin_referer( 'safestore_size_variations' );

	$action  = sanitize_key( wp_unslash( $_POST['safestore_size_action'] ) );
	$notices = array();

	if ( 'ensure_attribute' === $action ) {
		$result = safestore_size_ensure_terms();
		if ( is_wp_error( $result ) ) {
			$notices[] = array(
				'type' => 'error',
				'text' => $result->get_error_message(),
			);
		} else {
			$notices[] = array(
				'type' => 'success',
				'text' => sprintf(
					/* translators: 1: created count, 2: existing count */
					__( 'Size attribute ready. Created %1$d term(s), %2$d already existed (39–44).', 'safestore-minimal' ),
					count( $result['created'] ),
					count( $result['existing'] )
				),
			);
		}
	}

	if ( 'convert_one' === $action ) {
		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$result     = safestore_size_convert_product( $product_id );
		if ( is_wp_error( $result ) ) {
			$notices[] = array(
				'type' => 'error',
				'text' => $result->get_error_message(),
			);
		} else {
			$notices[] = array(
				'type' => ! empty( $result['skipped'] ) ? 'info' : 'success',
				'text' => $result['message'],
			);
		}
	}

	if ( 'convert_all' === $action ) {
		$ids     = safestore_size_get_safety_shoe_product_ids();
		$ok      = 0;
		$skipped = 0;
		$failed  = 0;

		foreach ( $ids as $product_id ) {
			$result = safestore_size_convert_product( $product_id );
			if ( is_wp_error( $result ) ) {
				$failed++;
				continue;
			}
			if ( ! empty( $result['skipped'] ) ) {
				$skipped++;
			} else {
				$ok++;
			}
		}

		$notices[] = array(
			'type' => $failed ? 'warning' : 'success',
			'text' => sprintf(
				/* translators: 1: converted, 2: skipped, 3: failed */
				__( 'Bulk done — converted: %1$d, skipped: %2$d, failed: %3$d.', 'safestore-minimal' ),
				$ok,
				$skipped,
				$failed
			),
		);
	}

	if ( 'enable_stock_all' === $action ) {
		$ids     = safestore_size_get_safety_shoe_product_ids();
		$updated = 0;
		$failed  = 0;

		foreach ( $ids as $product_id ) {
			$result = safestore_size_enable_variation_stock( $product_id );
			if ( is_wp_error( $result ) ) {
				$failed++;
				continue;
			}
			$updated += (int) $result['updated'];
		}

		$notices[] = array(
			'type' => $failed ? 'warning' : 'success',
			'text' => sprintf(
				/* translators: 1: variation count, 2: failed products */
				__( 'Per-size stock enabled on %1$d variations (%2$d products skipped/failed).', 'safestore-minimal' ),
				$updated,
				$failed
			),
		);
	}

	set_transient( 'safestore_size_admin_notices_' . get_current_user_id(), $notices, 60 );

	wp_safe_redirect( admin_url( 'admin.php?page=safestore-size-variations' ) );
	exit;
}
add_action( 'admin_init', 'safestore_size_handle_actions' );

/**
 * Render admin page.
 */
function safestore_size_admin_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'WooCommerce is required.', 'safestore-minimal' ) . '</p></div></div>';
		return;
	}

	$notices = get_transient( 'safestore_size_admin_notices_' . get_current_user_id() );
	if ( is_array( $notices ) ) {
		delete_transient( 'safestore_size_admin_notices_' . get_current_user_id() );
		foreach ( $notices as $notice ) {
			$type = isset( $notice['type'] ) ? $notice['type'] : 'info';
			$text = isset( $notice['text'] ) ? $notice['text'] : '';
			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $type ),
				esc_html( $text )
			);
		}
	}

	$attribute_id = wc_attribute_taxonomy_id_by_name( 'size' );
	$term_count   = taxonomy_exists( 'pa_size' ) ? (int) wp_count_terms(
		array(
			'taxonomy'   => 'pa_size',
			'hide_empty' => false,
		)
	) : 0;
	if ( is_wp_error( $term_count ) ) {
		$term_count = 0;
	}

	$cat      = get_term_by( 'slug', safestore_size_category_slug(), 'product_cat' );
	$products = safestore_size_get_safety_shoe_product_ids();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Safety Shoes — Size Variations', 'safestore-minimal' ); ?></h1>
		<p><?php esc_html_e( 'Create the global Size attribute (pa_size), add EU sizes 39–44, then convert Safety Shoes products into variable products.', 'safestore-minimal' ); ?></p>

		<div class="card" style="max-width:720px;padding:12px 16px;margin:16px 0;">
			<h2 style="margin-top:0;"><?php esc_html_e( '1. Attribute status', 'safestore-minimal' ); ?></h2>
			<ul>
				<li>
					<?php
					echo $attribute_id
						? esc_html( sprintf( __( 'Size attribute exists (ID %d, taxonomy pa_size).', 'safestore-minimal' ), $attribute_id ) )
						: esc_html__( 'Size attribute is not created yet.', 'safestore-minimal' );
					?>
				</li>
				<li>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: term count */
							__( 'pa_size terms currently: %d', 'safestore-minimal' ),
							$term_count
						)
					);
					?>
				</li>
				<li>
					<?php
					echo $cat && ! is_wp_error( $cat )
						? esc_html( sprintf( __( 'Category “Safety Shoes” found (slug: %s).', 'safestore-minimal' ), safestore_size_category_slug() ) )
						: esc_html( sprintf( __( 'Category slug “%s” not found — check Products → Categories.', 'safestore-minimal' ), safestore_size_category_slug() ) );
					?>
				</li>
			</ul>

			<form method="post">
				<?php wp_nonce_field( 'safestore_size_variations' ); ?>
				<input type="hidden" name="safestore_size_action" value="ensure_attribute">
				<?php submit_button( __( 'Create / sync Size attribute + terms (39–44)', 'safestore-minimal' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>

		<div class="card" style="max-width:960px;padding:12px 16px;margin:16px 0;">
			<h2 style="margin-top:0;"><?php esc_html_e( '2. Convert Safety Shoes products', 'safestore-minimal' ); ?></h2>
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: product count */
						__( '%d product(s) in Safety Shoes. Each conversion keeps the current price and creates variations for sizes 39–44.', 'safestore-minimal' ),
						count( $products )
					)
				);
				?>
			</p>

			<?php if ( $products ) : ?>
				<form method="post" style="margin-bottom:12px;display:inline-block;margin-right:8px;" onsubmit="return confirm('<?php echo esc_js( __( 'Convert ALL Safety Shoes products to Size variations?', 'safestore-minimal' ) ); ?>');">
					<?php wp_nonce_field( 'safestore_size_variations' ); ?>
					<input type="hidden" name="safestore_size_action" value="convert_all">
					<?php submit_button( __( 'Convert all Safety Shoes products', 'safestore-minimal' ), 'primary', 'submit', false ); ?>
				</form>
				<form method="post" style="margin-bottom:16px;display:inline-block;" onsubmit="return confirm('<?php echo esc_js( __( 'Enable managed stock on every Size variation? Existing qty values are kept; missing qty defaults to 10.', 'safestore-minimal' ) ); ?>');">
					<?php wp_nonce_field( 'safestore_size_variations' ); ?>
					<input type="hidden" name="safestore_size_action" value="enable_stock_all">
					<?php submit_button( __( 'Enable per-size stock on all', 'safestore-minimal' ), 'secondary', 'submit', false ); ?>
				</form>

				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'ID', 'safestore-minimal' ); ?></th>
							<th><?php esc_html_e( 'Product', 'safestore-minimal' ); ?></th>
							<th><?php esc_html_e( 'Type', 'safestore-minimal' ); ?></th>
							<th><?php esc_html_e( 'Size status', 'safestore-minimal' ); ?></th>
							<th><?php esc_html_e( 'Action', 'safestore-minimal' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $products as $product_id ) : ?>
							<?php
							$product = wc_get_product( $product_id );
							if ( ! $product ) {
								continue;
							}
							$ready = safestore_size_product_has_size_variations( $product );
							?>
							<tr>
								<td><?php echo (int) $product_id; ?></td>
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>">
										<?php echo esc_html( $product->get_name() ); ?>
									</a>
								</td>
								<td><?php echo esc_html( $product->get_type() ); ?></td>
								<td>
									<?php
									echo $ready
										? esc_html__( 'Ready (39–44)', 'safestore-minimal' )
										: esc_html__( 'Needs conversion', 'safestore-minimal' );
									?>
								</td>
								<td>
									<?php if ( ! $ready ) : ?>
										<form method="post" style="display:inline;">
											<?php wp_nonce_field( 'safestore_size_variations' ); ?>
											<input type="hidden" name="safestore_size_action" value="convert_one">
											<input type="hidden" name="product_id" value="<?php echo (int) $product_id; ?>">
											<?php submit_button( __( 'Convert', 'safestore-minimal' ), 'small', 'submit', false ); ?>
										</form>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'No products found in the Safety Shoes category yet.', 'safestore-minimal' ); ?></p>
			<?php endif; ?>
		</div>

		<p class="description">
			<?php esc_html_e( 'Tip: convert one product first, check the storefront Size dropdown, then use “Convert all”. This tool is safe to leave installed — it skips products that already have Size variations.', 'safestore-minimal' ); ?>
		</p>
	</div>
	<?php
}

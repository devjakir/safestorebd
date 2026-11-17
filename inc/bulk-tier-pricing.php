<?php
/**
 * Bulk (quantity) tier pricing + advance payment for large orders.
 *
 * Pricing
 *   Product data → General → "Bulk price tiers": up to six rows of
 *   "from N pieces → ৳X per piece" (meta `_sft_bulk_tiers`, set on the parent
 *   product, so every colour / size of a variable product shares the tiers).
 *   In the cart, all lines of the same product count together (30 Red + 20 Lime
 *   = 50 pcs) and every piece gets the price of the highest tier reached. A tier
 *   never raises a price: if the product's own price is already lower, that
 *   price stays.
 *   Shown on the product page (tier table, live "your price" line, quick
 *   quantity chips), on shop cards ("Bulk from ৳120/pc"), in the cart (struck
 *   regular price + "add N more" nudge) and on the order line ("Bulk price").
 *
 * Advance payment
 *   Orders of `safestore_bulk_advance_min_qty()` pieces or more (default 50,
 *   all products together) paid by Cash on delivery need a share of the total
 *   (`safestore_bulk_advance_percent()`, default 50%) sent first by bKash /
 *   Nagad (TrxID required at checkout). The rest is collected on delivery. The
 *   order goes on-hold until the TrxID is checked. Customers who prefer to talk
 *   first get Call / WhatsApp buttons with their cart pre-filled.
 *   Paying the full amount by bKash / Nagad (inc/mfs-payment.php) is unchanged.
 *
 * Assets: css/bulk-tiers.css, js/bulk-tiers.js (product page only).
 *
 * @package safestore-minimal
 */

defined( 'ABSPATH' ) || exit;

const SAFESTORE_BULK_META = '_sft_bulk_tiers';
const SAFESTORE_BULK_ROWS = 6;

/* =========================================================================
 * Tiers: read, clean, look up.
 * ====================================================================== */

/**
 * Clean tier rows: whole-number quantities ≥ 2, prices > 0, one row per
 * quantity, sorted by quantity. Order of input rows does not matter.
 *
 * @param mixed $rows Rows of array( 'min' => …, 'price' => … ).
 * @return array[] array( array( 'min' => int, 'price' => float ), … )
 */
function safestore_bulk_clean_tiers( $rows ) {
	$out = array();
	if ( ! is_array( $rows ) ) {
		return $out;
	}
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$min   = isset( $row['min'] ) ? (int) preg_replace( '/[^\d]/', '', (string) $row['min'] ) : 0;
		$price = isset( $row['price'] ) ? (float) wc_format_decimal( $row['price'] ) : 0.0;
		if ( $min < 2 || $price <= 0 ) {
			continue;
		}
		$out[ $min ] = array(
			'min'   => $min,
			'price' => $price,
		);
	}
	ksort( $out, SORT_NUMERIC );
	return array_values( $out );
}

/** Parent product id for a product / variation / id. */
function safestore_bulk_parent_id( $product ) {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( (int) $product );
	}
	if ( ! $product instanceof WC_Product ) {
		return 0;
	}
	return $product->get_parent_id() ? (int) $product->get_parent_id() : (int) $product->get_id();
}

/**
 * Tiers of a product (variations use their parent's).
 * Filter: `safestore_bulk_tiers` ( $tiers, $parent_id ).
 *
 * @param WC_Product|int $product Product, variation or id.
 * @return array[]
 */
function safestore_bulk_tiers( $product ) {
	static $cache = array();
	$parent_id    = safestore_bulk_parent_id( $product );
	if ( $parent_id <= 0 ) {
		return array();
	}
	if ( ! isset( $cache[ $parent_id ] ) ) {
		$raw                 = get_post_meta( $parent_id, SAFESTORE_BULK_META, true );
		$cache[ $parent_id ] = safestore_bulk_clean_tiers( is_array( $raw ) ? $raw : array() );
	}
	return (array) apply_filters( 'safestore_bulk_tiers', $cache[ $parent_id ], $parent_id );
}

/** Highest tier reached at $qty, or null. */
function safestore_bulk_tier_for( $tiers, $qty ) {
	$hit = null;
	foreach ( (array) $tiers as $tier ) {
		if ( (int) $qty >= (int) $tier['min'] ) {
			$hit = $tier;
		}
	}
	return $hit;
}

/** First tier above $qty (the next one to unlock), or null. */
function safestore_bulk_next_tier( $tiers, $qty ) {
	foreach ( (array) $tiers as $tier ) {
		if ( (int) $tier['min'] > (int) $qty ) {
			return $tier;
		}
	}
	return null;
}

/** Plain "৳1,250" (no HTML). */
function safestore_bulk_plain_price( $amount ) {
	return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
}

/** Lowest tier price, or 0. */
function safestore_bulk_lowest_price( $tiers ) {
	$low = 0.0;
	foreach ( (array) $tiers as $tier ) {
		if ( 0.0 === $low || $tier['price'] < $low ) {
			$low = (float) $tier['price'];
		}
	}
	return $low;
}

/**
 * Display price of one piece before any tier (sale price when on sale).
 * Variable products: the cheapest variation.
 */
function safestore_bulk_display_base( WC_Product $product ) {
	if ( $product->is_type( 'variable' ) ) {
		return (float) $product->get_variation_price( 'min', true );
	}
	return (float) wc_get_price_to_display( $product );
}

/* =========================================================================
 * Admin: Product data → General → Bulk price tiers.
 * ====================================================================== */

function safestore_bulk_admin_field() {
	global $product_object;
	$product = $product_object instanceof WC_Product ? $product_object : null;
	$tiers   = $product ? safestore_bulk_tiers( $product ) : array();
	$base    = ( $product && $product->get_id() ) ? safestore_bulk_display_base( $product ) : 0.0;

	// Warnings for the shop manager (shown after saving).
	$warnings = array();
	$prev     = $base;
	foreach ( $tiers as $tier ) {
		if ( $prev > 0 && $tier['price'] >= $prev ) {
			$warnings[] = sprintf(
				/* translators: 1: quantity, 2: tier price, 3: price it should be below */
				__( '%1$d+ pcs: %2$s is not lower than %3$s, so it gives no discount.', 'safestore-minimal' ),
				$tier['min'],
				safestore_bulk_plain_price( $tier['price'] ),
				safestore_bulk_plain_price( $prev )
			);
		}
		$prev = $tier['price'];
	}
	?>
	<div class="options_group sft-bulk-admin">
		<p class="form-field sft-bulk-admin__intro">
			<label><?php esc_html_e( 'Bulk price tiers', 'safestore-minimal' ); ?></label>
			<span class="description">
				<?php
				esc_html_e( 'Price per piece from a quantity. All colours / sizes of this product count together. Leave empty for no bulk price.', 'safestore-minimal' );
				if ( $base > 0 ) {
					echo ' ';
					/* translators: %s: current price */
					printf( esc_html__( 'Current price: %s / pc.', 'safestore-minimal' ), esc_html( safestore_bulk_plain_price( $base ) ) );
				}
				?>
			</span>
		</p>
		<table class="sft-bulk-admin__table" role="presentation">
			<thead>
				<tr>
					<th><?php esc_html_e( 'From (pcs)', 'safestore-minimal' ); ?></th>
					<th><?php echo esc_html( sprintf( /* translators: %s: currency symbol */ __( 'Price / pc (%s)', 'safestore-minimal' ), get_woocommerce_currency_symbol() ) ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php for ( $i = 0; $i < SAFESTORE_BULK_ROWS; $i++ ) : ?>
					<?php $row = isset( $tiers[ $i ] ) ? $tiers[ $i ] : array( 'min' => '', 'price' => '' ); ?>
					<tr>
						<td><input type="number" min="2" step="1" inputmode="numeric" name="sft_bulk_tiers[<?php echo (int) $i; ?>][min]" value="<?php echo esc_attr( (string) $row['min'] ); ?>" placeholder="<?php echo esc_attr( 0 === $i ? '50' : '' ); ?>" aria-label="<?php esc_attr_e( 'From quantity', 'safestore-minimal' ); ?>" /></td>
						<td><input type="text" class="wc_input_price" inputmode="decimal" name="sft_bulk_tiers[<?php echo (int) $i; ?>][price]" value="<?php echo esc_attr( '' === $row['price'] ? '' : wc_format_localized_price( $row['price'] ) ); ?>" placeholder="<?php echo esc_attr( 0 === $i ? '140' : '' ); ?>" aria-label="<?php esc_attr_e( 'Price per piece', 'safestore-minimal' ); ?>" /></td>
					</tr>
				<?php endfor; ?>
			</tbody>
		</table>
		<?php foreach ( $warnings as $warning ) : ?>
			<p class="sft-bulk-admin__warn">⚠ <?php echo esc_html( $warning ); ?></p>
		<?php endforeach; ?>
		<?php wp_nonce_field( 'safestore_save_bulk_tiers', 'safestore_bulk_nonce' ); ?>
		<style>
			.sft-bulk-admin__table{margin:0 0 9px 162px;border-collapse:collapse}
			.sft-bulk-admin__table th{text-align:left;font-weight:600;padding:2px 8px 4px 0}
			.sft-bulk-admin__table td{padding:2px 8px 2px 0}
			.sft-bulk-admin__table input{width:110px}
			.sft-bulk-admin__warn{margin:0 0 6px 162px!important;color:#b45309}
			@media (max-width:782px){.sft-bulk-admin__table,.sft-bulk-admin__warn{margin-left:12px!important}}
		</style>
	</div>
	<?php
}
add_action( 'woocommerce_product_options_general_product_data', 'safestore_bulk_admin_field', 30 );

function safestore_bulk_save_field( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['safestore_bulk_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['safestore_bulk_nonce'] ) ), 'safestore_save_bulk_tiers' ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- cleaned field by field below.
	$raw   = isset( $_POST['sft_bulk_tiers'] ) ? wp_unslash( $_POST['sft_bulk_tiers'] ) : array();
	$rows  = array();
	foreach ( (array) $raw as $row ) {
		$rows[] = array(
			'min'   => isset( $row['min'] ) ? sanitize_text_field( $row['min'] ) : '',
			'price' => isset( $row['price'] ) ? wc_format_decimal( sanitize_text_field( $row['price'] ) ) : '',
		);
	}
	$tiers = safestore_bulk_clean_tiers( $rows );
	if ( empty( $tiers ) ) {
		delete_post_meta( $post_id, SAFESTORE_BULK_META );
	} else {
		update_post_meta( $post_id, SAFESTORE_BULK_META, $tiers );
	}
}
add_action( 'woocommerce_process_product_meta', 'safestore_bulk_save_field' );

/* =========================================================================
 * Cart: apply tier prices.
 * ====================================================================== */

/**
 * Set every tiered cart line to its tier price. Idempotent: the base price is
 * read fresh each time, so lowering the quantity brings the normal price back.
 * Runs before totals and when the cart loads from the session (mini-cart,
 * fragments), so every view shows the same price.
 *
 * Each tiered line gets `sft_bulk`: qty (all lines of the product), base,
 * price, tier (or null), next (or null).
 *
 * @param WC_Cart $cart Cart.
 */
function safestore_bulk_apply_to_cart( $cart ) {
	if ( ! $cart instanceof WC_Cart || ( is_admin() && ! wp_doing_ajax() ) ) {
		return;
	}

	$group_qty = array();
	$last_key  = array();
	foreach ( $cart->get_cart() as $key => $item ) {
		$pid               = (int) $item['product_id'];
		$group_qty[ $pid ] = ( isset( $group_qty[ $pid ] ) ? $group_qty[ $pid ] : 0 ) + (int) $item['quantity'];
		$last_key[ $pid ]  = $key;
	}

	foreach ( $cart->get_cart() as $key => $item ) {
		unset( $cart->cart_contents[ $key ]['sft_bulk'] );

		$product = isset( $item['data'] ) ? $item['data'] : null;
		$tiers   = safestore_bulk_tiers( (int) $item['product_id'] );
		if ( ! $product instanceof WC_Product || empty( $tiers ) ) {
			continue;
		}

		$fresh = wc_get_product( $product->get_id() );
		$base  = $fresh ? (float) $fresh->get_price() : (float) $product->get_price();
		if ( $base <= 0 ) {
			continue;
		}

		$qty   = $group_qty[ (int) $item['product_id'] ];
		$tier  = safestore_bulk_tier_for( $tiers, $qty );
		$price = ( $tier && $tier['price'] < $base ) ? (float) $tier['price'] : $base;

		$product->set_price( $price );

		$next = safestore_bulk_next_tier( $tiers, $qty );
		$cart->cart_contents[ $key ]['sft_bulk'] = array(
			'qty'   => $qty,
			'base'  => $base,
			'price' => $price,
			'tier'  => ( $price < $base ) ? $tier : null,
			'next'  => ( $next && $next['price'] < $price ) ? $next : null,
			// The "add N more" nudge shows once per product, on its last line.
			'last'  => $last_key[ (int) $item['product_id'] ] === $key,
		);
	}
}
add_action( 'woocommerce_before_calculate_totals', 'safestore_bulk_apply_to_cart', 20 );
add_action( 'woocommerce_cart_loaded_from_session', 'safestore_bulk_apply_to_cart', 20 );

/** Bulk info of a cart line, or null when no tier price is active. */
function safestore_bulk_item_active( $cart_item ) {
	return ( ! empty( $cart_item['sft_bulk']['tier'] ) ) ? $cart_item['sft_bulk'] : null;
}

/** Whether any cart line has a bulk price. */
function safestore_bulk_cart_has_tier() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( safestore_bulk_item_active( $item ) ) {
			return true;
		}
	}
	return false;
}

// Cart / mini-cart unit price: <del>৳150</del> ৳140.
add_filter(
	'woocommerce_cart_item_price',
	static function ( $html, $cart_item ) {
		$bulk = safestore_bulk_item_active( $cart_item );
		if ( ! $bulk ) {
			return $html;
		}
		return '<span class="sft-bulk-cart-price"><del aria-hidden="true">' . wc_price( $bulk['base'] ) . '</del> <ins>' . wc_price( $bulk['price'] ) . '</ins></span>';
	},
	20,
	2
);

// "Bulk price: 50+ pcs" under the item name (cart, checkout review, mini-cart).
add_filter(
	'woocommerce_get_item_data',
	static function ( $data, $cart_item ) {
		$bulk = safestore_bulk_item_active( $cart_item );
		if ( $bulk ) {
			$data[] = array(
				'key'   => __( 'Bulk price', 'safestore-minimal' ),
				/* translators: 1: tier quantity, 2: saving per piece */
				'value' => sprintf( __( '%1$d+ pcs tier, you save %2$s / pc', 'safestore-minimal' ), $bulk['tier']['min'], safestore_bulk_plain_price( $bulk['base'] - $bulk['price'] ) ),
			);
		}
		return $data;
	},
	20,
	2
);

// Cart page nudge: "Add 40 more (any colour) → ৳130 / pc".
add_action(
	'woocommerce_after_cart_item_name',
	static function ( $cart_item ) {
		if ( empty( $cart_item['sft_bulk']['next'] ) || empty( $cart_item['sft_bulk']['last'] ) ) {
			return;
		}
		$bulk = $cart_item['sft_bulk'];
		$more = (int) $bulk['next']['min'] - (int) $bulk['qty'];
		// Only nudge when it is within reach: at most the same again.
		if ( $more <= 0 || $more > max( 10, (int) $bulk['qty'] ) ) {
			return;
		}
		$product  = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		$variable = $product instanceof WC_Product && $product->is_type( 'variation' );
		$text     = $variable
			/* translators: 1: pieces to add, 2: price per piece */
			? __( 'Add %1$d more (any colour) to pay %2$s / pc', 'safestore-minimal' )
			/* translators: 1: pieces to add, 2: price per piece */
			: __( 'Add %1$d more to pay %2$s / pc', 'safestore-minimal' );
		echo '<p class="sft-bulk-nudge">' . esc_html( sprintf( $text, $more, safestore_bulk_plain_price( $bulk['next']['price'] ) ) ) . '</p>';
	}
);

// Order line meta (admin, emails, my account).
add_action(
	'woocommerce_checkout_create_order_line_item',
	static function ( $item, $cart_item_key, $values ) {
		$bulk = safestore_bulk_item_active( $values );
		if ( ! $bulk ) {
			return;
		}
		$item->add_meta_data(
			__( 'Bulk price', 'safestore-minimal' ),
			sprintf(
				/* translators: 1: tier quantity, 2: regular price per piece */
				__( '%1$d+ pcs tier (regular %2$s / pc)', 'safestore-minimal' ),
				$bulk['tier']['min'],
				safestore_bulk_plain_price( $bulk['base'] )
			),
			true
		);
	},
	10,
	3
);

/**
 * Coupons do not stack on bulk prices (filter `safestore_bulk_allow_coupons`).
 */
add_filter(
	'woocommerce_coupon_is_valid',
	static function ( $valid ) {
		if ( $valid && ! apply_filters( 'safestore_bulk_allow_coupons', false ) && safestore_bulk_cart_has_tier() ) {
			throw new Exception( esc_html__( 'Coupons cannot be used together with bulk prices. Your bulk price is already applied.', 'safestore-minimal' ) );
		}
		return $valid;
	},
	20
);

/* =========================================================================
 * Product page: tier table.
 * ====================================================================== */

/** The product on a product page when it has tiers, else null. */
function safestore_bulk_pdp_product() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return null;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product instanceof WC_Product || ! $product->is_purchasable() || empty( safestore_bulk_tiers( $product ) ) ) {
		return null;
	}
	return $product;
}

function safestore_bulk_pdp_table() {
	global $product;
	if ( ! $product instanceof WC_Product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}
	$tiers = safestore_bulk_tiers( $product );
	$base  = safestore_bulk_display_base( $product );
	if ( empty( $tiers ) || $base <= 0 ) {
		return;
	}

	// Only tiers that are cheaper than the price before them.
	$rows = array();
	$prev = $base;
	foreach ( $tiers as $tier ) {
		if ( $tier['price'] < $prev ) {
			$rows[] = $tier;
			$prev   = $tier['price'];
		}
	}
	if ( empty( $rows ) ) {
		return;
	}

	$variable  = $product->is_type( 'variable' );
	$adv_min   = safestore_bulk_advance_min_qty();
	$adv_pct   = safestore_bulk_advance_percent();
	$wa_text   = sprintf(
		/* translators: 1: product name, 2: product URL */
		__( 'Hi SafeStoreBD! I need a bulk quote for %1$s (%2$s). Quantity: ', 'safestore-minimal' ),
		$product->get_name(),
		get_permalink( $product->get_id() )
	);
	$wa_url    = function_exists( 'safestore_wa_link' ) ? safestore_wa_link( 'primary', $wa_text ) : '';
	$config    = array(
		'base'     => $base,
		'tiers'    => $rows,
		'variable' => $variable,
		'symbol'   => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
		'pos'      => (string) get_option( 'woocommerce_currency_pos', 'left' ),
		'thousand' => wc_get_price_thousand_separator(),
		'decimal'  => wc_get_price_decimal_separator(),
		'i18n'     => array(
			/* translators: %s: saving per piece */
			'saveRow'   => __( 'Save %s', 'safestore-minimal' ),
			'pc'        => __( 'pc', 'safestore-minimal' ),
			/* translators: 1: price per piece, 2: quantity, 3: line total */
			'yourPrice' => __( 'Your price: %1$s / pc × %2$d = %3$s', 'safestore-minimal' ),
			/* translators: %s: total saving */
			'save'      => __( 'You save %s', 'safestore-minimal' ),
			/* translators: 1: pieces to add, 2: price per piece */
			'next'      => $variable ? __( 'Add %1$d more (any colour) to pay %2$s / pc', 'safestore-minimal' ) : __( 'Add %1$d more to pay %2$s / pc', 'safestore-minimal' ),
			/* translators: %d: pieces in stock */
			'stock'     => __( 'Only %d in stock right now — WhatsApp us for a larger quantity.', 'safestore-minimal' ),
			'best'      => __( 'Best bulk price applied', 'safestore-minimal' ),
		),
	);
	?>
	<section class="sft-bulk" data-sft-bulk="<?php echo esc_attr( wp_json_encode( $config ) ); ?>" aria-labelledby="sft-bulk-title">
		<div class="sft-bulk__head">
			<h2 class="sft-bulk__title" id="sft-bulk-title"><?php esc_html_e( 'Bulk price — buy more, pay less', 'safestore-minimal' ); ?></h2>
			<?php if ( $variable ) : ?>
				<p class="sft-bulk__sub"><?php esc_html_e( 'Mix colours freely — all pieces count together.', 'safestore-minimal' ); ?></p>
			<?php endif; ?>
		</div>

		<ul class="sft-bulk__tiers" role="list">
			<li class="sft-bulk__tier is-active" data-min="1">
				<span class="sft-bulk__qty">
					<?php
					/* translators: %d: last quantity before the first tier */
					printf( esc_html__( '1–%d pcs', 'safestore-minimal' ), (int) $rows[0]['min'] - 1 );
					?>
				</span>
				<span class="sft-bulk__price"><span data-sft-bulk-base><?php echo esc_html( safestore_bulk_plain_price( $base ) ); ?></span><small>/<?php esc_html_e( 'pc', 'safestore-minimal' ); ?></small></span>
				<span class="sft-bulk__off"><?php esc_html_e( 'Regular', 'safestore-minimal' ); ?></span>
			</li>
			<?php foreach ( $rows as $i => $tier ) : ?>
				<?php $to = isset( $rows[ $i + 1 ] ) ? (int) $rows[ $i + 1 ]['min'] - 1 : 0; ?>
				<li class="sft-bulk__tier" data-min="<?php echo (int) $tier['min']; ?>" data-price="<?php echo esc_attr( (string) $tier['price'] ); ?>">
					<button type="button" class="sft-bulk__pick" data-sft-bulk-pick="<?php echo (int) $tier['min']; ?>">
						<span class="sft-bulk__qty">
							<?php
							echo esc_html(
								$to
									/* translators: 1: from, 2: to */
									? sprintf( __( '%1$d–%2$d pcs', 'safestore-minimal' ), $tier['min'], $to )
									/* translators: %d: from */
									: sprintf( __( '%d+ pcs', 'safestore-minimal' ), $tier['min'] )
							);
							?>
						</span>
						<span class="sft-bulk__price"><?php echo esc_html( safestore_bulk_plain_price( $tier['price'] ) ); ?><small>/<?php esc_html_e( 'pc', 'safestore-minimal' ); ?></small></span>
						<span class="sft-bulk__off" data-sft-bulk-off>
							<?php
							/* translators: %s: saving per piece */
							printf( esc_html__( 'Save %s', 'safestore-minimal' ), esc_html( safestore_bulk_plain_price( $base - $tier['price'] ) ) );
							?>
						</span>
					</button>
				</li>
			<?php endforeach; ?>
		</ul>

		<p class="sft-bulk__status" data-sft-bulk-status aria-live="polite"></p>

		<ul class="sft-bulk__notes" role="list">
			<li>
				<?php
				printf(
					/* translators: 1: quantity, 2: percent */
					esc_html__( '%1$d+ pcs orders: %2$d%% advance by bKash / Nagad, the rest cash on delivery — or call / WhatsApp us to confirm.', 'safestore-minimal' ),
					(int) $adv_min,
					(int) $adv_pct
				);
				?>
			</li>
			<li>
				<?php esc_html_e( 'Larger quantity or logo printing?', 'safestore-minimal' ); ?>
				<?php if ( $wa_url ) : ?>
					<a href="<?php echo esc_url( $wa_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get a quote on WhatsApp', 'safestore-minimal' ); ?></a>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/bulk-orders/' ) ); ?>"><?php esc_html_e( 'Get a quote', 'safestore-minimal' ); ?></a>
				<?php endif; ?>
			</li>
		</ul>
	</section>
	<?php
}
// Between the short description / meta (22) and the contact row (26) — right above quantity + Add to cart (30).
add_action( 'woocommerce_single_product_summary', 'safestore_bulk_pdp_table', 24 );

function safestore_bulk_enqueue() {
	if ( ! safestore_bulk_pdp_product() ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	if ( file_exists( $dir . '/css/bulk-tiers.css' ) ) {
		wp_enqueue_style( 'safestore-bulk-tiers', $uri . '/css/bulk-tiers.css', array( 'safestore-minimal-style' ), (string) filemtime( $dir . '/css/bulk-tiers.css' ) );
	}
	if ( file_exists( $dir . '/js/bulk-tiers.js' ) ) {
		wp_enqueue_script(
			'safestore-bulk-tiers',
			$uri . '/js/bulk-tiers.js',
			array( 'jquery' ),
			(string) filemtime( $dir . '/js/bulk-tiers.js' ),
			function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'safestore_bulk_enqueue', 30 );

/* =========================================================================
 * Shop cards: "Bulk from ৳120 / pc".
 * ====================================================================== */

function safestore_bulk_card_badge( $product = null ) {
	if ( ! $product instanceof WC_Product ) {
		global $product;
	}
	if ( ! $product instanceof WC_Product || ! $product->is_in_stock() ) {
		return;
	}
	$low  = safestore_bulk_lowest_price( safestore_bulk_tiers( $product ) );
	$base = safestore_bulk_display_base( $product );
	if ( $low <= 0 || $low >= $base ) {
		return;
	}
	printf(
		'<p class="sft-bulk-chip">%s</p>',
		esc_html(
			sprintf(
				/* translators: %s: lowest bulk price per piece */
				__( 'Bulk from %s / pc', 'safestore-minimal' ),
				safestore_bulk_plain_price( $low )
			)
		)
	);
}
add_action( 'safestore_product_card_after_price', 'safestore_bulk_card_badge' );

// Chip / nudge / advance-box styles are small: inline instead of another request.
add_action(
	'wp_head',
	static function () {
		$css = '.sft-bulk-chip{display:inline-block;margin:2px 0 4px;padding:2px 7px;border-radius:999px;background:#ecfdf5;color:#166534;font-size:11.5px;font-weight:600;line-height:1.5}'
			. '.sft-bulk-nudge{margin:4px 0 0;font-size:12.5px;font-weight:600;color:#166534}'
			. '.sft-bulk-cart-price ins{text-decoration:none;font-weight:600;color:#166534}'
			. '.sft-sticky-atc__bulk{display:block;font-size:11.5px;font-weight:600;color:#166534;line-height:1.3}';
		if ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) {
			$css .= '.sft-adv-cart-note{margin:0 0 12px;padding:10px 12px;border:1px solid #fde68a;border-radius:8px;background:#fffbeb;color:#92400e;font-size:13px;line-height:1.5}'
				. '.sft-adv__split{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:0 0 12px}'
				. '.sft-adv__part{margin:0;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;background:#fff}'
				. '.sft-adv__part--now{border-color:#86efac;background:#f0fdf4}'
				. '.sft-adv__part dt{margin:0;font-size:12px;color:#6b7280;font-weight:500}'
				. '.sft-adv__part dd{margin:2px 0 0;font-size:18px;font-weight:700;color:#0f172a}'
				. '.sft-adv__talk{margin-top:14px;padding-top:12px;border-top:1px dashed #e5e7eb}'
				. '.sft-adv__talk p{margin:0 0 8px;font-size:13px;color:#374151}'
				. '.sft-adv__contact{display:flex;flex-wrap:wrap;gap:8px}'
				. '.sft-adv__btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:8px 16px;border-radius:8px;font-weight:600;font-size:14px;text-decoration:none!important;border:1px solid #d1d5db;color:#0f172a!important;background:#fff}'
				. '.sft-adv__btn--wa{background:#25d366;border-color:#25d366;color:#fff!important}'
				. '@media (max-width:480px){.sft-adv__btn{flex:1 1 0}}';
		}
		echo '<style id="sft-bulk-inline">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static CSS.
	},
	30
);

/* =========================================================================
 * Advance payment for large Cash on delivery orders.
 * ====================================================================== */

/** Pieces in the cart / order from which an advance is needed. */
function safestore_bulk_advance_min_qty() {
	return max( 1, (int) apply_filters( 'safestore_bulk_advance_min_qty', 50 ) );
}

/** Advance share of the total, in percent. */
function safestore_bulk_advance_percent() {
	return min( 100, max( 1, (int) apply_filters( 'safestore_bulk_advance_percent', 50 ) ) );
}

/** Advance (rounded up to whole taka) and the rest for a total. */
function safestore_bulk_advance_split( $total ) {
	$total   = (float) $total;
	$advance = (float) ceil( $total * safestore_bulk_advance_percent() / 100 );
	return array(
		'advance' => min( $advance, $total ),
		'rest'    => max( 0, $total - $advance ),
	);
}

/** Pieces in the cart (all products). */
function safestore_bulk_cart_pieces() {
	return ( function_exists( 'WC' ) && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;
}

/** Whether the current cart needs an advance for Cash on delivery. */
function safestore_bulk_cart_needs_advance() {
	return safestore_bulk_cart_pieces() >= safestore_bulk_advance_min_qty();
}

/**
 * Saved advance details of an order, or null when none was required.
 *
 * @param WC_Order $order Order.
 */
function safestore_bulk_order_advance( $order ) {
	if ( ! $order instanceof WC_Order || 'yes' !== $order->get_meta( '_sft_adv_required' ) ) {
		return null;
	}
	return array(
		'pieces'  => (int) $order->get_meta( '_sft_adv_pieces' ),
		'percent' => (int) $order->get_meta( '_sft_adv_percent' ),
		'advance' => (float) $order->get_meta( '_sft_adv_amount' ),
		'rest'    => (float) $order->get_meta( '_sft_adv_due' ),
		'wallet'  => (string) $order->get_meta( '_sft_adv_wallet' ),
		'sender'  => (string) $order->get_meta( '_sft_adv_sender' ),
		'trxid'   => (string) $order->get_meta( '_sft_adv_trxid' ),
	);
}

/** "bKash TrxID … from …" for an advance (reuses the bKash / Nagad helper). */
function safestore_bulk_advance_describe( $adv ) {
	if ( function_exists( 'safestore_mfs_describe' ) ) {
		return safestore_mfs_describe( $adv );
	}
	return trim( $adv['wallet'] . ' ' . $adv['trxid'] . ' ' . $adv['sender'] );
}

/** Call + WhatsApp buttons with the cart pre-filled in the WhatsApp message. */
function safestore_bulk_contact_buttons_html( $context_text ) {
	$tel     = preg_replace( '/[^\d+]/', '', (string) apply_filters( 'safestore_minimal_phone_tel', '+8801811892291' ) );
	$wa_url  = function_exists( 'safestore_wa_link' ) ? safestore_wa_link( 'primary', $context_text ) : '';
	$out     = '<div class="sft-adv__contact">';
	if ( $tel ) {
		$out .= '<a class="sft-adv__btn sft-adv__btn--call" href="' . esc_url( 'tel:' . $tel ) . '">' . esc_html__( 'Call us', 'safestore-minimal' ) . '</a>';
	}
	if ( $wa_url ) {
		$out .= '<a class="sft-adv__btn sft-adv__btn--wa" href="' . esc_url( $wa_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'WhatsApp us', 'safestore-minimal' ) . '</a>';
	}
	return $out . '</div>';
}

/** WhatsApp text that lists the cart. */
function safestore_bulk_cart_whatsapp_text() {
	$lines = array();
	if ( function_exists( 'WC' ) && WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $item ) {
			$product = $item['data'];
			if ( $product instanceof WC_Product ) {
				$lines[] = '• ' . wp_strip_all_tags( $product->get_name() ) . ' × ' . (int) $item['quantity'];
			}
		}
		$total = safestore_bulk_plain_price( WC()->cart->get_total( 'edit' ) );
	} else {
		$total = '';
	}
	return __( 'Hi SafeStoreBD! I want to place a bulk order:', 'safestore-minimal' ) . "\n" . implode( "\n", $lines ) . ( $total ? "\n" . __( 'Total:', 'safestore-minimal' ) . ' ' . $total : '' );
}

// COD description for bulk carts.
add_filter(
	'woocommerce_gateway_description',
	static function ( $description, $gateway_id ) {
		if ( 'cod' !== $gateway_id || ( is_admin() && ! wp_doing_ajax() ) || ! safestore_bulk_cart_needs_advance() ) {
			return $description;
		}
		return sprintf(
			/* translators: 1: pieces, 2: percent */
			__( 'Bulk order (%1$d pcs): pay %2$d%% in advance by bKash / Nagad now, and the rest in cash when the parcel arrives.', 'safestore-minimal' ),
			safestore_bulk_cart_pieces(),
			safestore_bulk_advance_percent()
		);
	},
	30,
	2
);

/**
 * Advance box inside the Cash on delivery payment box (bulk carts).
 * Called from woocommerce/checkout/payment-method.php.
 */
function safestore_bulk_advance_box() {
	$total = ( function_exists( 'WC' ) && WC()->cart ) ? (float) WC()->cart->get_total( 'edit' ) : 0;
	if ( $total <= 0 ) {
		return;
	}
	$split = safestore_bulk_advance_split( $total );
	$adv   = safestore_bulk_plain_price( $split['advance'] );
	?>
	<div class="sft-adv sft-mfs">
		<dl class="sft-adv__split">
			<div class="sft-adv__part sft-adv__part--now">
				<dt><?php esc_html_e( 'Advance now', 'safestore-minimal' ); ?></dt>
				<dd><?php echo esc_html( $adv ); ?></dd>
			</div>
			<div class="sft-adv__part">
				<dt><?php esc_html_e( 'Cash on delivery', 'safestore-minimal' ); ?></dt>
				<dd><?php echo esc_html( safestore_bulk_plain_price( $split['rest'] ) ); ?></dd>
			</div>
		</dl>

		<?php
		if ( function_exists( 'safestore_mfs_numbers_html' ) ) {
			echo '<p class="sft-mfs__lead">' . esc_html( safestore_mfs_copy( 'lead' ) ) . '</p>';
			echo safestore_mfs_numbers_html( $adv ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		}
		?>
		<ol class="sft-mfs-howto">
			<?php if ( function_exists( 'safestore_mfs_copy' ) ) : ?>
				<li><?php echo wp_kses( safestore_mfs_copy( 'step1' ), array( 'strong' => array() ) ); ?></li>
			<?php endif; ?>
			<li>
				<?php
				/* translators: %s: advance amount */
				echo wp_kses( sprintf( __( 'Send <strong>%s</strong> (advance) to the number above.', 'safestore-minimal' ), esc_html( $adv ) ), array( 'strong' => array() ) );
				?>
			</li>
			<li><?php esc_html_e( 'Enter the Transaction ID (TrxID) from your SMS below.', 'safestore-minimal' ); ?></li>
		</ol>

		<?php
		if ( function_exists( 'safestore_mfs_limit_note' ) ) {
			$limit = safestore_mfs_limit_note( $split['advance'] );
			if ( $limit ) {
				echo '<p class="sft-mfs-note sft-mfs-note--warn">' . wp_kses( $limit, array( 'strong' => array() ) ) . '</p>';
			}
		}
		// WooCommerce's checkout.js keeps typed values across refreshes (by input id).
		if ( function_exists( 'safestore_mfs_fields_html' ) ) {
			echo safestore_mfs_fields_html( 'sft_adv' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		}
		?>

		<div class="sft-adv__talk">
			<p><?php esc_html_e( 'Prefer to talk first? Call or WhatsApp us — we will confirm the order and payment with you.', 'safestore-minimal' ); ?></p>
			<?php echo safestore_bulk_contact_buttons_html( safestore_bulk_cart_whatsapp_text() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
		</div>
	</div>
	<?php
}

/** Whether checkout data is a Cash on delivery order that needs an advance. */
function safestore_bulk_checkout_needs_advance( $data ) {
	return ! empty( $data['payment_method'] ) && 'cod' === $data['payment_method'] && safestore_bulk_cart_needs_advance();
}

add_action(
	'woocommerce_after_checkout_validation',
	static function ( $data, $errors ) {
		if ( ! safestore_bulk_checkout_needs_advance( $data ) || ! function_exists( 'safestore_mfs_read_posted' ) ) {
			return;
		}
		list( , $error ) = safestore_mfs_read_posted( 'sft_adv', true );
		if ( $error ) {
			$errors->add(
				'sft_adv',
				sprintf(
					/* translators: 1: percent, 2: error text */
					__( 'Bulk order — %1$d%% advance: %2$s Or call / WhatsApp us to confirm the order.', 'safestore-minimal' ),
					safestore_bulk_advance_percent(),
					$error
				)
			);
		}
	},
	10,
	2
);

add_action(
	'woocommerce_checkout_create_order',
	static function ( $order, $data ) {
		if ( ! safestore_bulk_checkout_needs_advance( $data ) || ! function_exists( 'safestore_mfs_read_posted' ) ) {
			return;
		}
		list( $values, $error ) = safestore_mfs_read_posted( 'sft_adv', true );
		if ( $error ) {
			return;
		}
		$split = safestore_bulk_advance_split( $order->get_total() );
		$order->update_meta_data( '_sft_adv_required', 'yes' );
		$order->update_meta_data( '_sft_adv_pieces', safestore_bulk_cart_pieces() );
		$order->update_meta_data( '_sft_adv_percent', safestore_bulk_advance_percent() );
		$order->update_meta_data( '_sft_adv_amount', $split['advance'] );
		$order->update_meta_data( '_sft_adv_due', $split['rest'] );
		$order->update_meta_data( '_sft_adv_wallet', $values['wallet'] );
		$order->update_meta_data( '_sft_adv_sender', $values['sender'] );
		$order->update_meta_data( '_sft_adv_trxid', $values['trxid'] );
	},
	10,
	2
);

add_action(
	'woocommerce_checkout_order_processed',
	static function ( $order_id, $posted, $order ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		$adv = safestore_bulk_order_advance( $order );
		if ( ! $adv ) {
			return;
		}
		$order->add_order_note(
			sprintf(
				/* translators: 1: pieces, 2: advance, 3: "bKash TrxID … from …", 4: amount to collect */
				__( 'Bulk order (%1$d pcs): customer says the advance of %2$s is paid — %3$s. Verify in the wallet app, then move the order to Processing. Courier COD amount: %4$s.', 'safestore-minimal' ),
				$adv['pieces'],
				safestore_bulk_plain_price( $adv['advance'] ),
				safestore_bulk_advance_describe( $adv ),
				safestore_bulk_plain_price( $adv['rest'] )
			)
		);
	},
	10,
	3
);

// Bulk COD orders wait on-hold until the advance is verified.
add_filter(
	'woocommerce_cod_process_payment_order_status',
	static function ( $status, $order ) {
		return safestore_bulk_order_advance( $order ) ? 'on-hold' : $status;
	},
	20,
	2
);

/** Advance summary rows (HTML) for admin, emails and the thank-you page. */
function safestore_bulk_advance_summary_html( $adv ) {
	$rows = array(
		/* translators: %d: percent */
		sprintf( __( 'Advance (%d%%)', 'safestore-minimal' ), $adv['percent'] ) => safestore_bulk_plain_price( $adv['advance'] ) . ( $adv['trxid'] ? ' — ' . safestore_bulk_advance_describe( $adv ) : '' ),
		__( 'Cash on delivery', 'safestore-minimal' )                            => safestore_bulk_plain_price( $adv['rest'] ),
	);
	$html = '';
	foreach ( $rows as $label => $value ) {
		$html .= '<p class="sft-adv-row"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
	}
	return $html;
}

// Admin order screen.
add_action(
	'woocommerce_admin_order_data_after_order_details',
	static function ( $order ) {
		$adv = safestore_bulk_order_advance( $order );
		if ( ! $adv ) {
			return;
		}
		echo '<div class="form-field form-field-wide sft-adv-admin"><h3>' . esc_html__( 'Bulk order — advance', 'safestore-minimal' ) . '</h3>';
		echo safestore_bulk_advance_summary_html( $adv ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		echo '<p class="description">' . esc_html__( 'Set the courier COD amount to the "Cash on delivery" value above.', 'safestore-minimal' ) . '</p></div>';
	}
);

// Order emails (customer + admin).
add_action(
	'woocommerce_email_after_order_table',
	static function ( $order, $sent_to_admin, $plain_text ) {
		$adv = safestore_bulk_order_advance( $order );
		if ( ! $adv ) {
			return;
		}
		if ( $plain_text ) {
			echo "\n" . esc_html( wp_strip_all_tags( str_replace( '</p>', "\n", safestore_bulk_advance_summary_html( $adv ) ) ) ) . "\n";
			return;
		}
		echo '<div style="margin:0 0 24px">' . safestore_bulk_advance_summary_html( $adv ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
	},
	20,
	3
);

/**
 * Thank-you page steps for a bulk COD order (woocommerce/checkout/thankyou.php).
 *
 * @param WC_Order $order    Order.
 * @param string   $phone    Customer phone (display).
 * @return array[]|null Steps, or null when the order has no advance.
 */
function safestore_bulk_thankyou_steps( $order, $phone = '' ) {
	$adv = safestore_bulk_order_advance( $order );
	if ( ! $adv ) {
		return null;
	}
	return array(
		array(
			'done'  => true,
			/* translators: %s: advance amount */
			'title' => sprintf( __( 'Advance %s sent', 'safestore-minimal' ), safestore_bulk_plain_price( $adv['advance'] ) ),
			'text'  => esc_html( safestore_bulk_advance_describe( $adv ) ),
		),
		array(
			'title' => __( 'We verify and confirm', 'safestore-minimal' ),
			'text'  => $phone
				/* translators: %s: customer's phone number */
				? sprintf( __( 'We check the TrxID and call or WhatsApp you on %s to confirm the order.', 'safestore-minimal' ), '<strong>' . esc_html( $phone ) . '</strong>' )
				: __( 'We check the TrxID and call or WhatsApp you to confirm the order.', 'safestore-minimal' ),
		),
		array(
			/* translators: %s: amount due */
			'title' => sprintf( __( 'Pay the rest, %s, on delivery', 'safestore-minimal' ), safestore_bulk_plain_price( $adv['rest'] ) ),
			'text'  => __( 'Check the parcel, then pay the delivery person.', 'safestore-minimal' ),
		),
	);
}

// Cart page: tell bulk buyers about the advance before checkout.
add_action(
	'woocommerce_before_cart_totals',
	static function () {
		if ( ! safestore_bulk_cart_needs_advance() ) {
			return;
		}
		echo '<p class="sft-adv-cart-note">' . esc_html(
			sprintf(
				/* translators: 1: pieces, 2: percent */
				__( 'Bulk order (%1$d pcs): Cash on delivery needs %2$d%% advance by bKash / Nagad. Or pay in full by bKash / Nagad, or call / WhatsApp us.', 'safestore-minimal' ),
				safestore_bulk_cart_pieces(),
				safestore_bulk_advance_percent()
			)
		) . '</p>';
	}
);

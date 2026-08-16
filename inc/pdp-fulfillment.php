<?php
/**
 * PDP fulfillment: compact buy-box trust badges + Delivery & returns tab.
 *
 * Long shipping/returns copy lives in the product tab and on the canonical
 * /shipping-delivery/ and /return-refund-policy/ pages — not above the fold.
 *
 * @package SafeStore_Minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared fulfillment rows (badges + tab). Filter to change copy or hide items.
 *
 * @return array<int, array<string, string>>
 */
function safestore_minimal_pdp_fulfillment_items() {
	$pickup = function_exists( 'safestore_minimal_get_pickup_address' )
		? safestore_minimal_get_pickup_address()
		: '';

	return apply_filters(
		'safestore_minimal_pdp_fulfillment_items',
		array(
			array(
				'id'     => 'pickup',
				'icon'   => 'home',
				'label'  => __( 'Free pickup', 'safestore-minimal' ),
				'hint'   => __( 'Pallabi office', 'safestore-minimal' ),
				'title'  => __( 'Pick up from store', 'safestore-minimal' ),
				'detail' => $pickup,
				'chip'   => __( 'Free', 'safestore-minimal' ),
			),
			array(
				'id'     => 'dhaka',
				'icon'   => 'courier',
				'label'  => __( 'Dhaka ~24h', 'safestore-minimal' ),
				'hint'   => __( 'From ৳80', 'safestore-minimal' ),
				'title'  => __( 'Courier — inside Dhaka', 'safestore-minimal' ),
				'detail' => __( 'Typical handover within ~24 hours. From ৳80 depending on partner & weight.', 'safestore-minimal' ),
				'chip'   => '',
			),
			array(
				'id'     => 'nationwide',
				'icon'   => 'globe',
				'label'  => __( 'Nationwide', 'safestore-minimal' ),
				'hint'   => __( 'From ৳135+', 'safestore-minimal' ),
				'title'  => __( 'Outside Dhaka', 'safestore-minimal' ),
				'detail' => __( '2–3 days typical. From ৳135+ by destination and carrier.', 'safestore-minimal' ),
				'chip'   => '',
			),
			array(
				'id'     => 'returns',
				'icon'   => 'returns',
				'label'  => __( '7-day returns', 'safestore-minimal' ),
				'hint'   => __( 'Unused items', 'safestore-minimal' ),
				'title'  => __( 'Returns', 'safestore-minimal' ),
				'detail' => __( 'Unused items in original condition where applicable. Contact us before sending anything back.', 'safestore-minimal' ),
				'chip'   => __( '7 days', 'safestore-minimal' ),
			),
		)
	);
}

/**
 * Inline SVG for a fulfillment icon key.
 *
 * @param string $icon Icon key: home|courier|globe|returns.
 * @param int    $size Pixel size.
 * @return string
 */
function safestore_minimal_pdp_fulfillment_icon( $icon, $size = 18 ) {
	$size = max( 12, (int) $size );
	$paths = array(
		'home'     => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
		'courier'  => '<rect x="2" y="7" width="16" height="12" rx="1"/><path d="M18 11h2.5a1.5 1.5 0 0 1 1.5 1.5V17a1 1 0 0 1-1 1h-3M8 5v4M12 5v4M6 5h8"/>',
		'globe'    => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/>',
		'returns'  => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
	);

	$key = isset( $paths[ $icon ] ) ? $icon : 'courier';

	return sprintf(
		'<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
		$size,
		$paths[ $key ]
	);
}

/**
 * Compact 2×2 trust badges in the buy box (after ATC / action bar).
 */
function safestore_minimal_pdp_trust_badges() {
	$items = safestore_minimal_pdp_fulfillment_items();
	if ( empty( $items ) ) {
		return;
	}
	?>
	<ul class="sft-pdp-trustb" aria-label="<?php esc_attr_e( 'Delivery and returns', 'safestore-minimal' ); ?>">
		<?php foreach ( $items as $item ) : ?>
			<?php
			$id    = isset( $item['id'] ) ? sanitize_html_class( (string) $item['id'] ) : '';
			$label = isset( $item['label'] ) ? (string) $item['label'] : '';
			$hint  = isset( $item['hint'] ) ? (string) $item['hint'] : '';
			$title = isset( $item['title'] ) ? (string) $item['title'] : $label;
			$icon  = isset( $item['icon'] ) ? (string) $item['icon'] : 'courier';
			?>
			<li>
				<a
					class="sft-pdp-trustb__item sft-pdp-trustb__item--<?php echo esc_attr( $id ); ?>"
					href="#tab-delivery"
					data-sft-open-tab="delivery"
					aria-label="<?php echo esc_attr( $title . ( $hint !== '' ? ' — ' . $hint : '' ) ); ?>"
				>
					<span class="sft-pdp-trustb__icon">
						<?php echo safestore_minimal_pdp_fulfillment_icon( $icon, 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span class="sft-pdp-trustb__text">
						<span class="sft-pdp-trustb__label"><?php echo esc_html( $label ); ?></span>
						<?php if ( $hint !== '' ) : ?>
							<span class="sft-pdp-trustb__hint"><?php echo esc_html( $hint ); ?></span>
						<?php endif; ?>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * WooCommerce tab: delivery/returns summary + links to canonical policy pages.
 *
 * @param array<string, array<string, mixed>> $tabs Tabs.
 * @return array<string, array<string, mixed>>
 */
function safestore_minimal_pdp_delivery_tab( $tabs ) {
	$tabs['delivery'] = array(
		'title'    => __( 'Delivery & returns', 'safestore-minimal' ),
		'priority' => 25,
		'callback' => 'safestore_minimal_pdp_delivery_tab_content',
	);
	return $tabs;
}

/**
 * Tab panel markup.
 */
function safestore_minimal_pdp_delivery_tab_content() {
	$items        = safestore_minimal_pdp_fulfillment_items();
	$shipping_url = home_url( '/shipping-delivery/' );
	$returns_url  = home_url( '/return-refund-policy/' );
	?>
	<div class="sft-pdp-delivery">
		<p class="sft-pdp-delivery__lede">
			<?php esc_html_e( 'Courier across Bangladesh or free pickup from our Pallabi office. Final shipping is calculated at checkout.', 'safestore-minimal' ); ?>
		</p>
		<ul class="sft-pdp-delivery__list">
			<?php foreach ( $items as $item ) : ?>
				<?php
				$id     = isset( $item['id'] ) ? sanitize_html_class( (string) $item['id'] ) : '';
				$icon   = isset( $item['icon'] ) ? (string) $item['icon'] : 'courier';
				$title  = isset( $item['title'] ) ? (string) $item['title'] : '';
				$detail = isset( $item['detail'] ) ? (string) $item['detail'] : '';
				$chip   = isset( $item['chip'] ) ? (string) $item['chip'] : '';
				?>
				<li class="sft-pdp-delivery__item" id="sft-delivery-<?php echo esc_attr( $id ); ?>">
					<span class="sft-pdp-delivery__icon">
						<?php echo safestore_minimal_pdp_fulfillment_icon( $icon, 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<div class="sft-pdp-delivery__body">
						<h3 class="sft-pdp-delivery__title">
							<?php echo esc_html( $title ); ?>
							<?php if ( $chip !== '' ) : ?>
								<span class="sft-pdp-delivery__chip<?php echo 'returns' === $id ? ' sft-pdp-delivery__chip--muted' : ''; ?>"><?php echo esc_html( $chip ); ?></span>
							<?php endif; ?>
						</h3>
						<p class="sft-pdp-delivery__detail"><?php echo esc_html( $detail ); ?></p>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="sft-pdp-delivery__links">
			<a href="<?php echo esc_url( $shipping_url ); ?>"><?php esc_html_e( 'Shipping & delivery', 'safestore-minimal' ); ?></a>
			<span aria-hidden="true">·</span>
			<a href="<?php echo esc_url( $returns_url ); ?>"><?php esc_html_e( 'Return & refund policy', 'safestore-minimal' ); ?></a>
		</p>
	</div>
	<?php
}

add_action( 'woocommerce_single_product_summary', 'safestore_minimal_pdp_trust_badges', 36 );
add_filter( 'woocommerce_product_tabs', 'safestore_minimal_pdp_delivery_tab', 20 );

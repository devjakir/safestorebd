<?php
/**
 * Home — Support / Need-help section
 *
 * Contact items are rendered with the shared safestore_contact_item()
 * component so the icons, spacing, and typography match the footer.
 */

$support_items = array(
	array(
		'type'   => 'phone',
		'label'  => __( 'Call Us', 'safestore-minimal' ),
		'value'  => '+880 1880-307446',
		'detail' => __( 'Sat–Thu, 9am–8pm', 'safestore-minimal' ),
		'href'   => 'tel:+8801880307446',
	),
	array(
		'type'   => 'whatsapp',
		'label'  => __( 'WhatsApp', 'safestore-minimal' ),
		'value'  => '+880 1880-307446',
		'detail' => __( 'Avg reply ~5 min', 'safestore-minimal' ),
		'href'   => 'https://wa.me/8801880307446',
	),
	array(
		'type'   => 'email',
		'label'  => __( 'Email', 'safestore-minimal' ),
		'value'  => 'bdsafestore@gmail.com',
		'detail' => __( 'Reply within 4 hours', 'safestore-minimal' ),
		'href'   => 'mailto:bdsafestore@gmail.com',
	),
	array(
		'type'   => 'help',
		'label'  => __( 'Help Center', 'safestore-minimal' ),
		'value'  => __( 'FAQ & guides', 'safestore-minimal' ),
		'detail' => __( 'Orders, delivery & returns', 'safestore-minimal' ),
		'href'   => home_url( '/faqs/' ),
	),
);
?>

<section class="support-bar" aria-label="<?php esc_attr_e( 'Customer support', 'safestore-minimal' ); ?>">
	<div class="support-bar-card">
		<div class="support-bar-head">
			<span class="support-bar-badge">
				<svg class="support-bar-badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
				</svg>
				<?php esc_html_e( '24/7 Support', 'safestore-minimal' ); ?>
			</span>
			<h2 class="support-bar-title"><?php esc_html_e( 'Need help with your order?', 'safestore-minimal' ); ?></h2>
			<p class="support-bar-text"><?php esc_html_e( 'Our team is ready to help with bulk orders, shipping, returns, or product questions.', 'safestore-minimal' ); ?></p>
		</div>
		<div class="support-bar-grid">
			<?php
			foreach ( $support_items as $sft_support_item ) {
				echo safestore_contact_item( $sft_support_item ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>
	</div>
</section>

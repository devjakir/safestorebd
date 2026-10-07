<?php
/**
 * Template Name: Bulk Orders
 *
 * Bulk & corporate quotes — the B2B door. The header links to /bulk-orders/,
 * and so do the blog guides. Form = Fluent Forms, looked up by title so the
 * page never breaks if the form id changes; WhatsApp and email are always
 * shown as the fallback route.
 *
 * @package safestore-minimal
 */

get_header();

$wa_text    = __( 'Hello SafeStoreBD, I need a bulk quotation for:', 'safestore-minimal' );
$wa_href    = safestore_primary_wa_link( $wa_text );
$phone_href = 'tel:' . safestore_primary_phone_e164();
$phone      = safestore_primary_phone_display();
$email      = 'contact@safestorebd.com';
$mailto     = 'mailto:' . $email . '?subject=' . rawurlencode( 'Bulk quotation request — SafeStoreBD' );
$shop_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$office     = function_exists( 'safestore_minimal_get_pickup_address' ) ? safestore_minimal_get_pickup_address() : '';

if ( ! function_exists( 'safestore_bulk_quote_form_id' ) ) :
/**
 * Fluent Forms form id for the quote form, found by title.
 *
 * @return int 0 when the form does not exist yet.
 */
function safestore_bulk_quote_form_id() {
	global $wpdb;
	if ( ! shortcode_exists( 'fluentform' ) ) {
		return 0;
	}
	$table = $wpdb->prefix . 'fluentform_forms';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return 0;
	}
	$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE title = %s AND status = 'published' ORDER BY id DESC LIMIT 1", 'Bulk Quote' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return (int) apply_filters( 'safestore_bulk_quote_form_id', (int) $id );
}
endif;

$form_id = safestore_bulk_quote_form_id();

$segments = array(
	__( 'Garment & textile factories', 'safestore-minimal' ),
	__( 'Construction & real estate', 'safestore-minimal' ),
	__( 'Shipyards & steel', 'safestore-minimal' ),
	__( 'Logistics & warehousing', 'safestore-minimal' ),
	__( 'Power, cement & chemicals', 'safestore-minimal' ),
	__( 'Hospitals & facilities', 'safestore-minimal' ),
);

$steps = array(
	array(
		'title' => __( 'Send the list', 'safestore-minimal' ),
		'text'  => __( 'Items, quantities, sizes if known, delivery district and the date you need them by. A photo of your requisition is fine.', 'safestore-minimal' ),
	),
	array(
		'title' => __( 'Quotation within 24 hours', 'safestore-minimal' ),
		'text'  => __( 'You get a written quotation with per-piece price, stock position, delivery charge and lead time — on WhatsApp and email.', 'safestore-minimal' ),
	),
	array(
		'title' => __( 'Confirm and receive', 'safestore-minimal' ),
		'text'  => __( 'Pay by bank transfer, bKash or cash on delivery as agreed. Goods ship from Dhaka with a delivery challan and VAT invoice.', 'safestore-minimal' ),
	),
);

$includes = array(
	__( 'Volume pricing on 12, 50 and 100+ pieces', 'safestore-minimal' ),
	__( 'VAT invoice (Mushak 6.3) and delivery challan', 'safestore-minimal' ),
	__( 'Standard markings and test certificates on request', 'safestore-minimal' ),
	__( 'Size runs for footwear and gloves, mixed per order', 'safestore-minimal' ),
	__( 'Delivery to all 64 districts; free pickup from Pallabi, Dhaka', 'safestore-minimal' ),
	__( 'Genuine brand stock: MSA, Honeywell, 3M, Karam and more', 'safestore-minimal' ),
);

while ( have_posts() ) :
	the_post();
	?>
	<main class="sft-about sft-bulk" id="main-content" itemscope itemtype="https://schema.org/WebPage">
		<meta itemprop="name" content="<?php echo esc_attr( get_the_title() ); ?>" />
		<meta itemprop="description" content="<?php echo esc_attr( __( 'Bulk and corporate PPE orders in Bangladesh — safety shoes, helmets, gloves, goggles and vests with VAT invoice and nationwide delivery. Quotation within 24 hours.', 'safestore-minimal' ) ); ?>" />

		<section class="sft-about-hero sft-bulk-hero" aria-labelledby="sft-bulk-title">
			<div class="sft-about-hero-inner">
				<p class="sft-about-eyebrow"><?php esc_html_e( 'Corporate & bulk orders', 'safestore-minimal' ); ?></p>
				<h1 class="sft-about-title" id="sft-bulk-title"><?php the_title(); ?></h1>
				<p class="sft-about-lede">
					<?php esc_html_e( 'Kitting out a floor, a site or a whole crew? Send us the list and you will have a written quotation within 24 hours — with VAT invoice, challan and delivery anywhere in Bangladesh.', 'safestore-minimal' ); ?>
				</p>
				<div class="sft-about-hero-cta">
					<a class="sft-about-btn sft-about-btn--primary sft-wa-cta" href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo function_exists( 'safestore_wa_icon_svg' ) ? safestore_wa_icon_svg( 'sft-wa-cta-icon' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'Quote on WhatsApp', 'safestore-minimal' ); ?>
					</a>
					<a class="sft-about-btn sft-about-btn--ghost" href="#sft-bulk-form"><?php esc_html_e( 'Use the quote form', 'safestore-minimal' ); ?></a>
				</div>
				<ul class="sft-bulk-segments" aria-label="<?php esc_attr_e( 'Who we supply', 'safestore-minimal' ); ?>">
					<?php foreach ( $segments as $segment ) : ?>
						<li><?php echo esc_html( $segment ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>

		<?php
		// Editor notes, if any. The form is placed by this template, so a
		// [fluentform] shortcode left in the page content (the pre-template
		// fallback) is dropped here rather than rendered twice.
		$notes = (string) get_post()->post_content;
		$notes = preg_replace( '/\\[fluentform[^\\]]*\\]/i', '', $notes );
		$notes = trim( (string) $notes );
		?>
		<?php if ( '' !== $notes ) : ?>
			<section class="sft-about-editor-wrap" aria-label="<?php esc_attr_e( 'Notes', 'safestore-minimal' ); ?>">
				<div class="sft-about-inner">
					<div class="sft-about-editor entry-content">
						<?php echo apply_filters( 'the_content', $notes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="sft-about-body sft-bulk-body" aria-labelledby="sft-bulk-form-heading">
			<div class="sft-about-inner sft-about-body-grid">
				<div class="sft-bulk-main" id="sft-bulk-form">
					<h2 class="sft-about-h2" id="sft-bulk-form-heading"><?php esc_html_e( 'Request a quotation', 'safestore-minimal' ); ?></h2>
					<p class="sft-contact-required-note">
						<?php esc_html_e( 'Fields marked with * are required. Quotations go out within 24 hours, Sat–Thu 9am–8pm. Urgent? WhatsApp is faster.', 'safestore-minimal' ); ?>
					</p>

					<?php if ( $form_id > 0 ) : ?>
						<div class="sft-bulk-form">
							<?php echo do_shortcode( '[fluentform id="' . (int) $form_id . '"]' ); ?>
						</div>
					<?php else : ?>
						<div class="sft-bulk-form sft-bulk-form--fallback">
							<p>
								<?php
								printf(
									/* translators: 1: WhatsApp link, 2: email link */
									wp_kses_post( __( 'Send your list on <a href="%1$s" target="_blank" rel="noopener noreferrer">WhatsApp</a> or by <a href="%2$s">email</a> — company name, items, quantities, delivery district and the date you need them by.', 'safestore-minimal' ) ),
									esc_url( $wa_href ),
									esc_url( $mailto )
								);
								?>
							</p>
						</div>
					<?php endif; ?>

					<h3 class="sft-about-h3 sft-bulk-steps-title"><?php esc_html_e( 'How it works', 'safestore-minimal' ); ?></h3>
					<ol class="sft-bulk-steps">
						<?php foreach ( $steps as $i => $step ) : ?>
							<li>
								<span class="sft-bulk-steps__num" aria-hidden="true"><?php echo (int) $i + 1; ?></span>
								<div>
									<strong><?php echo esc_html( $step['title'] ); ?></strong>
									<p><?php echo esc_html( $step['text'] ); ?></p>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>

				<aside class="sft-about-contact-card sft-bulk-card" aria-labelledby="sft-bulk-contact-heading">
					<h3 class="sft-about-h3" id="sft-bulk-contact-heading"><?php esc_html_e( 'Corporate sales desk', 'safestore-minimal' ); ?></h3>
					<p class="sft-about-contact-lead">
						<?php esc_html_e( 'One person handles your order from quotation to delivery. Call, WhatsApp or email — replies within business hours.', 'safestore-minimal' ); ?>
					</p>
					<ul class="sft-about-contact-list">
						<li>
							<span class="sft-about-contact-label"><?php esc_html_e( 'Phone / WhatsApp', 'safestore-minimal' ); ?></span>
							<a href="<?php echo esc_url( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a>
						</li>
						<li>
							<span class="sft-about-contact-label"><?php esc_html_e( 'Email', 'safestore-minimal' ); ?></span>
							<?php echo function_exists( 'safestore_contact_email_links' ) ? safestore_contact_email_links() : '<a href="' . esc_url( $mailto ) . '">' . esc_html( $email ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</li>
						<li>
							<span class="sft-about-contact-label"><?php esc_html_e( 'Hours', 'safestore-minimal' ); ?></span>
							<?php esc_html_e( 'Sat–Thu, 9am–8pm', 'safestore-minimal' ); ?>
						</li>
						<?php if ( '' !== $office ) : ?>
							<li>
								<span class="sft-about-contact-label"><?php esc_html_e( 'Office & pickup', 'safestore-minimal' ); ?></span>
								<?php echo esc_html( $office ); ?>
							</li>
						<?php endif; ?>
					</ul>
					<a class="sft-about-btn sft-about-btn--primary sft-about-contact-shop sft-copy-skip" href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp the sales desk', 'safestore-minimal' ); ?></a>

					<h4 class="sft-bulk-card__title"><?php esc_html_e( 'Every bulk order includes', 'safestore-minimal' ); ?></h4>
					<ul class="sft-bulk-card__list">
						<?php foreach ( $includes as $item ) : ?>
							<li><?php echo esc_html( $item ); ?></li>
						<?php endforeach; ?>
					</ul>
				</aside>
			</div>
		</section>

		<?php
		$bulk_actions  = safestore_page_cta_btn(
			array(
				'href'    => $wa_href,
				'label'   => __( 'Quote on WhatsApp', 'safestore-minimal' ),
				'variant' => 'primary',
				'blank'   => true,
			)
		);
		$bulk_actions .= safestore_page_cta_btn(
			array(
				'href'    => $phone_href,
				'label'   => $phone,
				'variant' => 'secondary',
			)
		);
		$bulk_actions .= safestore_page_cta_btn(
			array(
				'href'    => $shop_url,
				'label'   => __( 'Browse the catalogue', 'safestore-minimal' ),
				'variant' => 'secondary',
			)
		);

		safestore_render_page_cta(
			array(
				'title'   => __( 'Not sure what your site needs?', 'safestore-minimal' ),
				'text'    => __( 'Tell us the job and the hazards — we will put together a PPE list with standards and prices before you order.', 'safestore-minimal' ),
				'label'   => __( 'Talk to us', 'safestore-minimal' ),
				'actions' => $bulk_actions,
			)
		);
		?>
	</main>
	<?php
endwhile;

get_footer();

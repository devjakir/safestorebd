<?php
/**
 * Template Name: Track Order
 *
 * Order tracking — Bangladesh market, WooCommerce.
 *
 * @package safestore-minimal
 */

get_header();

$shipping_url = home_url( '/shipping-delivery/' );
$faq_url      = home_url( '/faqs/' );
$returns_url  = home_url( '/return-refund-policy/' );
$contact_url  = home_url( '/contact/' );
$phone_href   = 'tel:+8801761699627';
$phone        = '+880 176 1699 627';
$wa_href      = 'https://wa.me/8801761699627';
$email        = 'bdsafestore@gmail.com';
$steps        = safestore_minimal_get_track_steps();

while ( have_posts() ) :
	the_post();
	?>
	<main class="sft-about sft-track" id="main-content" itemscope itemtype="https://schema.org/WebPage">
		<meta itemprop="name" content="<?php echo esc_attr( get_the_title() ); ?>" />
		<meta itemprop="description" content="<?php echo esc_attr( __( 'Track your SafeStoreBD PPE order in Bangladesh — order number, status, and courier updates.', 'safestore-minimal' ) ); ?>" />

		<section class="sft-about-hero sft-track-hero" aria-labelledby="sft-track-title">
			<div class="sft-about-hero-inner">
				<p class="sft-about-eyebrow"><?php esc_html_e( 'Order status', 'safestore-minimal' ); ?></p>
				<h1 class="sft-about-title" id="sft-track-title"><?php the_title(); ?></h1>
				<p class="sft-about-lede">
					<?php esc_html_e( 'Enter your order number and billing email below, or message us on WhatsApp for courier tracking in Bangladesh.', 'safestore-minimal' ); ?>
				</p>
			</div>
		</section>

		<?php if ( trim( (string) get_post()->post_content ) !== '' ) : ?>
			<section class="sft-about-editor-wrap" aria-label="<?php esc_attr_e( 'Additional notes', 'safestore-minimal' ); ?>">
				<div class="sft-about-inner">
					<div class="sft-about-editor entry-content">
						<?php the_content(); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="sft-about-body sft-track-body" aria-labelledby="sft-track-form-heading">
			<div class="sft-about-inner sft-about-body-grid">
				<div class="sft-track-main">
					<h2 class="sft-about-h2" id="sft-track-form-heading"><?php esc_html_e( 'Look up your order', 'safestore-minimal' ); ?></h2>

					<?php if ( function_exists( 'wc_print_notices' ) ) : ?>
						<div class="sft-track-notices">
							<?php wc_print_notices(); ?>
						</div>
					<?php endif; ?>

					<div class="sft-track-form">
						<?php
						if ( shortcode_exists( 'woocommerce_order_tracking' ) ) {
							echo do_shortcode( '[woocommerce_order_tracking]' );
						} else {
							echo '<p>' . esc_html__( 'Order tracking is temporarily unavailable. Please contact us on WhatsApp with your order number.', 'safestore-minimal' ) . '</p>';
						}
						?>
					</div>

					<h3 class="sft-track-steps-heading"><?php esc_html_e( 'What happens next', 'safestore-minimal' ); ?></h3>
					<ol class="sft-track-steps">
						<?php foreach ( $steps as $step ) : ?>
							<li>
								<strong><?php echo esc_html( $step['title'] ); ?></strong>
								<span><?php echo esc_html( $step['text'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>

				<aside class="sft-about-contact-card" aria-labelledby="sft-track-help-heading">
					<h3 class="sft-about-h3" id="sft-track-help-heading"><?php esc_html_e( 'Need tracking now?', 'safestore-minimal' ); ?></h3>
					<p class="sft-about-contact-lead">
						<?php esc_html_e( 'Share your order number and phone on WhatsApp — we send courier links when your parcel leaves our Pallabi office.', 'safestore-minimal' ); ?>
					</p>
					<ul class="sft-about-contact-list">
						<li><a href="<?php echo esc_url( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a></li>
						<li><a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a></li>
						<li>
							<a class="sft-about-contact-wa" href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp', 'safestore-minimal' ); ?></a>
						</li>
					</ul>
					<a class="sft-about-btn sft-about-btn--primary sft-about-contact-shop" href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Message us', 'safestore-minimal' ); ?></a>
				</aside>
			</div>
		</section>

		<aside class="sft-track-page-footer" aria-label="<?php esc_attr_e( 'Order tracking help', 'safestore-minimal' ); ?>">
			<div class="sft-track-page-footer-inner">
				<div class="sft-track-page-footer-grid">
					<div class="sft-track-page-footer-col">
						<h3 class="sft-track-page-footer-heading"><?php esc_html_e( 'Quick links', 'safestore-minimal' ); ?></h3>
						<ul class="sft-track-page-footer-links">
							<li><a href="<?php echo esc_url( $shipping_url ); ?>"><?php esc_html_e( 'Shipping & delivery', 'safestore-minimal' ); ?></a></li>
							<li><a href="<?php echo esc_url( $faq_url ); ?>"><?php esc_html_e( 'FAQ', 'safestore-minimal' ); ?></a></li>
							<li><a href="<?php echo esc_url( $returns_url ); ?>"><?php esc_html_e( 'Returns', 'safestore-minimal' ); ?></a></li>
						</ul>
					</div>
					<div class="sft-track-page-footer-col">
						<h3 class="sft-track-page-footer-heading"><?php esc_html_e( 'Can’t find your order?', 'safestore-minimal' ); ?></h3>
						<ul class="sft-track-page-footer-tips">
							<li><?php esc_html_e( 'Use the order number from your confirmation email or SMS.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Billing email must match checkout (same as bKash/Nagad receipt name if used).', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'COD not yet delivered? The courier may call first — keep your line open.', 'safestore-minimal' ); ?></li>
						</ul>
					</div>
					<div class="sft-track-page-footer-col">
						<h3 class="sft-track-page-footer-heading"><?php esc_html_e( 'Dispatch hours', 'safestore-minimal' ); ?></h3>
						<ul class="sft-track-page-footer-tips">
							<li><?php esc_html_e( 'Packed and handed to courier Sat–Thu from our Pallabi office.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Closed Fridays — updates resume Saturday.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Outside Dhaka: allow 2–3 business days after dispatch.', 'safestore-minimal' ); ?></li>
						</ul>
					</div>
				</div>

				<div class="sft-track-page-footer-cta">
					<div class="sft-track-page-footer-cta-copy">
						<h2 class="sft-track-page-footer-cta-title"><?php esc_html_e( 'Still waiting for an update?', 'safestore-minimal' ); ?></h2>
						<p><?php esc_html_e( 'WhatsApp your order number — we will check with the warehouse and courier.', 'safestore-minimal' ); ?></p>
					</div>
					<div class="sft-track-page-footer-cta-actions">
						<a class="sft-about-btn sft-about-btn--primary sft-about-btn--light" href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp', 'safestore-minimal' ); ?></a>
						<a class="sft-about-btn sft-about-btn--ghost sft-about-btn--light" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact', 'safestore-minimal' ); ?></a>
					</div>
				</div>
			</div>
		</aside>
	</main>
	<?php
endwhile;

get_footer();

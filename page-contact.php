<?php
/**
 * Template Name: Contact
 *
 * Contact page — Fluent Forms.
 *
 * @package safestore-minimal
 */

get_header();

$faq_url      = home_url( '/faqs/' );
$track_url    = home_url( '/track-order/' );
$shipping_url = home_url( '/shipping-delivery/' );
$wa_href      = 'https://wa.me/8801761699627';

while ( have_posts() ) :
	the_post();
	?>
	<main class="sft-about sft-contact" id="main-content" itemscope itemtype="https://schema.org/ContactPage">
		<meta itemprop="name" content="<?php echo esc_attr( get_the_title() ); ?>" />

		<section class="sft-about-hero sft-contact-hero" aria-labelledby="sft-contact-title">
			<div class="sft-about-hero-inner">
				<p class="sft-about-eyebrow"><?php esc_html_e( 'Support', 'safestore-minimal' ); ?></p>
				<h1 class="sft-about-title" id="sft-contact-title"><?php the_title(); ?></h1>
				<p class="sft-about-lede">
					<?php esc_html_e( 'Questions about PPE specs, bulk pricing, delivery, or returns — send a message and we will reply by email or WhatsApp.', 'safestore-minimal' ); ?>
				</p>
			</div>
		</section>

		<section class="sft-about-body sft-contact-body" aria-labelledby="sft-contact-form-heading">
			<div class="sft-about-inner sft-contact-inner">
				<h2 class="sft-about-h2" id="sft-contact-form-heading"><?php esc_html_e( 'Send us a message', 'safestore-minimal' ); ?></h2>
				<p class="sft-contact-required-note">
					<?php esc_html_e( 'Fields marked with * are required. We usually reply within one business day (Sat–Thu).', 'safestore-minimal' ); ?>
				</p>
				<?php echo do_shortcode('[fluentform id="1"]'); ?>
				<?php if ( trim( (string) get_post()->post_content ) !== '' ) : ?>
					<div class="sft-contact-form entry-content">
						<?php the_content(); ?>
					</div>
				<?php else : ?>
<!-- 					<p class="sft-contact-empty"> -->
						<?php
// 						printf(
// 							/* translators: %s: WhatsApp link */
// 							wp_kses_post( __( 'Add your Fluent Forms shortcode to this page in the editor, or <a href="%s">message us on WhatsApp</a> for immediate help.', 'safestore-minimal' ) ),
// 							esc_url( $wa_href )
// 						);
						?>
<!-- 					</p> -->
				<?php endif; ?>
			</div>
		</section>

		<section class="sft-about-cta sft-contact-cta" aria-labelledby="sft-contact-cta-heading">
			<div class="sft-about-inner sft-about-cta-inner">
				<div class="sft-about-cta-copy">
					<h2 class="sft-about-cta-title" id="sft-contact-cta-heading"><?php esc_html_e( 'Looking for something else?', 'safestore-minimal' ); ?></h2>
					<p><?php esc_html_e( 'Track an order, read shipping times, or browse common questions.', 'safestore-minimal' ); ?></p>
				</div>
				<div class="sft-about-cta-actions">
					<a class="sft-about-btn sft-about-btn--primary sft-about-btn--light" href="<?php echo esc_url( $track_url ); ?>"><?php esc_html_e( 'Track order', 'safestore-minimal' ); ?></a>
					<a class="sft-about-btn sft-about-btn--ghost sft-about-btn--light" href="<?php echo esc_url( $faq_url ); ?>"><?php esc_html_e( 'FAQ', 'safestore-minimal' ); ?></a>
					<a class="sft-about-btn sft-about-btn--ghost sft-about-btn--light" href="<?php echo esc_url( $shipping_url ); ?>"><?php esc_html_e( 'Shipping', 'safestore-minimal' ); ?></a>
				</div>
			</div>
		</section>
	</main>
	<?php
endwhile;

get_footer();

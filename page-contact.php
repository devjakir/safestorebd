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
$wa_href      = safestore_wa_link();

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

		<?php
		$cta_actions = '';
		$cta_actions .= safestore_page_cta_btn(
			array(
				'href'    => $track_url,
				'label'   => __( 'Track order', 'safestore-minimal' ),
				'variant' => 'primary',
			)
		);
		$cta_actions .= safestore_page_cta_btn(
			array(
				'href'    => $faq_url,
				'label'   => __( 'FAQ', 'safestore-minimal' ),
				'variant' => 'secondary',
			)
		);
		$cta_actions .= safestore_page_cta_btn(
			array(
				'href'    => $shipping_url,
				'label'   => __( 'Shipping', 'safestore-minimal' ),
				'variant' => 'secondary',
			)
		);

		safestore_render_page_cta(
			array(
				'title'   => __( 'Looking for something else?', 'safestore-minimal' ),
				'text'    => __( 'Track an order, read shipping times, or browse common questions.', 'safestore-minimal' ),
				'label'   => __( 'Other help', 'safestore-minimal' ),
				'actions' => $cta_actions,
			)
		);
		?>
	</main>
	<?php
endwhile;

get_footer();

<?php
/**
 * Template Name: Terms of Service
 *
 * @package safestore-minimal
 */

get_header();

$privacy_url  = home_url( '/privacy-policy/' );
$returns_url  = home_url( '/return-refund-policy/' );
$shipping_url = home_url( '/shipping-delivery/' );
$contact_url  = home_url( '/contact/' );
$email        = 'bdsafestore@gmail.com';
$wa_href      = 'https://wa.me/8801880307446';
$sections     = safestore_minimal_get_terms_sections();
$updated      = apply_filters( 'safestore_minimal_terms_updated', 'May 2026' );

while ( have_posts() ) :
	the_post();
	?>
	<main class="sft-about sft-terms" id="main-content" itemscope itemtype="https://schema.org/WebPage">
		<meta itemprop="name" content="<?php echo esc_attr( get_the_title() ); ?>" />

		<section class="sft-about-hero sft-terms-hero" aria-labelledby="sft-terms-title">
			<div class="sft-about-hero-inner">
				<h1 class="sft-about-title" id="sft-terms-title"><?php the_title(); ?></h1>
				<p class="sft-about-lede">
					<?php esc_html_e( 'Rules for buying industrial PPE from SafeStoreBD — imported from China, delivered across Bangladesh.', 'safestore-minimal' ); ?>
				</p>
				<p class="sft-terms-updated"><?php echo esc_html( sprintf( __( 'Last updated: %s', 'safestore-minimal' ), $updated ) ); ?></p>
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

		<section class="sft-about-body sft-terms-body" aria-label="<?php esc_attr_e( 'Terms of service', 'safestore-minimal' ); ?>">
			<div class="sft-about-inner sft-about-body-grid">
				<article class="sft-terms-content">
					<?php foreach ( $sections as $section ) : ?>
						<section class="sft-policy-section" id="<?php echo esc_attr( $section['id'] ); ?>">
							<h2 class="sft-about-h2"><?php echo esc_html( $section['title'] ); ?></h2>
							<?php if ( ! empty( $section['paragraphs'] ) ) : ?>
								<?php foreach ( $section['paragraphs'] as $paragraph ) : ?>
									<p class="sft-about-summary-text"><?php echo wp_kses_post( $paragraph ); ?></p>
								<?php endforeach; ?>
							<?php endif; ?>
							<?php if ( ! empty( $section['list'] ) ) : ?>
								<ul class="sft-about-highlights">
									<?php foreach ( $section['list'] as $item ) : ?>
										<li><?php echo wp_kses_post( $item ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</section>
					<?php endforeach; ?>
				</article>

				<aside class="sft-about-contact-card" aria-labelledby="sft-terms-help-heading">
					<h3 class="sft-about-h3" id="sft-terms-help-heading"><?php esc_html_e( 'Order support', 'safestore-minimal' ); ?></h3>
					<p class="sft-about-contact-lead">
						<?php esc_html_e( 'Questions before you buy or after delivery — Sat–Thu, 9am–8pm.', 'safestore-minimal' ); ?>
					</p>
					<ul class="sft-about-contact-list">
						<li><a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a></li>
						<li>
							<a class="sft-about-contact-wa" href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp', 'safestore-minimal' ); ?></a>
						</li>
					</ul>
					<a class="sft-about-btn sft-about-btn--primary sft-about-contact-shop" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact us', 'safestore-minimal' ); ?></a>
				</aside>
			</div>
		</section>

		<aside class="sft-terms-page-footer" aria-label="<?php esc_attr_e( 'Related policies', 'safestore-minimal' ); ?>">
			<div class="sft-terms-page-footer-inner">
				<div class="sft-terms-page-footer-grid">
					<div class="sft-terms-page-footer-col">
						<h3 class="sft-terms-page-footer-heading"><?php esc_html_e( 'Policies', 'safestore-minimal' ); ?></h3>
						<ul class="sft-terms-page-footer-links">
							<li><a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Privacy policy', 'safestore-minimal' ); ?></a></li>
							<li><a href="<?php echo esc_url( $returns_url ); ?>"><?php esc_html_e( 'Return & refund', 'safestore-minimal' ); ?></a></li>
							<li><a href="<?php echo esc_url( $shipping_url ); ?>"><?php esc_html_e( 'Shipping', 'safestore-minimal' ); ?></a></li>
						</ul>
					</div>
					<div class="sft-terms-page-footer-col">
						<h3 class="sft-terms-page-footer-heading"><?php esc_html_e( 'Before you order', 'safestore-minimal' ); ?></h3>
						<ul class="sft-terms-page-footer-tips">
							<li><?php esc_html_e( 'PPE is sourced from China — check sizing and specs on each product.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Prices in BDT; shipping shown at checkout.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'COD: inspect the parcel before paying the courier.', 'safestore-minimal' ); ?></li>
						</ul>
					</div>
					<div class="sft-terms-page-footer-col">
						<h3 class="sft-terms-page-footer-heading"><?php esc_html_e( 'Your protection', 'safestore-minimal' ); ?></h3>
						<ul class="sft-terms-page-footer-tips">
							<li><?php esc_html_e( 'Bangladesh consumer law applies to your purchase.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( '7-day returns on unused items — contact us first.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Workplace safety compliance is the buyer’s responsibility.', 'safestore-minimal' ); ?></li>
						</ul>
					</div>
				</div>

				<div class="sft-terms-page-footer-cta">
					<div class="sft-terms-page-footer-cta-copy">
						<h2 class="sft-terms-page-footer-cta-title"><?php esc_html_e( 'Questions about these terms?', 'safestore-minimal' ); ?></h2>
						<p><?php esc_html_e( 'WhatsApp or email us before placing a bulk or corporate order.', 'safestore-minimal' ); ?></p>
					</div>
					<div class="sft-terms-page-footer-cta-actions">
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

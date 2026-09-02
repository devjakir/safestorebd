<?php
/**
 * Template Name: Privacy Policy
 *
 * @package safestore-minimal
 */

get_header();

$contact_url  = home_url( '/contact/' );
$terms_url    = home_url( '/terms-of-service/' );
$returns_url  = home_url( '/return-refund-policy/' );
$email        = 'contact@safestorebd.com';
$privacy_mail = 'mailto:' . $email . '?subject=' . rawurlencode( 'Privacy request — SafeStoreBD' );
$wa_href      = safestore_wa_link();
$wa_phone     = safestore_wa_display();
$phone        = '+880 1811-892291';
$sections     = safestore_minimal_get_privacy_sections();
$updated      = apply_filters( 'safestore_minimal_privacy_policy_updated', 'May 2026' );

while ( have_posts() ) :
	the_post();
	?>
	<main class="sft-about sft-privacy" id="main-content" itemscope itemtype="https://schema.org/WebPage">
		<meta itemprop="name" content="<?php echo esc_attr( get_the_title() ); ?>" />

		<section class="sft-about-hero sft-privacy-hero" aria-labelledby="sft-privacy-title">
			<div class="sft-about-hero-inner">
				<h1 class="sft-about-title" id="sft-privacy-title"><?php the_title(); ?></h1>
				<p class="sft-about-lede">
					<?php esc_html_e( 'PPE imported from China, sold in Bangladesh — how we handle your personal information.', 'safestore-minimal' ); ?>
				</p>
				<p class="sft-privacy-updated"><?php echo esc_html( sprintf( __( 'Last updated: %s', 'safestore-minimal' ), $updated ) ); ?></p>
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

		<section class="sft-about-body sft-privacy-body" aria-label="<?php esc_attr_e( 'Privacy policy', 'safestore-minimal' ); ?>">
			<div class="sft-about-inner sft-about-body-grid">
				<article class="sft-privacy-content">
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

				<aside class="sft-about-contact-card" aria-labelledby="sft-privacy-contact-heading">
					<h3 class="sft-about-h3" id="sft-privacy-contact-heading"><?php esc_html_e( 'Privacy contact', 'safestore-minimal' ); ?></h3>
					<p class="sft-about-contact-lead">
						<?php esc_html_e( 'Email or WhatsApp for data requests. Sat–Thu, 9am–8pm.', 'safestore-minimal' ); ?>
					</p>
					<ul class="sft-about-contact-list">
						<li><?php echo safestore_contact_email_links(); ?></li>
						<li>
							<?php echo safestore_wa_cta_link( $wa_href, $wa_phone, 'sft-about-contact-wa' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</li>
					</ul>
					<a class="sft-about-btn sft-about-btn--primary sft-about-contact-shop" href="<?php echo esc_url( $privacy_mail ); ?>"><?php esc_html_e( 'Email us', 'safestore-minimal' ); ?></a>
				</aside>
			</div>
		</section>

		<?php safestore_render_page_cta(); ?>
	</main>
	<?php
endwhile;

get_footer();

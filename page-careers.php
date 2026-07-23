<?php
/**
 * Template Name: Careers
 *
 * Careers at SafeStoreBD — industrial safety products, Bangladesh.
 *
 * @package safestore-minimal
 */

get_header();

$about_url    = home_url( '/about/' );
$contact_url  = home_url( '/contact/' );
$email        = 'bdsafestore@gmail.com';
$mailto       = 'mailto:' . $email . '?subject=' . rawurlencode( 'Job application — SafeStoreBD' );
$phone_href   = 'tel:+8801880307446';
$phone        = '+880 1880-307446';
$wa_href      = 'https://wa.me/8801880307446';
$openings     = safestore_minimal_get_career_openings();
$location     = safestore_minimal_get_pickup_address();

while ( have_posts() ) :
	the_post();
	?>
	<main class="sft-about sft-careers" id="main-content" itemscope itemtype="https://schema.org/WebPage">
		<meta itemprop="name" content="<?php echo esc_attr( get_the_title() ); ?>" />
		<meta itemprop="description" content="<?php echo esc_attr( __( 'Join SafeStoreBD — industrial PPE supply, warehouse, sales, and support roles in Dhaka, Bangladesh.', 'safestore-minimal' ) ); ?>" />

		<section class="sft-about-hero sft-careers-hero" aria-labelledby="sft-careers-title">
			<div class="sft-about-hero-inner">
				<p class="sft-about-eyebrow"><?php esc_html_e( 'Careers', 'safestore-minimal' ); ?></p>
				<h1 class="sft-about-title" id="sft-careers-title"><?php the_title(); ?></h1>
				<p class="sft-about-lede">
					<?php esc_html_e( 'Help factories and worksites across Bangladesh get the PPE they need — from our Pallabi team.', 'safestore-minimal' ); ?>
				</p>
				<div class="sft-about-hero-cta">
					<a class="sft-about-btn sft-about-btn--primary" href="<?php echo esc_url( $mailto ); ?>"><?php esc_html_e( 'Apply by email', 'safestore-minimal' ); ?></a>
					<a class="sft-about-btn sft-about-btn--ghost" href="<?php echo esc_url( $about_url ); ?>"><?php esc_html_e( 'About us', 'safestore-minimal' ); ?></a>
				</div>
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

		<section class="sft-about-body sft-careers-body" aria-labelledby="sft-careers-openings-heading">
			<div class="sft-about-inner sft-about-body-grid">
				<div class="sft-careers-main">
					<h2 class="sft-about-h2" id="sft-careers-openings-heading"><?php esc_html_e( 'Open roles', 'safestore-minimal' ); ?></h2>
					<p class="sft-about-summary-text">
						<?php esc_html_e( 'We are a growing industrial safety supplier. Roles are based in Pallabi, Dhaka unless noted. Bengali and English both used day to day.', 'safestore-minimal' ); ?>
					</p>

					<ul class="sft-careers-list">
						<?php foreach ( $openings as $job ) : ?>
							<li class="sft-careers-card">
								<div class="sft-careers-card-head">
									<h3 class="sft-careers-card-title"><?php echo esc_html( $job['title'] ); ?></h3>
									<span class="sft-careers-card-meta"><?php echo esc_html( $job['type'] ); ?></span>
								</div>
								<p class="sft-careers-card-summary"><?php echo esc_html( $job['summary'] ); ?></p>
							</li>
						<?php endforeach; ?>
					</ul>

					<p class="sft-careers-general">
						<?php esc_html_e( 'Don’t see your role? Send a general application — we keep strong CVs on file.', 'safestore-minimal' ); ?>
					</p>
				</div>

				<aside class="sft-about-contact-card" aria-labelledby="sft-careers-apply-heading">
					<h3 class="sft-about-h3" id="sft-careers-apply-heading"><?php esc_html_e( 'How to apply', 'safestore-minimal' ); ?></h3>
					<p class="sft-about-contact-lead">
						<?php esc_html_e( 'Email your CV (PDF) with the role in the subject line, or message us on WhatsApp with a short intro.', 'safestore-minimal' ); ?>
					</p>
					<ul class="sft-about-contact-list">
						<li><a href="<?php echo esc_url( $mailto ); ?>"><?php echo esc_html( $email ); ?></a></li>
						<li><a href="<?php echo esc_url( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a></li>
						<li>
							<?php echo safestore_wa_cta_link( $wa_href, $phone, 'sft-about-contact-wa' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</li>
					</ul>
					<p class="sft-careers-location">
						<strong><?php esc_html_e( 'Office:', 'safestore-minimal' ); ?></strong>
						<?php echo esc_html( $location ); ?>
					</p>
					<a class="sft-about-btn sft-about-btn--primary sft-about-contact-shop" href="<?php echo esc_url( $mailto ); ?>"><?php esc_html_e( 'Send CV', 'safestore-minimal' ); ?></a>
				</aside>
			</div>
		</section>

		<aside class="sft-careers-page-footer" aria-label="<?php esc_attr_e( 'Careers information', 'safestore-minimal' ); ?>">
			<div class="sft-careers-page-footer-inner">
				<div class="sft-careers-page-footer-grid">
					<div class="sft-careers-page-footer-col">
						<h3 class="sft-careers-page-footer-heading"><?php esc_html_e( 'Company', 'safestore-minimal' ); ?></h3>
						<ul class="sft-careers-page-footer-links">
							<li><a href="<?php echo esc_url( $about_url ); ?>"><?php esc_html_e( 'About SafeStoreBD', 'safestore-minimal' ); ?></a></li>
							<li><a href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact', 'safestore-minimal' ); ?></a></li>
						</ul>
					</div>
					<div class="sft-careers-page-footer-col">
						<h3 class="sft-careers-page-footer-heading"><?php esc_html_e( 'What we look for', 'safestore-minimal' ); ?></h3>
						<ul class="sft-careers-page-footer-tips">
							<li><?php esc_html_e( 'Reliable attendance — Sat–Thu schedule, closed Fridays.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Comfort with WhatsApp, couriers, and factory customers.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Interest in industrial safety and honest product communication.', 'safestore-minimal' ); ?></li>
						</ul>
					</div>
					<div class="sft-careers-page-footer-col">
						<h3 class="sft-careers-page-footer-heading"><?php esc_html_e( 'Your application', 'safestore-minimal' ); ?></h3>
						<ul class="sft-careers-page-footer-tips">
							<li><?php esc_html_e( 'CV in PDF (1–2 pages).', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Role name in the email subject.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Expected salary (optional, in BDT).', 'safestore-minimal' ); ?></li>
						</ul>
					</div>
				</div>

				<div class="sft-careers-page-footer-cta">
					<div class="sft-careers-page-footer-cta-copy">
						<h2 class="sft-careers-page-footer-cta-title"><?php esc_html_e( 'Ready to apply?', 'safestore-minimal' ); ?></h2>
						<p><?php esc_html_e( 'We review applications during business hours and reply by email or WhatsApp.', 'safestore-minimal' ); ?></p>
					</div>
					<div class="sft-careers-page-footer-cta-actions">
						<a class="sft-about-btn sft-about-btn--primary sft-about-btn--light" href="<?php echo esc_url( $mailto ); ?>"><?php esc_html_e( 'Email CV', 'safestore-minimal' ); ?></a>
						<?php echo safestore_wa_cta_link( $wa_href, $phone, 'sft-about-btn sft-about-btn--ghost sft-about-btn--light' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</div>
			</div>
		</aside>
	</main>
	<?php
endwhile;

get_footer();

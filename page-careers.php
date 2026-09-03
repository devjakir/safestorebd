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
$email        = 'contact@safestorebd.com';
$mailto       = 'mailto:' . $email . '?subject=' . rawurlencode( 'Job application — SafeStoreBD' );
$phone_href   = 'tel:' . safestore_primary_phone_e164();
$phone        = safestore_primary_phone_display();
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
						<?php esc_html_e( 'Email your CV (PDF) with the role in the subject line, or call / WhatsApp us on the number below with a short intro.', 'safestore-minimal' ); ?>
					</p>
					<ul class="sft-about-contact-list">
						<li>
							<span class="sft-about-contact-label"><?php esc_html_e( 'Email', 'safestore-minimal' ); ?></span>
							<?php echo safestore_contact_email_links(); ?>
						</li>
						<li>
							<span class="sft-about-contact-label"><?php esc_html_e( 'Phone / WhatsApp', 'safestore-minimal' ); ?></span>
							<a href="<?php echo esc_url( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a>
						</li>
					</ul>
					<p class="sft-careers-location">
						<strong><?php esc_html_e( 'Office:', 'safestore-minimal' ); ?></strong>
						<?php echo esc_html( $location ); ?>
					</p>
					<a class="sft-about-btn sft-about-btn--primary sft-about-contact-shop sft-copy-skip" href="<?php echo esc_url( $mailto ); ?>"><?php esc_html_e( 'Send CV', 'safestore-minimal' ); ?></a>

					<div class="sft-careers-notes">
						<h4 class="sft-careers-notes__title"><?php esc_html_e( 'What we look for', 'safestore-minimal' ); ?></h4>
						<ul class="sft-careers-notes__list">
							<li><?php esc_html_e( 'Reliable attendance — Sat–Thu schedule, closed Fridays.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Comfort with WhatsApp, couriers, and factory customers.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Interest in industrial safety and honest product communication.', 'safestore-minimal' ); ?></li>
						</ul>

						<h4 class="sft-careers-notes__title"><?php esc_html_e( 'Before you send', 'safestore-minimal' ); ?></h4>
						<ul class="sft-careers-notes__list">
							<li><?php esc_html_e( 'CV in PDF, 1–2 pages.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Expected salary (optional, in BDT).', 'safestore-minimal' ); ?></li>
						</ul>
					</div>
				</aside>
			</div>
		</section>

		<?php
		$careers_actions  = safestore_page_cta_btn(
			array(
				'href'    => $mailto,
				'label'   => __( 'Email CV', 'safestore-minimal' ),
				'variant' => 'wa',
				'icon'    => '<svg class="sft-page-cta__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z"/><path d="m3.4 6.3 8.6 6 8.6-6"/></svg>',
			)
		);
		$careers_actions .= safestore_page_cta_btn(
			array(
				'href'    => safestore_primary_wa_link(),
				'label'   => safestore_primary_phone_display(),
				'variant' => 'call',
				'blank'   => true,
				'aria'    => sprintf( /* translators: %s: phone number */ __( 'Chat on WhatsApp: %s', 'safestore-minimal' ), safestore_primary_phone_display() ),
				'icon'    => '<svg class="sft-page-cta__icon" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false"><path d="M17.5 14.4c-.3-.2-1.7-.9-2-1-.3-.1-.5-.2-.7.1-.2.3-.7 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.3-.5 0-.2 0-.4-.1-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1.1 2.8 1.2 3c.2.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.7-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.2-.6-.4z"/><path d="M12 2A10 10 0 0 0 3.4 17.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2c-1.5 0-3-.4-4.3-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2z"/></svg>',
			)
		);

		safestore_render_page_cta(
			array(
				'title'   => __( 'Ready to apply?', 'safestore-minimal' ),
				'text'    => __( 'We review applications during business hours and reply by email or WhatsApp.', 'safestore-minimal' ),
				'label'   => __( 'Apply to SafeStoreBD', 'safestore-minimal' ),
				'actions' => $careers_actions,
			)
		);
		?>
	</main>
	<?php
endwhile;

get_footer();

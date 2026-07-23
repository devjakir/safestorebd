<?php
/**
 * Template Name: About
 *
 * @package safestore-minimal
 */
get_header();

$shop_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$contact    = home_url( '/contact/' );
$bulk_url   = home_url( '/bulk-orders/' );
$phone_href = 'tel:+8801880307446';
$phone      = '+880 1880-307446';
$wa_href    = 'https://wa.me/8801880307446';
$email      = 'bdsafestore@gmail.com';

while ( have_posts() ) :
	the_post();
	?>
	<main class="sft-about" id="main-content">
		<section class="sft-about-hero" aria-labelledby="sft-about-title">
			<div class="sft-about-hero-inner">
				<p class="sft-about-eyebrow"><?php esc_html_e( 'About SafeStoreBD', 'safestore-minimal' ); ?></p>
				<h1 class="sft-about-title" id="sft-about-title"><?php the_title(); ?></h1>
				<p class="sft-about-lede">
					<?php esc_html_e( 'Industrial safety equipment and PPE for workplaces across Bangladesh. Our products are sourced from China and stocked locally — straightforward specs, fair pricing, and reliable delivery.', 'safestore-minimal' ); ?>
				</p>
				<ul class="sft-about-trust" role="list">
					<li><?php esc_html_e( 'Nationwide delivery', 'safestore-minimal' ); ?></li>
					<li><?php esc_html_e( 'Sourced from China', 'safestore-minimal' ); ?></li>
					<li><?php esc_html_e( 'B2B &amp; bulk orders', 'safestore-minimal' ); ?></li>
				</ul>
				<div class="sft-about-hero-cta">
					<a class="sft-about-btn sft-about-btn--primary" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop catalogue', 'safestore-minimal' ); ?></a>
					<a class="sft-about-btn sft-about-btn--ghost" href="<?php echo esc_url( $contact ); ?>"><?php esc_html_e( 'Contact us', 'safestore-minimal' ); ?></a>
				</div>
			</div>
		</section>

		<?php if ( isset( $post->post_content ) && trim( (string) $post->post_content ) !== '' ) : ?>
			<section class="sft-about-editor-wrap" aria-label="<?php esc_attr_e( 'Page content', 'safestore-minimal' ); ?>">
				<div class="sft-about-inner">
					<div class="sft-about-editor entry-content">
						<?php the_content(); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="sft-about-body" aria-labelledby="sft-about-summary-heading">
			<div class="sft-about-inner sft-about-body-grid">
				<div class="sft-about-summary">
					<h2 class="sft-about-h2" id="sft-about-summary-heading"><?php esc_html_e( 'Who we are', 'safestore-minimal' ); ?></h2>
					<p class="sft-about-summary-text">
						<?php esc_html_e( 'We supply PPE and industrial safety gear to employers across Bangladesh. We do not issue or publish formal certifications ourselves; products are imported from suppliers in China. Ask us for photos, descriptions, or sizing details before you buy.', 'safestore-minimal' ); ?>
					</p>
					<ul class="sft-about-highlights">
						<li><?php esc_html_e( 'Imports from China — curated for workplace safety categories', 'safestore-minimal' ); ?></li>
						<li><?php esc_html_e( 'Honest product details — contact us if you need specifics', 'safestore-minimal' ); ?></li>
						<li><?php esc_html_e( 'Dispatch to factories and project sites nationwide', 'safestore-minimal' ); ?></li>
					</ul>
				</div>
				<aside class="sft-about-contact-card" aria-labelledby="sft-about-contact-heading">
					<h3 class="sft-about-h3" id="sft-about-contact-heading"><?php esc_html_e( 'Get in touch', 'safestore-minimal' ); ?></h3>
					<p class="sft-about-contact-lead"><?php esc_html_e( 'Questions about specs, bulk pricing, or delivery — reach us directly.', 'safestore-minimal' ); ?></p>
					<ul class="sft-about-contact-list">
						<li><a href="<?php echo esc_url( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a></li>
						<li><a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a></li>
						<li>
							<?php echo safestore_wa_cta_link( $wa_href, $phone, 'sft-about-contact-wa' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</li>
					</ul>
					<a class="sft-about-btn sft-about-btn--primary sft-about-contact-shop" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Browse shop', 'safestore-minimal' ); ?></a>
				</aside>
			</div>
		</section>

		<section class="sft-about-cta" aria-labelledby="sft-about-cta-heading">
			<div class="sft-about-inner sft-about-cta-inner">
				<div class="sft-about-cta-copy">
					<h2 class="sft-about-cta-title" id="sft-about-cta-heading"><?php esc_html_e( 'Need a quote or bulk order?', 'safestore-minimal' ); ?></h2>
					<p><?php esc_html_e( 'Share your requirements — we will respond with options and lead times.', 'safestore-minimal' ); ?></p>
				</div>
				<div class="sft-about-cta-actions">
					<a class="sft-about-btn sft-about-btn--primary sft-about-btn--light" href="<?php echo esc_url( $bulk_url ); ?>"><?php esc_html_e( 'Bulk / corporate', 'safestore-minimal' ); ?></a>
					<a class="sft-about-btn sft-about-btn--ghost sft-about-btn--light" href="<?php echo esc_url( $contact ); ?>"><?php esc_html_e( 'Contact form', 'safestore-minimal' ); ?></a>
				</div>
			</div>
		</section>
	</main>
	<?php
endwhile;

get_footer();

<?php
/**
 * Template Name: Sitemap
 *
 * @package safestore-minimal
 */

get_header();

$legal_url    = home_url( '/legal/' );
$contact_url  = home_url( '/contact/' );
$wa_href      = 'https://wa.me/8801880307446';
$phone        = '+880 1880-307446';
$groups       = safestore_minimal_get_sitemap_groups();

while ( have_posts() ) :
	the_post();
	?>
	<main class="sft-about sft-sitemap" id="main-content" itemscope itemtype="https://schema.org/WebPage">
		<meta itemprop="name" content="<?php echo esc_attr( get_the_title() ); ?>" />

		<section class="sft-about-hero sft-sitemap-hero" aria-labelledby="sft-sitemap-title">
			<div class="sft-about-hero-inner">
				<h1 class="sft-about-title" id="sft-sitemap-title"><?php the_title(); ?></h1>
				<p class="sft-about-lede">
					<?php esc_html_e( 'Find pages on SafeStoreBD — shop, support, and legal information for PPE in Bangladesh.', 'safestore-minimal' ); ?>
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

		<section class="sft-about-body sft-sitemap-body" aria-label="<?php esc_attr_e( 'Site map', 'safestore-minimal' ); ?>">
			<div class="sft-about-inner">
				<div class="sft-sitemap-grid">
					<?php foreach ( $groups as $group ) : ?>
						<section class="sft-sitemap-group">
							<h2 class="sft-sitemap-group-title"><?php echo esc_html( $group['title'] ); ?></h2>
							<ul class="sft-sitemap-links">
								<?php foreach ( $group['links'] as $link ) : ?>
									<li><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<aside class="sft-sitemap-page-footer" aria-label="<?php esc_attr_e( 'Sitemap footer', 'safestore-minimal' ); ?>">
			<div class="sft-sitemap-page-footer-inner">
				<div class="sft-sitemap-page-footer-grid">
					<div class="sft-sitemap-page-footer-col">
						<h3 class="sft-sitemap-page-footer-heading"><?php esc_html_e( 'Shop', 'safestore-minimal' ); ?></h3>
						<ul class="sft-sitemap-page-footer-links">
							<?php
							$shop_group = $groups[0]['links'] ?? array();
							foreach ( array_slice( $shop_group, 0, 4 ) as $link ) :
								?>
								<li><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<div class="sft-sitemap-page-footer-col">
						<h3 class="sft-sitemap-page-footer-heading"><?php esc_html_e( 'Support', 'safestore-minimal' ); ?></h3>
						<ul class="sft-sitemap-page-footer-tips">
							<li><?php esc_html_e( 'Track orders, shipping, returns, and FAQ.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'WhatsApp support for Bangladesh customers.', 'safestore-minimal' ); ?></li>
						</ul>
					</div>
					<div class="sft-sitemap-page-footer-col">
						<h3 class="sft-sitemap-page-footer-heading"><?php esc_html_e( 'Legal', 'safestore-minimal' ); ?></h3>
						<ul class="sft-sitemap-page-footer-links">
							<li><a href="<?php echo esc_url( $legal_url ); ?>"><?php esc_html_e( 'Legal hub', 'safestore-minimal' ); ?></a></li>
							<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy', 'safestore-minimal' ); ?></a></li>
							<li><a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>"><?php esc_html_e( 'Terms', 'safestore-minimal' ); ?></a></li>
						</ul>
					</div>
				</div>

				<div class="sft-sitemap-page-footer-cta">
					<div class="sft-sitemap-page-footer-cta-copy">
						<h2 class="sft-sitemap-page-footer-cta-title"><?php esc_html_e( 'Can’t find a page?', 'safestore-minimal' ); ?></h2>
						<p><?php esc_html_e( 'Search the shop or contact us — we sell industrial safety gear nationwide from Dhaka.', 'safestore-minimal' ); ?></p>
					</div>
					<div class="sft-sitemap-page-footer-cta-actions">
						<a class="sft-about-btn sft-about-btn--primary sft-about-btn--light" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact', 'safestore-minimal' ); ?></a>
						<?php echo safestore_wa_cta_link( $wa_href, $phone, 'sft-about-btn sft-about-btn--ghost sft-about-btn--light' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</div>
			</div>
		</aside>
	</main>
	<?php
endwhile;

get_footer();

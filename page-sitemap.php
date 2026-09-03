<?php
/**
 * Template Name: Sitemap
 *
 * @package safestore-minimal
 */

get_header();

$legal_url    = home_url( '/legal/' );
$contact_url  = home_url( '/contact/' );
$wa_href      = 'https://wa.me/8801811892291';
$phone        = '+880 1811-892291';
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

		<?php safestore_render_page_cta(); ?>
	</main>
	<?php
endwhile;

get_footer();

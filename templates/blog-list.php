<?php
/**
 * Blog list body shared by home.php, archive.php and search.php:
 * hero, category chips, card grid, pagination, empty state.
 *
 * Args: 'title', 'lede', 'eyebrow', 'empty_title', 'empty_text', 'lead' (bool).
 *
 * @package safestore-minimal
 */

$a = wp_parse_args(
	$args,
	array(
		'title'       => safestore_blog_title(),
		'lede'        => '',
		'eyebrow'     => '',
		'empty_title' => __( 'No articles yet', 'safestore-minimal' ),
		'empty_text'  => __( 'Our team is writing practical guides on safety shoes, helmets, gloves and workplace PPE in Bangladesh. Check back soon — meanwhile, browse the shop or ask us on WhatsApp.', 'safestore-minimal' ),
		'lead'        => false,
	)
);

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
?>
<main class="sft-blog" id="main-content">
	<section class="sft-about-hero sft-blog-hero" aria-labelledby="sft-blog-title">
		<div class="sft-blog-hero__inner">
			<?php safestore_blog_breadcrumb(); ?>
			<?php if ( '' !== $a['eyebrow'] ) : ?>
				<p class="sft-blog-hero__eyebrow"><?php echo esc_html( $a['eyebrow'] ); ?></p>
			<?php endif; ?>
			<h1 class="sft-about-title" id="sft-blog-title"><?php echo esc_html( $a['title'] ); ?></h1>
			<?php if ( '' !== $a['lede'] ) : ?>
				<p class="sft-about-lede"><?php echo wp_kses_post( $a['lede'] ); ?></p>
			<?php endif; ?>
			<?php safestore_blog_search_form(); ?>
		</div>
	</section>

	<div class="sft-blog-wrap">
		<?php safestore_blog_category_nav(); ?>

		<?php if ( have_posts() ) : ?>
			<div class="sft-blog-grid">
				<?php
				$i = 0;
				while ( have_posts() ) :
					the_post();
					$is_lead = $a['lead'] && 0 === $i;
					get_template_part( 'templates/blog-card', null, array( 'featured' => $is_lead ) );
					++$i;
				endwhile;
				?>
			</div>
			<?php safestore_blog_pagination(); ?>
		<?php else : ?>
			<div class="sft-blog-empty">
				<span class="sft-blog-empty__icon" aria-hidden="true">
					<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M4 4h12l4 4v12H4z"/><path d="M8 10h8M8 14h8M8 18h5"/></svg>
				</span>
				<h2><?php echo esc_html( $a['empty_title'] ); ?></h2>
				<p><?php echo esc_html( $a['empty_text'] ); ?></p>
				<div class="sft-blog-empty__actions">
					<a class="sft-about-btn sft-about-btn--primary" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop safety gear', 'safestore-minimal' ); ?></a>
					<?php if ( ! is_home() ) : ?>
						<a class="sft-about-btn sft-blog-btn--outline" href="<?php echo esc_url( safestore_blog_url() ); ?>"><?php esc_html_e( 'All articles', 'safestore-minimal' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<?php
	if ( function_exists( 'safestore_render_page_cta' ) ) {
		safestore_render_page_cta(
			array(
				'title' => __( 'Need help choosing PPE?', 'safestore-minimal' ),
				'text'  => __( 'Tell us the job and the site — we will suggest the right gear and send a quote.', 'safestore-minimal' ),
			)
		);
	}
	?>
</main>

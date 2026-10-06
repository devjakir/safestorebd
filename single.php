<?php
/**
 * Single blog post.
 * (Single products use woocommerce.php; pages use page templates.)
 *
 * @package safestore-minimal
 */

get_header();

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

while ( have_posts() ) :
	the_post();
	$author_id = (int) get_the_author_meta( 'ID' );
	?>
	<div class="sft-blog-progress" aria-hidden="true"><span></span></div>

	<main class="sft-blog sft-blog-single" id="main-content">
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'sft-blog-article' ); ?>>
			<header class="sft-about-hero sft-blog-hero sft-blog-hero--post">
				<div class="sft-blog-hero__inner sft-blog-hero__inner--post">
					<?php safestore_blog_breadcrumb(); ?>
					<div class="sft-blog-hero__pills"><?php safestore_blog_category_pill(); ?></div>
					<h1 class="sft-about-title sft-blog-post-title"><?php the_title(); ?></h1>
					<?php if ( has_excerpt() ) : ?>
						<p class="sft-about-lede"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
					<div class="sft-blog-byline">
						<?php echo get_avatar( $author_id, 40, '', '', array( 'class' => 'sft-blog-byline__avatar' ) ); ?>
						<div>
							<span class="sft-blog-byline__name"><?php the_author(); ?></span>
							<?php safestore_blog_meta(); ?>
						</div>
					</div>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="sft-blog-cover">
					<?php
					the_post_thumbnail(
						'large',
						array(
							'class'         => 'sft-blog-cover__img',
							'loading'       => 'eager',
							'fetchpriority' => 'high',
							'sizes'         => '(max-width: 960px) 100vw, 960px',
						)
					);
					$caption = get_the_post_thumbnail_caption();
					if ( $caption ) :
						?>
						<figcaption><?php echo esc_html( $caption ); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endif; ?>

			<div class="sft-blog-body">
				<div class="sft-blog-content entry-content">
					<?php
					the_content();
					wp_link_pages(
						array(
							'before' => '<nav class="sft-blog-pages" aria-label="' . esc_attr__( 'Article pages', 'safestore-minimal' ) . '">',
							'after'  => '</nav>',
						)
					);
					?>
				</div>

				<footer class="sft-blog-article__foot">
					<?php
					$tags = get_the_tags();
					if ( $tags ) :
						?>
						<ul class="sft-blog-tags" aria-label="<?php esc_attr_e( 'Topics', 'safestore-minimal' ); ?>">
							<?php foreach ( $tags as $tag ) : ?>
								<li><a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>">#<?php echo esc_html( $tag->name ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php safestore_blog_share(); ?>

					<?php
					$bio = get_the_author_meta( 'description', $author_id );
					if ( $bio ) :
						?>
						<div class="sft-blog-author">
							<?php echo get_avatar( $author_id, 64, '', '', array( 'class' => 'sft-blog-author__avatar' ) ); ?>
							<div>
								<p class="sft-blog-author__label"><?php esc_html_e( 'Written by', 'safestore-minimal' ); ?></p>
								<p class="sft-blog-author__name"><a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>"><?php the_author(); ?></a></p>
								<p class="sft-blog-author__bio"><?php echo esc_html( $bio ); ?></p>
							</div>
						</div>
					<?php endif; ?>
				</footer>
			</div>
		</article>

		<?php
		$prev = get_previous_post();
		$next = get_next_post();
		if ( $prev || $next ) :
			?>
			<nav class="sft-blog-adjacent" aria-label="<?php esc_attr_e( 'More articles', 'safestore-minimal' ); ?>">
				<?php if ( $prev ) : ?>
					<a class="sft-blog-adjacent__link sft-blog-adjacent__link--prev" href="<?php echo esc_url( get_permalink( $prev ) ); ?>">
						<span class="sft-blog-adjacent__label"><?php esc_html_e( '← Previous', 'safestore-minimal' ); ?></span>
						<span class="sft-blog-adjacent__title"><?php echo esc_html( get_the_title( $prev ) ); ?></span>
					</a>
				<?php endif; ?>
				<?php if ( $next ) : ?>
					<a class="sft-blog-adjacent__link sft-blog-adjacent__link--next" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
						<span class="sft-blog-adjacent__label"><?php esc_html_e( 'Next →', 'safestore-minimal' ); ?></span>
						<span class="sft-blog-adjacent__title"><?php echo esc_html( get_the_title( $next ) ); ?></span>
					</a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>

		<?php if ( shortcode_exists( 'products' ) ) : ?>
			<section class="sft-blog-section sft-blog-shop" aria-labelledby="sft-blog-shop-title">
				<div class="sft-blog-section__head">
					<h2 class="sft-blog-section__title" id="sft-blog-shop-title"><?php esc_html_e( 'Shop the gear', 'safestore-minimal' ); ?></h2>
					<a class="sft-blog-section__link" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'View all', 'safestore-minimal' ); ?> <span aria-hidden="true">→</span></a>
				</div>
				<div class="sft-shop sft-blog-shop__grid">
					<?php echo do_shortcode( '[products limit="4" columns="4" orderby="popularity"]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>
		<?php endif; ?>

		<?php
		$related = safestore_blog_related_posts( 3 );
		if ( $related ) :
			?>
			<section class="sft-blog-section" aria-labelledby="sft-blog-related-title">
				<div class="sft-blog-section__head">
					<h2 class="sft-blog-section__title" id="sft-blog-related-title"><?php esc_html_e( 'Keep reading', 'safestore-minimal' ); ?></h2>
					<a class="sft-blog-section__link" href="<?php echo esc_url( safestore_blog_url() ); ?>"><?php esc_html_e( 'All articles', 'safestore-minimal' ); ?> <span aria-hidden="true">→</span></a>
				</div>
				<div class="sft-blog-grid">
					<?php
					global $post;
					foreach ( $related as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride
						setup_postdata( $post );
						get_template_part( 'templates/blog-card' );
					endforeach;
					wp_reset_postdata();
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( comments_open() || get_comments_number() ) : ?>
			<section class="sft-blog-section sft-blog-comments-wrap">
				<?php comments_template(); ?>
			</section>
		<?php endif; ?>

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
	<?php
endwhile;

get_footer();

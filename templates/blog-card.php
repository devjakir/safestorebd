<?php
/**
 * Blog card (index, archives, search, related posts).
 *
 * Args: 'featured' (bool) — wide lead card on the first blog page.
 *
 * @package safestore-minimal
 */

$featured = ! empty( $args['featured'] );
?>
<article <?php post_class( 'sft-blog-card' . ( $featured ? ' sft-blog-card--featured' : '' ) ); ?>>
	<a class="sft-blog-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php
			the_post_thumbnail(
				$featured ? 'large' : 'medium_large',
				array(
					'class'   => 'sft-blog-card__img',
					'loading' => $featured ? 'eager' : 'lazy',
					'sizes'   => $featured ? '(max-width: 900px) 100vw, 720px' : '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 400px',
					'alt'     => '',
				)
			);
			?>
		<?php else : ?>
			<span class="sft-blog-card__placeholder">
				<svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 3 4 6v6c0 5 3.4 8.4 8 9 4.6-.6 8-4 8-9V6l-8-3z"/><path d="m9 12 2 2 4-4"/></svg>
			</span>
		<?php endif; ?>
	</a>
	<div class="sft-blog-card__body">
		<div class="sft-blog-card__top">
			<?php safestore_blog_category_pill(); ?>
			<?php if ( is_sticky() && $featured ) : ?>
				<span class="sft-blog-pill sft-blog-pill--hot"><?php esc_html_e( 'Featured', 'safestore-minimal' ); ?></span>
			<?php endif; ?>
		</div>
		<?php
		$heading = $featured ? 'h2' : 'h3';
		printf(
			'<%1$s class="sft-blog-card__title"><a href="%2$s">%3$s</a></%1$s>',
			esc_attr( $heading ),
			esc_url( get_permalink() ),
			esc_html( get_the_title() ? get_the_title() : __( '(Untitled)', 'safestore-minimal' ) )
		);
		?>
		<p class="sft-blog-card__excerpt"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
		<div class="sft-blog-card__foot">
			<?php safestore_blog_meta(); ?>
			<a class="sft-blog-card__more" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: post title */ __( 'Read: %s', 'safestore-minimal' ), wp_strip_all_tags( get_the_title() ) ) ); ?>"><?php esc_html_e( 'Read', 'safestore-minimal' ); ?> <span aria-hidden="true">→</span></a>
		</div>
	</div>
</article>

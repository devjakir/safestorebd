<?php
/**
 * Blog card (index, archives, search, related posts).
 *
 * One column card per post: cover with the category pill on it, the
 * date and reading time ruled off above the title, excerpt, read link.
 *
 * @package safestore-minimal
 */

?>
<article <?php post_class( 'sft-blog-card' ); ?>>
	<div class="sft-blog-card__media">
		<a class="sft-blog-card__cover" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php
				the_post_thumbnail(
					'medium_large',
					array(
						'class'   => 'sft-blog-card__img',
						'loading' => 'lazy',
						'sizes'   => '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 400px',
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
		<div class="sft-blog-card__tags">
			<?php safestore_blog_category_pill(); ?>
			<?php if ( is_sticky() && ( is_home() || is_front_page() ) ) : ?>
				<span class="sft-blog-pill sft-blog-pill--hot"><?php esc_html_e( 'Featured', 'safestore-minimal' ); ?></span>
			<?php endif; ?>
		</div>
	</div>
	<div class="sft-blog-card__body">
		<?php safestore_blog_card_meta(); ?>
		<h3 class="sft-blog-card__title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ? get_the_title() : __( '(Untitled)', 'safestore-minimal' ) ); ?></a></h3>
		<p class="sft-blog-card__excerpt"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
		<a class="sft-blog-card__more" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: post title */ __( 'Read more: %s', 'safestore-minimal' ), wp_strip_all_tags( get_the_title() ) ) ); ?>">
			<?php esc_html_e( 'Read More', 'safestore-minimal' ); ?>
			<span aria-hidden="true">&rarr;</span>
		</a>
	</div>
</article>

<?php
/**
 * Comments for blog posts. (Product reviews use woocommerce/single-product-reviews.php.)
 *
 * @package safestore-minimal
 */

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="sft-blog-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="sft-blog-section__title">
			<?php
			$count = (int) get_comments_number();
			/* translators: %d: number of comments */
			echo esc_html( sprintf( _n( '%d comment', '%d comments', $count, 'safestore-minimal' ), $count ) );
			?>
		</h2>
		<ol class="sft-blog-comments__list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 40,
				)
			);
			?>
		</ol>
		<?php
		the_comments_navigation(
			array(
				'prev_text' => __( '← Older comments', 'safestore-minimal' ),
				'next_text' => __( 'Newer comments →', 'safestore-minimal' ),
			)
		);
		?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="sft-blog-comments__closed"><?php esc_html_e( 'Comments are closed.', 'safestore-minimal' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'title_reply'          => __( 'Leave a comment', 'safestore-minimal' ),
			'title_reply_before'   => '<h2 id="reply-title" class="sft-blog-section__title">',
			'title_reply_after'    => '</h2>',
			'class_submit'         => 'sft-about-btn sft-about-btn--primary',
			'comment_notes_before' => '<p class="comment-notes">' . esc_html__( 'Your email will not be published. Comments are reviewed before they appear.', 'safestore-minimal' ) . '</p>',
		)
	);
	?>
</div>

<?php
/**
 * Search results.
 *
 * Article search (post_type=post, from the blog search box) gets the blog
 * layout. Every other search keeps the theme's existing behaviour (the
 * header search is product-only and is routed by WooCommerce).
 *
 * @package safestore-minimal
 */

if ( 'post' !== get_query_var( 'post_type' ) ) {
	require get_template_directory() . '/index.php';
	return;
}

get_header();

global $wp_query;
$count = (int) $wp_query->found_posts;

get_template_part(
	'templates/blog-list',
	null,
	array(
		/* translators: %s: search query */
		'title'       => sprintf( __( 'Results for “%s”', 'safestore-minimal' ), get_search_query() ),
		'eyebrow'     => __( 'Article search', 'safestore-minimal' ),
		/* translators: %d: number of results */
		'lede'        => sprintf( _n( '%d article found.', '%d articles found.', $count, 'safestore-minimal' ), $count ),
		'empty_title' => __( 'No matching articles', 'safestore-minimal' ),
		'empty_text'  => __( 'Try a shorter word like “helmet” or “gloves”, or browse all articles.', 'safestore-minimal' ),
	)
);

get_footer();

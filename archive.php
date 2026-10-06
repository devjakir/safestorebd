<?php
/**
 * Post archives: category, tag, author, date.
 * (WooCommerce product archives use woocommerce.php.)
 *
 * @package safestore-minimal
 */

get_header();

$description = get_the_archive_description();
$eyebrow     = '';
if ( is_category() ) {
	$eyebrow = __( 'Category', 'safestore-minimal' );
} elseif ( is_tag() ) {
	$eyebrow = __( 'Topic', 'safestore-minimal' );
} elseif ( is_author() ) {
	$eyebrow = __( 'Author', 'safestore-minimal' );
} elseif ( is_date() ) {
	$eyebrow = __( 'Archive', 'safestore-minimal' );
}

get_template_part(
	'templates/blog-list',
	null,
	array(
		'title'       => wp_strip_all_tags( get_the_archive_title() ),
		'eyebrow'     => $eyebrow,
		'lede'        => $description ? wp_strip_all_tags( $description ) : '',
		'empty_title' => __( 'Nothing here yet', 'safestore-minimal' ),
		'empty_text'  => __( 'There are no articles in this section yet. See all articles or browse the shop.', 'safestore-minimal' ),
	)
);

get_footer();

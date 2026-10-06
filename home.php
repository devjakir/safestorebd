<?php
/**
 * Blog index (Settings → Reading → Posts page, /blog/).
 *
 * @package safestore-minimal
 */

get_header();

$page_id = (int) get_option( 'page_for_posts' );
$intro   = $page_id ? get_post_field( 'post_excerpt', $page_id ) : '';

get_template_part(
	'templates/blog-list',
	null,
	array(
		'title'   => safestore_blog_title(),
		'eyebrow' => __( 'SafeStoreBD Journal', 'safestore-minimal' ),
		'lede'    => '' !== $intro ? $intro : __( 'Practical guides on choosing and using safety shoes, helmets, gloves and PPE — written for factories, construction sites and warehouses in Bangladesh.', 'safestore-minimal' ),
		'lead'    => true,
	)
);

get_footer();

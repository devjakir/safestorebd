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
		'title'   => __( 'Safety & PPE Guides', 'safestore-minimal' ),
		'eyebrow' => __( 'SafeStoreBD Journal', 'safestore-minimal' ),
		'lede'    => '' !== $intro ? $intro : __( 'Practical guides on safety shoes, helmets, gloves and PPE — helping workers and businesses make better choices for factories, construction sites, warehouses and industrial workplaces in Bangladesh.', 'safestore-minimal' ),
	)
);

get_footer();

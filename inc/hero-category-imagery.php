<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Homepage hero artwork: one theme-shipped group shot per slide
 * (assets/images/hero/hero-{key}-{480,800,1200}w.webp, 5:3 transparent cutouts
 * with a soft amber glow for the dark slide). No product price or rating is
 * shown on the hero.
 */

/** Intrinsic size of the largest file; every width shares the 5:3 ratio. */
const SAFESTORE_HERO_IMAGE_W = 1200;
const SAFESTORE_HERO_IMAGE_H = 720;

/**
 * Rendered width of the hero stage (css/page-home.css):
 * phones min(88vw, 360px), ≤900px min(76vw, 460px), desktop up to ~640px.
 */
const SAFESTORE_HERO_IMAGE_SIZES = '(max-width: 480px) 88vw, (max-width: 900px) 460px, 640px';

/**
 * Hero image for a slide key ('safety-helmets', 'safety-vests', 'safety-shoes').
 */
function safestore_hero_slide_image( $key, $alt ) {
	$base = get_template_directory_uri() . '/assets/images/hero/hero-' . sanitize_key( (string) $key );

	return array(
		'src'    => $base . '-800w.webp',
		'srcset' => $base . '-480w.webp 480w, ' . $base . '-800w.webp 800w, ' . $base . '-1200w.webp 1200w',
		'sizes'  => SAFESTORE_HERO_IMAGE_SIZES,
		'alt'    => (string) $alt,
		'width'  => SAFESTORE_HERO_IMAGE_W,
		'height' => SAFESTORE_HERO_IMAGE_H,
	);
}

/**
 * Image config of the first (LCP) slide. The template and the <head> preload
 * both read it from here so the preloaded file can never differ from the rendered one.
 */
function safestore_hero_first_slide_config() {
	return array(
		'image' => 'safety-helmets',
		'alt'   => 'Safety helmets in white, red, green and yellow',
	);
}

/** Image of the first (LCP) hero slide — used by the template and by the preload. */
function safestore_hero_first_slide_image() {
	$cfg = safestore_hero_first_slide_config();
	return safestore_hero_slide_image( $cfg['image'], $cfg['alt'] );
}

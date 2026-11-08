<?php
/**
 * Order landings — document head, skip link and minimal header (logo, call, WhatsApp).
 *
 * get_template_part( 'templates/landing/header', null, array( 'wa' => …, 'main' => … ) )
 *   wa   WhatsApp href for the header button (pre-filled order text). Default: plain link.
 *   main id of <main>, target of the skip link.
 * Call safestore_pl_head() (title, robots, preload) before this — it hooks wp_head.
 */

defined( 'ABSPATH' ) || exit;

$lp_c    = safestore_lp_contact();
$lp_wa   = ! empty( $args['wa'] ) ? $args['wa'] : $lp_c['wa'];
$lp_main = ! empty( $args['main'] ) ? $args['main'] : 'lp-main';
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="lp-skip" href="#<?php echo esc_attr( $lp_main ); ?>">মূল কনটেন্টে যান</a>

<header class="lp-header" role="banner">
	<div class="lp-wrap lp-header__in">
		<a class="lp-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — হোম">
			<img src="<?php echo esc_url( $lp_c['logo']['url'] ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="<?php echo (int) $lp_c['logo']['width']; ?>" height="<?php echo (int) $lp_c['logo']['height']; ?>" decoding="async">
		</a>
		<div class="lp-header__acts">
			<a class="lp-header__tel" href="<?php echo esc_url( 'tel:' . $lp_c['tel'] ); ?>" data-spl-loc="header" aria-label="<?php echo esc_attr( 'কল করুন: ' . $lp_c['tel_label'] ); ?>">
				<?php echo safestore_pl_icon( 'phone', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="lp-header__num" aria-hidden="true"><?php echo esc_html( $lp_c['tel_label'] ); ?></span>
			</a>
			<a class="lp-btn lp-btn--wa lp-btn--sm" href="<?php echo esc_url( $lp_wa ); ?>" target="_blank" rel="noopener noreferrer" data-spl-loc="header" data-spl-wa>
				<?php echo $lp_c['wa_icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span>WhatsApp</span>
			</a>
		</div>
	</div>
</header>

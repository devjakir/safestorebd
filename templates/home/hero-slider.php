<?php

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

/** UTF-8 narrow no-break space — keeps two-line headline layout stable */
$nbsp = "\xC2\xA0";

$helmet_cat    = safestore_home_category_url( 'helmet' );
$vest_cat      = safestore_home_category_url( 'vest' );
$shoe_cat      = safestore_home_category_url( 'shoe' );
$browse_anchor = home_url( '/#browse-categories' );

// First slide = LCP image; inc/hero-category-imagery.php owns its config so the <head> preload matches.
$hero_first = safestore_hero_first_slide_config();

$hero_slides = array(
	array(
		'badge'         => 'Head protection',
		'title'         => 'Safety' . $nbsp . 'Helmets',
		'title_accent'  => '&' . $nbsp . 'Hard' . $nbsp . 'Hats',
		'text'          => 'Certified helmets and hard hats for construction, plant, and logistics crews.',
		'cta'           => 'Shop Hard Hats',
		'url'           => $helmet_cat,
		'cta_secondary' => 'Browse categories',
		'url_secondary' => $browse_anchor,
		'category_slug' => $hero_first['category_slug'],
		'image_base'    => $hero_first['image_base'],
		'alt'           => $hero_first['alt'],
	),
	array(
		'badge'         => 'High visibility',
		'title'         => 'Hi-Vis',
		'title_accent'  => 'Safety Vest',
		'text'          => 'Hi-vis vests for roads, warehouses, and yards.',
		'cta'           => 'Shop Safety Vests',
		'url'           => $vest_cat,
		'cta_secondary' => 'Explore PPE',
		'url_secondary' => $browse_anchor,
		'category_slug' => safestore_home_category_slug( 'vest' ),
		'image_base'    => 'sf-safety-vest',
		'alt'           => 'High visibility safety vests in yellow and orange for industrial and warehouse use',
	),
	array(
		'badge'         => 'Foot protection',
		'title'         => 'Industrial',
		'title_accent'  => 'Safety Shoes',
		'text'          => 'Leather work boots for oil, grit, and long shifts.',
		'cta'           => 'Shop Safety Shoes',
		'url'           => $shoe_cat,
		'cta_secondary' => 'Shop now',
		'url_secondary' => $shop_url,
		'category_slug' => safestore_home_category_slug( 'shoe' ),
		'image_base'    => 'sf-category-shoe',
		'alt'           => 'Industrial safety shoes and leather work boots on a construction-site surface',
	),
);

$hero_proof = array(
	array(
		'icon' => 'check',
		'html' => __( '<strong>10,000+</strong> orders delivered', 'safestore-minimal' ),
	),
	array(
		'icon' => 'pay',
		'html' => __( '<strong>Cash on delivery</strong> · bKash · Nagad', 'safestore-minimal' ),
	),
);

$safestore_hero_proof_icon = static function ( string $name ): string {
	$paths = array(
		'check' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.2 2.3 2.3 4.7-5"/>',
		'pay'   => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><path d="M7 15h3"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return '<svg class="hero-slide-proof__icon hero-slide-proof__icon--' . esc_attr( $name ) . '" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
};
?>

<section class="hero-slider" aria-label="<?php esc_attr_e( 'Featured safety products', 'safestore-minimal' ); ?>">
	<?php
	// The page's single H1 (site value prop, echoes the title tag). It lives
	// outside the rotating slides so it is always visible and never inside a
	// visibility:hidden slide; slide titles stay H2.
	?>
	<h1 class="hero-site-h1"><?php esc_html_e( 'Industrial Safety Equipment & PPE Store in Bangladesh', 'safestore-minimal' ); ?></h1>
	<div class="hero-slider-viewport" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Featured safety products', 'safestore-minimal' ); ?>">
		<?php foreach ( $hero_slides as $index => $slide ) : ?>
			<?php
			$overlay    = ( ! empty( $slide['category_slug'] ) && function_exists( 'safestore_hero_overlay_for_category' ) )
				? safestore_hero_overlay_for_category( $slide['category_slug'] )
				: array();
			$overlay_html = function_exists( 'safestore_hero_overlay_markup' )
				? safestore_hero_overlay_markup( $overlay )
				: '';

			// Same resolver the LCP preload uses (inc/hero-category-imagery.php).
			$image      = safestore_hero_slide_image( (string) $slide['category_slug'], $slide['image_base'], $slide['alt'] );
			// Opaque product photos get framed as a white card; cutouts float on the dark slide.
			$stage_class = ( $image['is_overlay'] && empty( $image['cutout'] ) ) ? ' hero-slide-stage--photo' : '';
			$img_src    = $image['src'];
			$img_srcset = $image['srcset'];
			$img_sizes  = $image['sizes'];
			$img_alt    = $image['alt'];
			$img_w      = $image['width'];
			$img_h      = $image['height'];
			$is_first   = ( 0 === $index );
			?>
			<div class="hero-slide<?php echo $is_first ? ' is-active' : ''; ?>"
				role="group"
				aria-roledescription="slide"
				aria-label="<?php echo esc_attr( sprintf( '%d of %d', $index + 1, count( $hero_slides ) ) ); ?>"
				data-slide="<?php echo (int) $index; ?>"
				<?php echo ! $is_first ? 'aria-hidden="true"' : ''; ?>>
				<div class="hero-slide-content">
					<span class="hero-slide-badge">
						<span class="hero-slide-badge-star" aria-hidden="true">&#9733;</span>
						<?php echo wp_kses_post( $slide['badge'] ); ?>
					</span>
					<h2 class="hero-slide-title">
						<span class="hero-slide-title-line"><?php echo esc_html( $slide['title'] ); ?></span>
						<?php if ( ! empty( $slide['title_accent'] ) ) : ?>
							<span class="hero-slide-title-accent"><?php echo esc_html( $slide['title_accent'] ); ?></span>
						<?php endif; ?>
					</h2>
					<p class="hero-slide-text"><?php echo wp_kses_post( $slide['text'] ); ?></p>
					<div class="hero-slide-actions">
						<a class="hero-slide-cta hero-slide-cta--primary" href="<?php echo esc_url( $slide['url'] ); ?>">
							<?php echo esc_html( $slide['cta'] ); ?>
							<span class="hero-slide-cta-arrow" aria-hidden="true">&rarr;</span>
						</a>
						<?php if ( ! empty( $slide['cta_secondary'] ) ) : ?>
							<a class="hero-slide-cta hero-slide-cta--secondary" href="<?php echo esc_url( $slide['url_secondary'] ); ?>">
								<?php echo esc_html( $slide['cta_secondary'] ); ?>
							</a>
						<?php endif; ?>
					</div>
					<ul class="hero-slide-proof" aria-label="<?php esc_attr_e( 'Why shop with SafeStoreBD', 'safestore-minimal' ); ?>">
						<?php foreach ( $hero_proof as $proof ) : ?>
							<li class="hero-slide-proof__item">
								<?php echo $safestore_hero_proof_icon( $proof['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span class="hero-slide-proof__text"><?php echo wp_kses( $proof['html'], array( 'strong' => array() ) ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div class="hero-slide-media">
					<div class="hero-slide-stage<?php echo esc_attr( $stage_class ); ?>">
						<?php if ( $is_first ) : ?>
							<img class="hero-slide-product"
								src="<?php echo esc_url( $img_src ); ?>"
								<?php echo '' !== $img_srcset ? 'srcset="' . esc_attr( $img_srcset ) . '"' : ''; ?>
								sizes="<?php echo esc_attr( $img_sizes ); ?>"
								alt="<?php echo esc_attr( $img_alt ); ?>"
								width="<?php echo esc_attr( (string) $img_w ); ?>"
								height="<?php echo esc_attr( (string) $img_h ); ?>"
								loading="eager"
								decoding="async"
								fetchpriority="high">
						<?php else : ?>
							<img class="hero-slide-product"
								src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
								data-src="<?php echo esc_url( $img_src ); ?>"
								<?php echo '' !== $img_srcset ? 'data-srcset="' . esc_attr( $img_srcset ) . '"' : ''; ?>
								data-sizes="<?php echo esc_attr( $img_sizes ); ?>"
								alt="<?php echo esc_attr( $img_alt ); ?>"
								width="<?php echo esc_attr( (string) $img_w ); ?>"
								height="<?php echo esc_attr( (string) $img_h ); ?>"
								loading="lazy"
								decoding="async">
						<?php endif; ?>
						<?php echo $overlay_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>

		<div class="hero-slider-dots" role="group" aria-label="<?php esc_attr_e( 'Select slide', 'safestore-minimal' ); ?>">
			<?php foreach ( $hero_slides as $index => $slide ) : ?>
				<button type="button"
					class="hero-dot<?php echo 0 === $index ? ' is-active' : ''; ?>"
					data-slide="<?php echo (int) $index; ?>"
					<?php echo 0 === $index ? 'aria-current="true"' : ''; ?>
					aria-label="<?php echo esc_attr( sprintf( 'Go to slide %d', $index + 1 ) ); ?>"></button>
			<?php endforeach; ?>
		</div>
	</div>
</section>

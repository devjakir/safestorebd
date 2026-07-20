<?php
/**
 * Home — Hero slider (3 slides, one hero product image each)
 */

$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$assets_url  = get_template_directory_uri() . '/assets/images';

$safestore_hero_product_src = static function ( string $filename ) use ( $assets_url ): string {
	return $assets_url . '/' . rawurlencode( $filename );
};

/** UTF-8 narrow no-break space — keeps two-line headline layout stable */
$nbsp = "\xC2\xA0";

$helmet_cat    = home_url( '/product-category/protective-helmets/' );
$vest_cat    = home_url( '/product-category/safety-vests/' );
$shoe_cat    = home_url( '/product-category/safety-shoes/' );
$browse_anchor = home_url( '/#browse-categories' );

$hero_slides = array(
	array(
		'badge'         => 'Head protection',
		'title'         => 'Safety' . $nbsp . 'Helmets',
		'title_accent'  => '&' . $nbsp . 'Hard' . $nbsp . 'Hats',
		'text'          => 'Certified helmets and hard hats for construction, plant, and logistics crews — trusted brands in stock.',
		'price_old'     => '',
		'price_new'     => '',
		'discount'      => '',
		'cta'           => 'Shop Hard Hats',
		'url'           => $helmet_cat,
		'cta_secondary' => 'Browse categories',
		'url_secondary' => $browse_anchor,
		'cert_badge'    => '',
		'reviews'       => '',
		'image'         => $safestore_hero_product_src( 'sf-helmet-category.webp' ),
		'alt'           => 'Safety helmets and hard hats with protective eyewear on a bright surface',
	),
	array(
		'badge'         => 'High visibility',
		'title'         => 'Hi-Vis',
		'title_accent'  => 'Safety Vest',
		'text'          => 'Hi-vis vests for roads, warehouses, and yards. Bright reflective tape keeps crews visible in low light.',
		'price_old'     => '',
		'price_new'     => '',
		'discount'      => '',
		'cta'           => 'Shop Safety Vests',
		'url'           => $vest_cat,
		'cta_secondary' => 'Explore PPE',
		'url_secondary' => $browse_anchor,
		'cert_badge'    => '',
		'reviews'       => '',
		'image'         => $safestore_hero_product_src( 'sf-safety-vest.webp' ),
		'alt'           => 'High visibility safety vests in yellow and orange for industrial and warehouse use',
	),
	array(
		'badge'         => 'Foot protection',
		'title'         => 'Industrial',
		'title_accent'  => 'Safety Shoes',
		'text'          => 'Leather work boots for oil, grit, and long shifts — slip-resistant soles and dependable toe protection.',
		'price_old'     => '',
		'price_new'     => '',
		'discount'      => '',
		'cta'           => 'Shop Safety Shoes',
		'url'           => $shoe_cat,
		'cta_secondary' => 'Shop now',
		'url_secondary' => $shop_url,
		'cert_badge'    => '',
		'reviews'       => '',
		'image'         => $safestore_hero_product_src( 'sf-category-shoe.webp' ),
		'alt'           => 'Industrial safety shoes and leather work boots on a construction-site surface',
	),
);
?>

<section class="hero-slider" aria-roledescription="carousel" aria-label="Featured safety products">
	<div class="hero-slider-viewport">
		<?php foreach ( $hero_slides as $index => $slide ) : ?>
			<article class="hero-slide<?php echo 0 === $index ? ' is-active' : ''; ?>"
				role="group"
				aria-roledescription="slide"
				aria-label="<?php echo esc_attr( sprintf( '%d of %d', $index + 1, count( $hero_slides ) ) ); ?>"
				data-slide="<?php echo (int) $index; ?>"
				<?php echo 0 !== $index ? 'aria-hidden="true"' : ''; ?>>
				<div class="hero-slide-content">
					<span class="hero-slide-badge">
						<span class="hero-slide-badge-star" aria-hidden="true">&#9733;</span>
						<?php echo wp_kses_post( $slide['badge'] ); ?>
					</span>
					<?php if ( 0 === $index ) : ?>
						<h1 class="hero-slide-title">
							<span class="hero-slide-title-line"><?php echo esc_html( $slide['title'] ); ?></span>
							<?php if ( ! empty( $slide['title_accent'] ) ) : ?>
								<span class="hero-slide-title-accent"><?php echo esc_html( $slide['title_accent'] ); ?></span>
							<?php endif; ?>
						</h1>
					<?php else : ?>
						<h2 class="hero-slide-title">
							<span class="hero-slide-title-line"><?php echo esc_html( $slide['title'] ); ?></span>
							<?php if ( ! empty( $slide['title_accent'] ) ) : ?>
								<span class="hero-slide-title-accent"><?php echo esc_html( $slide['title_accent'] ); ?></span>
							<?php endif; ?>
						</h2>
					<?php endif; ?>
					<p class="hero-slide-text"><?php echo wp_kses_post( $slide['text'] ); ?></p>
					<?php if ( ! empty( $slide['price_new'] ) ) : ?>
						<div class="hero-slide-price">
							<?php if ( ! empty( $slide['price_old'] ) ) : ?>
								<span class="hero-slide-price-old"><?php echo esc_html( $slide['price_old'] ); ?></span>
							<?php endif; ?>
							<span class="hero-slide-price-new"><?php echo esc_html( $slide['price_new'] ); ?></span>
							<?php if ( ! empty( $slide['discount'] ) ) : ?>
								<span class="hero-slide-price-discount"><?php echo esc_html( $slide['discount'] ); ?></span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
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
				</div>
				<div class="hero-slide-media">
					<span class="hero-slide-glow" aria-hidden="true"></span>
					<img class="hero-slide-product"
						src="<?php echo esc_url( $slide['image'] ); ?>"
						alt="<?php echo esc_attr( $slide['alt'] ); ?>"
						width="900"
						height="900"
						loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>"
						decoding="async"
						<?php echo 0 === $index ? 'fetchpriority="high"' : ''; ?>>
					<?php if ( ! empty( $slide['cert_badge'] ) ) : ?>
						<span class="hero-slide-spec hero-slide-spec--cert"><?php echo wp_kses_post( $slide['cert_badge'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $slide['reviews'] ) ) : ?>
						<span class="hero-slide-spec hero-slide-spec--reviews"><?php echo wp_kses_post( $slide['reviews'] ); ?></span>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>

		<div class="hero-slider-dots" role="tablist" aria-label="Select slide">
			<?php foreach ( $hero_slides as $index => $slide ) : ?>
				<button type="button"
					class="hero-dot<?php echo 0 === $index ? ' is-active' : ''; ?>"
					role="tab"
					data-slide="<?php echo (int) $index; ?>"
					aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
					aria-label="<?php echo esc_attr( sprintf( 'Go to slide %d', $index + 1 ) ); ?>"></button>
			<?php endforeach; ?>
		</div>
	</div>
</section>

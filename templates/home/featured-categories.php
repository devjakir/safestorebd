<?php

$shop_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$assets_url = get_template_directory_uri() . '/assets/images';
$helmet_cat = safestore_home_category_url( 'helmet' );
$vest_cat = safestore_home_category_url( 'vest' );
$gloves_cat = safestore_home_category_url( 'glove' );
$goggle_cat = safestore_home_category_url( 'goggle' );
$shoe_cat = safestore_home_category_url( 'shoe' );

$safestore_cat_img = static function ( string $filename ) use ( $assets_url ): string {
	// Version by file mtime so browsers/CDN fetch a replaced image immediately.
	$path = get_template_directory() . '/assets/images/' . $filename;
	$ver  = file_exists( $path ) ? (string) filemtime( $path ) : '';
	$url  = $assets_url . '/' . rawurlencode( $filename );
	return '' !== $ver ? $url . '?ver=' . $ver : $url;
};

// srcset from the resized copies next to each image (name-400w.webp, -600w, -900w).
$safestore_cat_srcset = static function ( string $filename, int $full_width ) use ( $assets_url ): string {
	$dir   = get_template_directory() . '/assets/images/';
	$base  = substr( $filename, 0, -5 ); // strip ".webp"
	$parts = array();
	foreach ( array( 400, 600, 900 ) as $w ) {
		$file = $base . '-' . $w . 'w.webp';
		if ( $w < $full_width && file_exists( $dir . $file ) ) {
			$parts[] = $assets_url . '/' . rawurlencode( $file ) . '?ver=' . filemtime( $dir . $file ) . ' ' . $w . 'w';
		}
	}
	if ( file_exists( $dir . $filename ) ) {
		$parts[] = $assets_url . '/' . rawurlencode( $filename ) . '?ver=' . filemtime( $dir . $filename ) . ' ' . $full_width . 'w';
	}
	return implode( ', ', $parts );
};

/*
 * `sizes` per card shape. Cards use object-fit: cover in fixed-height rows
 * (400px desktop, 240px <=900px, 180px <=480px), so the slot can need more
 * pixels than its width — these values account for that.
 */
$safestore_cat_sizes = array(
	'xl' => '(max-width: 900px) 100vw, 680px',
	'lg' => '(max-width: 900px) 50vw, 760px',
	'md' => '(max-width: 900px) 50vw, 560px',
	'sm' => '(max-width: 900px) 50vw, 400px',
);

$categories = array(
	array(
		'size'  => 'xl',
		'tag'   => '',
		'title' => 'Industrial Safety Shoes',
		'image' => $safestore_cat_img( 'safety-shoes-category.webp' ),
		'file'  => 'safety-shoes-category.webp',
		'w'     => 1400,
		'h'     => 827,
		'fit'   => 'cover',
		'url'   => $shoe_cat,
		'alt'   => 'Industrial safety shoes in Bangladesh: TINGGU and HAOJIA steel toe work shoes, DINGGU safety boots, black leather safety shoes and J.C.D gumboots',
	),
	array(
		'size'  => 'sm',
		'tag'   => '',
		'title' => 'High-Visibility Safety Vests',
		'image' => $safestore_cat_img( 'premium_hyper_realistic_product_photography_of_a_high_visibility_safety_vest._a.webp' ),
		'file'  => 'premium_hyper_realistic_product_photography_of_a_high_visibility_safety_vest._a.webp',
		'w'     => 800,
		'h'     => 800,
		'fit'   => 'cover',
		'url'   => $vest_cat,
		'alt'   => 'High visibility reflective safety vest product on neutral background',
	),
	array(
		'size'  => 'sm',
		'tag'   => '',
		'title' => 'Industrial Safety Gloves',
		'image' => $safestore_cat_img( 'premium_hyper_realistic_product_photography_of_industrial_safety_gloves._a_pair (1).webp' ),
		'file'  => 'premium_hyper_realistic_product_photography_of_industrial_safety_gloves._a_pair (1).webp',
		'w'     => 800,
		'h'     => 800,
		'fit'   => 'cover',
		'url'   => $gloves_cat,
		'alt'   => 'Industrial safety gloves product shot for warehouse and factory use',
	),
	array(
		'size'  => 'md',
		'tag'   => '',
		'title' => 'Safety Glasses & Goggles',
		'image' => $safestore_cat_img( 'premium_hyper_realistic_product_photography_of_industrial_safety_goggles._a (3).webp' ),
		'file'  => 'premium_hyper_realistic_product_photography_of_industrial_safety_goggles._a (3).webp',
		'w'     => 1024,
		'h'     => 1024,
		'fit'   => 'cover',
		'url'   => $goggle_cat,
		'alt'   => 'Safety glasses and goggles product photo for workshop eye protection',
	),
	array(
		'size'  => 'lg',
		'tag'   => '',
		'title' => 'Safety Helmets & Hard Hats',
		'image' => $safestore_cat_img( 'industrial_safety_category-helmet.webp' ),
		'file'  => 'industrial_safety_category-helmet.webp',
		'w'     => 1264,
		'h'     => 848,
		'fit'   => 'cover',
		'url'   => $helmet_cat,
		'alt'   => 'Industrial safety helmets and hard hats category art',
	),
);
?>

<section id="browse-categories" class="featured-categories" aria-label="Shop by safety category">
	<div class="featured-categories-head">
		<div class="featured-categories-intro">
			<h2>Featured Category</h2>
			<p>Get Your Desired Product from Featured Category!</p>
		</div>
		<a class="featured-categories-view-all" href="<?php echo esc_url( $shop_url ); ?>">View all <span aria-hidden="true">&rarr;</span></a>
	</div>

	<div class="featured-categories-grid">
		<?php foreach ( $categories as $cat ) : ?>
			<?php
			$card_sizes  = isset( $safestore_cat_sizes[ $cat['size'] ] ) ? $safestore_cat_sizes[ $cat['size'] ] : $safestore_cat_sizes['sm'];
			$card_srcset = $safestore_cat_srcset( $cat['file'], (int) $cat['w'] );
			?>
			<a class="category-card category-card--<?php echo esc_attr( $cat['size'] ); ?> category-card--<?php echo esc_attr( $cat['fit'] ); ?>"
				href="<?php echo esc_url( $cat['url'] ); ?>">
				<img src="<?php echo esc_url( $cat['image'] ); ?>"
					<?php if ( '' !== $card_srcset ) : ?>srcset="<?php echo esc_attr( $card_srcset ); ?>"<?php endif; ?>
					alt="<?php echo esc_attr( $cat['alt'] ); ?>"
					width="<?php echo (int) $cat['w']; ?>"
					height="<?php echo (int) $cat['h']; ?>"
					sizes="<?php echo esc_attr( $card_sizes ); ?>"
					loading="lazy"
					decoding="async"
					fetchpriority="low">
				<div class="category-card-overlay">
					<?php if ( ! empty( $cat['tag'] ) ) : ?>
						<span class="category-card-tag"><?php echo esc_html( $cat['tag'] ); ?></span>
					<?php endif; ?>
					<h3 class="category-card-title"><?php echo esc_html( $cat['title'] ); ?></h3>
				</div>
				<?php // SVG, not the &rarr; glyph: font metrics left the glyph thin and ~2px low; this path is centred on the viewBox. ?>
				<span class="category-card-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M4 12h16M13 5l7 7-7 7"/></svg></span>
			</a>
		<?php endforeach; ?>
	</div>
</section>
<section class="sft-bestsellers" aria-label="<?php esc_attr_e( 'Featured products', 'safestore-minimal' ); ?>">
	<div class="sft-bestsellers__head">
		<div class="sft-bestsellers__intro">
			<h2 class="sft-bestsellers__title"><?php esc_html_e( 'Featured Products', 'safestore-minimal' ); ?></h2>
			<p class="sft-bestsellers__lede"><?php esc_html_e( 'The safety gear teams across Bangladesh order most.', 'safestore-minimal' ); ?></p>
		</div>
		<a class="sft-bestsellers__view-all" href="<?php echo esc_url( $shop_url ); ?>">
			<?php esc_html_e( 'View all', 'safestore-minimal' ); ?>
			<span aria-hidden="true">&rarr;</span>
		</a>
	</div>
	<div class="sft-bestsellers__grid">
		<?php
		// 12 products: full rows at 4 (desktop), 3 (tablet) and 2 columns; phones
		// show the first 8 (css/page-home.css). Same card as the shop.
		// Deterministic: WooCommerce popularity (total sales), not random.
		echo do_shortcode( '[products limit="12" columns="4" orderby="popularity"]' );
		?>
	</div>
</section>

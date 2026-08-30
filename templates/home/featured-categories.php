<?php
/**
 * Home — Featured categories (product-focused thumbnails for browsing)
 */

$shop_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$assets_url = get_template_directory_uri() . '/assets/images';
$helmet_cat = home_url( '/product-category/protective-helmets/' );
$vest_cat = home_url( '/product-category/safety-vests/' );
$gloves_cat = home_url( '/product-category/safety-gloves/' );
$goggle_cat = home_url( '/product-category/safety-goggles/' );
$shoe_cat = home_url( '/product-category/safety-shoes/' );

/**
 * @param string $filename File name as stored in assets/images (spaces & special chars OK).
 */
$safestore_cat_img = static function ( string $filename ) use ( $assets_url ): string {
	return $assets_url . '/' . rawurlencode( $filename );
};

$categories = array(
	array(
		'size'  => 'xl',
		'tag'   => '',
		'title' => 'Industrial Safety Shoes',
		'image' => $safestore_cat_img( 'premium_brown_leather_industrial_safety_boots.webp' ),
		'fit'   => 'cover',
		'url'   => $shoe_cat,
		'alt'   => 'Premium brown leather industrial safety boots and work shoes',
	),
	array(
		'size'  => 'sm',
		'tag'   => '',
		'title' => 'High-Visibility Safety Vests',
		'image' => $safestore_cat_img( 'premium_hyper_realistic_product_photography_of_a_high_visibility_safety_vest._a.webp' ),
		'fit'   => 'cover',
		'url'   => $vest_cat,
		'alt'   => 'High visibility reflective safety vest product on neutral background',
	),
	array(
		'size'  => 'sm',
		'tag'   => '',
		'title' => 'Industrial Safety Gloves',
		'image' => $safestore_cat_img( 'premium_hyper_realistic_product_photography_of_industrial_safety_gloves._a_pair (1).webp' ),
		'fit'   => 'cover',
		'url'   => $gloves_cat,
		'alt'   => 'Industrial safety gloves product shot for warehouse and factory use',
	),
	array(
		'size'  => 'md',
		'tag'   => '',
		'title' => 'Safety Glasses & Goggles',
		'image' => $safestore_cat_img( 'premium_hyper_realistic_product_photography_of_industrial_safety_goggles._a (3).webp' ),
		'fit'   => 'cover',
		'url'   => $goggle_cat,
		'alt'   => 'Safety glasses and goggles product photo for workshop eye protection',
	),
	array(
		'size'  => 'lg',
		'tag'   => '',
		'title' => 'Safety Helmets & Hard Hats',
		'image' => $safestore_cat_img( 'industrial_safety_category-helmet.webp' ),
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
			$card_sizes = ( $cat['size'] === 'xl' )
				? '(max-width: 900px) 92vw, (max-width: 1200px) 50vw, 560px'
				: '(max-width: 900px) 46vw, (max-width: 1200px) 40vw, 420px';
			?>
			<a class="category-card category-card--<?php echo esc_attr( $cat['size'] ); ?> category-card--<?php echo esc_attr( $cat['fit'] ); ?>"
				href="<?php echo esc_url( $cat['url'] ); ?>">
				<img src="<?php echo esc_url( $cat['image'] ); ?>"
					alt="<?php echo esc_attr( $cat['alt'] ); ?>"
					width="800"
					height="600"
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
				<span class="category-card-arrow" aria-hidden="true">&rarr;</span>
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
		// 8 products, 4-up on desktop — same card template as shop (content-product.php).
		// Deterministic: WooCommerce popularity (total sales), not random.
		echo do_shortcode( '[products limit="10" columns="4" orderby="popularity"]' );
		?>
	</div>
</section>

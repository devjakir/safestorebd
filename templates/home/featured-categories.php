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
		'title' => 'Safety Helmets & Hard Hats',
		'image' => $safestore_cat_img( 'industrial_safety_category-helmet.webp' ),
		'fit'   => 'cover',
		'url'   => $helmet_cat,
		'alt'   => 'Industrial safety helmets and hard hats category art',
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
		'title' => 'Industrial Safety Shoes',
		'image' => $safestore_cat_img( 'premium_brown_leather_industrial_safety_boots.webp' ),
		'fit'   => 'cover',
		'url'   => $shoe_cat,
		'alt'   => 'Premium brown leather industrial safety boots and work shoes',
	),
);
?>

<section id="browse-categories" class="featured-categories" aria-label="Shop by safety category">
	<div class="featured-categories-head">
		<div>
			<h2>Shop by Safety Category</h2>
			<p>Certified industrial PPE — browse helmets, hi-vis vests, gloves, eyewear, and safety shoes.</p>
		</div>
		<a class="featured-categories-view-all" href="<?php echo esc_url( $shop_url ); ?>">View all <span aria-hidden="true">&rarr;</span></a>
	</div>

	<div class="featured-categories-grid">
		<?php foreach ( $categories as $cat ) : ?>
			<a class="category-card category-card--<?php echo esc_attr( $cat['size'] ); ?> category-card--<?php echo esc_attr( $cat['fit'] ); ?>"
				href="<?php echo esc_url( $cat['url'] ); ?>">
				<img src="<?php echo esc_url( $cat['image'] ); ?>"
					alt="<?php echo esc_attr( $cat['alt'] ); ?>"
					loading="lazy"
					decoding="async">
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
<section class="featured-categories" aria-label="Best selling products">
	<div class="featured-categories-head">
		<div>
			<h2>Best Sellers</h2>
			<p>The safety gear teams across Bangladesh order most.</p>
		</div>
		<a class="featured-categories-view-all" href="<?php echo esc_url( $shop_url ); ?>">View all <span aria-hidden="true">&rarr;</span></a>
	</div>
	<?php echo do_shortcode( '[products limit="8" columns="4" orderby="popularity"]' ); ?>
</section>

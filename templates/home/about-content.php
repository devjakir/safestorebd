<?php
/**
 * Home — SEO copy block (Star Tech–style: full-width H2 / H3 article).
 *
 * H1 lives in the hero. Inline links use real shop, category, and policy URLs.
 *
 * @package safestore-minimal
 */

$home_url    = home_url( '/' );
$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$shoe_url    = safestore_home_category_url( 'shoe' );
$helmet_url  = safestore_home_category_url( 'helmet' );
$vest_url    = safestore_home_category_url( 'vest' );
$glove_url   = safestore_home_category_url( 'glove' );
$goggle_url  = safestore_home_category_url( 'goggle' );
$about_url   = home_url( '/about/' );
$contact_url = home_url( '/contact/' );
$bulk_url    = home_url( '/bulk-orders/' );
$ship_url    = home_url( '/shipping-delivery/' );
$return_url  = home_url( '/return-refund-policy/' );
$faq_url     = home_url( '/faqs/' );
$track_url   = home_url( '/track-order/' );
$compare_url = home_url( '/compare/' );

$kses = array(
	'a'      => array( 'href' => true ),
	'strong' => array(),
);
?>

<section class="sft-home-story" id="about-safestorebd" aria-labelledby="sft-home-story-title">
	<div class="sft-home-story__inner">
		<h2 class="sft-home-story__title" id="sft-home-story-title">
			<?php esc_html_e( 'Industrial Safety Equipment, Safety Shoes & PPE Shop in Bangladesh', 'safestore-minimal' ); ?>
		</h2>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: home URL, 2: shop URL, 3: about URL, 4: contact URL */
					__( 'Factories, construction sites, and warehouses in Bangladesh need gear that survives a real shift. <a href="%1$s">SafeStoreBD</a> is an <a href="%2$s">industrial safety equipment</a> shop built around that job — Safety First, honest specs, and stock you can inspect before you kit a crew. Read <a href="%3$s">about us</a> or <a href="%4$s">contact</a> the team if you need photos, sizing, or a quote.', 'safestore-minimal' ),
					esc_url( $home_url ),
					esc_url( $shop_url ),
					esc_url( $about_url ),
					esc_url( $contact_url )
				),
				$kses
			);
			?>
		</p>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: shipping URL, 2: FAQ URL */
					__( 'We sell quality-checked imported PPE and deliver to all 64 districts. Payment is bKash, Nagad, or cash on delivery. See <a href="%1$s">shipping</a> for the dispatch window, or the <a href="%2$s">FAQ</a> for orders and returns.', 'safestore-minimal' ),
					esc_url( $ship_url ),
					esc_url( $faq_url )
				),
				$kses
			);
			?>
		</p>

		<h3><?php esc_html_e( 'Safety Footwear Shop in Bangladesh', 'safestore-minimal' ); ?></h3>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: safety shoes URL, 2: compare URL */
					__( 'Site work, plant floors, and warehouses need different boots. Our <a href="%1$s">safety shoes</a> cover leather <a href="%1$s">steel-toe work shoes</a>, anti-slip soles, anti-static <a href="%1$s">PVC gumboots</a>, and waterproof oil-resistant rain boots. <a href="%2$s">Compare</a> models before you buy, or tell us the hazard and budget and we will point to a pair that fits the job.', 'safestore-minimal' ),
					esc_url( $shoe_url ),
					esc_url( $compare_url )
				),
				$kses
			);
			?>
		</p>

		<h3><?php esc_html_e( 'Industrial PPE: Helmets, Vests, Gloves & Eyewear', 'safestore-minimal' ); ?></h3>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: helmets, 2: vests, 3: gloves, 4: goggles, 5: shop */
					__( 'Beyond footwear we stock <a href="%1$s">protective helmets and hard hats</a>, <a href="%2$s">high-visibility safety vests</a>, <a href="%3$s">industrial gloves</a>, and <a href="%4$s">safety goggles</a>. Use the <a href="%5$s">PPE shop</a> to restock a factory cupboard or kit a new project — pick the category that matches the work so you are not overbuying.', 'safestore-minimal' ),
					esc_url( $helmet_url ),
					esc_url( $vest_url ),
					esc_url( $glove_url ),
					esc_url( $goggle_url ),
					esc_url( $shop_url )
				),
				$kses
			);
			?>
		</p>

		<h3><?php esc_html_e( 'Buy Safety Equipment Online in Bangladesh', 'safestore-minimal' ); ?></h3>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: shop URL, 2: bulk URL, 3: track order URL, 4: contact URL */
					__( 'Order from <a href="%1$s">safestorebd.com</a> from anywhere in the country. Search, add to cart, and check out. Companies that buy in volume can request a <a href="%2$s">bulk order</a> instead of hunting for a hidden coupon. After you place an order, <a href="%3$s">track it</a> or <a href="%4$s">message us</a> if the site needs a packing list.', 'safestore-minimal' ),
					esc_url( $shop_url ),
					esc_url( $bulk_url ),
					esc_url( $track_url ),
					esc_url( $contact_url )
				),
				$kses
			);
			?>
		</p>

		<h3><?php esc_html_e( 'Price, Delivery & Returns', 'safestore-minimal' ); ?></h3>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: shipping URL, 2: returns URL, 3: about URL */
					__( 'A single pair and a plant pallet go through the same queue. We ship nationwide — including Rangpur, Chattogram, and Khulna — under the windows on our <a href="%1$s">shipping</a> page (order before 2 PM for that day’s dispatch cut-off). Returns are <a href="%2$s">7 days</a>, not 14. For industrial safety equipment you can inspect before you scale up, start with <a href="%3$s">SafeStoreBD</a>.', 'safestore-minimal' ),
					esc_url( $ship_url ),
					esc_url( $return_url ),
					esc_url( $about_url )
				),
				$kses
			);
			?>
		</p>
	</div>
</section>

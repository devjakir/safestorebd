<?php

$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$shoe_url    = safestore_home_category_url( 'shoe' );
$helmet_url  = safestore_home_category_url( 'helmet' );
$vest_url    = safestore_home_category_url( 'vest' );
$glove_url   = safestore_home_category_url( 'glove' );
$goggle_url  = safestore_home_category_url( 'goggle' );
$contact_url = home_url( '/contact/' );
$bulk_url    = home_url( '/bulk-orders/' );
$ship_url    = home_url( '/shipping-delivery/' );
$return_url  = home_url( '/return-refund-policy/' );

$phone_e164    = function_exists( 'safestore_primary_phone_e164' ) ? safestore_primary_phone_e164() : '+8801811892291';
$phone_display = function_exists( 'safestore_primary_phone_display' ) ? safestore_primary_phone_display() : '+880 1811-892291';
$wa_url        = function_exists( 'safestore_primary_wa_link' ) ? safestore_primary_wa_link( 'Hello SafeStoreBD! I need help choosing safety gear.' ) : 'https://wa.me/' . preg_replace( '/\D/', '', $phone_e164 );

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
					/* translators: 1: shop URL */
					__( 'SafeStoreBD supplies <a href="%1$s">safety gear</a> to garment factories, construction sites, warehouses and workshops across Bangladesh. We stock quality-checked PPE with clear specs on every product page, and deliver to all 64 districts with cash on delivery, bKash or Nagad.', 'safestore-minimal' ),
					esc_url( $shop_url )
				),
				$kses
			);
			?>
		</p>

		<h3><?php esc_html_e( 'Safety Shoes & Gumboots', 'safestore-minimal' ); ?></h3>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: safety shoes URL */
					__( 'Choose from steel-toe leather <a href="%1$s">safety shoes</a>, lightweight work sneakers and waterproof PVC gumboots for site work, factory floors and wet areas. Not sure which pair suits the job? Tell us the work and your budget, and we will suggest one.', 'safestore-minimal' ),
					esc_url( $shoe_url )
				),
				$kses
			);
			?>
		</p>

		<h3><?php esc_html_e( 'Helmets, Vests, Gloves & Goggles', 'safestore-minimal' ); ?></h3>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: helmets URL, 2: vests URL, 3: gloves URL, 4: goggles URL */
					__( 'We also carry <a href="%1$s">safety helmets</a>, reflective <a href="%2$s">safety vests</a>, heat-resistant and electrical <a href="%3$s">safety gloves</a>, and <a href="%4$s">safety goggles</a> — for one worker or a full team.', 'safestore-minimal' ),
					esc_url( $helmet_url ),
					esc_url( $vest_url ),
					esc_url( $glove_url ),
					esc_url( $goggle_url )
				),
				$kses
			);
			?>
		</p>

		<h3><?php esc_html_e( 'Bulk Orders, Delivery & Returns', 'safestore-minimal' ); ?></h3>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: bulk order URL, 2: shipping URL, 3: returns URL */
					__( 'Buying for a factory or project? Send us your list through the <a href="%1$s">bulk order</a> page for volume pricing. Orders placed before 2 PM are dispatched the same day (see <a href="%2$s">shipping details</a>), and you can return or exchange within <a href="%3$s">7 days</a>.', 'safestore-minimal' ),
					esc_url( $bulk_url ),
					esc_url( $ship_url ),
					esc_url( $return_url )
				),
				$kses
			);
			?>
		</p>
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: 1: WhatsApp URL, 2: phone number, 3: contact URL */
					__( 'Questions, photos or a quote? Call or WhatsApp <a href="%1$s"><strong>%2$s</strong></a> or <a href="%3$s">contact us</a>.', 'safestore-minimal' ),
					esc_url( $wa_url ),
					esc_html( $phone_display ),
					esc_url( $contact_url )
				),
				$kses
			);
			?>
		</p>
	</div>
</section>

<?php
// Template Name: About
get_header();

$shop_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$contact    = home_url( '/contact/' );
$bulk_url   = home_url( '/bulk-orders/' );
$ship_url   = home_url( '/shipping-delivery/' );
$return_url = home_url( '/return-refund-policy/' );
$phone_href = 'tel:+8801811892291';
$phone      = '+880 1811-892291';
$wa_href    = safestore_wa_link();
$wa_phone   = safestore_wa_display();
$email      = 'contact@safestorebd.com';
$office     = function_exists( 'safestore_minimal_get_pickup_address' ) ? safestore_minimal_get_pickup_address() : '';
if ( '' === $office ) {
	$office = '17/5/1 Alabdirtek, Pallabi, Dhaka 1216, Bangladesh';
}

$trust = array(
	__( 'Delivery to all 64 districts', 'safestore-minimal' ),
	__( 'Written bulk quotation within 24 hours', 'safestore-minimal' ),
	__( '7-day returns', 'safestore-minimal' ),
);

// What we supply: label, description, product category slug.
$categories = array(
	array( __( 'Head protection', 'safestore-minimal' ), __( 'safety helmets and hard hats', 'safestore-minimal' ), 'protective-helmet' ),
	array( __( 'Foot protection', 'safestore-minimal' ), __( 'steel-toe safety shoes and PVC gumboots', 'safestore-minimal' ), 'safety-shoe' ),
	array( __( 'Hand protection', 'safestore-minimal' ), __( 'industrial and oil-resistant gloves', 'safestore-minimal' ), 'safety-glove' ),
	array( __( 'Eye protection', 'safestore-minimal' ), __( 'safety goggles and glasses', 'safestore-minimal' ), 'safety-goggle' ),
	array( __( 'Visibility', 'safestore-minimal' ), __( 'hi-vis reflective vests', 'safestore-minimal' ), 'safety-vest' ),
);

$promises = array(
	__( 'We list the brand, material and specs as they are, with real photos.', 'safestore-minimal' ),
	__( 'We share the manufacturer\'s standard markings and test reports on request, where the product has them.', 'safestore-minimal' ),
	__( 'We send extra photos or sizing details before you buy.', 'safestore-minimal' ),
	__( 'We take it back within 7 days if it is not what we described.', 'safestore-minimal' ),
);

$segments = array(
	__( 'Garment & textile factories', 'safestore-minimal' ),
	__( 'Construction & real estate', 'safestore-minimal' ),
	__( 'Shipyards & steel', 'safestore-minimal' ),
	__( 'Logistics & warehousing', 'safestore-minimal' ),
	__( 'Power, cement & chemicals', 'safestore-minimal' ),
	__( 'Hospitals & facilities', 'safestore-minimal' ),
);

$bulk_points = array(
	__( 'Written quotation within 24 hours (Sat–Thu)', 'safestore-minimal' ),
	__( 'Volume pricing at 12, 50 and 100+ pieces', 'safestore-minimal' ),
	__( 'VAT invoice (Mushak 6.3) and delivery challan with every order', 'safestore-minimal' ),
	__( 'Mixed size runs for footwear and gloves', 'safestore-minimal' ),
	__( 'One contact person from quotation to delivery', 'safestore-minimal' ),
);

$facts = array(
	__( 'Founded', 'safestore-minimal' )          => __( '17 October 2025', 'safestore-minimal' ),
	__( 'Founder', 'safestore-minimal' )          => __( 'Md Jakir Hossain, BSc Engineer', 'safestore-minimal' ),
	__( 'Office & pickup', 'safestore-minimal' )  => $office,
	__( 'Hours', 'safestore-minimal' )            => __( 'Sat–Thu, 9am–8pm', 'safestore-minimal' ),
);

while ( have_posts() ) :
	the_post();
	?>
	<main class="sft-about" id="main-content">
		<section class="sft-about-hero" aria-labelledby="sft-about-title">
			<div class="sft-about-hero-inner">
				<p class="sft-about-eyebrow"><?php esc_html_e( 'About SafeStoreBD', 'safestore-minimal' ); ?></p>
				<h1 class="sft-about-title" id="sft-about-title"><?php esc_html_e( 'Safety gear for the people who keep Bangladesh working', 'safestore-minimal' ); ?></h1>
				<p class="sft-about-lede">
					<?php esc_html_e( 'SafeStoreBD is an online PPE and industrial safety store based in Pallabi, Dhaka. We stock helmets, safety shoes, gloves, goggles and hi-vis vests, show the price up front, and deliver to all 64 districts.', 'safestore-minimal' ); ?>
				</p>
				<ul class="sft-about-trust" role="list">
					<?php foreach ( $trust as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
				<div class="sft-about-hero-cta">
					<a class="sft-about-btn sft-about-btn--primary" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop catalogue', 'safestore-minimal' ); ?></a>
					<a class="sft-about-btn sft-about-btn--ghost" href="<?php echo esc_url( $bulk_url ); ?>"><?php esc_html_e( 'Request a bulk quote', 'safestore-minimal' ); ?></a>
				</div>
			</div>
		</section>

		<?php if ( isset( $post->post_content ) && trim( (string) $post->post_content ) !== '' ) : ?>
			<section class="sft-about-editor-wrap" aria-label="<?php esc_attr_e( 'Page content', 'safestore-minimal' ); ?>">
				<div class="sft-about-inner">
					<div class="sft-about-editor entry-content">
						<?php the_content(); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="sft-about-body" aria-labelledby="sft-about-summary-heading">
			<div class="sft-about-inner sft-about-body-grid">
				<div class="sft-about-summary">
					<section class="sft-about-section">
						<h2 class="sft-about-h2" id="sft-about-summary-heading"><?php esc_html_e( 'Who we are', 'safestore-minimal' ); ?></h2>
						<p class="sft-about-summary-text">
							<?php esc_html_e( 'SafeStoreBD was founded on 17 October 2025 by Md Jakir Hossain, a BSc engineer, with a simple idea: a site supervisor or factory buyer should be able to see the real price of a safety shoe or helmet without making five phone calls.', 'safestore-minimal' ); ?>
						</p>
						<p class="sft-about-summary-text">
							<?php esc_html_e( 'Today we supply single pairs to individual workers and full kits to factories, construction sites and warehouses. A single pair and a pallet go through the same queue, with the same care.', 'safestore-minimal' ); ?>
						</p>
					</section>

					<section class="sft-about-section" aria-labelledby="sft-about-supply-heading">
						<h2 class="sft-about-h2" id="sft-about-supply-heading"><?php esc_html_e( 'What we supply', 'safestore-minimal' ); ?></h2>
						<ul class="sft-about-highlights">
							<?php
							foreach ( $categories as $cat ) :
								$link = function_exists( 'get_term_link' ) ? get_term_link( $cat[2], 'product_cat' ) : '';
								if ( ! is_string( $link ) || '' === $link ) {
									$link = home_url( '/product-category/' . $cat[2] . '/' );
								}
								?>
								<li><a href="<?php echo esc_url( $link ); ?>"><strong><?php echo esc_html( $cat[0] ); ?></strong></a>: <?php echo esc_html( $cat[1] ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>

					<section class="sft-about-section" aria-labelledby="sft-about-source-heading">
						<h2 class="sft-about-h2" id="sft-about-source-heading"><?php esc_html_e( 'How we source, and what we promise', 'safestore-minimal' ); ?></h2>
						<p class="sft-about-summary-text">
							<?php esc_html_e( 'We import our stock and check it in Dhaka before it is listed. We are a supplier, not a testing lab, so we do not issue certificates of our own. What we do instead:', 'safestore-minimal' ); ?>
						</p>
						<ul class="sft-about-highlights">
							<?php foreach ( $promises as $item ) : ?>
								<li><?php echo esc_html( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
						<p class="sft-about-note">
							<?php esc_html_e( 'If a job needs a specific standard, tell us. We will say plainly whether a product meets it.', 'safestore-minimal' ); ?>
							<a href="<?php echo esc_url( $return_url ); ?>"><?php esc_html_e( 'Return policy', 'safestore-minimal' ); ?></a>
						</p>
					</section>

					<section class="sft-about-section" aria-labelledby="sft-about-serve-heading">
						<h2 class="sft-about-h2" id="sft-about-serve-heading"><?php esc_html_e( 'Industries we supply', 'safestore-minimal' ); ?></h2>
						<ul class="sft-about-segments" role="list">
							<?php foreach ( $segments as $segment ) : ?>
								<li><?php echo esc_html( $segment ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>

					<section class="sft-about-section" aria-labelledby="sft-about-bulk-heading">
						<h2 class="sft-about-h2" id="sft-about-bulk-heading"><?php esc_html_e( 'Built for company buyers', 'safestore-minimal' ); ?></h2>
						<ul class="sft-about-highlights">
							<?php foreach ( $bulk_points as $item ) : ?>
								<li><?php echo esc_html( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
						<a class="sft-about-btn sft-about-btn--primary" href="<?php echo esc_url( $bulk_url ); ?>"><?php esc_html_e( 'Request a quotation', 'safestore-minimal' ); ?></a>
					</section>

					<section class="sft-about-section" aria-labelledby="sft-about-delivery-heading">
						<h2 class="sft-about-h2" id="sft-about-delivery-heading"><?php esc_html_e( 'Delivery and payment', 'safestore-minimal' ); ?></h2>
						<p class="sft-about-summary-text">
							<?php esc_html_e( 'We dispatch from Pallabi, Dhaka. Orders placed before 2 PM go out the same day. Inside Dhaka takes about 24 hours; outside Dhaka 2–3 business days. Pay by cash on delivery, bKash or Nagad, or by bank transfer for corporate orders. Pickup from our Pallabi office is free.', 'safestore-minimal' ); ?>
						</p>
						<p class="sft-about-note"><a href="<?php echo esc_url( $ship_url ); ?>"><?php esc_html_e( 'Shipping rates and timing', 'safestore-minimal' ); ?></a></p>
					</section>
				</div>

				<aside class="sft-about-contact-card" aria-labelledby="sft-about-contact-heading">
					<h3 class="sft-about-h3" id="sft-about-contact-heading"><?php esc_html_e( 'Company details', 'safestore-minimal' ); ?></h3>
					<ul class="sft-about-contact-list">
						<?php foreach ( $facts as $label => $value ) : ?>
							<li>
								<span class="sft-about-contact-label"><?php echo esc_html( $label ); ?></span>
								<?php echo esc_html( $value ); ?>
							</li>
						<?php endforeach; ?>
						<li>
							<span class="sft-about-contact-label"><?php esc_html_e( 'Phone', 'safestore-minimal' ); ?></span>
							<a href="<?php echo esc_url( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a>
						</li>
						<li>
							<span class="sft-about-contact-label"><?php esc_html_e( 'Email', 'safestore-minimal' ); ?></span>
							<?php echo safestore_contact_email_links(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</li>
						<li>
							<span class="sft-about-contact-label"><?php esc_html_e( 'WhatsApp', 'safestore-minimal' ); ?></span>
							<?php echo safestore_wa_cta_link( $wa_href, $wa_phone, 'sft-about-contact-wa' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</li>
					</ul>
					<a class="sft-about-btn sft-about-btn--primary sft-about-contact-shop" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Browse shop', 'safestore-minimal' ); ?></a>
				</aside>
			</div>
		</section>

		<?php
		$cta_actions = '';
		$cta_actions .= safestore_page_cta_btn(
			array(
				'href'    => $wa_href,
				'label'   => __( 'WhatsApp us', 'safestore-minimal' ),
				'variant' => 'primary',
				'blank'   => true,
			)
		);
		$cta_actions .= safestore_page_cta_btn(
			array(
				'href'    => $contact,
				'label'   => __( 'Contact form', 'safestore-minimal' ),
				'variant' => 'secondary',
			)
		);

		safestore_render_page_cta(
			array(
				'title'   => __( 'Not sure what your site needs?', 'safestore-minimal' ),
				'text'    => __( 'Tell us the job and the hazards. We will put together a PPE list with prices before you order.', 'safestore-minimal' ),
				'label'   => __( 'Talk to us', 'safestore-minimal' ),
				'actions' => $cta_actions,
			)
		);
		?>
	</main>
	<?php
endwhile;

get_footer();

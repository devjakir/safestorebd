<?php
/**
 * Template Name: Shipping & Delivery
 *
 * Shipping and delivery information — Bangladesh market.
 *
 * @package safestore-minimal
 */

get_header();

$track_url    = home_url( '/track-order/' );
$faq_url      = home_url( '/faqs/' );
$returns_url  = home_url( '/return-refund-policy/' );
$contact_url  = home_url( '/contact/' );
$phone_href   = 'tel:+8801880307446';
$phone        = '+880 1880-307446';
$wa_href      = 'https://wa.me/8801880307446';
$email        = 'bdsafestore@gmail.com';
$zones        = safestore_minimal_get_shipping_zones();
$pickup       = safestore_minimal_get_pickup_address();

while ( have_posts() ) :
	the_post();
	?>
	<main class="sft-about sft-ship" id="main-content" itemscope itemtype="https://schema.org/WebPage">
		<meta itemprop="name" content="<?php echo esc_attr( get_the_title() ); ?>" />
		<meta itemprop="description" content="<?php echo esc_attr( __( 'Nationwide courier delivery and free Pallabi office pickup for industrial PPE orders in Bangladesh.', 'safestore-minimal' ) ); ?>" />

		<section class="sft-about-hero sft-ship-hero" aria-labelledby="sft-ship-title">
			<div class="sft-about-hero-inner">
				<p class="sft-about-eyebrow"><?php esc_html_e( 'Delivery', 'safestore-minimal' ); ?></p>
				<h1 class="sft-about-title" id="sft-ship-title"><?php the_title(); ?></h1>
				<p class="sft-about-lede">
					<?php esc_html_e( 'We dispatch from Pallabi, Dhaka to all districts — courier or free store pickup.', 'safestore-minimal' ); ?>
				</p>
				<div class="sft-about-hero-cta">
					<a class="sft-about-btn sft-about-btn--primary" href="<?php echo esc_url( $track_url ); ?>"><?php esc_html_e( 'Track order', 'safestore-minimal' ); ?></a>
					<a class="sft-about-btn sft-about-btn--ghost" href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp', 'safestore-minimal' ); ?></a>
				</div>
			</div>
		</section>

		<?php if ( trim( (string) get_post()->post_content ) !== '' ) : ?>
			<section class="sft-about-editor-wrap" aria-label="<?php esc_attr_e( 'Additional notes', 'safestore-minimal' ); ?>">
				<div class="sft-about-inner">
					<div class="sft-about-editor entry-content">
						<?php the_content(); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="sft-about-body sft-ship-body" aria-labelledby="sft-ship-rates-heading">
			<div class="sft-about-inner sft-about-body-grid">
				<article class="sft-about-summary sft-ship-content">
					<h2 class="sft-about-h2" id="sft-ship-rates-heading"><?php esc_html_e( 'Rates & timing', 'safestore-minimal' ); ?></h2>
					<p class="sft-about-summary-text">
						<?php esc_html_e( 'Final shipping is calculated at checkout by weight and destination. Business days: Saturday–Thursday.', 'safestore-minimal' ); ?>
					</p>

					<div class="sft-ship-table-wrap">
						<table class="sft-ship-table">
							<thead>
								<tr>
									<th scope="col"><?php esc_html_e( 'Area', 'safestore-minimal' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Dispatch', 'safestore-minimal' ); ?></th>
									<th scope="col"><?php esc_html_e( 'From', 'safestore-minimal' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $zones as $row ) : ?>
									<tr>
										<td><?php echo esc_html( $row['zone'] ); ?></td>
										<td><?php echo esc_html( $row['time'] ); ?></td>
										<td><?php echo esc_html( $row['cost'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<ul class="sft-about-highlights sft-ship-highlights">
						<li><?php esc_html_e( 'COD orders: keep your phone on — couriers may call before delivery.', 'safestore-minimal' ); ?></li>
						<li><?php esc_html_e( 'Use a complete address with area, thana, and working mobile number.', 'safestore-minimal' ); ?></li>
						<li><?php esc_html_e( 'Heavy or bulk PPE may need a custom quote — contact us before ordering.', 'safestore-minimal' ); ?></li>
					</ul>
				</article>

				<aside class="sft-about-contact-card" aria-labelledby="sft-ship-contact-heading">
					<h3 class="sft-about-h3" id="sft-ship-contact-heading"><?php esc_html_e( 'Pickup & support', 'safestore-minimal' ); ?></h3>
					<p class="sft-about-contact-lead"><?php echo esc_html( $pickup ); ?></p>
					<ul class="sft-about-contact-list">
						<li><a href="<?php echo esc_url( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a></li>
						<li><a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a></li>
						<li>
							<a class="sft-about-contact-wa" href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp', 'safestore-minimal' ); ?></a>
						</li>
					</ul>
					<a class="sft-about-btn sft-about-btn--primary sft-about-contact-shop" href="<?php echo esc_url( $track_url ); ?>"><?php esc_html_e( 'Track order', 'safestore-minimal' ); ?></a>
				</aside>
			</div>
		</section>

		<aside class="sft-ship-page-footer" aria-label="<?php esc_attr_e( 'Shipping help', 'safestore-minimal' ); ?>">
			<div class="sft-ship-page-footer-inner">
				<div class="sft-ship-page-footer-grid">
					<div class="sft-ship-page-footer-col">
						<h3 class="sft-ship-page-footer-heading"><?php esc_html_e( 'Quick links', 'safestore-minimal' ); ?></h3>
						<ul class="sft-ship-page-footer-links">
							<li><a href="<?php echo esc_url( $track_url ); ?>"><?php esc_html_e( 'Track order', 'safestore-minimal' ); ?></a></li>
							<li><a href="<?php echo esc_url( $faq_url ); ?>"><?php esc_html_e( 'FAQ', 'safestore-minimal' ); ?></a></li>
							<li><a href="<?php echo esc_url( $returns_url ); ?>"><?php esc_html_e( 'Returns', 'safestore-minimal' ); ?></a></li>
						</ul>
					</div>
					<div class="sft-ship-page-footer-col">
						<h3 class="sft-ship-page-footer-heading"><?php esc_html_e( 'Before delivery', 'safestore-minimal' ); ?></h3>
						<ul class="sft-ship-page-footer-tips">
							<li><?php esc_html_e( 'Confirm COD orders when we call or message.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Inspect the parcel before paying the courier.', 'safestore-minimal' ); ?></li>
							<li><?php esc_html_e( 'Sat–Thu dispatch; closed Fridays.', 'safestore-minimal' ); ?></li>
						</ul>
					</div>
					<div class="sft-ship-page-footer-col sft-ship-page-footer-col--pay">
						<h3 class="sft-ship-page-footer-heading"><?php esc_html_e( 'On delivery', 'safestore-minimal' ); ?></h3>
						<ul class="sft-payment-pills sft-ship-page-footer-pills" aria-label="<?php esc_attr_e( 'Payment on delivery', 'safestore-minimal' ); ?>">
							<li class="sft-pay sft-pay--cod" title="Cash on Delivery"><span class="sft-ship-pay-label"><?php esc_html_e( 'Cash on Delivery', 'safestore-minimal' ); ?></span></li>
							<li class="sft-pay sft-pay--bkash" title="bKash"><span class="sft-ship-pay-label">bKash</span></li>
							<li class="sft-pay sft-pay--nagad" title="Nagad"><span class="sft-ship-pay-label">Nagad</span></li>
						</ul>
						<p class="sft-ship-page-footer-pay-note"><?php esc_html_e( 'Prepaid via wallet or pay COD to the courier where offered.', 'safestore-minimal' ); ?></p>
					</div>
				</div>

				<div class="sft-ship-page-footer-cta">
					<div class="sft-ship-page-footer-cta-copy">
						<h2 class="sft-ship-page-footer-cta-title"><?php esc_html_e( 'Question about your shipment?', 'safestore-minimal' ); ?></h2>
						<p><?php esc_html_e( 'Message us with your order number for tracking or delivery changes.', 'safestore-minimal' ); ?></p>
					</div>
					<div class="sft-ship-page-footer-cta-actions">
						<a class="sft-about-btn sft-about-btn--primary sft-about-btn--light" href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp', 'safestore-minimal' ); ?></a>
						<a class="sft-about-btn sft-about-btn--ghost sft-about-btn--light" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact', 'safestore-minimal' ); ?></a>
					</div>
				</div>
			</div>
		</aside>
	</main>
	<?php
endwhile;

get_footer();

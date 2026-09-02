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
$phone_href   = 'tel:+8801811892291';
$phone        = '+880 1811-892291';
$wa_href      = safestore_wa_link();
$wa_phone     = safestore_wa_display();
$email        = 'contact@safestorebd.com';
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
					<?php echo safestore_wa_cta_link( $wa_href, $wa_phone, 'sft-about-btn sft-about-btn--ghost' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
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
						<li><?php echo safestore_contact_email_links(); ?></li>
						<li>
							<?php echo safestore_wa_cta_link( $wa_href, $wa_phone, 'sft-about-contact-wa' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</li>
					</ul>
					<a class="sft-about-btn sft-about-btn--primary sft-about-contact-shop" href="<?php echo esc_url( $track_url ); ?>"><?php esc_html_e( 'Track order', 'safestore-minimal' ); ?></a>
				</aside>
			</div>
		</section>

		<?php safestore_render_page_cta(); ?>
	</main>
	<?php
endwhile;

get_footer();

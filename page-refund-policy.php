<?php
/**
 * Template Name: Refund & Policy
 *
 * Return and refund policy for industrial safety products — Bangladesh market.
 *
 * @package safestore-minimal
 */

get_header();

$shop_url     = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$contact_url  = home_url( '/contact/' );
$phone_href   = 'tel:+8801880307446';
$phone        = '+880 1880-307446';
$wa_href      = 'https://wa.me/8801880307446';
$email        = 'bdsafestore@gmail.com';

while ( have_posts() ) :
	the_post();
	?>
	<main class="sft-about sft-policy" id="main-content" itemscope itemtype="https://schema.org/WebPage">
		<meta itemprop="name" content="<?php echo esc_attr( get_the_title() ); ?>" />
		<meta itemprop="description" content="<?php echo esc_attr( __( 'Return and refund policy for industrial PPE purchased from SafeStoreBD in Bangladesh.', 'safestore-minimal' ) ); ?>" />

		<section class="sft-about-hero" aria-labelledby="sft-policy-title">
			<div class="sft-about-hero-inner">
				<p class="sft-about-eyebrow"><?php esc_html_e( 'Customer policy', 'safestore-minimal' ); ?></p>
				<h1 class="sft-about-title" id="sft-policy-title"><?php the_title(); ?></h1>
				<p class="sft-about-lede">
					<?php esc_html_e( 'You can return unused PPE in original condition within 7 days of delivery. Please contact us first for return approval.' ); ?>
				</p>
				<div class="sft-about-hero-cta">
					<a class="sft-about-btn sft-about-btn--primary" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Start a return', 'safestore-minimal' ); ?></a>
					<a class="sft-about-btn sft-about-btn--ghost" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Shop', 'safestore-minimal' ); ?></a>
				</div>
			</div>
		</section>

		<?php if ( trim( (string) get_post()->post_content ) !== '' ) : ?>
			<section class="sft-about-editor-wrap" aria-label="<?php esc_attr_e( 'Additional policy notes', 'safestore-minimal' ); ?>">
				<div class="sft-about-inner">
					<div class="sft-about-editor entry-content">
						<?php the_content(); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="sft-about-body" aria-labelledby="sft-policy-summary-heading">
			<div class="sft-about-inner sft-about-body-grid">
				<article class="sft-about-summary sft-policy-content" itemscope itemtype="https://schema.org/WebContentElement">

					<h2 class="sft-about-h2" id="sft-policy-summary-heading"><?php esc_html_e( 'Policy summary', 'safestore-minimal' ); ?></h2>

					<ul class="sft-about-highlights">
						<li><?php esc_html_e( '7-day window from delivery; items must be unused with tags and packaging.', 'safestore-minimal' ); ?></li>
						<li><?php esc_html_e( 'Get approval before shipping anything back — wrong or defective items: send order number and photos.', 'safestore-minimal' ); ?></li>
						<li><?php esc_html_e( 'No returns on opened gloves, custom-printed vests, used PPE, or clearance items.', 'safestore-minimal' ); ?></li>
						<li><?php esc_html_e( 'Refunds in 5–10 business days (bKash, Nagad, or original payment) after inspection at our Pallabi office.', 'safestore-minimal' ); ?></li>
					</ul>

					<p class="sft-about-summary-text sft-policy-note">
						<?php esc_html_e( 'Delivery fees are not refunded unless we made an error. Your rights under Bangladesh consumer law still apply.', 'safestore-minimal' ); ?>
					</p>
				</article>

				<aside class="sft-about-contact-card" aria-labelledby="sft-policy-contact-heading">
					<h3 class="sft-about-h3" id="sft-policy-contact-heading"><?php esc_html_e( 'Return support', 'safestore-minimal' ); ?></h3>
					<ul class="sft-about-contact-list">
						<li><a href="<?php echo esc_url( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a></li>
						<li><a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a></li>
						<li>
							<a class="sft-about-contact-wa" href="<?php echo esc_url( $wa_href ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp', 'safestore-minimal' ); ?></a>
						</li>
					</ul>
					<a class="sft-about-btn sft-about-btn--primary sft-about-contact-shop" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact us', 'safestore-minimal' ); ?></a>
				</aside>
			</div>
		</section>
	</main>
	<?php
endwhile;

get_footer();

</main>
<?php
/**
 * Site footer.
 * @package safestore-minimal
 */

$sfx_tel_raw    = apply_filters( 'safestore_minimal_phone_tel', '+8801811892291' );
$sfx_tel_digits = preg_replace( '/[^\d+]/', '', (string) $sfx_tel_raw );
$sfx_tel_label  = function_exists( 'safestore_wa_format_number' )
	? safestore_wa_format_number( $sfx_tel_raw )
	: trim( preg_replace( '/^\+?880/', '+880 ', (string) $sfx_tel_raw ) );
$sfx_address    = function_exists( 'safestore_minimal_get_pickup_address' )
	? safestore_minimal_get_pickup_address()
	: '17/5/1 Alabdirtek, Pallabi, Dhaka 1207, Bangladesh';
$sfx_emails     = function_exists( 'safestore_contact_email_addresses' )
	? safestore_contact_email_addresses()
	: array( 'contact@safestorebd.com' );
$sfx_about_links = array(
	array( 'About Us', home_url( '/about/' ) ),
	array( 'Careers', home_url( '/careers/' ) ),
	array( 'Contact', home_url( '/contact/' ) ),
	array( 'Legal', home_url( '/legal/' ) ),
);

$sfx_service_links = array(
	array( 'Shipping', home_url( '/shipping-delivery/' ) ),
	array( 'Return and Refund Policy', home_url( '/return-refund-policy/' ) ),
	array( 'Privacy Policy', home_url( '/privacy-policy/' ) ),
	array( 'Terms of Service', home_url( '/terms-of-service/' ) ),
	array( 'Track Order', home_url( '/track-order/' ) ),
	array( 'FAQ', home_url( '/faqs/' ) ),
);

$sfx_socials = array(
	array( 'Facebook', 'https://www.facebook.com/safestorebd', '<path d="M13.5 8.5V6.8c0-.8.5-.9.8-.9h2.1V2.5h-2.9C10.5 2.5 9 4.8 9 8v.5H6.8v3.6H9v9.4h4.5v-9.4h2.8l.4-3.6h-3.2z"/>' ),
	array( 'X', 'https://x.com/safestorebd', '<path d="M18.9 3h2.9l-6.3 7.2L23 21h-5.9l-4.6-6-5.3 6H4.3l6.8-7.8L1 3h6l4.2 5.5L18.9 3zm-1 16.3h1.6L6.1 4.6H4.4z"/>' ),
	array( 'Instagram', 'https://www.instagram.com/safestorebd/', '<path d="M12 7.2a4.8 4.8 0 1 0 0 9.6 4.8 4.8 0 0 0 0-9.6zm0 7.9a3.1 3.1 0 1 1 0-6.2 3.1 3.1 0 0 1 0 6.2z"/><path d="M18.1 2.5H5.9A3.4 3.4 0 0 0 2.5 5.9v12.2a3.4 3.4 0 0 0 3.4 3.4h12.2a3.4 3.4 0 0 0 3.4-3.4V5.9a3.4 3.4 0 0 0-3.4-3.4zm1.7 15.6c0 .9-.8 1.7-1.7 1.7H5.9c-.9 0-1.7-.8-1.7-1.7V5.9c0-.9.8-1.7 1.7-1.7h12.2c.9 0 1.7.8 1.7 1.7v12.2z"/><circle cx="17.4" cy="6.6" r="1.1"/>' ),
	array( 'Pinterest', 'https://www.pinterest.com/safestorebd/', '<path d="M12 2C6.5 2 2 6.5 2 12c0 4.2 2.6 7.8 6.3 9.3-.1-.8-.2-2 0-2.9.2-.8 1.2-5 1.2-5s-.3-.6-.3-1.5c0-1.4.8-2.4 1.8-2.4.9 0 1.3.6 1.3 1.4 0 .9-.5 2.1-.8 3.3-.2 1 .5 1.8 1.5 1.8 1.8 0 3.1-1.9 3.1-4.6 0-2.4-1.7-4.1-4.2-4.1-2.8 0-4.5 2.1-4.5 4.3 0 .9.3 1.8.7 2.3.1.1.1.2.1.3l-.3 1.1c0 .2-.1.2-.3.1-1.2-.6-2-2.4-2-3.9 0-3.2 2.3-6.1 6.6-6.1 3.5 0 6.2 2.5 6.2 5.8 0 3.4-2.2 6.2-5.2 6.2-1 0-2-.5-2.3-1.1l-.6 2.4c-.2.9-.8 2-1.2 2.6.9.3 1.9.4 3 .4 5.5 0 10-4.5 10-10S17.5 2 12 2z"/>' ),
	array( 'LinkedIn', 'https://www.linkedin.com/in/safestorebd/', '<path d="M5.4 8.1H2V21h3.4V8.1zM3.7 2.7A2 2 0 1 0 3.7 6.7 2 2 0 0 0 3.7 2.7zM22 13.6c0-3.4-1.8-5.9-5.2-5.9-2.4 0-3.4 1.3-4 2.2V8.1H9.4V21h3.4v-6.4c0-1.7.3-3.4 2.4-3.4s2.1 1.9 2.1 3.5V21H22v-7.4z"/>' ),
	array( 'YouTube', 'https://www.youtube.com/@SafeStoreBD', '<path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2 31.5 31.5 0 0 0 0 12a31.5 31.5 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1A31.5 31.5 0 0 0 24 12a31.5 31.5 0 0 0-.5-5.8zM9.8 15.5v-7l6.3 3.5-6.3 3.5z"/>' ),
);

/**
 * Small stroke icon for the support column.
 *
 * @param string $name Icon key.
 * @return string
 */
function sfx_footer_icon( $name ) {
	$paths = array(
		'phone'    => '<path d="M6.7 3.4 9.1 3l1.8 4.2-2 1.6a12.2 12.2 0 0 0 5.3 5.3l1.6-2 4.2 1.8-.4 2.4a2 2 0 0 1-2.2 1.7A15.6 15.6 0 0 1 5 6.2a2 2 0 0 1 1.7-2.8z"/>',
		'place'    => '<path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}
?>
<footer class="sfx-footer" role="contentinfo" aria-label="<?php esc_attr_e( 'Site footer', 'safestore-minimal' ); ?>">
	<div class="sfx-footer__inner">
		<div class="sfx-footer__main">

			<!-- Support ------------------------------------------------------ -->
			<div class="sfx-block sfx-block--support">
				<h2 class="sfx-heading"><?php esc_html_e( 'Support', 'safestore-minimal' ); ?></h2>

				<?php if ( '' !== $sfx_tel_digits ) : ?>
					<a class="sfx-bigbtn" href="<?php echo esc_url( 'tel:' . $sfx_tel_digits ); ?>">
						<span class="sfx-bigbtn__icon"><?php echo sfx_footer_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="sfx-bigbtn__text">
							<span class="sfx-bigbtn__eyebrow"><?php esc_html_e( 'Sat–Thu, 9am–8pm', 'safestore-minimal' ); ?></span>
							<span class="sfx-bigbtn__value"><?php echo esc_html( $sfx_tel_label ); ?></span>
						</span>
					</a>
				<?php endif; ?>

				<div class="sfx-bigbtn sfx-bigbtn--static">
					<span class="sfx-bigbtn__icon"><?php echo sfx_footer_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="sfx-bigbtn__text">
						<span class="sfx-bigbtn__eyebrow"><?php esc_html_e( 'Hours', 'safestore-minimal' ); ?></span>
						<span class="sfx-bigbtn__value"><?php esc_html_e( 'Sat–Thu, 9am–8pm — full week only', 'safestore-minimal' ); ?></span>
						<span class="sfx-bigbtn__eyebrow"><?php esc_html_e( 'Office: 9am–10pm', 'safestore-minimal' ); ?></span>
					</span>
				</div>
			</div>

			<!-- About us + customer service --------------------------------- -->
			<div class="sfx-block sfx-block--links">
				<?php
				$sfx_link_groups = array(
					array(
						'id'    => 'sfx-panel-about',
						'title' => __( 'About Us', 'safestore-minimal' ),
						'links' => $sfx_about_links,
					),
					array(
						'id'    => 'sfx-panel-service',
						'title' => __( 'Customer Service', 'safestore-minimal' ),
						'links' => $sfx_service_links,
					),
				);
				?>
				<?php foreach ( $sfx_link_groups as $sfx_group ) : ?>
					<div class="sfx-linkcol" data-sfx-acc>
						<h2 class="sfx-heading"><?php echo esc_html( $sfx_group['title'] ); ?></h2>
						<button type="button" class="sfx-acc__btn" aria-expanded="false" aria-controls="<?php echo esc_attr( $sfx_group['id'] ); ?>">
							<span><?php echo esc_html( $sfx_group['title'] ); ?></span>
							<svg class="sfx-acc__chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><polyline points="6 9 12 15 18 9"/></svg>
						</button>
						<ul class="sfx-links" id="<?php echo esc_attr( $sfx_group['id'] ); ?>">
							<?php foreach ( $sfx_group['links'] as $sfx_link ) : ?>
								<li><a href="<?php echo esc_url( $sfx_link[1] ); ?>"><?php echo esc_html( $sfx_link[0] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Stay connected ----------------------------------------------- -->
			<div class="sfx-block sfx-block--org">
				<h2 class="sfx-heading"><?php esc_html_e( 'Stay Connected', 'safestore-minimal' ); ?></h2>
				<!--<p class="sfx-org__desc"><?php esc_html_e( "Bangladesh's trusted source for industrial safety products. Quality PPE, dependable service, paperwork that holds up.", 'safestore-minimal' ); ?></p> -->

				<p class="sfx-org__line">
					<strong><?php esc_html_e( 'Visit us', 'safestore-minimal' ); ?></strong>
					<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php echo esc_html( $sfx_address ); ?></a>
				</p>

				<?php if ( ! empty( $sfx_emails ) ) : ?>
					<p class="sfx-org__line">
						<strong><?php esc_html_e( 'Email', 'safestore-minimal' ); ?></strong>
						<?php foreach ( $sfx_emails as $sfx_i => $sfx_email ) : ?>
							<?php
							$sfx_mailto = function_exists( 'safestore_contact_mailto_href' )
								? safestore_contact_mailto_href( $sfx_email )
								: esc_url( 'mailto:' . $sfx_email );
							?>
							<?php if ( '' !== $sfx_mailto ) : ?>
								<a href="<?php echo $sfx_mailto; // phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html( $sfx_email ); ?></a><?php echo ( $sfx_i < count( $sfx_emails ) - 1 ) ? '<br>' : ''; ?>
							<?php endif; ?>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>
			</div>

		</div>

		<!-- Payment / sourcing / socials ---------------------------------- -->
		<div class="sfx-footer__strip">
			<div class="sfx-strip__meta">
				<div class="sfx-metagroup">
					<span class="sfx-meta__label"><?php esc_html_e( 'We accept', 'safestore-minimal' ); ?></span>
					<ul class="sfx-pills" aria-label="<?php esc_attr_e( 'Payment methods', 'safestore-minimal' ); ?>">
						<li class="sfx-pay sfx-pay--cod" title="<?php esc_attr_e( 'Cash on Delivery', 'safestore-minimal' ); ?>">
							<span class="sfx-pay__label"><?php esc_html_e( 'COD', 'safestore-minimal' ); ?></span>
						</li>
						<li class="sfx-pay sfx-pay--bkash" title="bKash">
							<span class="sfx-pay__label">bKash</span>
						</li>
						<li class="sfx-pay sfx-pay--nagad" title="Nagad">
							<span class="sfx-pay__label">Nagad</span>
						</li>
					</ul>
				</div>

				<div class="sfx-metagroup">
					<span class="sfx-meta__label"><?php esc_html_e( 'Sourcing', 'safestore-minimal' ); ?></span>
					<ul class="sfx-pills">
						<li class="sfx-cert"><?php esc_html_e( '100% Genuine Imported Stock', 'safestore-minimal' ); ?></li>
					</ul>
				</div>
			</div>

			<div class="sfx-socials" aria-label="<?php esc_attr_e( 'Follow SafeStoreBD', 'safestore-minimal' ); ?>">
				<?php foreach ( $sfx_socials as $sfx_social ) : ?>
					<a class="sfx-social" href="<?php echo esc_url( $sfx_social[1] ); ?>" aria-label="<?php echo esc_attr( $sfx_social[0] ); ?>" target="_blank" rel="noopener noreferrer">
						<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><?php echo $sfx_social[2]; // phpcs:ignore WordPress.Security.EscapeOutput ?></svg>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="sfx-subfooter">
			<p class="sfx-copy">
				<?php
				printf(
					/* translators: %s: current year */
					esc_html__( '© %s SafeStoreBD. All rights reserved.', 'safestore-minimal' ),
					esc_html( date_i18n( 'Y' ) )
				);
				?>
			</p>
			<nav class="sfx-legal" aria-label="<?php esc_attr_e( 'Legal', 'safestore-minimal' ); ?>">
				<a href="<?php echo esc_url( home_url( '/sitemap/' ) ); ?>"><?php esc_html_e( 'Sitemap', 'safestore-minimal' ); ?></a>
			</nav>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>

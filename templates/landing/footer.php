<?php
/**
 * Order landings — simple footer (address, hours, call, email, policy links).
 * Closes nothing: the template prints its sticky bar / JSON after this, then wp_footer().
 */

defined( 'ABSPATH' ) || exit;

$lp_c = safestore_lp_contact();
?>
<footer class="lp-footer" role="contentinfo">
	<div class="lp-wrap lp-footer__grid">
		<div>
			<p class="lp-footer__brand"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<p><?php echo esc_html( $lp_c['address'] ); ?></p>
			<p><?php echo esc_html( $lp_c['hours'] ); ?></p>
		</div>
		<ul>
			<li><a href="<?php echo esc_url( 'tel:' . $lp_c['tel'] ); ?>" data-spl-loc="footer"><?php echo safestore_pl_icon( 'phone', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $lp_c['tel_label'] ); ?></a></li>
			<?php if ( '' !== $lp_c['email'] ) : ?>
				<li><a href="<?php echo esc_url( 'mailto:' . $lp_c['email'] ); ?>"><?php echo esc_html( $lp_c['email'] ); ?></a></li>
			<?php endif; ?>
		</ul>
		<nav aria-label="নীতিমালা">
			<ul class="lp-footer__links">
				<li><a href="<?php echo esc_url( home_url( '/shipping-delivery/' ) ); ?>">ডেলিভারি</a></li>
				<li><a href="<?php echo esc_url( home_url( '/return-refund-policy/' ) ); ?>">রিটার্ন ও রিফান্ড</a></li>
				<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">প্রাইভেসি পলিসি</a></li>
				<li><a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>">শর্তাবলি</a></li>
				<li><a href="<?php echo esc_url( home_url( '/track-order/' ) ); ?>">অর্ডার ট্র্যাক</a></li>
				<li><a href="<?php echo esc_url( $lp_c['shop'] ); ?>">শপ</a></li>
			</ul>
		</nav>
	</div>
	<p class="lp-wrap lp-footer__copy">© <?php echo esc_html( safestore_pl_bn( date_i18n( 'Y' ) ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
</footer>

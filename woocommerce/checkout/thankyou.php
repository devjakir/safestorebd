<?php
/**
 * Thank-you page (order received) — SafeStoreBD.
 *
 * Built for a shopper on a phone who just placed an order and wants three
 * answers fast: "Did it work?", "What do I do now?" and "What did I buy?".
 *
 *   1. Success header — name, order number (copyable), what happens next.
 *   2. Next steps — numbered, per payment type (bKash/Nagad, cash on delivery,
 *      or the gateway's own instructions for anything else).
 *   3. Order summary — products with images, totals, payment, address.
 *
 * Every WooCommerce hook of the core template still fires
 * (woocommerce_before_thankyou, woocommerce_thankyou_{gateway},
 * woocommerce_thankyou), so tracking pixels and gateway add-ons keep working.
 * Helpers live in inc/order-received.php, styles in css/page-order-received.css.
 *
 * Based on WooCommerce templates/checkout/thankyou.php.
 *
 * @package safestore-minimal
 * @version 11.2.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;

$sft_shop_url = wc_get_page_permalink( 'shop' );
$sft_hotline  = safestore_ty_hotline();
$sft_hot_disp = safestore_ty_format_phone( $sft_hotline );
?>

<div class="woocommerce-order sft-ty">

	<?php
	if ( ! $order ) :
		// No order data (e.g. the order key was not verified) — keep it simple.
		?>
		<section class="sft-ty-card sft-ty-hero">
			<span class="sft-ty-hero__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
			<div class="sft-ty-hero__body">
				<h1 class="sft-ty-hero__title"><?php esc_html_e( 'Thank you! Your order has been received.', 'safestore-minimal' ); ?></h1>
			</div>
		</section>
		<?php
		echo '</div>';
		return;
	endif;

	do_action( 'woocommerce_before_thankyou', $order->get_id() );

	if ( $order->has_status( 'failed' ) ) :
		?>
		<section class="sft-ty-card sft-ty-hero sft-ty-hero--failed">
			<span class="sft-ty-hero__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></span>
			<div class="sft-ty-hero__body">
				<h1 class="sft-ty-hero__title"><?php esc_html_e( 'Payment was not completed', 'safestore-minimal' ); ?></h1>
				<p class="sft-ty-hero__lead">
					<?php
					$sft_failed = apply_filters(
						'woocommerce_thankyou_order_failed_text',
						esc_html__( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ),
						$order
					);
					echo wp_kses_post( is_string( $sft_failed ) ? $sft_failed : '' );
					?>
				</p>
				<div class="sft-ty-actions">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="sft-ty-btn sft-ty-btn--primary"><?php esc_html_e( 'Try payment again', 'safestore-minimal' ); ?></a>
					<a href="<?php echo esc_url( 'tel:+' . $sft_hotline ); ?>" class="sft-ty-btn sft-ty-btn--ghost" data-sft-copy-skip>
						<?php
						/* translators: %s: hotline number */
						printf( esc_html__( 'Call %s', 'safestore-minimal' ), esc_html( $sft_hot_disp ) );
						?>
					</a>
				</div>
			</div>
		</section>
		<?php
		do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
		do_action( 'woocommerce_thankyou', $order->get_id() );
		echo '</div>';
		return;
	endif;

	// ---------------------------------------------------------------- data
	$sft_number    = $order->get_order_number();
	$sft_name      = safestore_ty_customer_name( $order );
	$sft_phone     = safestore_ty_format_phone( $order->get_billing_phone() );
	$sft_total_txt = safestore_ty_plain_price( $order->get_total(), $order );
	$sft_pay_type  = safestore_ty_payment_type( $order );
	$sft_pay_title = $order->get_payment_method_title();
	$sft_created   = $order->get_date_created();
	$sft_awaiting  = $order->has_status( array( 'pending', 'on-hold' ) );

	// A mobile-wallet order that is already marked paid needs no payment steps.
	if ( 'mobile' === $sft_pay_type && ! $sft_awaiting ) {
		$sft_pay_type = 'paid';
	}

	// bKash / Nagad Send Money details (inc/mfs-payment.php).
	$sft_mfs      = ( 'mobile' === $sft_pay_type && function_exists( 'safestore_mfs_order_payment' ) && safestore_mfs_gateway_id() === $order->get_payment_method() );
	$sft_mfs_paid = $sft_mfs ? safestore_mfs_order_payment( $order ) : array( 'trxid' => '' );
	$sft_trx_sent = $sft_mfs && '' !== $sft_mfs_paid['trxid'];

	$sft_wa_text = sprintf(
		/* translators: 1: order number, 2: order total, 3: payment method */
		__( 'Hi SafeStoreBD, I just placed order #%1$s (%2$s, %3$s).', 'safestore-minimal' ),
		$sft_number,
		$sft_total_txt,
		wp_strip_all_tags( $sft_pay_title )
	);
	if ( 'mobile' === $sft_pay_type && ! $sft_trx_sent ) {
		$sft_wa_text .= ' ' . __( 'I sent the payment by bKash / Nagad Send Money. TrxID:', 'safestore-minimal' ) . ' ';
	}
	$sft_wa_url = function_exists( 'safestore_wa_link' ) ? safestore_wa_link( 'primary', $sft_wa_text ) : '';

	// Gateway instructions (woocommerce_thankyou_{id}). Captured so they can sit
	// inside the "Next steps" card. For bKash/Nagad and COD the numbered steps
	// below already say the same thing, so plain text is dropped there — but
	// anything interactive a gateway prints (a form, a pay button) is kept.
	ob_start();
	do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
	$sft_gateway_html = trim( (string) ob_get_clean() );
	$sft_gateway_is_interactive = (bool) preg_match( '/<(form|input|button|iframe|script|a\s)/i', $sft_gateway_html );

	// --------------------------------------------------------------- steps
	$sft_steps = array();

	if ( 'mobile' === $sft_pay_type && $sft_trx_sent ) {
		$sft_steps = array(
			array(
				'done'  => true,
				'title' => __( 'Payment sent', 'safestore-minimal' ),
				'text'  => esc_html( safestore_mfs_describe( $sft_mfs_paid ) ),
			),
			array(
				'title' => __( 'We verify your payment', 'safestore-minimal' ),
				'text'  => __( 'We check the TrxID in our bKash / Nagad app — usually within a few working hours.', 'safestore-minimal' ),
			),
			array(
				'title' => __( 'We ship your order', 'safestore-minimal' ),
				'text'  => __( 'Packed and handed to the courier right after.', 'safestore-minimal' ),
			),
		);
	} elseif ( 'mobile' === $sft_pay_type ) {
		$sft_hours = function_exists( 'safestore_mfs_pay_hours' ) ? safestore_mfs_pay_hours() : 24;
		$sft_hours = (int) apply_filters( 'safestore_ty_prepay_hours', $sft_hours, $order );
		// Clone keeps the store timezone, so the deadline reads in local time.
		$sft_deadline = $sft_created ? wc_format_datetime( ( clone $sft_created )->modify( '+' . $sft_hours . ' hours' ), 'j M, g:i A' ) : '';

		$sft_pay_extra = '';
		if ( $sft_mfs ) {
			$sft_pay_extra = safestore_mfs_numbers_html( '' );
			$sft_limit     = safestore_mfs_limit_note( $order->get_total() );
			if ( $sft_limit ) {
				$sft_pay_extra .= '<p class="sft-mfs-note sft-mfs-note--warn">' . wp_kses( $sft_limit, array( 'strong' => array() ) ) . '</p>';
			}
		}

		$sft_steps = array(
			array(
				/* translators: %s: order total */
				'title' => sprintf( __( 'Send Money %s by bKash or Nagad', 'safestore-minimal' ), $sft_total_txt ),
				'text'  => $sft_deadline
					/* translators: 1: hours, 2: deadline date and time */
					? sprintf( __( 'Use <strong>Send Money</strong> to our personal number within %1$d hours — by %2$s.', 'safestore-minimal' ), $sft_hours, '<strong>' . esc_html( $sft_deadline ) . '</strong>' )
					/* translators: %d: hours */
					: sprintf( __( 'Use <strong>Send Money</strong> to our personal number within %d hours.', 'safestore-minimal' ), $sft_hours ),
				'extra' => $sft_pay_extra,
			),
			array(
				'title' => __( 'Submit your Transaction ID', 'safestore-minimal' ),
				'text'  => $sft_mfs
					? __( 'Copy the TrxID from your bKash / Nagad SMS and enter it here — or send it to us on WhatsApp.', 'safestore-minimal' )
					: __( 'Send us the TrxID from your bKash / Nagad SMS on WhatsApp.', 'safestore-minimal' ),
				'extra' => $sft_mfs ? safestore_mfs_trx_form_html( $order ) : '',
			),
			array(
				'title' => __( 'We verify and ship', 'safestore-minimal' ),
				'text'  => __( 'Once the payment is confirmed, we pack it and hand it to the courier.', 'safestore-minimal' ),
			),
		);
	} elseif ( 'cod' === $sft_pay_type ) {
		$sft_steps = array(
			array(
				'title' => __( 'We confirm your order', 'safestore-minimal' ),
				/* translators: %s: customer's phone number */
				'text'  => $sft_phone ? sprintf( __( 'We call or WhatsApp you on %s. Please keep your phone on.', 'safestore-minimal' ), '<strong>' . esc_html( $sft_phone ) . '</strong>' ) : __( 'We call or WhatsApp you. Please keep your phone on.', 'safestore-minimal' ),
			),
			array(
				'title' => __( 'We ship your order', 'safestore-minimal' ),
				'text'  => __( 'Packed and handed to the courier after confirmation.', 'safestore-minimal' ),
			),
			array(
				/* translators: %s: order total */
				'title' => sprintf( __( 'Pay %s cash on delivery', 'safestore-minimal' ), $sft_total_txt ),
				'text'  => __( 'Check the parcel, then pay the delivery person.', 'safestore-minimal' )
					. ( function_exists( 'safestore_cod_is_large_order' ) && safestore_cod_is_large_order( $order->get_total() ) ? ' ' . safestore_cod_large_order_note() : '' ),
			),
		);
	}

	$sft_show_gateway = '' !== $sft_gateway_html && ( empty( $sft_steps ) || $sft_gateway_is_interactive );

	$sft_hero_lead = array(
		'mobile' => $sft_trx_sent
			? __( 'We received your Transaction ID. We will verify the payment and ship your order.', 'safestore-minimal' )
			: __( 'One more step: Send Money with bKash or Nagad so we can ship it.', 'safestore-minimal' ),
		'cod'    => __( 'We will contact you shortly to confirm it. You pay when it arrives.', 'safestore-minimal' ),
		'paid'   => __( 'Your payment is confirmed. We are getting your order ready.', 'safestore-minimal' ),
		'other'  => __( 'We will contact you shortly to confirm it.', 'safestore-minimal' ),
	);
	?>

	<?php // ---------------------------------------------------------- 1. Hero ?>
	<section class="sft-ty-card sft-ty-hero" aria-labelledby="sft-ty-title">
		<span class="sft-ty-hero__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
		<div class="sft-ty-hero__body">
			<h1 class="sft-ty-hero__title" id="sft-ty-title">
				<?php
				if ( $sft_name ) {
					/* translators: %s: customer name */
					printf( esc_html__( 'Thank you, %s! Your order is placed.', 'safestore-minimal' ), esc_html( $sft_name ) );
				} else {
					esc_html_e( 'Thank you! Your order is placed.', 'safestore-minimal' );
				}
				?>
			</h1>
			<p class="sft-ty-hero__lead"><?php echo esc_html( $sft_hero_lead[ $sft_pay_type ] ?? $sft_hero_lead['other'] ); ?></p>

			<dl class="sft-ty-facts">
				<div class="sft-ty-fact sft-ty-fact--order">
					<dt><?php esc_html_e( 'Order number', 'safestore-minimal' ); ?></dt>
					<dd>
						<span class="sft-ty-fact__num">#<?php echo esc_html( $sft_number ); ?></span>
						<?php
						if ( function_exists( 'safestore_copy_button' ) ) {
							echo safestore_copy_button( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
								array(
									'value' => (string) $sft_number,
									'noun'  => __( 'Order number', 'safestore-minimal' ),
									'class' => 'sft-ty-copy',
								)
							);
						}
						?>
					</dd>
				</div>
				<?php if ( $sft_created ) : ?>
					<div class="sft-ty-fact">
						<dt><?php esc_html_e( 'Date', 'safestore-minimal' ); ?></dt>
						<dd><?php echo esc_html( wc_format_datetime( $sft_created, 'j M Y, g:i A' ) ); ?></dd>
					</div>
				<?php endif; ?>
				<div class="sft-ty-fact">
					<dt><?php esc_html_e( 'Total', 'safestore-minimal' ); ?></dt>
					<dd class="sft-ty-fact__total"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></dd>
				</div>
				<?php if ( $sft_pay_title ) : ?>
					<div class="sft-ty-fact">
						<dt><?php esc_html_e( 'Payment', 'safestore-minimal' ); ?></dt>
						<dd><?php echo wp_kses_post( $sft_pay_title ); ?></dd>
					</div>
				<?php endif; ?>
			</dl>
			<p class="sft-ty-hero__hint"><?php esc_html_e( 'Save your order number — you will need it to track or ask about this order.', 'safestore-minimal' ); ?></p>
		</div>
	</section>

	<div class="sft-ty-grid">

		<div class="sft-ty-main">

			<?php // ------------------------------------------------ 2. Next steps ?>
			<?php if ( ! empty( $sft_steps ) || $sft_show_gateway || 'paid' === $sft_pay_type ) : ?>
				<section class="sft-ty-card sft-ty-next sft-ty-next--<?php echo esc_attr( $sft_pay_type ); ?>" id="sft-ty-pay" aria-labelledby="sft-ty-next-title">
					<header class="sft-ty-card__head">
						<h2 class="sft-ty-card__title" id="sft-ty-next-title">
							<?php
							if ( 'mobile' === $sft_pay_type && ! $sft_trx_sent ) {
								esc_html_e( 'Next step: pay with bKash / Nagad', 'safestore-minimal' );
							} else {
								esc_html_e( 'What happens next', 'safestore-minimal' );
							}
							?>
						</h2>
						<?php if ( ( 'mobile' === $sft_pay_type && ! $sft_trx_sent ) || 'cod' === $sft_pay_type ) : ?>
							<div class="sft-ty-due">
								<span class="sft-ty-due__label">
									<?php 'cod' === $sft_pay_type ? esc_html_e( 'Pay on delivery', 'safestore-minimal' ) : esc_html_e( 'Amount to pay', 'safestore-minimal' ); ?>
								</span>
								<span class="sft-ty-due__amount"><?php echo esc_html( $sft_total_txt ); ?></span>
							</div>
						<?php endif; ?>
					</header>

					<?php
					$sft_notice = ( $sft_mfs && function_exists( 'safestore_mfs_form_notice' ) ) ? safestore_mfs_form_notice( $order ) : array();
					if ( $sft_notice ) :
						?>
						<p class="sft-mfs-alert sft-mfs-alert--<?php echo esc_attr( $sft_notice[0] ); ?>" role="<?php echo 'error' === $sft_notice[0] ? 'alert' : 'status'; ?>"><?php echo esc_html( $sft_notice[1] ); ?></p>
					<?php endif; ?>

					<ol class="sft-ty-steps">
						<li class="sft-ty-step is-done">
							<span class="sft-ty-step__dot" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
							<div class="sft-ty-step__body">
								<p class="sft-ty-step__title"><?php esc_html_e( 'Order placed', 'safestore-minimal' ); ?> <span class="screen-reader-text"><?php esc_html_e( '(done)', 'safestore-minimal' ); ?></span></p>
							</div>
						</li>
						<?php if ( 'paid' === $sft_pay_type ) : ?>
							<li class="sft-ty-step is-done">
								<span class="sft-ty-step__dot" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
								<div class="sft-ty-step__body">
									<p class="sft-ty-step__title"><?php esc_html_e( 'Payment confirmed', 'safestore-minimal' ); ?></p>
								</div>
							</li>
							<li class="sft-ty-step">
								<span class="sft-ty-step__dot" aria-hidden="true">3</span>
								<div class="sft-ty-step__body">
									<p class="sft-ty-step__title"><?php esc_html_e( 'We ship your order', 'safestore-minimal' ); ?></p>
								</div>
							</li>
						<?php endif; ?>
						<?php
						$sft_current = null;
						foreach ( $sft_steps as $sft_i => $sft_step ) :
							$sft_done = ! empty( $sft_step['done'] );
							$sft_cls  = $sft_done ? ' is-done' : '';
							if ( ! $sft_done && null === $sft_current ) {
								$sft_current = $sft_i;
								$sft_cls     = ' is-current';
							}
							?>
							<li class="sft-ty-step<?php echo esc_attr( $sft_cls ); ?>">
								<span class="sft-ty-step__dot" aria-hidden="true">
									<?php if ( $sft_done ) : ?>
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
									<?php else : ?>
										<?php echo (int) $sft_i + 2; ?>
									<?php endif; ?>
								</span>
								<div class="sft-ty-step__body">
									<p class="sft-ty-step__title"><?php echo esc_html( $sft_step['title'] ); ?></p>
									<p class="sft-ty-step__text"><?php echo wp_kses( $sft_step['text'], array( 'strong' => array() ) ); ?></p>
									<?php
									if ( ! empty( $sft_step['extra'] ) ) {
										echo $sft_step['extra']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped helpers in inc/mfs-payment.php.
									}
									?>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>

					<?php if ( $sft_show_gateway ) : ?>
						<div class="sft-ty-gateway"><?php echo $sft_gateway_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- gateway output, as WooCommerce prints it. ?></div>
					<?php endif; ?>

					<div class="sft-ty-help">
						<p class="sft-ty-help__text">
							<?php
							if ( 'mobile' === $sft_pay_type && ! $sft_trx_sent ) {
								esc_html_e( 'Prefer WhatsApp? Send us the TrxID there, or ask us anything about the payment.', 'safestore-minimal' );
							} elseif ( 'mobile' === $sft_pay_type ) {
								esc_html_e( 'Questions about your payment or order? We are happy to help.', 'safestore-minimal' );
							} elseif ( $sft_phone ) {
								/* translators: %s: customer's phone number */
								printf( esc_html__( 'Wrong phone number (%s)? Message us now so we can reach you.', 'safestore-minimal' ), esc_html( $sft_phone ) );
							} else {
								esc_html_e( 'Questions about your order? We are happy to help.', 'safestore-minimal' );
							}
							?>
						</p>
						<div class="sft-ty-actions">
							<?php if ( $sft_wa_url ) : ?>
								<a class="sft-ty-btn sft-ty-btn--wa" href="<?php echo esc_url( $sft_wa_url ); ?>" target="_blank" rel="noopener" data-sft-copy-skip>
									<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.79-1.47-1.76-1.64-2.05-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.5h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.75-.72 2-1.4.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35M12.05 21.5h-.01a9.4 9.4 0 0 1-4.8-1.31l-.34-.2-3.56.93.95-3.47-.22-.36A9.4 9.4 0 0 1 2.6 12.1c0-5.2 4.24-9.44 9.46-9.44a9.4 9.4 0 0 1 6.68 2.77 9.38 9.38 0 0 1 2.76 6.68c0 5.21-4.24 9.45-9.45 9.45m8.04-17.49A11.3 11.3 0 0 0 12.05.67C5.78.67.68 5.77.68 12.04c0 2 .52 3.96 1.52 5.68L.58 23.6l6.02-1.58a11.3 11.3 0 0 0 5.44 1.39h.01c6.27 0 11.37-5.1 11.37-11.37 0-3.04-1.18-5.9-3.33-8.04"/></svg>
									<?php esc_html_e( 'WhatsApp us', 'safestore-minimal' ); ?>
								</a>
							<?php endif; ?>
							<a class="sft-ty-btn sft-ty-btn--ghost" href="<?php echo esc_url( 'tel:+' . $sft_hotline ); ?>" data-sft-copy-skip>
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
								<?php
								/* translators: %s: hotline number */
								printf( esc_html__( 'Call %s', 'safestore-minimal' ), esc_html( $sft_hot_disp ) );
								?>
							</a>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<?php // ------------------------------------------ Delivery address ?>
			<?php
			$sft_addr = safestore_ty_address_parts( $order );
			$sft_has_address = ! empty( $sft_addr['street'] ) || '' !== $sft_addr['area'];
			// Same rule as WooCommerce's order/order-details.php (guests: both 0).
			$sft_show_customer = $order->get_user_id() === get_current_user_id();
			if ( $sft_show_customer && ( $sft_has_address || $sft_phone ) ) :
				$sft_eta = safestore_ty_is_dhaka( $order )
					? __( 'Inside Dhaka · usually within 24 hours', 'safestore-minimal' )
					: __( 'Outside Dhaka · 2–3 business days', 'safestore-minimal' );
				$sft_fix_url = function_exists( 'safestore_wa_link' )
					/* translators: %s: order number */
					? safestore_wa_link( 'primary', sprintf( __( 'Hi SafeStoreBD, please correct the delivery address for order #%s:', 'safestore-minimal' ), $sft_number ) . ' ' )
					: '';
				?>
				<section class="sft-ty-card sft-ty-address" aria-labelledby="sft-ty-address-title">
					<header class="sft-ty-address__head">
						<span class="sft-ty-address__badge" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M2.8 6.6h11.4v9.6H2.8z"/><path d="M14.2 10h3.4l3.6 3.3v2.9h-7z"/><circle cx="7" cy="18.4" r="1.7"/><circle cx="17.4" cy="18.4" r="1.7"/></svg></span>
						<div class="sft-ty-address__heading">
							<h2 class="sft-ty-card__title" id="sft-ty-address-title"><?php esc_html_e( 'Delivery address', 'safestore-minimal' ); ?></h2>
							<p class="sft-ty-address__sub"><?php esc_html_e( 'Please check it — the courier will deliver here.', 'safestore-minimal' ); ?></p>
						</div>
						<?php if ( $sft_has_address ) : ?>
							<p class="sft-ty-address__eta">
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
								<?php echo esc_html( $sft_eta ); ?>
							</p>
						<?php endif; ?>
					</header>

					<div class="sft-ty-address__label">
						<?php if ( '' !== $sft_addr['name'] || $sft_phone ) : ?>
							<div class="sft-ty-address__col sft-ty-address__col--who">
								<p class="sft-ty-address__kicker"><?php esc_html_e( 'Recipient', 'safestore-minimal' ); ?></p>
								<div class="sft-ty-address__person">
									<?php if ( '' !== $sft_addr['initials'] ) : ?>
										<span class="sft-ty-address__avatar" aria-hidden="true"><?php echo esc_html( $sft_addr['initials'] ); ?></span>
									<?php endif; ?>
									<div class="sft-ty-address__person-body">
										<?php if ( '' !== $sft_addr['name'] ) : ?>
											<p class="sft-ty-address__name"><?php echo esc_html( $sft_addr['name'] ); ?></p>
										<?php endif; ?>
										<?php if ( '' !== $sft_addr['company'] ) : ?>
											<p class="sft-ty-address__company"><?php echo esc_html( $sft_addr['company'] ); ?></p>
										<?php endif; ?>
										<?php if ( $sft_phone ) : ?>
											<p class="sft-ty-address__phone">
												<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
												<?php echo esc_html( $sft_phone ); ?>
											</p>
										<?php endif; ?>
									</div>
								</div>
							</div>
						<?php endif; ?>

						<?php if ( $sft_has_address ) : ?>
							<div class="sft-ty-address__col sft-ty-address__col--where">
								<p class="sft-ty-address__kicker"><?php esc_html_e( 'Address', 'safestore-minimal' ); ?></p>
								<address class="sft-ty-address__where">
									<svg class="sft-ty-address__pin" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.6"/></svg>
									<span class="sft-ty-address__lines">
										<?php foreach ( $sft_addr['street'] as $sft_line ) : ?>
											<span class="sft-ty-address__street"><?php echo esc_html( $sft_line ); ?></span>
										<?php endforeach; ?>
										<?php if ( '' !== $sft_addr['area'] ) : ?>
											<span class="sft-ty-address__area"><?php echo esc_html( $sft_addr['area'] ); ?></span>
										<?php endif; ?>
									</span>
								</address>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( $sft_fix_url ) : ?>
						<p class="sft-ty-address__fix">
							<span class="sft-ty-address__fix-q"><?php esc_html_e( 'Address wrong?', 'safestore-minimal' ); ?></span>
							<a href="<?php echo esc_url( $sft_fix_url ); ?>" target="_blank" rel="noopener" data-sft-copy-skip>
								<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.79-1.47-1.76-1.64-2.05-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.5h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.75-.72 2-1.4.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35M12.05 21.5h-.01a9.4 9.4 0 0 1-4.8-1.31l-.34-.2-3.56.93.95-3.47-.22-.36A9.4 9.4 0 0 1 2.6 12.1c0-5.2 4.24-9.44 9.46-9.44a9.4 9.4 0 0 1 6.68 2.77 9.38 9.38 0 0 1 2.76 6.68c0 5.21-4.24 9.45-9.45 9.45m8.04-17.49A11.3 11.3 0 0 0 12.05.67C5.78.67.68 5.77.68 12.04c0 2 .52 3.96 1.52 5.68L.58 23.6l6.02-1.58a11.3 11.3 0 0 0 5.44 1.39h.01c6.27 0 11.37-5.1 11.37-11.37 0-3.04-1.18-5.9-3.33-8.04"/></svg>
								<?php esc_html_e( 'Tell us on WhatsApp before we ship', 'safestore-minimal' ); ?>
							</a>
						</p>
					<?php endif; ?>
				</section>
			<?php endif; ?>

		</div>

		<?php // ------------------------------------------------ 3. Order summary ?>
		<aside class="sft-ty-side">
			<section class="sft-ty-card sft-ty-summary" aria-labelledby="sft-ty-summary-title">
				<?php do_action( 'woocommerce_order_details_before_order_table', $order ); ?>
				<h2 class="sft-ty-card__title" id="sft-ty-summary-title">
					<?php esc_html_e( 'Order summary', 'safestore-minimal' ); ?>
					<span class="sft-ty-card__count">
						<?php
						$sft_qty = $order->get_item_count();
						/* translators: %d: number of items */
						printf( esc_html( _n( '%d item', '%d items', $sft_qty, 'safestore-minimal' ) ), (int) $sft_qty );
						?>
					</span>
				</h2>

				<ul class="sft-ty-items">
					<?php
					foreach ( $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) ) as $sft_item_id => $sft_item ) :
						if ( ! apply_filters( 'woocommerce_order_item_visible', true, $sft_item ) ) {
							continue;
						}
						$sft_product = $sft_item->get_product();
						$sft_link    = ( $sft_product && $sft_product->is_visible() ) ? $sft_product->get_permalink( $sft_item ) : '';
						$sft_thumb   = $sft_product ? $sft_product->get_image( 'woocommerce_gallery_thumbnail', array( 'loading' => 'lazy' ) ) : wc_placeholder_img( 'woocommerce_gallery_thumbnail' );
						?>
						<li class="sft-ty-item">
							<span class="sft-ty-item__img">
								<?php echo wp_kses_post( $sft_thumb ); ?>
								<span class="sft-ty-item__qty" aria-hidden="true"><?php echo (int) $sft_item->get_quantity(); ?></span>
							</span>
							<div class="sft-ty-item__info">
								<p class="sft-ty-item__name">
									<?php
									$sft_name_html = $sft_link ? sprintf( '<a href="%s">%s</a>', esc_url( $sft_link ), esc_html( $sft_item->get_name() ) ) : esc_html( $sft_item->get_name() );
									echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $sft_name_html, $sft_item, $sft_link ? true : false ) );
									?>
								</p>
								<?php
								do_action( 'woocommerce_order_item_meta_start', $sft_item_id, $sft_item, $order, false );
								wc_display_item_meta( $sft_item, array( 'before' => '<ul class="sft-ty-item__meta"><li>', 'separator' => '</li><li>', 'after' => '</li></ul>' ) );
								do_action( 'woocommerce_order_item_meta_end', $sft_item_id, $sft_item, $order, false );
								?>
								<p class="sft-ty-item__unit">
									<?php
									/* translators: 1: quantity, 2: unit price */
									printf( esc_html__( 'Qty %1$d × %2$s', 'safestore-minimal' ), (int) $sft_item->get_quantity(), esc_html( safestore_ty_plain_price( $order->get_item_subtotal( $sft_item, false, true ), $order ) ) );
									?>
								</p>
							</div>
							<span class="sft-ty-item__price"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $sft_item ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>

				<table class="sft-ty-totals">
					<?php foreach ( safestore_ty_total_rows( $order ) as $sft_key => $sft_row ) : ?>
						<tr class="sft-ty-totals__row sft-ty-totals__row--<?php echo esc_attr( $sft_key ); ?>">
							<th scope="row"><?php echo esc_html( $sft_row['label'] ); ?></th>
							<td><?php echo wp_kses_post( $sft_row['value'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</table>

				<?php if ( $order->get_customer_note() ) : ?>
					<p class="sft-ty-note"><strong><?php esc_html_e( 'Your note:', 'safestore-minimal' ); ?></strong> <?php echo wp_kses( nl2br( wc_wptexturize_order_note( $order->get_customer_note() ) ), array( 'br' => array() ) ); ?></p>
				<?php endif; ?>
				<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
			</section>

			<div class="sft-ty-actions sft-ty-actions--stack">
				<a class="sft-ty-btn sft-ty-btn--primary" href="<?php echo esc_url( $sft_shop_url ? $sft_shop_url : home_url( '/' ) ); ?>"><?php esc_html_e( 'Continue shopping', 'safestore-minimal' ); ?></a>
				<a class="sft-ty-btn sft-ty-btn--ghost" href="<?php echo esc_url( home_url( '/track-order/' ) ); ?>"><?php esc_html_e( 'Track this order', 'safestore-minimal' ); ?></a>
			</div>
		</aside>

	</div>

	<?php
	// Core thank-you hook (analytics, pixels, plugins). WooCommerce hangs its
	// order-details table + customer details on it; this page already shows
	// both, so that one callback is removed for this request only.
	remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', 10 );
	do_action( 'woocommerce_thankyou', $order->get_id() );
	?>

</div>

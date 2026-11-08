<?php
/**
 * Order landings — everything in the order <form> after the page's own pick
 * (size / quantity on the product landing, the vest list on a category
 * landing): delivery details, order summary, payment method (Cash on Delivery
 * or bKash / Nagad "Pay now"), anti-abuse fields, submit, notes, WhatsApp.
 * The template opens <form data-spl-form id="spl-order"> and closes it.
 * Behaviour: js/landing-order.js. Server: inc/product-landing-order.php.
 *
 * get_template_part( 'templates/landing/order-form', null, array(
 *   'cfg'   => order JSON from safestore_lp_order_cfg() (ship / flat / pay),
 *   'lines' => summary lines: array( array( 'name' =>, 'note' =>, 'amount' => ) ) —
 *              their note / amount carry data-spl-line / data-spl-sub for the page script,
 *   'empty' => text shown while 'lines' is empty (the page script fills [data-spl-lines]),
 *   'total' => initial total (number),
 *   'wa'    => WhatsApp href with the order text,
 *   'loc'   => tracking location of that WhatsApp button (default "order"),
 * ) )
 */

defined( 'ABSPATH' ) || exit;

$lp_cfg   = isset( $args['cfg'] ) ? (array) $args['cfg'] : array();
$lp_lines = isset( $args['lines'] ) ? (array) $args['lines'] : array();
$lp_vals  = safestore_lp_form_values();
$lp_c     = safestore_lp_contact();
$lp_wa    = ! empty( $args['wa'] ) ? $args['wa'] : $lp_c['wa'];
$lp_total = safestore_pl_taka( isset( $args['total'] ) ? (float) $args['total'] : 0 );
$lp_rates = ! empty( $lp_cfg['ship'] ) || ! empty( $lp_cfg['flat'] );
// Payment: right after the total, right before the button — the buyer already knows what they
// pay and only picks how. Cash on Delivery is pre-selected; "Pay now" opens bKash / Nagad inline.
$lp_mfs = ! empty( $lp_cfg['pay']['mfs'] ) && function_exists( 'safestore_mfs_accounts' );
?>
<div class="spl-ship">
	<p class="spl-ship__title">Delivery details</p>
	<p class="spl-ship__sub">We will call this number to confirm your order</p>
	<p class="lp-alert" role="alert" tabindex="-1" data-spl-alert hidden></p>
	<div class="spl-ship__grid">
		<?php
		foreach ( safestore_pl_form_fields() as $lp_k => $lp_f ) :
			$lp_id   = 'spl-' . str_replace( '_', '-', $lp_k );
			$lp_opts = array();
			if ( 'billing_state' === $lp_k ) {
				$lp_opts = safestore_pl_districts();
			} elseif ( 'billing_city' === $lp_k && '' !== $lp_vals['billing_state'] ) {
				$lp_list = safestorebd_thanas_for( $lp_vals['billing_state'] );
				$lp_opts = $lp_list ? array_combine( $lp_list, $lp_list ) : array();
			}
			// An empty thana list can't be "required" without JS (nothing to pick); the server checks it.
			$lp_att = sprintf(
				'id="%1$s" name="%2$s" autocomplete="%3$s"%5$s aria-describedby="%1$s-err" data-err="%4$s" data-spl-field',
				esc_attr( $lp_id ),
				esc_attr( $lp_k ),
				esc_attr( $lp_f['auto'] ),
				esc_attr( $lp_f['err'] ),
				'billing_city' === $lp_k && ! $lp_opts ? '' : ' required'
			);
			?>
			<div class="lp-field spl-f--<?php echo esc_attr( $lp_f['type'] ); ?>">
				<label for="<?php echo esc_attr( $lp_id ); ?>"><?php echo esc_html( $lp_f['label'] ); ?> <span class="lp-req" aria-hidden="true">*</span></label>
				<?php if ( 'select' === $lp_f['type'] ) : ?>
					<select <?php echo $lp_att; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above ?>>
						<option value=""><?php echo esc_html( 'billing_city' === $lp_k && $lp_opts ? 'Select thana / upazila' : $lp_f['ph'] ); ?></option>
						<?php foreach ( $lp_opts as $lp_v => $lp_l ) : ?>
							<option value="<?php echo esc_attr( $lp_v ); ?>"<?php selected( $lp_vals[ $lp_k ], (string) $lp_v ); ?>><?php echo esc_html( $lp_l ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php elseif ( 'textarea' === $lp_f['type'] ) : ?>
					<textarea <?php echo $lp_att; // phpcs:ignore WordPress.Security.EscapeOutput ?> rows="2" placeholder="<?php echo esc_attr( $lp_f['ph'] ); ?>"><?php echo esc_textarea( $lp_vals[ $lp_k ] ); ?></textarea>
				<?php else : ?>
					<input <?php echo $lp_att; // phpcs:ignore WordPress.Security.EscapeOutput ?> type="<?php echo esc_attr( $lp_f['type'] ); ?>"<?php echo 'tel' === $lp_f['type'] ? ' inputmode="tel" maxlength="16"' : ''; ?> placeholder="<?php echo esc_attr( $lp_f['ph'] ); ?>" value="<?php echo esc_attr( $lp_vals[ $lp_k ] ); ?>">
				<?php endif; ?>
				<p class="lp-err" id="<?php echo esc_attr( $lp_id ); ?>-err" hidden></p>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="spl-sum" aria-live="polite">
		<div class="spl-sum__lines" data-spl-lines>
			<?php if ( ! $lp_lines && ! empty( $args['empty'] ) ) : ?>
				<p class="spl-sum__empty"><?php echo esc_html( $args['empty'] ); ?></p>
			<?php endif; ?>
			<?php foreach ( $lp_lines as $lp_line ) : ?>
				<p><span><?php echo esc_html( $lp_line['name'] ); ?> <small data-spl-line><?php echo esc_html( isset( $lp_line['note'] ) ? $lp_line['note'] : '' ); ?></small></span><b data-spl-sub><?php echo esc_html( safestore_pl_taka( $lp_line['amount'] ) ); ?></b></p>
			<?php endforeach; ?>
		</div>
		<p class="spl-sum__ship"><span>Delivery charge <small data-spl-zone></small></span><b data-spl-ship><?php echo $lp_rates ? 'Select district' : 'Added at checkout'; ?></b></p>
		<p class="spl-sum__tot"><span>Total</span><b data-spl-total><?php echo esc_html( $lp_total ); ?></b></p>
	</div>
</div>

<fieldset class="spl-pay" data-spl-pay>
	<legend class="spl-ship__title">Payment method</legend>
	<label class="spl-pay__opt">
		<input type="radio" name="spl_payment" value="cod" checked data-spl-pay-opt>
		<span class="spl-pay__body">
			<span class="spl-pay__name"><?php echo safestore_pl_icon( 'card', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> Cash on Delivery</span>
			<span class="spl-pay__desc">Pay when you receive it — check the product first.</span>
		</span>
	</label>
	<?php if ( $lp_mfs ) : ?>
		<label class="spl-pay__opt">
			<input type="radio" name="spl_payment" value="mfs" data-spl-pay-opt>
			<span class="spl-pay__body">
				<span class="spl-pay__name"><span class="spl-pay__wallet spl-pay__wallet--bkash">bKash</span><span class="spl-pay__wallet spl-pay__wallet--nagad">Nagad</span> Pay now</span>
				<span class="spl-pay__desc">Pay online now — nothing to pay at delivery.</span>
			</span>
		</label>
		<div class="spl-mfs" data-spl-mfs hidden>
			<p class="spl-mfs__lead"><?php echo esc_html( function_exists( 'safestore_mfs_copy' ) ? safestore_mfs_copy( 'lead' ) : 'Send Money to one of our numbers:' ); ?></p>
			<ul class="spl-mfs__nums">
				<?php foreach ( safestore_mfs_accounts() as $lp_acc ) : ?>
					<li class="spl-mfs__num spl-mfs__num--<?php echo esc_attr( $lp_acc['key'] ); ?>">
						<span class="spl-mfs__brand"><?php echo esc_html( $lp_acc['label'] ); ?></span>
						<span class="spl-mfs__digits"><?php echo esc_html( safestore_mfs_format_number( $lp_acc['number'] ) ); ?></span>
						<button type="button" class="spl-mfs__copy" data-spl-copy="<?php echo esc_attr( $lp_acc['number'] ); ?>" aria-label="<?php echo esc_attr( 'Copy ' . $lp_acc['label'] . ' number' ); ?>">Copy</button>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="spl-mfs__amount">Amount to send <b data-spl-total><?php echo esc_html( $lp_total ); ?></b></p>
			<ol class="spl-mfs__steps">
				<li><?php echo wp_kses( function_exists( 'safestore_mfs_copy' ) ? safestore_mfs_copy( 'step1' ) : 'Open the bKash or Nagad app and tap <strong>Send Money</strong>.', array( 'strong' => array() ) ); ?></li>
				<li>Send exactly the amount above to one of the numbers.</li>
				<li>Enter the Transaction ID (TrxID) from your SMS below.</li>
			</ol>
			<div class="spl-mfs__fields">
				<fieldset class="spl-mfs__wallets">
					<legend>Paid with</legend>
					<?php foreach ( safestore_mfs_wallet_labels() as $lp_wk => $lp_wl ) : ?>
						<label><input type="radio" name="sft_mfs_wallet" value="<?php echo esc_attr( $lp_wk ); ?>"<?php checked( 'bkash', $lp_wk ); ?>> <?php echo esc_html( $lp_wl ); ?></label>
					<?php endforeach; ?>
				</fieldset>
				<div class="lp-field">
					<label for="spl-mfs-sender">Your bKash / Nagad number</label>
					<input id="spl-mfs-sender" name="sft_mfs_sender" type="tel" inputmode="tel" maxlength="16" placeholder="01XXXXXXXXX" autocomplete="tel" aria-describedby="spl-mfs-sender-err" data-err="Enter the 11-digit number you sent the money from">
					<p class="lp-err" id="spl-mfs-sender-err" hidden></p>
				</div>
				<div class="lp-field">
					<label for="spl-mfs-trxid">Transaction ID (TrxID)</label>
					<input id="spl-mfs-trxid" name="sft_mfs_trxid" type="text" maxlength="16" placeholder="e.g. 9BG7X2K4LM" autocapitalize="characters" autocomplete="off" spellcheck="false" aria-describedby="spl-mfs-trxid-err" data-err="The TrxID is 8–12 letters and numbers from your SMS">
					<p class="lp-err" id="spl-mfs-trxid-err" hidden></p>
				</div>
			</div>
			<p class="spl-mfs__later">Not paid yet? Leave the TrxID empty and place the order — pay within <?php echo (int) ( function_exists( 'safestore_mfs_pay_hours' ) ? safestore_mfs_pay_hours() : 24 ); ?> hours. The next page shows the numbers again.</p>
		</div>
	<?php endif; ?>
</fieldset>

<p class="lp-hp" aria-hidden="true"><label>Website <input type="text" name="spl_website" tabindex="-1" autocomplete="off"></label></p>
<input type="hidden" name="spl_token" value="" data-spl-token>
<wc-order-attribution-inputs></wc-order-attribution-inputs>

<button class="lp-btn lp-btn--amber lp-btn--lg lp-btn--block spl-cta" type="submit" data-spl-submit>
	<span class="lp-spin" aria-hidden="true"></span>
	<span data-spl-submit-label>Confirm order</span>
</button>
<p class="lp-note"><span data-spl-note="cod">We'll call you to confirm the order · Check the product, then pay</span><span data-spl-note="mfs" hidden>We'll verify your payment and call you to confirm the order</span><?php if ( function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) && wc_terms_and_conditions_checkbox_enabled() ) : ?><br>By placing the order you agree to our <a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>">Terms</a><?php endif; ?></p>
<a class="lp-btn lp-btn--outline lp-btn--block" href="<?php echo esc_url( $lp_wa ); ?>" target="_blank" rel="noopener noreferrer" data-spl-loc="<?php echo esc_attr( ! empty( $args['loc'] ) ? $args['loc'] : 'order' ); ?>" data-spl-wa>
	<?php echo $lp_c['wa_icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	Order on WhatsApp
</a>

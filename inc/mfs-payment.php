<?php
/**
 * bKash / Nagad "Send Money" payment (personal numbers).
 *
 * The store takes mobile-wallet payments with WooCommerce's offline
 * "Check payments" gateway (id `cheque`), renamed. This file turns it into a
 * proper BD Send Money flow, version-controlled instead of living in the
 * gateway settings:
 *
 *   Checkout   – both numbers with copy buttons, the exact amount, three steps,
 *                and optional "wallet / sender number / TrxID" fields (pay first
 *                and enter the TrxID, or place the order and pay within 24 h).
 *   Thank-you  – woocommerce/checkout/thankyou.php shows the numbers again and
 *                a TrxID form (handled here) when no TrxID was given.
 *   Admin/mail – TrxID, wallet and sender number on the order screen and in
 *                order emails; every submission adds an order note.
 *
 * Numbers: safestore_mfs_accounts() (filter `safestore_mfs_accounts`).
 * Box markup: woocommerce/checkout/payment-method.php → safestore_mfs_payment_box().
 * Styles: css/mfs-pay.css (checkout + order received).
 *
 * @package safestore-minimal
 */

defined( 'ABSPATH' ) || exit;

/** Gateway that carries bKash / Nagad payments. */
function safestore_mfs_gateway_id() {
	return (string) apply_filters( 'safestore_mfs_gateway_id', 'cheque' );
}

/**
 * Receiving accounts. Personal numbers → customers use "Send Money".
 *
 * @return array[] key, label, number (11 digits).
 */
function safestore_mfs_accounts() {
	return apply_filters(
		'safestore_mfs_accounts',
		array(
			array(
				'key'    => 'bkash',
				'label'  => 'bKash',
				'number' => '01761699627',
			),
			array(
				'key'    => 'nagad',
				'label'  => 'Nagad',
				'number' => '01558990209',
			),
		)
	);
}

/** Labels keyed by wallet: array( 'bkash' => 'bKash', ... ). */
function safestore_mfs_wallet_labels() {
	$out = array();
	foreach ( safestore_mfs_accounts() as $acc ) {
		$out[ $acc['key'] ] = $acc['label'];
	}
	return $out;
}

/** "01761-699627". */
function safestore_mfs_format_number( $digits ) {
	$digits = preg_replace( '/\D/', '', (string) $digits );
	return 11 === strlen( $digits ) ? substr( $digits, 0, 5 ) . '-' . substr( $digits, 5 ) : $digits;
}

/**
 * Receiving account type — the single switch for every bKash / Nagad line.
 *
 * 'personal' (default): customers use "Send Money" (what the numbers are today).
 * 'merchant': customers use "Payment" — only flip this once the numbers really
 * are bKash / Nagad merchant accounts, otherwise payments fail in the app.
 *
 * Set with define( 'SFT_MFS_ACCOUNT_TYPE', 'merchant' ); in wp-config.php or the
 * `safestore_mfs_account_type` filter.
 */
function safestore_mfs_account_type() {
	$type = defined( 'SFT_MFS_ACCOUNT_TYPE' ) ? (string) SFT_MFS_ACCOUNT_TYPE : 'personal';
	$type = (string) apply_filters( 'safestore_mfs_account_type', $type );
	return 'merchant' === $type ? 'merchant' : 'personal';
}

/**
 * Customer-facing copy that depends on the account type. One place for the
 * checkout gateway, pay box, emails, product page and FAQ.
 *
 * @param string $key Copy key.
 * @return string Translated string (may contain printf placeholders / <strong>).
 */
function safestore_mfs_copy( $key ) {
	$merchant = 'merchant' === safestore_mfs_account_type();
	$copy     = $merchant
		? array(
			'method'       => __( 'Payment', 'safestore-minimal' ),
			'title'        => __( 'bKash / Nagad (Merchant Payment)', 'safestore-minimal' ),
			/* translators: %s: "bKash: 01761-699627 · Nagad: 01558-990209" */
			'description'  => __( 'Pay to our merchant number with “Payment” — %s — then enter the Transaction ID.', 'safestore-minimal' ),
			/* translators: 1: wallet numbers, 2: hours */
			'instructions' => __( 'Pay by bKash / Nagad “Payment” to our merchant number — %1$s — within %2$d hours, using the exact order total. Then send us the Transaction ID (TrxID) from your SMS on WhatsApp: 01811-892291. Your order ships once the payment is confirmed.', 'safestore-minimal' ),
			'num_type'     => __( 'Merchant · Payment', 'safestore-minimal' ),
			'lead'         => __( 'Pay to one of our merchant numbers:', 'safestore-minimal' ),
			'step1'        => __( 'Open the bKash or Nagad app and tap <strong>Payment</strong>.', 'safestore-minimal' ),
			'pdp_note'     => __( 'bKash / Nagad: pay to our merchant number and enter the TrxID at checkout.', 'safestore-minimal' ),
		)
		: array(
			'method'       => __( 'Send Money', 'safestore-minimal' ),
			'title'        => __( 'bKash / Nagad (Send Money)', 'safestore-minimal' ),
			/* translators: %s: "bKash: 01761-699627 · Nagad: 01558-990209" */
			'description'  => __( 'Send Money to our personal number — %s — then enter the Transaction ID.', 'safestore-minimal' ),
			/* translators: 1: wallet numbers, 2: hours */
			'instructions' => __( 'Pay by bKash / Nagad Send Money to our personal number — %1$s — within %2$d hours, using the exact order total. Then send us the Transaction ID (TrxID) from your SMS on WhatsApp: 01811-892291. Your order ships once the payment is confirmed.', 'safestore-minimal' ),
			'num_type'     => __( 'Personal · Send Money', 'safestore-minimal' ),
			'lead'         => __( 'Send Money to one of our personal numbers:', 'safestore-minimal' ),
			'step1'        => __( 'Open the bKash or Nagad app and tap <strong>Send Money</strong>.', 'safestore-minimal' ),
			'pdp_note'     => __( 'bKash / Nagad: Send Money to our personal number and enter the TrxID at checkout.', 'safestore-minimal' ),
		);

	$copy = (array) apply_filters( 'safestore_mfs_copy', $copy, $merchant ? 'merchant' : 'personal' );
	return isset( $copy[ $key ] ) ? (string) $copy[ $key ] : '';
}

/** Hours the customer has to pay after ordering. */
function safestore_mfs_pay_hours() {
	return (int) apply_filters( 'safestore_mfs_pay_hours', 24 );
}

/**
 * Personal-account Send Money cap per day (Bangladesh Bank, March 2025:
 * ৳50,000 / day, ৳3 lakh / month). Larger orders get a "split it or ask for a
 * bank transfer" note.
 */
function safestore_mfs_daily_limit() {
	return (float) apply_filters( 'safestore_mfs_daily_limit', 50000 );
}

/** Plain "৳11,830". */
function safestore_mfs_plain_price( $amount ) {
	return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
}

/** 017XXXXXXXX from "+880 17…", "8801…", "017-…"; '' when not a BD mobile. */
function safestore_mfs_clean_mobile( $raw ) {
	$d = preg_replace( '/\D/', '', (string) $raw );
	if ( 13 === strlen( $d ) && 0 === strpos( $d, '880' ) ) {
		$d = substr( $d, 2 );
	}
	return preg_match( '/^01[3-9]\d{8}$/', $d ) ? $d : '';
}

/** Upper-case TrxID without spaces; '' when it does not look like one. */
function safestore_mfs_clean_trxid( $raw ) {
	$t = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $raw ) );
	// bKash TrxIDs are 10 characters, Nagad 8; accept 8–12 letters / digits.
	return preg_match( '/^[A-Z0-9]{8,12}$/', $t ) ? $t : '';
}

/** Saved payment details of an order. */
function safestore_mfs_order_payment( $order ) {
	return array(
		'wallet' => (string) $order->get_meta( '_sft_mfs_wallet' ),
		'sender' => (string) $order->get_meta( '_sft_mfs_sender' ),
		'trxid'  => (string) $order->get_meta( '_sft_mfs_trxid' ),
	);
}

/* -------------------------------------------------------------------------
 * Gateway copy — title, fallback description, email / thank-you instructions.
 * Overrides WooCommerce → Settings → Payments → Check payments.
 * ---------------------------------------------------------------------- */
add_filter(
	'option_woocommerce_cheque_settings',
	static function ( $settings ) {
		if ( 'cheque' !== safestore_mfs_gateway_id() ) {
			return $settings;
		}
		$settings = is_array( $settings ) ? $settings : array();

		$lines = array();
		foreach ( safestore_mfs_accounts() as $acc ) {
			$lines[] = $acc['label'] . ': ' . safestore_mfs_format_number( $acc['number'] );
		}
		$numbers = implode( ' · ', $lines );

		$settings['title']        = safestore_mfs_copy( 'title' );
		$settings['description']  = sprintf( safestore_mfs_copy( 'description' ), $numbers );
		$settings['instructions'] = sprintf( safestore_mfs_copy( 'instructions' ), $numbers, safestore_mfs_pay_hours() );
		return $settings;
	}
);

/* -------------------------------------------------------------------------
 * Pay box (checkout + thank-you).
 * ---------------------------------------------------------------------- */

/**
 * Numbers card: wallet badge, number, copy button.
 *
 * @param string $amount_text Plain amount to send ("৳11,830").
 */
function safestore_mfs_numbers_html( $amount_text ) {
	ob_start();
	?>
	<div class="sft-mfs-numbers">
		<?php foreach ( safestore_mfs_accounts() as $acc ) : ?>
			<div class="sft-mfs-num sft-mfs-num--<?php echo esc_attr( $acc['key'] ); ?>">
				<span class="sft-mfs-num__brand"><?php echo esc_html( $acc['label'] ); ?></span>
				<span class="sft-mfs-num__value">
					<span class="sft-mfs-num__digits"><?php echo esc_html( safestore_mfs_format_number( $acc['number'] ) ); ?></span>
					<span class="sft-mfs-num__type"><?php echo esc_html( safestore_mfs_copy( 'num_type' ) ); ?></span>
				</span>
				<?php
				if ( function_exists( 'safestore_copy_button' ) ) {
					echo safestore_copy_button( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
						array(
							'value' => $acc['number'],
							/* translators: %s: bKash / Nagad */
							'noun'  => sprintf( __( '%s number', 'safestore-minimal' ), $acc['label'] ),
							'class' => 'sft-mfs-copy',
						)
					);
				}
				?>
			</div>
		<?php endforeach; ?>
		<?php if ( '' !== $amount_text ) : ?>
			<div class="sft-mfs-amount">
				<span><?php esc_html_e( 'Amount to send', 'safestore-minimal' ); ?></span>
				<strong><?php echo esc_html( $amount_text ); ?></strong>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/** "Over ৳50,000" note, or ''. */
function safestore_mfs_limit_note( $amount ) {
	$limit = safestore_mfs_daily_limit();
	// The daily cap is a personal-account Send Money rule.
	if ( 'personal' !== safestore_mfs_account_type() || $limit <= 0 || (float) $amount <= $limit ) {
		return '';
	}
	return sprintf(
		/* translators: %s: daily limit, e.g. ৳50,000 */
		__( '<strong>Large amount:</strong> Send Money from a personal account is limited to %s per day. Send it in parts, or WhatsApp us for a bank transfer.', 'safestore-minimal' ),
		esc_html( safestore_mfs_plain_price( $limit ) )
	);
}

/**
 * TrxID fields. Same markup on checkout and on the thank-you form.
 *
 * @param string $prefix  Field id / name prefix.
 * @param array  $values  wallet, sender, trxid.
 * @param bool   $required Mark TrxID + sender as required.
 */
function safestore_mfs_fields_html( $prefix, $values = array(), $required = false ) {
	$values  = wp_parse_args( $values, array( 'wallet' => '', 'sender' => '', 'trxid' => '' ) );
	$wallets = safestore_mfs_wallet_labels();
	$wallet  = isset( $wallets[ $values['wallet'] ] ) ? $values['wallet'] : (string) key( $wallets );
	$req     = $required ? ' required' : '';
	ob_start();
	?>
	<div class="sft-mfs-fields">
		<fieldset class="sft-mfs-wallets">
			<legend><?php esc_html_e( 'Paid with', 'safestore-minimal' ); ?></legend>
			<?php foreach ( $wallets as $key => $label ) : ?>
				<label class="sft-mfs-wallet sft-mfs-wallet--<?php echo esc_attr( $key ); ?>">
					<input type="radio" id="<?php echo esc_attr( $prefix . '_wallet_' . $key ); ?>" name="<?php echo esc_attr( $prefix . '_wallet' ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $wallet, $key ); ?> />
					<span><?php echo esc_html( $label ); ?></span>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<p class="sft-mfs-field">
			<label for="<?php echo esc_attr( $prefix . '_sender' ); ?>"><?php esc_html_e( 'Your bKash / Nagad number', 'safestore-minimal' ); ?></label>
			<input type="tel" class="input-text" id="<?php echo esc_attr( $prefix . '_sender' ); ?>" name="<?php echo esc_attr( $prefix . '_sender' ); ?>" value="<?php echo esc_attr( $values['sender'] ); ?>" placeholder="01XXXXXXXXX" inputmode="numeric" autocomplete="tel" maxlength="16"<?php echo esc_attr( $req ); ?> />
		</p>
		<p class="sft-mfs-field">
			<label for="<?php echo esc_attr( $prefix . '_trxid' ); ?>"><?php esc_html_e( 'Transaction ID (TrxID)', 'safestore-minimal' ); ?></label>
			<input type="text" class="input-text" id="<?php echo esc_attr( $prefix . '_trxid' ); ?>" name="<?php echo esc_attr( $prefix . '_trxid' ); ?>" value="<?php echo esc_attr( $values['trxid'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. 9BG7X2K4LM', 'safestore-minimal' ); ?>" autocapitalize="characters" autocomplete="off" spellcheck="false" minlength="8" maxlength="16" pattern="[A-Za-z0-9 \-]{8,16}" title="<?php esc_attr_e( '8–12 letters and numbers from your bKash / Nagad SMS', 'safestore-minimal' ); ?>"<?php echo esc_attr( $req ); ?> />
		</p>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Checkout payment box for the bKash / Nagad gateway.
 * Called from woocommerce/checkout/payment-method.php.
 */
function safestore_mfs_payment_box() {
	$total = ( function_exists( 'WC' ) && WC()->cart ) ? (float) WC()->cart->get_total( 'edit' ) : 0;
	$text  = $total > 0 ? safestore_mfs_plain_price( $total ) : '';

	// Keep what the customer typed across WooCommerce's checkout refreshes and
	// after a validation error (checkout.js also restores by input id).
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- display only.
	$values = array(
		'wallet' => isset( $_POST['sft_mfs_wallet'] ) ? sanitize_key( wp_unslash( $_POST['sft_mfs_wallet'] ) ) : '',
		'sender' => isset( $_POST['sft_mfs_sender'] ) ? sanitize_text_field( wp_unslash( $_POST['sft_mfs_sender'] ) ) : '',
		'trxid'  => isset( $_POST['sft_mfs_trxid'] ) ? sanitize_text_field( wp_unslash( $_POST['sft_mfs_trxid'] ) ) : '',
	);
	// phpcs:enable
	?>
	<div class="sft-mfs">
		<p class="sft-mfs__lead"><?php echo esc_html( safestore_mfs_copy( 'lead' ) ); ?></p>
		<?php echo safestore_mfs_numbers_html( $text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>

		<ol class="sft-mfs-howto">
			<li><?php echo wp_kses( safestore_mfs_copy( 'step1' ), array( 'strong' => array() ) ); ?></li>
			<li>
				<?php
				echo $text
					/* translators: %s: amount */
					? wp_kses( sprintf( __( 'Send exactly <strong>%s</strong> to the number above.', 'safestore-minimal' ), esc_html( $text ) ), array( 'strong' => array() ) )
					: esc_html__( 'Send the exact order total to the number above.', 'safestore-minimal' );
				?>
			</li>
			<li><?php esc_html_e( 'Enter the Transaction ID (TrxID) from your SMS below.', 'safestore-minimal' ); ?></li>
		</ol>

		<?php
		$limit = safestore_mfs_limit_note( $total );
		if ( $limit ) {
			echo '<p class="sft-mfs-note sft-mfs-note--warn">' . wp_kses( $limit, array( 'strong' => array() ) ) . '</p>';
		}

		echo safestore_mfs_fields_html( 'sft_mfs', $values ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
		?>

		<p class="sft-mfs-note">
			<?php
			/* translators: %d: hours */
			printf( esc_html__( 'Not paid yet? Leave the TrxID empty and place the order — pay within %d hours and send us the TrxID.', 'safestore-minimal' ), (int) safestore_mfs_pay_hours() );
			?>
		</p>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Checkout: validate + save.
 * ---------------------------------------------------------------------- */

/**
 * Validate posted fields; returns array( values, error ).
 *
 * @param string $prefix Field prefix.
 * @param bool   $require_trx TrxID is mandatory (thank-you form).
 */
function safestore_mfs_read_posted( $prefix, $require_trx = false ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- callers verify.
	$raw_wallet = isset( $_POST[ $prefix . '_wallet' ] ) ? sanitize_key( wp_unslash( $_POST[ $prefix . '_wallet' ] ) ) : '';
	$raw_sender = isset( $_POST[ $prefix . '_sender' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . '_sender' ] ) ) : '';
	$raw_trxid  = isset( $_POST[ $prefix . '_trxid' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . '_trxid' ] ) ) : '';
	// phpcs:enable

	$wallets = safestore_mfs_wallet_labels();
	$values  = array(
		'wallet' => isset( $wallets[ $raw_wallet ] ) ? $raw_wallet : '',
		'sender' => safestore_mfs_clean_mobile( $raw_sender ),
		'trxid'  => safestore_mfs_clean_trxid( $raw_trxid ),
	);

	$error = '';
	if ( '' === trim( $raw_trxid ) ) {
		if ( $require_trx ) {
			$error = __( 'Please enter the Transaction ID (TrxID) from your bKash / Nagad SMS.', 'safestore-minimal' );
		}
		return array( array( 'wallet' => '', 'sender' => '', 'trxid' => '' ), $error );
	}

	if ( '' === $values['trxid'] ) {
		$error = __( 'The Transaction ID looks wrong. Copy it exactly from your bKash / Nagad SMS: 8–12 letters and numbers (bKash 10, Nagad 8).', 'safestore-minimal' );
	} elseif ( '' === $values['sender'] ) {
		$error = __( 'Please enter the bKash / Nagad number you sent the money from (11 digits, e.g. 017XXXXXXXX).', 'safestore-minimal' );
	} elseif ( '' === $values['wallet'] ) {
		$error = __( 'Please choose bKash or Nagad.', 'safestore-minimal' );
	}

	return array( $values, $error );
}

add_action(
	'woocommerce_after_checkout_validation',
	static function ( $data, $errors ) {
		if ( empty( $data['payment_method'] ) || safestore_mfs_gateway_id() !== $data['payment_method'] ) {
			return;
		}
		list( , $error ) = safestore_mfs_read_posted( 'sft_mfs' );
		if ( $error ) {
			$errors->add( 'sft_mfs', $error );
		}
	},
	10,
	2
);

/** Store wallet / sender / TrxID on the order. */
function safestore_mfs_store( $order, $values ) {
	$order->update_meta_data( '_sft_mfs_wallet', $values['wallet'] );
	$order->update_meta_data( '_sft_mfs_sender', $values['sender'] );
	$order->update_meta_data( '_sft_mfs_trxid', $values['trxid'] );
	$order->update_meta_data( '_sft_mfs_submitted', time() );
}

/** "bKash TrxID 9BG7X2K4LM from 01712-345678". */
function safestore_mfs_describe( $values ) {
	$labels = safestore_mfs_wallet_labels();
	return sprintf(
		/* translators: 1: bKash / Nagad, 2: TrxID, 3: sender number */
		__( '%1$s TrxID %2$s from %3$s', 'safestore-minimal' ),
		isset( $labels[ $values['wallet'] ] ) ? $labels[ $values['wallet'] ] : '',
		$values['trxid'],
		safestore_mfs_format_number( $values['sender'] )
	);
}

add_action(
	'woocommerce_checkout_create_order',
	static function ( $order, $data ) {
		if ( empty( $data['payment_method'] ) || safestore_mfs_gateway_id() !== $data['payment_method'] ) {
			return;
		}
		list( $values, $error ) = safestore_mfs_read_posted( 'sft_mfs' );
		if ( ! $error && '' !== $values['trxid'] ) {
			safestore_mfs_store( $order, $values );
		}
	},
	10,
	2
);

add_action(
	'woocommerce_checkout_order_processed',
	static function ( $order_id, $posted, $order ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order || safestore_mfs_gateway_id() !== $order->get_payment_method() ) {
			return;
		}
		$p = safestore_mfs_order_payment( $order );
		if ( '' !== $p['trxid'] ) {
			/* translators: %s: "bKash TrxID … from …" */
			$order->add_order_note( sprintf( __( 'Customer entered at checkout: %s. Verify in the wallet app before shipping.', 'safestore-minimal' ), safestore_mfs_describe( $p ) ) );
		}
	},
	10,
	3
);

/* -------------------------------------------------------------------------
 * Thank-you page: TrxID form submit (POST to the order-received URL).
 * ---------------------------------------------------------------------- */
add_action(
	'template_redirect',
	static function () {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified below.
		if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || empty( $_POST['sft_mfs_trx_submit'] ) ) {
			return;
		}
		$order_id = isset( $_POST['sft_mfs_order'] ) ? absint( $_POST['sft_mfs_order'] ) : 0;
		$key      = isset( $_POST['sft_mfs_key'] ) ? sanitize_text_field( wp_unslash( $_POST['sft_mfs_key'] ) ) : '';
		$nonce    = isset( $_POST['sft_mfs_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sft_mfs_nonce'] ) ) : '';
		// phpcs:enable

		$order = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $order || '' === $key || ! hash_equals( $order->get_order_key(), $key ) || safestore_mfs_gateway_id() !== $order->get_payment_method() ) {
			return; // Not ours / wrong key: let the page render normally.
		}

		$back = $order->get_checkout_order_received_url();

		if ( ! wp_verify_nonce( $nonce, 'sft_mfs_trx_' . $order->get_id() ) ) {
			wp_safe_redirect( add_query_arg( 'trx', 'expired', $back ) . '#sft-ty-pay' );
			exit;
		}

		list( $values, $error ) = safestore_mfs_read_posted( 'sft_mfs_ty', true );
		if ( $error ) {
			set_transient( 'sft_mfs_err_' . $order->get_id(), $error, 10 * MINUTE_IN_SECONDS );
			wp_safe_redirect( add_query_arg( 'trx', 'invalid', $back ) . '#sft-ty-pay' );
			exit;
		}

		safestore_mfs_store( $order, $values );
		$order->save();
		/* translators: %s: "bKash TrxID … from …" */
		$order->add_order_note( sprintf( __( 'Customer submitted on the thank-you page: %s. Verify in the wallet app before shipping.', 'safestore-minimal' ), safestore_mfs_describe( $values ) ) );

		// Tell the shop — this arrives after the "new order" email.
		$admin = get_option( 'admin_email' );
		if ( $admin ) {
			wp_mail(
				$admin,
				/* translators: 1: order number, 2: amount */
				sprintf( __( '[SafeStoreBD] Payment TrxID for order #%1$s (%2$s)', 'safestore-minimal' ), $order->get_order_number(), safestore_mfs_plain_price( $order->get_total() ) ),
				sprintf(
					/* translators: 1: order number, 2: "bKash TrxID … from …", 3: amount, 4: admin URL */
					__( "Order #%1\$s\n%2\$s\nAmount due: %3\$s\n\nVerify it in the wallet app, then move the order to Processing:\n%4\$s", 'safestore-minimal' ),
					$order->get_order_number(),
					safestore_mfs_describe( $values ),
					safestore_mfs_plain_price( $order->get_total() ),
					$order->get_edit_order_url()
				)
			);
		}

		wp_safe_redirect( add_query_arg( 'trx', 'sent', $back ) . '#sft-ty-pay' );
		exit;
	}
);

/**
 * TrxID form for the thank-you page.
 *
 * @param WC_Order $order Order.
 */
function safestore_mfs_trx_form_html( $order ) {
	ob_start();
	?>
	<form class="sft-mfs-form" method="post" action="<?php echo esc_url( $order->get_checkout_order_received_url() ); ?>#sft-ty-pay">
		<?php echo safestore_mfs_fields_html( 'sft_mfs_ty', array(), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
		<input type="hidden" name="sft_mfs_order" value="<?php echo esc_attr( $order->get_id() ); ?>" />
		<input type="hidden" name="sft_mfs_key" value="<?php echo esc_attr( $order->get_order_key() ); ?>" />
		<input type="hidden" name="sft_mfs_nonce" value="<?php echo esc_attr( wp_create_nonce( 'sft_mfs_trx_' . $order->get_id() ) ); ?>" />
		<button type="submit" name="sft_mfs_trx_submit" value="1" class="sft-ty-btn sft-ty-btn--primary"><?php esc_html_e( 'Submit Transaction ID', 'safestore-minimal' ); ?></button>
	</form>
	<?php
	return ob_get_clean();
}

/** Status message after a form submit ('' when none). */
function safestore_mfs_form_notice( $order ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display flag only.
	$flag = isset( $_GET['trx'] ) ? sanitize_key( wp_unslash( $_GET['trx'] ) ) : '';
	if ( 'invalid' === $flag ) {
		$msg = get_transient( 'sft_mfs_err_' . $order->get_id() );
		delete_transient( 'sft_mfs_err_' . $order->get_id() );
		return array( 'error', $msg ? $msg : __( 'Please check the Transaction ID and number and try again.', 'safestore-minimal' ) );
	}
	if ( 'expired' === $flag ) {
		return array( 'error', __( 'The form expired. Please enter the Transaction ID again.', 'safestore-minimal' ) );
	}
	if ( 'sent' === $flag ) {
		return array( 'success', __( 'Thank you! We received your Transaction ID and will verify it shortly.', 'safestore-minimal' ) );
	}
	return array();
}

/* -------------------------------------------------------------------------
 * Admin order screen + emails.
 * ---------------------------------------------------------------------- */
add_action(
	'woocommerce_admin_order_data_after_billing_address',
	static function ( $order ) {
		if ( safestore_mfs_gateway_id() !== $order->get_payment_method() ) {
			return;
		}
		$p      = safestore_mfs_order_payment( $order );
		$labels = safestore_mfs_wallet_labels();
		echo '<div class="sft-mfs-admin" style="margin-top:12px;padding:10px 12px;border:1px solid #e2e8f0;border-left:3px solid #e2136e;border-radius:4px;background:#fff">';
		echo '<p style="margin:0 0 6px"><strong>' . esc_html__( 'bKash / Nagad payment', 'safestore-minimal' ) . '</strong></p>';
		if ( '' === $p['trxid'] ) {
			echo '<p style="margin:0;color:#b45309">' . esc_html__( 'No Transaction ID yet — customer has not submitted one.', 'safestore-minimal' ) . '</p>';
		} else {
			echo '<p style="margin:0">' . esc_html__( 'Wallet:', 'safestore-minimal' ) . ' <strong>' . esc_html( isset( $labels[ $p['wallet'] ] ) ? $labels[ $p['wallet'] ] : $p['wallet'] ) . '</strong><br />';
			echo esc_html__( 'Sender:', 'safestore-minimal' ) . ' <strong>' . esc_html( safestore_mfs_format_number( $p['sender'] ) ) . '</strong><br />';
			echo esc_html__( 'TrxID:', 'safestore-minimal' ) . ' <strong style="font-family:monospace;font-size:14px">' . esc_html( $p['trxid'] ) . '</strong></p>';
		}
		echo '</div>';
	}
);

add_action(
	'woocommerce_email_order_meta',
	static function ( $order, $sent_to_admin, $plain_text ) {
		if ( ! $order instanceof WC_Order || safestore_mfs_gateway_id() !== $order->get_payment_method() ) {
			return;
		}
		$p = safestore_mfs_order_payment( $order );
		if ( '' === $p['trxid'] ) {
			return;
		}
		$line = safestore_mfs_describe( $p );
		if ( $plain_text ) {
			echo "\n" . esc_html__( 'Payment submitted:', 'safestore-minimal' ) . ' ' . esc_html( $line ) . "\n";
		} else {
			echo '<p><strong>' . esc_html__( 'Payment submitted:', 'safestore-minimal' ) . '</strong> ' . esc_html( $line ) . '</p>';
		}
	},
	10,
	3
);

/* -------------------------------------------------------------------------
 * Styles: checkout + order received (is_checkout() covers both).
 * ---------------------------------------------------------------------- */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( function_exists( 'is_checkout' ) && is_checkout() && function_exists( 'safestore_page_css' ) ) {
			safestore_page_css( 'mfs-pay', 'mfs-pay.css' );
		}
	},
	21
);

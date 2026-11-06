<?php
/**
 * Output a single payment method — SafeStoreBD.
 *
 * Same as WooCommerce's template, except the bKash / Nagad gateway
 * (safestore_mfs_gateway_id(), the renamed "Check payments") renders the
 * Send Money box from inc/mfs-payment.php: numbers, amount, TrxID fields.
 * Every other gateway is untouched.
 *
 * Based on WooCommerce templates/checkout/payment-method.php.
 *
 * @package safestore-minimal
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sft_is_mfs = function_exists( 'safestore_mfs_gateway_id' ) && safestore_mfs_gateway_id() === $gateway->id;
?>
<li class="wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?>">
	<input id="payment_method_<?php echo esc_attr( $gateway->id ); ?>" type="radio" class="input-radio" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $gateway->chosen, true ); ?> data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>" />

	<label for="payment_method_<?php echo esc_attr( $gateway->id ); ?>">
		<?php echo $gateway->get_title(); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The base gateway title is sanitized; the public filter intentionally allows HTML. */ ?> <?php echo $gateway->get_icon(); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The base icon attributes are escaped; the public filter intentionally allows HTML. */ ?>
	</label>
	<?php if ( $sft_is_mfs || $gateway->has_fields() || $gateway->get_description() ) : ?>
		<div class="payment_box payment_method_<?php echo esc_attr( $gateway->id ); ?>" <?php if ( ! $gateway->chosen ) : /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>style="display:none;"<?php endif; /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>>
			<?php
			if ( $sft_is_mfs ) {
				safestore_mfs_payment_box();
			} else {
				$gateway->payment_fields();
			}
			?>
		</div>
	<?php endif; ?>
</li>

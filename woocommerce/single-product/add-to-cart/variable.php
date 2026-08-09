<?php
/**
 * Variable product add to cart — SafeStore override.
 *
 * Footwear (Safety Shoes): dedicated EU size 39–44 panel wired to
 * variation stock / SKU / price. Non-footwear keeps the standard table
 * and never surfaces Size.
 *
 * @package SafeStore_Minimal
 * @version 10.9.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$is_footwear     = function_exists( 'safestore_is_footwear_product' ) && safestore_is_footwear_product( $product );
$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

// Non-footwear: never show Size on the PDP even if the attribute exists.
if ( ! $is_footwear && isset( $attributes['pa_size'] ) ) {
	unset( $attributes['pa_size'] );
	$attribute_keys = array_keys( $attributes );
}

$form_classes = 'variations_form cart';
if ( $is_footwear ) {
	$form_classes .= ' sft-variations-form sft-variations-form--footwear';
}

do_action( 'woocommerce_before_add_to_cart_form' );
?>

<form class="<?php echo esc_attr( $form_classes ); ?>" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data" data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" data-footwear="<?php echo $is_footwear ? '1' : '0'; ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php elseif ( $is_footwear && empty( $attributes ) ) : ?>
		<p class="stock out-of-stock"><?php esc_html_e( 'Size options are not configured for this safety shoe yet.', 'safestore-minimal' ); ?></p>
	<?php else : ?>

		<?php if ( $is_footwear ) : ?>
			<div class="sft-pdp-size" data-sft-size-panel>
				<div class="sft-pdp-size__header">
					<span class="sft-pdp-size__label" id="sft-pdp-size-label"><?php esc_html_e( 'Size', 'safestore-minimal' ); ?></span>
					<span class="sft-pdp-size__required"><?php esc_html_e( 'Required', 'safestore-minimal' ); ?></span>
				</div>

				<table class="variations sft-pdp-size__table" cellspacing="0" role="presentation">
					<tbody>
						<?php foreach ( $attributes as $attribute_name => $options ) : ?>
							<?php
							$is_size_attr = ( 'pa_size' === $attribute_name || 'size' === sanitize_title( $attribute_name ) );
							if ( ! $is_size_attr ) {
								continue;
							}
							?>
							<tr class="sft-pdp-size__row">
								<th class="label screen-reader-text">
									<label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>">
										<?php echo wc_attribute_label( $attribute_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</label>
								</th>
								<td class="value">
									<?php
									wc_dropdown_variation_attribute_options(
										array(
											'options'   => $options,
											'attribute' => $attribute_name,
											'product'   => $product,
										)
									);
									?>
								</td>
							</tr>
						<?php endforeach; ?>

						<?php
						// Keep any non-size attributes (rare) available but visually secondary.
						foreach ( $attributes as $attribute_name => $options ) :
							$is_size_attr = ( 'pa_size' === $attribute_name || 'size' === sanitize_title( $attribute_name ) );
							if ( $is_size_attr ) {
								continue;
							}
							?>
							<tr>
								<th class="label"><label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo wc_attribute_label( $attribute_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label></th>
								<td class="value">
									<?php
									wc_dropdown_variation_attribute_options(
										array(
											'options'   => $options,
											'attribute' => $attribute_name,
											'product'   => $product,
										)
									);
									echo end( $attribute_keys ) === $attribute_name
										? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#" aria-label="' . esc_attr__( 'Clear options', 'woocommerce' ) . '">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) )
										: '';
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<div class="sft-pdp-size__meta" data-sft-size-meta aria-live="polite">
					<p class="sft-pdp-size__prompt"><?php esc_html_e( 'Select a size (EU 39–44) to see stock and continue.', 'safestore-minimal' ); ?></p>
					<p class="sft-pdp-size__stock" data-sft-size-stock hidden></p>
					<p class="sft-pdp-size__sku" data-sft-size-sku hidden></p>
				</div>

				<a class="reset_variations sft-pdp-size__clear" href="#" aria-label="<?php esc_attr_e( 'Clear size', 'safestore-minimal' ); ?>"><?php esc_html_e( 'Clear size', 'safestore-minimal' ); ?></a>
			</div>
		<?php else : ?>
			<table class="variations" cellspacing="0" role="presentation">
				<tbody>
					<?php foreach ( $attributes as $attribute_name => $options ) : ?>
						<tr>
							<th class="label"><label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo wc_attribute_label( $attribute_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label></th>
							<td class="value">
								<?php
								wc_dropdown_variation_attribute_options(
									array(
										'options'   => $options,
										'attribute' => $attribute_name,
										'product'   => $product,
									)
								);
								echo end( $attribute_keys ) === $attribute_name
									? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#" aria-label="' . esc_attr__( 'Clear options', 'woocommerce' ) . '">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) )
									: '';
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>

		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<div class="single_variation_wrap">
			<?php
			do_action( 'woocommerce_before_single_variation' );
			do_action( 'woocommerce_single_variation' );
			do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php
do_action( 'woocommerce_after_add_to_cart_form' );

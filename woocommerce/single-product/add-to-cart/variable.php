<?php
/**
 * Variable product add to cart
 *
 * Theme override of WooCommerce core 11.1.0. Differences from core:
 *  - Footwear products: the size row is visually hidden (the native <select>
 *    stays in the DOM for WooCommerce) and js/footwear-sizing.js renders size
 *    pills into the [data-sft-size-host] mount next to Add to cart.
 *  - Non-footwear products never show a global pa_size attribute.
 *  - No "Clear" link on footwear (tapping a selected pill deselects it).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.1.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$is_footwear = function_exists( 'safestore_is_footwear_product' ) && safestore_is_footwear_product( $product );

if ( ! $is_footwear && isset( $attributes['pa_size'] ) ) {
	unset( $attributes['pa_size'] );
}

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

$form_classes = 'variations_form cart';
if ( $is_footwear ) {
	$form_classes .= ' sft-variations-form sft-variations-form--footwear';
}

/**
 * Fires before the add to cart form of a variable product.
 *
 * @since 1.2.1
 */
do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form class="<?php echo esc_attr( $form_classes ); ?>" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above by wc_esc_json() / _wp_specialchars(). ?>" data-footwear="<?php echo $is_footwear ? '1' : '0'; ?>">
	<?php
	/**
	 * Fires at the top of the variations form, inside the form element.
	 *
	 * @since 2.4.0
	 */
	do_action( 'woocommerce_before_variations_form' );
	?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		<table class="variations<?php echo $is_footwear ? ' sft-variations--size-native' : ''; ?>" cellspacing="0" role="presentation">
			<tbody>
				<?php foreach ( $attributes as $attribute_name => $options ) : ?>
					<?php
					$is_size_attr = ( 'pa_size' === $attribute_name || 'size' === sanitize_title( $attribute_name ) );
					$is_pill_row  = $is_footwear && $is_size_attr;
					?>
					<tr class="<?php echo esc_attr( $is_pill_row ? 'sft-size-dropdown-row' : '' ); ?>">
						<th class="label<?php echo $is_pill_row ? ' screen-reader-text' : ''; ?>"><label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo wc_attribute_label( $attribute_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute names are sanitized on save; the filter output may carry markup by design. ?></label></th>
						<td class="value">
							<?php
								wc_dropdown_variation_attribute_options(
									array(
										'options'   => $options,
										'attribute' => $attribute_name,
										'product'   => $product,
									)
								);
								/**
								 * Filters the reset variation button.
								 *
								 * @since 2.5.0
								 *
								 * @param string  $button The reset variation button HTML.
								 */
								echo ( ! $is_footwear && end( $attribute_keys ) === $attribute_name ) ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#" aria-label="' . esc_attr__( 'Clear options', 'woocommerce' ) . '">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) ) : '';
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>
		<?php
		// Reset snapshot for cases where a theme/plugin loads the variation form later, like quick-view modals.
		if ( function_exists( 'wc_get_product_gallery_html' ) ) :
			?>
			<template class="wc-product-gallery-default-template"><?php echo wc_get_product_gallery_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></template>
		<?php endif; ?>
		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<div class="single_variation_wrap">
			<?php
				/**
				 * Hook: woocommerce_before_single_variation.
				 *
				 * @since 2.1.0
				 */
				do_action( 'woocommerce_before_single_variation' );

				/**
				 * Hook: woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
				 *
				 * @since 2.4.0
				 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
				 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
				 */
				do_action( 'woocommerce_single_variation' );

				/**
				 * Hook: woocommerce_after_single_variation.
				 *
				 * @since 2.1.0
				 */
				do_action( 'woocommerce_after_single_variation' );
			?>
			<?php if ( $is_footwear ) : ?>
				<!-- Fallback mount; js/footwear-sizing.js moves this directly above Add to cart. -->
				<div class="sft-size-swatches-host" data-sft-size-host hidden></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php
	/**
	 * Fires at the bottom of the variations form, inside the form element.
	 *
	 * @since 2.4.0
	 */
	do_action( 'woocommerce_after_variations_form' );
	?>
</form>

<?php
/**
 * Fires after the add to cart form of a variable product.
 *
 * @since 1.2.1
 */
do_action( 'woocommerce_after_add_to_cart_form' );

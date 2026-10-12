<?php
// @version 3.6.0

defined('ABSPATH') || exit;

global $product;

do_action('woocommerce_before_single_product');

if (post_password_required()) {
    echo get_the_password_form();
    return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class('sft-pdp', $product); ?>>

	<div class="sft-pdp__hero">
		<div class="sft-pdp__col sft-pdp__col--media">
			<?php
			do_action('woocommerce_before_single_product_summary');
			?>
		</div>

		<div class="sft-pdp__col sft-pdp__col--summary">
			<div class="summary entry-summary sft-pdp__summary">
				<?php
				do_action('woocommerce_single_product_summary');
				?>
			</div>
		</div>
	</div>

	<?php
	do_action('woocommerce_after_single_product_summary');
	?>
</div>

<?php do_action('woocommerce_after_single_product'); ?>

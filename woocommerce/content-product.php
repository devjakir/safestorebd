<?php
/**
 * Product card used inside the shop / archive loops.
 *
 * @package safestore-minimal
 */

defined('ABSPATH') || exit;

global $product;

if (empty($product) || ! $product->is_visible()) {
    return;
}

$product_id     = $product->get_id();
$permalink      = get_permalink();
$is_on_sale     = $product->is_on_sale();
$regular_price  = (float) $product->get_regular_price();
$sale_price     = (float) $product->get_sale_price();
$discount_pct   = 0;

if ($is_on_sale && $regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price) {
    $discount_pct = (int) round((($regular_price - $sale_price) / $regular_price) * 100);
}

$category_html  = wc_get_product_category_list($product_id, ', ', '', '');
$category_text  = trim(wp_strip_all_tags($category_html));

$avg_rating     = (float) $product->get_average_rating();
$rating_count   = (int) $product->get_rating_count();
$rating_pct     = max(0, min(100, ($avg_rating / 5) * 100));
?>
<li <?php wc_product_class('sft-product-card', $product); ?>>

    <div class="sft-product-card__media">
        <?php if ($discount_pct > 0) : ?>
            <span class="sft-product-card__badge">-<?php echo esc_html($discount_pct); ?>%</span>
        <?php endif; ?>

        <a class="sft-product-card__media-link" href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($product->get_name()); ?>">
            <?php echo $product->get_image('woocommerce_thumbnail', array('class' => 'sft-product-card__img')); ?>
        </a>

        <?php
        /**
         * Loop add-to-cart — rendered over the image (bottom-left via CSS).
         *
         * @hooked safestore_minimal_product_card_loop_add_to_cart — see functions.php
         */
        ?>
        <div class="sft-product-card__footer">
            <?php do_action('safestore_minimal_product_card_footer'); ?>
        </div>
    </div>

    <div class="sft-product-card__body">
        <?php if ($category_text) : ?>
            <span class="sft-product-card__cat"><?php echo esc_html($category_text); ?></span>
        <?php endif; ?>

        <div class="sft-product-card__price">
            <?php echo $product->get_price_html(); ?>
        </div>

        <h3 class="sft-product-card__title">
            <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($product->get_name()); ?></a>
        </h3>

        <?php if ($rating_count > 0) : ?>
            <div class="sft-product-card__rating" aria-label="<?php echo esc_attr(sprintf(__('Rated %s out of 5', 'safestore-minimal'), number_format($avg_rating, 2))); ?>">
                <span class="sft-stars" aria-hidden="true">
                    <span class="sft-stars__row sft-stars__row--empty">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                    <span class="sft-stars__row sft-stars__row--filled" style="width: <?php echo esc_attr($rating_pct); ?>%;">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                </span>
                <span class="sft-rating-count">(<?php echo esc_html(number_format($avg_rating, 2)); ?>)</span>
            </div>
        <?php endif; ?>
    </div>

</li>

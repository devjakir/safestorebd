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

$product_id = $product->get_id();
$permalink  = get_permalink();

// Savings badge (top-left, inside the image). Simple sale products show the
// amount saved ("Save ৳400") — the most conversion-focused message; variable
// products show a best-effort percentage since variations can differ.
$badge_text = '';
if ($product->is_on_sale()) {
    if ($product->is_type('simple')) {
        $regular = (float) $product->get_regular_price();
        $sale    = (float) $product->get_sale_price();
        if ($regular > 0 && $sale > 0 && $sale < $regular) {
            $badge_text = sprintf(
                /* translators: %s: amount saved, e.g. ৳400 */
                __('Save %s', 'safestore-minimal'),
                html_entity_decode(wp_strip_all_tags(wc_price($regular - $sale, array('decimals' => 0))))
            );
        }
    } elseif ($product->is_type('variable')) {
        $reg_min = (float) $product->get_variation_regular_price('min');
        $now_min = (float) $product->get_variation_price('min');
        if ($reg_min > 0 && $now_min > 0 && $now_min < $reg_min) {
            $badge_text = '-' . (int) round((($reg_min - $now_min) / $reg_min) * 100) . '%';
        }
    }
}

$category_html = wc_get_product_category_list($product_id, ', ', '', '');
$category_text = trim(wp_strip_all_tags($category_html));
?>
<li <?php wc_product_class('sft-product-card', $product); ?>>

    <div class="sft-product-card__media">
        <?php if ('' !== $badge_text) : ?>
            <span class="sft-product-card__badge"><?php echo esc_html($badge_text); ?></span>
        <?php endif; ?>

        <a class="sft-product-card__media-link" href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($product->get_name()); ?>">
            <?php echo $product->get_image('woocommerce_thumbnail', array('class' => 'sft-product-card__img')); ?>
        </a>
    </div>

    <div class="sft-product-card__body">
        <?php if ($category_text) : ?>
            <div class="sft-product-card__cat-wrap">
                <span class="sft-product-card__cat"><?php echo esc_html($category_text); ?></span>
                <button type="button" class="sft-product-card__cat-toggle" aria-expanded="false" hidden
                    data-more="<?php esc_attr_e('Show more', 'safestore-minimal'); ?>"
                    data-less="<?php esc_attr_e('Show less', 'safestore-minimal'); ?>"><?php esc_html_e('Show more', 'safestore-minimal'); ?></button>
            </div>
        <?php endif; ?>

        <div class="sft-product-card__price">
            <?php echo $product->get_price_html(); ?>
        </div>

        <h3 class="sft-product-card__title">
            <a href="<?php echo esc_url($permalink); ?>" title="<?php echo esc_attr($product->get_name()); ?>"><?php echo esc_html($product->get_name()); ?></a>
        </h3>

        <?php
        /**
         * Loop add-to-cart — always visible, pinned to the bottom of the card.
         *
         * @hooked safestore_minimal_product_card_loop_add_to_cart — see functions.php
         */
        ?>
        <div class="sft-product-card__footer">
            <?php do_action('safestore_minimal_product_card_footer'); ?>
        </div>
    </div>

</li>

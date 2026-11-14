<?php
/**
 * The template for displaying product content within loops
 *
 * Theme override of WooCommerce core 9.4.0. The theme renders its own product
 * card (.sft-product-card) instead of the core loop hooks, so
 * woocommerce_before_shop_loop_item / _title / after_shop_loop_item are not
 * fired; extras are added through `safestore_minimal_product_card_footer` and
 * direct calls (origin badge). The visibility guard matches core 9.4.0.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined('ABSPATH') || exit;

global $product;

// Check if the product is a valid WooCommerce product and ensure its visibility before proceeding.
if (! is_a($product, WC_Product::class) || ! $product->is_visible()) {
    return;
}

$product_id = $product->get_id();
$permalink  = get_permalink();

// Discount badge (top-left, inside the image): one format everywhere, "-27%"
// (inc/hot-badge.php). Optional "HOT" badge sits next to it.
$badge_text = function_exists('safestore_discount_badge_text') ? safestore_discount_badge_text($product) : '';

$is_hot = function_exists('safestore_product_is_hot') && safestore_product_is_hot($product);

$category_text = function_exists('safestore_get_product_category_label')
    ? safestore_get_product_category_label($product_id)
    : trim(wp_strip_all_tags(wc_get_product_category_list($product_id, ', ', '', '')));
?>
<li <?php wc_product_class('sft-product-card', $product); ?>>

    <div class="sft-product-card__media">
        <?php if ($is_hot || '' !== $badge_text) : ?>
            <div class="sft-product-card__badges">
                <?php if ($is_hot) : ?>
                    <?php echo safestore_hot_badge_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endif; ?>
                <?php if ('' !== $badge_text) : ?>
                    <span class="sft-product-card__badge"><?php echo esc_html($badge_text); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <a class="sft-product-card__media-link" href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($product->get_name()); ?>">
            <?php
            $sft_img_attrs = array(
                'class'    => 'sft-product-card__img',
                'loading'  => 'lazy',
                'decoding' => 'async',
                'sizes'    => '(max-width: 820px) 48vw, (max-width: 1080px) 30vw, 280px', // 2-up on phones (shop + home)
            );
            // Shop / category main loop: the first card image is the page's LCP
            // element, so the first two load eagerly (the first with high priority)
            // instead of waiting for the lazy-load observer (PSI: 1.5 s load delay).
            if ( function_exists( 'safestore_minimal_eager_card_slot' ) ) {
                $sft_slot = safestore_minimal_eager_card_slot();
                if ( $sft_slot > 0 ) {
                    $sft_img_attrs['loading'] = 'eager';
                    if ( 1 === $sft_slot ) {
                        $sft_img_attrs['fetchpriority'] = 'high';
                    }
                }
            }
            echo $product->get_image( 'woocommerce_thumbnail', $sft_img_attrs );
            ?>
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

        <?php
        // Star rating when reviews exist — BD buyers treat stars as a legitimacy check.
        $rating_count = (int) $product->get_rating_count();
        $avg_rating   = (float) $product->get_average_rating();
        if ( $rating_count > 0 && $avg_rating > 0 && function_exists( 'wc_get_rating_html' ) ) :
            $fill_pct = max( 0, min( 100, ( $avg_rating / 5 ) * 100 ) );
            ?>
            <div class="sft-product-card__rating" aria-label="<?php echo esc_attr( sprintf(
                /* translators: 1: average rating, 2: review count */
                __( 'Rated %1$s out of 5 from %2$s reviews', 'safestore-minimal' ),
                number_format_i18n( $avg_rating, 1 ),
                number_format_i18n( $rating_count )
            ) ); ?>">
                <span class="sft-stars" aria-hidden="true">
                    <span class="sft-stars__row sft-stars__row--empty">★★★★★</span>
                    <span class="sft-stars__row sft-stars__row--filled" style="width: <?php echo esc_attr( (string) $fill_pct ); ?>%">★★★★★</span>
                </span>
                <span class="sft-rating-count">(<?php echo esc_html( number_format_i18n( $rating_count ) ); ?>)</span>
            </div>
        <?php endif; ?>

        <h3 class="sft-product-card__title">
            <a href="<?php echo esc_url($permalink); ?>" title="<?php echo esc_attr($product->get_name()); ?>"><?php echo esc_html($product->get_name()); ?></a>
        </h3>

        <?php
        // Custom card does not fire woocommerce_after_shop_loop_item_title.
        if ( function_exists( 'safestore_origin_badge_loop' ) ) {
            safestore_origin_badge_loop();
        }
        ?>

        <?php
        ?>
        <div class="sft-product-card__footer">
            <?php do_action('safestore_minimal_product_card_footer'); ?>
        </div>
    </div>

</li>

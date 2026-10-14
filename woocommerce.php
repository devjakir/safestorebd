<?php
get_header(); ?>

<main id="content" class="sft-main sft-shop-main" role="main">
    <div class="sft-shop">

        <?php if (is_shop() || is_product_taxonomy()) : ?>
            <?php
            if (function_exists('woocommerce_breadcrumb')) {
                woocommerce_breadcrumb(
                    array(
                        'delimiter'   => '<span class="sft-pdp-breadcrumb__sep" aria-hidden="true">/</span>',
                        'wrap_before' => '<nav class="sft-shop-breadcrumb woocommerce-breadcrumb" aria-label="' . esc_attr__('Breadcrumb', 'safestore-minimal') . '">',
                        'wrap_after'  => '</nav>',
                        'before'      => '',
                        'after'       => '',
                        'home'        => _x('Home', 'breadcrumb', 'safestore-minimal'),
                    )
                );
            }
            ?>
            <?php
            // HUSKY /swoof/ URLs resolve as the shop page; keep the category's own title and intro there.
            $sft_term = function_exists('safestore_minimal_shop_current_term') ? safestore_minimal_shop_current_term() : null;
            ?>
            <header class="sft-shop-header">
                <h1 class="sft-shop-title">
                    <?php
                    if ($sft_term) {
                        echo esc_html($sft_term->name);
                    } else {
                        echo esc_html__('Safety Equipment in Bangladesh', 'safestore-minimal');
                    }
                    ?>
                </h1>
                <?php
                if ($sft_term) {
                    // Term descriptions are short — keep them in the header.
                    $description = term_description($sft_term->term_id);
                } else {
                    // Shop page: short tagline only. The full SEO content now
                    // renders below the product grid (inc/shop-filter.php),
                    // so products stay above the fold.
                    $shop_page_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('shop') : 0;
                    $raw          = $shop_page_id > 0 ? get_post_field('post_content', $shop_page_id) : '';
                    $description  = '' !== trim((string) $raw)
                        ? '<p>' . esc_html(wp_trim_words(wp_strip_all_tags((string) $raw), 24, '…')) . '</p>'
                        : '';
                }
                if ($description) : ?>
                    <div class="sft-shop-desc is-clamped"><div class="sft-shop-desc__text" id="sft-shop-desc-text"><?php echo wp_kses_post($description); ?></div></div>
                <?php endif; ?>
                <?php
                // Vest + helmet categories: custom logo / name printing (inc/custom-print.php).
                if ($sft_term && function_exists('safestore_custom_print_category_note')) {
                    safestore_custom_print_category_note($sft_term);
                }
                ?>
            </header>
        <?php endif; ?>

        <?php if (function_exists('safestore_minimal_shop_filter_available') && safestore_minimal_shop_filter_available()) : ?>
            <?php // Filter sits outside HUSKY's #woof_results_by_ajax wrapper so AJAX redraws of the grid never wipe it. ?>
            <div class="sft-shop-layout">
                <aside class="sft-shop-sidebar" aria-label="<?php esc_attr_e('Product filters', 'safestore-minimal'); ?>">
                    <?php safestore_minimal_shop_filter_render(); ?>
                </aside>
                <div class="sft-shop-results">
                    <?php // Toolbar bar: the sort form stays in WooCommerce's loop (inside HUSKY's AJAX region) and is positioned into this bar by css/shop-filter.css. ?>
                    <div class="sft-shop-toolbar">
                        <h2 class="sft-shop-toolbar__title"><?php echo esc_html(!empty($sft_term) ? $sft_term->name : __('All products', 'safestore-minimal')); ?></h2>
                    </div>
                    <?php woocommerce_content(); ?>
                </div>
            </div>
        <?php else : ?>
            <?php woocommerce_content(); ?>
        <?php endif; ?>

    </div>
</main>

<?php get_footer(); ?>

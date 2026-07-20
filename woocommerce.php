<?php
/**
 * The template for displaying all WooCommerce pages (shop, archives, single product).
 * Wraps the WooCommerce content with the theme's header/footer and shop container.
 */
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
            <header class="sft-shop-header">
                <h1 class="sft-shop-title">
                    <?php
                    if (is_product_taxonomy()) {
                        single_term_title();
                    } else {
                        echo esc_html(woocommerce_page_title(false));
                    }
                    ?>
                </h1>
                <?php
                $description = is_product_taxonomy() ? term_description() : '';
                if ($description) : ?>
                    <div class="sft-shop-desc"><?php echo wp_kses_post($description); ?></div>
                <?php endif; ?>
            </header>
        <?php endif; ?>

        <?php woocommerce_content(); ?>

    </div>
</main>

<?php get_footer(); ?>

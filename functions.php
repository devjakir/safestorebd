<?php

/**
 * Front-end performance helpers (defer, preload, emoji/CSS cleanup).
 */
require get_template_directory() . '/inc/performance.php';

/**
 * Sitemap / crawl hygiene (Rank Math exclusions, noindex, cache busting).
 */
require get_template_directory() . '/inc/seo-sitemap.php';

/**
 * WhatsApp chat widget — floating button, chat panel, admin settings
 * (Settings → WhatsApp Chat).
 */
require get_template_directory() . '/inc/whatsapp-chat.php';

/**
 * Add to Cart success toast — branded confirmation, header cart sync, AJAX
 * single-product add. See inc/cart-toast.php.
 */
require get_template_directory() . '/inc/cart-toast.php';

/**
 * Site-wide copy to clipboard — copy buttons on contact details everywhere
 * they appear, plus automatic buttons on plain tel:/mailto:/wa.me links in
 * page content. See inc/copy-to-clipboard.php.
 */
require get_template_directory() . '/inc/copy-to-clipboard.php';

/**
 * Hostinger SMTP enforcement (WP Mail SMTP / phpmailer).
 * Password comes from WPMS_SMTP_PASS in wp-config or inc/smtp-secret.php.
 * See deploy/wp-config-smtp-snippet.php.
 */
require get_template_directory() . '/inc/smtp.php';

/**
 * Safety Shoes size variations setup — admin tool under
 * WooCommerce → Size Variations (pa_size 39–44).
 */
require get_template_directory() . '/inc/size-variations-setup.php';

/**
 * Footwear storefront logic — PDP size swatches 39–44, per-size stock
 * validation, retail/B2B qty rules.
 */
require get_template_directory() . '/inc/footwear-sizing.php';

/**
 * PDP size selector for Safety Shoes simple products (cart item meta).
 * Hook: woocommerce_before_add_to_cart_button.
 */
require get_template_directory() . '/inc/pdp-shoe-size.php';

/**
 * PDP social support/share buttons + product Open Graph / Twitter Card meta.
 * See inc/pdp-social.php.
 */
require get_template_directory() . '/inc/pdp-social.php';

/**
 * Product comparison — floating bar, REST payload, /compare/ page seed.
 * Complements the PDP Compare toggle (localStorage key sft_compare).
 */
require get_template_directory() . '/inc/product-compare.php';

/**
 * Made in Bangladesh origin badge — Product Data checkbox, shop cards, PDP.
 * See inc/origin-badge.php.
 */
require get_template_directory() . '/inc/origin-badge.php';

/**
 * PDP compact trust badges + Delivery & returns product tab.
 * See inc/pdp-fulfillment.php.
 */
require get_template_directory() . '/inc/pdp-fulfillment.php';

/**
 * Mobile & tablet navigation — sticky bottom bar, header quick links,
 * hide-on-scroll header. Renders below 1025px only.
 */
require get_template_directory() . '/inc/mobile-nav.php';

function safestore_minimal_enqueue_assets() {
    $version = wp_get_theme()->get('Version');

    // Do not chain theme CSS behind WooCommerce styles — that made WC CSS
    // render-blocking for the entire first paint (PSI). Theme rules already
    // own product-card / PDP chrome; WC sheets load separately (often async).
    $style_path = get_stylesheet_directory() . '/style.css';
    wp_enqueue_style(
        'safestore-minimal-style',
        get_stylesheet_uri(),
        array(),
        file_exists($style_path) ? (string) filemtime($style_path) : $version
    );

    if (function_exists('is_product') && is_product()) {
        $pdp_tabs_path = get_template_directory() . '/css/pdp-tabs.css';
        if (file_exists($pdp_tabs_path)) {
            wp_enqueue_style(
                'safestore-minimal-pdp-tabs',
                get_template_directory_uri() . '/css/pdp-tabs.css',
                array('safestore-minimal-style'),
                (string) filemtime($pdp_tabs_path)
            );
        }

        $pdp_gallery_path = get_template_directory() . '/js/pdp-gallery.js';
        if (file_exists($pdp_gallery_path)) {
            wp_enqueue_script(
                'safestore-minimal-pdp-gallery',
                get_template_directory_uri() . '/js/pdp-gallery.js',
                array('jquery'),
                (string) filemtime($pdp_gallery_path),
                function_exists('safestore_perf_script_args') ? safestore_perf_script_args(true) : true
            );
        }

        // Side-by-side image zoom (lens + flyout). Desktop-only; the asset pair
        // self-gates at 992px so it stays inert on touch devices.
        $pdp_zoom_css = get_template_directory() . '/css/pdp-zoom.css';
        if (file_exists($pdp_zoom_css)) {
            wp_enqueue_style(
                'safestore-minimal-pdp-zoom',
                get_template_directory_uri() . '/css/pdp-zoom.css',
                array('safestore-minimal-style'),
                (string) filemtime($pdp_zoom_css)
            );
        }

        $pdp_zoom_js = get_template_directory() . '/js/pdp-zoom.js';
        if (file_exists($pdp_zoom_js)) {
            wp_enqueue_script(
                'safestore-minimal-pdp-zoom',
                get_template_directory_uri() . '/js/pdp-zoom.js',
                array(),
                (string) filemtime($pdp_zoom_js),
                function_exists('safestore_perf_script_args') ? safestore_perf_script_args(true) : true
            );
        }
    }

    // Site footer — dark Star Tech-style slab. Its own sheet so it never
    // has to out-specify the light-theme footer rules still in style.css.
    $footer_css = get_template_directory() . '/css/footer.css';
    if ( file_exists( $footer_css ) ) {
        wp_enqueue_style(
            'safestore-minimal-footer',
            get_template_directory_uri() . '/css/footer.css',
            array( 'safestore-minimal-style' ),
            (string) filemtime( $footer_css )
        );
    }

    $footer_js = get_template_directory() . '/js/footer-accordion.js';
    if ( file_exists( $footer_js ) ) {
        wp_enqueue_script(
            'safestore-minimal-footer-accordion',
            get_template_directory_uri() . '/js/footer-accordion.js',
            array(),
            (string) filemtime( $footer_js ),
            function_exists('safestore_perf_script_args') ? safestore_perf_script_args(true) : true
        );
    }

    wp_enqueue_script(
        'safestore-minimal-header-search-cat',
        get_template_directory_uri() . '/js/header-search-cat.js',
        array(),
        $version,
        function_exists('safestore_perf_script_args') ? safestore_perf_script_args(true) : true
    );

    if (is_page_template('page-home.php')) {
        wp_enqueue_script(
            'safestore-minimal-hero-slider',
            get_template_directory_uri() . '/js/hero-slider.js',
            array(),
            $version,
            function_exists('safestore_perf_script_args') ? safestore_perf_script_args(true) : true
        );
    }

    // Product-card enhancements (category "Show more / less"). Loaded wherever
    // product cards can render (shop, archives, single-product related, home).
    if (class_exists('WooCommerce')) {
        $shop_cards_path = get_template_directory() . '/js/shop-cards.js';
        if (file_exists($shop_cards_path)) {
            wp_enqueue_script(
                'safestore-minimal-shop-cards',
                get_template_directory_uri() . '/js/shop-cards.js',
                array(),
                (string) filemtime($shop_cards_path),
                function_exists('safestore_perf_script_args') ? safestore_perf_script_args(true) : true
            );
        }
    }
}
add_action('wp_enqueue_scripts', 'safestore_minimal_enqueue_assets');

function safestore_minimal_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    register_nav_menus(array(
        'primary'           => __('Primary Menu', 'safestore-minimal'),
        'header_categories' => __('Header Category Bar', 'safestore-minimal'),
    ));
}
add_action('after_setup_theme', 'safestore_minimal_setup');

/**
 * Turn off WooCommerce's in-place magnifier (jquery-zoom).
 *
 * js/pdp-zoom.js replaces it with a side-by-side lens + flyout; running both
 * puts two magnifiers on the same hover. Theme support stays declared so the
 * lightbox and gallery slider are untouched — flip this back to __return_true
 * to restore core behaviour.
 */
add_filter('woocommerce_single_product_zoom_enabled', '__return_false');

/**
 * Strip WooCommerce's default page wrappers and sidebar — the theme provides its own layout.
 */
add_action('init', function () {
    remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
    remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
    remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

    // Page title is rendered inside our own shop header.
    add_filter('woocommerce_show_page_title', '__return_false');

    // Shop / archives: use numbered pagination only (no “Showing X–Y of Z results”).
    remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);

    // Loop card: add-to-cart is rendered in theme card footer (not after the closing product link).
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
});

/**
 * Product grid card footer — WooCommerce loop add to cart (same callback as core loop).
 */
function safestore_minimal_product_card_loop_add_to_cart() {
    if (!function_exists('woocommerce_template_loop_add_to_cart')) {
        return;
    }
    $GLOBALS['safestore_minimal_loop_cart_context'] = true;
    woocommerce_template_loop_add_to_cart();
    unset($GLOBALS['safestore_minimal_loop_cart_context']);
}
add_action('safestore_minimal_product_card_footer', 'safestore_minimal_product_card_loop_add_to_cart', 10);

/**
 * @param array<string, mixed> $args    Loop add to cart args.
 * @param WC_Product           $product Product.
 * @return array<string, mixed>
 */
function safestore_minimal_loop_add_to_cart_args($args, $product) {
    if (empty($GLOBALS['safestore_minimal_loop_cart_context'])) {
        return $args;
    }
    $args['class'] = trim(($args['class'] ?? '') . ' sft-product-card__cart-btn');
    return $args;
}
add_filter('woocommerce_loop_add_to_cart_args', 'safestore_minimal_loop_add_to_cart_args', 10, 2);

/**
 * @param string     $link    Anchor HTML.
 * @param WC_Product $product Product.
 * @param array      $args    Args.
 */
function safestore_minimal_loop_add_to_cart_link($link, $product, $args) {
    if (empty($GLOBALS['safestore_minimal_loop_cart_context'])) {
        return $link;
    }

    // Match the icon to the CTA: a cart for a direct "Add to cart" (simple,
    // purchasable, in-stock products carry WooCommerce's ajax_add_to_cart class),
    // and an options/sliders glyph for "Select options" (variable products that
    // need a choice on the product page first).
    if (false !== strpos($link, 'ajax_add_to_cart')) {
        $icon = '<svg class="sft-product-card__cart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/><path d="M3 4h2l2.4 11.4a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.5L21 8H6"/></svg>';
    } else {
        $icon = '<svg class="sft-product-card__cart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><circle cx="10" cy="9" r="2.4"/><circle cx="15" cy="15" r="2.4"/></svg>';
    }
    $label = '<span class="sft-product-card__cart-label">' . esc_html($product->add_to_cart_text()) . '</span>';

    $count    = 0;
    $replaced = preg_replace('/<a(\s[^>]*)>[^<]*<\/a>/', '<a$1>' . $icon . $label . '</a>', $link, 1, $count);
    return ($count > 0) ? $replaced : $link;
}
add_filter('woocommerce_loop_add_to_cart_link', 'safestore_minimal_loop_add_to_cart_link', 10, 3);

/**
 * Shop loop: 4 columns, 12 products per page (grid scales 4→3→2→1 in CSS).
 */
add_filter('loop_shop_columns', function () { return 4; }, 99);
add_filter('loop_shop_per_page', function () { return 12; }, 99);

/**
 * Single product (PDP): breadcrumb, summary order, contact/delivery blocks.
 */
function safestore_minimal_is_product_page() {
    return function_exists('is_product') && is_product();
}

add_action(
    'wp',
    function () {
        if (!safestore_minimal_is_product_page()) {
            return;
        }

        add_action(
            'woocommerce_before_single_product',
            static function () {
                if (!function_exists('woocommerce_breadcrumb')) {
                    return;
                }
                woocommerce_breadcrumb(
                    array(
                        'delimiter'   => '<span class="sft-pdp-breadcrumb__sep" aria-hidden="true">/</span>',
                        'wrap_before' => '<nav class="sft-pdp-breadcrumb woocommerce-breadcrumb" aria-label="' . esc_attr__('Breadcrumb', 'safestore-minimal') . '">',
                        'wrap_after'  => '</nav>',
                        'before'      => '',
                        'after'       => '',
                        'home'        => _x('Home', 'breadcrumb', 'safestore-minimal'),
                    )
                );
            },
            6
        );

        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);

        add_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 7);
        add_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 11);
        add_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 18);
        add_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 22);
        add_action('woocommerce_product_meta_start', 'safestore_minimal_pdp_suppress_meta_taxonomies');
        add_action('woocommerce_product_meta_end', 'safestore_minimal_pdp_restore_meta_taxonomies');
        add_action('woocommerce_single_product_summary', 'safestore_minimal_pdp_contact_row', 26);
        add_action('woocommerce_single_product_summary', 'safestore_minimal_pdp_trust_line', 27);
        add_action('woocommerce_after_add_to_cart_button', 'safestore_minimal_pdp_buy_now_button');
        // After add-to-cart (priority 30): Compare / Wishlist | Share icon.
        add_action('woocommerce_single_product_summary', 'safestore_minimal_pdp_action_bar', 35);

        add_filter(
            'woocommerce_single_product_carousel_options',
            static function ($options) {
                $options['smoothHeight'] = false;
                return $options;
            }
        );
    }
);

/**
 * Hide SKU, category, and tag rows in the PDP meta box only.
 * Brand remains. Taxonomies and SKU stay in wp-admin and elsewhere.
 */
function safestore_minimal_pdp_hide_meta_terms($terms, $post_id, $taxonomy) {
    if ($taxonomy === 'product_cat' || $taxonomy === 'product_tag') {
        return false;
    }
    return $terms;
}

function safestore_minimal_pdp_suppress_meta_taxonomies() {
    add_filter('get_the_terms', 'safestore_minimal_pdp_hide_meta_terms', 10, 3);
    add_filter('wc_product_sku_enabled', '__return_false');
}

function safestore_minimal_pdp_restore_meta_taxonomies() {
    remove_filter('get_the_terms', 'safestore_minimal_pdp_hide_meta_terms', 10);
    remove_filter('wc_product_sku_enabled', '__return_false');
}

/**
 * WhatsApp + Messenger + Telegram + phone row (filterable URLs / numbers).
 */
function safestore_minimal_pdp_contact_row() {
    $wa = apply_filters('safestore_minimal_whatsapp_e164', '');
    $wa = preg_replace('/[^0-9]/', '', (string) $wa);
    $tel_raw = apply_filters('safestore_minimal_phone_tel', '+8801811892291');
    $tel_digits = preg_replace('/[^\d+]/', '', (string) $tel_raw);
    $profiles = function_exists('safestore_pdp_social_profiles') ? safestore_pdp_social_profiles() : array();
    $messenger = isset($profiles['messenger']) ? (string) $profiles['messenger'] : '';
    $telegram  = isset($profiles['telegram']) ? (string) $profiles['telegram'] : '';

    if ($wa === '' && $tel_digits === '' && $messenger === '' && $telegram === '') {
        return;
    }

    $tel_href = $tel_digits !== '' ? 'tel:' . $tel_digits : '';

    // Prefill WhatsApp with the current product for faster support.
    $wa_href = '';
    if ($wa !== '') {
        $wa_href = 'https://wa.me/' . $wa;
        global $product;
        if ($product instanceof WC_Product) {
            $prefill = sprintf(
                /* translators: 1: product name, 2: product URL */
                __("Hi SafeStoreBD! I'm interested in %1\$s (%2\$s). Can you help with availability or bulk pricing?", 'safestore-minimal'),
                $product->get_name(),
                get_permalink($product->get_id())
            );
            $wa_href = add_query_arg('text', $prefill, $wa_href);
        }
    }

    // Copy only for dialable phone — chat deep-links open the app directly.
    $tel_copy = ($tel_digits !== '' && function_exists('safestore_copy_button'))
        ? safestore_copy_button(
            array(
                'value' => $tel_digits,
                'noun'  => __('Phone number', 'safestore-minimal'),
                'class' => 'sft-pdp-contact__copy',
            )
        )
        : '';

    $has_channels = ($wa_href !== '' || $messenger !== '' || $telegram !== '');
    ?>
    <p class="sft-pdp-contact__label"><?php esc_html_e('Need help?', 'safestore-minimal'); ?></p>
    <div class="sft-pdp-contact" role="group" aria-label="<?php esc_attr_e('Product support', 'safestore-minimal'); ?>">
        <?php if ($has_channels) : ?>
            <div class="sft-pdp-contact__channels">
                <?php if ($wa_href !== '') : ?>
                    <a class="sft-pdp-contact__btn sft-pdp-contact__btn--wa" href="<?php echo esc_url($wa_href); ?>" target="_blank" rel="noopener noreferrer" data-sft-copy-init="1" aria-label="<?php esc_attr_e('WhatsApp', 'safestore-minimal'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.148-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <span class="sft-pdp-contact__btn-label"><?php esc_html_e('WhatsApp', 'safestore-minimal'); ?></span>
                    </a>
                <?php endif; ?>
                <?php if ($messenger !== '') : ?>
                    <a class="sft-pdp-contact__btn sft-pdp-contact__btn--messenger" href="<?php echo esc_url($messenger); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Messenger', 'safestore-minimal'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.19 5.41 3.14 7.17V22l2.88-1.58c.91.25 1.87.39 2.98.39 5.64 0 10.2-4.13 10.2-9.7S17.64 2 12 2zm1.01 13.06-2.61-2.78-5.1 2.78 5.6-5.94 2.67 2.76 5.04-2.76-5.6 5.94z"/></svg>
                        <span class="sft-pdp-contact__btn-label"><?php esc_html_e('Messenger', 'safestore-minimal'); ?></span>
                    </a>
                <?php endif; ?>
                <?php if ($telegram !== '') : ?>
                    <a class="sft-pdp-contact__btn sft-pdp-contact__btn--telegram" href="<?php echo esc_url($telegram); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Telegram', 'safestore-minimal'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.4 15.4 9.2 18.7c.4 0 .6-.2.8-.4l1.9-1.8 3.9 2.9c.7.4 1.2.2 1.4-.7l2.5-11.8c.2-.9-.3-1.3-1-1.1L4.3 10.2c-.9.3-.9.8-.2 1l3.7 1.2 8.6-5.4c.4-.2.8 0 .5.3L9.4 15.4z"/></svg>
                        <span class="sft-pdp-contact__btn-label"><?php esc_html_e('Telegram', 'safestore-minimal'); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($tel_href !== '') : ?>
            <div class="sft-pdp-contact__unit sft-pdp-contact__unit--call">
                <a class="sft-pdp-contact__btn sft-pdp-contact__btn--call" href="<?php echo esc_url($tel_href); ?>" data-sft-copy-init="1" aria-label="<?php esc_attr_e('Call us', 'safestore-minimal'); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span class="sft-pdp-contact__btn-label"><?php esc_html_e('Call us', 'safestore-minimal'); ?></span>
                </a>
                <?php echo $tel_copy; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Subtle trust line (avoid fake “viewers”; override via filter).
 */
function safestore_minimal_pdp_trust_line() {
    $line = apply_filters(
        'safestore_minimal_pdp_trust_line',
        __('Fast dispatch from Dhaka · Genuine brand stock · Bulk & corporate quotes', 'safestore-minimal')
    );
    if ($line === false || $line === '') {
        return;
    }
    echo '<p class="sft-pdp-trust">' . esc_html($line) . '</p>';
}

/**
 * Secondary checkout CTA — simple products link straight to checkout;
 * variable products (e.g. Safety Shoes sizes) submit the variations form
 * then redirect to checkout once a size is chosen.
 */
function safestore_minimal_pdp_buy_now_button() {
    global $product;
    if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
        return;
    }

    if ($product->is_type('simple')) {
        $url = add_query_arg('add-to-cart', $product->get_id(), wc_get_checkout_url());
        printf(
            '<a class="button sft-pdp-buy-now" href="%s" role="button">%s</a>',
            esc_url($url),
            esc_html__('Buy now', 'safestore-minimal')
        );
        return;
    }

    if ($product->is_type('variable')) {
        printf(
            '<button type="submit" name="safestore_buy_now" value="1" class="button sft-pdp-buy-now">%s</button>',
            esc_html__('Buy now', 'safestore-minimal')
        );
    }
}

/**
 * After a Buy now submit on a variable product, go to checkout.
 *
 * @param string $url Redirect URL.
 * @return string
 */
function safestore_minimal_buy_now_redirect($url) {
    if (isset($_REQUEST['safestore_buy_now'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return wc_get_checkout_url();
    }
    return $url;
}
add_filter('woocommerce_add_to_cart_redirect', 'safestore_minimal_buy_now_redirect', 20);

/**
 * Simple Safety Shoes "Buy now" must submit the cart form so the selected
 * size is posted as cart meta (not a bare add-to-cart checkout URL).
 */
add_action(
    'wp',
    static function () {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }
        if (!function_exists('safestore_pdp_needs_shoe_size_meta')) {
            return;
        }
        $product = wc_get_product(get_the_ID());
        if (!safestore_pdp_needs_shoe_size_meta($product)) {
            return;
        }
        remove_action('woocommerce_after_add_to_cart_button', 'safestore_minimal_pdp_buy_now_button');
        add_action(
            'woocommerce_after_add_to_cart_button',
            static function () {
                printf(
                    '<button type="submit" name="safestore_buy_now" value="1" class="button sft-pdp-buy-now">%s</button>',
                    esc_html__('Buy now', 'safestore-minimal')
                );
            },
            10
        );
    },
    20
);

/**
 * PDP tabs: clearer labels for long-form storytelling (reference site style).
 *
 * @param array<string, array<string, mixed>> $tabs Tabs.
 * @return array<string, array<string, mixed>>
 */
function safestore_minimal_product_tabs_labels($tabs) {
    if (isset($tabs['description'])) {
        $tabs['description']['title'] = __('Why choose this product', 'safestore-minimal');
    }
    if (isset($tabs['additional_information'])) {
        $tabs['additional_information']['title'] = __('Specifications', 'safestore-minimal');
    }
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'safestore_minimal_product_tabs_labels', 98);

/**
 * Hide redundant in-tab headings (tab labels already describe the section).
 */
add_filter('woocommerce_product_description_heading', '__return_false');
add_filter(
    'woocommerce_reviews_title',
    static function ($title, $count) {
        if ((int) $count > 0) {
            return $title;
        }
        return '';
    },
    10,
    2
);

/**
 * Remove WooCommerce sample "Photography by @cottonbro." credit from product copy.
 *
 * @param string $content Post content.
 * @return string
 */
function safestore_minimal_strip_sample_photo_credit($content) {
    if (!is_string($content) || $content === '') {
        return $content;
    }

    $content = preg_replace('/<p>\s*Photography by @cottonbro\.?\s*<\/p>/i', '', $content);
    $content = preg_replace('/\s*Photography by @cottonbro\.?\s*/i', '', $content);

    return trim($content);
}

add_filter('the_content', 'safestore_minimal_strip_sample_photo_credit', 25);

/**
 * One-time DB cleanup for demo product descriptions (WooCommerce sample data).
 */
function safestore_minimal_cleanup_sample_photo_credit() {
    if (get_option('safestore_stripped_cottonbro_v1')) {
        return;
    }

    $product_ids = get_posts(
        array(
            'post_type'      => array('product', 'product_variation'),
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        )
    );

    foreach ($product_ids as $product_id) {
        $post = get_post($product_id);
        if (!$post instanceof WP_Post || stripos($post->post_content, 'cottonbro') === false) {
            continue;
        }

        $cleaned = safestore_minimal_strip_sample_photo_credit($post->post_content);
        if ($cleaned !== $post->post_content) {
            wp_update_post(
                array(
                    'ID'           => $product_id,
                    'post_content' => $cleaned,
                )
            );
        }
    }

    update_option('safestore_stripped_cottonbro_v1', 1, false);
}
add_action('init', 'safestore_minimal_cleanup_sample_photo_credit', 99);

/**
 * Create Return & Refund Policy page if missing (footer link: /return-refund-policy/).
 */
function safestore_minimal_seed_refund_policy_page() {
	if ( get_option( 'safestore_refund_policy_page_v1' ) ) {
		return;
	}

	$slug = 'return-refund-policy';
	if ( ! get_page_by_path( $slug ) ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => __( 'Return & Refund Policy', 'safestore-minimal' ),
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'page-refund-policy.php' );
		}
	} else {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			update_post_meta( $page->ID, '_wp_page_template', 'page-refund-policy.php' );
		}
	}

	update_option( 'safestore_refund_policy_page_v1', 1, false );
}
add_action( 'after_switch_theme', 'safestore_minimal_seed_refund_policy_page' );
add_action(
	'init',
	static function () {
		if ( ! get_option( 'safestore_refund_policy_page_v1' ) ) {
			safestore_minimal_seed_refund_policy_page();
		}
	},
	20
);

/**
 * SEO meta for Refund & Policy template (when no SEO plugin).
 */
function safestore_minimal_is_refund_policy_page() {
	return is_page_template( 'page-refund-policy.php' );
}

add_action(
	'wp_head',
	static function () {
		if ( ! safestore_minimal_is_refund_policy_page() ) {
			return;
		}
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$desc = __( '7-day returns on unused industrial PPE in Bangladesh. Contact SafeStoreBD before sending items back.', 'safestore-minimal' );
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( get_permalink() ) );
	},
	1
);

add_filter(
	'pre_get_document_title',
	static function ( $title ) {
		if ( ! safestore_minimal_is_refund_policy_page() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return $title;
		}
		return __( 'Return & Refund Policy — Industrial PPE Bangladesh | SafeStoreBD', 'safestore-minimal' );
	}
);

/**
 * FAQ sections for page-faq.php (Bangladesh market: payments, COD, courier, PPE).
 *
 * @return array<int, array{id: string, title: string, intro?: string, items: array<int, array{q: string, a: string}>}>
 */
function safestore_minimal_get_faq_sections() {
	$returns_url  = home_url( '/return-refund-policy/' );
	$shipping_url = home_url( '/shipping-delivery/' );
	$track_url    = home_url( '/track-order/' );
	$bulk_url     = home_url( '/bulk-orders/' );

	return array(
		array(
			'id'    => 'payment',
			'title' => __( 'Payment', 'safestore-minimal' ),
			'items' => array(
				array(
					'q' => __( 'Which payment methods do you accept?', 'safestore-minimal' ),
					'a' => __( '<strong>bKash</strong>, <strong>Nagad</strong>, <strong>Rocket</strong>, <strong>Upay</strong>, bank transfer, and <strong>COD</strong> (where available). For wallet payments, send your transaction ID and order number on WhatsApp.', 'safestore-minimal' ),
				),
				array(
					'q' => __( 'How does cash on delivery work?', 'safestore-minimal' ),
					'a' => __( 'Pay the courier when the parcel arrives. Check the box before you pay; if it looks damaged or wrong, refuse it and WhatsApp us with your order number.', 'safestore-minimal' ),
				),
			),
		),
		array(
			'id'    => 'delivery',
			'title' => __( 'Delivery', 'safestore-minimal' ),
			'items' => array(
				array(
					'q' => __( 'How long does delivery take?', 'safestore-minimal' ),
					'a' => sprintf(
						/* translators: 1: office area (e.g. Pallabi, Dhaka), 2: shipping page URL */
						__( 'Dispatch from %1$s: inside Dhaka usually within <strong>24 hours</strong> (Sat–Thu); outside Dhaka <strong>2–3 business days</strong>. <a href="%2$s">Shipping details</a>.', 'safestore-minimal' ),
						esc_html( safestore_minimal_get_office_location_short() ),
						esc_url( $shipping_url )
					),
				),
				array(
					'q' => __( 'Can I pick up or track my order?', 'safestore-minimal' ),
					'a' => sprintf(
						/* translators: 1: office address, 2: track order URL */
						__( 'Free pickup at our Dhaka office: %1$s. Track on the <a href="%2$s">track order</a> page or WhatsApp us with your order number.', 'safestore-minimal' ),
						esc_html( safestore_minimal_get_pickup_address() ),
						esc_url( $track_url )
					),
				),
			),
		),
		array(
			'id'    => 'returns-help',
			'title' => __( 'Returns & help', 'safestore-minimal' ),
			'items' => array(
				array(
					'q' => __( 'What is your return policy?', 'safestore-minimal' ),
					'a' => sprintf(
						/* translators: %s: return policy page URL */
						__( 'Unused items in original packaging: <strong>7 days</strong> from delivery. Contact us before sending anything back. Full policy: <a href="%s">returns</a>.', 'safestore-minimal' ),
						esc_url( $returns_url )
					),
				),
				array(
					'q' => __( 'Do you take bulk or factory orders?', 'safestore-minimal' ),
					'a' => sprintf(
						/* translators: %s: bulk orders page URL */
						__( 'Yes. Request a quote on <a href="%s">bulk orders</a> or WhatsApp your quantity and delivery area.', 'safestore-minimal' ),
						esc_url( $bulk_url )
					),
				),
				array(
					'q' => __( 'How do I contact support?', 'safestore-minimal' ),
					'a' => __( 'WhatsApp <strong>+880 1811-892291</strong> or call/email on this site. Open <strong>Sat–Thu, 9am–8pm</strong>; closed Fridays.', 'safestore-minimal' ),
				),
			),
		),
	);
}

/**
 * Create FAQ page if missing (footer link: /faqs/).
 */
function safestore_minimal_seed_faq_page() {
	if ( get_option( 'safestore_faq_page_v1' ) ) {
		return;
	}

	$slug = 'faqs';
	if ( ! get_page_by_path( $slug ) ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => __( 'Frequently Asked Questions', 'safestore-minimal' ),
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'page-faq.php' );
		}
	} else {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			update_post_meta( $page->ID, '_wp_page_template', 'page-faq.php' );
		}
	}

	update_option( 'safestore_faq_page_v1', 1, false );
}
add_action( 'after_switch_theme', 'safestore_minimal_seed_faq_page' );
add_action(
	'init',
	static function () {
		if ( ! get_option( 'safestore_faq_page_v1' ) ) {
			safestore_minimal_seed_faq_page();
		}
	},
	20
);

function safestore_minimal_is_faq_page() {
	return is_page_template( 'page-faq.php' );
}

add_action(
	'wp_head',
	static function () {
		if ( ! safestore_minimal_is_faq_page() ) {
			return;
		}
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$desc = __( 'FAQ for SafeStoreBD: bKash, Nagad, COD, nationwide delivery, PPE returns, and bulk orders in Bangladesh.', 'safestore-minimal' );
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( get_permalink() ) );

		$entities = array();
		foreach ( safestore_minimal_get_faq_sections() as $section ) {
			foreach ( $section['items'] as $item ) {
				$entities[] = array(
					'@type'          => 'Question',
					'name'           => wp_strip_all_tags( $item['q'] ),
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => wp_strip_all_tags( $item['a'] ),
					),
				);
			}
		}
		$schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	},
	1
);

add_filter(
	'pre_get_document_title',
	static function ( $title ) {
		if ( ! safestore_minimal_is_faq_page() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return $title;
		}
		return __( 'FAQ — PPE, Delivery & Payments Bangladesh | SafeStoreBD', 'safestore-minimal' );
	}
);

/**
 * Structured office / pickup address — single source of truth.
 *
 * Both the human-readable address string and the PostalAddress structured
 * data derive from these parts, so the footer copy and the schema can never
 * drift apart when the address changes. Filter individual parts via
 * `safestore_minimal_address_parts`.
 *
 * @return array{street:string, locality:string, postal_code:string, country:string, country_name:string}
 */
function safestore_minimal_get_address_parts() {
	return apply_filters(
		'safestore_minimal_address_parts',
		array(
			'street'       => __( '17/5/1 Alabdirtek, Pallabi', 'safestore-minimal' ),
			'locality'     => __( 'Dhaka', 'safestore-minimal' ),
			'postal_code'  => '1207',
			'country'      => 'BD',
			'country_name' => __( 'Bangladesh', 'safestore-minimal' ),
		)
	);
}

/**
 * Office / pickup address as a single display string (shared site-wide).
 */
function safestore_minimal_get_pickup_address() {
	$parts   = safestore_minimal_get_address_parts();
	$address = sprintf(
		/* translators: 1: street address, 2: city/locality, 3: postal code, 4: country name */
		_x( '%1$s, %2$s %3$s, %4$s', 'office postal address', 'safestore-minimal' ),
		$parts['street'],
		$parts['locality'],
		$parts['postal_code'],
		$parts['country_name']
	);

	return apply_filters( 'safestore_minimal_pdp_pickup_address', $address );
}

/**
 * Short office area label for dispatch copy (e.g. Pallabi, Dhaka).
 */
function safestore_minimal_get_office_location_short() {
	return apply_filters(
		'safestore_minimal_office_location_short',
		__( 'Pallabi, Dhaka', 'safestore-minimal' )
	);
}

/* -------------------------------------------------------------------------
 * Reusable contact component
 *
 * A single icon-left / detail-right contact row used everywhere contact
 * information appears (footer, homepage support section, …) so the icon,
 * spacing, alignment, and typography stay identical site-wide. All icons
 * inherit `currentColor`, so the brand colour is applied purely in CSS via
 * the `--sft-contact-accent` custom property.
 * ---------------------------------------------------------------------- */

/**
 * Inline SVG glyph for a contact method. Returns trusted, self-authored
 * markup (no user input), safe to echo without escaping.
 *
 * @param string $type phone|whatsapp|email|location|clock|help.
 * @return string SVG markup, or '' for an unknown type.
 */
function safestore_contact_icon_svg( $type ) {
	if ( 'whatsapp' === $type && function_exists( 'safestore_wa_icon_svg' ) ) {
		return safestore_wa_icon_svg( 'sft-contact-item__glyph' );
	}

	$icons = array(
		'phone'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
		'email'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
		'location' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
		'clock'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
		'help'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/><path d="M9 17h4"/></svg>',
	);

	return isset( $icons[ $type ] ) ? $icons[ $type ] : '';
}

/**
 * Inner markup shared by the clickable and static contact rows.
 *
 * @param string $icon   Trusted SVG markup for the icon chip.
 * @param string $label  Method label; rendered but visually hidden (CSS), so it
 *                       stays available to screen readers (optional).
 * @param string $value  Primary contact value.
 * @param string $detail Small line below the value (optional).
 * @return string
 */
function safestore_contact_item_markup( $icon, $label, $value, $detail ) {
	$body = '';
	if ( '' !== (string) $label ) {
		$body .= '<span class="sft-contact-item__label">' . esc_html( $label ) . '</span>';
	}
	$body .= '<span class="sft-contact-item__value">' . esc_html( $value ) . '</span>';
	if ( '' !== (string) $detail ) {
		$body .= '<span class="sft-contact-item__detail">' . esc_html( $detail ) . '</span>';
	}

	return '<span class="sft-contact-item__icon" aria-hidden="true">' . $icon . '</span>'
		. '<span class="sft-contact-item__body">' . $body . '</span>';
}

/**
 * The site's public contact email addresses, in display order (primary first).
 * Single source of truth — filter `safestore_contact_emails` to change them.
 *
 * @return string[]
 */
function safestore_contact_email_addresses() {
	$emails = apply_filters(
		'safestore_contact_emails',
		array(
			'contact@safestorebd.com',
			'bdsafestore@gmail.com',
		)
	);

	return array_values( array_filter( array_map( 'trim', (array) $emails ) ) );
}

/**
 * Build a safe mailto: href for a contact email. Falls back to esc_attr if a
 * protocol filter empties esc_url() — keeps the action working under strict
 * URL sanitizers.
 *
 * @param string $email Raw email address.
 * @return string Escaped href ready for an attribute, or '' when invalid.
 */
function safestore_contact_mailto_href( $email ) {
	$safe = sanitize_email( (string) $email );
	if ( '' === $safe || ! is_email( $safe ) ) {
		return '';
	}

	$mailto = 'mailto:' . $safe;
	$href   = esc_url( $mailto, array( 'mailto' ) );
	if ( '' === $href ) {
		$href = esc_attr( $mailto );
	}

	return $href;
}

/**
 * Stacked mailto links for the site contact emails (one address per line;
 * .sft-contact-emails CSS keeps each address from wrapping mid-string).
 * When copy-to-clipboard is enabled, each address gets its own copy button.
 * Reusable anywhere the contact email is shown. Returns trusted markup.
 *
 * @param array $args {
 *     @type string $class      Root span class.
 *     @type bool   $copy       Whether to render a per-email copy button. Default true.
 *     @type string $copy_class Extra classes on each copy button.
 * }
 * @return string
 */
function safestore_contact_email_links( $args = array() ) {
	$args   = wp_parse_args(
		$args,
		array(
			'class'      => 'sft-contact-emails',
			'copy'       => true,
			'copy_class' => 'sft-copy-btn--inline',
		)
	);
	$emails = safestore_contact_email_addresses();
	if ( empty( $emails ) ) {
		return '';
	}

	$wants_copy = (bool) $args['copy'] && function_exists( 'safestore_copy_button' );

	$items = array();
	foreach ( $emails as $email ) {
		$href = safestore_contact_mailto_href( $email );
		if ( '' === $href ) {
			continue;
		}

		$safe_mail = sanitize_email( $email );
		$send_label = sprintf(
			/* translators: %s: email address */
			__( 'Send email to %s', 'safestore-minimal' ),
			$safe_mail
		);

		$copy_button = '';
		if ( $wants_copy ) {
			$copy = function_exists( 'safestore_copy_contact_value' )
				? safestore_copy_contact_value( 'email', $email )
				: array(
					'value' => $email,
					'noun'  => __( 'Email address', 'safestore-minimal' ),
				);
			if ( '' !== $copy['value'] ) {
				// Noun is the address itself so each button has a unique name
				// ("Copy contact@…") when two emails share a row.
				$copy_button = safestore_copy_button(
					array(
						'value' => $copy['value'],
						'noun'  => $copy['value'],
						'class' => $args['copy_class'],
					)
				);
			}
		}

		// data-sft-copy-init tells the auto-enhancer this mailto already has a
		// server-rendered copy control, so it must not append a second button.
		$init_attr = '' !== $copy_button ? ' data-sft-copy-init="1"' : '';
		$link      = sprintf(
			'<a class="sft-contact-emails__link" href="%1$s" aria-label="%2$s" title="%2$s"%3$s>%4$s</a>',
			$href,
			esc_attr( $send_label ),
			$init_attr,
			esc_html( $email )
		);

		$items[] = '<span class="sft-contact-emails__item">' . $link . $copy_button . '</span>';
	}

	return '<span class="' . esc_attr( $args['class'] ) . '">'
		. implode( '', $items )
		. '</span>';
}

/**
 * Direct mailto action chip for a single email (mirrors phone / WhatsApp).
 *
 * @param string $email Email address.
 * @param string $icon  Trusted SVG markup.
 * @return string
 */
function safestore_contact_email_action_link( $email, $icon ) {
	$href = safestore_contact_mailto_href( $email );
	$safe = sanitize_email( $email );
	if ( '' === $href || '' === $safe ) {
		return '';
	}

	$label = sprintf(
		/* translators: %s: email address */
		__( 'Send email to %s', 'safestore-minimal' ),
		$safe
	);

	return sprintf(
		'<a class="sft-contact-item__action" href="%1$s" aria-label="%2$s" title="%2$s" data-sft-copy-init="1"><span class="sft-contact-item__icon" aria-hidden="true">%3$s</span></a>',
		$href,
		esc_attr( $label ),
		$icon
	);
}

/**
 * Multi-email action: disclosure that lets the user choose which address to open.
 * Uses <details> so the menu works without JS; the companion script adds Escape,
 * outside-click close, one-open-at-a-time, and arrow-key focus.
 *
 * @param string[] $emails Validated email addresses.
 * @param string   $icon   Trusted SVG markup for the trigger chip.
 * @return string
 */
function safestore_contact_email_chooser( $emails, $icon ) {
	$emails = array_values( array_filter( array_map( 'sanitize_email', (array) $emails ) ) );
	$emails = array_values( array_filter( $emails, 'is_email' ) );
	if ( count( $emails ) < 2 ) {
		return '';
	}

	static $chooser_i = 0;
	++$chooser_i;
	$panel_id = 'sft-email-chooser-panel-' . $chooser_i;

	$chevron = '<svg class="sft-contact-email-chooser__caret" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 4.5 6 7.5 9 4.5"/></svg>';

	$trigger_label = __( 'Choose an email address to contact', 'safestore-minimal' );
	$menu_label    = __( 'Email addresses', 'safestore-minimal' );

	$items = array();
	foreach ( $emails as $email ) {
		$href = safestore_contact_mailto_href( $email );
		if ( '' === $href ) {
			continue;
		}
		$item_label = sprintf(
			/* translators: %s: email address */
			__( 'Send email to %s', 'safestore-minimal' ),
			$email
		);
		$items[] = sprintf(
			'<a class="sft-contact-email-chooser__option" role="menuitem" href="%1$s" aria-label="%2$s" data-sft-copy-init="1"><span class="sft-contact-email-chooser__option-icon" aria-hidden="true">%3$s</span><span class="sft-contact-email-chooser__option-text">%4$s</span></a>',
			$href,
			esc_attr( $item_label ),
			$icon,
			esc_html( $email )
		);
	}

	if ( empty( $items ) ) {
		return '';
	}

	return sprintf(
		'<details class="sft-contact-email-chooser" data-sft-email-chooser>'
		. '<summary class="sft-contact-item__action sft-contact-email-chooser__trigger" aria-haspopup="menu" aria-expanded="false" aria-controls="%1$s" aria-label="%2$s" title="%2$s">'
		. '<span class="sft-contact-item__icon" aria-hidden="true">%3$s%4$s</span>'
		. '</summary>'
		. '<div id="%1$s" class="sft-contact-email-chooser__panel" role="menu" aria-label="%5$s">'
		. '%6$s'
		. '</div>'
		. '</details>',
		esc_attr( $panel_id ),
		esc_attr( $trigger_label ),
		$icon,
		$chevron,
		esc_attr( $menu_label ),
		implode( '', $items )
	);
}

/**
 * Full contact row for the email addresses — lead icon, stacked addresses with
 * inline copy, and a clear action: direct mailto when there is one address, or
 * a choose-email disclosure when there are two or more. Used by the footer and
 * homepage support section.
 *
 * @param array $args { @type string $label, @type string $detail, @type string $class }
 * @return string
 */
function safestore_contact_emails_row( $args = array() ) {
	$args  = wp_parse_args(
		$args,
		array(
			'label'  => __( 'Email', 'safestore-minimal' ),
			'detail' => '',
			'class'  => '',
		)
	);
	$links = safestore_contact_email_links();
	if ( '' === $links ) {
		return '';
	}

	$emails   = safestore_contact_email_addresses();
	$primary  = isset( $emails[0] ) ? sanitize_email( $emails[0] ) : '';
	$icon     = safestore_contact_icon_svg( 'email' );
	$copyable = function_exists( 'safestore_copy_to_clipboard_enabled' ) && safestore_copy_to_clipboard_enabled();

	$body = '';
	if ( '' !== (string) $args['label'] ) {
		$body .= '<span class="sft-contact-item__label">' . esc_html( $args['label'] ) . '</span>';
	}
	$body .= '<span class="sft-contact-item__value">' . $links . '</span>';
	if ( '' !== (string) $args['detail'] ) {
		$body .= '<span class="sft-contact-item__detail">' . esc_html( $args['detail'] ) . '</span>';
	}

	$classes = 'sft-contact-item sft-contact-item--email sft-contact-item--emails';
	if ( $copyable ) {
		$classes .= ' sft-contact-item--copyable';
	}

	// Multi-address: explicit chooser so the action chip is never ambiguous.
	// Single address: same direct mailto chip pattern as phone / WhatsApp.
	$action = '';
	if ( count( $emails ) > 1 ) {
		$action = safestore_contact_email_chooser( $emails, $icon );
		if ( '' !== $action ) {
			$classes .= ' sft-contact-item--email-chooser';
		}
	} elseif ( '' !== $primary && is_email( $primary ) ) {
		$action = safestore_contact_email_action_link( $primary, $icon );
	}

	if ( '' !== $action ) {
		return sprintf(
			'<div class="%1$s"><span class="sft-contact-item__lead" aria-hidden="true">%2$s</span><span class="sft-contact-item__body">%3$s</span>%4$s</div>',
			esc_attr( trim( $classes . ' ' . $args['class'] ) ),
			$icon,
			$body,
			$action
		);
	}

	return sprintf(
		'<div class="%1$s"><span class="sft-contact-item__icon" aria-hidden="true">%2$s</span><span class="sft-contact-item__body">%3$s</span></div>',
		esc_attr( trim( $classes . ' ' . $args['class'] ) ),
		$icon,
		$body
	);
}

/**
 * Enqueue the multi-email chooser enhancer (Escape, outside click, focus).
 * Markup works without this script via native <details>.
 */
function safestore_contact_email_chooser_enqueue() {
	if ( is_admin() ) {
		return;
	}

	$js_path = get_template_directory() . '/js/contact-email-chooser.js';
	if ( ! file_exists( $js_path ) ) {
		return;
	}

	wp_enqueue_script(
		'safestore-contact-email-chooser',
		get_template_directory_uri() . '/js/contact-email-chooser.js',
		array(),
		(string) filemtime( $js_path ),
		function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true
	);
}
add_action( 'wp_enqueue_scripts', 'safestore_contact_email_chooser_enqueue', 21 );

/**
 * Render a clickable contact item. Copyable methods (phone / WhatsApp / email)
 * use [lead icon] [value + inline copy] … [action chip]; non-copyable methods
 * (e.g. Help Center) stay a single anchor with the brand icon on the left.
 * Returns markup; callers echo it (contains trusted SVG).
 *
 * @param array $args {
 *     @type string $type   phone|whatsapp|email (selects icon + auto-builds href). Default 'phone'.
 *     @type string $value  Display value, e.g. "+880 1811-892291". Required.
 *     @type string $href   Link target. Auto-built from $type + $value when omitted.
 *     @type string $label  Method label, e.g. "Call us" — not shown; used as the aria-label + tooltip.
 *     @type string $detail Small line below the value.
 *     @type string $icon   Custom trusted SVG (overrides the type icon).
 *     @type bool   $external Open in a new tab. Defaults to true for WhatsApp.
 *     @type string $class  Extra classes on the root element.
 * }
 * @return string
 */
function safestore_contact_item( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'type'     => 'phone',
			'value'    => '',
			'href'     => '',
			'label'    => '',
			'detail'   => '',
			'icon'     => '',
			'external' => null,
			'class'    => '',
		)
	);

	$type  = (string) $args['type'];
	$value = (string) $args['value'];
	$href  = (string) $args['href'];

	if ( '' === $href && '' !== $value ) {
		if ( 'email' === $type ) {
			$href = 'mailto:' . $value;
		} elseif ( 'phone' === $type ) {
			$href = 'tel:' . preg_replace( '/[^\d+]/', '', $value );
		} elseif ( 'whatsapp' === $type ) {
			$href = 'https://wa.me/' . preg_replace( '/\D/', '', $value );
		}
	}

	if ( '' === $value || '' === $href ) {
		return '';
	}

	$icon     = '' !== $args['icon'] ? $args['icon'] : safestore_contact_icon_svg( $type );
	$external = is_null( $args['external'] ) ? ( 'whatsapp' === $type ) : (bool) $args['external'];

	$names  = array(
		'phone'    => __( 'Phone', 'safestore-minimal' ),
		'whatsapp' => __( 'WhatsApp', 'safestore-minimal' ),
		'email'    => __( 'Email', 'safestore-minimal' ),
	);
	$method = '' !== $args['label'] ? $args['label'] : ( isset( $names[ $type ] ) ? $names[ $type ] : ucfirst( $type ) );

	$root_class = trim( preg_replace( '/\s+/', ' ', 'sft-contact-item sft-contact-item--' . $type . ' ' . $args['class'] ) );
	$aria_label = trim( wp_strip_all_tags( $method . ': ' . $value ) );
	$ext_attrs  = $external ? ' target="_blank" rel="noopener noreferrer"' : '';

	// Copyable methods (phone / WhatsApp / email) use the reference layout:
	// [lead icon] [value] [inline copy] … [action chip]. Everything else —
	// the Help Center link, for instance — stays a plain row.
	$copy        = safestore_copy_contact_value( $type, $value );
	$copy_button = '' !== $copy['value']
		? safestore_copy_button(
			array(
				'value' => $copy['value'],
				'noun'  => $copy['noun'],
				'class' => 'sft-copy-btn--inline',
			)
		)
		: '';

	if ( '' === $copy_button ) {
		return sprintf(
			'<a class="%1$s" href="%2$s"%3$s aria-label="%4$s" title="%5$s">%6$s</a>',
			esc_attr( $root_class ),
			esc_url( $href ),
			$ext_attrs,
			esc_attr( $aria_label ),
			esc_attr( $method ),
			safestore_contact_item_markup( $icon, $args['label'], $value, $args['detail'] )
		);
	}

	$body = '';
	if ( '' !== (string) $args['label'] ) {
		$body .= '<span class="sft-contact-item__label">' . esc_html( $args['label'] ) . '</span>';
	}
	// Value stays a real link (click-to-call / chat / mail). data-sft-copy-init
	// prevents the auto-enhancer from injecting a second copy control.
	$body .= '<span class="sft-contact-item__value-row">'
		. '<a class="sft-contact-item__value" href="' . esc_url( $href ) . '"' . $ext_attrs . ' data-sft-copy-init="1">' . esc_html( $value ) . '</a>'
		. $copy_button
		. '</span>';
	if ( '' !== (string) $args['detail'] ) {
		$body .= '<span class="sft-contact-item__detail">' . esc_html( $args['detail'] ) . '</span>';
	}

	return sprintf(
		'<div class="%1$s"><span class="sft-contact-item__lead" aria-hidden="true">%2$s</span><span class="sft-contact-item__body">%3$s</span><a class="sft-contact-item__action" href="%4$s"%5$s aria-label="%6$s" title="%7$s" data-sft-copy-init="1"><span class="sft-contact-item__icon" aria-hidden="true">%8$s</span></a></div>',
		esc_attr( $root_class . ' sft-contact-item--copyable' ),
		$icon,
		$body,
		esc_url( $href ),
		$ext_attrs,
		esc_attr( $aria_label ),
		esc_attr( $method ),
		$icon
	);
}

/**
 * Render a static (non-clickable) contact line — same layout as
 * safestore_contact_item(), used for address, opening hours, etc.
 *
 * @param array $args type, value (required), label, detail, icon, class, copy.
 *                    `copy` is null (auto — on for the address row), or a bool
 *                    to force the copy button on/off for this row.
 * @return string
 */
function safestore_contact_line( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'type'   => 'info',
			'value'  => '',
			'label'  => '',
			'detail' => '',
			'icon'   => '',
			'class'  => '',
			'copy'   => null,
		)
	);

	if ( '' === (string) $args['value'] ) {
		return '';
	}

	$icon = '' !== $args['icon'] ? $args['icon'] : safestore_contact_icon_svg( $args['type'] );

	// The label is kept as a screen-reader-only span (via the markup helper) and
	// as a hover tooltip, but is not shown inline.
	$title = '' !== (string) $args['label'] ? ' title="' . esc_attr( $args['label'] ) . '"' : '';

	// Copy is on by default only where the value is worth pasting elsewhere —
	// the address. Opening hours and similar prose rows stay plain.
	$copy        = safestore_copy_contact_value( $args['type'], $args['value'] );
	$wants_copy  = is_null( $args['copy'] ) ? ( '' !== $copy['value'] ) : (bool) $args['copy'];
	$copy_button = '';

	if ( $wants_copy ) {
		$copy_button = safestore_copy_button(
			array(
				'value' => '' !== $copy['value'] ? $copy['value'] : (string) $args['value'],
				'noun'  => '' !== $copy['noun'] ? $copy['noun'] : __( 'Value', 'safestore-minimal' ),
			)
		);
	}

	$classes = 'sft-contact-item sft-contact-item--static sft-contact-item--' . $args['type'] . ' ' . $args['class'];
	if ( '' !== $copy_button ) {
		$classes .= ' sft-contact-item--copyable';
	}

	return sprintf(
		'<div class="%1$s"%2$s>%3$s%4$s</div>',
		esc_attr( trim( preg_replace( '/\s+/', ' ', $classes ) ) ),
		$title,
		safestore_contact_item_markup( $icon, $args['label'], $args['value'], $args['detail'] ),
		$copy_button
	);
}

/**
 * Shipping zones for page-shipping.php (filterable).
 *
 * @return array<int, array{zone: string, time: string, cost: string}>
 */
function safestore_minimal_get_shipping_zones() {
	return apply_filters(
		'safestore_minimal_shipping_zones',
		array(
			array(
				'zone'  => __( 'Inside Dhaka', 'safestore-minimal' ),
				'time'  => __( '~24 hours dispatch (Sat–Thu)', 'safestore-minimal' ),
				'cost'  => __( 'From ৳80', 'safestore-minimal' ),
			),
			array(
				'zone'  => __( 'Outside Dhaka', 'safestore-minimal' ),
				'time'  => __( '2–3 business days after dispatch', 'safestore-minimal' ),
				'cost'  => __( 'From ৳135+', 'safestore-minimal' ),
			),
			array(
				'zone'  => __( 'Pickup — Pallabi office', 'safestore-minimal' ),
				'time'  => __( 'When order is ready', 'safestore-minimal' ),
				'cost'  => __( 'Free', 'safestore-minimal' ),
			),
		)
	);
}

/**
 * Create Shipping page if missing (footer link: /shipping-delivery/).
 */
function safestore_minimal_seed_shipping_page() {
	if ( get_option( 'safestore_shipping_page_v1' ) ) {
		return;
	}

	$slug = 'shipping-delivery';
	if ( ! get_page_by_path( $slug ) ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => __( 'Shipping & Delivery', 'safestore-minimal' ),
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'page-shipping.php' );
		}
	} else {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			update_post_meta( $page->ID, '_wp_page_template', 'page-shipping.php' );
		}
	}

	update_option( 'safestore_shipping_page_v1', 1, false );
}
add_action( 'after_switch_theme', 'safestore_minimal_seed_shipping_page' );
add_action(
	'init',
	static function () {
		if ( ! get_option( 'safestore_shipping_page_v1' ) ) {
			safestore_minimal_seed_shipping_page();
		}
	},
	20
);

function safestore_minimal_is_shipping_page() {
	return is_page_template( 'page-shipping.php' );
}

add_action(
	'wp_head',
	static function () {
		if ( ! safestore_minimal_is_shipping_page() ) {
			return;
		}
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$desc = __( 'Courier delivery across Bangladesh and free Pallabi office pickup. Dhaka from ৳80, outside Dhaka from ৳135+. Track your SafeStoreBD order.', 'safestore-minimal' );
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( get_permalink() ) );
	},
	1
);

add_filter(
	'pre_get_document_title',
	static function ( $title ) {
		if ( ! safestore_minimal_is_shipping_page() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return $title;
		}
		return __( 'Shipping & Delivery — Nationwide PPE Bangladesh | SafeStoreBD', 'safestore-minimal' );
	}
);

/**
 * Open roles for page-careers.php (filterable).
 *
 * @return array<int, array{title: string, type: string, summary: string}>
 */
function safestore_minimal_get_career_openings() {
	return apply_filters(
		'safestore_minimal_career_openings',
		array(
			array(
				'title'   => __( 'Warehouse & packing associate', 'safestore-minimal' ),
				'type'    => __( 'Full-time · Pallabi, Dhaka', 'safestore-minimal' ),
				'summary' => __( 'Receive stock, check PPE against orders, pack for courier, and keep the store organised. Experience in warehouse or retail is a plus.', 'safestore-minimal' ),
			),
			array(
				'title'   => __( 'Delivery & logistics coordinator', 'safestore-minimal' ),
				'type'    => __( 'Full-time · Pallabi, Dhaka', 'safestore-minimal' ),
				'summary' => __( 'Book couriers, share tracking with customers, and follow up on failed deliveries — common in Bangladesh e-commerce. Must be comfortable on the phone.', 'safestore-minimal' ),
			),
			array(
				'title'   => __( 'B2B sales executive', 'safestore-minimal' ),
				'type'    => __( 'Full-time · Dhaka (field visits)', 'safestore-minimal' ),
				'summary' => __( 'Quote PPE for factories, construction sites, and workshops. Build relationships with safety officers and procurement teams.', 'safestore-minimal' ),
			),
			array(
				'title'   => __( 'Customer support (WhatsApp & phone)', 'safestore-minimal' ),
				'type'    => __( 'Full-time · Pallabi, Dhaka', 'safestore-minimal' ),
				'summary' => __( 'Answer product, order, and delivery questions in Bengali and English. Clear writing on WhatsApp is essential.', 'safestore-minimal' ),
			),
		)
	);
}

/**
 * Create Careers page if missing (footer link: /careers/).
 */
function safestore_minimal_seed_careers_page() {
	if ( get_option( 'safestore_careers_page_v1' ) ) {
		return;
	}

	$slug = 'careers';
	if ( ! get_page_by_path( $slug ) ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => __( 'Careers', 'safestore-minimal' ),
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'page-careers.php' );
		}
	} else {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			update_post_meta( $page->ID, '_wp_page_template', 'page-careers.php' );
		}
	}

	update_option( 'safestore_careers_page_v1', 1, false );
}
add_action( 'after_switch_theme', 'safestore_minimal_seed_careers_page' );
add_action(
	'init',
	static function () {
		if ( ! get_option( 'safestore_careers_page_v1' ) ) {
			safestore_minimal_seed_careers_page();
		}
	},
	20
);

function safestore_minimal_is_careers_page() {
	return is_page_template( 'page-careers.php' );
}

add_action(
	'wp_head',
	static function () {
		if ( ! safestore_minimal_is_careers_page() ) {
			return;
		}
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$desc = __( 'Careers at SafeStoreBD: warehouse, logistics, B2B sales, and customer support roles in Pallabi, Dhaka — industrial PPE for Bangladesh.', 'safestore-minimal' );
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( get_permalink() ) );

		$jobs          = array();
		$address_parts = safestore_minimal_get_address_parts();
		foreach ( safestore_minimal_get_career_openings() as $job ) {
			$jobs[] = array(
				'@type'    => 'JobPosting',
				'title'    => $job['title'],
				'description' => $job['summary'],
				'employmentType' => 'FULL_TIME',
				'hiringOrganization' => array(
					'@type' => 'Organization',
					'name'  => 'SafeStoreBD',
					'sameAs' => home_url( '/' ),
				),
				'jobLocation' => array(
					'@type'   => 'Place',
					'address' => array(
						'@type'           => 'PostalAddress',
						'streetAddress'   => $address_parts['street'],
						'addressLocality' => $address_parts['locality'],
						'postalCode'      => $address_parts['postal_code'],
						'addressCountry'  => $address_parts['country'],
					),
				),
			);
		}
		if ( ! empty( $jobs ) ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $jobs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
		}
	},
	1
);

add_filter(
	'pre_get_document_title',
	static function ( $title ) {
		if ( ! safestore_minimal_is_careers_page() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return $title;
		}
		return __( 'Careers — Industrial PPE Jobs Dhaka | SafeStoreBD', 'safestore-minimal' );
	}
);

/**
 * Order tracking steps for page-track-order.php (filterable).
 *
 * @return array<int, array{title: string, text: string}>
 */
function safestore_minimal_get_track_steps() {
	return apply_filters(
		'safestore_minimal_track_steps',
		array(
			array(
				'title' => __( 'Order confirmed', 'safestore-minimal' ),
				'text'  => __( 'Payment received or COD approved — we prepare your PPE at our Pallabi office.', 'safestore-minimal' ),
			),
			array(
				'title' => __( 'Packed & dispatched', 'safestore-minimal' ),
				'text'  => __( 'Handed to courier (inside Dhaka ~24h, outside 2–3 business days).', 'safestore-minimal' ),
			),
			array(
				'title' => __( 'Out for delivery', 'safestore-minimal' ),
				'text'  => __( 'Courier may call before arrival — especially on COD orders.', 'safestore-minimal' ),
			),
			array(
				'title' => __( 'Delivered', 'safestore-minimal' ),
				'text'  => __( 'Inspect the parcel before paying the courier. Issues? WhatsApp us within 7 days.', 'safestore-minimal' ),
			),
		)
	);
}

/**
 * Create Track Order page if missing (footer link: /track-order/).
 */
function safestore_minimal_seed_track_order_page() {
	if ( get_option( 'safestore_track_order_page_v1' ) ) {
		return;
	}

	$slug = 'track-order';
	if ( ! get_page_by_path( $slug ) ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => __( 'Track Your Order', 'safestore-minimal' ),
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'page-track-order.php' );
		}
	} else {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			update_post_meta( $page->ID, '_wp_page_template', 'page-track-order.php' );
		}
	}

	update_option( 'safestore_track_order_page_v1', 1, false );
}
add_action( 'after_switch_theme', 'safestore_minimal_seed_track_order_page' );
add_action(
	'init',
	static function () {
		if ( ! get_option( 'safestore_track_order_page_v1' ) ) {
			safestore_minimal_seed_track_order_page();
		}
	},
	20
);

function safestore_minimal_is_track_order_page() {
	return is_page_template( 'page-track-order.php' );
}

add_action(
	'wp_head',
	static function () {
		if ( ! safestore_minimal_is_track_order_page() ) {
			return;
		}
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$desc = __( 'Track your SafeStoreBD order in Bangladesh. Enter order number and email, or WhatsApp us for courier updates.', 'safestore-minimal' );
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( get_permalink() ) );
	},
	1
);

add_filter(
	'pre_get_document_title',
	static function ( $title ) {
		if ( ! safestore_minimal_is_track_order_page() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return $title;
		}
		return __( 'Track Your Order — PPE Delivery Bangladesh | SafeStoreBD', 'safestore-minimal' );
	}
);

/**
 * Privacy policy sections for page-privacy-policy.php (filterable).
 *
 * @return array<int, array{id: string, title: string, paragraphs: string[], list?: string[]}>
 */
function safestore_minimal_get_privacy_sections() {
	$contact_url = home_url( '/contact/' );

	return apply_filters(
		'safestore_minimal_privacy_sections',
		array(
			array(
				'id'         => 'who',
				'title'      => __( 'Who we are', 'safestore-minimal' ),
				'paragraphs' => array(
					__( 'SafeStoreBD imports industrial safety products and PPE from suppliers in <strong>China</strong>, stocks them locally, and sells to customers across <strong>Bangladesh</strong> through this website and our Pallabi, Dhaka office. By using the site or placing an order, you agree to this policy.', 'safestore-minimal' ),
				),
			),
			array(
				'id'         => 'data',
				'title'      => __( 'What we collect', 'safestore-minimal' ),
				'list'       => array(
					__( 'Name, phone, email, and delivery address (for courier and COD).', 'safestore-minimal' ),
					__( 'Order details and messages (WhatsApp, phone, email).', 'safestore-minimal' ),
					__( 'Payment method and reference (bKash, Nagad, Rocket, Upay, COD) — not wallet PINs.', 'safestore-minimal' ),
					__( 'Basic cookies and site logs needed to run checkout securely.', 'safestore-minimal' ),
				),
			),
			array(
				'id'         => 'use',
				'title'      => __( 'How we use & share it', 'safestore-minimal' ),
				'paragraphs' => array(
					__( 'We use your data to process orders, arrange delivery, confirm payments, handle returns, and reply to support. We <strong>do not sell</strong> personal information.', 'safestore-minimal' ),
				),
				'list'       => array(
					__( 'Shared with couriers (name, phone, address) and payment providers you choose.', 'safestore-minimal' ),
					__( 'Kept only as long as needed for orders, returns, and legal records.', 'safestore-minimal' ),
					__( 'Shared with authorities when required under Bangladesh law.', 'safestore-minimal' ),
				),
			),
			array(
				'id'         => 'rights',
				'title'      => __( 'Your rights', 'safestore-minimal' ),
				'paragraphs' => array(
					sprintf(
						/* translators: 1: email links, 2: contact URL */
						__( 'Ask us to access or correct your data at %1$s or our <a href="%2$s">contact page</a>. Your rights under Bangladesh consumer protection law still apply. We may update this policy — see the date above.', 'safestore-minimal' ),
						safestore_contact_email_links(),
						esc_url( $contact_url )
					),
				),
			),
		)
	);
}

/**
 * Create Privacy Policy page if missing (footer link: /privacy-policy/).
 */
function safestore_minimal_seed_privacy_policy_page() {
	if ( get_option( 'safestore_privacy_policy_page_v1' ) ) {
		return;
	}

	$slug = 'privacy-policy';
	if ( ! get_page_by_path( $slug ) ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => __( 'Privacy Policy', 'safestore-minimal' ),
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'page-privacy-policy.php' );
		}
	} else {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			update_post_meta( $page->ID, '_wp_page_template', 'page-privacy-policy.php' );
		}
	}

	update_option( 'safestore_privacy_policy_page_v1', 1, false );
}
add_action( 'after_switch_theme', 'safestore_minimal_seed_privacy_policy_page' );
add_action(
	'init',
	static function () {
		if ( ! get_option( 'safestore_privacy_policy_page_v1' ) ) {
			safestore_minimal_seed_privacy_policy_page();
		}
	},
	20
);

function safestore_minimal_is_privacy_policy_page() {
	return is_page_template( 'page-privacy-policy.php' );
}

add_action(
	'wp_head',
	static function () {
		if ( ! safestore_minimal_is_privacy_policy_page() ) {
			return;
		}
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$desc = __( 'SafeStoreBD privacy policy — China-sourced PPE sold in Bangladesh. How we use your data for orders, delivery, and support.', 'safestore-minimal' );
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( get_permalink() ) );
	},
	1
);

add_filter(
	'pre_get_document_title',
	static function ( $title ) {
		if ( ! safestore_minimal_is_privacy_policy_page() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return $title;
		}
		return __( 'Privacy Policy — SafeStoreBD Industrial PPE Bangladesh', 'safestore-minimal' );
	}
);

/**
 * Terms of service sections for page-terms-of-service.php (filterable).
 *
 * @return array<int, array{id: string, title: string, paragraphs?: string[], list?: string[]}>
 */
function safestore_minimal_get_terms_sections() {
	$returns_url  = home_url( '/return-refund-policy/' );
	$shipping_url = home_url( '/shipping-delivery/' );
	$privacy_url  = home_url( '/privacy-policy/' );
	$contact_url  = home_url( '/contact/' );

	return apply_filters(
		'safestore_minimal_terms_sections',
		array(
			array(
				'id'         => 'agreement',
				'title'      => __( 'Agreement', 'safestore-minimal' ),
				'paragraphs' => array(
					sprintf(
						/* translators: %s: privacy policy URL */
						__( 'These terms apply when you use safestorebd.com or buy from SafeStoreBD. We import industrial safety products and PPE from <strong>China</strong>, stock them in Bangladesh, and sell nationwide. By ordering or using the site, you agree to these terms and our <a href="%s">Privacy Policy</a>.', 'safestore-minimal' ),
						esc_url( $privacy_url )
					),
				),
			),
			array(
				'id'         => 'orders',
				'title'      => __( 'Orders & payment', 'safestore-minimal' ),
				'list'       => array(
					__( 'Prices are in <strong>BDT (৳)</strong> unless stated otherwise. We may correct listing errors before accepting an order.', 'safestore-minimal' ),
					__( 'An order is confirmed when we accept it and (if required) verify payment or COD details.', 'safestore-minimal' ),
					__( 'Payment options include bKash, Nagad, Rocket, Upay, bank transfer, and COD where available.', 'safestore-minimal' ),
					__( 'You must provide a correct phone number and delivery address — couriers in Bangladesh often call before delivery.', 'safestore-minimal' ),
					__( 'We may cancel or refuse orders suspected of fraud or abuse.', 'safestore-minimal' ),
				),
			),
			array(
				'id'         => 'products',
				'title'      => __( 'Products & delivery', 'safestore-minimal' ),
				'paragraphs' => array(
					sprintf(
						/* translators: 1: shipping URL, 2: returns URL */
						__( 'Products are described on our website as supplied by import partners. We do not issue formal product certifications ourselves — ask us if you need details before buying. Delivery times and fees are explained on our <a href="%1$s">shipping page</a>. Returns: <a href="%2$s">return policy</a> (7 days, unused, contact us first).', 'safestore-minimal' ),
						esc_url( $shipping_url ),
						esc_url( $returns_url )
					),
				),
				'list'       => array(
					__( 'You are responsible for choosing suitable PPE for your workplace and using it correctly.', 'safestore-minimal' ),
					__( 'For COD, inspect the parcel before paying the courier; report damage or wrong items promptly.', 'safestore-minimal' ),
				),
			),
			array(
				'id'         => 'general',
				'title'      => __( 'General', 'safestore-minimal' ),
				'paragraphs' => array(
					__( 'To the extent permitted by law, SafeStoreBD is not liable for indirect loss or delays caused by couriers, payment networks, or events outside our control. Our total liability for a claim is limited to the amount you paid for the affected order.', 'safestore-minimal' ),
					sprintf(
						/* translators: %s: contact page URL */
						__( 'These terms are governed by the laws of Bangladesh. Disputes should first be raised with us at contact@safestorebd.com or bdsafestore@gmail.com, or via our <a href="%s">contact page</a>. We may update these terms — continued use of the site means you accept the current version.', 'safestore-minimal' ),
						esc_url( $contact_url )
					),
				),
			),
		)
	);
}

/**
 * Create Terms of Service page if missing (footer link: /terms-of-service/).
 */
function safestore_minimal_seed_terms_page() {
	if ( get_option( 'safestore_terms_page_v1' ) ) {
		return;
	}

	$slug = 'terms-of-service';
	if ( ! get_page_by_path( $slug ) ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => __( 'Terms of Service', 'safestore-minimal' ),
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'page-terms-of-service.php' );
		}
	} else {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			update_post_meta( $page->ID, '_wp_page_template', 'page-terms-of-service.php' );
		}
	}

	update_option( 'safestore_terms_page_v1', 1, false );
}
add_action( 'after_switch_theme', 'safestore_minimal_seed_terms_page' );
add_action(
	'init',
	static function () {
		if ( ! get_option( 'safestore_terms_page_v1' ) ) {
			safestore_minimal_seed_terms_page();
		}
	},
	20
);

function safestore_minimal_is_terms_page() {
	return is_page_template( 'page-terms-of-service.php' );
}

add_action(
	'wp_head',
	static function () {
		if ( ! safestore_minimal_is_terms_page() ) {
			return;
		}
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$desc = __( 'SafeStoreBD terms of service — buying China-imported industrial PPE in Bangladesh. Orders, payment, delivery, and returns.', 'safestore-minimal' );
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( get_permalink() ) );
	},
	1
);

add_filter(
	'pre_get_document_title',
	static function ( $title ) {
		if ( ! safestore_minimal_is_terms_page() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return $title;
		}
		return __( 'Terms of Service — SafeStoreBD PPE Bangladesh', 'safestore-minimal' );
	}
);

/**
 * Legal document links for page-legal.php.
 *
 * @return array<int, array{label: string, url: string, desc?: string}>
 */
function safestore_minimal_get_legal_documents() {
	return apply_filters(
		'safestore_minimal_legal_documents',
		array(
			array(
				'label' => __( 'Privacy Policy', 'safestore-minimal' ),
				'url'   => home_url( '/privacy-policy/' ),
				'desc'  => __( 'How we use your data', 'safestore-minimal' ),
			),
			array(
				'label' => __( 'Terms of Service', 'safestore-minimal' ),
				'url'   => home_url( '/terms-of-service/' ),
				'desc'  => __( 'Ordering rules & liability', 'safestore-minimal' ),
			),
			array(
				'label' => __( 'Return & Refund Policy', 'safestore-minimal' ),
				'url'   => home_url( '/return-refund-policy/' ),
				'desc'  => __( '7-day returns', 'safestore-minimal' ),
			),
			array(
				'label' => __( 'Shipping & Delivery', 'safestore-minimal' ),
				'url'   => home_url( '/shipping-delivery/' ),
				'desc'  => __( 'Rates & timing', 'safestore-minimal' ),
			),
		)
	);
}

/**
 * Legal page sections (filterable).
 *
 * @return array<int, array{id: string, title: string, paragraphs?: string[], list?: string[]}>
 */
function safestore_minimal_get_legal_sections() {
	return apply_filters(
		'safestore_minimal_legal_sections',
		array(
			array(
				'id'         => 'business',
				'title'      => __( 'About our business', 'safestore-minimal' ),
				'paragraphs' => array(
					__( 'SafeStoreBD operates as an e-commerce seller of industrial safety products and PPE in <strong>Bangladesh</strong>. Goods are <strong>imported from suppliers in China</strong>, checked and stocked at our Pallabi, Dhaka office, then sold to customers and businesses nationwide.', 'safestore-minimal' ),
				),
				'list'       => array(
					__( 'Registered contact: contact@safestorebd.com | bdsafestore@gmail.com · +880 1811-892291', 'safestore-minimal' ),
					sprintf(
						/* translators: %s: office address */
						__( 'Office: %s', 'safestore-minimal' ),
						safestore_minimal_get_pickup_address()
					),
				),
			),
			array(
				'id'         => 'notices',
				'title'      => __( 'Important notices', 'safestore-minimal' ),
				'list'       => array(
					__( 'Product photos and descriptions are for reference — ask us before buying if you need specific standards or certifications.', 'safestore-minimal' ),
					__( 'We do not provide workplace safety consulting; buyers must ensure PPE is suitable for their site.', 'safestore-minimal' ),
					__( 'Your rights under Bangladesh consumer protection law apply to purchases from us.', 'safestore-minimal' ),
					__( 'This page is for information only and does not replace professional legal advice.', 'safestore-minimal' ),
				),
			),
		)
	);
}

/**
 * HTML sitemap groups for page-sitemap.php (filterable).
 *
 * @return array<int, array{title: string, links: array<int, array{label: string, url: string}>}>
 */
function safestore_minimal_get_sitemap_groups() {
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

	$shop_links = array(
		array(
			'label' => __( 'Home', 'safestore-minimal' ),
			'url'   => home_url( '/' ),
		),
		array(
			'label' => __( 'Shop all PPE', 'safestore-minimal' ),
			'url'   => $shop_url,
		),
	);

	if ( taxonomy_exists( 'product_cat' ) ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => 8,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_link = get_term_link( $term );
				if ( is_wp_error( $term_link ) ) {
					continue;
				}
				$shop_links[] = array(
					'label' => $term->name,
					'url'   => $term_link,
				);
			}
		}
	}

	$groups = array(
		array(
			'title' => __( 'Shop', 'safestore-minimal' ),
			'links' => $shop_links,
		),
		array(
			'title' => __( 'Company', 'safestore-minimal' ),
			'links' => array(
				array( 'label' => __( 'About us', 'safestore-minimal' ), 'url' => home_url( '/about/' ) ),
				array( 'label' => __( 'Careers', 'safestore-minimal' ), 'url' => home_url( '/careers/' ) ),
				array( 'label' => __( 'Contact', 'safestore-minimal' ), 'url' => home_url( '/contact/' ) ),
				array( 'label' => __( 'Bulk orders', 'safestore-minimal' ), 'url' => home_url( '/bulk-orders/' ) ),
			),
		),
		array(
			'title' => __( 'Customer support', 'safestore-minimal' ),
			'links' => array(
				array( 'label' => __( 'FAQ', 'safestore-minimal' ), 'url' => home_url( '/faqs/' ) ),
				array( 'label' => __( 'Track order', 'safestore-minimal' ), 'url' => home_url( '/track-order/' ) ),
				array( 'label' => __( 'Shipping & delivery', 'safestore-minimal' ), 'url' => home_url( '/shipping-delivery/' ) ),
				array( 'label' => __( 'Return & refund', 'safestore-minimal' ), 'url' => home_url( '/return-refund-policy/' ) ),
			),
		),
		array(
			'title' => __( 'Legal', 'safestore-minimal' ),
			'links' => array(
				array( 'label' => __( 'Legal information', 'safestore-minimal' ), 'url' => home_url( '/legal/' ) ),
				array( 'label' => __( 'Privacy policy', 'safestore-minimal' ), 'url' => home_url( '/privacy-policy/' ) ),
				array( 'label' => __( 'Terms of service', 'safestore-minimal' ), 'url' => home_url( '/terms-of-service/' ) ),
				array( 'label' => __( 'Sitemap', 'safestore-minimal' ), 'url' => home_url( '/sitemap/' ) ),
			),
		),
	);

	return apply_filters( 'safestore_minimal_sitemap_groups', $groups );
}

/**
 * Create Legal page if missing (footer link: /legal/).
 */
function safestore_minimal_seed_legal_page() {
	if ( get_option( 'safestore_legal_page_v1' ) ) {
		return;
	}

	$slug = 'legal';
	if ( ! get_page_by_path( $slug ) ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => __( 'Legal', 'safestore-minimal' ),
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'page-legal.php' );
		}
	} else {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			update_post_meta( $page->ID, '_wp_page_template', 'page-legal.php' );
		}
	}

	update_option( 'safestore_legal_page_v1', 1, false );
}

/**
 * Create Sitemap page if missing (footer link: /sitemap/).
 */
function safestore_minimal_seed_sitemap_page() {
	if ( get_option( 'safestore_sitemap_page_v1' ) ) {
		return;
	}

	$slug = 'sitemap';
	if ( ! get_page_by_path( $slug ) ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => __( 'Sitemap', 'safestore-minimal' ),
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			),
			true
		);
		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_wp_page_template', 'page-sitemap.php' );
		}
	} else {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post ) {
			update_post_meta( $page->ID, '_wp_page_template', 'page-sitemap.php' );
		}
	}

	update_option( 'safestore_sitemap_page_v1', 1, false );
}

add_action( 'after_switch_theme', 'safestore_minimal_seed_legal_page' );
add_action( 'after_switch_theme', 'safestore_minimal_seed_sitemap_page' );
add_action(
	'init',
	static function () {
		if ( ! get_option( 'safestore_legal_page_v1' ) ) {
			safestore_minimal_seed_legal_page();
		}
		if ( ! get_option( 'safestore_sitemap_page_v1' ) ) {
			safestore_minimal_seed_sitemap_page();
		}
	},
	20
);

function safestore_minimal_is_legal_page() {
	return is_page_template( 'page-legal.php' );
}

function safestore_minimal_is_sitemap_page() {
	return is_page_template( 'page-sitemap.php' );
}

add_action(
	'wp_head',
	static function () {
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		if ( safestore_minimal_is_legal_page() ) {
			$desc = __( 'Legal information for SafeStoreBD — policies, disclaimers, and China-imported PPE sold in Bangladesh.', 'safestore-minimal' );
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
			printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( get_permalink() ) );
		} elseif ( safestore_minimal_is_sitemap_page() ) {
			$desc = __( 'SafeStoreBD sitemap — shop, support, and legal pages for industrial safety products in Bangladesh.', 'safestore-minimal' );
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
			printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( get_permalink() ) );
		}
	},
	1
);

add_filter(
	'pre_get_document_title',
	static function ( $title ) {
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return $title;
		}
		if ( safestore_minimal_is_legal_page() ) {
			return __( 'Legal — SafeStoreBD PPE Bangladesh', 'safestore-minimal' );
		}
		if ( safestore_minimal_is_sitemap_page() ) {
			return __( 'Sitemap — SafeStoreBD Industrial PPE', 'safestore-minimal' );
		}
		return $title;
	}
);

// ACR Checkout Page customize Start
add_filter( 'woocommerce_checkout_fields', 'safestorebd_checkout_fields' );

function safestorebd_checkout_fields( $fields ) {

    // Billing fields
    $fields['billing']['billing_first_name']['label'] = 'Full Name';
    $fields['billing']['billing_first_name']['priority'] = 10;
	$fields['billing']['billing_first_name']['class'] = array('form-row-wide');

    $fields['billing']['billing_phone']['priority'] = 20;

	$fields['billing']['billing_email']['label'] = 'Email';
    $fields['billing']['billing_email']['required'] = false;
    $fields['billing']['billing_email']['priority'] = 30;

	$fields['billing']['billing_address_1']['label'] = 'Full Address';
    $fields['billing']['billing_address_1']['priority'] = 40;

    $fields['billing']['billing_state']['label'] = 'District';
    $fields['billing']['billing_state']['priority'] = 50;

    // Remove unwanted billing fields
    unset( $fields['billing']['billing_last_name'] );
    unset( $fields['billing']['billing_company'] );
    unset( $fields['billing']['billing_country'] );
    unset( $fields['billing']['billing_city'] );
    unset( $fields['billing']['billing_postcode'] );
    unset( $fields['billing']['billing_address_2'] );
	unset( $fields['order']['order_comments'] );

    // Remove all shipping fields
    unset( $fields['shipping'] );

    return $fields;
}
// ACR Checkout Page customize End

// ACR Dynamically update cart count and price via WooCommerce AJAX
add_filter( 'woocommerce_add_to_cart_fragments', 'sft_custom_theme_cart_fragments' );

function sft_custom_theme_cart_fragments( $fragments ) {
    //  Update the Cart Item Count Badge
    ob_start();
    ?>
    <span class="sft-header-cart-badge"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
    <?php
    $fragments['span.sft-header-cart-badge'] = ob_get_clean();

    //  Update the Cart Total Price Label
    ob_start();
    ?>
    <span class="sft-header-action-label sft-header-cart-total"><?php echo WC()->cart->get_cart_total(); ?></span>
    <?php
    // Target the cart-specific class. The bare .sft-header-action-label
    // selector also matched the Wishlist label and overwrote it.
    $fragments['span.sft-header-cart-total'] = ob_get_clean();

    return $fragments;
}
// ACR Dynamically update cart count and price via WooCommerce AJAX End

/**
 * Prevent duplicate category description on shop archives.
 * The theme's woocommerce.php header already outputs term_description(),
 * so remove WooCommerce's default archive description output.
 */
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );

<?php

if (!defined('ABSPATH')) {
    exit;
}

function safestore_cart_toast_enabled() {
    $enabled = class_exists('WooCommerce') && !is_admin();

    return (bool) apply_filters('safestore_cart_toast_enabled', $enabled);
}

function safestore_cart_fragments_needed() {
    $needed = ! empty( $_COOKIE['woocommerce_items_in_cart'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        || is_user_logged_in()
        || ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) );

    return (bool) apply_filters( 'safestore_cart_fragments_needed', $needed );
}

function safestore_cart_toast_enqueue() {
    if (!safestore_cart_toast_enabled()) {
        return;
    }

    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();

    $css_path = $theme_dir . '/css/cart-toast.css';
    if (file_exists($css_path)) {
        wp_enqueue_style(
            'safestore-cart-toast',
            $theme_uri . '/css/cart-toast.css',
            array('safestore-minimal-style'),
            (string) filemtime($css_path)
        );
    }

    // WooCommerce's fragment infrastructure keeps the header cart in sync
    // across pages — but cart-fragments.js fires an uncached
    // ?wc-ajax=get_refreshed_fragments request on EVERY page view, even for a
    // visitor with an empty cart. Only load it once there is a cart to sync
    // (WooCommerce sets woocommerce_items_in_cart on the first add; LiteSpeed
    // serves those visitors their own cache copy via its woo_cart vary), for
    // logged-in users, and on cart / checkout / account. On the empty-cart
    // pages the AJAX add-to-cart response itself carries the fragments
    // (add-to-cart-toast.js applies them), so the count still updates at once.
    if (safestore_cart_fragments_needed() && wp_script_is('wc-cart-fragments', 'registered')) {
        wp_enqueue_script('wc-cart-fragments');
    }

    $deps = array('jquery');
    foreach (array('wc-add-to-cart', 'wc-cart-fragments') as $handle) {
        if (wp_script_is($handle, 'enqueued') || ('wc-add-to-cart' === $handle && wp_script_is($handle, 'registered'))) {
            $deps[] = $handle;
        }
    }

    $js_path = $theme_dir . '/js/add-to-cart-toast.js';
    if (!file_exists($js_path)) {
        return;
    }
    wp_enqueue_script(
        'safestore-cart-toast',
        $theme_uri . '/js/add-to-cart-toast.js',
        $deps,
        (string) filemtime($js_path),
        function_exists('safestore_perf_script_args') ? safestore_perf_script_args(true) : true
    );

    // One-time product name for standard (non-AJAX) page-reload adds. AJAX adds
    // are confirmed entirely client-side, so they never set this flag.
    $just_added = '';
    if (function_exists('WC') && WC() && WC()->session && !is_cart() && !is_checkout()) {
        $stored = WC()->session->get('safestore_cart_toast_name');
        if (!empty($stored)) {
            $just_added = (string) $stored;
            WC()->session->set('safestore_cart_toast_name', null);
        }
    }

    wp_localize_script(
        'safestore-cart-toast',
        'sftCartToast',
        array(
            'cartUrl'          => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/'),
            'ajaxUrl'          => class_exists('WC_AJAX') ? WC_AJAX::get_endpoint('add_to_cart') : '',
            'redirectAfterAdd' => ('yes' === get_option('woocommerce_cart_redirect_after_add')),
            'justAdded'        => $just_added,
            'i18n'             => array(
                'defaultName'        => __('Item', 'safestore-minimal'),
                'addedTitle'         => __('Added to cart', 'safestore-minimal'),
                'addToCart'          => __('Add to cart', 'safestore-minimal'),
                'selectSize'         => __('Select a size', 'safestore-minimal'),
                'selectOptions'      => __('Select options', 'safestore-minimal'),
                'selectOptionsFirst' => __('Please choose the product options first.', 'safestore-minimal'),
                'unavailable'        => __('Unavailable', 'safestore-minimal'),
                'errorTitle'         => __('Not added to cart', 'safestore-minimal'),
                'errorGeneric'       => __('This item could not be added. Please check the size and quantity, then try again.', 'safestore-minimal'),
                'errorNetwork'       => __('Network problem: the item was not added. Please try again.', 'safestore-minimal'),
                'errorTimeout'       => __('The connection is slow and we could not confirm the item was added. Check your cart or try again.', 'safestore-minimal'),
            ),
        )
    );
}
add_action('wp_enqueue_scripts', 'safestore_cart_toast_enqueue', 20);

function safestore_cart_toast_markup() {
    if (!safestore_cart_toast_enabled()) {
        return;
    }
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
    ?>
    <div id="sft-cart-toast" class="sft-cart-toast" role="status" aria-live="polite" aria-atomic="true" hidden>
        <button type="button" class="sft-cart-toast__close" aria-label="<?php esc_attr_e('Dismiss notification', 'safestore-minimal'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
        <div class="sft-cart-toast__head">
            <span class="sft-cart-toast__icon" aria-hidden="true">
                <svg class="sft-cart-toast__icon-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M20 6 9 17l-5-5"/></svg>
                <svg class="sft-cart-toast__icon-err" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M12 8v5M12 16.5v.01"/><circle cx="12" cy="12" r="9"/></svg>
            </span>
            <div class="sft-cart-toast__copy">
                <p class="sft-cart-toast__title"><?php esc_html_e('Added to cart', 'safestore-minimal'); ?></p>
                <p class="sft-cart-toast__name"></p>
            </div>
        </div>
        <div class="sft-cart-toast__actions">
            <button type="button" class="sft-cart-toast__btn sft-cart-toast__btn--ghost" data-sft-continue><?php esc_html_e('Continue shopping', 'safestore-minimal'); ?></button>
            <a class="sft-cart-toast__btn sft-cart-toast__btn--primary" href="<?php echo esc_url($cart_url); ?>" data-sft-view-cart><?php esc_html_e('View cart', 'safestore-minimal'); ?></a>
            <button type="button" class="sft-cart-toast__btn sft-cart-toast__btn--primary" data-sft-retry hidden><?php esc_html_e('Try again', 'safestore-minimal'); ?></button>
        </div>
        <span class="sft-cart-toast__progress" aria-hidden="true"></span>
    </div>
    <?php
}
add_action('wp_footer', 'safestore_cart_toast_markup', 30);

function safestore_cart_toast_fragments($fragments) {
    if (!function_exists('WC') || !WC() || !WC()->cart) {
        return $fragments;
    }

    ob_start();
    ?><span class="sft-header-cart-badge"><?php echo esc_html(WC()->cart->get_cart_contents_count()); ?></span><?php
    $fragments['span.sft-header-cart-badge'] = ob_get_clean();

    ob_start();
    ?><span class="sft-header-action-label sft-header-cart-total"><?php echo wp_kses_post(WC()->cart->get_cart_total()); ?></span><?php
    $fragments['.sft-header-cart-total'] = ob_get_clean();

    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'safestore_cart_toast_fragments');

function safestore_cart_toast_remember($cart_item_key, $product_id, $quantity, $variation_id) {
    if (wp_doing_ajax()) {
        return;
    }
    if (!function_exists('WC') || !WC() || !WC()->session) {
        return;
    }
    $product = wc_get_product($variation_id ? $variation_id : $product_id);
    if (!$product) {
        return;
    }
    WC()->session->set('safestore_cart_toast_name', $product->get_name());
}
add_action('woocommerce_add_to_cart', 'safestore_cart_toast_remember', 20, 4);

<?php
/**
 * SafeStoreBD — Add to Cart success toast.
 *
 * A branded, accessible "Added to cart" toast shown for:
 *   - AJAX archive adds (WooCommerce fires `added_to_cart`),
 *   - single-product adds (the form is intercepted and sent over AJAX so there
 *     is no page reload), and
 *   - standard non-AJAX adds (a one-time session flag shows the toast on the
 *     next page load — the graceful fallback when JS/AJAX is unavailable).
 *
 * The header cart count and total stay in sync via WooCommerce cart fragments,
 * so nothing here requires a page refresh.
 *
 * Self-contained: no third-party libraries. Everything is filterable/gated via
 * `safestore_cart_toast_enabled` for easy maintenance.
 *
 * @package safestore-minimal
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Whether the cart toast should load on this request.
 *
 * @return bool
 */
function safestore_cart_toast_enabled() {
    $enabled = class_exists('WooCommerce') && !is_admin();

    /**
     * Toggle the Add to Cart toast.
     *
     * @param bool $enabled Whether the toast loads on this request.
     */
    return (bool) apply_filters('safestore_cart_toast_enabled', $enabled);
}

/**
 * Enqueue the toast assets and pass the runtime data the script needs.
 */
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

    // Ensure WooCommerce's fragment infrastructure is present so the header cart
    // updates (and caches across pages) without a refresh.
    if (wp_script_is('wc-cart-fragments', 'registered')) {
        wp_enqueue_script('wc-cart-fragments');
    }

    $deps = array('jquery');
    foreach (array('wc-add-to-cart', 'wc-cart-fragments') as $handle) {
        if (wp_script_is($handle, 'registered')) {
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
        true
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
                'defaultName' => __('Item', 'safestore-minimal'),
            ),
        )
    );
}
add_action('wp_enqueue_scripts', 'safestore_cart_toast_enqueue', 20);

/**
 * Render the toast container once in the footer (hidden until an add happens).
 */
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M20 6 9 17l-5-5"/></svg>
            </span>
            <div class="sft-cart-toast__copy">
                <p class="sft-cart-toast__title"><?php esc_html_e('Added to cart', 'safestore-minimal'); ?></p>
                <p class="sft-cart-toast__name"></p>
            </div>
        </div>
        <div class="sft-cart-toast__actions">
            <button type="button" class="sft-cart-toast__btn sft-cart-toast__btn--ghost" data-sft-continue><?php esc_html_e('Continue shopping', 'safestore-minimal'); ?></button>
            <a class="sft-cart-toast__btn sft-cart-toast__btn--primary" href="<?php echo esc_url($cart_url); ?>"><?php esc_html_e('View cart', 'safestore-minimal'); ?></a>
        </div>
        <span class="sft-cart-toast__progress" aria-hidden="true"></span>
    </div>
    <?php
}
add_action('wp_footer', 'safestore_cart_toast_markup', 30);

/**
 * Keep the header cart count + total in sync via WooCommerce cart fragments,
 * so every AJAX add updates the header without a page refresh.
 *
 * @param array<string, string> $fragments
 * @return array<string, string>
 */
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

/**
 * Remember the product name for standard (non-AJAX) adds so the toast can be
 * shown on the next page load. AJAX adds are handled entirely client-side.
 *
 * @param string $cart_item_key
 * @param int    $product_id
 * @param int    $quantity
 * @param int    $variation_id
 */
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

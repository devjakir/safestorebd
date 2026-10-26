<?php

if (!defined('ABSPATH')) {
    exit;
}

function safestore_mnav_enabled() {
    if (is_admin()) {
        return false;
    }

    return (bool) apply_filters('safestore_mnav_enabled', true);
}

function safestore_mnav_current_path() {
    $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
    $path = wp_parse_url((string) $uri, PHP_URL_PATH);
    $path = trim((string) $path, '/');

    return '' === $path ? '/' : '/' . $path . '/';
}

function safestore_mnav_path_matches($url) {
    if ('' === (string) $url) {
        return false;
    }
    $path = wp_parse_url((string) $url, PHP_URL_PATH);
    $path = trim((string) $path, '/');
    $path = '' === $path ? '/' : '/' . $path . '/';

    return $path === safestore_mnav_current_path();
}

function safestore_mnav_account_url() {
    if (function_exists('wc_get_page_permalink')) {
        $url = wc_get_page_permalink('myaccount');
        if ($url) {
            return $url;
        }
    }
    return wp_login_url();
}

function safestore_mnav_cart_url() {
    if (function_exists('wc_get_cart_url')) {
        return wc_get_cart_url();
    }
    return home_url('/cart/');
}

function safestore_mnav_cart_count() {
    if (!function_exists('WC')) {
        return 0;
    }
    $wc = WC();
    if (!$wc || !isset($wc->cart) || !$wc->cart) {
        return 0;
    }
    return (int) $wc->cart->get_cart_contents_count();
}

function safestore_mnav_icon($name) {
    $paths = array(
        'home'       => '<path d="M3 10.6 12 3.2l9 7.4"/><path d="M5.6 9.6V20a1 1 0 0 0 1 1H10v-5.6h4V21h3.4a1 1 0 0 0 1-1V9.6"/>',
        'categories' => '<rect x="3.4" y="3.4" width="7.2" height="7.2" rx="1.6"/><rect x="13.4" y="3.4" width="7.2" height="7.2" rx="1.6"/><rect x="3.4" y="13.4" width="7.2" height="7.2" rx="1.6"/><rect x="13.4" y="13.4" width="7.2" height="7.2" rx="1.6"/>',
        'cart'       => '<circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/><path d="M3 4h2l2.4 11.4a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.5L21 8H6"/>',
        'track'      => '<path d="M2.8 6.6h11.4v9.6H2.8z"/><path d="M14.2 10h3.4l3.6 3.3v2.9h-7z"/><circle cx="7" cy="18.4" r="1.7"/><circle cx="17.4" cy="18.4" r="1.7"/>',
        'account'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'search'     => '<circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
    );

    if (!isset($paths[$name])) {
        return '';
    }

    return '<svg class="sft-bottomnav__svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
}

function safestore_mnav_items() {
    $cart_url    = safestore_mnav_cart_url();
    $account_url = safestore_mnav_account_url();
    $track_url   = home_url('/track-order/');
    $home_url    = home_url('/');

    $is_cart = function_exists('is_cart') ? is_cart() : safestore_mnav_path_matches($cart_url);
    $is_acct = function_exists('is_account_page') ? is_account_page() : safestore_mnav_path_matches($account_url);

    $items = array(
        array(
            'key'    => 'home',
            'type'   => 'link',
            'label'  => __('Home', 'safestore-minimal'),
            'url'    => $home_url,
            'icon'   => 'home',
            'active' => (is_front_page() || is_home()),
        ),
        array(
            'key'    => 'categories',
            'type'   => 'drawer',
            'label'  => __('Categories', 'safestore-minimal'),
            'url'    => '',
            'icon'   => 'categories',
            'active' => false,
        ),
        array(
            'key'    => 'cart',
            'type'   => 'link',
            'label'  => __('Cart', 'safestore-minimal'),
            'url'    => $cart_url,
            'icon'   => 'cart',
            'active' => (bool) $is_cart,
            'badge'  => true,
        ),
        array(
            'key'    => 'track',
            'type'   => 'link',
            'label'  => __('Track', 'safestore-minimal'),
            'url'    => $track_url,
            'icon'   => 'track',
            'active' => safestore_mnav_path_matches($track_url),
        ),
        array(
            'key'    => 'account',
            'type'   => 'link',
            'label'  => __('Account', 'safestore-minimal'),
            'url'    => $account_url,
            'icon'   => 'account',
            'active' => (bool) $is_acct,
        ),
    );

    return apply_filters('safestore_mnav_items', $items);
}

function safestore_mnav_badge_markup() {
    $count = safestore_mnav_cart_count();

    return sprintf(
        '<span class="sft-bottomnav__badge%1$s" aria-hidden="true">%2$s</span>',
        $count > 0 ? '' : ' is-empty',
        esc_html($count > 99 ? '99+' : (string) $count)
    );
}

function safestore_mnav_render() {
    if (!safestore_mnav_enabled()) {
        return;
    }

    $items = safestore_mnav_items();
    if (empty($items)) {
        return;
    }
    ?>
    <nav class="sft-bottomnav" aria-label="<?php esc_attr_e('Primary', 'safestore-minimal'); ?>">
        <?php foreach ($items as $item) :
            $classes = 'sft-bottomnav__item sft-bottomnav__item--' . sanitize_html_class($item['key']);
            if (!empty($item['active'])) {
                $classes .= ' is-active';
            }
            $icon = safestore_mnav_icon($item['icon']);
            ?>
            <?php if ('drawer' === $item['type']) : ?>
                <button type="button"
                    class="<?php echo esc_attr($classes); ?>"
                    data-sft-open-categories
                    aria-controls="offcanvas-menu"
                    aria-expanded="false">
                    <span class="sft-bottomnav__icon"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                    <span class="sft-bottomnav__label"><?php echo esc_html($item['label']); ?></span>
                </button>
            <?php else : ?>
                <a class="<?php echo esc_attr($classes); ?>"
                    href="<?php echo esc_url($item['url']); ?>"
                    <?php echo !empty($item['active']) ? 'aria-current="page"' : ''; ?>>
                    <span class="sft-bottomnav__icon">
                        <?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                        <?php if (!empty($item['badge'])) : ?>
                            <?php echo safestore_mnav_badge_markup(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                        <?php endif; ?>
                    </span>
                    <span class="sft-bottomnav__label"><?php echo esc_html($item['label']); ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php
}
// Priority 5 so the bar is in the DOM before the WhatsApp widget (20) — the
// widget lifts itself clear of the bar via the --sft-wa-offset variable.
add_action('wp_footer', 'safestore_mnav_render', 5);

function safestore_mnav_cart_fragments($fragments) {
    $fragments['span.sft-bottomnav__badge'] = safestore_mnav_badge_markup();

    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'safestore_mnav_cart_fragments');

function safestore_mnav_quicklinks() {
    if (!safestore_mnav_enabled()) {
        return;
    }

    $links = array(
        array(
            'label'  => __("Today's Deals", 'safestore-minimal'),
            'url'    => home_url('/deals/'),
            'accent' => true,
        ),
        array(
            'label' => __('Bulk Orders', 'safestore-minimal'),
            'url'   => home_url('/bulk-orders/'),
        ),
        array(
            'label' => __('Compare', 'safestore-minimal'),
            'url'   => home_url('/compare/'),
        ),
        array(
            'label' => __('Wishlist', 'safestore-minimal'),
            'url'   => home_url('/wishlist/'),
        ),
        array(
            'label' => __('Shipping', 'safestore-minimal'),
            'url'   => home_url('/shipping-delivery/'),
        ),
        array(
            'label' => __('Help', 'safestore-minimal'),
            'url'   => home_url('/faqs/'),
        ),
    );

    $links = apply_filters('safestore_mnav_quicklinks', $links);

    if (empty($links)) {
        return;
    }
    ?>
    <div class="sft-quicklinks" role="navigation" aria-label="<?php esc_attr_e('Shortcuts', 'safestore-minimal'); ?>">
        <ul class="sft-quicklinks__list">
            <?php foreach ($links as $link) : ?>
                <?php
                $is_current = safestore_mnav_path_matches($link['url']);
                $chip_class = 'sft-quicklinks__chip';
                $chip_class .= !empty($link['accent']) ? ' sft-quicklinks__chip--accent' : '';
                $chip_class .= $is_current ? ' is-active' : '';
                ?>
                <li>
                    <a class="<?php echo esc_attr($chip_class); ?>" href="<?php echo esc_url($link['url']); ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>>
                        <?php if (!empty($link['accent'])) : ?>
                            <span class="sft-quicklinks__dot" aria-hidden="true"></span>
                        <?php endif; ?>
                        <?php echo esc_html($link['label']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

function safestore_mnav_search_toggle() {
    if (!safestore_mnav_enabled()) {
        return;
    }
    ?>
    <button type="button"
        class="sft-header-action sft-header-action--search"
        data-sft-search-toggle
        aria-controls="sft-header-search-panel"
        aria-expanded="false"
        aria-label="<?php esc_attr_e('Search', 'safestore-minimal'); ?>">
        <?php echo safestore_mnav_icon('search'); // phpcs:ignore WordPress.Security.EscapeOutput ?>
    </button>
    <?php
}

function safestore_mnav_search_backdrop() {
    if (!safestore_mnav_enabled()) {
        return;
    }
    echo '<div class="sft-search-backdrop" data-sft-search-backdrop aria-hidden="true"></div>';
}

function safestore_mnav_enqueue() {
    if (!safestore_mnav_enabled()) {
        return;
    }

    $css_path = get_template_directory() . '/css/mobile-nav.css';
    $js_path  = get_template_directory() . '/js/mobile-nav.js';

    if (file_exists($css_path)) {
        wp_enqueue_style(
            'safestore-mobile-nav',
            get_template_directory_uri() . '/css/mobile-nav.css',
            array('safestore-minimal-style'),
            (string) filemtime($css_path)
        );
    }

    if (file_exists($js_path)) {
        $args = array('in_footer' => true);
        if (version_compare(get_bloginfo('version'), '6.3', '>=')) {
            $args['strategy'] = 'defer';
        }
        wp_enqueue_script(
            'safestore-mobile-nav',
            get_template_directory_uri() . '/js/mobile-nav.js',
            array(),
            (string) filemtime($js_path),
            $args
        );
    }
}
add_action('wp_enqueue_scripts', 'safestore_mnav_enqueue', 22);

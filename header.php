<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
wp_body_open();
$cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');

$sft_wc         = function_exists('WC') ? WC() : null;
$sft_wc_cart    = ($sft_wc && isset($sft_wc->cart) && $sft_wc->cart) ? $sft_wc->cart : null;
$sft_cart_count = $sft_wc_cart ? (int) $sft_wc_cart->get_cart_contents_count() : 0;
$sft_cart_total = $sft_wc_cart ? $sft_wc_cart->get_cart_total() : '';
?>

<header class="sft-header" role="banner">
<?php // Set at parse time, not by the deferred mobile-nav.js: on phones that class collapses the 44px search row, and adding it after first paint shifted every page by 0.05 CLS. ?>
<script>document.currentScript.parentNode.classList.add('has-js-search');</script>
    <div class="sft-header-sticky">
    <div class="sft-header-main">
        <button type="button" class="mobile-menu-trigger" aria-label="Open Menu" aria-controls="offcanvas-menu" aria-expanded="false">
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
        </button>
        <a class="sft-header-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: site name */ __( '%s home', 'safestore-minimal' ), get_bloginfo( 'name' ) ) ); ?>">
            <?php $sft_logo = safestore_brand_logo_svg(); ?>
            <img class="sft-header-brand-logo"
                src="<?php echo esc_url( $sft_logo['url'] ); ?>"
                alt="<?php bloginfo('name'); ?>"
                width="<?php echo (int) $sft_logo['width']; ?>"
                height="<?php echo (int) $sft_logo['height']; ?>"
                decoding="async"
                <?php echo ( is_page_template( 'page-home.php' ) || is_front_page() || ( function_exists( 'is_product' ) && is_product() ) ) ? '' : 'fetchpriority="high"'; ?> />
        </a>

        <?php
        $sft_search_action = home_url('/');
        if (function_exists('wc_get_page_permalink')) {
            $sft_shop = wc_get_page_permalink('shop');
            if ($sft_shop) {
                $sft_search_action = $sft_shop;
            }
        }
        $sft_selected_cat = isset($_GET['product_cat']) ? sanitize_title(wp_unslash((string) $_GET['product_cat'])) : '';
        $sft_header_cats  = array();
        if (taxonomy_exists('product_cat')) {
            $sft_terms = get_terms(
                array(
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => true,
                    'parent'     => 0,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                )
            );
            if (!is_wp_error($sft_terms)) {
                $sft_header_cats = $sft_terms;
            }
        }
        $sft_cat_trigger_label = __('All Categories', 'safestore-minimal');
        foreach ($sft_header_cats as $sft_cat) {
            if ($sft_cat->slug === $sft_selected_cat) {
                $sft_cat_trigger_label = $sft_cat->name;
                break;
            }
        }
        ?>
        <div class="sft-header-searchwrap" id="sft-header-search-panel">
        <form class="sft-header-search" role="search" action="<?php echo esc_url($sft_search_action); ?>" method="get">
            <?php if (function_exists('wc_get_page_permalink')) : ?>
                <input type="hidden" name="post_type" value="product">
            <?php endif; ?>
            <label class="screen-reader-text" id="sft-search-cat-label" for="sft-header-product-cat"><?php esc_html_e('Category', 'safestore-minimal'); ?></label>
            <div class="sft-header-search-cat-wrap sft-search-cat" data-sft-search-cat>
                <input type="hidden" name="product_cat" id="sft-header-product-cat" value="<?php echo esc_attr($sft_selected_cat); ?>">
                <button type="button" class="sft-search-cat__trigger" id="sft-search-cat-trigger" aria-labelledby="sft-search-cat-label" aria-expanded="false" aria-haspopup="listbox" aria-controls="sft-search-cat-listbox">
                    <span class="sft-search-cat__value"><?php echo esc_html($sft_cat_trigger_label); ?></span>
                    <svg class="sft-search-cat__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="sft-search-cat__panel" id="sft-search-cat-panel" hidden>
                    <ul class="sft-search-cat__list" id="sft-search-cat-listbox" role="listbox" aria-labelledby="sft-search-cat-label">
                        <li class="sft-search-cat__item" role="none">
                            <button type="button" class="sft-search-cat__option<?php echo $sft_selected_cat === '' ? ' is-selected' : ''; ?>" role="option" data-value="" aria-selected="<?php echo $sft_selected_cat === '' ? 'true' : 'false'; ?>">
                                <span class="sft-search-cat__option-label"><?php esc_html_e('All Categories', 'safestore-minimal'); ?></span>
                            </button>
                        </li>
                        <?php foreach ($sft_header_cats as $sft_cat) : ?>
                            <li class="sft-search-cat__item" role="none">
                                <button type="button" class="sft-search-cat__option<?php echo $sft_selected_cat === $sft_cat->slug ? ' is-selected' : ''; ?>" role="option" data-value="<?php echo esc_attr($sft_cat->slug); ?>" aria-selected="<?php echo $sft_selected_cat === $sft_cat->slug ? 'true' : 'false'; ?>">
                                    <span class="sft-search-cat__option-label"><?php echo esc_html($sft_cat->name); ?></span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <input class="sft-header-search-input" type="search" name="s" placeholder="<?php esc_attr_e('Search safety helmets, gloves, harness…', 'safestore-minimal'); ?>" aria-label="<?php esc_attr_e('Search', 'safestore-minimal'); ?>" value="<?php echo isset($_GET['s']) ? esc_attr(wp_unslash((string) $_GET['s'])) : ''; ?>">
            <button class="sft-header-search-btn" type="submit" aria-label="<?php esc_attr_e('Search', 'safestore-minimal'); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </button>
        </form>
        </div><!-- /.sft-header-searchwrap -->

        <div class="sft-header-actions">
            <?php if ( function_exists( 'safestore_mnav_search_toggle' ) ) { safestore_mnav_search_toggle(); } ?>

            <?php
            if ( function_exists( 'safestore_header_account_markup' ) ) {
                echo safestore_header_account_markup(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
            }
            ?>

            <?php if (function_exists('safestore_wishlist_enabled') && safestore_wishlist_enabled()) : ?>
            <a class="sft-header-action sft-header-action--wishlist" href="<?php echo esc_url(safestore_wishlist_page_url()); ?>" aria-label="<?php esc_attr_e('Wishlist', 'safestore-minimal'); ?>">
                <svg class="sft-header-action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
                <span class="sft-header-action-text">
                    <span class="sft-header-action-label sft-header-action-label--single">Wishlist</span>
                </span>
            </a>
            <?php endif; ?>

            <a class="sft-header-action sft-header-action--cart" href="<?php echo esc_url($cart_url); ?>" aria-label="<?php esc_attr_e('Cart', 'safestore-minimal'); ?>">
                <span class="sft-header-cart-icon">
                    <svg class="sft-header-action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/><path d="M3 4h2l2.4 11.4a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.5L21 8H6"/></svg>
                    <span class="sft-header-cart-badge"><?php echo esc_html($sft_cart_count); ?></span>
                </span>
                <span class="sft-header-action-text">
                    <span class="sft-header-action-eyebrow">Cart</span>
                    <span class="sft-header-action-label sft-header-cart-total"><?php echo wp_kses_post($sft_cart_total); ?></span>
                </span>
            </a>
        </div>
    </div>

    <?php if ( function_exists( 'safestore_mnav_quicklinks' ) ) { safestore_mnav_quicklinks(); } ?>
    </div><!-- /.sft-header-sticky -->

    <?php if ( function_exists( 'safestore_mnav_search_backdrop' ) ) { safestore_mnav_search_backdrop(); } ?>

<!-- Dark Overlay Backdrop Component -->
<div class="menu-overlay" id="menu-overlay"></div>
<!-- Off-Canvas Left Side Sliding Panel -->
<div class="offcanvas-menu" id="offcanvas-menu">
  <div class="offcanvas-header">
    <div class="menu-logo">All Categories</div>
    <button type="button" class="mobile-menu-close" aria-label="Close Menu">&times;</button>
  </div>
  
  <div class="offcanvas-body">
    <nav class="sft-header-nav" aria-label="Categories">
        <div class="sft-header-nav-inner">
            <?php
            if (has_nav_menu('header_categories')) {
                wp_nav_menu(
                    array(
                        'theme_location' => 'header_categories',
                        'container'      => 'div',
                        'container_class'=> 'sft-menu-container', /* Unique scoped container */
                        'menu_class'     => 'sft-header-nav-list', /* Kept your exact native list class */
                        'depth'          => 0,                       
                        'fallback_cb'    => false,
                    )
                );
            }
            ?>
            <a class="sft-header-deal" href="<?php echo esc_url(home_url('/deals/')); ?>">
                <span class="sft-header-deal-dot" aria-hidden="true"></span>
                Today's Deals
            </a>
        </div>
    </nav>

    <?php
    // Mobile drawer extras (hidden >=1025px, where this panel is the category bar).
    $sft_drawer_tel_raw = (string) apply_filters( 'safestore_minimal_phone_tel', '+8801811892291' );
    $sft_drawer_tel     = preg_replace( '/[^\d+]/', '', $sft_drawer_tel_raw );
    $sft_drawer_tel_lab = function_exists( 'safestore_wa_format_number' )
        ? safestore_wa_format_number( $sft_drawer_tel_raw )
        : trim( preg_replace( '/^\+?880/', '+880 ', $sft_drawer_tel_raw ) );

    // Gumboot is only added when the category menu above doesn't list it already.
    $sft_drawer_menu_text = '';
    $sft_drawer_locations = get_nav_menu_locations();
    if ( ! empty( $sft_drawer_locations['header_categories'] ) ) {
        foreach ( (array) wp_get_nav_menu_items( $sft_drawer_locations['header_categories'] ) as $sft_drawer_item ) {
            $sft_drawer_menu_text .= ' ' . $sft_drawer_item->title . ' ' . $sft_drawer_item->url;
        }
    }

    $sft_drawer_icons = array(
        'tag'     => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.4"/>',
        'account' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'track'   => '<path d="M2.8 6.6h11.4v9.6H2.8z"/><path d="M14.2 10h3.4l3.6 3.3v2.9h-7z"/><circle cx="7" cy="18.4" r="1.7"/><circle cx="17.4" cy="18.4" r="1.7"/>',
        'bulk'    => '<path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5z"/><path d="M3 7.5 12 12l9-4.5"/><path d="M12 12v9"/>',
        'contact' => '<path d="M21 11.5a8.4 8.4 0 0 1-12.3 7.5L3 20.5l1.6-5A8.4 8.4 0 1 1 21 11.5z"/>',
    );

    $sft_drawer_links = array();
    if ( false === stripos( $sft_drawer_menu_text, 'gumboot' ) && false === stripos( $sft_drawer_menu_text, 'gum-boot' ) ) {
        $sft_drawer_gumboot = function_exists( 'safestore_category_url' )
            ? safestore_category_url( array( 'safety-gumboot', 'gumboot', 'gumboots', 'gum-boot' ), add_query_arg( array( 's' => 'gumboot', 'post_type' => 'product' ), home_url( '/' ) ) )
            : add_query_arg( array( 's' => 'gumboot', 'post_type' => 'product' ), home_url( '/' ) );
        $sft_drawer_links[] = array( __( 'Safety Gumboot', 'safestore-minimal' ), $sft_drawer_gumboot, 'tag' );
    }
    $sft_drawer_links[] = array( is_user_logged_in() ? __( 'My Account', 'safestore-minimal' ) : __( 'Login / Register', 'safestore-minimal' ), function_exists( 'safestore_mnav_account_url' ) ? safestore_mnav_account_url() : wp_login_url(), 'account' );
    $sft_drawer_links[] = array( __( 'Track Order', 'safestore-minimal' ), home_url( '/track-order/' ), 'track' );
    $sft_drawer_links[] = array( __( 'Bulk Orders', 'safestore-minimal' ), home_url( '/bulk-orders/' ), 'bulk' );
    $sft_drawer_links[] = array( __( 'Contact', 'safestore-minimal' ), home_url( '/contact/' ), 'contact' );
    ?>
    <nav class="sft-drawer-extra" aria-labelledby="sft-drawer-extra-title">
        <p class="sft-drawer-extra__title" id="sft-drawer-extra-title"><?php esc_html_e( 'Help & account', 'safestore-minimal' ); ?></p>
        <ul class="sft-drawer-extra__list">
            <?php foreach ( $sft_drawer_links as $sft_drawer_link ) : ?>
                <li>
                    <a href="<?php echo esc_url( $sft_drawer_link[1] ); ?>">
                        <svg class="sft-drawer-extra__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?php echo $sft_drawer_icons[ $sft_drawer_link[2] ]; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG paths. ?></svg>
                        <span class="sft-drawer-extra__label"><?php echo esc_html( $sft_drawer_link[0] ); ?></span>
                        <svg class="sft-drawer-extra__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6"/></svg>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ( '' !== $sft_drawer_tel ) : ?>
            <a class="sft-drawer-extra__call" href="<?php echo esc_url( 'tel:' . $sft_drawer_tel ); ?>" data-sft-copy-skip>
                <span class="sft-drawer-extra__call-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M6.7 3.4 9.1 3l1.8 4.2-2 1.6a12.2 12.2 0 0 0 5.3 5.3l1.6-2 4.2 1.8-.4 2.4a2 2 0 0 1-2.2 1.7A15.6 15.6 0 0 1 5 6.2a2 2 0 0 1 1.7-2.8z"/></svg>
                </span>
                <span class="sft-drawer-extra__call-text">
                    <span class="sft-drawer-extra__call-title"><?php esc_html_e( 'Call us', 'safestore-minimal' ); ?> <span class="sft-drawer-extra__call-num"><?php echo esc_html( $sft_drawer_tel_lab ); ?></span></span>
                    <span class="sft-drawer-extra__hours"><?php echo esc_html( function_exists( 'safestore_store_hours' ) ? safestore_store_hours() : '' ); ?></span>
                </span>
            </a>
        <?php endif; ?>
    </nav>
  </div>
</div>
</header>

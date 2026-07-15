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
?>

<header class="sft-header" role="banner">
    <div class="sft-header-main">
        <a class="sft-header-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php bloginfo('name'); ?> home">
            <img class="sft-header-brand-logo"
                src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo/safe-store-bd.png'); ?>"
                alt="<?php bloginfo('name'); ?>" />
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

        <div class="sft-header-actions">
            <a class="sft-header-action" href="<?php echo esc_url(wp_login_url()); ?>">
                <svg class="sft-header-action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                <span class="sft-header-action-text">
                    <span class="sft-header-action-eyebrow">Hello, sign in</span>
                    <span class="sft-header-action-label">Account</span>
                </span>
            </a>

            <a class="sft-header-action" href="<?php echo esc_url(home_url('/wishlist/')); ?>">
                <svg class="sft-header-action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
                <span class="sft-header-action-text">
                    <span class="sft-header-action-label sft-header-action-label--single">Wishlist</span>
                </span>
            </a>

            <a class="sft-header-action sft-header-action--cart" href="<?php echo esc_url($cart_url); ?>">
                <span class="sft-header-cart-icon">
                    <svg class="sft-header-action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/><path d="M3 4h2l2.4 11.4a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.5L21 8H6"/></svg>
                    <span class="sft-header-cart-badge"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
                </span>
                <span class="sft-header-action-text">
                    <span class="sft-header-action-eyebrow">Cart</span>
                    <span class="sft-header-action-label"><?php echo WC()->cart->get_cart_total(); ?></span>
                </span>
            </a>
        </div>
    </div>
<button class="mobile-menu-trigger" aria-label="Open Menu" aria-controls="offcanvas-menu" aria-expanded="false">
  <span class="hamburger-bar"></span>
  <span class="hamburger-bar"></span>
  <span class="hamburger-bar"></span>
</button>
<!-- Dark Overlay Backdrop Component -->
<div class="menu-overlay" id="menu-overlay"></div>
<!-- Off-Canvas Left Side Sliding Panel -->
<div class="offcanvas-menu" id="offcanvas-menu">
  <div class="offcanvas-header">
    <div class="menu-logo">All Categories</div>
    <button class="mobile-menu-close" aria-label="Close Menu">&times;</button>
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
  </div>
</div>
</header>

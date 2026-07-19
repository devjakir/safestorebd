<?php

/**
 * WhatsApp chat widget — floating button, chat panel, admin settings
 * (Settings → WhatsApp Chat).
 */
require get_template_directory() . '/inc/whatsapp-chat.php';

function safestore_minimal_enqueue_assets() {
    $version = wp_get_theme()->get('Version');

    $style_deps = array();
    foreach (array('woocommerce-general', 'woocommerce-layout', 'woocommerce-blocktheme') as $handle) {
        if (wp_style_is($handle, 'registered')) {
            $style_deps[] = $handle;
        }
    }

    $style_path = get_stylesheet_directory() . '/style.css';
    wp_enqueue_style(
        'safestore-minimal-style',
        get_stylesheet_uri(),
        $style_deps,
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
                true
            );
        }
    }

    wp_enqueue_script(
        'safestore-minimal-header-search-cat',
        get_template_directory_uri() . '/js/header-search-cat.js',
        array(),
        $version,
        true
    );

    if (is_page_template('page-home.php')) {
        wp_enqueue_script(
            'safestore-minimal-hero-slider',
            get_template_directory_uri() . '/js/hero-slider.js',
            array(),
            $version,
            true
        );
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

    $icon = '<svg class="sft-product-card__cart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/><path d="M3 4h2l2.4 11.4a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.5L21 8H6"/></svg>';
    $label = '<span class="sft-product-card__cart-label">' . esc_html($product->add_to_cart_text()) . '</span>';

    $count    = 0;
    $replaced = preg_replace('/<a(\s[^>]*)>[^<]*<\/a>/', '<a$1>' . $icon . $label . '</a>', $link, 1, $count);
    return ($count > 0) ? $replaced : $link;
}
add_filter('woocommerce_loop_add_to_cart_link', 'safestore_minimal_loop_add_to_cart_link', 10, 3);

/**
 * Shop loop: 3 columns, 12 products per page.
 */
add_filter('loop_shop_columns', function () { return 3; }, 99);
add_filter('loop_shop_per_page', function () { return 12; }, 99);

/**
 * Single product (PDP): breadcrumb, summary order, promo/contact/delivery blocks.
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
        add_action('woocommerce_single_product_summary', 'safestore_minimal_pdp_promo_strip', 25);
        add_action('woocommerce_single_product_summary', 'safestore_minimal_pdp_contact_row', 26);
        add_action('woocommerce_single_product_summary', 'safestore_minimal_pdp_trust_line', 27);
        add_action('woocommerce_after_add_to_cart_button', 'safestore_minimal_pdp_buy_now_button');

        add_action('woocommerce_after_single_product_summary', 'safestore_minimal_pdp_fulfillment_section', 8);

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
 * Optional coupon / event strip (filterable for store-specific copy).
 */
function safestore_minimal_pdp_promo_strip() {
    $title = apply_filters('safestore_minimal_pdp_promo_title', __('Safety shoe shopping event', 'safestore-minimal'));
    $text  = apply_filters(
        'safestore_minimal_pdp_promo_text',
        __('Get discounts on safety footwear and workwear — up to 20% off eligible lines.', 'safestore-minimal')
    );
    $code = apply_filters('safestore_minimal_pdp_promo_code', 'SAFETY20');

    if ($title === false) {
        return;
    }
    ?>
    <div class="sft-pdp-promo">
        <div class="sft-pdp-promo__text">
            <p class="sft-pdp-promo__title"><?php echo esc_html($title); ?></p>
            <p class="sft-pdp-promo__desc"><?php echo esc_html($text); ?></p>
        </div>
        <?php if ($code !== false && $code !== '') : ?>
            <div class="sft-pdp-promo__code" data-code="<?php echo esc_attr($code); ?>">
                <span class="sft-pdp-promo__code-label"><?php esc_html_e('Code', 'safestore-minimal'); ?></span>
                <code class="sft-pdp-promo__code-value"><?php echo esc_html($code); ?></code>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * WhatsApp + phone row (set via filters from a child theme or small plugin).
 */
function safestore_minimal_pdp_contact_row() {
    $wa = apply_filters('safestore_minimal_whatsapp_e164', '');
    $wa = preg_replace('/[^0-9]/', '', (string) $wa);
    $tel_raw = apply_filters('safestore_minimal_phone_tel', '+8801761699627');
    $tel_digits = preg_replace('/[^\d+]/', '', (string) $tel_raw);

    if ($wa === '' && $tel_digits === '') {
        return;
    }

    $tel_href = $tel_digits !== '' ? 'tel:' . $tel_digits : '';
    ?>
    <p class="sft-pdp-contact__label"><?php esc_html_e('Need help?', 'safestore-minimal'); ?></p>
    <div class="sft-pdp-contact" role="group" aria-label="<?php esc_attr_e('Product support', 'safestore-minimal'); ?>">
        <?php if ($wa !== '') : ?>
            <a class="sft-pdp-contact__btn sft-pdp-contact__btn--wa" href="<?php echo esc_url('https://wa.me/' . $wa); ?>" target="_blank" rel="noopener noreferrer">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.372-.025-.521-.075-.148-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                <?php esc_html_e('WhatsApp', 'safestore-minimal'); ?>
            </a>
        <?php endif; ?>
        <?php if ($tel_href !== '') : ?>
            <a class="sft-pdp-contact__btn sft-pdp-contact__btn--call" href="<?php echo esc_url($tel_href); ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <?php esc_html_e('Call us', 'safestore-minimal'); ?>
            </a>
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
 * Secondary checkout CTA for simple products (mirrors common “Buy now”).
 */
function safestore_minimal_pdp_buy_now_button() {
    global $product;
    if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) {
        return;
    }
    if (!$product->is_type('simple')) {
        return;
    }
    $url = add_query_arg('add-to-cart', $product->get_id(), wc_get_checkout_url());
    printf(
        '<a class="button sft-pdp-buy-now" href="%s" role="button">%s</a>',
        esc_url($url),
        esc_html__('Buy now', 'safestore-minimal')
    );
}

/**
 * Pickup + courier + returns (filterable address and rates).
 */
function safestore_minimal_pdp_fulfillment_section() {
    $pickup_address = safestore_minimal_get_pickup_address();
    ?>
    <section class="sft-pdp-fulfill" aria-label="<?php echo esc_attr__('Delivery and pickup', 'safestore-minimal'); ?>">
        <h2 class="sft-pdp-fulfill__heading"><?php esc_html_e('How you get it', 'safestore-minimal'); ?></h2>
        <ul class="sft-pdp-fulfill__list">
            <li class="sft-pdp-fulfill__item">
                <span class="sft-pdp-fulfill__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
                </span>
                <div class="sft-pdp-fulfill__body">
                    <p class="sft-pdp-fulfill__title"><?php esc_html_e('Pick up from store', 'safestore-minimal'); ?> <span class="sft-pdp-fulfill__badge"><?php esc_html_e('Free', 'safestore-minimal'); ?></span></p>
                    <p class="sft-pdp-fulfill__detail"><?php echo esc_html($pickup_address); ?></p>
                </div>
            </li>
            <li class="sft-pdp-fulfill__item">
                <span class="sft-pdp-fulfill__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="7" width="16" height="12" rx="1"/><path d="M18 11h2.5a1.5 1.5 0 0 1 1.5 1.5V17a1 1 0 0 1-1 1h-3M8 5v4M12 5v4M6 5h8"/></svg>
                </span>
                <div class="sft-pdp-fulfill__body">
                    <p class="sft-pdp-fulfill__title"><?php esc_html_e('Courier — inside Dhaka', 'safestore-minimal'); ?></p>
                    <p class="sft-pdp-fulfill__detail"><?php esc_html_e('Typical handover within ~24 hours. From ৳80 depending on partner & weight.', 'safestore-minimal'); ?></p>
                </div>
            </li>
            <li class="sft-pdp-fulfill__item">
                <span class="sft-pdp-fulfill__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/></svg>
                </span>
                <div class="sft-pdp-fulfill__body">
                    <p class="sft-pdp-fulfill__title"><?php esc_html_e('Outside Dhaka', 'safestore-minimal'); ?></p>
                    <p class="sft-pdp-fulfill__detail"><?php esc_html_e('2–3 days typical. From ৳135+ by destination and carrier.', 'safestore-minimal'); ?></p>
                </div>
            </li>
            <li class="sft-pdp-fulfill__item">
                <span class="sft-pdp-fulfill__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                </span>
                <div class="sft-pdp-fulfill__body">
                    <p class="sft-pdp-fulfill__title"><?php esc_html_e('Returns', 'safestore-minimal'); ?> <span class="sft-pdp-fulfill__badge sft-pdp-fulfill__badge--muted"><?php esc_html_e('7 days', 'safestore-minimal'); ?></span></p>
                    <p class="sft-pdp-fulfill__detail"><?php esc_html_e('Unused items in original condition where applicable. Contact us before sending anything back.', 'safestore-minimal'); ?></p>
                </div>
            </li>
        </ul>
    </section>
    <?php
}

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
					'a' => __( 'WhatsApp <strong>+880 176 1699 627</strong> or call/email on this site. Open <strong>Sat–Thu, 9am–8pm</strong>; closed Fridays.', 'safestore-minimal' ),
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
 * Office / pickup address (shared site-wide).
 */
function safestore_minimal_get_pickup_address() {
	return apply_filters(
		'safestore_minimal_pdp_pickup_address',
		__( 'Floor 2B, Dream 36 Tower, Bepari Market, Pallabi New Thana, Dhaka-1221', 'safestore-minimal' )
	);
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

		$jobs = array();
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
						'streetAddress'   => safestore_minimal_get_pickup_address(),
						'addressLocality' => 'Dhaka',
						'postalCode'      => '1221',
						'addressCountry'  => 'BD',
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
						/* translators: 1: mailto, 2: email, 3: contact URL */
						__( 'Ask us to access or correct your data at <a href="%1$s">%2$s</a> or our <a href="%3$s">contact page</a>. Your rights under Bangladesh consumer protection law still apply. We may update this policy — see the date above.', 'safestore-minimal' ),
						esc_url( 'mailto:bdsafestore@gmail.com' ),
						'bdsafestore@gmail.com',
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
						__( 'These terms are governed by the laws of Bangladesh. Disputes should first be raised with us at bdsafestore@gmail.com or via our <a href="%s">contact page</a>. We may update these terms — continued use of the site means you accept the current version.', 'safestore-minimal' ),
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
					__( 'Registered contact: bdsafestore@gmail.com · +880 176 1699 627', 'safestore-minimal' ),
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
    <span class="sft-header-action-label"><?php echo WC()->cart->get_cart_total(); ?></span>
    <?php
    $fragments['span.sft-header-action-label'] = ob_get_clean();

    return $fragments;
}
// ACR Dynamically update cart count and price via WooCommerce AJAX End

/**
 * Prevent duplicate category description on shop archives.
 * The theme's woocommerce.php header already outputs term_description(),
 * so remove WooCommerce's default archive description output.
 */
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );

<?php
/**
 * Single-product campaign landing page (page-templates/template-product-landing.php).
 *
 * Built for Facebook / Instagram ad traffic: ONE product above the fold, pick a
 * size, tap "অর্ডার করুন" and land on the normal WooCommerce checkout with that
 * size already in the cart (COD, district → thana, courier charge all live there).
 *
 * - Settings per page (meta box): main product, extra products, headline, lede,
 *   benefit bullets, badge, index/noindex. Anything empty falls back to the
 *   defaults below and then to live product data.
 * - ?spl_add=<variation or product id>&qty=N → cart → checkout (template_redirect).
 * - Orders that started here get an order note + meta "_spl_landing_page".
 * - Template-only assets; floating WhatsApp widget and mobile bottom nav are off
 *   (the page has its own sticky bar).
 */

defined( 'ABSPATH' ) || exit;

define( 'SAFESTORE_PL_TEMPLATE', 'page-templates/template-product-landing.php' );
define( 'SAFESTORE_PL_MAX_QTY', 24 );

function safestore_pl_is_landing() {
	return ! is_admin() && is_page_template( SAFESTORE_PL_TEMPLATE );
}

/* -------------------------------------------------------------------------
 * Defaults (first campaign: Haojia safety shoe). Page settings override these.
 * ---------------------------------------------------------------------- */

function safestore_pl_defaults() {
	return apply_filters(
		'safestore_pl_defaults',
		array(
			// URL, slug or ID of the product this page sells.
			'product'  => 'haojia-safety-shoes-price-in-bangladesh',
			// Cards under the main product — URL, slug or ID each.
			'more'     => array(
				'lblbx-safety-shoes-price-in-bangladesh',
				'tinggu-safety-shoes',
				'solex-super-safety-shoes-bd',
				'runner-ss4-steel-toe-safety-shoes',
				'gat-safety-shoes',
				'vaultex-safety-helmet',
			),
			// Used only when the page sells the default product and has no copy of its own.
			'headline' => 'Haojia সেফটি শু — স্টিল টো, অ্যান্টি-স্লিপ, সারাদিন আরাম',
			'lede'     => 'কনস্ট্রাকশন, ফ্যাক্টরি ও ওয়্যারহাউসের দৈনন্দিন কাজের জন্য। সাইজ নির্বাচন করে অর্ডার করুন — সারা বাংলাদেশে ক্যাশ অন ডেলিভারি।',
			'bullets'  => array(
				'স্টিল টো ক্যাপ — ভারী বস্তু পড়লেও পায়ের আঙুল সুরক্ষিত থাকে',
				'পাংচার-রেজিস্ট্যান্ট মিডসোল — পেরেক বা ধারালো বস্তু ভেদ করতে পারে না',
				'অ্যান্টি-স্লিপ ও তেল-প্রতিরোধী রাবার সোল — ভেজা বা তৈলাক্ত মেঝেতেও পা পিছলায় না',
				'হালকা ও বাতাস চলাচলের উপযোগী — কুশন ইনসোলের কারণে সারাদিন আরামে পরা যায়',
			),
			'uses'     => array( 'কনস্ট্রাকশন সাইট', 'গার্মেন্টস ও ফ্যাক্টরি', 'ওয়্যারহাউস', 'ওয়ার্কশপ', 'ইলেকট্রিক্যাল মেইনটেন্যান্স' ),
			'badge'    => '',
		)
	);
}

/** Page settings: meta → defaults. */
function safestore_pl_settings( $page_id ) {
	$d   = safestore_pl_defaults();
	$get = static function ( $key ) use ( $page_id ) {
		return trim( (string) get_post_meta( $page_id, $key, true ) );
	};
	$lines = static function ( $raw ) {
		return array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', (string) $raw ) ) ) );
	};

	$product    = $get( '_spl_product' );
	$product    = '' !== $product ? $product : $d['product'];
	$is_default = $product === $d['product'];
	$more       = $lines( $get( '_spl_more' ) );
	$bullets    = $lines( $get( '_spl_bullets' ) );

	return array(
		'product'  => $product,
		'more'     => $more ? $more : $d['more'],
		'headline' => '' !== $get( '_spl_headline' ) ? $get( '_spl_headline' ) : ( $is_default ? $d['headline'] : '' ),
		'lede'     => '' !== $get( '_spl_lede' ) ? $get( '_spl_lede' ) : ( $is_default ? $d['lede'] : '' ),
		'bullets'  => $bullets ? $bullets : ( $is_default ? $d['bullets'] : array() ),
		'uses'     => $is_default ? $d['uses'] : array(),
		'badge'    => '' !== $get( '_spl_badge' ) ? $get( '_spl_badge' ) : $d['badge'],
		'index'    => '1' === $get( '_spl_index' ),
	);
}

/* -------------------------------------------------------------------------
 * Small helpers
 * ---------------------------------------------------------------------- */

function safestore_pl_bn( $value ) {
	return function_exists( 'safestore_bl_bn' ) ? safestore_bl_bn( $value ) : (string) $value;
}

/** "৳২,৫০০" or '' */
function safestore_pl_taka( $amount ) {
	if ( function_exists( 'safestore_bl_taka' ) ) {
		return safestore_bl_taka( $amount );
	}
	if ( '' === $amount || null === $amount || ! is_numeric( $amount ) ) {
		return '';
	}
	return '৳' . number_format( (float) $amount, 0, '.', ',' );
}

function safestore_pl_icon( $name, $size = 24 ) {
	return function_exists( 'safestore_bl_icon' ) ? safestore_bl_icon( $name, $size ) : '';
}

/** Five stars, $rating of them filled (rounded to halves shown as full). */
function safestore_pl_stars( $rating, $size = 18 ) {
	$rating = (float) $rating;
	$out    = '<span class="spl-stars" role="img" aria-label="' . esc_attr( safestore_pl_bn( number_format( $rating, 1 ) ) . ' / ৫' ) . '">';
	for ( $i = 1; $i <= 5; $i++ ) {
		$fill = $rating >= $i - 0.25 ? 'currentColor' : 'none';
		$out .= sprintf(
			'<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="%2$s" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m12 2.8 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.6l-5.8 3.1 1.1-6.5-4.7-4.6 6.5-.9z"/></svg>',
			(int) $size,
			$fill
		);
	}
	return $out . '</span>';
}

/**
 * Product from a URL, slug or ID. Variations resolve to their parent.
 *
 * @return WC_Product|null published product or null
 */
function safestore_pl_find_product( $ref ) {
	$ref = trim( (string) $ref );
	if ( '' === $ref || ! function_exists( 'wc_get_product' ) ) {
		return null;
	}

	$id = 0;
	if ( ctype_digit( $ref ) ) {
		$id = (int) $ref;
	} elseif ( preg_match( '#^https?://#i', $ref ) ) {
		$id = (int) url_to_postid( $ref );
		if ( ! $id ) {
			$path = trim( (string) wp_parse_url( $ref, PHP_URL_PATH ), '/' );
			$slug = '' !== $path ? basename( $path ) : '';
			$post = '' !== $slug ? get_page_by_path( $slug, OBJECT, 'product' ) : null;
			$id   = $post ? (int) $post->ID : 0;
		}
	} else {
		$post = get_page_by_path( sanitize_title( $ref ), OBJECT, 'product' );
		$id   = $post ? (int) $post->ID : 0;
	}

	$product = $id ? wc_get_product( $id ) : null;
	if ( $product && $product->is_type( 'variation' ) ) {
		$product = wc_get_product( $product->get_parent_id() );
	}
	return ( $product && 'publish' === $product->get_status() ) ? $product : null;
}

/** @return array{now: float, was: float, from: bool} display prices; was = 0 when not on sale */
function safestore_pl_price( $product ) {
	if ( $product->is_type( 'variable' ) ) {
		$now = (float) $product->get_variation_price( 'min', true );
		$max = (float) $product->get_variation_price( 'max', true );
		$was = (float) $product->get_variation_regular_price( 'min', true );
		return array( 'now' => $now, 'was' => $was > $now ? $was : 0, 'from' => $max > $now );
	}
	$now = (float) wc_get_price_to_display( $product );
	$reg = $product->get_regular_price();
	$was = '' !== $reg ? (float) wc_get_price_to_display( $product, array( 'price' => $reg ) ) : 0;
	return array( 'now' => $now, 'was' => $was > $now ? $was : 0, 'from' => false );
}

/**
 * Selectable options for a variable product with ONE attribute (sizes).
 *
 * @return array|null array( 'name' => label, 'items' => [...] ) — items empty for a
 *                    simple product; null when the product needs more than one
 *                    choice (colour + size) and is better bought on its own page.
 */
function safestore_pl_options( $product ) {
	if ( ! $product->is_type( 'variable' ) ) {
		return array( 'name' => '', 'items' => array() );
	}
	$attrs = $product->get_variation_attributes();
	if ( 1 !== count( $attrs ) ) {
		return null;
	}
	$attr_name = (string) key( $attrs );
	$taxonomy  = taxonomy_exists( $attr_name ) ? $attr_name : '';
	$field     = 'attribute_' . sanitize_title( $attr_name );
	$items     = array();

	foreach ( $product->get_available_variations() as $v ) {
		$value = isset( $v['attributes'][ $field ] ) ? (string) $v['attributes'][ $field ] : '';
		if ( '' === $value ) {
			continue; // "Any size" — nothing to pre-select
		}
		// inc/footwear-sizing.php marks sizes the shop does not sell as inactive.
		if ( isset( $v['variation_is_active'] ) && ! $v['variation_is_active'] ) {
			continue;
		}
		$label = $value;
		if ( '' !== $taxonomy ) {
			$term  = get_term_by( 'slug', $value, $taxonomy );
			$label = $term ? $term->name : $label;
		}
		$items[] = array(
			'id'       => (int) $v['variation_id'],
			'label'    => $label,
			'in_stock' => ! empty( $v['is_in_stock'] ) && ! empty( $v['is_purchasable'] ),
			'price'    => isset( $v['display_price'] ) ? (float) $v['display_price'] : 0,
			'max'      => ( isset( $v['max_qty'] ) && '' !== $v['max_qty'] ) ? (int) $v['max_qty'] : SAFESTORE_PL_MAX_QTY,
		);
	}

	usort(
		$items,
		static function ( $a, $b ) {
			if ( is_numeric( $a['label'] ) && is_numeric( $b['label'] ) ) {
				return (float) $a['label'] <=> (float) $b['label'];
			}
			return strnatcasecmp( $a['label'], $b['label'] );
		}
	);

	return array( 'name' => wc_attribute_label( $attr_name, $product ), 'items' => $items );
}

/** Benefit bullets: page copy → <li>s of the short description → sentences. */
function safestore_pl_bullets( $product, $override ) {
	if ( $override ) {
		return array_slice( $override, 0, 6 );
	}
	$html  = (string) $product->get_short_description();
	$items = array();
	if ( preg_match_all( '#<li[^>]*>(.*?)</li>#is', $html, $m ) ) {
		foreach ( $m[1] as $li ) {
			$t = trim( wp_strip_all_tags( $li ) );
			if ( '' !== $t ) {
				$items[] = $t;
			}
		}
	}
	if ( ! $items ) {
		$text = trim( wp_strip_all_tags( $html ) );
		foreach ( preg_split( '/(?<=[.!?।])\s+/u', $text ) as $s ) {
			$s = trim( (string) $s );
			if ( mb_strlen( $s ) > 8 ) {
				$items[] = $s;
			}
		}
	}
	return array_slice( $items, 0, 6 );
}

/** Latest approved reviews of the product (real social proof only). */
function safestore_pl_reviews( $product, $limit = 3 ) {
	$comments = get_comments(
		array(
			'post_id' => $product->get_id(),
			'status'  => 'approve',
			'type'    => 'review',
			'number'  => $limit,
			'orderby' => 'comment_date_gmt',
			'order'   => 'DESC',
		)
	);
	$out = array();
	foreach ( (array) $comments as $c ) {
		$text = trim( wp_strip_all_tags( (string) $c->comment_content ) );
		if ( '' === $text ) {
			continue;
		}
		$out[] = array(
			'text'     => $text,
			'name'     => (string) $c->comment_author,
			'rating'   => (int) get_comment_meta( $c->comment_ID, 'rating', true ),
			'verified' => function_exists( 'wc_review_is_from_verified_owner' ) && wc_review_is_from_verified_owner( $c->comment_ID ),
		);
	}
	return $out;
}

/** Extra product cards from the page's list, else WooCommerce upsells / cross-sells. */
function safestore_pl_more_products( $refs, $main ) {
	$seen  = array( $main->get_id() => true );
	$cards = array();
	$ids   = array();
	foreach ( (array) $refs as $ref ) {
		$p = safestore_pl_find_product( $ref );
		if ( $p ) {
			$ids[] = $p;
		}
	}
	if ( ! $ids ) {
		foreach ( array_merge( $main->get_upsell_ids(), $main->get_cross_sell_ids() ) as $id ) {
			$p = wc_get_product( $id );
			if ( $p && 'publish' === $p->get_status() ) {
				$ids[] = $p;
			}
		}
	}
	foreach ( $ids as $p ) {
		if ( isset( $seen[ $p->get_id() ] ) || ! $p->is_visible() ) {
			continue;
		}
		$seen[ $p->get_id() ] = true;
		$price = safestore_pl_price( $p );
		$thumb = (int) $p->get_image_id();
		$cards[] = array(
			'url'      => $p->get_permalink(),
			'name'     => $p->get_name(),
			'price'    => $price,
			'in_stock' => $p->is_in_stock(),
			'image'    => $thumb ? wp_get_attachment_image(
				$thumb,
				'woocommerce_thumbnail',
				false,
				array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => $p->get_name(), 'sizes' => '(max-width: 599px) 45vw, 280px' )
			) : wc_placeholder_img( 'woocommerce_thumbnail' ),
		);
		if ( count( $cards ) >= 8 ) {
			break;
		}
	}
	return $cards;
}

/** Delivery facts: live courier lane prices when the shipping method is on, else site copy. */
function safestore_pl_delivery() {
	$inside  = '৳৮০';
	$outside = '৳১৩৫';
	if ( function_exists( 'safestorebd_courier_public_rates' ) ) {
		$rates = safestorebd_courier_public_rates();
		if ( is_array( $rates ) && isset( $rates['inside']['first'], $rates['outside']['first'] ) ) {
			$inside  = safestore_pl_taka( $rates['inside']['first'] );
			$outside = safestore_pl_taka( $rates['outside']['first'] );
		}
	}
	$address = function_exists( 'safestore_minimal_get_pickup_address' ) ? safestore_minimal_get_pickup_address() : 'Pallabi, Dhaka 1216';

	return apply_filters(
		'safestore_pl_delivery',
		array(
			array( 'icon' => 'truck', 'title' => 'ঢাকার ভেতরে', 'text' => 'দুপুর ২টার আগে অর্ডার নিশ্চিত হলে সাধারণত ২৪ ঘণ্টার মধ্যে ডেলিভারি। চার্জ ' . $inside . ' থেকে শুরু।' ),
			array( 'icon' => 'pin', 'title' => 'ঢাকার বাইরে — ৬৪ জেলায়', 'text' => 'কুরিয়ারের মাধ্যমে ডিসপ্যাচের ২–৩ কার্যদিবসের মধ্যে ডেলিভারি। চার্জ ' . $outside . ' থেকে শুরু।' ),
			array( 'icon' => 'card', 'title' => 'ক্যাশ অন ডেলিভারি', 'text' => 'পণ্য হাতে পেয়ে মূল্য পরিশোধ করুন। bKash ও Nagad-এও পরিশোধ করা যায়।' ),
			array( 'icon' => 'return', 'title' => '৭ দিনে রিটার্ন ও এক্সচেঞ্জ', 'text' => 'ডেলিভারির ৭ দিনের মধ্যে অব্যবহৃত পণ্য অরিজিনাল প্যাকেটসহ ফেরত বা পরিবর্তন করা যায়। আগে আমাদের জানাতে হবে।' ),
			array( 'icon' => 'box', 'title' => 'অফিস থেকে সংগ্রহ (ফ্রি)', 'text' => 'সরাসরি এসে পণ্য দেখে নিতে পারেন: ' . $address . ' (শনি–বৃহস্পতি, সকাল ৯টা – রাত ৮টা)।' ),
		),
		$inside,
		$outside
	);
}

function safestore_pl_faq( $product, $has_options ) {
	$faq = array(
		array(
			'q' => 'ক্যাশ অন ডেলিভারি সুবিধা আছে কি?',
			'a' => 'হ্যাঁ। পণ্য হাতে পেয়ে যাচাই করার পর মূল্য পরিশোধ করবেন। bKash ও Nagad-এও পরিশোধ করা যায়।',
		),
		array(
			'q' => 'ডেলিভারিতে কত দিন লাগে, চার্জ কত?',
			'a' => 'ঢাকার ভেতরে সাধারণত ২৪ ঘণ্টার মধ্যে এবং ঢাকার বাইরে ২–৩ কার্যদিবসের মধ্যে ডেলিভারি হয়। চেকআউটে ঠিকানা দেওয়ার পর ডেলিভারি চার্জ দেখা যাবে। পল্লবী অফিস থেকে বিনা খরচে পণ্য সংগ্রহও করতে পারেন।',
		),
	);
	if ( $has_options ) {
		$faq[] = array(
			'q' => 'সাইজ না মিললে কী করব?',
			'a' => 'ডেলিভারির ৭ দিনের মধ্যে অব্যবহৃত অবস্থায়, অরিজিনাল প্যাকেটসহ সাইজ পরিবর্তন করা যায়; এজন্য আগে আমাদের জানাতে হবে। অর্ডারের আগে নিশ্চিত হতে চাইলে WhatsApp-এ আপনার পায়ের মাপ (সেমি) বা নিয়মিত পরা সাইজ জানান, আমরা সঠিক সাইজ জানিয়ে দেব।',
		);
	}
	$faq[] = array(
		'q' => 'পণ্য কি নতুন এবং ছবির মতোই?',
		'a' => 'হ্যাঁ, সম্পূর্ণ নতুন পণ্য। ছবিতে যা দেখছেন, সেটিই পাবেন। ডেলিভারির সময় পণ্য যাচাই করে তারপর মূল্য পরিশোধ করুন।',
	);
	$faq[] = array(
		'q' => 'কীভাবে অর্ডার করব?',
		'a' => ( $has_options ? 'সাইজ নির্বাচন করে ' : '' ) . '"অর্ডার করুন" বাটনে ক্লিক করুন, তারপর নাম, মোবাইল নম্বর ও ঠিকানা দিয়ে অর্ডার নিশ্চিত করুন। চাইলে WhatsApp-এ ' . ( $has_options ? 'সাইজ ও ' : '' ) . 'ঠিকানা পাঠিয়েও অর্ডার করতে পারেন।',
	);
	return apply_filters( 'safestore_pl_faq', $faq, $product );
}

/* -------------------------------------------------------------------------
 * ?spl_add=<id>&qty=N → cart → checkout
 * ---------------------------------------------------------------------- */

add_action(
	'template_redirect',
	static function () {
		if ( ! safestore_pl_is_landing() || ! isset( $_GET['spl_add'] ) || ! function_exists( 'WC' ) || ! WC()->cart ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		// Plain GET link, same model as WooCommerce's own ?add-to-cart=; nothing is written until checkout.
		// phpcs:disable WordPress.Security.NonceVerification
		$page_id  = (int) get_queried_object_id();
		$settings = safestore_pl_settings( $page_id );
		$product  = safestore_pl_find_product( $settings['product'] );
		$pick     = absint( $_GET['spl_add'] );
		$qty      = isset( $_GET['qty'] ) ? max( 1, min( SAFESTORE_PL_MAX_QTY, absint( $_GET['qty'] ) ) ) : 1;
		// phpcs:enable
		$back = static function ( $code ) use ( $page_id ) {
			wp_safe_redirect( add_query_arg( 'spl_err', $code, get_permalink( $page_id ) ) . '#order' );
			exit;
		};

		if ( ! $product || ! $product->is_purchasable() ) {
			$back( 'product' );
		}

		$product_id   = $product->get_id();
		$variation_id = 0;
		$attrs        = array();

		if ( $product->is_type( 'variable' ) ) {
			$variation = $pick ? wc_get_product( $pick ) : null;
			if ( ! $variation || ! $variation->is_type( 'variation' ) || (int) $variation->get_parent_id() !== $product_id ) {
				$back( 'size' );
			}
			$variation_id = $pick;
			foreach ( $variation->get_variation_attributes() as $key => $value ) {
				$attrs[ $key ] = $value; // attribute_pa_size => 42
			}
		} elseif ( $pick && $pick !== $product_id ) {
			$back( 'product' );
		}

		// Same line already in the cart (double tap, back button): set the quantity, don't stack a second pair.
		$cart_id  = WC()->cart->generate_cart_id( $product_id, $variation_id, $attrs );
		$existing = WC()->cart->find_product_in_cart( $cart_id );
		if ( $existing ) {
			$ok = WC()->cart->set_quantity( $existing, $qty );
		} else {
			$ok = (bool) WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $attrs );
		}
		if ( ! $ok ) {
			$back( 'cart' ); // WooCommerce queued the reason as an error notice; the page prints it.
		}

		if ( WC()->session ) {
			WC()->session->set(
				'spl_landing',
				array( 'page' => $page_id, 'product' => $product_id, 'variation' => $variation_id )
			);
		}
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	},
	5
);

/** Error text for ?spl_err= (WooCommerce notices for 'cart'). */
function safestore_pl_error_text() {
	$code = isset( $_GET['spl_err'] ) ? sanitize_key( wp_unslash( $_GET['spl_err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( '' === $code ) {
		return '';
	}
	if ( 'cart' === $code && function_exists( 'wc_get_notices' ) ) {
		$lines = array();
		foreach ( (array) wc_get_notices( 'error' ) as $n ) {
			$text = is_array( $n ) && isset( $n['notice'] ) ? $n['notice'] : $n;
			$text = trim( wp_strip_all_tags( (string) $text ) );
			if ( '' !== $text ) {
				$lines[] = $text;
			}
		}
		wc_clear_notices();
		if ( $lines ) {
			$joined = implode( ' ', $lines );
			// WooCommerce's stock notices are English; the page is Bangla.
			if ( preg_match( '/out of stock|not enough|only \d+ (?:left|available)/i', $joined ) ) {
				return 'নির্বাচিত সাইজটি বর্তমানে স্টকে নেই অথবা পর্যাপ্ত পরিমাণে নেই। অন্য সাইজ নির্বাচন করুন, অথবা WhatsApp-এ যোগাযোগ করুন।';
			}
			return $joined;
		}
	}
	$map = array(
		'size'    => 'অনুগ্রহ করে আগে সাইজ নির্বাচন করুন।',
		'product' => 'দুঃখিত, পণ্যটি এই মুহূর্তে অর্ডার করা যাচ্ছে না। বিস্তারিত জানতে WhatsApp-এ যোগাযোগ করুন।',
		'cart'    => 'দুঃখিত, অর্ডারটি সম্পন্ন করা যায়নি। আবার চেষ্টা করুন অথবা WhatsApp-এ যোগাযোগ করুন।',
	);
	return isset( $map[ $code ] ) ? $map[ $code ] : $map['cart'];
}

// Mark orders that started on a landing page (next to WooCommerce's own order attribution).
add_action(
	'woocommerce_checkout_order_created',
	static function ( $order ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}
		$lp = WC()->session->get( 'spl_landing' );
		if ( ! is_array( $lp ) || empty( $lp['page'] ) ) {
			return;
		}
		$title = get_the_title( (int) $lp['page'] );
		$order->update_meta_data( '_spl_landing_page', $title . ' (#' . (int) $lp['page'] . ')' );
		$order->update_meta_data( '_spl_landing_url', get_permalink( (int) $lp['page'] ) );
		$order->add_order_note( sprintf( 'Order started on landing page: %s — %s', $title, get_permalink( (int) $lp['page'] ) ) );
		$order->save();
		WC()->session->__unset( 'spl_landing' );
	}
);

/* -------------------------------------------------------------------------
 * Page settings meta box
 * ---------------------------------------------------------------------- */

function safestore_pl_fields() {
	return array(
		'_spl_product'  => array( 'text', __( 'Main product', 'safestore-minimal' ), __( 'Product URL, slug or ID. Empty = the default product.', 'safestore-minimal' ) ),
		'_spl_headline' => array( 'text', __( 'Headline (H1)', 'safestore-minimal' ), __( 'Bangla. Empty = product name (default product has its own copy).', 'safestore-minimal' ) ),
		'_spl_lede'     => array( 'textarea', __( 'Sub-headline', 'safestore-minimal' ), __( 'One or two short sentences under the headline.', 'safestore-minimal' ) ),
		'_spl_bullets'  => array( 'textarea', __( 'Benefit bullets', 'safestore-minimal' ), __( 'One per line, 3–6 lines. Empty = bullet points of the product short description.', 'safestore-minimal' ) ),
		'_spl_badge'    => array( 'text', __( 'Badge on the photo', 'safestore-minimal' ), __( 'Optional, e.g. "ক্যাম্পেইন অফার" or "স্টক সীমিত". Leave empty for none.', 'safestore-minimal' ) ),
		'_spl_more'     => array( 'textarea', __( 'More products (cards below)', 'safestore-minimal' ), __( 'One product URL, slug or ID per line. Empty = defaults, then the product\'s upsells / cross-sells.', 'safestore-minimal' ) ),
	);
}

add_action(
	'add_meta_boxes_page',
	static function () {
		add_meta_box( 'spl-settings', __( 'Product Landing (Facebook) — page settings', 'safestore-minimal' ), 'safestore_pl_render_box', 'page', 'normal', 'high' );
	}
);

function safestore_pl_render_box( $post ) {
	wp_nonce_field( 'spl_save_' . $post->ID, 'spl_nonce' );
	$d = safestore_pl_defaults();
	echo '<p class="description">' . esc_html__( 'Used only when the page template is "Product Landing (Facebook)". Default product: ', 'safestore-minimal' ) . '<code>' . esc_html( $d['product'] ) . '</code></p>';
	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( safestore_pl_fields() as $key => $f ) {
		list( $type, $label, $hint ) = $f;
		$value = (string) get_post_meta( $post->ID, $key, true );
		$id    = 'spl-' . substr( $key, 5 );
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'textarea' === $type ) {
			echo '<textarea class="large-text" rows="4" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( $value ) . '</textarea>';
		} else {
			echo '<input type="text" class="large-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
		}
		echo '<p class="description">' . esc_html( $hint ) . '</p></td></tr>';
	}
	$index = '1' === (string) get_post_meta( $post->ID, '_spl_index', true );
	echo '<tr><th scope="row">' . esc_html__( 'Search engines', 'safestore-minimal' ) . '</th><td><label><input type="checkbox" name="_spl_index" value="1"' . checked( $index, true, false ) . '> ' . esc_html__( 'Allow Google to index this page', 'safestore-minimal' ) . '</label>';
	echo '<p class="description">' . esc_html__( 'Off (recommended for ad campaigns): noindex, so it never competes with the real product page. On: indexed, canonical points at the product page.', 'safestore-minimal' ) . '</p></td></tr>';
	echo '</tbody></table>';
}

add_action(
	'save_post_page',
	static function ( $post_id ) {
		if ( ! isset( $_POST['spl_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['spl_nonce'] ) ), 'spl_save_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
		foreach ( safestore_pl_fields() as $key => $f ) {
			$raw   = isset( $_POST[ $key ] ) ? wp_unslash( (string) $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$value = 'textarea' === $f[0] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
			if ( '' === trim( $value ) ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
		if ( ! empty( $_POST['_spl_index'] ) ) {
			update_post_meta( $post_id, '_spl_index', '1' );
		} else {
			delete_post_meta( $post_id, '_spl_index' );
		}
	}
);

/* -------------------------------------------------------------------------
 * Head: title, description, robots / canonical, OG, LCP preload
 * ---------------------------------------------------------------------- */

/** Called from the template before wp_head(). */
function safestore_pl_head( $seo, $index, $canonical, $preload ) {
	$has_seo_plugin = defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' );

	if ( ! $index ) {
		// Campaign page: keep it out of Google so it never competes with the product page.
		add_filter(
			'wp_robots',
			static function ( $robots ) {
				unset( $robots['index'], $robots['max-image-preview'] );
				$robots['noindex'] = true;
				$robots['follow']  = true;
				return $robots;
			},
			20
		);
		add_filter( 'rank_math/frontend/robots', static function () {
			return array( 'index' => 'noindex', 'follow' => 'follow' );
		} );
		add_filter( 'wpseo_robots', static function () {
			return 'noindex, follow';
		} );
	} else {
		add_filter( 'rank_math/frontend/canonical', static function () use ( $canonical ) {
			return $canonical;
		} );
		add_filter( 'wpseo_canonical', static function () use ( $canonical ) {
			return $canonical;
		} );
	}

	if ( ! $has_seo_plugin ) {
		add_filter(
			'pre_get_document_title',
			static function () use ( $seo ) {
				return $seo['title'];
			},
			20
		);
	}
	add_filter(
		'rank_math/frontend/description',
		static function ( $desc ) use ( $seo ) {
			return '' !== trim( (string) $desc ) ? $desc : $seo['description'];
		}
	);

	add_action(
		'wp_head',
		static function () use ( $seo, $index, $canonical, $preload, $has_seo_plugin ) {
			if ( ! empty( $preload['src'] ) ) {
				printf(
					'<link rel="preload" as="image" href="%s" fetchpriority="high"%s%s />' . "\n",
					esc_url( $preload['src'] ),
					! empty( $preload['srcset'] ) ? ' imagesrcset="' . esc_attr( $preload['srcset'] ) . '"' : '',
					! empty( $preload['sizes'] ) ? ' imagesizes="' . esc_attr( $preload['sizes'] ) . '"' : ''
				);
			}
			if ( $has_seo_plugin ) {
				return;
			}
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $seo['description'] ) );
			printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $index ? $canonical : get_permalink() ) );
			printf( '<meta property="og:type" content="product" />' . "\n" );
			printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $seo['title'] ) );
			printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $seo['description'] ) );
			printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( get_permalink() ) );
			if ( ! empty( $seo['image'] ) ) {
				printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $seo['image'] ) );
			}
		},
		3
	);
}

/* -------------------------------------------------------------------------
 * Assets — this template only
 * ---------------------------------------------------------------------- */

add_filter( 'safestore_wa_render', static function ( $render ) {
	return safestore_pl_is_landing() ? false : $render;
} );
add_filter( 'safestore_mnav_enabled', static function ( $enabled ) {
	return safestore_pl_is_landing() ? false : $enabled;
} );
add_filter( 'body_class', static function ( $classes ) {
	if ( safestore_pl_is_landing() ) {
		$classes[] = 'lp-page';
		$classes[] = 'spl-page';
	}
	return $classes;
} );
add_filter( 'language_attributes', static function ( $output ) {
	return safestore_pl_is_landing() ? 'lang="bn-BD"' : $output;
} );

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! safestore_pl_is_landing() ) {
			return;
		}
		foreach ( array( 'safestore-minimal-footer', 'safestore-cart-toast', 'safestore-copy-to-clipboard', 'safestore-product-compare' ) as $handle ) {
			wp_dequeue_style( $handle );
		}
		foreach ( array( 'safestore-minimal-header-search-cat', 'safestore-minimal-footer-accordion', 'safestore-minimal-shop-cards', 'safestore-cart-toast', 'safestore-copy-to-clipboard', 'safestore-contact-email-chooser', 'safestore-product-compare', 'wc-cart-fragments' ) as $handle ) {
			wp_dequeue_script( $handle );
		}

		if ( function_exists( 'safestore_page_css' ) ) {
			safestore_page_css( 'landing-common', 'landing-common.css' );
			safestore_page_css( 'product-landing', 'product-landing.css' );
		}

		$js = get_template_directory() . '/js/product-landing.js';
		if ( file_exists( $js ) ) {
			wp_enqueue_script(
				'safestore-product-landing',
				get_template_directory_uri() . '/js/product-landing.js',
				array(),
				(string) filemtime( $js ),
				function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true
			);
		}
	},
	100
);

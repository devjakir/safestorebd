<?php
/**
 * Category campaign landing page (page-templates/template-category-landing.php).
 *
 * One category (first campaign: Safety Vest) for Facebook / Instagram ad
 * traffic: a short "which one is for you?" guide, every product of the
 * category as a pick-and-quantity card, and ONE order form for all picked
 * products — the order is placed on the page (COD or bKash / Nagad), then the
 * thank-you page. Same order pipeline as the product landing
 * (safestore_pl_place_order), with several cart lines.
 *
 * - Settings per page (meta box, shown once the page uses this template):
 *   category, product list override, headline, sub-headline, badge, indexing.
 * - Per-product card copy (short title, tag, three points) and the guide live
 *   in safestore_cl_defaults(), keyed by product slug, filterable.
 * - Simple products are ordered on the page; a product that needs a size /
 *   colour pick links to its own page.
 * - Shared with every order landing: inc/landing-common.php.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/landing-common.php';

define( 'SAFESTORE_CL_TEMPLATE', 'page-templates/template-category-landing.php' );
define( 'SAFESTORE_CL_MAX_QTY', 99 );    // per product; teams order 10–50 at a time
define( 'SAFESTORE_CL_BULK_QTY', 10 );   // from this many pieces the bulk / logo-print offer shows

function safestore_cl_is_landing() {
	return ! is_admin() && is_page_template( SAFESTORE_CL_TEMPLATE );
}

add_filter(
	'safestore_lp_templates',
	static function ( $templates ) {
		$templates[ SAFESTORE_CL_TEMPLATE ] = 'scl-page';
		return $templates;
	}
);

/* -------------------------------------------------------------------------
 * Defaults (first campaign: Safety Vest). Page settings override the top ones.
 * Facts in the card copy come from each product's own description.
 * ---------------------------------------------------------------------- */

function safestore_cl_defaults() {
	return apply_filters(
		'safestore_cl_defaults',
		array(
			'category' => 'safety-vest',
			'headline' => 'Hi-Vis Reflective Safety Vests',
			'lede'     => 'দিনে ফ্লুরোসেন্ট রঙ, রাতে রিফ্লেক্টিভ টেপ — কাজের জায়গায় দূর থেকেই চোখে পড়বেন। পছন্দের ভেস্ট ও পরিমাণ বেছে এই পেজেই অর্ডার করুন, সারা বাংলাদেশে ক্যাশ অন ডেলিভারি।',
			'badge'    => '',
			'bullets'  => array(
				'ফ্লুরোসেন্ট লাইম ও অরেঞ্জ রঙ — দিনের আলোয় অনেক দূর থেকে চোখে পড়ে',
				'সিলভার রিফ্লেক্টিভ টেপ — রাতে গাড়ির হেডলাইটের আলো ফিরিয়ে দেয়',
				'হালকা নেট / মেশ কাপড় — বাংলাদেশের গরমেও সারাদিন পরা যায়',
				'শার্ট বা ইউনিফর্মের ওপর সহজে পরা-খোলা যায় — বেশিরভাগই ফ্রি সাইজ',
			),
			'uses'     => array( 'কনস্ট্রাকশন সাইট', 'রোড ও ট্রাফিক', 'ওয়্যারহাউস', 'গার্মেন্টস ও ফ্যাক্টরি', 'সিকিউরিটি ও নাইট ডিউটি', 'পার্কিং', 'ইভেন্ট ও ভলান্টিয়ার' ),
			// "Which one is for you?" — each need points at the cards that fit it.
			'guide'    => array(
				array(
					'icon'  => 'hardhat',
					'title' => 'গরমে সারাদিন পরবেন',
					'text'  => 'নেট / মেশ কাপড়ে বাতাস চলে — কনস্ট্রাকশন, রোড ওয়ার্ক, ফ্যাক্টরি।',
					'slugs' => array( 'zipper-safety-vest', 'safety-vest-for-male', 'worker-reflective-vest' ),
				),
				array(
					'icon'  => 'list',
					'title' => 'টুলস, ফোন, আইডি সাথে রাখবেন',
					'text'  => 'পকেটওয়ালা ভেস্ট — সুপারভাইজার, ইঞ্জিনিয়ার, ওয়্যারহাউস টিম।',
					'slugs' => array( 'multi-pocket-safety-vest-bangladesh', 'reflective-work-vest' ),
				),
				array(
					'icon'  => 'truck',
					'title' => 'রাস্তা, ট্রাফিক বা রাতের ডিউটি',
					'text'  => 'অরেঞ্জ রঙ ও ৩৬০° রিফ্লেক্টিভ টেপ — হেডলাইটে দূর থেকে দেখা যায়।',
					'slugs' => array( 'securex-reflective-safety-vest-orange', 'worker-reflective-vest' ),
				),
			),
			// Card copy by product slug: short English title (like prices / form), Bangla points.
			'items'    => array(
				'zipper-safety-vest'                    => array(
					'title'  => 'Zipper Net Safety Vest',
					'points' => array( 'নেট কাপড় — গরমেও আরাম', 'সামনে চেইন, কাঁধ ও শরীরজুড়ে রিফ্লেক্টিভ টেপ', 'ফ্রি সাইজ · লাইম গ্রিন' ),
				),
				'safety-vest-for-male'                  => array(
					'title'  => 'Lime Mesh Safety Vest',
					'points' => array( 'দুটি চওড়া সিলভার রিফ্লেক্টিভ স্ট্রিপ', 'বাতাস চলাচলের মেশ কাপড় — হালকা', 'ফ্রি সাইজ · লাইম গ্রিন' ),
				),
				'worker-reflective-vest'                => array(
					'title'  => 'Hi-Vis Safety Vest — 360° Tape',
					'points' => array( 'সামনে-পেছনে ৩৬০° রিফ্লেক্টিভ টেপ', 'মেশ কাপড় · সামনে চেইন', 'পকেটসহ' ),
				),
				'multi-pocket-safety-vest-bangladesh'   => array(
					'title'  => 'Multi-Pocket Safety Vest (4 pockets)',
					'tag'    => '৪ পকেট',
					'points' => array( '৪টি পকেট — টুলস, ফোন, কলম, আইডি কার্ড', 'হালকা পলিয়েস্টার · সামনে চেইন', 'সাইজ M–XXL — কনফার্মেশন কলে জেনে নেব' ),
				),
				'securex-reflective-safety-vest-orange' => array(
					'title'  => 'Securex Orange Safety Vest',
					'tag'    => 'অরেঞ্জ',
					'points' => array( 'ফ্লুরোসেন্ট অরেঞ্জ — রাস্তায় দূর থেকে চোখে পড়ে', 'নিটেড কটন কাপড় · ২টি রিফ্লেক্টিভ ব্যান্ড', 'সামনে চেইন · সাইজ M–XXL — কলে জেনে নেব' ),
				),
				'reflective-work-vest'                  => array(
					'title'  => 'Premium Reflective Work Vest',
					'tag'    => 'সুপারভাইজারদের জন্য',
					'points' => array( 'মোটা সলিড কাপড় — দিনে আরও বেশি চোখে পড়ে', 'চেইন, ফ্ল্যাপ পকেট ও আইডি কার্ড পকেট', 'ওয়াকি-টকি মাইক ট্যাব · ফ্রি সাইজ' ),
				),
			),
		)
	);
}

/** Page settings: meta → defaults. */
function safestore_cl_settings( $page_id ) {
	$d   = safestore_cl_defaults();
	$get = static function ( $key ) use ( $page_id ) {
		return trim( (string) get_post_meta( $page_id, $key, true ) );
	};
	return array_merge(
		$d,
		array(
			'category' => '' !== $get( '_scl_category' ) ? $get( '_scl_category' ) : $d['category'],
			'products' => safestore_lp_lines( $get( '_scl_products' ) ),
			'headline' => '' !== $get( '_scl_headline' ) ? $get( '_scl_headline' ) : $d['headline'],
			'lede'     => '' !== $get( '_scl_lede' ) ? $get( '_scl_lede' ) : $d['lede'],
			'badge'    => '' !== $get( '_scl_badge' ) ? $get( '_scl_badge' ) : $d['badge'],
			'index'    => '1' === $get( '_scl_index' ),
		)
	);
}

/** product_cat term from a slug or ID, or null. */
function safestore_cl_term( $ref ) {
	$ref  = trim( (string) $ref );
	$term = ctype_digit( $ref ) ? get_term( (int) $ref, 'product_cat' ) : get_term_by( 'slug', sanitize_title( $ref ), 'product_cat' );
	return $term instanceof WP_Term ? $term : null;
}

/**
 * Products on the page: the page's own list (in that order), else the
 * category's visible products — in stock first, best sellers first, then cheapest.
 *
 * @return WC_Product[]
 */
function safestore_cl_products( $settings ) {
	$list = array();
	if ( $settings['products'] ) {
		foreach ( $settings['products'] as $ref ) {
			$p = safestore_pl_find_product( $ref );
			if ( $p && $p->is_visible() ) {
				$list[ $p->get_id() ] = $p;
			}
		}
		return array_values( $list );
	}
	$term = safestore_cl_term( $settings['category'] );
	if ( ! $term || ! function_exists( 'wc_get_products' ) ) {
		return array();
	}
	$list = wc_get_products(
		array(
			'status'     => 'publish',
			'visibility' => 'catalog',
			'category'   => array( $term->slug ),
			'limit'      => 24,
		)
	);
	usort(
		$list,
		static function ( $a, $b ) {
			return array( (int) ! $a->is_in_stock(), -(int) $a->get_total_sales(), (float) $a->get_price() )
				<=> array( (int) ! $b->is_in_stock(), -(int) $b->get_total_sales(), (float) $b->get_price() );
		}
	);
	return $list;
}

/**
 * Card data for each product. Tags: real best seller, cheapest, else the copy's own.
 *
 * @param WC_Product[] $products
 * @param array        $copy     safestore_cl_defaults()['items']
 */
function safestore_cl_items( $products, $copy ) {
	$best  = 0;
	$cheap = 0;
	$top   = 0;
	$low   = PHP_FLOAT_MAX;
	foreach ( $products as $p ) {
		if ( (int) $p->get_total_sales() > $top ) {
			$top  = (int) $p->get_total_sales();
			$best = $p->get_id();
		}
		$price = safestore_pl_price( $p )['now'];
		if ( $price > 0 && $price < $low && $p->is_in_stock() ) {
			$low   = $price;
			$cheap = $p->get_id();
		}
	}

	$items = array();
	foreach ( $products as $p ) {
		$slug   = $p->get_slug();
		$c      = isset( $copy[ $slug ] ) ? (array) $copy[ $slug ] : array();
		$simple = ! $p->is_type( 'variable' ) && ! $p->is_type( 'grouped' ) && ! $p->is_type( 'external' );
		$max    = $p->get_max_purchase_quantity() > 0 ? min( SAFESTORE_CL_MAX_QTY, $p->get_max_purchase_quantity() ) : SAFESTORE_CL_MAX_QTY;
		$tag    = isset( $c['tag'] ) ? $c['tag'] : '';
		if ( count( $products ) > 1 && $p->get_id() === $best ) {
			$tag = 'সবচেয়ে বেশি বিক্রি';
		} elseif ( count( $products ) > 1 && $p->get_id() === $cheap ) {
			$tag = 'সবচেয়ে কম দামে';
		}
		$items[] = array(
			'id'      => $p->get_id(),
			'slug'    => $slug,
			'anchor'  => 'v-' . $slug,
			'sku'     => (string) $p->get_sku(),
			'name'    => $p->get_name(),
			'title'   => ! empty( $c['title'] ) ? $c['title'] : $p->get_name(),
			'tag'     => $tag,
			'points'  => ! empty( $c['points'] ) ? array_slice( (array) $c['points'], 0, 3 ) : safestore_pl_bullets( $p, array() ),
			'price'   => safestore_pl_price( $p ),
			'url'     => $p->get_permalink(),
			'image'   => (int) $p->get_image_id(),
			'kg'      => safestore_lp_product_kg( $p ),
			'max'     => $max,
			'in_page' => $simple, // ordered here; others link to their page
			'can_buy' => $simple && $p->is_purchasable() && $p->is_in_stock(),
		);
	}
	return $items;
}

/** Guide cards that point at products on this page (missing ones dropped). */
function safestore_cl_guide( $guide, $items ) {
	$by_slug = array();
	foreach ( $items as $it ) {
		$by_slug[ $it['slug'] ] = $it;
	}
	$out = array();
	foreach ( (array) $guide as $g ) {
		$picks = array();
		foreach ( (array) $g['slugs'] as $slug ) {
			if ( isset( $by_slug[ $slug ] ) ) {
				$picks[] = $by_slug[ $slug ];
			}
		}
		if ( $picks ) {
			$g['picks'] = $picks;
			$out[]      = $g;
		}
	}
	return $out;
}

/** Latest reviews across the products + the combined rating (real reviews only). */
function safestore_cl_reviews( $products, $limit = 3 ) {
	$reviews = array();
	$count   = 0;
	$sum     = 0.0;
	foreach ( $products as $p ) {
		$n = (int) $p->get_review_count();
		if ( $n < 1 ) {
			continue;
		}
		$count  += $n;
		$sum    += $n * (float) $p->get_average_rating();
		$reviews = array_merge( $reviews, safestore_pl_reviews( $p, $limit ) );
	}
	return array(
		'items'  => array_slice( $reviews, 0, $limit ),
		'count'  => $count,
		'rating' => $count ? $sum / $count : 0.0,
	);
}

function safestore_cl_faq() {
	$common = safestore_lp_faq_common();
	return apply_filters(
		'safestore_cl_faq',
		array(
			$common['cod'],
			array(
				'q' => 'কোন সাইজ নেব?',
				'a' => 'বেশিরভাগ ভেস্ট ফ্রি সাইজ — শার্ট বা ইউনিফর্মের ওপর বেশিরভাগ মানুষের গায়ে লাগে। যেগুলোর M–XXL সাইজ আছে, অর্ডার কনফার্ম করার কলে আমরা সাইজ জেনে নেব। না মিললে ডেলিভারির ৭ দিনের মধ্যে অব্যবহৃত অবস্থায় বদলে নেওয়া যায়।',
			),
			array(
				'q' => 'একসাথে অনেকগুলো লাগবে — কোম্পানির লোগো প্রিন্ট করা যায়?',
				'a' => 'হ্যাঁ। এই পেজ থেকেই একসাথে যত পিস লাগে অর্ডার করতে পারেন। ভেস্টে কোম্পানির নাম বা লোগো প্রিন্ট করাতে চাইলে WhatsApp-এ লোগো, পরিমাণ ও রঙ পাঠান — দাম, সর্বনিম্ন পরিমাণ ও ডেলিভারির সময় জানিয়ে কোটেশন দেব; ডিজাইন অনুমোদনের পর প্রিন্ট শুরু হয়।',
			),
			$common['delivery'],
			array(
				'q' => 'ভেস্ট কীভাবে ধোব? রিফ্লেক্টিভ টেপ নষ্ট হবে?',
				'a' => 'ঠান্ডা বা হালকা গরম পানিতে হাতে ধুয়ে ছায়ায় শুকান। ব্লিচ দেবেন না, আর রিফ্লেক্টিভ টেপের ওপর সরাসরি ইস্ত্রি করবেন না — তাহলে টেপ অনেকদিন ভালো থাকে।',
			),
			$common['new'],
			array(
				'q' => 'কীভাবে অর্ডার করব?',
				'a' => 'যে ভেস্ট লাগবে তাতে "যোগ করুন" চাপুন ও পরিমাণ ঠিক করুন — একাধিক ভেস্ট একসাথে নেওয়া যায়। তারপর নিচের ফর্মে নাম, মোবাইল নম্বর ও ঠিকানা দিয়ে "Confirm order" চাপুন। চাইলে WhatsApp-এ ভেস্টের নাম, পরিমাণ ও ঠিকানা পাঠিয়েও অর্ডার করতে পারেন।',
			),
		)
	);
}

/** WhatsApp order text; the page script rewrites it with the picked vests. */
function safestore_cl_wa_text( $category_name ) {
	return "আসসালামু আলাইকুম SafeStoreBD, আমি অর্ডার করতে চাই ({$category_name}):\n{items}\nনাম: \nঠিকানা: \nফোন: ";
}

/* -------------------------------------------------------------------------
 * Order: POST scl_qty[<product id>] = quantity → one order with all lines
 * ---------------------------------------------------------------------- */

/**
 * Cart lines for the posted quantities; only products shown on this page, simple, qty ≥ 1.
 *
 * @return array|string list of lines, or an error code (pick | product)
 */
function safestore_cl_resolve_lines( $page_id, $raw ) {
	$allowed = array();
	foreach ( safestore_cl_products( safestore_cl_settings( $page_id ) ) as $p ) {
		$allowed[ $p->get_id() ] = $p;
	}
	$lines = array();
	foreach ( (array) $raw as $id => $qty ) {
		$id  = absint( $id );
		$qty = absint( $qty );
		if ( $qty < 1 ) {
			continue;
		}
		$p = isset( $allowed[ $id ] ) ? $allowed[ $id ] : null;
		if ( ! $p || $p->is_type( 'variable' ) || ! $p->is_purchasable() ) {
			return 'product';
		}
		$max     = $p->get_max_purchase_quantity() > 0 ? min( SAFESTORE_CL_MAX_QTY, $p->get_max_purchase_quantity() ) : SAFESTORE_CL_MAX_QTY;
		$lines[] = array(
			'product_id'   => $id,
			'variation_id' => 0,
			'attrs'        => array(),
			'qty'          => min( $max, $qty ),
		);
	}
	return $lines ? $lines : 'pick';
}

add_action(
	'template_redirect',
	static function () {
		if ( ! safestore_cl_is_landing() || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification -- public order form, see inc/product-landing-order.php
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['scl_qty'] ) ) {
			return;
		}
		$page_id = (int) get_queried_object_id();
		$ajax    = ! empty( $_POST['spl_ajax'] );
		$lines   = safestore_cl_resolve_lines( $page_id, is_array( $_POST['scl_qty'] ) ? wp_unslash( $_POST['scl_qty'] ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- absint per value
		// phpcs:enable
		$fail = safestore_lp_fail( $page_id, $ajax );
		if ( ! is_array( $lines ) ) {
			$fail( $lines );
		}
		safestore_pl_place_order( $page_id, $lines, $ajax, $fail ); // exits
	},
	5
);

/* -------------------------------------------------------------------------
 * Page settings meta box (pages using this template)
 * ---------------------------------------------------------------------- */

function safestore_cl_fields() {
	return array(
		'_scl_category' => array( 'text', __( 'Category', 'safestore-minimal' ), __( 'Product category slug or ID. Empty = safety-vest.', 'safestore-minimal' ) ),
		'_scl_products' => array( 'textarea', __( 'Products (optional)', 'safestore-minimal' ), __( 'One product URL, slug or ID per line, in the order to show. Empty = every product of the category, best sellers first.', 'safestore-minimal' ) ),
		'_scl_headline' => array( 'text', __( 'Headline (H1)', 'safestore-minimal' ), __( 'Empty = "Hi-Vis Reflective Safety Vests".', 'safestore-minimal' ) ),
		'_scl_lede'     => array( 'textarea', __( 'Sub-headline', 'safestore-minimal' ), __( 'One or two short Bangla sentences under the headline. Empty = default copy.', 'safestore-minimal' ) ),
		'_scl_badge'    => array( 'text', __( 'Badge on the photos', 'safestore-minimal' ), __( 'Optional, e.g. "ক্যাম্পেইন অফার". Empty = the biggest discount, if any.', 'safestore-minimal' ) ),
		'_scl_index'    => array( 'checkbox', __( 'Search engines', 'safestore-minimal' ), __( 'Off (recommended for ad campaigns): noindex. On: indexed, canonical points at the category page.', 'safestore-minimal' ), __( 'Allow Google to index this page', 'safestore-minimal' ) ),
	);
}

add_action(
	'add_meta_boxes_page',
	static function ( $post ) {
		if ( SAFESTORE_CL_TEMPLATE === get_page_template_slug( $post ) ) {
			add_meta_box( 'scl-settings', __( 'Category Landing (Facebook) — page settings', 'safestore-minimal' ), 'safestore_cl_render_box', 'page', 'normal', 'high' );
		}
	}
);

function safestore_cl_render_box( $post ) {
	wp_nonce_field( 'scl_save_' . $post->ID, 'scl_nonce' );
	echo '<p class="description">' . esc_html__( 'Card copy per product and the "which vest" guide: safestore_cl_defaults() in inc/category-landing.php.', 'safestore-minimal' ) . '</p>';
	safestore_lp_meta_table( $post, safestore_cl_fields() );
}

add_action(
	'save_post_page',
	static function ( $post_id ) {
		if ( ! isset( $_POST['scl_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['scl_nonce'] ) ), 'scl_save_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
		safestore_lp_meta_save( $post_id, safestore_cl_fields() );
	}
);

/* -------------------------------------------------------------------------
 * This template's own assets (shared chrome + order core: inc/landing-common.php)
 * ---------------------------------------------------------------------- */

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( safestore_cl_is_landing() ) {
			// product-landing.css holds the shared order block (.spl-order / -ship / -sum / -pay / -mfs, trust, why, delivery, reviews).
			safestore_lp_enqueue( array( 'product-landing', 'category-landing' ), 'category-landing' );
		}
	},
	100
);

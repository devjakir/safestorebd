<?php
/**
 * Template Name: Bulk Landing
 *
 * Focused bulk / corporate PPE quote page (suggested slug: /bulk-safety-gear/).
 * Minimal header + footer on purpose — no mega menu, no category bar.
 * Logic (lead post type, submit handler, assets) lives in inc/bulk-landing.php.
 * Styling: css/landing-common.css (shared .lp-* blocks) + css/bulk-landing.css.
 *
 * Facts on this page mirror the live site (home trust bar, /bulk-orders/,
 * /shipping-delivery/, /return-refund-policy/). If one of those changes,
 * change it here too. Everything editable is in $bl below — search "TODO".
 */

defined( 'ABSPATH' ) || exit;

$sbl_img = get_template_directory_uri() . '/assets/images/';

/* =========================================================================
 * EDIT HERE
 * ====================================================================== */
$bl = array(

	'seo'        => array(
		'title'       => 'বাল্ক সেফটি গিয়ার অর্ডার — ফ্যাক্টরি ও কনস্ট্রাকশনের PPE | SafeStoreBD',
		'description' => 'হেলমেট, সেফটি শু, গ্লাভস, গগলস ও হাই-ভিস ভেস্ট বাল্ক দামে। ২৪ ঘণ্টায় লিখিত কোটেশন, VAT ইনভয়েস (মূসক ৬.৩), ৬৪ জেলায় ডেলিভারি।',
		'image'       => $sbl_img . 'sf-helmet-category.webp', // TODO: 1200×630 share image.
	),

	// WhatsApp message the visitor starts with (URL-encoded automatically).
	'wa_text'    => "আসসালামু আলাইকুম SafeStoreBD, আমাদের প্রতিষ্ঠানের জন্য বাল্ক সেফটি গিয়ারের কোটেশন চাই।\nকোম্পানি: \nকী কী লাগবে ও কত পিস: \nডেলিভারি জেলা: ",
	'wa_after'   => 'আসসালামু আলাইকুম, আমি এইমাত্র ওয়েবসাইটে বাল্ক কোটেশন ফর্ম পাঠিয়েছি।',

	'hero'       => array(
		'eyebrow' => 'বাল্ক ও কর্পোরেট অর্ডার',
		'title'   => 'আপনার পুরো টিমের সেফটি গিয়ার — এক জায়গায়, বাল্ক দামে',
		'lede'    => 'ফ্যাক্টরি, কনস্ট্রাকশন ও ওয়্যারহাউসের জন্য হেলমেট, সেফটি শু, গ্লাভস, গগলস ও ভেস্ট। ৬৪ জেলায় ডেলিভারি।',
		'note'    => '২৪ ঘণ্টার মধ্যে লিখিত কোটেশন (শনি–বৃহস্পতি) · কোনো বাধ্যবাধকতা নেই',
		// TODO: replace with a real photo of a full worker kit (WebP, ~1200×800, 3:2).
		'image'   => array(
			'src'    => $sbl_img . 'sf-helmet-category-720w.webp',
			'srcset' => $sbl_img . 'sf-helmet-category-480w.webp 480w, ' . $sbl_img . 'sf-helmet-category-720w.webp 720w, ' . $sbl_img . 'sf-helmet-category.webp 1248w',
			'w'      => 720,
			'h'      => 480,
			'alt'    => 'সেফটি হেলমেট ও কর্মীদের সুরক্ষা সরঞ্জাম — Safety helmets and worker PPE',
		),
	),

	// Same claims as the home page trust bar.
	'trust'      => array(
		array( 'icon' => 'truck', 'text' => '১০,০০০+ অর্ডার ডেলিভারড' ),
		array( 'icon' => 'pin', 'text' => '৬৪ জেলায় ডেলিভারি' ),
		array( 'icon' => 'card', 'text' => 'COD · bKash · Nagad' ),
		array( 'icon' => 'return', 'text' => '৭ দিনের রিটার্ন' ),
	),

	// "cat" = key used by inc/category-links.php (helmet, shoe, glove, goggle, vest).
	'audiences'  => array(
		array( 'icon' => 'factory', 'title' => 'গার্মেন্টস ও ফ্যাক্টরি', 'gear' => 'গ্লাভস, গগলস, সেফটি শু', 'cat' => 'glove' ),
		array( 'icon' => 'hardhat', 'title' => 'কনস্ট্রাকশন', 'gear' => 'হেলমেট, ভেস্ট, স্টিল-টো শু', 'cat' => 'helmet' ),
		array( 'icon' => 'warehouse', 'title' => 'ওয়্যারহাউস ও লজিস্টিকস', 'gear' => 'হাই-ভিস ভেস্ট, অ্যান্টি-স্লিপ শু', 'cat' => 'vest' ),
		array( 'icon' => 'bolt', 'title' => 'ইলেকট্রিক্যাল ও পাওয়ার', 'gear' => 'ইনসুলেটেড গ্লাভস, ইলেকট্রিক্যাল সেফটি শু', 'cat' => 'shoe' ),
	),

	// Kits are built from real shop products ('products' = product slugs; the
	// cheapest in-stock one is used), so the retail price is always the live one.
	// 'price' = your BULK per-person price in BDT (e.g. 3600). Set it to show
	// that instead of the retail total.
	'kits'       => array(
		'worker-starter' => array(
			'name'    => 'Worker Starter Kit',
			'for'     => 'ফ্যাক্টরি ও সাইটের প্রতিদিনের কাজের জন্য',
			'items'   => array(
				array( 'label' => 'সেফটি হেলমেট', 'products' => array( 'vaultex-safety-helmet', 'udyogi-safety-helmet-yellow', 'super-safe-guard-safety-helmet' ) ),
				array( 'label' => 'হাই-ভিস সেফটি ভেস্ট', 'products' => array( 'zipper-safety-vest', 'worker-reflective-vest', 'multi-pocket-safety-vest-bangladesh' ) ),
				array( 'label' => 'ওয়ার্ক গ্লাভস', 'products' => array( 'cut-resistant-gloves-bangladesh' ) ),
				array( 'label' => 'স্টিল-টো সেফটি শু', 'products' => array( 'solex-super-safety-shoes-bd', 'runner-ss4-steel-toe-safety-shoes', 'haojia-safety-shoes-price-in-bangladesh' ) ),
			),
			'price'   => '', // TODO: bulk per-person price.
			'feature' => true,
		),
		'site-visitor'   => array(
			'name'    => 'Site Visitor Kit',
			'for'     => 'অতিথি, অডিটর ও সুপারভাইজারদের জন্য',
			'items'   => array(
				array( 'label' => 'সেফটি হেলমেট', 'products' => array( 'vaultex-safety-helmet', 'udyogi-safety-helmet-yellow', 'super-safe-guard-safety-helmet' ) ),
				array( 'label' => 'হাই-ভিস সেফটি ভেস্ট', 'products' => array( 'zipper-safety-vest', 'worker-reflective-vest' ) ),
				array( 'label' => 'সেফটি গগলস', 'products' => array( '3m-safety-goggles', '3m-virtua-protective-eyewear', '3m-nuvo-protective-eyewear' ) ),
			),
			'price'   => '', // TODO: bulk per-person price.
			'feature' => false,
		),
		'electrical'     => array(
			'name'    => 'Electrical Kit',
			'for'     => 'ইলেকট্রিশিয়ান ও পাওয়ার লাইনের কাজের জন্য',
			'items'   => array(
				array( 'label' => 'ইনসুলেটেড গ্লাভস (Kavach 1500V)', 'products' => array( 'kavach-electrical-safety-hand-gloves-1500v' ) ),
				array( 'label' => 'ইলেকট্রিক্যাল সেফটি শু (GAT)', 'products' => array( 'gat-safety-shoes' ) ),
				array( 'label' => 'সেফটি হেলমেট', 'products' => array( 'vaultex-safety-helmet', 'udyogi-safety-helmet-yellow' ) ),
			),
			'price'   => '', // TODO: bulk per-person price.
			'feature' => false,
		),
	),

	// Live /bulk-orders/ promises volume pricing on 50 and 100+ pieces.
	// discount: e.g. '৫%'. Leave '' to show "on quote".
	'tiers'      => array(
		array( 'qty' => '১০+ পিস', 'who' => 'ছোট টিম', 'discount' => '' ),   // TODO
		array( 'qty' => '৫০+ পিস', 'who' => 'একটি ফ্লোর বা সাইট', 'discount' => '' ),   // TODO
		array( 'qty' => '১০০+ পিস', 'who' => 'পুরো ফ্যাক্টরি', 'discount' => '' ),   // TODO
	),

	// Product categories, pulled live from WooCommerce. Image is the fallback
	// when the category has no thumbnail of its own.
	'categories' => array(
		'helmet' => array( 'label' => 'সেফটি হেলমেট', 'image' => 'sf-helmet-category-480w.webp', 'w' => 480, 'h' => 320 ),
		'shoe'   => array( 'label' => 'সেফটি শু', 'image' => 'sf-category-shoe-480w.webp', 'w' => 480, 'h' => 322 ),
		'glove'  => array( 'label' => 'সেফটি গ্লাভস', 'image' => 'sf-safety-gloves.webp', 'w' => 800, 'h' => 537 ),
		'goggle' => array( 'label' => 'সেফটি গগলস', 'image' => 'sf-safety-glasses.webp', 'w' => 1264, 'h' => 848 ),
		'vest'   => array( 'label' => 'সেফটি ভেস্ট', 'image' => 'sf-safety-vest-480w.webp', 'w' => 480, 'h' => 322 ),
	),

	// "Every bulk order includes" — wording follows the live /bulk-orders/ page.
	'includes'   => array(
		array( 'icon' => 'invoice', 'title' => 'VAT ইনভয়েস (মূসক ৬.৩)', 'text' => 'কোম্পানির নামে, প্রতিটি আইটেমের দাম ও পরিমাণসহ — অডিট ফাইলে রাখার মতো।' ),
		array( 'icon' => 'packing', 'title' => 'ডেলিভারি চালান ও প্যাকিং লিস্ট', 'text' => 'সাইজ ও পরিমাণ মিলিয়ে নেওয়ার জন্য প্রতিটি চালানের সাথে।' ),
		array( 'icon' => 'spec', 'title' => 'প্রোডাক্ট স্পেক শিট', 'text' => 'ম্যাটেরিয়াল, সাইজ ও ব্যবহারের তথ্য — বায়ার অডিটের জন্য।' ), // TODO: confirm you can send one per product.
		array( 'icon' => 'tag', 'title' => 'কোম্পানির লোগো প্রিন্ট', 'text' => 'ভেস্টে লোগো ও নাম, হেলমেটে লোগো বা লেখা — ডিজাইন অনুমোদনের পর প্রিন্ট।' ),
		array( 'icon' => 'ruler', 'title' => 'সাইজ মিলিয়ে এক অর্ডারে', 'text' => 'জুতা ও গ্লাভসের আলাদা আলাদা সাইজ একই অর্ডারে নিতে পারবেন।' ),
		array( 'icon' => 'user', 'title' => 'একজন নির্দিষ্ট সেলস প্রতিনিধি', 'text' => 'কোটেশন থেকে ডেলিভারি পর্যন্ত একজনই আপনার অর্ডার দেখবেন।' ),
	),
	// TODO: list ONLY certifications you can show a document for, e.g.
	// array( 'label' => 'EN ISO 20345 (safety shoes — brand X model Y)' ). Empty = hidden.
	'certs'      => array(),

	'steps'      => array(
		array( 'title' => 'আপনার লিস্ট পাঠান', 'text' => 'ফর্ম, WhatsApp বা রিকুইজিশনের ছবি — যেভাবে সুবিধা।' ),
		array( 'title' => '২৪ ঘণ্টায় কোটেশন পান', 'text' => 'পিস প্রতি দাম, স্টক, ডেলিভারি চার্জ ও সময়সহ লিখিত কোটেশন — WhatsApp ও ইমেইলে।' ),
		array( 'title' => 'কনফার্ম করে ডেলিভারি নিন', 'text' => 'ব্যাংক ট্রান্সফার, bKash বা ক্যাশ অন ডেলিভারি — VAT ইনভয়েস ও চালানসহ।' ),
	),

	// What to write for a faster quote (shown beside the form).
	'quote_tips' => array(
		'কোন আইটেম, কত পিস',
		'জুতা ও গ্লাভসের সাইজ (জানা থাকলে)',
		'ডেলিভারির জেলা ও কবে লাগবে',
		'কাজের ধরন — যেমন ইলেকট্রিক্যাল, কেমিক্যাল, উঁচুতে কাজ',
		'লোগো প্রিন্ট লাগলে সেটাও',
	),

	// SOCIAL PROOF — the whole section stays hidden until logos or reviews are added.
	// TODO: only real clients who agreed to be shown.
	// array( 'src' => $sbl_img . 'clients/abc.webp', 'alt' => 'ABC Garments', 'w' => 160, 'h' => 64 )
	'logos'      => array(),
	// TODO: real reviews only. array( 'text' => '…', 'name' => '…', 'role' => 'HSE Manager', 'company' => '…' )
	'reviews'    => array(),
	// TODO: delivery photos. array( 'src' => …, 'alt' => '…', 'w' => 600, 'h' => 450 )
	'gallery'    => array(),

	// Answers follow /shipping-delivery/, /return-refund-policy/ and /bulk-orders/.
	'faq'        => array(
		array(
			'q' => 'ইনভয়েস দেন কি?',
			'a' => 'হ্যাঁ। প্রতিটি বাল্ক অর্ডারে আপনার কোম্পানির নামে VAT ইনভয়েস (মূসক ৬.৩) ও ডেলিভারি চালান দেওয়া হয়।',
		),
		array(
			'q' => 'ডেলিভারি কত দিনে?',
			'a' => 'দুপুর ২টার আগে কনফার্ম হলে দ্রুত শিপিং। ঢাকার ভেতরে সাধারণত ২৪ ঘণ্টার মধ্যে ডিসপ্যাচ, ঢাকার বাইরে ডিসপ্যাচের পর ২–৩ কার্যদিবস। বড় অর্ডারে স্টক অনুযায়ী সময় কোটেশনে লেখা থাকে। পল্লবী অফিস থেকে ফ্রি পিকআপও নিতে পারেন।',
		),
		array(
			'q' => 'সাইজ না মিললে কী হবে?',
			'a' => 'ডেলিভারির ৭ দিনের মধ্যে অব্যবহৃত পণ্য অরিজিনাল প্যাকেটসহ ফেরত দিতে পারবেন — আগে আমাদের জানিয়ে অনুমোদন নিন।',
		),
		array(
			'q' => 'পেমেন্ট কীভাবে করব?',
			'a' => 'ব্যাংক ট্রান্সফার, bKash, Nagad অথবা ক্যাশ অন ডেলিভারি — কোটেশনে যেটা ঠিক হয়।',
		),
		array(
			'q' => 'ন্যূনতম কত পিস অর্ডার করতে হবে?',
			'a' => 'বাল্ক দাম শুরু ১০ পিস থেকে, ৫০ ও ১০০+ পিসে আরও ভালো দাম। বিভিন্ন আইটেম মিলিয়েও হতে পারে। এর কম লাগলে সরাসরি শপ থেকে অর্ডার করুন।', // TODO: confirm the 10-piece start.
		),
		array(
			'q' => 'কোম্পানির লোগো প্রিন্ট করা যায়?',
			'a' => 'হ্যাঁ — সেফটি ভেস্টে লোগো ও কোম্পানির নাম, হেলমেটে লোগো বা লেখা। পরিমাণ ও রঙ জানিয়ে লোগো পাঠান; দাম, ন্যূনতম পরিমাণ ও সময় জানিয়ে দেব, ডিজাইন অনুমোদনের পর প্রিন্ট।',
		),
	),

	'final'      => array(
		'title' => 'আপনার পুরো টিমের সেফটি গিয়ার — এক জায়গায়, বাল্ক দামে',
		'text'  => 'লিস্ট পাঠান — ২৪ ঘণ্টার মধ্যে লিখিত কোটেশন পাঠাচ্ছি।',
	),
);
/* ====================================================================== */

$sbl_tel       = function_exists( 'safestore_primary_phone_e164' ) ? safestore_primary_phone_e164() : '+8801811892291';
$sbl_tel_label = function_exists( 'safestore_primary_phone_display' ) ? safestore_primary_phone_display() : '+880 1811-892291';
$sbl_wa        = function_exists( 'safestore_primary_wa_link' ) ? safestore_primary_wa_link( $bl['wa_text'] ) : 'https://wa.me/8801811892291?text=' . rawurlencode( $bl['wa_text'] );
$sbl_wa_after  = function_exists( 'safestore_primary_wa_link' ) ? safestore_primary_wa_link( $bl['wa_after'] ) : $sbl_wa;
$sbl_wa_icon   = function_exists( 'safestore_wa_icon_svg' ) ? safestore_wa_icon_svg( 'lp-ico-wa' ) : '';
$sbl_address   = function_exists( 'safestore_minimal_get_pickup_address' ) ? safestore_minimal_get_pickup_address() : '17/5/1 Alabdirtek, Pallabi, Dhaka 1216, Bangladesh';
$sbl_map       = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( 'SafeStoreBD, ' . $sbl_address );
$sbl_emails    = function_exists( 'safestore_contact_email_addresses' ) ? safestore_contact_email_addresses() : array( 'contact@safestorebd.com' );
$sbl_email     = $sbl_emails ? reset( $sbl_emails ) : '';
$sbl_shop      = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$sbl_logo_dir  = get_template_directory_uri() . '/assets/images/logo/';
$sbl_hours     = 'শনি–বৃহস্পতি, সকাল ৯টা – রাত ৮টা';

$sbl_state  = safestore_bl_form_state();
$sbl_old    = $sbl_state['old'];
$sbl_errors = $sbl_state['errors'];
$sbl_done   = 'ok' === $sbl_state['status'];
$sbl_retail = safestore_bl_kit_retail( $bl['kits'] );

$sbl_kit_items_text = static function ( $kit ) {
	return implode( ' + ', wp_list_pluck( $kit['items'], 'label' ) );
};

// ?kit=worker-starter#quote pre-fills the form when JavaScript is off.
$sbl_kit_key = isset( $_GET['kit'] ) ? sanitize_key( wp_unslash( $_GET['kit'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
if ( ! isset( $sbl_old['items'] ) && isset( $bl['kits'][ $sbl_kit_key ] ) ) {
	$sbl_k            = $bl['kits'][ $sbl_kit_key ];
	$sbl_old['kit']   = $sbl_k['name'];
	$sbl_old['items'] = $sbl_k['name'] . ' (' . $sbl_kit_items_text( $sbl_k ) . ")\nমোট জন: ";
}
$sbl_val = static function ( $key ) use ( $sbl_old ) {
	return isset( $sbl_old[ $key ] ) ? (string) $sbl_old[ $key ] : '';
};

$sbl_show_proof = ! empty( $bl['logos'] ) || ! empty( $bl['reviews'] );

safestore_bl_seo( $bl['seo'], $bl['faq'] );

/** Error text + aria wiring for one field. */
$sbl_field_error = static function ( $key ) use ( $sbl_errors ) {
	$msg = isset( $sbl_errors[ $key ] ) ? $sbl_errors[ $key ] : '';
	printf(
		'<p class="lp-err" id="sbl-err-%1$s" data-sbl-error="%1$s"%2$s>%3$s</p>',
		esc_attr( $key ),
		'' === $msg ? ' hidden' : '',
		esc_html( $msg )
	);
};
$sbl_invalid = static function ( $key ) use ( $sbl_errors ) {
	return isset( $sbl_errors[ $key ] ) ? ' aria-invalid="true"' : '';
};
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="lp-skip" href="#sbl-main">মূল কনটেন্টে যান</a>

<header class="lp-header" role="banner">
	<div class="lp-wrap lp-header__in">
		<a class="lp-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — হোম">
			<picture>
				<source type="image/webp" srcset="<?php echo esc_url( $sbl_logo_dir . 'safe-store-bd.webp' ); ?>">
				<img src="<?php echo esc_url( $sbl_logo_dir . 'safe-store-bd.png' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="240" height="128" decoding="async">
			</picture>
		</a>
		<div class="lp-header__acts">
			<a class="lp-header__tel" href="<?php echo esc_url( 'tel:' . $sbl_tel ); ?>" data-sbl-loc="header" aria-label="<?php echo esc_attr( 'কল করুন: ' . $sbl_tel_label ); ?>">
				<?php echo safestore_bl_icon( 'phone', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="lp-header__num" aria-hidden="true"><?php echo esc_html( $sbl_tel_label ); ?></span>
			</a>
			<a class="lp-btn lp-btn--wa lp-btn--sm" href="<?php echo esc_url( $sbl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-sbl-loc="header">
				<?php echo $sbl_wa_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span>WhatsApp</span>
			</a>
		</div>
	</div>
</header>

<main id="sbl-main">

	<!-- 1. HERO ============================================================ -->
	<section class="sbl-hero" aria-labelledby="sbl-hero-title">
		<div class="lp-wrap sbl-hero__grid">
			<div>
				<p class="lp-eyebrow lp-eyebrow--light"><?php echo esc_html( $bl['hero']['eyebrow'] ); ?></p>
				<h1 class="sbl-hero__title" id="sbl-hero-title"><?php echo esc_html( $bl['hero']['title'] ); ?></h1>
				<p class="sbl-hero__lede"><?php echo esc_html( $bl['hero']['lede'] ); ?></p>
				<div class="lp-cta-row">
					<a class="lp-btn lp-btn--amber lp-btn--lg" href="#quote" data-sbl-goto="quote">
						ফ্রি কোটেশন নিন
						<?php echo safestore_bl_icon( 'arrow', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
					<a class="lp-btn lp-btn--wa lp-btn--lg" href="<?php echo esc_url( $sbl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-sbl-loc="hero">
						<?php echo $sbl_wa_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						WhatsApp-এ কথা বলুন
					</a>
				</div>
				<p class="lp-note"><?php echo esc_html( $bl['hero']['note'] ); ?></p>
			</div>
			<figure class="sbl-hero__media">
				<?php // TODO: hero photo — see $bl['hero']['image'] at the top of this file. ?>
				<img
					src="<?php echo esc_url( $bl['hero']['image']['src'] ); ?>"
					srcset="<?php echo esc_attr( $bl['hero']['image']['srcset'] ); ?>"
					sizes="(max-width: 899px) calc(100vw - 32px), 540px"
					width="<?php echo (int) $bl['hero']['image']['w']; ?>"
					height="<?php echo (int) $bl['hero']['image']['h']; ?>"
					alt="<?php echo esc_attr( $bl['hero']['image']['alt'] ); ?>"
					fetchpriority="high"
					decoding="async">
			</figure>
		</div>
		<div class="lp-wrap">
			<ul class="sbl-trust" aria-label="কেন SafeStoreBD">
				<?php foreach ( $bl['trust'] as $item ) : ?>
					<li><?php echo safestore_bl_icon( $item['icon'], 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $item['text'] ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<!-- 2. WHO IT'S FOR ===================================================== -->
	<section class="lp-section" aria-labelledby="sbl-who-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">কাদের জন্য</p>
				<h2 class="lp-h2" id="sbl-who-title">আপনার ইন্ডাস্ট্রির জন্য সঠিক গিয়ার</h2>
			</header>
			<ul class="lp-grid lp-grid--4">
				<?php foreach ( $bl['audiences'] as $aud ) : ?>
					<li>
						<a class="lp-card sbl-aud" href="<?php echo esc_url( function_exists( 'safestore_home_category_url' ) ? safestore_home_category_url( $aud['cat'] ) : $sbl_shop ); ?>">
							<span class="lp-ico"><?php echo safestore_bl_icon( $aud['icon'], 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span class="sbl-aud__body">
								<span class="lp-h3"><?php echo esc_html( $aud['title'] ); ?></span>
								<span class="sbl-aud__gear"><?php echo esc_html( $aud['gear'] ); ?></span>
							</span>
							<span class="sbl-aud__go"><?php echo safestore_bl_icon( 'arrow', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<!-- 3. READY-MADE KITS ================================================== -->
	<section class="lp-section lp-section--tint" id="kits" aria-labelledby="sbl-kits-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">রেডি-মেড কিট</p>
				<h2 class="lp-h2" id="sbl-kits-title">জনপ্রতি এক কিট — হিসাব সহজ</h2>
				<p class="lp-lede">কিটের আইটেম আমাদের শপের আসল প্রোডাক্ট, পাশে আজকের খুচরা দাম। সাইজ আলাদা আলাদা দিতে পারবেন, আইটেম বদলাতে চাইলে ফর্মে লিখে দিন।</p>
			</header>
			<ul class="lp-grid lp-grid--3">
				<?php foreach ( $bl['kits'] as $kit_key => $kit ) : ?>
					<?php
					$kit_bulk   = safestore_bl_taka( $kit['price'] );
					$kit_live   = isset( $sbl_retail[ $kit_key ] ) ? $sbl_retail[ $kit_key ] : array( 'total' => 0, 'items' => array() );
					$kit_retail = $kit_live['total'] > 0 ? safestore_bl_taka( ceil( $kit_live['total'] ) ) : '';
					?>
					<li>
						<article class="lp-card sbl-kit<?php echo $kit['feature'] ? ' sbl-kit--feature' : ''; ?>" aria-labelledby="sbl-kit-<?php echo esc_attr( $kit_key ); ?>">
							<h3 class="sbl-kit__name" id="sbl-kit-<?php echo esc_attr( $kit_key ); ?>" lang="en"><?php echo esc_html( $kit['name'] ); ?></h3>
							<p class="sbl-kit__for"><?php echo esc_html( $kit['for'] ); ?></p>
							<ul class="lp-checks">
								<?php foreach ( $kit['items'] as $i => $kit_item ) : ?>
									<?php $live = isset( $kit_live['items'][ $i ] ) ? $kit_live['items'][ $i ] : null; ?>
									<li>
										<?php echo safestore_bl_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<span>
											<?php if ( $live ) : ?>
												<a href="<?php echo esc_url( $live['url'] ); ?>"><?php echo esc_html( $kit_item['label'] ); ?></a>
											<?php else : ?>
												<?php echo esc_html( $kit_item['label'] ); ?>
											<?php endif; ?>
										</span>
										<?php if ( $live ) : ?>
											<span class="sbl-kit__item-price"><?php echo esc_html( safestore_bl_taka( ceil( $live['price'] ) ) ); ?></span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ul>
							<p class="sbl-kit__price">
								<?php if ( '' !== $kit_bulk ) : ?>
									বাল্ক দামে জনপ্রতি <strong><?php echo esc_html( $kit_bulk ); ?></strong>
								<?php elseif ( '' !== $kit_retail ) : ?>
									খুচরা দামে জনপ্রতি <strong><?php echo esc_html( $kit_retail ); ?></strong>
									<small>বাল্ক অর্ডারে আরও কম — কোটেশনে জানাব</small>
								<?php else : ?>
									জনপ্রতি দাম <strong>কোটেশনে জানাব</strong>
								<?php endif; ?>
							</p>
							<a class="lp-btn lp-btn--dark lp-btn--block"
								href="<?php echo esc_url( add_query_arg( 'kit', $kit_key, get_permalink() ) . '#quote' ); ?>"
								data-sbl-kit="<?php echo esc_attr( $kit['name'] ); ?>"
								data-sbl-kit-items="<?php echo esc_attr( $sbl_kit_items_text( $kit ) ); ?>">
								এই কিটের কোটেশন নিন
							</a>
						</article>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="sbl-tiers">
				<h3 class="lp-h3" id="sbl-tiers-title">যত বেশি পিস, তত কম দাম</h3>
				<table aria-labelledby="sbl-tiers-title">
					<thead>
						<tr><th scope="col">পরিমাণ</th><th scope="col">উপযোগী</th><th scope="col">ছাড়</th></tr>
					</thead>
					<tbody>
						<?php foreach ( $bl['tiers'] as $tier ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( $tier['qty'] ); ?></th>
								<td><?php echo esc_html( $tier['who'] ); ?></td>
								<td class="sbl-tiers__off"><?php echo esc_html( '' !== $tier['discount'] ? $tier['discount'] : 'কোটেশনে জানাব' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</section>

	<!-- 4. CATEGORY GRID ==================================================== -->
	<section class="lp-section" aria-labelledby="sbl-cats-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">ক্যাটাগরি</p>
				<h2 class="lp-h2" id="sbl-cats-title">আলাদা আলাদা আইটেমও নিতে পারেন</h2>
			</header>
			<ul class="lp-grid sbl-cats">
				<?php foreach ( safestore_bl_category_cards( $bl['categories'] ) as $cat ) : ?>
					<li>
						<a class="lp-card sbl-cat" href="<?php echo esc_url( $cat['url'] ); ?>">
							<span class="sbl-cat__img"><?php echo $cat['image']; // phpcs:ignore WordPress.Security.EscapeOutput -- built with esc_* / wp_get_attachment_image ?></span>
							<span class="sbl-cat__name"><?php echo esc_html( $cat['name'] ); ?></span>
							<?php if ( '' !== $cat['term'] ) : ?>
								<span class="sbl-cat__en" lang="en"><?php echo esc_html( $cat['term'] ); ?></span>
							<?php endif; ?>
							<span class="sbl-cat__price"><?php echo esc_html( '' !== $cat['price'] ? $cat['price'] . ' থেকে শুরু' : 'দাম দেখুন' ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<!-- 5. SAMPLE FIRST ===================================================== -->
	<section class="lp-section lp-section--tint" aria-labelledby="sbl-sample-title">
		<div class="lp-wrap sbl-sample">
			<div>
				<p class="lp-eyebrow">ঝুঁকি ছাড়া অর্ডার</p>
				<h2 class="lp-h2" id="sbl-sample-title">আগে স্যাম্পল, তারপর বাল্ক</h2>
				<p>বড় অর্ডারের আগে ২–৩টি স্যাম্পল নিয়ে দেখুন, অথবা আমাদের পল্লবী অফিসে এসে নিজে হাতে পণ্য যাচাই করুন। মান ও সাইজ মিলিয়ে তারপর পুরো অর্ডার।</p>
				<?php // TODO: are samples free or paid / adjusted against the bulk order? Add one line here once decided. ?>
				<div class="lp-cta-row">
					<a class="lp-btn lp-btn--amber" href="#quote" data-sbl-goto="quote" data-sbl-prefill="স্যাম্পল চাই: ">স্যাম্পল চাই</a>
					<a class="lp-btn lp-btn--outline" href="<?php echo esc_url( $sbl_map ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo safestore_bl_icon( 'pin', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						ম্যাপে দেখুন
					</a>
				</div>
			</div>
			<address class="lp-card sbl-office">
				<span class="lp-ico"><?php echo safestore_bl_icon( 'pin', 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<strong class="lp-h3">অফিস ও ফ্রি পিকআপ</strong>
				<span lang="en"><?php echo esc_html( $sbl_address ); ?></span>
				<span class="sbl-office__hours"><?php echo esc_html( $sbl_hours ); ?></span>
			</address>
		</div>
	</section>

	<!-- 6. EVERY BULK ORDER INCLUDES (audit-ready documents + services) ===== -->
	<section class="lp-section" aria-labelledby="sbl-inc-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">অডিট-রেডি কাগজপত্র</p>
				<h2 class="lp-h2" id="sbl-inc-title">প্রতিটি বাল্ক অর্ডারে যা পাবেন</h2>
				<p class="lp-lede">বায়ার কমপ্লায়েন্স অডিটের ফাইলে রাখার মতো ডকুমেন্ট, আর অর্ডার সহজ করার কিছু সুবিধা।</p>
			</header>
			<ul class="lp-grid lp-grid--3">
				<?php foreach ( $bl['includes'] as $inc ) : ?>
					<li class="lp-card sbl-inc">
						<span class="lp-ico"><?php echo safestore_bl_icon( $inc['icon'], 26 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<h3 class="lp-h3"><?php echo esc_html( $inc['title'] ); ?></h3>
						<p><?php echo esc_html( $inc['text'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( ! empty( $bl['certs'] ) ) : ?>
				<ul class="sbl-certs" aria-label="সার্টিফিকেশন">
					<?php foreach ( $bl['certs'] as $cert ) : ?>
						<li><?php echo safestore_bl_icon( 'shield', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span lang="en"><?php echo esc_html( $cert['label'] ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>

	<!-- 7. HOW IT WORKS ===================================================== -->
	<section class="lp-section lp-section--dark" aria-labelledby="sbl-steps-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow lp-eyebrow--light">কীভাবে কাজ করে</p>
				<h2 class="lp-h2" id="sbl-steps-title">৩ ধাপে অর্ডার</h2>
			</header>
			<ol class="lp-steps">
				<?php foreach ( $bl['steps'] as $i => $step ) : ?>
					<li class="lp-step">
						<span class="lp-step__n" aria-hidden="true"><?php echo esc_html( safestore_bl_bn( $i + 1 ) ); ?></span>
						<h3 class="lp-h3"><?php echo esc_html( $step['title'] ); ?></h3>
						<p><?php echo esc_html( $step['text'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<?php if ( $sbl_show_proof ) : ?>
	<!-- 8. SOCIAL PROOF ===================================================== -->
	<section class="lp-section" aria-labelledby="sbl-proof-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">যারা আমাদের থেকে নেন</p>
				<h2 class="lp-h2" id="sbl-proof-title">ফ্যাক্টরি ও প্রতিষ্ঠানের আস্থা</h2>
			</header>
			<?php if ( ! empty( $bl['logos'] ) ) : ?>
				<ul class="sbl-logos">
					<?php foreach ( $bl['logos'] as $logo ) : ?>
						<li><img src="<?php echo esc_url( $logo['src'] ); ?>" alt="<?php echo esc_attr( $logo['alt'] ); ?>" width="<?php echo (int) $logo['w']; ?>" height="<?php echo (int) $logo['h']; ?>" loading="lazy" decoding="async"></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( ! empty( $bl['reviews'] ) ) : ?>
				<ul class="lp-grid lp-grid--3">
					<?php foreach ( $bl['reviews'] as $review ) : ?>
						<li>
							<figure class="lp-card sbl-review">
								<blockquote><p><?php echo esc_html( $review['text'] ); ?></p></blockquote>
								<figcaption>
									<strong><?php echo esc_html( $review['name'] ); ?></strong>
									<span><?php echo esc_html( trim( $review['role'] . ', ' . $review['company'], ', ' ) ); ?></span>
								</figcaption>
							</figure>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( ! empty( $bl['gallery'] ) ) : ?>
				<ul class="sbl-gallery">
					<?php foreach ( $bl['gallery'] as $photo ) : ?>
						<li><img src="<?php echo esc_url( $photo['src'] ); ?>" alt="<?php echo esc_attr( $photo['alt'] ); ?>" width="<?php echo (int) $photo['w']; ?>" height="<?php echo (int) $photo['h']; ?>" loading="lazy" decoding="async"></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<!-- 9. QUOTE FORM ======================================================= -->
	<section class="lp-section lp-section--tint" id="quote" aria-labelledby="sbl-quote-title">
		<div class="lp-wrap sbl-quote__grid">
			<div class="sbl-quote__intro">
				<p class="lp-eyebrow">ফ্রি কোটেশন</p>
				<h2 class="lp-h2" id="sbl-quote-title">কোটেশনের জন্য লিস্ট পাঠান</h2>
				<p>৫টি তথ্য দিলেই হবে — ২৪ ঘণ্টার মধ্যে (শনি–বৃহস্পতি) দাম, স্টক ও ডেলিভারির সময়সহ লিখিত কোটেশন পাঠাব। দ্রুত কোটেশনের জন্য লিখুন:</p>
				<ul class="lp-checks">
					<?php foreach ( $bl['quote_tips'] as $tip ) : ?>
						<li><?php echo safestore_bl_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $tip ); ?></span></li>
					<?php endforeach; ?>
				</ul>
				<p>রিকুইজিশনের ছবি বা Excel দিলেও চলবে। কোটেশনে কোনো চার্জ বা বাধ্যবাধকতা নেই।</p>
				<p class="sbl-quote__alt">
					তাড়া আছে?
					<a href="<?php echo esc_url( $sbl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-sbl-loc="form-intro">WhatsApp-এ লিখুন</a>
					অথবা
					<a href="<?php echo esc_url( 'tel:' . $sbl_tel ); ?>" data-sbl-loc="form-intro">কল করুন</a>
				</p>
			</div>

			<div class="lp-card lp-formcard">
				<div class="lp-success" data-sbl-success tabindex="-1"<?php echo $sbl_done ? '' : ' hidden'; ?> role="status">
					<span class="lp-success__ico"><?php echo safestore_bl_icon( 'check', 32 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<p class="lp-success__title">ধন্যবাদ! আমাদের টিম শিগগিরই আপনার সাথে যোগাযোগ করবে।</p>
					<p>আরও দ্রুত কথা বলতে চাইলে WhatsApp-এ লিখুন।</p>
					<a class="lp-btn lp-btn--wa lp-btn--block" href="<?php echo esc_url( $sbl_wa_after ); ?>" target="_blank" rel="noopener noreferrer" data-sbl-loc="success">
						<?php echo $sbl_wa_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						WhatsApp-এ কথা বলুন
					</a>
				</div>

				<form class="lp-form" data-sbl-form method="post" enctype="multipart/form-data" novalidate
					action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"<?php echo $sbl_done ? ' hidden' : ''; ?>>
					<input type="hidden" name="action" value="<?php echo esc_attr( SAFESTORE_BL_ACTION ); ?>">
					<input type="hidden" name="sbl_nonce" value="<?php echo esc_attr( wp_create_nonce( SAFESTORE_BL_ACTION ) ); ?>">
					<input type="hidden" name="sbl_return" value="<?php echo (int) get_queried_object_id(); ?>">
					<input type="hidden" name="sbl_source" value="<?php echo esc_url( get_permalink() ); ?>" data-sbl-source>
					<input type="hidden" name="sbl_kit" value="<?php echo esc_attr( $sbl_val( 'kit' ) ); ?>" data-sbl-kit-field>

					<p class="lp-alert" data-sbl-error="form" role="alert"<?php echo isset( $sbl_errors['form'] ) ? '' : ' hidden'; ?>><?php echo esc_html( isset( $sbl_errors['form'] ) ? $sbl_errors['form'] : '' ); ?></p>

					<div class="lp-field">
						<label for="sbl-company">কোম্পানির নাম <span class="lp-req" aria-hidden="true">*</span></label>
						<input id="sbl-company" name="sbl_company" type="text" autocomplete="organization" required maxlength="150" value="<?php echo esc_attr( $sbl_val( 'company' ) ); ?>" aria-describedby="sbl-err-company"<?php echo $sbl_invalid( 'company' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<?php $sbl_field_error( 'company' ); ?>
					</div>

					<div class="lp-field">
						<label for="sbl-name">আপনার নাম <span class="lp-req" aria-hidden="true">*</span></label>
						<input id="sbl-name" name="sbl_name" type="text" autocomplete="name" required maxlength="100" value="<?php echo esc_attr( $sbl_val( 'name' ) ); ?>" aria-describedby="sbl-err-name"<?php echo $sbl_invalid( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<?php $sbl_field_error( 'name' ); ?>
					</div>

					<div class="lp-field">
						<label for="sbl-phone">ফোন নম্বর <span class="lp-req" aria-hidden="true">*</span></label>
						<input id="sbl-phone" name="sbl_phone" type="tel" inputmode="tel" autocomplete="tel" required maxlength="20" placeholder="01XXXXXXXXX" value="<?php echo esc_attr( $sbl_val( 'phone' ) ); ?>" aria-describedby="sbl-hint-phone sbl-err-phone"<?php echo $sbl_invalid( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<p class="lp-hint" id="sbl-hint-phone">এই নম্বরে WhatsApp থাকলে কোটেশন সেখানেও পাঠাব।</p>
						<?php $sbl_field_error( 'phone' ); ?>
					</div>

					<div class="lp-field">
						<label for="sbl-district">ডেলিভারির জেলা <span class="lp-req" aria-hidden="true">*</span></label>
						<select id="sbl-district" name="sbl_district" required aria-describedby="sbl-err-district"<?php echo $sbl_invalid( 'district' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
							<option value="">— জেলা বেছে নিন —</option>
							<?php foreach ( safestore_bl_districts() as $division => $districts ) : ?>
								<optgroup label="<?php echo esc_attr( $division ); ?>">
									<?php foreach ( $districts as $district ) : ?>
										<option value="<?php echo esc_attr( $district ); ?>"<?php selected( $sbl_val( 'district' ), $district ); ?>><?php echo esc_html( $district ); ?></option>
									<?php endforeach; ?>
								</optgroup>
							<?php endforeach; ?>
						</select>
						<?php $sbl_field_error( 'district' ); ?>
					</div>

					<div class="lp-field">
						<label for="sbl-items">কী কী লাগবে ও কত পিস <span class="lp-req" aria-hidden="true">*</span></label>
						<textarea id="sbl-items" name="sbl_items" rows="5" required maxlength="3000" placeholder="যেমন: সেফটি হেলমেট ৫০টি, গ্লাভস ১০০ জোড়া, সেফটি শু ৪০ জোড়া (সাইজ ৪০–৪৪)। লাগবে ১৫ তারিখের মধ্যে।" aria-describedby="sbl-err-items"<?php echo $sbl_invalid( 'items' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_textarea( $sbl_val( 'items' ) ); ?></textarea>
						<?php $sbl_field_error( 'items' ); ?>
					</div>

					<div class="lp-field">
						<label for="sbl-file">
							<?php echo safestore_bl_icon( 'upload', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							আইটেম লিস্টের ফাইল <span class="lp-opt">(ঐচ্ছিক)</span>
						</label>
						<input id="sbl-file" name="sbl_file" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.xls,.xlsx,.csv,image/*,application/pdf" aria-describedby="sbl-hint-file sbl-err-file"<?php echo $sbl_invalid( 'file' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<p class="lp-hint" id="sbl-hint-file">ছবি, PDF বা Excel — সর্বোচ্চ ৫MB</p>
						<?php $sbl_field_error( 'file' ); ?>
					</div>

					<div class="lp-hp" aria-hidden="true">
						<label for="sbl-website">Website</label>
						<input id="sbl-website" name="sbl_website" type="text" tabindex="-1" autocomplete="off">
					</div>

					<button class="lp-btn lp-btn--amber lp-btn--lg lp-btn--block lp-submit" type="submit" data-sbl-submit>
						<span class="lp-spin" aria-hidden="true"></span>
						<span data-sbl-submit-label>কোটেশন রিকোয়েস্ট পাঠান</span>
					</button>
					<p class="lp-form__note">আপনার তথ্য শুধু কোটেশনের জন্য ব্যবহার হবে।</p>
				</form>
			</div>
		</div>
	</section>

	<!-- 10. FAQ ============================================================== -->
	<section class="lp-section" aria-labelledby="sbl-faq-title">
		<div class="lp-wrap lp-wrap--narrow">
			<header class="lp-head">
				<p class="lp-eyebrow">প্রশ্ন ও উত্তর</p>
				<h2 class="lp-h2" id="sbl-faq-title">সাধারণ প্রশ্ন</h2>
			</header>
			<div class="lp-faq" data-sbl-faq>
				<?php foreach ( $bl['faq'] as $i => $faq ) : ?>
					<div class="lp-faq__item">
						<h3 class="lp-faq__q">
							<button type="button" class="lp-faq__btn" id="sbl-faq-btn-<?php echo (int) $i; ?>" aria-expanded="true" aria-controls="sbl-faq-panel-<?php echo (int) $i; ?>">
								<span><?php echo esc_html( $faq['q'] ); ?></span>
								<?php echo safestore_bl_icon( 'chevron', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</button>
						</h3>
						<div class="lp-faq__a" id="sbl-faq-panel-<?php echo (int) $i; ?>" role="region" aria-labelledby="sbl-faq-btn-<?php echo (int) $i; ?>">
							<p><?php echo esc_html( $faq['a'] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- 11. FINAL CTA ======================================================== -->
	<section class="lp-final" aria-labelledby="sbl-final-title">
		<div class="lp-wrap">
			<h2 class="lp-h2" id="sbl-final-title"><?php echo esc_html( $bl['final']['title'] ); ?></h2>
			<p><?php echo esc_html( $bl['final']['text'] ); ?></p>
			<div class="lp-cta-row">
				<a class="lp-btn lp-btn--amber lp-btn--lg" href="#quote" data-sbl-goto="quote">ফ্রি কোটেশন নিন</a>
				<a class="lp-btn lp-btn--wa lp-btn--lg" href="<?php echo esc_url( $sbl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-sbl-loc="final">
					<?php echo $sbl_wa_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					WhatsApp-এ কথা বলুন
				</a>
			</div>
		</div>
	</section>
</main>

<footer class="lp-footer" role="contentinfo">
	<div class="lp-wrap lp-footer__grid">
		<div>
			<p class="lp-footer__brand"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<p lang="en"><?php echo esc_html( $sbl_address ); ?></p>
			<p><?php echo esc_html( $sbl_hours ); ?></p>
		</div>
		<ul>
			<li><a href="<?php echo esc_url( 'tel:' . $sbl_tel ); ?>" data-sbl-loc="footer"><?php echo safestore_bl_icon( 'phone', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $sbl_tel_label ); ?></a></li>
			<?php if ( $sbl_email ) : ?>
				<li><a href="<?php echo esc_url( 'mailto:' . $sbl_email ); ?>"><?php echo esc_html( $sbl_email ); ?></a></li>
			<?php endif; ?>
		</ul>
		<nav aria-label="নীতিমালা">
			<ul class="lp-footer__links">
				<li><a href="<?php echo esc_url( home_url( '/shipping-delivery/' ) ); ?>">ডেলিভারি</a></li>
				<li><a href="<?php echo esc_url( home_url( '/return-refund-policy/' ) ); ?>">রিটার্ন ও রিফান্ড</a></li>
				<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">প্রাইভেসি পলিসি</a></li>
				<li><a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>">শর্তাবলি</a></li>
				<li><a href="<?php echo esc_url( home_url( '/bulk-orders/' ) ); ?>" lang="en">Bulk Orders (English)</a></li>
				<li><a href="<?php echo esc_url( $sbl_shop ); ?>">শপ</a></li>
			</ul>
		</nav>
	</div>
	<p class="lp-wrap lp-footer__copy">© <?php echo esc_html( safestore_bl_bn( date_i18n( 'Y' ) ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
</footer>

<!-- 12. MOBILE STICKY BAR (<768px) ========================================= -->
<nav class="lp-sticky" data-sbl-sticky aria-label="দ্রুত যোগাযোগ">
	<a href="<?php echo esc_url( 'tel:' . $sbl_tel ); ?>" data-sbl-loc="sticky">
		<?php echo safestore_bl_icon( 'phone', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span>কল</span>
	</a>
	<a class="lp-sticky__wa" href="<?php echo esc_url( $sbl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-sbl-loc="sticky">
		<?php echo $sbl_wa_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span>WhatsApp</span>
	</a>
	<a class="lp-sticky__cta" href="#quote" data-sbl-goto="quote">
		<?php echo safestore_bl_icon( 'quote', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span>কোটেশন</span>
	</a>
</nav>

<?php wp_footer(); ?>
</body>
</html>

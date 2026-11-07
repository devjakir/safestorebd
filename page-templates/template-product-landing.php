<?php
/**
 * Template Name: Product Landing (Facebook)
 *
 * One product, one action. Built for ad traffic (Facebook / Instagram):
 * photo + price + size picker + "অর্ডার করুন" above the fold, then proof,
 * delivery facts, more products, FAQ. The order button adds the chosen size
 * to the cart and goes straight to the normal checkout (COD).
 *
 * Per-page settings live in the "Product Landing (Facebook)" box on the page
 * edit screen; logic and defaults in inc/product-landing.php. Styling:
 * css/landing-common.css (shared .lp-*) + css/product-landing.css (.spl-*).
 */

defined( 'ABSPATH' ) || exit;

$spl_page_id = (int) get_queried_object_id();
$spl         = safestore_pl_settings( $spl_page_id );
$spl_product = safestore_pl_find_product( $spl['product'] );

$spl_tel       = function_exists( 'safestore_primary_phone_e164' ) ? safestore_primary_phone_e164() : '+8801811892291';
$spl_tel_label = function_exists( 'safestore_primary_phone_display' ) ? safestore_primary_phone_display() : '+880 1811-892291';
$spl_wa_base   = function_exists( 'safestore_primary_wa_link' ) ? safestore_primary_wa_link() : 'https://wa.me/8801811892291';
$spl_wa_icon   = function_exists( 'safestore_wa_icon_svg' ) ? safestore_wa_icon_svg( 'lp-ico-wa' ) : '';
$spl_address   = function_exists( 'safestore_minimal_get_pickup_address' ) ? safestore_minimal_get_pickup_address() : '17/5/1 Alabdirtek, Pallabi, Dhaka 1216, Bangladesh';
$spl_emails    = function_exists( 'safestore_contact_email_addresses' ) ? safestore_contact_email_addresses() : array( 'contact@safestorebd.com' );
$spl_email     = $spl_emails ? reset( $spl_emails ) : '';
$spl_shop      = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$spl_logo_dir  = get_template_directory_uri() . '/assets/images/logo/';
$spl_hours     = 'শনি–বৃহস্পতি, সকাল ৯টা – রাত ৮টা';

if ( $spl_product ) {
	$spl_name         = $spl_product->get_name();
	$spl_price        = safestore_pl_price( $spl_product );
	$spl_options      = safestore_pl_options( $spl_product ); // null = needs colour + size → buy on the PDP
	$spl_has_options  = is_array( $spl_options ) && ! empty( $spl_options['items'] );
	$spl_any_in_stock = $spl_has_options ? (bool) count( array_filter( wp_list_pluck( $spl_options['items'], 'in_stock' ) ) ) : $spl_product->is_in_stock();
	$spl_can_buy      = null !== $spl_options && $spl_product->is_purchasable() && $spl_any_in_stock;
	$spl_bullets      = safestore_pl_bullets( $spl_product, $spl['bullets'] );
	$spl_reviews      = safestore_pl_reviews( $spl_product );
	$spl_rating       = (float) $spl_product->get_average_rating();
	$spl_review_count = (int) $spl_product->get_review_count();
	$spl_more         = safestore_pl_more_products( $spl['more'], $spl_product );
	$spl_delivery     = safestore_pl_delivery();
	$spl_faq          = safestore_pl_faq( $spl_product, $spl_has_options );
	$spl_brand        = '';
	foreach ( array( 'product_brand', 'pa_brand', 'pwb-brand' ) as $spl_tax ) {
		if ( taxonomy_exists( $spl_tax ) ) {
			$spl_terms = get_the_terms( $spl_product->get_id(), $spl_tax );
			if ( $spl_terms && ! is_wp_error( $spl_terms ) ) {
				$spl_brand = $spl_terms[0]->name;
				break;
			}
		}
	}
	$spl_cat_terms = get_the_terms( $spl_product->get_id(), 'product_cat' );
	$spl_cat       = ( $spl_cat_terms && ! is_wp_error( $spl_cat_terms ) ) ? $spl_cat_terms[0]->name : '';
	$spl_eyebrow   = trim( $spl_brand . ( $spl_brand && $spl_cat ? ' · ' : '' ) . $spl_cat );

	$spl_headline = '' !== $spl['headline'] ? $spl['headline'] : $spl_name;
	$spl_lede     = '' !== $spl['lede'] ? $spl['lede'] : ( $spl_has_options ? 'সাইজ নির্বাচন করে অর্ডার করুন। সারা বাংলাদেশে ক্যাশ অন ডেলিভারি।' : 'এখনই অর্ডার করুন। সারা বাংলাদেশে ক্যাশ অন ডেলিভারি।' );

	$spl_img_id  = (int) $spl_product->get_image_id();
	$spl_gallery = array_values( array_unique( array_filter( array_merge( array( $spl_img_id ), (array) $spl_product->get_gallery_image_ids() ) ) ) );
	$spl_sizes   = '(max-width: 899px) calc(100vw - 32px), 560px';
	$spl_preload = $spl_img_id ? array(
		'src'    => wp_get_attachment_image_url( $spl_img_id, 'woocommerce_single' ),
		'srcset' => (string) wp_get_attachment_image_srcset( $spl_img_id, 'woocommerce_single' ),
		'sizes'  => $spl_sizes,
	) : array();

	// WhatsApp order text; JS fills {size} when a size is picked.
	$spl_wa_tpl = "আসসালামু আলাইকুম SafeStoreBD, আমি এই পণ্যটি অর্ডার করতে চাই:\n" . $spl_name . "\n" . ( $spl_has_options ? 'সাইজ: {size}' . "\n" : '' ) . "পরিমাণ: ১\nনাম: \nঠিকানা: \nফোন: ";
	$spl_wa     = $spl_wa_base . '?text=' . rawurlencode( str_replace( '{size}', '', $spl_wa_tpl ) );

	safestore_pl_head(
		array(
			'title'       => $spl_headline . ' | ' . get_bloginfo( 'name' ),
			'description' => $spl_lede,
			'image'       => $spl_img_id ? wp_get_attachment_image_url( $spl_img_id, 'large' ) : '',
		),
		$spl['index'],
		$spl_product->get_permalink(),
		$spl_preload
	);

	// GET form: a query string on the action would be dropped by the browser (plain permalinks).
	$spl_action = get_permalink();
	parse_str( (string) wp_parse_url( $spl_action, PHP_URL_QUERY ), $spl_action_args );

	$spl_js = array(
		'id'       => $spl_product->get_id(),
		'sku'      => (string) $spl_product->get_sku(),
		'name'     => $spl_name,
		'price'    => $spl_price['now'],
		'currency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'BDT',
		'waBase'   => $spl_wa_base,
		'waText'   => $spl_wa_tpl,
		'options'  => array(),
	);
	if ( $spl_has_options ) {
		foreach ( $spl_options['items'] as $spl_o ) {
			$spl_js['options'][ $spl_o['id'] ] = array( 'label' => $spl_o['label'], 'price' => $spl_o['price'], 'max' => $spl_o['max'] );
		}
	}
} else {
	$spl_wa = $spl_wa_base;
}

$spl_error = safestore_pl_error_text();
$spl_trust = array(
	array( 'icon' => 'card', 'text' => 'ক্যাশ অন ডেলিভারি' ),
	array( 'icon' => 'truck', 'text' => 'ঢাকায় ২৪ ঘণ্টায় ডেলিভারি' ),
	array( 'icon' => 'pin', 'text' => '৬৪ জেলায় ডেলিভারি' ),
	array( 'icon' => 'return', 'text' => '৭ দিনে রিটার্ন ও এক্সচেঞ্জ' ),
);
$spl_steps = array(
	array( 'title' => $spl_product && $spl_has_options ? 'সাইজ নির্বাচন করুন' : 'পরিমাণ নির্বাচন করুন', 'text' => ( $spl_product && $spl_has_options ? 'পছন্দের সাইজ ও পরিমাণ ঠিক করে ' : '' ) . '"অর্ডার করুন" বাটনে ক্লিক করুন।' ),
	array( 'title' => 'ডেলিভারির তথ্য দিন', 'text' => 'নাম, মোবাইল নম্বর ও পূর্ণ ঠিকানা লিখুন। জেলা ও থানা নির্বাচন করলে ডেলিভারি চার্জ দেখা যাবে।' ),
	array( 'title' => 'পণ্য যাচাই করে মূল্য পরিশোধ', 'text' => 'ডেলিভারি প্রতিনিধির সামনে পণ্য যাচাই করে মূল্য পরিশোধ করুন। bKash ও Nagad-এও পরিশোধ করা যায়।' ),
);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="lp-skip" href="#spl-main">মূল কনটেন্টে যান</a>

<header class="lp-header" role="banner">
	<div class="lp-wrap lp-header__in">
		<a class="lp-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> — হোম">
			<picture>
				<source type="image/webp" srcset="<?php echo esc_url( $spl_logo_dir . 'safe-store-bd.webp' ); ?>">
				<img src="<?php echo esc_url( $spl_logo_dir . 'safe-store-bd.png' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="240" height="128" decoding="async">
			</picture>
		</a>
		<div class="lp-header__acts">
			<a class="lp-header__tel" href="<?php echo esc_url( 'tel:' . $spl_tel ); ?>" data-spl-loc="header" aria-label="<?php echo esc_attr( 'কল করুন: ' . $spl_tel_label ); ?>">
				<?php echo safestore_pl_icon( 'phone', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="lp-header__num" aria-hidden="true"><?php echo esc_html( $spl_tel_label ); ?></span>
			</a>
			<a class="lp-btn lp-btn--wa lp-btn--sm" href="<?php echo esc_url( $spl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-spl-loc="header" data-spl-wa>
				<?php echo $spl_wa_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span>WhatsApp</span>
			</a>
		</div>
	</div>
</header>

<main id="spl-main">
<?php if ( ! $spl_product ) : ?>

	<section class="lp-section">
		<div class="lp-wrap lp-wrap--narrow spl-empty">
			<h1 class="lp-h2">পণ্যটি বর্তমানে পাওয়া যাচ্ছে না</h1>
			<p>আমাদের শপে সকল সেফটি পণ্য দেখুন, অথবা WhatsApp-এ যোগাযোগ করুন।</p>
			<?php if ( current_user_can( 'edit_page', $spl_page_id ) ) : ?>
				<p class="lp-alert">Admin: no published product matches "<?php echo esc_html( $spl['product'] ); ?>". Set the product URL / slug / ID in the "Product Landing (Facebook)" box on this page.</p>
			<?php endif; ?>
			<div class="lp-cta-row">
				<a class="lp-btn lp-btn--dark" href="<?php echo esc_url( $spl_shop ); ?>">শপে যান</a>
				<a class="lp-btn lp-btn--wa" href="<?php echo esc_url( $spl_wa ); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
			</div>
		</div>
	</section>

<?php else : ?>

	<!-- 1. HERO: photo + price + size + order ================================= -->
	<section class="spl-hero" aria-labelledby="spl-title">
		<div class="lp-wrap spl-hero__grid">

			<div class="spl-gallery" data-spl-gallery>
				<figure class="spl-gallery__main">
					<?php
					if ( $spl_img_id ) {
						echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
							$spl_img_id,
							'woocommerce_single',
							false,
							array(
								'fetchpriority'  => 'high',
								'decoding'       => 'async',
								'sizes'          => $spl_sizes,
								'alt'            => $spl_name,
								'data-spl-main'  => '1',
							)
						);
					} elseif ( function_exists( 'wc_placeholder_img' ) ) {
						echo wc_placeholder_img( 'woocommerce_single', array( 'data-spl-main' => '1' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
					<?php if ( '' !== $spl['badge'] ) : ?>
						<span class="spl-badge"><?php echo esc_html( $spl['badge'] ); ?></span>
					<?php elseif ( $spl_price['was'] > 0 ) : ?>
						<span class="spl-badge"><?php echo esc_html( safestore_pl_taka( $spl_price['was'] - $spl_price['now'] ) . ' ছাড়' ); ?></span>
					<?php endif; ?>
				</figure>
				<?php if ( count( $spl_gallery ) > 1 ) : ?>
					<ul class="spl-gallery__thumbs" aria-label="আরও ছবি">
						<?php foreach ( $spl_gallery as $spl_i => $spl_gid ) : ?>
							<li>
								<button type="button" class="spl-thumb<?php echo 0 === $spl_i ? ' is-active' : ''; ?>"
									data-spl-thumb
									data-src="<?php echo esc_url( wp_get_attachment_image_url( $spl_gid, 'woocommerce_single' ) ); ?>"
									data-srcset="<?php echo esc_attr( (string) wp_get_attachment_image_srcset( $spl_gid, 'woocommerce_single' ) ); ?>"
									aria-label="<?php echo esc_attr( 'ছবি ' . safestore_pl_bn( $spl_i + 1 ) ); ?>"
									<?php echo 0 === $spl_i ? 'aria-current="true"' : ''; ?>>
									<?php echo wp_get_attachment_image( $spl_gid, 'woocommerce_gallery_thumbnail', false, array( 'loading' => 0 === $spl_i ? 'eager' : 'lazy', 'decoding' => 'async', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="spl-buy">
				<?php if ( '' !== $spl_eyebrow ) : ?>
					<p class="lp-eyebrow" lang="en"><?php echo esc_html( $spl_eyebrow ); ?></p>
				<?php endif; ?>
				<h1 class="spl-title" id="spl-title"><?php echo esc_html( $spl_headline ); ?></h1>
				<p class="spl-lede"><?php echo esc_html( $spl_lede ); ?></p>

				<?php if ( $spl_review_count > 0 ) : ?>
					<a class="spl-rating" href="#reviews">
						<?php echo safestore_pl_stars( $spl_rating ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><?php echo esc_html( safestore_pl_bn( number_format( $spl_rating, 1 ) ) ); ?></span>
						<span class="spl-rating__count"><?php echo esc_html( '(' . safestore_pl_bn( $spl_review_count ) . ' রিভিউ)' ); ?></span>
					</a>
				<?php endif; ?>

				<p class="spl-price">
					<?php if ( $spl_price['from'] ) : ?><span class="spl-price__from">শুরু</span><?php endif; ?>
					<strong data-spl-price><?php echo esc_html( safestore_pl_taka( $spl_price['now'] ) ); ?></strong>
					<?php if ( $spl_price['was'] > 0 ) : ?>
						<s><?php echo esc_html( safestore_pl_taka( $spl_price['was'] ) ); ?></s>
					<?php endif; ?>
				</p>

				<?php if ( $spl_bullets ) : ?>
					<ul class="lp-checks spl-bullets">
						<?php foreach ( $spl_bullets as $spl_b ) : ?>
							<li><?php echo safestore_pl_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $spl_b ); ?></span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( '' !== $spl_error ) : ?>
					<p class="lp-alert" role="alert" id="order"><?php echo esc_html( $spl_error ); ?></p>
				<?php endif; ?>

				<?php if ( null === $spl_options ) : ?>
					<?php // Colour + size etc.: pick them on the product page. ?>
					<div class="spl-order" id="<?php echo '' === $spl_error ? 'order' : 'order-cta'; ?>">
						<a class="lp-btn lp-btn--amber lp-btn--lg lp-btn--block spl-cta" href="<?php echo esc_url( $spl_product->get_permalink() ); ?>" data-spl-pdp>
							অপশন নির্বাচন করে অর্ডার করুন
							<?php echo safestore_pl_icon( 'arrow', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
						<a class="lp-btn lp-btn--wa lp-btn--lg lp-btn--block" href="<?php echo esc_url( $spl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-spl-loc="hero" data-spl-wa>
							<?php echo $spl_wa_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							WhatsApp-এ অর্ডার করুন
						</a>
					</div>
				<?php elseif ( ! $spl_can_buy ) : ?>
					<div class="spl-order" id="<?php echo '' === $spl_error ? 'order' : 'order-cta'; ?>">
						<p class="spl-soldout">পণ্যটি বর্তমানে স্টকে নেই। স্টক সম্পর্কে জানতে WhatsApp-এ যোগাযোগ করুন।</p>
						<a class="lp-btn lp-btn--wa lp-btn--lg lp-btn--block" href="<?php echo esc_url( $spl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-spl-loc="hero" data-spl-wa>
							<?php echo $spl_wa_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							WhatsApp-এ স্টক জানুন
						</a>
					</div>
				<?php else : ?>
					<form class="spl-order" id="spl-order" method="get" action="<?php echo esc_url( $spl_action ); ?>" data-spl-form>
						<?php if ( '' === $spl_error ) : ?><span id="order" class="spl-anchor" aria-hidden="true"></span><?php endif; ?>
						<?php foreach ( (array) $spl_action_args as $spl_k => $spl_v ) : ?>
							<input type="hidden" name="<?php echo esc_attr( $spl_k ); ?>" value="<?php echo esc_attr( $spl_v ); ?>">
						<?php endforeach; ?>

						<?php if ( $spl_has_options ) : ?>
							<fieldset class="spl-sizes" data-spl-sizes>
								<legend><?php echo esc_html( $spl_options['name'] ); ?> নির্বাচন করুন <span class="lp-req" aria-hidden="true">*</span></legend>
								<div class="spl-sizes__grid">
									<?php foreach ( $spl_options['items'] as $spl_o ) : ?>
										<label class="spl-size<?php echo $spl_o['in_stock'] ? '' : ' is-out'; ?>">
											<input type="radio" name="spl_add" value="<?php echo (int) $spl_o['id']; ?>" required<?php echo $spl_o['in_stock'] ? '' : ' disabled'; ?> data-spl-size>
											<span lang="en"><?php echo esc_html( $spl_o['label'] ); ?></span>
											<?php if ( ! $spl_o['in_stock'] ) : ?><small>স্টক নেই</small><?php endif; ?>
										</label>
									<?php endforeach; ?>
								</div>
								<p class="spl-sizes__hint" data-spl-size-hint>আপনি নিয়মিত যে সাইজের জুতা পরেন, সেটিই নির্বাচন করুন। সাইজ নিয়ে নিশ্চিত না হলে WhatsApp-এ যোগাযোগ করুন। ডেলিভারির ৭ দিনের মধ্যে সাইজ পরিবর্তন করা যায়।</p>
							</fieldset>
						<?php else : ?>
							<input type="hidden" name="spl_add" value="<?php echo (int) $spl_product->get_id(); ?>">
						<?php endif; ?>

						<div class="spl-qty">
							<label for="spl-qty">পরিমাণ</label>
							<div class="spl-qty__ctl">
								<button type="button" data-spl-qty="-1" aria-label="কমান">−</button>
								<input id="spl-qty" name="qty" type="number" inputmode="numeric" min="1" max="<?php echo (int) SAFESTORE_PL_MAX_QTY; ?>" step="1" value="1" data-spl-qty-input>
								<button type="button" data-spl-qty="1" aria-label="বাড়ান">+</button>
							</div>
						</div>

						<button class="lp-btn lp-btn--amber lp-btn--lg lp-btn--block spl-cta" type="submit" data-spl-submit>
							<span data-spl-submit-label>অর্ডার করুন — ক্যাশ অন ডেলিভারি</span>
							<?php echo safestore_pl_icon( 'arrow', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</button>
						<a class="lp-btn lp-btn--wa lp-btn--lg lp-btn--block" href="<?php echo esc_url( $spl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-spl-loc="hero" data-spl-wa>
							<?php echo $spl_wa_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
							WhatsApp-এ অর্ডার করুন
						</a>
						<p class="lp-note">অর্ডার নিশ্চিত করতে আমরা ফোনে যোগাযোগ করব · পণ্য যাচাই করে মূল্য পরিশোধ</p>
					</form>
				<?php endif; ?>

				<ul class="spl-trust" aria-label="কেন SafeStoreBD">
					<?php foreach ( $spl_trust as $spl_t ) : ?>
						<li><?php echo safestore_pl_icon( $spl_t['icon'], 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $spl_t['text'] ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</section>

	<!-- 2. WHY / WHERE IT IS USED ============================================= -->
	<?php if ( $spl_bullets || $spl['uses'] ) : ?>
	<section class="lp-section lp-section--tint" id="features" aria-labelledby="spl-why-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">সুবিধাগুলো</p>
				<h2 class="lp-h2" id="spl-why-title">কেন এটা কিনবেন?</h2>
			</header>
			<?php if ( $spl_bullets ) : ?>
				<ul class="lp-grid lp-grid--2 spl-why">
					<?php foreach ( $spl_bullets as $spl_b ) : ?>
						<li class="lp-card spl-why__item">
							<span class="lp-ico"><?php echo safestore_pl_icon( 'shield', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<p><?php echo esc_html( $spl_b ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $spl['uses'] ) : ?>
				<p class="spl-uses__label">যেসব কাজে উপযোগী</p>
				<ul class="spl-uses" aria-label="ব্যবহারের জায়গা">
					<?php foreach ( $spl['uses'] as $spl_u ) : ?>
						<li><?php echo esc_html( $spl_u ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( $spl_more ) : ?>
	<!-- 2b. MORE PRODUCTS (alternatives, right after features) ====================================================== -->
	<section class="lp-section" id="more" aria-labelledby="spl-more-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">আরও পণ্য</p>
				<h2 class="lp-h2" id="spl-more-title">আপনার কাজের উপযোগী আরও সেফটি পণ্য</h2>
				<p class="lp-lede">প্রতিটি পণ্যে একই সুবিধা: সারা বাংলাদেশে ক্যাশ অন ডেলিভারি ও ৭ দিনের রিটার্ন।</p>
			</header>
			<ul class="lp-grid spl-more">
				<?php foreach ( $spl_more as $spl_m ) : ?>
					<li>
						<a class="lp-card spl-card" href="<?php echo esc_url( $spl_m['url'] ); ?>">
							<span class="spl-card__img"><?php echo $spl_m['image']; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_get_attachment_image ?></span>
							<span class="spl-card__name" lang="en"><?php echo esc_html( $spl_m['name'] ); ?></span>
							<span class="spl-card__price">
								<?php if ( $spl_m['price']['now'] > 0 ) : ?>
									<?php echo esc_html( ( $spl_m['price']['from'] ? 'শুরু ' : '' ) . safestore_pl_taka( $spl_m['price']['now'] ) ); ?>
									<?php if ( $spl_m['price']['was'] > 0 ) : ?><s><?php echo esc_html( safestore_pl_taka( $spl_m['price']['was'] ) ); ?></s><?php endif; ?>
								<?php else : ?>
									দাম দেখুন
								<?php endif; ?>
							</span>
							<span class="spl-card__go"><?php echo $spl_m['in_stock'] ? 'দেখুন ও অর্ডার করুন' : 'বিস্তারিত দেখুন'; ?> <?php echo safestore_pl_icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="spl-more__all"><a href="<?php echo esc_url( $spl_shop ); ?>">সব প্রোডাক্ট দেখুন →</a></p>
		</div>
	</section>
	<?php endif; ?>

	<!-- 3. DELIVERY & PAYMENT ================================================= -->
	<section class="lp-section lp-section--tint" id="delivery" aria-labelledby="spl-delivery-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">ডেলিভারি, পেমেন্ট ও রিটার্ন</p>
				<h2 class="lp-h2" id="spl-delivery-title">অর্ডার করার আগে জেনে নিন</h2>
			</header>
			<ul class="lp-grid lp-grid--3 spl-delivery">
				<?php foreach ( $spl_delivery as $spl_d ) : ?>
					<li class="lp-card spl-delivery__item">
						<span class="lp-ico"><?php echo safestore_pl_icon( $spl_d['icon'], 24 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<h3 class="lp-h3"><?php echo esc_html( $spl_d['title'] ); ?></h3>
						<p><?php echo esc_html( $spl_d['text'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<!-- 4. HOW TO ORDER ======================================================= -->
	<section class="lp-section lp-section--dark" aria-labelledby="spl-steps-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow lp-eyebrow--light">কীভাবে অর্ডার করবেন</p>
				<h2 class="lp-h2" id="spl-steps-title">৩টি সহজ ধাপে অর্ডার করুন</h2>
			</header>
			<ol class="lp-steps">
				<?php foreach ( $spl_steps as $spl_i => $spl_s ) : ?>
					<li class="lp-step">
						<span class="lp-step__n" aria-hidden="true"><?php echo esc_html( safestore_pl_bn( $spl_i + 1 ) ); ?></span>
						<h3 class="lp-h3"><?php echo esc_html( $spl_s['title'] ); ?></h3>
						<p><?php echo esc_html( $spl_s['text'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<?php if ( $spl_reviews ) : ?>
	<!-- 5. REVIEWS (real WooCommerce reviews only) ============================ -->
	<section class="lp-section" id="reviews" aria-labelledby="spl-reviews-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">ক্রেতাদের মতামত</p>
				<h2 class="lp-h2" id="spl-reviews-title">ক্রেতারা যা বলছেন</h2>
				<p class="lp-lede"><?php echo esc_html( safestore_pl_bn( $spl_review_count ) . 'টি রিভিউর ভিত্তিতে গড় রেটিং ' . safestore_pl_bn( number_format( $spl_rating, 1 ) ) . ' / ৫' ); ?></p>
			</header>
			<ul class="lp-grid lp-grid--3">
				<?php foreach ( $spl_reviews as $spl_r ) : ?>
					<li>
						<figure class="lp-card spl-review">
							<?php if ( $spl_r['rating'] > 0 ) : ?>
								<?php echo safestore_pl_stars( $spl_r['rating'], 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php endif; ?>
							<blockquote><p><?php echo esc_html( $spl_r['text'] ); ?></p></blockquote>
							<figcaption>
								<strong><?php echo esc_html( $spl_r['name'] ); ?></strong>
								<?php if ( $spl_r['verified'] ) : ?><span>ভেরিফাইড ক্রেতা</span><?php endif; ?>
							</figcaption>
						</figure>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php endif; ?>

	<!-- 7. FAQ ================================================================ -->
	<section class="lp-section" id="faq" aria-labelledby="spl-faq-title">
		<div class="lp-wrap lp-wrap--narrow">
			<header class="lp-head">
				<p class="lp-eyebrow">প্রশ্ন ও উত্তর</p>
				<h2 class="lp-h2" id="spl-faq-title">সচরাচর জিজ্ঞাসা</h2>
			</header>
			<div class="lp-faq" data-spl-faq>
				<?php foreach ( $spl_faq as $spl_i => $spl_f ) : ?>
					<div class="lp-faq__item">
						<h3 class="lp-faq__q">
							<button type="button" class="lp-faq__btn" id="spl-faq-btn-<?php echo (int) $spl_i; ?>" aria-expanded="true" aria-controls="spl-faq-panel-<?php echo (int) $spl_i; ?>">
								<span><?php echo esc_html( $spl_f['q'] ); ?></span>
								<?php echo safestore_pl_icon( 'chevron', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</button>
						</h3>
						<div class="lp-faq__a" id="spl-faq-panel-<?php echo (int) $spl_i; ?>" role="region" aria-labelledby="spl-faq-btn-<?php echo (int) $spl_i; ?>">
							<p><?php echo esc_html( $spl_f['a'] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- 8. FINAL CTA ========================================================== -->
	<section class="lp-final" aria-labelledby="spl-final-title">
		<div class="lp-wrap">
			<h2 class="lp-h2" id="spl-final-title"><?php echo esc_html( $spl_name . ' — ' . ( $spl_price['from'] ? 'শুরু ' : '' ) . safestore_pl_taka( $spl_price['now'] ) ); ?></h2>
			<p>ক্যাশ অন ডেলিভারি · সারা বাংলাদেশে · ৭ দিনের রিটার্ন</p>
			<div class="lp-cta-row">
				<?php if ( $spl_can_buy ) : ?>
					<a class="lp-btn lp-btn--amber lp-btn--lg" href="#order" data-spl-goto="order">অর্ডার করুন</a>
				<?php elseif ( null === $spl_options ) : ?>
					<a class="lp-btn lp-btn--amber lp-btn--lg" href="<?php echo esc_url( $spl_product->get_permalink() ); ?>">অপশন নির্বাচন করে অর্ডার করুন</a>
				<?php endif; ?>
				<a class="lp-btn lp-btn--wa lp-btn--lg" href="<?php echo esc_url( $spl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-spl-loc="final" data-spl-wa>
					<?php echo $spl_wa_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					WhatsApp-এ অর্ডার করুন
				</a>
			</div>
		</div>
	</section>

<?php endif; ?>
</main>

<footer class="lp-footer" role="contentinfo">
	<div class="lp-wrap lp-footer__grid">
		<div>
			<p class="lp-footer__brand"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<p lang="en"><?php echo esc_html( $spl_address ); ?></p>
			<p><?php echo esc_html( $spl_hours ); ?></p>
		</div>
		<ul>
			<li><a href="<?php echo esc_url( 'tel:' . $spl_tel ); ?>" data-spl-loc="footer"><?php echo safestore_pl_icon( 'phone', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $spl_tel_label ); ?></a></li>
			<?php if ( $spl_email ) : ?>
				<li><a href="<?php echo esc_url( 'mailto:' . $spl_email ); ?>"><?php echo esc_html( $spl_email ); ?></a></li>
			<?php endif; ?>
		</ul>
		<nav aria-label="নীতিমালা">
			<ul class="lp-footer__links">
				<li><a href="<?php echo esc_url( home_url( '/shipping-delivery/' ) ); ?>">ডেলিভারি</a></li>
				<li><a href="<?php echo esc_url( home_url( '/return-refund-policy/' ) ); ?>">রিটার্ন ও রিফান্ড</a></li>
				<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">প্রাইভেসি পলিসি</a></li>
				<li><a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>">শর্তাবলি</a></li>
				<li><a href="<?php echo esc_url( home_url( '/track-order/' ) ); ?>">অর্ডার ট্র্যাক</a></li>
				<li><a href="<?php echo esc_url( $spl_shop ); ?>">শপ</a></li>
			</ul>
		</nav>
	</div>
	<p class="lp-wrap lp-footer__copy">© <?php echo esc_html( safestore_pl_bn( date_i18n( 'Y' ) ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
</footer>

<?php if ( $spl_product ) : ?>
<!-- 9. MOBILE STICKY BAR (<768px) ============================================ -->
<nav class="lp-sticky spl-sticky" data-spl-sticky aria-label="দ্রুত অর্ডার">
	<a class="lp-sticky__wa" href="<?php echo esc_url( $spl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-spl-loc="sticky" data-spl-wa aria-label="WhatsApp-এ অর্ডার করুন">
		<?php echo $spl_wa_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</a>
	<?php if ( $spl_can_buy ) : ?>
		<button class="lp-sticky__cta" type="submit" form="spl-order" data-spl-sticky-cta>
			<span>অর্ডার করুন</span>
			<strong data-spl-price="total"><?php echo esc_html( safestore_pl_taka( $spl_price['now'] ) ); ?></strong>
		</button>
	<?php elseif ( null === $spl_options ) : ?>
		<a class="lp-sticky__cta" href="<?php echo esc_url( $spl_product->get_permalink() ); ?>"><span>অপশন নির্বাচন করে অর্ডার করুন</span></a>
	<?php else : ?>
		<a class="lp-sticky__cta" href="<?php echo esc_url( 'tel:' . $spl_tel ); ?>" data-spl-loc="sticky"><?php echo safestore_pl_icon( 'phone', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>কল করুন</span></a>
	<?php endif; ?>
</nav>
<script type="application/json" id="spl-data"><?php echo wp_json_encode( $spl_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>

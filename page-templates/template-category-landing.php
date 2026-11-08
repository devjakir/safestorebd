<?php
/**
 * Template Name: Category Landing (Facebook)
 *
 * One category, one order. Built for ad traffic (Facebook / Instagram) that
 * comes for a kind of product rather than one item (first campaign: Safety
 * Vest): promise + price anchor above the fold, a "which one is for you?"
 * guide, every product as a card with "Add" + quantity, and one order form
 * next to the list (name, phone, address, COD or bKash / Nagad) — the order is
 * placed on this page, then the thank-you page. Then proof, bulk / logo
 * printing, delivery facts, steps, FAQ.
 *
 * ?pick=<product slug or ID> pre-adds one piece of that product (ads for one vest).
 *
 * Settings: "Category Landing (Facebook)" box on the page edit screen; logic,
 * defaults and card copy in inc/category-landing.php; header, footer and the
 * order form are the shared partials in templates/landing/. Styling:
 * landing-common.css (.lp-*) + product-landing.css (shared order block .spl-*)
 * + category-landing.css (.scl-*).
 */

defined( 'ABSPATH' ) || exit;

$scl_page_id = (int) get_queried_object_id();
$scl         = safestore_cl_settings( $scl_page_id );
$scl_term    = safestore_cl_term( $scl['category'] );
$scl_items   = safestore_cl_items( safestore_cl_products( $scl ), $scl['items'] );
$scl_c       = safestore_lp_contact();
$scl_cat     = $scl_term ? $scl_term->name : 'Safety Vest';
$scl_wa_tpl  = safestore_cl_wa_text( $scl_cat );
$scl_wa      = safestore_lp_wa( str_replace( "{items}\n", '', $scl_wa_tpl ) );
$scl_error   = safestore_pl_error_text();

if ( $scl_items ) {
	$scl_buyable = array_values( array_filter( $scl_items, static function ( $it ) {
		return $it['can_buy'];
	} ) );
	$scl_prices  = array_filter( wp_list_pluck( wp_list_pluck( $scl_items, 'price' ), 'now' ) );
	$scl_from    = $scl_prices ? min( $scl_prices ) : 0;
	$scl_off     = 0;
	foreach ( $scl_items as $scl_it ) {
		if ( $scl_it['price']['was'] > 0 ) {
			$scl_off = max( $scl_off, (int) round( ( $scl_it['price']['was'] - $scl_it['price']['now'] ) / $scl_it['price']['was'] * 100 ) );
		}
	}
	$scl_badge    = '' !== $scl['badge'] ? $scl['badge'] : ( $scl_off > 0 ? 'Up to -' . $scl_off . '%' : '' );
	$scl_guide    = safestore_cl_guide( $scl['guide'], $scl_items );
	$scl_reviews  = safestore_cl_reviews( safestore_cl_products( $scl ) );
	$scl_delivery = safestore_pl_delivery();
	$scl_faq      = safestore_cl_faq();
	$scl_trust    = safestore_lp_trust();
	$scl_steps    = safestore_lp_steps( array( 'title' => 'ভেস্ট ও পরিমাণ বেছে নিন', 'text' => 'যে ভেস্ট লাগবে তাতে "Add" চাপুন, তারপর কয়টি লাগবে ঠিক করুন — একাধিক ভেস্ট একসাথে নেওয়া যায়।' ) );
	$scl_collage  = array_slice( array_filter( wp_list_pluck( $scl_items, 'image' ) ), 0, 4 );
	$scl_tile     = '(max-width: 899px) 46vw, 280px';

	// ?pick=<slug|ID> from an ad for one vest: one piece of it is pre-added.
	$scl_pick = isset( $_GET['pick'] ) ? sanitize_title( wp_unslash( $_GET['pick'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$scl_qty  = array();
	foreach ( $scl_items as $scl_it ) {
		$scl_qty[ $scl_it['id'] ] = ( '' !== $scl_pick && $scl_it['can_buy'] && ( $scl_pick === $scl_it['slug'] || $scl_pick === (string) $scl_it['id'] ) ) ? 1 : 0;
	}

	$scl_first_img = $scl_collage ? (int) reset( $scl_collage ) : 0;
	safestore_pl_head(
		array(
			'title'       => $scl['headline'] . ' | ' . get_bloginfo( 'name' ),
			'description' => wp_strip_all_tags( $scl['lede'] ),
			'image'       => $scl_first_img ? wp_get_attachment_image_url( $scl_first_img, 'large' ) : '',
			'type'        => 'website',
		),
		$scl['index'],
		$scl_term ? get_term_link( $scl_term ) : home_url( '/' ),
		$scl_first_img ? array(
			'src'    => wp_get_attachment_image_url( $scl_first_img, 'woocommerce_thumbnail' ),
			'srcset' => (string) wp_get_attachment_image_srcset( $scl_first_img, 'woocommerce_thumbnail' ),
			'sizes'  => $scl_tile,
		) : array()
	);

	// Order core (thanas, delivery charge, payment) + the products on this page.
	$scl_js = array_merge(
		safestore_lp_order_cfg( wc_get_product( $scl_buyable ? $scl_buyable[0]['id'] : $scl_items[0]['id'] ) ),
		array(
			'category' => $scl_cat,
			'waText'   => $scl_wa_tpl,
			'bulk'     => SAFESTORE_CL_BULK_QTY,
			'items'    => array(),
		)
	);
	foreach ( $scl_items as $scl_it ) {
		$scl_js['items'][ $scl_it['id'] ] = array(
			'title' => $scl_it['title'],
			'name'  => $scl_it['name'],
			'sku'   => $scl_it['sku'],
			'price' => $scl_it['price']['now'],
			'kg'    => $scl_it['kg'],
			'max'   => $scl_it['max'],
		);
	}
}

get_template_part( 'templates/landing/header', null, array( 'wa' => $scl_wa, 'main' => 'scl-main' ) );
?>
<main id="scl-main">
<?php if ( ! $scl_items ) : ?>

	<section class="lp-section">
		<div class="lp-wrap lp-wrap--narrow spl-empty">
			<h1 class="lp-h2">এই মুহূর্তে পণ্য পাওয়া যাচ্ছে না</h1>
			<p>আমাদের শপে সকল সেফটি পণ্য দেখুন, অথবা WhatsApp-এ যোগাযোগ করুন।</p>
			<?php if ( current_user_can( 'edit_page', $scl_page_id ) ) : ?>
				<p class="lp-alert">Admin: no visible products in category "<?php echo esc_html( $scl['category'] ); ?>". Set the category (or a product list) in the "Category Landing (Facebook)" box on this page.</p>
			<?php endif; ?>
			<div class="lp-cta-row">
				<a class="lp-btn lp-btn--dark" href="<?php echo esc_url( $scl_c['shop'] ); ?>">শপে যান</a>
				<a class="lp-btn lp-btn--wa" href="<?php echo esc_url( $scl_wa ); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
			</div>
		</div>
	</section>

<?php else : ?>

	<!-- 1. HERO: promise + price anchor + the range at a glance ================ -->
	<section class="scl-hero" aria-labelledby="scl-title">
		<div class="lp-wrap scl-hero__grid">
			<div class="scl-collage<?php echo count( $scl_collage ) < 4 ? ' scl-collage--' . count( $scl_collage ) : ''; ?>">
				<?php foreach ( $scl_collage as $scl_i => $scl_img ) : ?>
					<span class="scl-collage__tile">
						<?php
						echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput
							$scl_img,
							'woocommerce_thumbnail',
							false,
							array(
								'loading'       => 0 === $scl_i ? 'eager' : 'lazy',
								'fetchpriority' => 0 === $scl_i ? 'high' : 'auto',
								'decoding'      => 'async',
								'sizes'         => $scl_tile,
								'alt'           => 0 === $scl_i ? $scl_cat : '',
							)
						);
						?>
					</span>
				<?php endforeach; ?>
				<?php if ( '' !== $scl_badge ) : ?>
					<span class="spl-badge"><?php echo esc_html( $scl_badge ); ?></span>
				<?php endif; ?>
			</div>

			<div class="scl-hero__copy">
				<p class="lp-eyebrow"><?php echo esc_html( $scl_cat . ' · ' . count( $scl_items ) . ' রকম' ); ?></p>
				<h1 class="spl-title" id="scl-title"><?php echo esc_html( $scl['headline'] ); ?></h1>
				<p class="spl-lede"><?php echo esc_html( $scl['lede'] ); ?></p>

				<?php if ( $scl_from > 0 ) : ?>
					<p class="spl-price">
						<span class="spl-price__from">From</span>
						<strong><?php echo esc_html( safestore_pl_taka( $scl_from ) ); ?></strong>
						<small>প্রতি পিস · একসাথে যত লাগে অর্ডার করুন</small>
					</p>
				<?php endif; ?>

				<ul class="lp-checks spl-bullets">
					<?php foreach ( array_slice( $scl['bullets'], 0, 3 ) as $scl_b ) : ?>
						<li><?php echo safestore_pl_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $scl_b ); ?></span></li>
					<?php endforeach; ?>
				</ul>

				<div class="scl-hero__cta">
					<a class="lp-btn lp-btn--amber lp-btn--lg" href="#vests">ভেস্ট বেছে নিন <?php echo safestore_pl_icon( 'chevron', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<a class="lp-btn lp-btn--wa lp-btn--lg" href="<?php echo esc_url( $scl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-spl-loc="hero" data-spl-wa>
						<?php echo $scl_c['wa_icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						WhatsApp-এ অর্ডার
					</a>
				</div>

				<ul class="spl-trust" aria-label="কেন SafeStoreBD">
					<?php foreach ( $scl_trust as $scl_t ) : ?>
						<li><?php echo safestore_pl_icon( $scl_t['icon'], 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $scl_t['text'] ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</section>

	<?php if ( $scl_guide ) : ?>
	<!-- 2. WHICH ONE IS FOR YOU? (need → matching vests) ===================== -->
	<section class="lp-section lp-section--tint scl-guide" aria-labelledby="scl-guide-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">বেছে নিতে সাহায্য</p>
				<h2 class="lp-h2" id="scl-guide-title">কোন ভেস্টটি আপনার কাজের জন্য?</h2>
			</header>
			<ul class="lp-grid lp-grid--3">
				<?php foreach ( $scl_guide as $scl_g ) : ?>
					<li class="lp-card scl-guide__item">
						<span class="lp-ico"><?php echo safestore_pl_icon( $scl_g['icon'], 24 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<h3 class="lp-h3"><?php echo esc_html( $scl_g['title'] ); ?></h3>
						<p><?php echo esc_html( $scl_g['text'] ); ?></p>
						<ul class="scl-guide__picks">
							<?php foreach ( $scl_g['picks'] as $scl_p ) : ?>
								<li><a href="#<?php echo esc_attr( $scl_p['anchor'] ); ?>" data-scl-jump="<?php echo (int) $scl_p['id']; ?>"><?php echo esc_html( $scl_p['title'] ); ?> <b><?php echo esc_html( safestore_pl_taka( $scl_p['price']['now'] ) ); ?></b></a></li>
							<?php endforeach; ?>
						</ul>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php endif; ?>

	<!-- 3. PICK VESTS + ORDER FORM (one order for everything picked) ========= -->
	<section class="lp-section scl-shop" id="vests" aria-labelledby="scl-shop-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">অর্ডার করুন</p>
				<h2 class="lp-h2" id="scl-shop-title">ভেস্ট বেছে নিন — একাধিক ভেস্ট একসাথে নেওয়া যায়</h2>
				<p class="lp-lede">"Add" চাপুন, পরিমাণ ঠিক করুন, তারপর পাশের (মোবাইলে নিচের) ফর্মে ঠিকানা দিন।</p>
			</header>

			<div class="scl-shop__grid">
				<div class="scl-list" data-scl-list tabindex="-1">
					<p class="lp-alert scl-list__err" role="alert" data-scl-list-err hidden>Please add at least one vest first.</p>
					<ul class="scl-items">
						<?php foreach ( $scl_items as $scl_i => $scl_it ) : ?>
							<li class="lp-card scl-item<?php echo $scl_it['can_buy'] || ! $scl_it['in_page'] ? '' : ' is-out'; ?><?php echo $scl_qty[ $scl_it['id'] ] ? ' is-picked' : ''; ?>" id="<?php echo esc_attr( $scl_it['anchor'] ); ?>" data-scl-item="<?php echo (int) $scl_it['id']; ?>">
								<a class="scl-item__img" href="<?php echo esc_url( $scl_it['url'] ); ?>" tabindex="-1" aria-hidden="true">
									<?php
									echo $scl_it['image'] // phpcs:ignore WordPress.Security.EscapeOutput
										? wp_get_attachment_image( $scl_it['image'], 'woocommerce_thumbnail', false, array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '', 'sizes' => '(max-width: 599px) 112px, 160px' ) )
										: wc_placeholder_img( 'woocommerce_thumbnail' );
									?>
									<?php if ( '' !== $scl_it['tag'] ) : ?><span class="scl-item__tag"><?php echo esc_html( $scl_it['tag'] ); ?></span><?php endif; ?>
								</a>
								<div class="scl-item__body">
									<h3 class="scl-item__name"><a href="<?php echo esc_url( $scl_it['url'] ); ?>" title="<?php echo esc_attr( $scl_it['name'] ); ?>"><?php echo esc_html( $scl_it['title'] ); ?></a></h3>
									<?php if ( $scl_it['points'] ) : ?>
										<ul class="scl-item__pts">
											<?php foreach ( $scl_it['points'] as $scl_pt ) : ?>
												<li><?php echo safestore_pl_icon( 'check', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $scl_pt ); ?></span></li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
									<div class="scl-item__foot">
										<p class="scl-item__price">
											<strong><?php echo esc_html( ( $scl_it['price']['from'] ? 'From ' : '' ) . safestore_pl_taka( $scl_it['price']['now'] ) ); ?></strong>
											<?php if ( $scl_it['price']['was'] > 0 ) : ?><s><?php echo esc_html( safestore_pl_taka( $scl_it['price']['was'] ) ); ?></s><?php endif; ?>
										</p>
										<?php if ( ! $scl_it['in_page'] ) : ?>
											<a class="lp-btn lp-btn--outline lp-btn--sm" href="<?php echo esc_url( $scl_it['url'] ); ?>">Choose options</a>
										<?php elseif ( ! $scl_it['can_buy'] ) : ?>
											<span class="scl-item__soldout">Sold out</span>
										<?php else : ?>
											<div class="scl-item__act">
												<button type="button" class="lp-btn lp-btn--dark lp-btn--sm scl-add" data-scl-add hidden aria-label="<?php echo esc_attr( 'Add ' . $scl_it['title'] ); ?>">+ Add</button>
												<div class="spl-qty__ctl scl-qty" data-scl-stepper>
													<button type="button" data-scl-step="-1" aria-label="<?php echo esc_attr( 'Decrease quantity: ' . $scl_it['title'] ); ?>">−</button>
													<input type="number" name="scl_qty[<?php echo (int) $scl_it['id']; ?>]" form="spl-order" inputmode="numeric" min="0" max="<?php echo (int) $scl_it['max']; ?>" step="1" value="<?php echo (int) $scl_qty[ $scl_it['id'] ]; ?>" aria-label="<?php echo esc_attr( 'Quantity: ' . $scl_it['title'] ); ?>" data-scl-qty>
													<button type="button" data-scl-step="1" aria-label="<?php echo esc_attr( 'Increase quantity: ' . $scl_it['title'] ); ?>">+</button>
												</div>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="scl-panel">
					<?php if ( '' !== $scl_error ) : ?>
						<p class="lp-alert" role="alert" id="order"><?php echo esc_html( $scl_error ); ?></p>
					<?php endif; ?>
					<?php if ( $scl_buyable ) : ?>
						<form class="spl-order lp-form scl-order" id="spl-order" method="post" action="<?php echo esc_url( get_permalink() ); ?>" data-spl-form>
							<?php if ( '' === $scl_error ) : ?><span id="order" class="spl-anchor" aria-hidden="true"></span><?php endif; ?>
							<p class="spl-ship__title">Your order <small class="scl-order__count" data-scl-count></small></p>
							<p class="scl-bulk" data-scl-bulk hidden>
								<?php echo safestore_pl_icon( 'tag', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span>বড় অর্ডার? ভেস্টে কোম্পানির নাম বা লোগো প্রিন্ট করাতে চাইলে <a href="#bulk">কোটেশন নিন</a>।</span>
							</p>
							<?php
							get_template_part(
								'templates/landing/order-form',
								null,
								array(
									'cfg'   => $scl_js,
									'lines' => array(),
									'empty' => 'No vest added yet — tap "Add" on a vest.',
									'total' => 0,
									'wa'    => $scl_wa,
								)
							);
							?>
						</form>
					<?php else : ?>
						<div class="spl-order">
							<p class="spl-soldout">এই মুহূর্তে সব ভেস্ট স্টকে নেই। স্টক সম্পর্কে জানতে WhatsApp-এ যোগাযোগ করুন।</p>
							<a class="lp-btn lp-btn--wa lp-btn--lg lp-btn--block" href="<?php echo esc_url( $scl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-spl-loc="order" data-spl-wa>
								<?php echo $scl_c['wa_icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
								WhatsApp-এ স্টক জানুন
							</a>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<!-- 4. WHY HI-VIS / WHERE IT IS USED ===================================== -->
	<section class="lp-section lp-section--tint" id="features" aria-labelledby="scl-why-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">কেন সেফটি ভেস্ট</p>
				<h2 class="lp-h2" id="scl-why-title">দেখা না গেলে দুর্ঘটনার ঝুঁকি — ভেস্ট আপনাকে দৃশ্যমান রাখে</h2>
			</header>
			<ul class="lp-grid lp-grid--2 spl-why">
				<?php foreach ( $scl['bullets'] as $scl_b ) : ?>
					<li class="lp-card spl-why__item">
						<span class="lp-ico"><?php echo safestore_pl_icon( 'shield', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<p><?php echo esc_html( $scl_b ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $scl['uses'] ) : ?>
				<p class="spl-uses__label">যেসব কাজে ব্যবহার হয়</p>
				<ul class="spl-uses" aria-label="ব্যবহারের জায়গা">
					<?php foreach ( $scl['uses'] as $scl_u ) : ?>
						<li><?php echo esc_html( $scl_u ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</section>

	<!-- 5. TEAMS: bulk + logo printing (quote, inc/custom-print.php) ========== -->
	<section class="lp-section" id="bulk" aria-labelledby="scl-bulk-title">
		<div class="lp-wrap">
			<div class="lp-card scl-teams">
				<div>
					<p class="lp-eyebrow">অফিস, কারখানা ও কনস্ট্রাকশন টিম</p>
					<h2 class="lp-h2" id="scl-bulk-title">পুরো টিমের জন্য ভেস্ট? কোম্পানির লোগো প্রিন্ট করিয়ে নিন</h2>
					<ul class="lp-checks">
						<li><?php echo safestore_pl_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>এই পেজ থেকেই একসাথে যত পিস লাগে অর্ডার করুন — প্রতিটি ভেস্টে <?php echo esc_html( safestore_pl_bn( SAFESTORE_CL_MAX_QTY ) ); ?> পিস পর্যন্ত</span></li>
						<li><?php echo safestore_pl_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>ভেস্টে কোম্পানির নাম ও লোগো প্রিন্ট — লোগো, পরিমাণ ও রঙ পাঠালে দাম, সর্বনিম্ন পরিমাণ ও সময় জানিয়ে কোটেশন</span></li>
						<li><?php echo safestore_pl_icon( 'check', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>ডিজাইন অনুমোদনের পর প্রিন্ট শুরু · কোম্পানির জন্য ইনভয়েস</span></li>
					</ul>
				</div>
				<div class="scl-teams__cta">
					<a class="lp-btn lp-btn--wa lp-btn--lg lp-btn--block" href="<?php echo esc_url( safestore_lp_wa( 'আসসালামু আলাইকুম SafeStoreBD, আমাদের ' . $scl_cat . '-এ লোগো প্রিন্ট করাতে চাই। পরিমাণ: , রঙ: ' ) ); ?>" target="_blank" rel="noopener noreferrer" data-spl-loc="bulk">
						<?php echo $scl_c['wa_icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						WhatsApp-এ লোগো পাঠান
					</a>
					<?php if ( function_exists( 'safestore_custom_print_url' ) ) : ?>
						<a class="lp-btn lp-btn--outline lp-btn--lg lp-btn--block" href="<?php echo esc_url( safestore_custom_print_url() ); ?>">কোটেশন ফর্ম</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<!-- 6. DELIVERY & PAYMENT ================================================= -->
	<section class="lp-section lp-section--tint" id="delivery" aria-labelledby="scl-delivery-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">ডেলিভারি, পেমেন্ট ও রিটার্ন</p>
				<h2 class="lp-h2" id="scl-delivery-title">অর্ডার করার আগে জেনে নিন</h2>
			</header>
			<ul class="lp-grid lp-grid--3 spl-delivery">
				<?php foreach ( $scl_delivery as $scl_d ) : ?>
					<li class="lp-card spl-delivery__item">
						<span class="lp-ico"><?php echo safestore_pl_icon( $scl_d['icon'], 24 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<h3 class="lp-h3"><?php echo esc_html( $scl_d['title'] ); ?></h3>
						<p><?php echo esc_html( $scl_d['text'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<!-- 7. HOW TO ORDER ======================================================= -->
	<section class="lp-section lp-section--dark" aria-labelledby="scl-steps-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow lp-eyebrow--light">কীভাবে অর্ডার করবেন</p>
				<h2 class="lp-h2" id="scl-steps-title">৩টি সহজ ধাপে অর্ডার করুন</h2>
			</header>
			<ol class="lp-steps">
				<?php foreach ( $scl_steps as $scl_i => $scl_s ) : ?>
					<li class="lp-step">
						<span class="lp-step__n" aria-hidden="true"><?php echo esc_html( safestore_pl_bn( $scl_i + 1 ) ); ?></span>
						<h3 class="lp-h3"><?php echo esc_html( $scl_s['title'] ); ?></h3>
						<p><?php echo esc_html( $scl_s['text'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<?php if ( $scl_reviews['items'] ) : ?>
	<!-- 8. REVIEWS (real WooCommerce reviews of these products only) ========== -->
	<section class="lp-section" id="reviews" aria-labelledby="scl-reviews-title">
		<div class="lp-wrap">
			<header class="lp-head">
				<p class="lp-eyebrow">ক্রেতাদের মতামত</p>
				<h2 class="lp-h2" id="scl-reviews-title">ক্রেতারা যা বলছেন</h2>
				<p class="lp-lede"><?php echo esc_html( safestore_pl_bn( $scl_reviews['count'] ) . 'টি রিভিউর ভিত্তিতে গড় রেটিং ' . safestore_pl_bn( number_format( $scl_reviews['rating'], 1 ) ) . ' / ৫' ); ?></p>
			</header>
			<ul class="lp-grid lp-grid--3">
				<?php foreach ( $scl_reviews['items'] as $scl_r ) : ?>
					<li>
						<figure class="lp-card spl-review">
							<?php if ( $scl_r['rating'] > 0 ) : ?>
								<?php echo safestore_pl_stars( $scl_r['rating'], 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php endif; ?>
							<blockquote><p><?php echo esc_html( $scl_r['text'] ); ?></p></blockquote>
							<figcaption>
								<strong><?php echo esc_html( $scl_r['name'] ); ?></strong>
								<?php if ( $scl_r['verified'] ) : ?><span>ভেরিফাইড ক্রেতা</span><?php endif; ?>
							</figcaption>
						</figure>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php endif; ?>

	<!-- 9. FAQ ================================================================ -->
	<section class="lp-section" id="faq" aria-labelledby="scl-faq-title">
		<div class="lp-wrap lp-wrap--narrow">
			<header class="lp-head">
				<p class="lp-eyebrow">প্রশ্ন ও উত্তর</p>
				<h2 class="lp-h2" id="scl-faq-title">সচরাচর জিজ্ঞাসা</h2>
			</header>
			<div class="lp-faq" data-spl-faq>
				<?php foreach ( $scl_faq as $scl_i => $scl_f ) : ?>
					<div class="lp-faq__item">
						<h3 class="lp-faq__q">
							<button type="button" class="lp-faq__btn" id="scl-faq-btn-<?php echo (int) $scl_i; ?>" aria-expanded="true" aria-controls="scl-faq-panel-<?php echo (int) $scl_i; ?>">
								<span><?php echo esc_html( $scl_f['q'] ); ?></span>
								<?php echo safestore_pl_icon( 'chevron', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</button>
						</h3>
						<div class="lp-faq__a" id="scl-faq-panel-<?php echo (int) $scl_i; ?>" role="region" aria-labelledby="scl-faq-btn-<?php echo (int) $scl_i; ?>">
							<p><?php echo esc_html( $scl_f['a'] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- 10. FINAL CTA ========================================================= -->
	<section class="lp-final" aria-labelledby="scl-final-title">
		<div class="lp-wrap">
			<h2 class="lp-h2" id="scl-final-title"><?php echo esc_html( $scl['headline'] . ( $scl_from > 0 ? ' — From ' . safestore_pl_taka( $scl_from ) : '' ) ); ?></h2>
			<p>ক্যাশ অন ডেলিভারি · সারা বাংলাদেশে · ৭ দিনের রিটার্ন</p>
			<div class="lp-cta-row">
				<?php if ( $scl_buyable ) : ?>
					<a class="lp-btn lp-btn--amber lp-btn--lg" href="#order" data-spl-goto="order">অর্ডার করুন</a>
				<?php endif; ?>
				<a class="lp-btn lp-btn--wa lp-btn--lg" href="<?php echo esc_url( $scl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-spl-loc="final" data-spl-wa>
					<?php echo $scl_c['wa_icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					WhatsApp-এ অর্ডার করুন
				</a>
			</div>
		</div>
	</section>

<?php endif; ?>
</main>

<?php get_template_part( 'templates/landing/footer' ); ?>

<?php if ( $scl_items ) : ?>
<!-- 11. MOBILE STICKY BAR (<768px) =========================================== -->
<nav class="lp-sticky spl-sticky" data-spl-sticky aria-label="দ্রুত অর্ডার">
	<a class="lp-sticky__wa" href="<?php echo esc_url( $scl_wa ); ?>" target="_blank" rel="noopener noreferrer" data-spl-loc="sticky" data-spl-wa aria-label="WhatsApp-এ অর্ডার করুন">
		<?php echo $scl_c['wa_icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</a>
	<?php if ( $scl_buyable ) : ?>
		<button class="lp-sticky__cta" type="submit" form="spl-order" data-spl-goto="order" data-spl-sticky-cta>
			<span data-scl-sticky-label>ভেস্ট বেছে নিন</span>
			<strong data-scl-sticky-total hidden></strong>
		</button>
	<?php else : ?>
		<a class="lp-sticky__cta" href="<?php echo esc_url( 'tel:' . $scl_c['tel'] ); ?>" data-spl-loc="sticky"><?php echo safestore_pl_icon( 'phone', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>কল করুন</span></a>
	<?php endif; ?>
</nav>
<script type="application/json" id="scl-data"><?php echo wp_json_encode( $scl_js, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>

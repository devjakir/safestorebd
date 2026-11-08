<?php
/**
 * Landing pages that take the order on the page — shared PHP (the partner of
 * css/landing-common.css and js/landing-order.js).
 *
 * A landing module registers its template once:
 *
 *     add_filter( 'safestore_lp_templates', fn( $t ) => $t + array( 'page-templates/x.php' => 'x-page' ) );
 *
 * and gets, on that template only: the slim chrome (no floating WhatsApp, no
 * mobile bottom nav, lang="bn-BD", body classes "lp-page" + its own), the
 * slimmed asset set with landing-common.css and the shared order script, the
 * shared header / footer / order-form partials (templates/landing/), and the
 * helpers below (contact facts, trust strip, steps, common FAQ, form values,
 * parcel weight, error redirect, meta-box fields).
 *
 * Formatting helpers (safestore_pl_bn / _taka / _icon / _address / _phone_bn),
 * the form field list, districts, courier data and the order pipeline live in
 * inc/product-landing.php and inc/product-landing-order.php.
 */

defined( 'ABSPATH' ) || exit;

/** Registered order landings: template file => body class. */
function safestore_lp_templates() {
	return (array) apply_filters( 'safestore_lp_templates', array() );
}

/** Body class of the current registered landing, or '' when this is not one. */
function safestore_lp_current() {
	if ( is_admin() ) {
		return '';
	}
	foreach ( safestore_lp_templates() as $template => $class ) {
		if ( is_page_template( $template ) ) {
			return (string) $class;
		}
	}
	return '';
}

/* -------------------------------------------------------------------------
 * Chrome and assets
 * ---------------------------------------------------------------------- */

add_filter( 'safestore_wa_render', static function ( $render ) {
	return '' !== safestore_lp_current() ? false : $render; // the page has its own sticky bar
} );
add_filter( 'safestore_mnav_enabled', static function ( $enabled ) {
	return '' !== safestore_lp_current() ? false : $enabled;
} );
add_filter( 'body_class', static function ( $classes ) {
	$class = safestore_lp_current();
	if ( '' !== $class ) {
		$classes[] = 'lp-page';
		$classes[] = $class;
	}
	return $classes;
} );
add_filter( 'language_attributes', static function ( $output ) {
	return '' !== safestore_lp_current() ? 'lang="bn-BD"' : $output;
} );

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( '' === safestore_lp_current() ) {
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
		}
		$js = get_template_directory() . '/js/landing-order.js';
		if ( file_exists( $js ) ) {
			wp_register_script( 'safestore-landing-order', get_template_directory_uri() . '/js/landing-order.js', array(), (string) filemtime( $js ), safestore_lp_script_args() );
		}
	},
	99 // before the modules' own enqueues at 100
);

function safestore_lp_script_args() {
	return function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true;
}

/**
 * Enqueue a landing's own sheets and script (css/<name>.css, js/<name>.js);
 * the script runs on top of the shared order core.
 *
 * @param string[] $sheets CSS file names without extension, in cascade order.
 * @param string   $script JS file name without extension, or ''.
 */
function safestore_lp_enqueue( $sheets, $script = '' ) {
	if ( function_exists( 'safestore_page_css' ) ) {
		foreach ( (array) $sheets as $sheet ) {
			safestore_page_css( $sheet, $sheet . '.css' );
		}
	}
	$js = get_template_directory() . '/js/' . $script . '.js';
	if ( '' !== $script && file_exists( $js ) ) {
		wp_enqueue_script( 'safestore-' . $script, get_template_directory_uri() . '/js/' . $script . '.js', array( 'safestore-landing-order' ), (string) filemtime( $js ), safestore_lp_script_args() );
	}
}

/* -------------------------------------------------------------------------
 * Facts and copy every order landing shows
 * ---------------------------------------------------------------------- */

/** Phone, WhatsApp, address, email, shop link, logo, hours — one place for header, footer and CTAs. */
function safestore_lp_contact() {
	static $c = null;
	if ( null !== $c ) {
		return $c;
	}
	$emails = function_exists( 'safestore_contact_email_addresses' ) ? safestore_contact_email_addresses() : array( 'contact@safestorebd.com' );
	$c      = array(
		'tel'       => function_exists( 'safestore_primary_phone_e164' ) ? safestore_primary_phone_e164() : '+8801811892291',
		'tel_label' => safestore_pl_phone_bn( function_exists( 'safestore_primary_phone_display' ) ? safestore_primary_phone_display() : '+880 1811-892291' ),
		'wa'        => function_exists( 'safestore_primary_wa_link' ) ? safestore_primary_wa_link() : 'https://wa.me/8801811892291',
		'wa_icon'   => function_exists( 'safestore_wa_icon_svg' ) ? safestore_wa_icon_svg( 'lp-ico-wa' ) : '',
		'address'   => safestore_pl_address(),
		'email'     => $emails ? (string) reset( $emails ) : '',
		'shop'      => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
		'logo'      => function_exists( 'safestore_brand_logo_svg' ) ? safestore_brand_logo_svg() : array( 'url' => '', 'width' => 0, 'height' => 0 ),
		'hours'     => 'শনি–বৃহস্পতি, সকাল ৯টা – রাত ৮টা',
	);
	return $c;
}

/** "{WhatsApp base}?text=…" */
function safestore_lp_wa( $text ) {
	$c = safestore_lp_contact();
	return '' === (string) $text ? $c['wa'] : $c['wa'] . '?text=' . rawurlencode( (string) $text );
}

/** Trust strip under the order block. */
function safestore_lp_trust() {
	return apply_filters(
		'safestore_lp_trust',
		array(
			array( 'icon' => 'card', 'text' => 'ক্যাশ অন ডেলিভারি' ),
			array( 'icon' => 'truck', 'text' => 'ঢাকায় ২৪ ঘণ্টায় ডেলিভারি' ),
			array( 'icon' => 'pin', 'text' => '৬৪ জেলায় ডেলিভারি' ),
			array( 'icon' => 'return', 'text' => '৭ দিনে রিটার্ন ও এক্সচেঞ্জ' ),
		)
	);
}

/** "How to order" — the page's own first step, then the two every landing shares. */
function safestore_lp_steps( $first ) {
	return array(
		$first,
		array( 'title' => 'ঠিকানা দিয়ে অর্ডার কনফার্ম', 'text' => 'এই পেজেই নাম, মোবাইল ও ঠিকানা দিন — জেলা বেছে নিলেই ডেলিভারি চার্জ দেখবেন। তারপর "Confirm order" চাপুন।' ),
		array( 'title' => 'পণ্য দেখে টাকা দিন', 'text' => 'আমরা ফোন করে অর্ডার নিশ্চিত করব। ডেলিভারি প্রতিনিধির সামনে পণ্য যাচাই করে মূল্য দিন। বিকাশ ও নগদেও দেওয়া যায়।' ),
	);
}

/** FAQ answers that hold for every product, keyed so a page can place them. */
function safestore_lp_faq_common() {
	return array(
		'cod'      => array(
			'q' => 'ক্যাশ অন ডেলিভারি সুবিধা আছে কি?',
			'a' => 'হ্যাঁ। পণ্য হাতে পেয়ে যাচাই করার পর মূল্য পরিশোধ করবেন। বিকাশ ও নগদেও পরিশোধ করা যায়।',
		),
		'delivery' => array(
			'q' => 'ডেলিভারিতে কত দিন লাগে, চার্জ কত?',
			'a' => 'ঢাকার ভেতরে সাধারণত ২৪ ঘণ্টার মধ্যে এবং ঢাকার বাইরে ২–৩ কার্যদিবসের মধ্যে ডেলিভারি হয়। অর্ডার ফর্মে জেলা ও থানা বেছে নিলেই ডেলিভারি চার্জ দেখা যাবে। পল্লবী অফিস থেকে বিনা খরচে পণ্য সংগ্রহও করতে পারেন।',
		),
		'new'      => array(
			'q' => 'পণ্য কি নতুন এবং ছবির মতোই?',
			'a' => 'হ্যাঁ, সম্পূর্ণ নতুন পণ্য। ছবিতে যা দেখছেন, সেটিই পাবেন। ডেলিভারির সময় পণ্য যাচাই করে তারপর মূল্য পরিশোধ করুন।',
		),
	);
}

/* -------------------------------------------------------------------------
 * Order helpers
 * ---------------------------------------------------------------------- */

/**
 * Order form values the visitor gave before (logged in, or a failed no-JS
 * post). On a fresh visit a district without a thana is only the store's
 * default address, so it is dropped.
 */
function safestore_lp_form_values() {
	$vals = array();
	foreach ( array_keys( safestore_pl_form_fields() ) as $key ) {
		$vals[ $key ] = function_exists( 'WC' ) && WC()->checkout() ? (string) WC()->checkout()->get_value( $key ) : '';
	}
	if ( '' === $vals['billing_city'] && ! isset( $_GET['spl_err'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$vals['billing_state'] = '';
	}
	return $vals;
}

/**
 * What the order core (js/landing-order.js) needs besides the page's own pick:
 * thanas, the delivery charge source and the payment options.
 *
 * @param WC_Product $product Product whose weight / price prices the delivery
 *                            (the landing's product, or a category's first one).
 */
function safestore_lp_order_cfg( $product ) {
	$ship = safestore_pl_ship_data( $product ); // Steadfast courier, null while it is not live
	return array(
		'currency' => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'BDT',
		'waBase'   => safestore_lp_contact()['wa'],
		'thanas'   => function_exists( 'safestorebd_bd_thanas' ) ? safestorebd_bd_thanas() : array(),
		'ship'     => $ship,
		// Courier not live → what WooCommerce's own zones (Flat rate…) charge.
		'flat'     => $ship ? null : safestore_pl_wc_ship_data( $product ),
		'pay'      => array( 'mfs' => safestore_pl_mfs_available() ),
	);
}

/** Parcel weight of one unit in kg: the product's own, else the courier's default. */
function safestore_lp_product_kg( $product ) {
	$kg = (float) $product->get_weight();
	if ( $kg > 0 ) {
		return (float) wc_get_weight( $kg, 'kg' );
	}
	if ( class_exists( 'SafeStoreBD_Shipping_Courier' ) ) {
		return max( 0.0, (float) ( new SafeStoreBD_Shipping_Courier() )->get_option( 'default_weight' ) );
	}
	return 0.0;
}

/**
 * Error answer for a landing order request: JSON for the page script, else
 * back to the form with ?spl_err=<code> (the template prints it). Exits.
 */
function safestore_lp_fail( $page_id, $ajax ) {
	return static function ( $code ) use ( $page_id, $ajax ) {
		if ( $ajax ) {
			wp_send_json( array( 'result' => 'failure', 'messages' => safestore_pl_error_text( $code ) ) );
		}
		wp_safe_redirect( add_query_arg( 'spl_err', $code, get_permalink( $page_id ) ) . '#order' );
		exit;
	};
}

/* -------------------------------------------------------------------------
 * Page settings meta box fields: key => array( type, label, hint[, checkbox text] )
 * type: text | textarea | checkbox
 * ---------------------------------------------------------------------- */

function safestore_lp_meta_table( $post, $fields ) {
	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( $fields as $key => $f ) {
		list( $type, $label, $hint ) = $f;
		$value = (string) get_post_meta( $post->ID, $key, true );
		$id    = 'lp-' . trim( str_replace( '_', '-', $key ), '-' );
		echo '<tr><th scope="row">';
		echo 'checkbox' === $type ? esc_html( $label ) : '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
		echo '</th><td>';
		if ( 'checkbox' === $type ) {
			echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="1"' . checked( '1', $value, false ) . '> ' . esc_html( isset( $f[3] ) ? $f[3] : $label ) . '</label>';
		} elseif ( 'textarea' === $type ) {
			echo '<textarea class="large-text" rows="4" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( $value ) . '</textarea>';
		} else {
			echo '<input type="text" class="large-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
		}
		echo '<p class="description">' . esc_html( $hint ) . '</p></td></tr>';
	}
	echo '</tbody></table>';
}

/** Save the fields after the caller checked its nonce; empty values are deleted. */
function safestore_lp_meta_save( $post_id, $fields ) {
	foreach ( $fields as $key => $f ) {
		$raw = isset( $_POST[ $key ] ) ? wp_unslash( (string) $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput,WordPress.Security.NonceVerification
		if ( 'checkbox' === $f[0] ) {
			$value = '' !== $raw ? '1' : '';
		} else {
			$value = 'textarea' === $f[0] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		}
		if ( '' === trim( $value ) ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}

/** Text lines of a textarea setting, empty ones dropped. */
function safestore_lp_lines( $raw ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', (string) $raw ) ) ) );
}

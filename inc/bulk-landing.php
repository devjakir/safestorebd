<?php
/**
 * Bulk / corporate order landing page (page-templates/template-bulk-landing.php).
 *
 * - "bulk_lead" private post type so no quote request is ever lost.
 * - One submit handler for both AJAX (admin-ajax.php) and the no-JS POST
 *   fallback (admin-post.php): nonce, honeypot, rate limit, BD phone check,
 *   optional item-list upload into a private folder, email to the store.
 * - Template-only assets; the floating WhatsApp widget and the mobile bottom
 *   nav are switched off on this template (it has its own sticky bar).
 */

defined( 'ABSPATH' ) || exit;

define( 'SAFESTORE_BL_TEMPLATE', 'page-templates/template-bulk-landing.php' );
define( 'SAFESTORE_BL_ACTION', 'safestore_bulk_lead' );
define( 'SAFESTORE_BL_MAX_UPLOAD', 5 * MB_IN_BYTES );

function safestore_bl_is_landing() {
	return ! is_admin() && is_page_template( SAFESTORE_BL_TEMPLATE );
}

/* -------------------------------------------------------------------------
 * Small helpers
 * ---------------------------------------------------------------------- */

/** Western → Bangla digits ("1,250" → "১,২৫০"). */
function safestore_bl_bn( $value ) {
	return strtr( (string) $value, array( '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯' ) );
}

/** "৳১,২৫০" from a number, or '' when the value is empty / not numeric. */
function safestore_bl_taka( $amount ) {
	if ( '' === $amount || null === $amount || ! is_numeric( $amount ) ) {
		return '';
	}
	return '৳' . safestore_bl_bn( number_format( (float) $amount, 0, '.', ',' ) );
}

/** 01XXXXXXXXX or '' (accepts +880 / 880 prefixes, spaces, dashes, Bangla digits). */
function safestore_bl_clean_phone( $raw ) {
	$digits = function_exists( 'safestorebd_normalize_bd_phone' )
		? safestorebd_normalize_bd_phone( $raw )
		: (string) preg_replace( '/\D+/', '', (string) $raw );
	return preg_match( '/^01[3-9]\d{8}$/', $digits ) ? $digits : '';
}

/** All 64 districts, grouped by division (Bangla). */
function safestore_bl_districts() {
	return array(
		'ঢাকা বিভাগ'      => array( 'ঢাকা', 'গাজীপুর', 'নারায়ণগঞ্জ', 'নরসিংদী', 'মানিকগঞ্জ', 'মুন্সিগঞ্জ', 'টাঙ্গাইল', 'কিশোরগঞ্জ', 'ফরিদপুর', 'গোপালগঞ্জ', 'মাদারীপুর', 'রাজবাড়ী', 'শরীয়তপুর' ),
		'চট্টগ্রাম বিভাগ'  => array( 'চট্টগ্রাম', 'কক্সবাজার', 'কুমিল্লা', 'ফেনী', 'নোয়াখালী', 'লক্ষ্মীপুর', 'চাঁদপুর', 'ব্রাহ্মণবাড়িয়া', 'রাঙ্গামাটি', 'খাগড়াছড়ি', 'বান্দরবান' ),
		'রাজশাহী বিভাগ'   => array( 'রাজশাহী', 'নাটোর', 'নওগাঁ', 'চাঁপাইনবাবগঞ্জ', 'পাবনা', 'সিরাজগঞ্জ', 'বগুড়া', 'জয়পুরহাট' ),
		'খুলনা বিভাগ'     => array( 'খুলনা', 'যশোর', 'সাতক্ষীরা', 'বাগেরহাট', 'নড়াইল', 'মাগুরা', 'ঝিনাইদহ', 'কুষ্টিয়া', 'চুয়াডাঙ্গা', 'মেহেরপুর' ),
		'বরিশাল বিভাগ'    => array( 'বরিশাল', 'পটুয়াখালী', 'ভোলা', 'পিরোজপুর', 'ঝালকাঠি', 'বরগুনা' ),
		'সিলেট বিভাগ'     => array( 'সিলেট', 'মৌলভীবাজার', 'হবিগঞ্জ', 'সুনামগঞ্জ' ),
		'রংপুর বিভাগ'     => array( 'রংপুর', 'দিনাজপুর', 'ঠাকুরগাঁও', 'পঞ্চগড়', 'নীলফামারী', 'লালমনিরহাট', 'কুড়িগ্রাম', 'গাইবান্ধা' ),
		'ময়মনসিংহ বিভাগ'  => array( 'ময়মনসিংহ', 'জামালপুর', 'শেরপুর', 'নেত্রকোণা' ),
	);
}

function safestore_bl_is_district( $name ) {
	foreach ( safestore_bl_districts() as $list ) {
		if ( in_array( $name, $list, true ) ) {
			return true;
		}
	}
	return false;
}

/** Simple 24px line icons (stroke = currentColor). */
function safestore_bl_icon( $name, $size = 24 ) {
	$paths = array(
		'factory'   => '<path d="M2 20V9l6 4V9l6 4V4h6v16z"/><path d="M2 20h20"/><path d="M7 17h1M12 17h1M17 17h1"/>',
		'hardhat'   => '<path d="M3 18h18v-2a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1z"/><path d="M4 15v-2a8 8 0 0 1 5-7.4"/><path d="M15 5.6A8 8 0 0 1 20 13v2"/><path d="M9 10V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v5"/>',
		'warehouse' => '<path d="M22 20V8.4a2 2 0 0 0-1.3-1.9l-8-3.2a2 2 0 0 0-1.4 0l-8 3.2A2 2 0 0 0 2 8.4V20"/><path d="M6 20v-9h12v9"/><path d="M6 14h12M6 17h12"/>',
		'bolt'      => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
		'phone'     => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
		'check'     => '<path d="M20 6 9 17l-5-5"/>',
		'invoice'   => '<path d="M5 2v20l2.3-1.5L9.7 22l2.3-1.5 2.3 1.5 2.4-1.5L19 22V2l-2.3 1.5L14.3 2 12 3.5 9.7 2 7.3 3.5z"/><path d="M9 8h6M9 12h6M9 16h4"/>',
		'spec'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>',
		'packing'   => '<path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.7z"/><path d="M3.3 7 12 12l8.7-5M12 22V12"/>',
		'pin'       => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
		'truck'     => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.7a1 1 0 0 0-.2-.6l-3.5-4.3a1 1 0 0 0-.8-.4H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
		'list'      => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/>',
		'quote'     => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h5"/>',
		'box'       => '<path d="M16.5 9.4 7.5 4.2"/><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.7z"/><path d="M3.3 7 12 12l8.7-5M12 22V12"/>',
		'card'      => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
		'return'    => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>',
		'search'    => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
		'shield'    => '<path d="M12 22s8-3.5 8-10V5l-8-3-8 3v7c0 6.5 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
		'upload'    => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5M12 3v12"/>',
		'chevron'   => '<path d="m6 9 6 6 6-6"/>',
		'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'tag'       => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
		'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'ruler'     => '<path d="M21.3 8.7 8.7 21.3a1 1 0 0 1-1.4 0l-4.6-4.6a1 1 0 0 1 0-1.4L15.3 2.7a1 1 0 0 1 1.4 0l4.6 4.6a1 1 0 0 1 0 1.4z"/><path d="m7.5 10.5 2 2M10.5 7.5l2 2M13.5 4.5l2 2M4.5 13.5l2 2"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="sbl-icon" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
		(int) $size,
		$paths[ $name ]
	);
}

/* -------------------------------------------------------------------------
 * Lead post type
 * ---------------------------------------------------------------------- */

add_action(
	'init',
	static function () {
		register_post_type(
			'bulk_lead',
			array(
				'labels'              => array(
					'name'          => __( 'Bulk Leads', 'safestore-minimal' ),
					'singular_name' => __( 'Bulk Lead', 'safestore-minimal' ),
					'menu_name'     => __( 'Bulk Leads', 'safestore-minimal' ),
					'edit_item'     => __( 'Bulk lead', 'safestore-minimal' ),
					'search_items'  => __( 'Search leads', 'safestore-minimal' ),
					'not_found'     => __( 'No leads yet.', 'safestore-minimal' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'menu_position'       => 56,
				'menu_icon'           => 'dashicons-clipboard',
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
				'capabilities'        => array( 'create_posts' => 'do_not_allow' ), // leads only come from the form
				'map_meta_cap'        => true,
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}
);

function safestore_bl_lead_fields() {
	return array(
		'_sbl_company'  => __( 'Company', 'safestore-minimal' ),
		'_sbl_name'     => __( 'Contact name', 'safestore-minimal' ),
		'_sbl_phone'    => __( 'Phone', 'safestore-minimal' ),
		'_sbl_district' => __( 'District', 'safestore-minimal' ),
		'_sbl_kit'      => __( 'Kit', 'safestore-minimal' ),
		'_sbl_source'   => __( 'Page URL', 'safestore-minimal' ),
	);
}

add_action(
	'add_meta_boxes_bulk_lead',
	static function () {
		add_meta_box( 'sbl-lead-details', __( 'Lead details', 'safestore-minimal' ), 'safestore_bl_render_lead_box', 'bulk_lead', 'normal', 'high' );
	}
);

function safestore_bl_render_lead_box( $post ) {
	echo '<table class="widefat striped"><tbody>';
	foreach ( safestore_bl_lead_fields() as $key => $label ) {
		$value = (string) get_post_meta( $post->ID, $key, true );
		if ( '_sbl_source' === $key && '' !== $value ) {
			$value_html = '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener">' . esc_html( $value ) . '</a>';
		} elseif ( '_sbl_phone' === $key && '' !== $value ) {
			$value_html = '<a href="' . esc_url( 'tel:' . $value ) . '">' . esc_html( $value ) . '</a> &nbsp;·&nbsp; <a href="' . esc_url( 'https://wa.me/88' . $value ) . '" target="_blank" rel="noopener">WhatsApp</a>';
		} else {
			$value_html = '' !== $value ? esc_html( $value ) : '—';
		}
		printf( '<tr><th style="width:140px">%s</th><td>%s</td></tr>', esc_html( $label ), $value_html ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	printf(
		'<tr><th>%s</th><td style="white-space:pre-wrap">%s</td></tr>',
		esc_html__( 'Items & quantity', 'safestore-minimal' ),
		esc_html( $post->post_content )
	);
	$file = (string) get_post_meta( $post->ID, '_sbl_file', true );
	if ( '' !== $file ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=safestore_bl_file&lead=' . $post->ID ), 'sbl_file_' . $post->ID );
		printf(
			'<tr><th>%s</th><td><a class="button" href="%s">%s</a></td></tr>',
			esc_html__( 'Attachment', 'safestore-minimal' ),
			esc_url( $url ),
			esc_html( preg_replace( '/^[A-Za-z0-9]{12}-/', '', basename( $file ) ) )
		);
	}
	$mailed = get_post_meta( $post->ID, '_sbl_mail_sent', true );
	printf(
		'<tr><th>%s</th><td>%s</td></tr>',
		esc_html__( 'Email sent', 'safestore-minimal' ),
		$mailed ? esc_html__( 'Yes', 'safestore-minimal' ) : '<strong style="color:#b91c1c">' . esc_html__( 'No — follow up from here', 'safestore-minimal' ) . '</strong>'
	);
	echo '</tbody></table>';
}

add_filter(
	'manage_bulk_lead_posts_columns',
	static function ( $columns ) {
		$date = isset( $columns['date'] ) ? $columns['date'] : __( 'Date', 'safestore-minimal' );
		unset( $columns['date'] );
		$columns['sbl_phone']    = __( 'Phone', 'safestore-minimal' );
		$columns['sbl_district'] = __( 'District', 'safestore-minimal' );
		$columns['sbl_kit']      = __( 'Kit', 'safestore-minimal' );
		$columns['date']         = $date;
		return $columns;
	}
);

add_action(
	'manage_bulk_lead_posts_custom_column',
	static function ( $column, $post_id ) {
		$map = array( 'sbl_phone' => '_sbl_phone', 'sbl_district' => '_sbl_district', 'sbl_kit' => '_sbl_kit' );
		if ( isset( $map[ $column ] ) ) {
			$value = (string) get_post_meta( $post_id, $map[ $column ], true );
			echo esc_html( '' !== $value ? $value : '—' );
		}
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * Private upload folder (item lists can hold company details)
 * ---------------------------------------------------------------------- */

function safestore_bl_private_dir() {
	$uploads = wp_upload_dir( null, false );
	$dir     = trailingslashit( $uploads['basedir'] ) . 'bulk-leads';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	// Apache / LiteSpeed (Hostinger) refuse direct requests; files are served
	// to admins through safestore_bl_file below. Names are random as well.
	if ( ! file_exists( $dir . '/.htaccess' ) ) {
		file_put_contents( $dir . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	if ( ! file_exists( $dir . '/index.php' ) ) {
		file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $dir;
}

function safestore_bl_upload_mimes() {
	return array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
		'pdf'          => 'application/pdf',
		'xls'          => 'application/vnd.ms-excel',
		'xlsx'         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'csv'          => 'text/csv',
	);
}

/** @return array{path?:string,error?:string} */
function safestore_bl_store_upload( $file ) {
	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	$dir    = safestore_bl_private_dir();
	$filter = static function ( $u ) use ( $dir ) {
		$u['subdir'] = '/bulk-leads';
		$u['path']   = $dir;
		$u['url']    = trailingslashit( $u['baseurl'] ) . 'bulk-leads';
		return $u;
	};

	$file['name'] = wp_generate_password( 12, false ) . '-' . sanitize_file_name( $file['name'] );

	add_filter( 'upload_dir', $filter );
	$result = wp_handle_upload(
		$file,
		array(
			'test_form' => false,
			'mimes'     => safestore_bl_upload_mimes(),
		)
	);
	remove_filter( 'upload_dir', $filter );

	if ( empty( $result['file'] ) || ! empty( $result['error'] ) ) {
		return array( 'error' => isset( $result['error'] ) ? (string) $result['error'] : 'upload failed' );
	}
	return array( 'path' => $result['file'] );
}

add_action(
	'admin_post_safestore_bl_file',
	static function () {
		$lead = isset( $_GET['lead'] ) ? absint( $_GET['lead'] ) : 0;
		check_admin_referer( 'sbl_file_' . $lead );
		if ( ! $lead || 'bulk_lead' !== get_post_type( $lead ) || ! current_user_can( 'edit_post', $lead ) ) {
			wp_die( esc_html__( 'Not allowed.', 'safestore-minimal' ), 403 );
		}
		$path = realpath( (string) get_post_meta( $lead, '_sbl_file', true ) );
		$dir  = realpath( safestore_bl_private_dir() );
		if ( ! $path || ! $dir || 0 !== strpos( $path, $dir . DIRECTORY_SEPARATOR ) || ! is_file( $path ) ) {
			wp_die( esc_html__( 'File not found.', 'safestore-minimal' ), 404 );
		}
		$type = wp_check_filetype( $path, safestore_bl_upload_mimes() );
		nocache_headers();
		header( 'Content-Type: ' . ( $type['type'] ? $type['type'] : 'application/octet-stream' ) );
		header( 'Content-Disposition: attachment; filename="' . rawurlencode( preg_replace( '/^[A-Za-z0-9]{12}-/', '', basename( $path ) ) ) . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
);

/* -------------------------------------------------------------------------
 * Form submit — AJAX + no-JS fallback share one handler
 * ---------------------------------------------------------------------- */

function safestore_bl_messages() {
	return array(
		'company'  => 'কোম্পানির নাম লিখুন।',
		'name'     => 'আপনার নাম লিখুন।',
		'phone'    => 'সঠিক মোবাইল নম্বর দিন — ১১ ডিজিট, যেমন 01712345678।',
		'district' => 'ডেলিভারির জেলা বেছে নিন।',
		'items'    => 'কী কী লাগবে ও কত পিস — লিখুন।',
		'file'     => 'শুধু ছবি, PDF বা Excel ফাইল দিন — সর্বোচ্চ ৫MB।',
		'nonce'    => 'পেজটির মেয়াদ শেষ হয়ে গেছে। পেজ রিফ্রেশ করে আবার পাঠান, অথবা WhatsApp-এ লিখুন।',
		'rate'     => 'অল্প সময়ে অনেকবার চেষ্টা হয়েছে। কিছুক্ষণ পরে আবার চেষ্টা করুন বা WhatsApp-এ লিখুন।',
		'server'   => 'দুঃখিত, এখন পাঠানো যায়নি। আবার চেষ্টা করুন বা WhatsApp-এ লিখুন।',
		'success'  => 'ধন্যবাদ! আমাদের টিম শিগগিরই আপনার সাথে যোগাযোগ করবে।',
	);
}

function safestore_bl_client_ip() {
	foreach ( array( 'HTTP_CF_CONNECTING_IP', 'REMOTE_ADDR' ) as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) ) {
			$ip = filter_var( wp_unslash( $_SERVER[ $key ] ), FILTER_VALIDATE_IP ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( $ip ) {
				return $ip;
			}
		}
	}
	return '0.0.0.0';
}

function safestore_bl_post( $key, $max = 200, $multiline = false ) {
	$raw = isset( $_POST[ $key ] ) ? wp_unslash( (string) $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	$val = $multiline ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
	return mb_substr( trim( $val ), 0, $max );
}

function safestore_bl_handle_submit() {
	$ajax     = wp_doing_ajax();
	$messages = safestore_bl_messages();
	$return   = isset( $_POST['sbl_return'] ) ? get_permalink( absint( $_POST['sbl_return'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$return   = $return ? $return : home_url( '/' );

	$old = array(
		'company'  => safestore_bl_post( 'sbl_company', 150 ),
		'name'     => safestore_bl_post( 'sbl_name', 100 ),
		'phone'    => safestore_bl_post( 'sbl_phone', 30 ),
		'district' => safestore_bl_post( 'sbl_district', 60 ),
		'items'    => safestore_bl_post( 'sbl_items', 3000, true ),
		'kit'      => safestore_bl_post( 'sbl_kit', 80 ),
	);

	$fail = static function ( $errors, $status = 422 ) use ( $ajax, $return, $old ) {
		if ( $ajax ) {
			wp_send_json_error( array( 'errors' => $errors ), $status );
		}
		$token = wp_generate_password( 16, false );
		set_transient( 'sbl_err_' . $token, array( 'errors' => $errors, 'old' => $old ), 15 * MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'sbl', $token, $return ) . '#quote' );
		exit;
	};

	$nonce = isset( $_POST['sbl_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sbl_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, SAFESTORE_BL_ACTION ) ) {
		$fail( array( 'form' => $messages['nonce'] ), 403 );
	}

	// Honeypot: real visitors never see this field. Pretend it worked.
	if ( '' !== safestore_bl_post( 'sbl_website', 200 ) ) {
		if ( $ajax ) {
			wp_send_json_success( array( 'message' => $messages['success'] ) );
		}
		wp_safe_redirect( add_query_arg( 'sbl', 'ok', $return ) . '#quote' );
		exit;
	}

	$rate_key = 'sbl_rl_' . md5( safestore_bl_client_ip() );
	$attempts = (int) get_transient( $rate_key );
	if ( $attempts >= 8 ) {
		$fail( array( 'form' => $messages['rate'] ), 429 );
	}
	set_transient( $rate_key, $attempts + 1, 10 * MINUTE_IN_SECONDS );

	$errors = array();
	$phone  = safestore_bl_clean_phone( $old['phone'] );
	if ( '' === $old['company'] ) {
		$errors['company'] = $messages['company'];
	}
	if ( '' === $old['name'] ) {
		$errors['name'] = $messages['name'];
	}
	if ( '' === $phone ) {
		$errors['phone'] = $messages['phone'];
	}
	if ( ! safestore_bl_is_district( $old['district'] ) ) {
		$errors['district'] = $messages['district'];
	}
	if ( '' === $old['items'] ) {
		$errors['items'] = $messages['items'];
	}

	$file = isset( $_FILES['sbl_file'] ) && is_array( $_FILES['sbl_file'] ) ? $_FILES['sbl_file'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$has_file = $file && isset( $file['error'] ) && UPLOAD_ERR_NO_FILE !== (int) $file['error'];
	if ( $has_file && ( UPLOAD_ERR_OK !== (int) $file['error'] || (int) $file['size'] > SAFESTORE_BL_MAX_UPLOAD ) ) {
		$errors['file'] = $messages['file'];
	}

	if ( $errors ) {
		$fail( $errors );
	}

	$stored = '';
	if ( $has_file ) {
		$upload = safestore_bl_store_upload( $file );
		if ( isset( $upload['error'] ) ) {
			$fail( array( 'file' => $messages['file'] ) );
		}
		$stored = $upload['path'];
	}

	$source = isset( $_POST['sbl_source'] ) ? esc_url_raw( wp_unslash( $_POST['sbl_source'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$lead   = array(
		'_sbl_company'  => $old['company'],
		'_sbl_name'     => $old['name'],
		'_sbl_phone'    => $phone,
		'_sbl_district' => $old['district'],
		'_sbl_kit'      => $old['kit'],
		'_sbl_source'   => mb_substr( $source, 0, 500 ),
	);

	// wp_insert_post() expects slashed data (it unslashes); without wp_slash a
	// Windows upload path such as E:\Local Sites\... loses its backslashes.
	$post_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'bulk_lead',
				'post_status'  => 'private',
				'post_title'   => sprintf( '%s — %s (%s)', $old['company'], $old['name'], $phone ),
				'post_content' => $old['items'],
				'meta_input'   => array_merge( $lead, $stored ? array( '_sbl_file' => $stored ) : array() ),
			)
		),
		true
	);
	$post_id = is_wp_error( $post_id ) ? 0 : (int) $post_id;

	$mailed = safestore_bl_send_mail( $post_id, $lead, $old['items'], $stored );
	if ( $post_id ) {
		update_post_meta( $post_id, '_sbl_mail_sent', $mailed ? 1 : 0 );
	}

	if ( ! $post_id && ! $mailed ) {
		$fail( array( 'form' => $messages['server'] ), 500 );
	}

	do_action( 'safestore_bulk_lead_created', $post_id, $lead );

	if ( $ajax ) {
		wp_send_json_success( array( 'message' => $messages['success'] ) );
	}
	wp_safe_redirect( add_query_arg( 'sbl', 'ok', $return ) . '#quote' );
	exit;
}

foreach ( array( 'wp_ajax_', 'wp_ajax_nopriv_', 'admin_post_', 'admin_post_nopriv_' ) as $sbl_hook ) {
	add_action( $sbl_hook . SAFESTORE_BL_ACTION, 'safestore_bl_handle_submit' );
}
unset( $sbl_hook );

// Fresh nonce at submit time, so a page served from LiteSpeed / Cloudflare
// cache for longer than the nonce lifetime still submits.
foreach ( array( 'wp_ajax_', 'wp_ajax_nopriv_' ) as $sbl_hook ) {
	add_action(
		$sbl_hook . 'safestore_bulk_lead_nonce',
		static function () {
			nocache_headers();
			wp_send_json_success( array( 'nonce' => wp_create_nonce( SAFESTORE_BL_ACTION ) ) );
		}
	);
}
unset( $sbl_hook );

function safestore_bl_send_mail( $post_id, $lead, $items, $file ) {
	$to = apply_filters( 'safestore_bulk_lead_recipients', get_option( 'admin_email' ) );

	$lines = array(
		'নতুন বাল্ক কোটেশন রিকোয়েস্ট',
		'',
		'কোম্পানি: ' . $lead['_sbl_company'],
		'নাম: ' . $lead['_sbl_name'],
		'ফোন: ' . $lead['_sbl_phone'],
		'WhatsApp: https://wa.me/88' . $lead['_sbl_phone'],
		'জেলা: ' . $lead['_sbl_district'],
	);
	if ( '' !== $lead['_sbl_kit'] ) {
		$lines[] = 'কিট: ' . $lead['_sbl_kit'];
	}
	$lines[] = '';
	$lines[] = 'কী কী লাগবে ও কত পিস:';
	$lines[] = $items;
	$lines[] = '';
	$lines[] = 'ফাইল: ' . ( $file ? 'সংযুক্ত' : 'নেই' );
	$lines[] = 'পেজ: ' . $lead['_sbl_source'];
	$lines[] = 'সময়: ' . wp_date( 'd M Y, h:i A' );
	if ( $post_id ) {
		$lines[] = 'অ্যাডমিনে দেখুন: ' . admin_url( 'post.php?post=' . $post_id . '&action=edit' );
	}

	$subject = sprintf( 'বাল্ক কোটেশন: %s (%s)', $lead['_sbl_company'], $lead['_sbl_district'] );
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	return (bool) wp_mail( $to, $subject, implode( "\n", $lines ), $headers, $file ? array( $file ) : array() );
}

/** State for the template after a no-JS submit: 'ok', or errors + old values. */
function safestore_bl_form_state() {
	$state = array( 'status' => '', 'errors' => array(), 'old' => array() );
	$flag  = isset( $_GET['sbl'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', (string) wp_unslash( $_GET['sbl'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	if ( 'ok' === $flag ) {
		$state['status'] = 'ok';
	} elseif ( '' !== $flag ) {
		$saved = get_transient( 'sbl_err_' . $flag );
		if ( is_array( $saved ) ) {
			$state['status'] = 'error';
			$state['errors'] = (array) $saved['errors'];
			$state['old']    = (array) $saved['old'];
		}
	}
	return $state;
}

/* -------------------------------------------------------------------------
 * Category cards: lowest in-stock price per category (cached)
 * ---------------------------------------------------------------------- */

function safestore_bl_category_min_price( $term_id ) {
	global $wpdb;
	$ids   = array_map( 'intval', array_merge( array( (int) $term_id ), (array) get_term_children( (int) $term_id, 'product_cat' ) ) );
	$table = $wpdb->prefix . 'wc_product_meta_lookup';
	$in    = implode( ',', array_filter( $ids ) );
	if ( '' === $in ) {
		return 0;
	}
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$min = $wpdb->get_var(
		"SELECT MIN(l.min_price) FROM {$table} l
		INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = l.product_id
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_cat'
		INNER JOIN {$wpdb->posts} p ON p.ID = l.product_id AND p.post_status = 'publish'
		WHERE tt.term_id IN ({$in}) AND l.min_price > 0 AND l.stock_status <> 'outofstock'"
	);
	return $min ? (float) $min : 0;
}

/**
 * @param array $items key => array( 'label' => Bangla name, 'image' => theme asset, 'w' => int, 'h' => int )
 * @return array[] url, name, image_html, price ('' when unknown)
 */
function safestore_bl_category_cards( $items ) {
	$prices = get_transient( 'safestore_bl_cat_prices' );
	if ( ! is_array( $prices ) ) {
		$prices = array();
	}
	$dirty = false;
	$cards = array();

	foreach ( $items as $key => $item ) {
		$slug = function_exists( 'safestore_home_category_slug' ) ? safestore_home_category_slug( $key ) : $key;
		$term = taxonomy_exists( 'product_cat' ) ? get_term_by( 'slug', $slug, 'product_cat' ) : false;
		$url  = function_exists( 'safestore_home_category_url' ) ? safestore_home_category_url( $key ) : home_url( '/shop/' );

		if ( $term && ! array_key_exists( $key, $prices ) && class_exists( 'WooCommerce' ) ) {
			$prices[ $key ] = safestore_bl_category_min_price( $term->term_id );
			$dirty          = true;
		}

		$alt   = $item['label'] . ( $term ? ' — ' . $term->name : '' );
		$thumb = $term ? (int) get_term_meta( $term->term_id, 'thumbnail_id', true ) : 0;
		$image = $thumb ? wp_get_attachment_image(
			$thumb,
			'woocommerce_thumbnail',
			false,
			array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => $alt, 'sizes' => '(max-width: 767px) 45vw, 220px' )
		) : '';
		if ( '' === $image ) {
			$image = sprintf(
				'<img src="%s" width="%d" height="%d" alt="%s" loading="lazy" decoding="async">',
				esc_url( get_template_directory_uri() . '/assets/images/' . rawurlencode( $item['image'] ) ),
				(int) $item['w'],
				(int) $item['h'],
				esc_attr( $alt )
			);
		}

		$cards[] = array(
			'key'   => $key,
			'url'   => $url,
			'name'  => $item['label'],
			'term'  => $term ? $term->name : '', // shop's own (English) category name
			'image' => $image,
			'price' => ! empty( $prices[ $key ] ) ? safestore_bl_taka( ceil( $prices[ $key ] ) ) : '',
		);
	}

	if ( $dirty ) {
		set_transient( 'safestore_bl_cat_prices', $prices, 6 * HOUR_IN_SECONDS );
	}
	return $cards;
}

/* -------------------------------------------------------------------------
 * Kits: retail "from" price built from real shop products, so the page
 * always shows what the store actually charges today.
 * ---------------------------------------------------------------------- */

/** Cheapest visible, in-stock product among $slugs: array( price, url ) or null. */
function safestore_bl_cheapest_product( $slugs ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return null;
	}
	$best = null;
	foreach ( (array) $slugs as $slug ) {
		$post = get_page_by_path( sanitize_title( $slug ), OBJECT, 'product' );
		$product = $post ? wc_get_product( $post ) : null;
		if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_in_stock() ) {
			continue;
		}
		$price = (float) $product->get_price(); // variable products: lowest variation price
		if ( $price > 0 && ( null === $best || $price < $best['price'] ) ) {
			$best = array( 'price' => $price, 'url' => get_permalink( $product->get_id() ) );
		}
	}
	return $best;
}

/**
 * @param array $kits Template kit config; each item may list 'products' => slugs.
 * @return array kit key => array( 'total' => float (0 if any item is unpriced), 'items' => array( i => cheapest|null ) )
 */
function safestore_bl_kit_retail( $kits ) {
	$key    = 'safestore_bl_kit_prices_' . md5( wp_json_encode( wp_list_pluck( $kits, 'items' ) ) );
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$out = array();
	foreach ( $kits as $kit_key => $kit ) {
		$total = 0.0;
		$items = array();
		foreach ( $kit['items'] as $i => $item ) {
			$items[ $i ] = empty( $item['products'] ) ? null : safestore_bl_cheapest_product( $item['products'] );
			$total       = ( null === $items[ $i ] || $total < 0 ) ? -1 : $total + $items[ $i ]['price'];
		}
		$out[ $kit_key ] = array( 'total' => max( 0, $total ), 'items' => $items );
	}
	set_transient( $key, $out, 6 * HOUR_IN_SECONDS );
	update_option( 'safestore_bl_kit_price_key', $key, false );
	return $out;
}

foreach ( array( 'woocommerce_update_product', 'woocommerce_new_product', 'woocommerce_product_set_stock', 'woocommerce_variation_set_stock' ) as $sbl_hook ) {
	add_action(
		$sbl_hook,
		static function () {
			delete_transient( 'safestore_bl_cat_prices' );
			$kit_key = get_option( 'safestore_bl_kit_price_key' );
			if ( $kit_key ) {
				delete_transient( $kit_key );
			}
		}
	);
}
unset( $sbl_hook );

/* -------------------------------------------------------------------------
 * Head: title, description, canonical, FAQPage schema
 * ---------------------------------------------------------------------- */

/** Called from the template before wp_head(). */
function safestore_bl_seo( $seo, $faq ) {
	$has_seo_plugin = defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' );

	if ( ! $has_seo_plugin ) {
		add_filter(
			'pre_get_document_title',
			static function () use ( $seo ) {
				return $seo['title'];
			},
			20
		);
	}

	// Rank Math: only fill the description when the page has none of its own.
	add_filter(
		'rank_math/frontend/description',
		static function ( $desc ) use ( $seo ) {
			return '' !== trim( (string) $desc ) ? $desc : $seo['description'];
		}
	);

	add_action(
		'wp_head',
		static function () use ( $seo, $faq, $has_seo_plugin ) {
			if ( ! $has_seo_plugin ) {
				printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $seo['description'] ) );
				printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( get_permalink() ) );
				printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $seo['title'] ) );
				printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $seo['description'] ) );
				printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( get_permalink() ) );
				if ( ! empty( $seo['image'] ) ) {
					printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $seo['image'] ) );
				}
			}

			$entities = array();
			foreach ( $faq as $item ) {
				$entities[] = array(
					'@type'          => 'Question',
					'name'           => wp_strip_all_tags( $item['q'] ),
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => wp_strip_all_tags( $item['a'] ),
					),
				);
			}
			if ( $entities ) {
				$schema = array(
					'@context'   => 'https://schema.org',
					'@type'      => 'FAQPage',
					'mainEntity' => $entities,
				);
				echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
			}
		},
		3
	);
}

/* -------------------------------------------------------------------------
 * Bangla / English pair: /bn/bulk-orders/ (this template) ↔ /bulk-orders/
 * ---------------------------------------------------------------------- */

/** Published page IDs of the language pair: array( 'en' => id, 'bn' => id ), either may be 0. */
function safestore_bl_language_pair() {
	static $pair = null;
	if ( null !== $pair ) {
		return $pair;
	}
	$en = get_page_by_path( 'bulk-orders' );
	$bn = get_page_by_path( 'bn/bulk-orders' );
	$pair = apply_filters(
		'safestore_bulk_language_pair',
		array(
			'en' => ( $en && 'publish' === $en->post_status ) ? (int) $en->ID : 0,
			'bn' => ( $bn && 'publish' === $bn->post_status ) ? (int) $bn->ID : 0,
		)
	);
	return $pair;
}

// hreflang on both pages so Google shows the Bangla page for Bangla searches
// and the English one for English searches instead of making them compete.
add_action(
	'wp_head',
	static function () {
		if ( ! is_page() ) {
			return;
		}
		$pair = safestore_bl_language_pair();
		if ( ! $pair['en'] || ! $pair['bn'] || ! in_array( (int) get_queried_object_id(), array( $pair['en'], $pair['bn'] ), true ) ) {
			return;
		}
		$en = get_permalink( $pair['en'] );
		$bn = get_permalink( $pair['bn'] );
		printf( '<link rel="alternate" hreflang="en" href="%s" />' . "\n", esc_url( $en ) );
		printf( '<link rel="alternate" hreflang="bn" href="%s" />' . "\n", esc_url( $bn ) );
		printf( '<link rel="alternate" hreflang="x-default" href="%s" />' . "\n", esc_url( $en ) );
	},
	4
);

// /bn/ is only a URL folder for Bangla pages; send visitors to the Bangla page.
add_action(
	'template_redirect',
	static function () {
		if ( ! is_page() ) {
			return;
		}
		$page = get_queried_object();
		if ( ! $page || 'bn' !== $page->post_name || 0 !== (int) $page->post_parent ) {
			return;
		}
		$pair = safestore_bl_language_pair();
		wp_safe_redirect( $pair['bn'] ? get_permalink( $pair['bn'] ) : home_url( '/' ), 302 );
		exit;
	}
);

/* -------------------------------------------------------------------------
 * Assets — this template only
 * ---------------------------------------------------------------------- */

// The page has its own sticky call / WhatsApp / quote bar.
add_filter( 'safestore_wa_render', static function ( $render ) {
	return safestore_bl_is_landing() ? false : $render;
} );
add_filter( 'safestore_mnav_enabled', static function ( $enabled ) {
	return safestore_bl_is_landing() ? false : $enabled;
} );

add_filter( 'body_class', static function ( $classes ) {
	if ( safestore_bl_is_landing() ) {
		$classes[] = 'lp-page';
		$classes[] = 'sbl-page';
	}
	return $classes;
} );

// Bangla copy: lets phones pick their Bengali system font (no webfont — same
// system-ui stack as the rest of the site).
add_filter( 'language_attributes', static function ( $output ) {
	return safestore_bl_is_landing() ? 'lang="bn-BD"' : $output;
} );

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! safestore_bl_is_landing() ) {
			return;
		}

		// Shell assets for chrome this template does not print (mega header,
		// footer accordion, product cards, cart toast).
		foreach ( array( 'safestore-minimal-footer', 'safestore-cart-toast', 'safestore-copy-to-clipboard', 'safestore-product-compare' ) as $handle ) {
			wp_dequeue_style( $handle );
		}
		foreach ( array( 'safestore-minimal-header-search-cat', 'safestore-minimal-footer-accordion', 'safestore-minimal-shop-cards', 'safestore-cart-toast', 'safestore-copy-to-clipboard', 'safestore-contact-email-chooser', 'safestore-product-compare', 'wc-cart-fragments' ) as $handle ) {
			wp_dequeue_script( $handle );
		}

		if ( function_exists( 'safestore_page_css' ) ) {
			safestore_page_css( 'landing-common', 'landing-common.css' ); // shared .lp-* blocks
			safestore_page_css( 'bulk-landing', 'bulk-landing.css' );     // this page only
		}

		$js = get_template_directory() . '/js/bulk-landing.js';
		if ( file_exists( $js ) ) {
			wp_enqueue_script(
				'safestore-bulk-landing',
				get_template_directory_uri() . '/js/bulk-landing.js',
				array(),
				(string) filemtime( $js ),
				function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true
			);
			$messages = safestore_bl_messages();
			wp_localize_script(
				'safestore-bulk-landing',
				'SBL',
				array(
					'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
					'maxBytes' => SAFESTORE_BL_MAX_UPLOAD,
					'exts'     => array( 'jpg', 'jpeg', 'jpe', 'png', 'webp', 'pdf', 'xls', 'xlsx', 'csv' ),
					'msg'      => array_merge(
						$messages,
						array( 'sending' => 'পাঠানো হচ্ছে…' )
					),
				)
			);
		}
	},
	100
);

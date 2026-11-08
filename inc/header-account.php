<?php
/**
 * Header account widget: "Hi, {first name}" + menu when logged in,
 * "Sign in / Register" when not. Cache-safe:
 *
 *  - The widget is also a WooCommerce cart fragment (div.sft-header-account),
 *    so any fragment refresh repaints it from the live session.
 *  - A readable `sft_li` cookie mirrors the login state. If a page served from
 *    LiteSpeed / Cloudflare cache shows the wrong state, the inline script sees
 *    the mismatch and asks for fresh fragments (cart + account) once.
 *  - Stored fragments are keyed by login state so a logged-in header kept in
 *    sessionStorage is never painted after logout (and vice versa).
 *  - bfcache (back / forward): WooCommerce already refreshes fragments on a
 *    persisted pageshow; the same mismatch check runs there too.
 *
 * @package safestore-minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SAFESTORE_LOGIN_HINT_COOKIE = 'sft_li';

function safestore_header_account_icon() {
	return '<svg class="sft-header-action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>';
}

/** Account widget markup for the current visitor. */
function safestore_header_account_markup() {
	$account_url = function_exists( 'safestore_mnav_account_url' ) ? safestore_mnav_account_url() : wp_login_url();

	if ( ! is_user_logged_in() ) {
		ob_start();
		?>
		<div class="sft-header-account" data-sft-auth="0">
			<a class="sft-header-action sft-header-action--account" href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php esc_attr_e( 'Sign in or register', 'safestore-minimal' ); ?>">
				<?php echo safestore_header_account_icon(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span class="sft-header-action-text">
					<span class="sft-header-action-eyebrow"><?php esc_html_e( 'Welcome', 'safestore-minimal' ); ?></span>
					<span class="sft-header-action-label"><?php esc_html_e( 'Sign in / Register', 'safestore-minimal' ); ?></span>
				</span>
			</a>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	$user = wp_get_current_user();
	$name = trim( (string) $user->first_name );
	if ( '' === $name ) {
		$name = trim( (string) $user->display_name );
	}
	if ( '' === $name || false !== strpos( $name, '@' ) ) {
		$name = __( 'there', 'safestore-minimal' );
	}
	// Keep the header tidy: first word only, max 14 characters.
	$name = preg_split( '/\s+/', $name )[0];
	if ( function_exists( 'mb_strimwidth' ) ) {
		$name = mb_strimwidth( $name, 0, 14, '…' );
	}

	$orders_url = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'orders' ) : $account_url;
	$logout_url = function_exists( 'wc_logout_url' ) ? wc_logout_url() : wp_logout_url( home_url( '/' ) );
	$menu_id    = 'sft-account-menu';

	ob_start();
	?>
	<div class="sft-header-account is-logged-in" data-sft-auth="1">
		<button type="button" class="sft-header-action sft-header-action--account" aria-haspopup="true" aria-expanded="false" aria-controls="<?php echo esc_attr( $menu_id ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: customer's first name */ __( 'Account menu, signed in as %s', 'safestore-minimal' ), $name ) ); ?>" data-sft-account-toggle>
			<?php echo safestore_header_account_icon(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="sft-header-action-text">
				<span class="sft-header-action-eyebrow">
					<?php
					/* translators: %s: customer's first name */
					echo esc_html( sprintf( __( 'Hi, %s', 'safestore-minimal' ), $name ) );
					?>
				</span>
				<span class="sft-header-action-label"><?php esc_html_e( 'My account', 'safestore-minimal' ); ?> <svg class="sft-header-account__chev" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></span>
			</span>
		</button>
		<ul class="sft-header-account__menu" id="<?php echo esc_attr( $menu_id ); ?>" hidden>
			<li><a href="<?php echo esc_url( $orders_url ); ?>"><?php esc_html_e( 'My Orders', 'safestore-minimal' ); ?></a></li>
			<li><a href="<?php echo esc_url( home_url( '/track-order/' ) ); ?>"><?php esc_html_e( 'Track Order', 'safestore-minimal' ); ?></a></li>
			<li><a href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Account details', 'safestore-minimal' ); ?></a></li>
			<li class="sft-header-account__sep"><a href="<?php echo esc_url( $logout_url ); ?>"><?php esc_html_e( 'Logout', 'safestore-minimal' ); ?></a></li>
		</ul>
	</div>
	<?php
	return (string) ob_get_clean();
}

// Fragment: repainted by every WooCommerce fragment refresh.
add_filter(
	'woocommerce_add_to_cart_fragments',
	static function ( $fragments ) {
		$fragments['div.sft-header-account'] = safestore_header_account_markup();
		return $fragments;
	}
);

// sessionStorage fragments are per login state (and per user).
add_filter(
	'woocommerce_cart_fragment_name',
	static function ( $name ) {
		return $name . ( is_user_logged_in() ? '_u' . get_current_user_id() : '_g' );
	}
);

/* -------------------------------------------------------------------------- */
/* Login hint cookie (not a credential: it only says "ask the server")        */
/* -------------------------------------------------------------------------- */

function safestore_login_hint_set( $expire = 0 ) {
	if ( headers_sent() ) {
		return;
	}
	setcookie(
		SAFESTORE_LOGIN_HINT_COOKIE,
		'1',
		array(
			'expires'  => (int) $expire,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
			'secure'   => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		)
	);
	$_COOKIE[ SAFESTORE_LOGIN_HINT_COOKIE ] = '1';
}

function safestore_login_hint_clear() {
	if ( headers_sent() || ! isset( $_COOKIE[ SAFESTORE_LOGIN_HINT_COOKIE ] ) ) {
		return;
	}
	setcookie(
		SAFESTORE_LOGIN_HINT_COOKIE,
		'',
		array(
			'expires'  => time() - YEAR_IN_SECONDS,
			'path'     => COOKIEPATH ? COOKIEPATH : '/',
			'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
			'secure'   => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		)
	);
	unset( $_COOKIE[ SAFESTORE_LOGIN_HINT_COOKIE ] );
}

// Same lifetime as the auth cookie.
add_action(
	'set_logged_in_cookie',
	static function ( $cookie, $expire ) {
		safestore_login_hint_set( $expire );
	},
	10,
	2
);
add_action( 'clear_auth_cookie', 'safestore_login_hint_clear' );

// Sessions that started before this shipped, or a hint left behind.
add_action(
	'init',
	static function () {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		$has_hint = isset( $_COOKIE[ SAFESTORE_LOGIN_HINT_COOKIE ] );
		if ( is_user_logged_in() && ! $has_hint ) {
			safestore_login_hint_set( 0 );
		} elseif ( ! is_user_logged_in() && $has_hint ) {
			safestore_login_hint_clear();
		}
	},
	1
);

/* -------------------------------------------------------------------------- */
/* Front end: menu toggle + cache mismatch check                              */
/* -------------------------------------------------------------------------- */

function safestore_header_account_script() {
	if ( is_admin() ) {
		return;
	}
	$js = <<<'JS'
(function () {
	'use strict';
	var COOKIE = 'sft_li';

	function loggedInHint() {
		return document.cookie.split(';').some(function (c) { return c.trim().indexOf(COOKIE + '=1') === 0; });
	}

	// Cached HTML says one thing, the cookie another → fetch fresh fragments.
	function checkAuth() {
		var box = document.querySelector('.sft-header-account');
		if (!box || !window.jQuery) { return; }
		var shown = box.getAttribute('data-sft-auth') === '1';
		if (shown !== loggedInHint()) {
			window.jQuery(document.body).trigger('wc_fragment_refresh');
		}
	}

	function closeMenus(except) {
		document.querySelectorAll('[data-sft-account-toggle][aria-expanded="true"]').forEach(function (btn) {
			if (btn === except) { return; }
			btn.setAttribute('aria-expanded', 'false');
			var menu = document.getElementById(btn.getAttribute('aria-controls'));
			if (menu) { menu.hidden = true; }
		});
	}

	// Delegated: the widget is replaced by fragment refreshes.
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-sft-account-toggle]');
		if (btn) {
			var open = btn.getAttribute('aria-expanded') !== 'true';
			closeMenus(btn);
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			var menu = document.getElementById(btn.getAttribute('aria-controls'));
			if (menu) {
				menu.hidden = !open;
				if (open) {
					var first = menu.querySelector('a');
					if (first && e.detail === 0) { first.focus(); } // keyboard activation
				}
			}
			return;
		}
		if (!e.target.closest('.sft-header-account__menu')) { closeMenus(); }
	});
	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') { return; }
		var openBtn = document.querySelector('[data-sft-account-toggle][aria-expanded="true"]');
		if (openBtn) { closeMenus(); openBtn.focus(); }
	});

	// After "load": the deferred cart-fragments.js has bound wc_fragment_refresh by then.
	if (document.readyState === 'complete') {
		checkAuth();
	} else {
		window.addEventListener('load', checkAuth);
	}
	window.addEventListener('pageshow', function (e) {
		if (e.persisted) { closeMenus(); checkAuth(); }
	});
})();
JS;
	if ( function_exists( 'wp_print_inline_script_tag' ) ) {
		wp_print_inline_script_tag( $js, array( 'id' => 'safestore-header-account-js' ) );
		return;
	}
	echo '<script id="safestore-header-account-js">' . $js . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'safestore_header_account_script', 40 );

<?php
/**
 * Back-to-top button (css/back-to-top.css + js/back-to-top.js).
 *
 * A small floating button that appears once the visitor is about a screen
 * down the page and glides them back to the top. It carries a scroll-progress
 * ring so it doubles as a "how far am I" cue on long shop / product pages.
 *
 * Placement: it shares the bottom-right corner with the WhatsApp FAB
 * (inc/whatsapp-chat.php). When that FAB is on the right, the body gets
 * "sft-btt-stack" and the button sits centred above it; the CSS follows the
 * same offsets the FAB uses for the mobile bottom nav, the compare bar and the
 * PDP sticky add-to-cart bar, so neither control ever covers the other.
 *
 * Not shown on: order landing pages (single-goal funnels with their own
 * sticky action bar) and the checkout form (nothing should compete with
 * "Place order"). Filter `safestore_btt_render` to change that.
 */

defined( 'ABSPATH' ) || exit;

function safestore_btt_should_render() {
	if ( is_admin() || wp_doing_ajax() || is_feed() || is_embed() ) {
		return false;
	}

	$render = true;

	// Order landings (inc/landing-common.php, inc/bulk-landing.php).
	if ( function_exists( 'safestore_lp_current' ) && '' !== safestore_lp_current() ) {
		$render = false;
	}
	if ( function_exists( 'safestore_bl_is_landing' ) && safestore_bl_is_landing() ) {
		$render = false;
	}

	// Checkout form — but keep it on the order-received page.
	if ( function_exists( 'is_checkout' ) && is_checkout()
		&& ! ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) ) {
		$render = false;
	}

	return (bool) apply_filters( 'safestore_btt_render', $render );
}

/** True when the WhatsApp FAB is printed in the same (right) corner. */
function safestore_btt_stacks_on_whatsapp() {
	if ( ! function_exists( 'safestore_wa_should_render' ) || ! safestore_wa_should_render() ) {
		return false;
	}
	$o = function_exists( 'safestore_wa_get_settings' ) ? safestore_wa_get_settings() : array();
	return ( $o['position'] ?? 'right' ) !== 'left';
}

add_filter( 'body_class', static function ( $classes ) {
	if ( safestore_btt_should_render() && safestore_btt_stacks_on_whatsapp() ) {
		$classes[] = 'sft-btt-stack';
	}
	return $classes;
} );

function safestore_btt_enqueue_assets() {
	if ( ! safestore_btt_should_render() ) {
		return;
	}

	$css_path = get_template_directory() . '/css/back-to-top.css';
	$js_path  = get_template_directory() . '/js/back-to-top.js';

	if ( file_exists( $css_path ) ) {
		wp_enqueue_style(
			'safestore-back-to-top',
			get_template_directory_uri() . '/css/back-to-top.css',
			array(),
			(string) filemtime( $css_path )
		);
	}

	if ( file_exists( $js_path ) ) {
		$args = array( 'in_footer' => true );
		if ( version_compare( get_bloginfo( 'version' ), '6.3', '>=' ) ) {
			$args['strategy'] = 'defer';
		}
		wp_enqueue_script(
			'safestore-back-to-top',
			get_template_directory_uri() . '/js/back-to-top.js',
			array(),
			(string) filemtime( $js_path ),
			$args
		);
	}
}
add_action( 'wp_enqueue_scripts', 'safestore_btt_enqueue_assets', 20 );

function safestore_btt_render() {
	if ( ! safestore_btt_should_render() ) {
		return;
	}
	$label = __( 'Back to top', 'safestore-minimal' );
	?>
	<button type="button" class="sft-btt" aria-label="<?php echo esc_attr( $label ); ?>" aria-hidden="true" tabindex="-1">
		<svg class="sft-btt__ring" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
			<circle class="sft-btt__track" cx="24" cy="24" r="22" />
			<circle class="sft-btt__progress" cx="24" cy="24" r="22" />
		</svg>
		<svg class="sft-btt__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
			<path d="M12 19V5" />
			<path d="M5.5 11.5 12 5l6.5 6.5" />
		</svg>
		<span class="sft-btt__tip" aria-hidden="true"><?php echo esc_html( $label ); ?></span>
	</button>
	<?php
}
// After the WhatsApp widget (priority 20) so `.sft-wa.is-open ~ .sft-btt` works.
add_action( 'wp_footer', 'safestore_btt_render', 21 );

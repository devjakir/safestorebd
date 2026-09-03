<?php
/**
 * Unified pre-footer CTA banner.
 *
 * Replaces the six near-identical `.sft-*-page-footer` blocks (policy columns
 * + bullet lists + per-page CTA) that duplicated links already present in the
 * main footer's Customer Service column.
 *
 * Also the single source of truth for the primary contact number, so the
 * banner, the footer and the schema markup can never drift apart again.
 *
 * @package safestore-minimal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Primary contact number in E.164 form (dial-ready).
 *
 * @return string
 */
function safestore_primary_phone_e164() {
	return (string) apply_filters( 'safestore_primary_phone_e164', '+8801811892291' );
}

/**
 * Primary contact number, human readable.
 *
 * @return string
 */
function safestore_primary_phone_display() {
	return (string) apply_filters( 'safestore_primary_phone_display', '+880 1811-892291' );
}

/**
 * wa.me deep link for the primary number.
 *
 * Deliberately NOT safestore_wa_link(): that reads the WhatsApp widget's
 * slot-1 setting, which is what made the CTA buttons show a different number
 * from the rest of the footer.
 *
 * @param string $text Optional pre-filled message.
 * @return string
 */
function safestore_primary_wa_link( $text = '' ) {
	$digits = preg_replace( '/\D/', '', safestore_primary_phone_e164() );
	if ( '' === $digits ) {
		return '';
	}
	$url = 'https://wa.me/' . $digits;
	if ( '' !== (string) $text ) {
		$url .= '?text=' . rawurlencode( (string) $text );
	}
	return $url;
}

/**
 * Force every WhatsApp surface onto the primary number.
 *
 * safestore_wa_lines() is the theme's documented single source of truth for
 * the advertised WhatsApp lines. Filtering it here means the floating widget,
 * the PDP contact buttons and every safestore_wa_link() / safestore_wa_display()
 * call resolve to safestore_primary_phone_e164() — regardless of what is stored
 * in the safestore_whatsapp_chat option.
 *
 * NOTE: while this filter is active the number in
 * Settings -> WhatsApp Chat is ignored. Change the number in
 * safestore_primary_phone_e164() above, or remove this filter to hand control
 * back to the admin screen.
 */
add_filter(
	'safestore_wa_lines',
	function ( $lines ) {
		$digits = preg_replace( '/\D/', '', safestore_primary_phone_e164() );
		if ( '' === $digits ) {
			return $lines;
		}

		$label = isset( $lines[0]['label'] ) && '' !== $lines[0]['label']
			? $lines[0]['label']
			: __( 'Sales & Orders', 'safestore-minimal' );

		return array(
			array(
				'slot'    => 'primary',
				'number'  => $digits,
				'display' => function_exists( 'safestore_wa_format_number' )
					? safestore_wa_format_number( $digits )
					: safestore_primary_phone_display(),
				'label'   => $label,
			),
		);
	},
	20
);


/**
 * Build one CTA button with the banner's shared styling.
 *
 * @param array $args {
 *     @type string $href    Required.
 *     @type string $label   Visible text.
 *     @type string $icon    Inline SVG markup (already trusted).
 *     @type string $variant 'primary' (solid) or 'secondary' (outline).
 *                          'wa' / 'call' still accepted.
 *     @type bool   $blank   Open in a new tab.
 *     @type string $aria    aria-label.
 * }
 * @return string
 */
function safestore_page_cta_btn( $args ) {
	$a = wp_parse_args(
		$args,
		array( 'href' => '', 'label' => '', 'icon' => '', 'variant' => 'call', 'blank' => false, 'aria' => '' )
	);
	if ( '' === $a['href'] ) {
		return '';
	}
	// 'wa'/'call' are the original names kept for existing callers.
	$solid = in_array( $a['variant'], array( 'wa', 'primary' ), true );

	return sprintf(
		'<a class="sft-page-cta__btn sft-page-cta__btn--%1$s" href="%2$s"%3$s%4$s>%5$s<span>%6$s</span></a>',
		$solid ? 'primary' : 'secondary',
		esc_url( $a['href'] ),
		$a['blank'] ? ' target="_blank" rel="noopener noreferrer"' : '',
		'' !== $a['aria'] ? ' aria-label="' . esc_attr( $a['aria'] ) . '"' : '',
		$a['icon'],
		esc_html( $a['label'] )
	);
}

/**
 * Render the slim, single-row pre-footer CTA banner.
 *
 * @param array $args {
 *     @type string $title Heading. Defaults to the unified copy.
 *     @type string $text  Subtext. Defaults to the unified copy.
 *     @type string $label   aria-label for the section.
 *     @type string $actions  Optional pre-escaped markup replacing the default
 *                            WhatsApp + Call pair. Use safestore_page_cta_btn()
 *                            so the buttons keep the shared styling.
 * }
 * @return void
 */
function safestore_render_page_cta( $args = array() ) {
	$a = wp_parse_args(
		$args,
		array(
			'title' => __( 'Questions or Bulk Orders?', 'safestore-minimal' ),
			'text'  => __( 'Reach out on WhatsApp or call us directly.', 'safestore-minimal' ),
			'label'   => __( 'Contact SafeStoreBD', 'safestore-minimal' ),
			'actions' => '',
		)
	);

	$display = safestore_primary_phone_display();
	$wa_url  = safestore_primary_wa_link();
	$tel_url = 'tel:' . safestore_primary_phone_e164();
	?>
	<section class="sft-page-cta" aria-label="<?php echo esc_attr( $a['label'] ); ?>">
		<div class="sft-page-cta__inner">
			<div class="sft-page-cta__copy">
				<h2 class="sft-page-cta__title"><?php echo esc_html( $a['title'] ); ?></h2>
				<p class="sft-page-cta__text"><?php echo esc_html( $a['text'] ); ?></p>
			</div>

			<div class="sft-page-cta__actions sft-copy-skip">
				<?php if ( '' !== $a['actions'] ) : ?>
					<?php echo $a['actions']; // phpcs:ignore WordPress.Security.EscapeOutput — built by safestore_page_cta_btn(). ?>
				<?php else : ?>
				<?php if ( '' !== $wa_url ) : ?>
					<a class="sft-page-cta__btn sft-page-cta__btn--wa"
						href="<?php echo esc_url( $wa_url ); ?>"
						target="_blank" rel="noopener noreferrer"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %s: phone number */ __( 'Chat on WhatsApp: %s', 'safestore-minimal' ), $display ) ); ?>">
						<svg class="sft-page-cta__icon" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false">
							<path d="M17.5 14.4c-.3-.2-1.7-.9-2-1-.3-.1-.5-.2-.7.1-.2.3-.7 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.3-.5 0-.2 0-.4-.1-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1.1 2.8 1.2 3c.2.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.7-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.2-.6-.4z"/>
							<path d="M12 2A10 10 0 0 0 3.4 17.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2c-1.5 0-3-.4-4.3-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2z"/>
						</svg>
						<span><?php echo esc_html( $display ); ?></span>
					</a>
				<?php endif; ?>

				<a class="sft-page-cta__btn sft-page-cta__btn--call"
					href="<?php echo esc_url( $tel_url ); ?>"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %s: phone number */ __( 'Call us: %s', 'safestore-minimal' ), $display ) ); ?>">
					<svg class="sft-page-cta__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
						<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>
					</svg>
					<span><?php esc_html_e( 'Call us', 'safestore-minimal' ); ?></span>
				</a>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

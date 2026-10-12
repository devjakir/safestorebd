<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function safestore_copy_to_clipboard_enabled() {
	$enabled = ! is_admin();

	return (bool) apply_filters( 'safestore_copy_to_clipboard_enabled', $enabled );
}

function safestore_copy_to_clipboard_enqueue() {
	if ( ! safestore_copy_to_clipboard_enabled() ) {
		return;
	}

	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();

	$css_path = $theme_dir . '/css/copy-to-clipboard.css';
	if ( file_exists( $css_path ) ) {
		wp_enqueue_style(
			'safestore-copy-to-clipboard',
			$theme_uri . '/css/copy-to-clipboard.css',
			array( 'safestore-minimal-style' ),
			(string) filemtime( $css_path )
		);
	}

	$js_path = $theme_dir . '/js/copy-to-clipboard.js';
	if ( ! file_exists( $js_path ) ) {
		return;
	}

	wp_enqueue_script(
		'safestore-copy-to-clipboard',
		$theme_uri . '/js/copy-to-clipboard.js',
		array(),
		(string) filemtime( $js_path ),
		function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true
	);

	wp_localize_script(
		'safestore-copy-to-clipboard',
		'sftCopy',
		array(
			'i18n' => array(
				/* translators: %s: what is being copied, e.g. "email address". */
				'copy'         => __( 'Copy %s', 'safestore-minimal' ),
				'copied'       => __( 'Copied!', 'safestore-minimal' ),
				/* translators: %s: what was copied, e.g. "Email address". */
				'copiedNoun'   => __( '%s copied', 'safestore-minimal' ),
				'failed'       => __( 'Press Ctrl+C to copy', 'safestore-minimal' ),
				'nounPhone'    => __( 'Phone number', 'safestore-minimal' ),
				'nounWhatsapp' => __( 'WhatsApp number', 'safestore-minimal' ),
				'nounEmail'    => __( 'Email address', 'safestore-minimal' ),
				'nounAddress'  => __( 'Address', 'safestore-minimal' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'safestore_copy_to_clipboard_enqueue', 20 );

function safestore_copy_to_clipboard_live_region() {
	if ( ! safestore_copy_to_clipboard_enabled() ) {
		return;
	}
	?>
	<div id="sft-copy-live" class="sft-copy-live" role="status" aria-live="polite" aria-atomic="true"></div>
	<?php
}
add_action( 'wp_footer', 'safestore_copy_to_clipboard_live_region', 30 );

function safestore_copy_button( $args = array() ) {
	if ( ! safestore_copy_to_clipboard_enabled() ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'value' => '',
			'noun'  => __( 'Value', 'safestore-minimal' ),
			'class' => '',
		)
	);

	$value = trim( (string) $args['value'] );
	if ( '' === $value ) {
		return '';
	}

	$noun = (string) $args['noun'];

	/* translators: %s: what is being copied, e.g. "phone number". */
	$copy_label = sprintf( __( 'Copy %s', 'safestore-minimal' ), strtolower( $noun ) );
	/* translators: %s: what was copied, e.g. "Phone number". */
	$done_label = sprintf( __( '%s copied', 'safestore-minimal' ), $noun );

	$icons = '<span class="sft-copy-btn__icon sft-copy-btn__icon--copy" aria-hidden="true">'
		. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>'
		. '</span>'
		. '<span class="sft-copy-btn__icon sft-copy-btn__icon--done" aria-hidden="true">'
		. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M20 6 9 17l-5-5"/></svg>'
		. '</span>'
		. '<span class="sft-copy-btn__flash" aria-hidden="true"></span>';

	return sprintf(
		'<button type="button" class="%1$s" data-sft-copy="%2$s" data-sft-copy-done="%3$s" data-sft-copy-title="%4$s" aria-label="%4$s" title="%4$s">%5$s</button>',
		esc_attr( trim( 'sft-copy-btn ' . $args['class'] ) ),
		esc_attr( $value ),
		esc_attr( $done_label ),
		esc_attr( $copy_label ),
		$icons
	);
}

function safestore_copy_contact_value( $type, $value ) {
	$value = trim( (string) $value );
	$none  = array(
		'value' => '',
		'noun'  => '',
	);

	if ( '' === $value ) {
		return $none;
	}

	switch ( $type ) {
		case 'phone':
			$digits = preg_replace( '/[^\d+]/', '', $value );
			return array(
				'value' => $digits,
				'noun'  => __( 'Phone number', 'safestore-minimal' ),
			);

		case 'whatsapp':
			$digits = preg_replace( '/\D/', '', $value );
			return array(
				'value' => '' !== $digits ? '+' . $digits : '',
				'noun'  => __( 'WhatsApp number', 'safestore-minimal' ),
			);

		case 'email':
			return array(
				'value' => $value,
				'noun'  => __( 'Email address', 'safestore-minimal' ),
			);

		case 'location':
			return array(
				'value' => $value,
				'noun'  => __( 'Address', 'safestore-minimal' ),
			);
	}

	return $none;
}

<?php
/**
 * Color swatches for variable products.
 *
 * Replaces the Color dropdown on the product page with selectable color chips
 * (dot + name). The native <select> stays in the DOM, hidden, so WooCommerce's
 * variation script keeps working; js/color-swatches.js mirrors chip clicks onto
 * it. Styles: css/color-swatches.css.
 *
 * Swatch colors come from, in order: the term meta keys in
 * safestore_color_swatch_meta_keys(), then a name lookup
 * (safestore_color_swatch_map(), filterable), then a word match inside the
 * name ("Hi-Vis Orange" → orange). Unknown colors render as a name-only chip.
 *
 * @package safestore-minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Whether a variation attribute is a color attribute (pa_color, Colour, রং …). */
function safestore_is_color_attribute( $attribute ) {
	$key = sanitize_title( $attribute );
	$key = preg_replace( '/^pa_/', '', $key );

	$keys = array( 'color', 'colour', 'colors', 'colours', 'rong', sanitize_title( 'রং' ), sanitize_title( 'রঙ' ) );
	$is   = in_array( $key, $keys, true );

	if ( ! $is ) {
		$label = strtolower( wc_attribute_label( $attribute ) );
		$is    = false !== strpos( $label, 'color' ) || false !== strpos( $label, 'colour' );
	}

	return (bool) apply_filters( 'safestore_is_color_attribute', $is, $attribute );
}

/** Lower-case color name → CSS color. */
function safestore_color_swatch_map() {
	$map = array(
		// Hi-vis / safety colors first — they are what this store sells most.
		'lime green'         => '#c6e816',
		'lime'               => '#c6e816',
		'fluorescent green'  => '#b8f00a',
		'neon green'         => '#b8f00a',
		'fluorescent yellow' => '#e6ff1a',
		'neon yellow'        => '#e6ff1a',
		'hi vis yellow'      => '#e6ff1a',
		'hi-vis yellow'      => '#e6ff1a',
		'fluorescent orange' => '#ff6a13',
		'neon orange'        => '#ff6a13',
		'hi vis orange'      => '#ff6a13',
		'hi-vis orange'      => '#ff6a13',
		'red'                => '#dc2626',
		'dark red'           => '#991b1b',
		'maroon'             => '#7f1d1d',
		'green'              => '#16a34a',
		'dark green'         => '#166534',
		'olive'              => '#6b7a2a',
		'army green'         => '#4b5320',
		'yellow'             => '#facc15',
		'orange'             => '#f97316',
		'blue'               => '#2563eb',
		'royal blue'         => '#1d4ed8',
		'sky blue'           => '#38bdf8',
		'light blue'         => '#7dd3fc',
		'navy'               => '#1e3a8a',
		'navy blue'          => '#1e3a8a',
		'black'              => '#111827',
		'white'              => '#ffffff',
		'grey'               => '#9ca3af',
		'gray'               => '#9ca3af',
		'dark grey'          => '#4b5563',
		'dark gray'          => '#4b5563',
		'ash'                => '#b2b7bd',
		'silver'             => '#c0c4c9',
		'brown'              => '#8b5a2b',
		'khaki'              => '#c3b091',
		'beige'              => '#e8dcc4',
		'cream'              => '#f7f1dc',
		'pink'               => '#ec4899',
		'purple'             => '#7c3aed',
		'violet'             => '#8b5cf6',
		'gold'               => '#d4a017',
		'golden'             => '#d4a017',
		'transparent'        => 'transparent',
		'clear'              => 'transparent',
		// Bangla.
		'লাল'                => '#dc2626',
		'সবুজ'               => '#16a34a',
		'হলুদ'               => '#facc15',
		'কমলা'               => '#f97316',
		'নীল'                => '#2563eb',
		'কালো'               => '#111827',
		'সাদা'               => '#ffffff',
		'ধূসর'               => '#9ca3af',
		'বাদামী'             => '#8b5a2b',
		'গোলাপী'             => '#ec4899',
	);

	return apply_filters( 'safestore_color_swatch_map', $map );
}

/** Term meta keys checked for an explicit hex (ours first, then common swatch plugins). */
function safestore_color_swatch_meta_keys() {
	return apply_filters( 'safestore_color_swatch_meta_keys', array( 'sft_swatch_color', 'swatch_color', 'color', 'product_attribute_color' ) );
}

/** Resolve one color name to a CSS color, or '' when unknown. */
function safestore_color_swatch_lookup( $name ) {
	$map  = safestore_color_swatch_map();
	$norm = function_exists( 'mb_strtolower' ) ? mb_strtolower( trim( $name ), 'UTF-8' ) : strtolower( trim( $name ) );
	$norm = preg_replace( '/[\s_\-]+/u', ' ', $norm );

	if ( isset( $map[ $norm ] ) ) {
		return $map[ $norm ];
	}

	// Longest known name inside the label wins ("Hi Vis Navy Blue" → navy blue).
	$keys = array_keys( $map );
	usort(
		$keys,
		static function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		}
	);
	foreach ( $keys as $key ) {
		if ( preg_match( '/(^|\s)' . preg_quote( $key, '/' ) . '(\s|$)/u', $norm ) ) {
			return $map[ $key ];
		}
	}

	return '';
}

/**
 * CSS background for a swatch dot. Two-tone names ("Yellow/Black",
 * "Orange & Grey") become a split dot; "Multicolor" a conic gradient.
 */
function safestore_color_swatch_background( $name, $term = null ) {
	if ( $term instanceof WP_Term ) {
		foreach ( safestore_color_swatch_meta_keys() as $meta_key ) {
			$value = get_term_meta( $term->term_id, $meta_key, true );
			if ( is_string( $value ) && preg_match( '/^#?[0-9a-f]{3,8}$/i', trim( $value ) ) ) {
				return '#' . ltrim( trim( $value ), '#' );
			}
		}
	}

	$custom = apply_filters( 'safestore_color_swatch_background', null, $name, $term );
	if ( is_string( $custom ) && '' !== $custom ) {
		return $custom;
	}

	if ( preg_match( '/multi\s*-?\s*colou?r|assorted|mixed/i', $name ) ) {
		return 'conic-gradient(#dc2626, #facc15, #16a34a, #2563eb, #7c3aed, #dc2626)';
	}

	$parts = preg_split( '/\s*(?:\/|&|\+|,|\band\b|\bwith\b)\s*/iu', $name );
	if ( is_array( $parts ) && count( $parts ) >= 2 ) {
		$a = safestore_color_swatch_lookup( $parts[0] );
		$b = safestore_color_swatch_lookup( $parts[1] );
		if ( '' !== $a && '' !== $b && 'transparent' !== $a && 'transparent' !== $b ) {
			return 'linear-gradient(135deg, ' . $a . ' 50%, ' . $b . ' 50%)';
		}
	}

	return safestore_color_swatch_lookup( $name );
}

/**
 * Options that have no purchasable in-stock variation. Only reliable when the
 * product varies on this one attribute; with several attributes WooCommerce's
 * own script disables combinations and js/color-swatches.js mirrors that.
 */
function safestore_color_swatch_unavailable( $product, $attribute ) {
	if ( ! $product instanceof WC_Product_Variable ) {
		return array();
	}

	$variation_attrs = array_filter(
		$product->get_attributes(),
		static function ( $attr ) {
			return $attr->get_variation();
		}
	);
	if ( 1 !== count( $variation_attrs ) ) {
		return array();
	}

	$key       = 'attribute_' . sanitize_title( $attribute );
	$available = array();

	foreach ( $product->get_children() as $child_id ) {
		$variation = wc_get_product( $child_id );
		if ( ! $variation || ! $variation->variation_is_visible() ) {
			continue;
		}
		$attrs = $variation->get_variation_attributes();
		$value = isset( $attrs[ $key ] ) ? (string) $attrs[ $key ] : '';
		$ok    = $variation->is_purchasable() && $variation->is_in_stock();

		if ( '' === $value ) {
			// "Any color" variation: nothing can be ruled out.
			if ( $ok ) {
				return array();
			}
			continue;
		}
		$available[ $value ] = ! empty( $available[ $value ] ) || $ok;
	}

	return array_keys(
		array_filter(
			$available,
			static function ( $ok ) {
				return ! $ok;
			}
		)
	);
}

/** Append the swatch group after the (now hidden) Color <select>. */
function safestore_color_swatches_html( $html, $args ) {
	$attribute = isset( $args['attribute'] ) ? $args['attribute'] : '';
	$product   = isset( $args['product'] ) ? $args['product'] : null;
	$options   = isset( $args['options'] ) ? (array) $args['options'] : array();

	if ( '' === $attribute || ! $product instanceof WC_Product || empty( $options ) || ! safestore_is_color_attribute( $attribute ) ) {
		return $html;
	}
	if ( ! apply_filters( 'safestore_color_swatches_enabled', true, $product, $attribute ) ) {
		return $html;
	}

	$select_id = ! empty( $args['id'] ) ? $args['id'] : sanitize_title( $attribute );
	$selected  = isset( $args['selected'] ) ? (string) $args['selected'] : '';
	$label     = wc_attribute_label( $attribute, $product );

	// Same option order and labels WooCommerce uses for the <select>.
	$items = array();
	if ( taxonomy_exists( $attribute ) ) {
		$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );
		foreach ( $terms as $term ) {
			if ( in_array( $term->slug, $options, true ) ) {
				$name    = apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product );
				$items[] = array( $term->slug, $name, safestore_color_swatch_background( $term->name, $term ) );
			}
		}
	} else {
		foreach ( $options as $option ) {
			$name    = apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product );
			$items[] = array( $option, $name, safestore_color_swatch_background( $option ) );
		}
	}
	if ( empty( $items ) ) {
		return $html;
	}

	$unavailable  = safestore_color_swatch_unavailable( $product, $attribute );
	$current_name = '';

	$buttons = '';
	foreach ( $items as $item ) {
		list( $value, $name, $bg ) = $item;
		$is_on  = '' !== $selected && $selected === (string) $value;
		$is_oos = in_array( (string) $value, $unavailable, true );
		if ( $is_on ) {
			$current_name = $name;
		}

		$classes = 'sft-swatch';
		if ( $is_on ) {
			$classes .= ' is-selected';
		}
		if ( $is_oos ) {
			$classes .= ' is-oos';
		}

		$dot = '';
		if ( '' !== $bg ) {
			$light = in_array( strtolower( $bg ), array( '#ffffff', '#fff', 'transparent', '#f7f1dc', '#e8dcc4' ), true );
			$dot   = '<span class="sft-swatch__dot' . ( $light ? ' is-light' : '' ) . ( 'transparent' === $bg ? ' is-clear' : '' ) . '" style="background:' . esc_attr( $bg ) . '" aria-hidden="true"></span>';
		}

		$buttons .= sprintf(
			'<button type="button" class="%1$s" role="radio" aria-checked="%2$s" tabindex="%3$s" data-value="%4$s" data-name="%5$s"%6$s%7$s>%8$s<span class="sft-swatch__name">%9$s</span><svg class="sft-swatch__check" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></button>',
			esc_attr( $classes ),
			$is_on ? 'true' : 'false',
			$is_on ? '0' : '-1',
			esc_attr( $value ),
			esc_attr( $name ),
			$is_oos ? ' disabled aria-disabled="true" data-oos="1"' : '',
			$is_oos ? ' title="' . esc_attr__( 'Out of stock', 'safestore-minimal' ) . '"' : '',
			$dot,
			esc_html( $name )
		);
	}

	$html = preg_replace( '/<select\b([^>]*)\bclass="/', '<select$1class="sft-swatch-select ', $html, 1, $count );
	if ( ! $count ) {
		$html = preg_replace( '/<select\b/', '<select class="sft-swatch-select"', $html, 1 );
	}

	$html .= sprintf(
		'<div class="sft-swatches" data-sft-swatches data-select="%1$s" data-placeholder="%2$s">' .
			'<div class="sft-swatches__head"><span class="sft-swatches__label">%3$s:</span> <span class="sft-swatches__current%4$s" data-sft-swatch-current aria-live="polite">%5$s</span></div>' .
			'<div class="sft-swatches__list" role="radiogroup" aria-label="%6$s">%7$s</div>' .
		'</div>',
		esc_attr( $select_id ),
		/* translators: %s: attribute label, e.g. Color. */
		esc_attr( sprintf( __( 'Select a %s', 'safestore-minimal' ), strtolower( $label ) ) ),
		esc_html( $label ),
		'' === $current_name ? ' is-empty' : '',
		esc_html( '' !== $current_name ? $current_name : sprintf( __( 'Select a %s', 'safestore-minimal' ), strtolower( $label ) ) ),
		esc_attr( $label ),
		$buttons
	);

	return $html;
}
add_filter( 'woocommerce_dropdown_variation_attribute_options_html', 'safestore_color_swatches_html', 25, 2 );

/** Whether the current product page has a color attribute to swatch. */
function safestore_color_swatches_needed() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return false;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return false;
	}
	foreach ( $product->get_attributes() as $name => $attr ) {
		if ( $attr->get_variation() && safestore_is_color_attribute( $name ) ) {
			return true;
		}
	}
	return false;
}

function safestore_color_swatches_enqueue() {
	if ( ! class_exists( 'WooCommerce' ) || ! safestore_color_swatches_needed() ) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();

	if ( file_exists( $dir . '/css/color-swatches.css' ) ) {
		wp_enqueue_style( 'safestore-color-swatches', $uri . '/css/color-swatches.css', array( 'safestore-minimal-style' ), (string) filemtime( $dir . '/css/color-swatches.css' ) );
	}
	if ( file_exists( $dir . '/js/color-swatches.js' ) ) {
		wp_enqueue_script( 'wc-add-to-cart-variation' );
		wp_enqueue_script(
			'safestore-color-swatches',
			$uri . '/js/color-swatches.js',
			array( 'jquery', 'wc-add-to-cart-variation' ),
			(string) filemtime( $dir . '/js/color-swatches.js' ),
			function_exists( 'safestore_perf_script_args' ) ? safestore_perf_script_args( true ) : true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'safestore_color_swatches_enqueue', 30 );

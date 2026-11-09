<?php
/**
 * Brand assets: the SafeStoreBD logo and favicon set.
 *
 * The theme is the source of truth for the brand. The favicon and the Rank Math
 * Organization logo are normally set in wp-admin (Site Icon, Rank Math → Titles &
 * Meta → Local SEO); both are overridden here so a logo change ships with a
 * deploy instead of needing a manual admin step on every environment.
 *
 * Source files: safestorebd-logo-transparent_3 pack. The logo is
 * safestorebd-logo-no-tagline-transparent.svg with its viewBox cropped to the
 * artwork; the favicons are rendered from safestorebd-icon-transparent(-1080).
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL of a theme brand file with ?ver=<mtime>, so browsers and CDNs drop the
 * cached copy when a logo or icon is replaced under the same filename.
 */
function safestore_brand_asset_url( $relative ) {
	$path = get_template_directory() . '/assets/images/' . $relative;
	$url  = get_template_directory_uri() . '/assets/images/' . $relative;
	return file_exists( $path ) ? add_query_arg( 'ver', (string) filemtime( $path ), $url ) : $url;
}

/**
 * Horizontal colour logo for <img> tags. SVG, so it stays sharp on any screen.
 * width/height are the desktop header size; CSS sets the real height per breakpoint.
 */
function safestore_brand_logo_svg() {
	return array(
		'url'    => safestore_brand_asset_url( 'logo/safestorebd-logo.svg' ),
		'width'  => 180,
		'height' => 44,
	);
}

/**
 * Raster logo for structured data, where search engines expect a bitmap.
 */
function safestore_brand_logo_png() {
	return array(
		'url'    => safestore_brand_asset_url( 'logo/safestorebd-logo.png' ),
		'width'  => 1200,
		'height' => 293,
	);
}

/**
 * Favicon / app icon closest to a requested square size, from the theme's icon set.
 */
function safestore_brand_icon_url( $size ) {
	$size = (int) $size;
	if ( $size <= 16 ) {
		$file = 'favicon-16.png';
	} elseif ( $size <= 32 ) {
		$file = 'favicon-32.png';
	} elseif ( $size <= 48 ) {
		$file = 'favicon-48.png';
	} elseif ( $size <= 180 ) {
		$file = 'apple-touch-icon.png';
	} elseif ( $size <= 192 ) {
		$file = 'icon-192.png';
	} else {
		$file = 'icon-512.png';
	}
	return safestore_brand_asset_url( 'favicon/' . $file );
}

/**
 * Serve the theme's icons wherever WordPress asks for the Site Icon: front end,
 * wp-admin, login page and the /favicon.ico fallback. has_site_icon() becomes true
 * too, so core's own <link rel="icon"> tags are printed even with no icon uploaded.
 */
add_filter(
	'get_site_icon_url',
	function ( $url, $size ) {
		return safestore_brand_icon_url( $size );
	},
	10,
	2
);

/**
 * Add the SVG favicon next to core's PNG ones; browsers that support it use it.
 */
add_filter(
	'site_icon_meta_tags',
	function ( $tags ) {
		$tags[] = sprintf(
			'<link rel="icon" href="%s" type="image/svg+xml" />',
			esc_url( safestore_brand_asset_url( 'favicon/favicon.svg' ) )
		);
		return $tags;
	}
);

/**
 * Rank Math: swap whatever logo is configured in its settings for the theme logo.
 *
 * The configured URL is read from the Organization's `logo` node, then every place
 * that URL appears in the graph (the logo, the home page's primary ImageObject and
 * the references to it) is pointed at the theme PNG with matching dimensions.
 */
function safestore_brand_rank_math_json_ld( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}

	$logo = safestore_brand_logo_png();
	$old  = array();
	foreach ( $data as $entity ) {
		if ( is_array( $entity ) && isset( $entity['logo'] ) && is_array( $entity['logo'] ) ) {
			foreach ( array( 'url', 'contentUrl' ) as $key ) {
				if ( ! empty( $entity['logo'][ $key ] ) && is_string( $entity['logo'][ $key ] ) ) {
					$old[] = $entity['logo'][ $key ];
				}
			}
		}
	}

	$old = array_values( array_diff( array_unique( $old ), array( $logo['url'] ) ) );
	if ( empty( $old ) ) {
		return $data;
	}

	return safestore_brand_swap_logo_urls( $data, $old, $logo );
}
add_filter( 'rank_math/json_ld', 'safestore_brand_rank_math_json_ld', 999 );

/**
 * Recursive helper for safestore_brand_rank_math_json_ld().
 */
function safestore_brand_swap_logo_urls( array $node, array $old, array $logo ) {
	$swapped = false;
	foreach ( $node as $key => $value ) {
		if ( is_array( $value ) ) {
			$node[ $key ] = safestore_brand_swap_logo_urls( $value, $old, $logo );
		} elseif ( is_string( $value ) && in_array( $value, $old, true ) ) {
			$node[ $key ] = $logo['url'];
			$swapped      = true;
		}
	}

	// An ImageObject that pointed at the old logo also carries its old dimensions.
	if ( $swapped && ( isset( $node['width'] ) || isset( $node['height'] ) ) ) {
		$node['width']  = $logo['width'];
		$node['height'] = $logo['height'];
	}

	return $node;
}

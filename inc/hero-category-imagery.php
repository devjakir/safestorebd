<?php
/**
 * Home hero category imagery — live price + review overlays.
 *
 * Hero media, price, and reviews come from a real WooCommerce product in the
 * category (term meta, then the best visible product with a photo / price /
 * sale / ratings). Static theme assets are only a fallback when that product
 * has no image. Sale percent and stars are never invented.
 *
 * @package safestore-minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SAFESTORE_HERO_PRODUCT_META = '_sft_hero_product_id';
const SAFESTORE_HERO_OVERLAY_TTL  = 10 * MINUTE_IN_SECONDS;
const SAFESTORE_HERO_OVERLAY_CACHE = 'sft_hero_overlay_v3_';

/**
 * Transient key for a category overlay payload.
 *
 * @param string $category_slug product_cat slug.
 * @return string
 */
function safestore_hero_overlay_cache_key( $category_slug ) {
	return SAFESTORE_HERO_OVERLAY_CACHE . sanitize_title( (string) $category_slug );
}

/**
 * Overlay payload for a product category slug.
 *
 * @param string $category_slug product_cat slug.
 * @return array{product_id:int,price:?array,reviews:?array,image:?array}
 */
function safestore_hero_overlay_for_category( $category_slug ) {
	$slug = sanitize_title( (string) $category_slug );
	$empty = array(
		'product_id' => 0,
		'price'      => null,
		'reviews'    => null,
		'image'      => null,
	);

	if ( '' === $slug || ! function_exists( 'wc_get_product' ) ) {
		return $empty;
	}

	$cache_key = safestore_hero_overlay_cache_key( $slug );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) && array_key_exists( 'image', $cached ) ) {
		return $cached;
	}

	$product_id = safestore_hero_resolve_category_product_id( $slug );
	$payload    = $empty;

	if ( $product_id > 0 ) {
		$product = wc_get_product( $product_id );
		if ( $product instanceof WC_Product && $product->is_visible() ) {
			$payload['product_id'] = $product_id;
			$payload['price']      = safestore_hero_product_price_payload( $product );
			$payload['reviews']    = safestore_hero_product_reviews_payload( $product );
			$payload['image']      = safestore_hero_product_image_payload( $product );
		}
	}

	set_transient( $cache_key, $payload, SAFESTORE_HERO_OVERLAY_TTL );

	return $payload;
}

/**
 * Pick the product that feeds hero overlays for a category.
 *
 * Order: filter → category term meta → highest-scoring visible product
 * (photo, live price, real sale, real ratings — never invented).
 *
 * @param string $category_slug product_cat slug.
 * @return int
 */
function safestore_hero_resolve_category_product_id( $category_slug ) {
	$term = get_term_by( 'slug', $category_slug, 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		return 0;
	}

	$forced = (int) apply_filters( 'safestore_hero_product_id', 0, $category_slug, $term );
	if ( $forced > 0 && safestore_hero_product_is_usable( $forced, $term ) ) {
		return $forced;
	}

	$meta_id = (int) get_term_meta( $term->term_id, SAFESTORE_HERO_PRODUCT_META, true );
	if ( $meta_id > 0 && safestore_hero_product_is_usable( $meta_id, $term ) ) {
		return $meta_id;
	}

	$ids = wc_get_products(
		array(
			'status'   => 'publish',
			'limit'    => 40,
			'return'   => 'ids',
			'category' => array( $term->slug ),
			'orderby'  => 'date',
			'order'    => 'DESC',
		)
	);

	$best_id    = 0;
	$best_score = -1;

	foreach ( (array) $ids as $candidate_id ) {
		$candidate_id = (int) $candidate_id;
		if ( $candidate_id <= 0 ) {
			continue;
		}
		$candidate = wc_get_product( $candidate_id );
		if ( ! $candidate instanceof WC_Product || ! $candidate->is_visible() ) {
			continue;
		}
		$score = safestore_hero_product_score( $candidate );
		if ( $score > $best_score ) {
			$best_score = $score;
			$best_id    = $candidate_id;
		}
	}

	return $best_id;
}

/**
 * Rank a candidate for the hero. Higher is better. Does not invent sale or rating.
 *
 * @param WC_Product $product Product.
 * @return int
 */
function safestore_hero_product_score( $product ) {
	$score = 0;

	if ( (int) $product->get_image_id() > 0 ) {
		$score += 100;
	}

	$current = (float) $product->get_price();
	if ( $current > 0 ) {
		$score += 80;
	}

	if ( $current > 0 && $product->is_on_sale() ) {
		$score += 50;
		$save = safestore_hero_product_save_pct( $product );
		if ( $save >= 1 ) {
			$score += min( 25, $save );
		}
	}

	$avg   = (float) $product->get_average_rating();
	$count = max( (int) $product->get_rating_count(), (int) $product->get_review_count() );
	if ( $avg > 0 && $count > 0 ) {
		$score += 40;
	}

	if ( $product->is_featured() ) {
		$score += 20;
	}

	if ( $product->is_in_stock() ) {
		$score += 10;
	}

	$terms      = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
	$term_count = is_array( $terms ) ? count( $terms ) : 0;
	if ( 1 === $term_count ) {
		$score += 15;
	} elseif ( $term_count >= 5 ) {
		$score -= 30;
	}

	return $score;
}

/**
 * @param int     $product_id Product ID.
 * @param WP_Term $term       Category term.
 * @return bool
 */
function safestore_hero_product_is_usable( $product_id, $term ) {
	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() ) {
		return false;
	}

	return has_term( (int) $term->term_id, 'product_cat', $product_id );
}

/**
 * Featured image data for the hero media column.
 *
 * @param WC_Product $product Product.
 * @return array<string,mixed>|null
 */
function safestore_hero_product_image_payload( $product ) {
	$image_id = (int) $product->get_image_id();
	if ( $image_id <= 0 ) {
		return null;
	}

	$src = wp_get_attachment_image_src( $image_id, 'woocommerce_single' );
	if ( ! is_array( $src ) || empty( $src[0] ) ) {
		$src = wp_get_attachment_image_src( $image_id, 'large' );
	}
	if ( ! is_array( $src ) || empty( $src[0] ) ) {
		return null;
	}

	$alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
	if ( ! is_string( $alt ) || '' === $alt ) {
		$alt = $product->get_name();
	}

	$srcset = wp_get_attachment_image_srcset( $image_id, 'woocommerce_single' );
	$sizes  = wp_get_attachment_image_sizes( $image_id, 'woocommerce_single' );

	return array(
		'src'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
		'srcset' => is_string( $srcset ) ? $srcset : '',
		'sizes'  => is_string( $sizes ) && '' !== $sizes ? $sizes : '(max-width: 900px) 92vw, 42vw',
		'alt'    => $alt,
	);
}

/**
 * Display current + regular prices for overlay math (tax-aware).
 *
 * @param WC_Product $product Product.
 * @return array{current:float,regular:float,from:bool}
 */
function safestore_hero_product_display_prices( $product ) {
	$from    = false;
	$current = 0.0;
	$regular = 0.0;

	if ( $product->is_type( 'variable' ) ) {
		$current = (float) $product->get_variation_price( 'min', true );
		$max     = (float) $product->get_variation_price( 'max', true );
		$from    = $max > $current + 0.0001;
		$regular = $current;

		$prices = $product->get_variation_prices( true );
		if ( $current > 0 && ! empty( $prices['price'] ) && ! empty( $prices['regular_price'] ) ) {
			foreach ( $prices['price'] as $variation_id => $price ) {
				if ( abs( (float) $price - $current ) > 0.0001 ) {
					continue;
				}
				$variation_regular = isset( $prices['regular_price'][ $variation_id ] )
					? (float) $prices['regular_price'][ $variation_id ]
					: $current;
				if ( $variation_regular > $regular ) {
					$regular = $variation_regular;
				}
			}
		}
	} else {
		$current     = (float) wc_get_price_to_display( $product );
		$regular_raw = $product->get_regular_price();
		$regular     = ( '' !== (string) $regular_raw )
			? (float) wc_get_price_to_display( $product, array( 'price' => $regular_raw ) )
			: $current;
	}

	if ( $regular <= 0 ) {
		$regular = $current;
	}

	return array(
		'current' => $current,
		'regular' => $regular,
		'from'    => $from,
	);
}

/**
 * Whole-percent off, or 0 when the product is not actually discounted.
 *
 * @param WC_Product $product Product.
 * @return int
 */
function safestore_hero_product_save_pct( $product ) {
	$prices  = safestore_hero_product_display_prices( $product );
	$current = $prices['current'];
	$regular = $prices['regular'];

	if ( $current <= 0 || $regular <= $current + 0.0001 || ! $product->is_on_sale() ) {
		return 0;
	}

	$save = (int) round( ( ( $regular - $current ) / $regular ) * 100 );
	return $save >= 1 ? $save : 0;
}

/**
 * @param WC_Product $product Product.
 * @return array<string,mixed>|null
 */
function safestore_hero_product_price_payload( $product ) {
	$prices  = safestore_hero_product_display_prices( $product );
	$current = $prices['current'];
	$regular = $prices['regular'];
	$save    = safestore_hero_product_save_pct( $product );
	$on_sale = $save >= 1;

	if ( $current <= 0 ) {
		return null;
	}

	return array(
		'current_html' => wc_price( $current ),
		'regular_html' => $on_sale ? wc_price( $regular ) : '',
		'save_pct'     => $save,
		'from'         => $prices['from'],
	);
}

/**
 * @param WC_Product $product Product.
 * @return array<string,mixed>|null
 */
function safestore_hero_product_reviews_payload( $product ) {
	$count = max( (int) $product->get_rating_count(), (int) $product->get_review_count() );
	$avg   = (float) $product->get_average_rating();

	if ( $count < 1 || $avg <= 0 ) {
		return null;
	}

	return array(
		'average' => round( $avg, 1 ),
		'count'   => $count,
		'width'   => min( 100, max( 0, ( $avg / 5 ) * 100 ) ),
	);
}

/**
 * Markup for floating price + review cards. Empty string when there is no data.
 *
 * @param array $overlay Overlay payload.
 * @return string
 */
function safestore_hero_overlay_markup( $overlay ) {
	if ( ! is_array( $overlay ) ) {
		return '';
	}

	$html = '';

	if ( ! empty( $overlay['price'] ) && is_array( $overlay['price'] ) ) {
		$price = $overlay['price'];
		$html .= '<div class="hero-slide-card hero-slide-card--price">';
		if ( ! empty( $price['regular_html'] ) ) {
			$html .= '<span class="hero-slide-card__old">' . wp_kses_post( $price['regular_html'] ) . '</span>';
		}
		$html .= '<span class="hero-slide-card__now">';
		if ( ! empty( $price['from'] ) ) {
			$html .= '<span class="hero-slide-card__from">' . esc_html__( 'From', 'safestore-minimal' ) . '</span> ';
		}
		$html .= wp_kses_post( $price['current_html'] );
		$html .= '</span>';
		if ( ! empty( $price['save_pct'] ) ) {
			$html .= '<span class="hero-slide-card__save">' . esc_html(
				sprintf(
					/* translators: %d: percent off */
					__( 'Save %d%%', 'safestore-minimal' ),
					(int) $price['save_pct']
				)
			) . '</span>';
		}
		$html .= '</div>';
	}

	if ( ! empty( $overlay['reviews'] ) && is_array( $overlay['reviews'] ) ) {
		$reviews = $overlay['reviews'];
		$label   = sprintf(
			/* translators: 1: average rating, 2: review count */
			_n( '%1$s out of 5 from %2$s review', '%1$s out of 5 from %2$s reviews', (int) $reviews['count'], 'safestore-minimal' ),
			number_format_i18n( (float) $reviews['average'], 1 ),
			number_format_i18n( (int) $reviews['count'] )
		);
		$html .= '<div class="hero-slide-card hero-slide-card--reviews">';
		$html .= '<span class="hero-slide-card__stars" role="img" aria-label="' . esc_attr( $label ) . '">';
		$html .= '<span class="hero-slide-card__stars-bg" aria-hidden="true">★★★★★</span>';
		$html .= '<span class="hero-slide-card__stars-fill" aria-hidden="true" style="width:' . esc_attr( (string) round( (float) $reviews['width'], 2 ) ) . '%">★★★★★</span>';
		$html .= '</span>';
		$html .= '<span class="hero-slide-card__rating">' . esc_html( number_format_i18n( (float) $reviews['average'], 1 ) . '/5' ) . '</span>';
		$html .= '<span class="hero-slide-card__count">' . esc_html(
			sprintf(
				/* translators: %s: number of reviews */
				_n( '%s review', '%s reviews', (int) $reviews['count'], 'safestore-minimal' ),
				number_format_i18n( (int) $reviews['count'] )
			)
		) . '</span>';
		$html .= '</div>';
	}

	return $html;
}

/**
 * Category admin: optional hero product ID.
 *
 * @param WP_Term $term Term.
 */
function safestore_hero_category_field( $term ) {
	$current = ( $term instanceof WP_Term ) ? (int) get_term_meta( $term->term_id, SAFESTORE_HERO_PRODUCT_META, true ) : 0;
	?>
	<tr class="form-field">
		<th scope="row"><label for="sft-hero-product-id"><?php esc_html_e( 'Hero product', 'safestore-minimal' ); ?></label></th>
		<td>
			<input name="sft_hero_product_id" id="sft-hero-product-id" type="number" min="0" step="1" value="<?php echo $current > 0 ? esc_attr( (string) $current ) : ''; ?>" />
			<p class="description"><?php esc_html_e( 'WooCommerce product ID used for the homepage hero image, price, and review cards. Leave empty to auto-pick a product in this category (prefers a photo, a real sale price, and real ratings).', 'safestore-minimal' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'safestore_hero_category_field', 20 );

/**
 * @param int $term_id Term ID.
 */
function safestore_hero_category_field_save( $term_id ) {
	$term_id = (int) $term_id;
	if ( $term_id <= 0 || ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}

	$raw = isset( $_POST['sft_hero_product_id'] ) ? absint( wp_unslash( $_POST['sft_hero_product_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $raw > 0 ) {
		update_term_meta( $term_id, SAFESTORE_HERO_PRODUCT_META, $raw );
	} else {
		delete_term_meta( $term_id, SAFESTORE_HERO_PRODUCT_META );
	}

	$term = get_term( $term_id, 'product_cat' );
	if ( $term instanceof WP_Term ) {
		delete_transient( safestore_hero_overlay_cache_key( $term->slug ) );
	}
}
add_action( 'edited_product_cat', 'safestore_hero_category_field_save' );

/**
 * @param int $product_id Product ID.
 */
function safestore_hero_overlay_flush_product( $product_id ) {
	$product_id = (int) $product_id;
	if ( $product_id <= 0 ) {
		return;
	}

	$terms = get_the_terms( $product_id, 'product_cat' );
	if ( ! is_array( $terms ) ) {
		return;
	}

	foreach ( $terms as $term ) {
		if ( $term instanceof WP_Term ) {
			delete_transient( safestore_hero_overlay_cache_key( $term->slug ) );
		}
	}
}
add_action( 'woocommerce_update_product', 'safestore_hero_overlay_flush_product' );
add_action( 'woocommerce_delete_product', 'safestore_hero_overlay_flush_product' );

/**
 * @param int $comment_id Comment ID.
 */
function safestore_hero_overlay_flush_comment( $comment_id ) {
	$comment = get_comment( $comment_id );
	if ( ! $comment || 'product' !== get_post_type( (int) $comment->comment_post_ID ) ) {
		return;
	}
	safestore_hero_overlay_flush_product( (int) $comment->comment_post_ID );
}
add_action( 'comment_post', 'safestore_hero_overlay_flush_comment' );
add_action( 'wp_set_comment_status', 'safestore_hero_overlay_flush_comment' );

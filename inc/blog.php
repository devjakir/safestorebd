<?php
/**
 * SafeStoreBD — Blog.
 *
 * Wires the WordPress posts into the theme: seeds the /blog/ posts page,
 * loads the blog stylesheet only on blog views, and provides the helpers the
 * templates use (breadcrumb, meta, reading time, share links, related posts,
 * pagination). Templates: home.php, single.php, archive.php, search.php,
 * comments.php, templates/blog-card.php.
 *
 * @package safestore-minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* --------------------------------------------------------------------------
 * Posts page (/blog/)
 * ----------------------------------------------------------------------- */

/**
 * Create the Blog page once and make it the posts page — only when the site
 * uses a static front page and no posts page has been chosen yet, so an
 * admin's own Settings → Reading choice is never overwritten.
 */
function safestore_blog_seed_page() {
	if ( get_option( 'safestore_blog_page_v1' ) ) {
		return;
	}

	// An admin already picked a posts page: respect it, nothing to do.
	if ( (int) get_option( 'page_for_posts' ) ) {
		update_option( 'safestore_blog_page_v1', 1, false );
		return;
	}

	$page_id = (int) get_option( 'safestore_blog_page_id' );
	if ( ! $page_id || ! get_post( $page_id ) ) {
		$page = get_page_by_path( 'blog' );
		if ( $page instanceof WP_Post && 'trash' !== $page->post_status ) {
			$page_id = (int) $page->ID;
			if ( 'publish' !== $page->post_status ) {
				wp_update_post(
					array(
						'ID'          => $page_id,
						'post_status' => 'publish',
					)
				);
			}
		} else {
			$page_id = wp_insert_post(
				array(
					'post_title'   => __( 'Blog', 'safestore-minimal' ),
					'post_name'    => 'blog',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => '',
				),
				true
			);
			if ( is_wp_error( $page_id ) ) {
				return; // Try again on the next request.
			}
		}
		update_option( 'safestore_blog_page_id', (int) $page_id, false );
	}

	// A posts page only exists alongside a static front page. Until the
	// front page is static this retries on each request (two cached
	// option reads), then finishes once.
	if ( 'page' === get_option( 'show_on_front' ) ) {
		update_option( 'page_for_posts', (int) $page_id );
		update_option( 'safestore_blog_page_v1', 1, false );
	}
}
add_action( 'after_switch_theme', 'safestore_blog_seed_page' );
add_action(
	'init',
	static function () {
		if ( ! get_option( 'safestore_blog_page_v1' ) ) {
			safestore_blog_seed_page();
		}
	},
	20
);

/**
 * URL of the blog index.
 *
 * @return string
 */
function safestore_blog_url() {
	$page_id = (int) get_option( 'page_for_posts' );
	if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
		return (string) get_permalink( $page_id );
	}
	return home_url( '/blog/' );
}

/**
 * Title of the blog index (the posts page title, or "Blog").
 *
 * @return string
 */
function safestore_blog_title() {
	$page_id = (int) get_option( 'page_for_posts' );
	$title   = $page_id ? get_the_title( $page_id ) : '';
	return '' !== $title ? $title : __( 'Blog', 'safestore-minimal' );
}

/**
 * True on every blog view: index, single post, post archives, post search.
 *
 * @return bool
 */
function safestore_is_blog_view() {
	if ( is_home() || is_singular( 'post' ) || is_category() || is_tag() || is_author() || is_date() ) {
		return true;
	}
	return is_search() && 'post' === get_query_var( 'post_type' );
}

/* --------------------------------------------------------------------------
 * Assets
 * ----------------------------------------------------------------------- */

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! safestore_is_blog_view() || ! function_exists( 'safestore_page_css' ) ) {
			return;
		}

		// Shared page chrome (hero, CTA band) + the blog's own sheet.
		safestore_page_css( 'pages-shared', 'page-pages-shared.css' );
		safestore_page_css( 'blog', 'page-blog.css' );

		if ( is_singular( 'post' ) ) {
			// Product cards in the "Shop the gear" strip use the shop styles.
			safestore_page_css( 'shop', 'page-shop.css' );

			$js = get_template_directory() . '/js/blog.js';
			if ( file_exists( $js ) ) {
				wp_enqueue_script(
					'safestore-blog',
					get_template_directory_uri() . '/js/blog.js',
					array(),
					(string) filemtime( $js ),
					array(
						'in_footer' => true,
						'strategy'  => 'defer',
					)
				);
			}
		}
	},
	30
);

/* --------------------------------------------------------------------------
 * Query + excerpt tuning
 * ----------------------------------------------------------------------- */

/**
 * Page sizes that fill the 3-column grid evenly.
 */
add_action(
	'pre_get_posts',
	static function ( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}
		// Index and archives: a 3×3 grid of cards on every page.
		if ( $query->is_home() ) {
			$query->set( 'posts_per_page', 9 );
			// WordPress adds sticky posts on top of posts_per_page, breaking the
			// grid. Sort them first in SQL instead (see posts_orderby below).
			$query->set( 'ignore_sticky_posts', true );
			$query->set( 'sft_sticky_first', true );
		} elseif ( $query->is_category() || $query->is_tag() || $query->is_author() || $query->is_date() ) {
			$query->set( 'posts_per_page', 9 );
		}
		if ( $query->is_search() && 'post' === $query->get( 'post_type' ) ) {
			$query->set( 'posts_per_page', 9 );
		}
	}
);

/** Sticky posts first on the blog index, without changing the page size. */
add_filter(
	'posts_orderby',
	static function ( $orderby, $query ) {
		if ( ! $query->get( 'sft_sticky_first' ) ) {
			return $orderby;
		}
		$sticky = array_filter( array_map( 'intval', (array) get_option( 'sticky_posts', array() ) ) );
		if ( empty( $sticky ) ) {
			return $orderby;
		}
		global $wpdb;
		return "CASE WHEN {$wpdb->posts}.ID IN (" . implode( ',', $sticky ) . ') THEN 0 ELSE 1 END ASC, ' . $orderby;
	},
	10,
	2
);

/** "Category: Safety Shoes" → "Safety Shoes" (the hero eyebrow names the type). */
add_filter(
	'get_the_archive_title_prefix',
	static function ( $prefix ) {
		return safestore_is_blog_view() ? '' : $prefix;
	}
);

add_filter(
	'excerpt_length',
	static function ( $length ) {
		return safestore_is_blog_view() ? 24 : $length;
	},
	20
);

add_filter(
	'excerpt_more',
	static function ( $more ) {
		return safestore_is_blog_view() ? '…' : $more;
	},
	20
);

/* --------------------------------------------------------------------------
 * Template helpers
 * ----------------------------------------------------------------------- */

/**
 * Estimated reading time in minutes. Counts whitespace-separated words so
 * Bangla text is measured correctly (str_word_count only knows Latin).
 *
 * @param int|WP_Post|null $post Post.
 * @return int
 */
function safestore_blog_reading_minutes( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 1;
	}
	$text  = trim( wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ) );
	$words = '' === $text ? 0 : count( preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY ) );
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Primary category of a post (Rank Math primary term when set).
 *
 * @param int|WP_Post|null $post Post.
 * @return WP_Term|null
 */
function safestore_blog_primary_category( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$primary = (int) get_post_meta( $post->ID, 'rank_math_primary_category', true );
	if ( $primary ) {
		$term = get_term( $primary, 'category' );
		if ( $term instanceof WP_Term ) {
			return $term;
		}
	}
	$cats = get_the_category( $post->ID );
	return ! empty( $cats ) ? $cats[0] : null;
}

/**
 * Category pill for a card/post.
 *
 * @param int|WP_Post|null $post Post.
 */
function safestore_blog_category_pill( $post = null ) {
	$cat = safestore_blog_primary_category( $post );
	if ( ! $cat || 'uncategorized' === $cat->slug ) {
		return;
	}
	printf(
		'<a class="sft-blog-pill" href="%1$s">%2$s</a>',
		esc_url( get_category_link( $cat ) ),
		esc_html( $cat->name )
	);
}

/**
 * Date · reading time line.
 *
 * @param int|WP_Post|null $post Post.
 */
function safestore_blog_meta( $post = null ) {
	$post    = get_post( $post );
	$minutes = safestore_blog_reading_minutes( $post );
	?>
	<span class="sft-blog-meta">
		<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $post ) ); ?>"><?php echo esc_html( get_the_date( '', $post ) ); ?></time>
		<span class="sft-blog-meta__dot" aria-hidden="true">·</span>
		<span>
			<?php
			/* translators: %d: minutes */
			echo esc_html( sprintf( _n( '%d min read', '%d min read', $minutes, 'safestore-minimal' ), $minutes ) );
			?>
		</span>
	</span>
	<?php
}

/**
 * Card meta row: the date on the left, the reading time on the right, each
 * behind a small icon. Separate from safestore_blog_meta(), which runs inline
 * in the article hero.
 *
 * @param int|WP_Post|null $post Post.
 */
function safestore_blog_card_meta( $post = null ) {
	$post    = get_post( $post );
	$minutes = safestore_blog_reading_minutes( $post );
	?>
	<div class="sft-blog-card__meta">
		<span class="sft-blog-card__meta-item">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
			<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $post ) ); ?>"><?php echo esc_html( get_the_date( '', $post ) ); ?></time>
		</span>
		<span class="sft-blog-card__meta-item">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
			<?php
			/* translators: %d: minutes */
			echo esc_html( sprintf( _n( '%d min read', '%d min read', $minutes, 'safestore-minimal' ), $minutes ) );
			?>
		</span>
	</div>
	<?php
}

/**
 * Visual breadcrumb: Home / Blog / [Category] / [Current].
 */
function safestore_blog_breadcrumb() {
	$items   = array();
	$items[] = array( __( 'Home', 'safestore-minimal' ), home_url( '/' ) );

	if ( is_home() ) {
		$items[] = array( safestore_blog_title(), '' );
	} else {
		$items[] = array( safestore_blog_title(), safestore_blog_url() );
		if ( is_singular( 'post' ) ) {
			$cat = safestore_blog_primary_category();
			if ( $cat && 'uncategorized' !== $cat->slug ) {
				$items[] = array( $cat->name, get_category_link( $cat ) );
			}
			$items[] = array( get_the_title(), '' );
		} elseif ( is_search() ) {
			$items[] = array( __( 'Search', 'safestore-minimal' ), '' );
		} else {
			$items[] = array( wp_strip_all_tags( get_the_archive_title() ), '' );
		}
	}
	?>
	<nav class="sft-blog-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'safestore-minimal' ); ?>">
		<ol>
			<?php foreach ( $items as $item ) : ?>
				<li>
					<?php if ( '' !== $item[1] ) : ?>
						<a href="<?php echo esc_url( $item[1] ); ?>"><?php echo esc_html( $item[0] ); ?></a>
					<?php else : ?>
						<span aria-current="page"><?php echo esc_html( $item[0] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * Category chips for the blog index / archives (only categories with posts).
 */
function safestore_blog_category_nav() {
	$cats = get_categories(
		array(
			'hide_empty' => true,
			'exclude'    => array( (int) get_option( 'default_category' ) ),
			'number'     => 12,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	if ( empty( $cats ) ) {
		return;
	}
	$current = is_category() ? get_queried_object_id() : 0;
	?>
	<nav class="sft-blog-cats" aria-label="<?php esc_attr_e( 'Blog categories', 'safestore-minimal' ); ?>">
		<a class="sft-blog-cats__chip<?php echo is_home() ? ' is-active' : ''; ?>" href="<?php echo esc_url( safestore_blog_url() ); ?>"<?php echo is_home() ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'All articles', 'safestore-minimal' ); ?></a>
		<?php foreach ( $cats as $cat ) : ?>
			<?php $active = ( $current === (int) $cat->term_id ); ?>
			<a class="sft-blog-cats__chip<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_category_link( $cat ) ); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $cat->name ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php
}

/**
 * Search form scoped to posts.
 */
function safestore_blog_search_form() {
	$value = ( is_search() && 'post' === get_query_var( 'post_type' ) ) ? get_search_query() : '';
	?>
	<form class="sft-blog-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="sft-blog-search-input"><?php esc_html_e( 'Search articles', 'safestore-minimal' ); ?></label>
		<input id="sft-blog-search-input" type="search" name="s" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'Search articles…', 'safestore-minimal' ); ?>" />
		<input type="hidden" name="post_type" value="post" />
		<button type="submit"><?php esc_html_e( 'Search', 'safestore-minimal' ); ?></button>
	</form>
	<?php
}

/**
 * Numbered pagination for blog lists.
 */
function safestore_blog_pagination() {
	the_posts_pagination(
		array(
			'mid_size'           => 1,
			'prev_text'          => __( '← Newer', 'safestore-minimal' ),
			'next_text'          => __( 'Older →', 'safestore-minimal' ),
			'screen_reader_text' => __( 'Articles navigation', 'safestore-minimal' ),
			'class'              => 'sft-blog-pagination',
		)
	);
}

/**
 * Share links for the current post. No third-party scripts: plain share
 * URLs plus a copy-link button (js/blog.js uses the native share sheet on
 * phones when available).
 */
function safestore_blog_share() {
	$url   = rawurlencode( get_permalink() );
	$title = rawurlencode( wp_strip_all_tags( get_the_title() ) );
	$links = array(
		'facebook' => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $url ),
		'whatsapp' => array( 'WhatsApp', 'https://wa.me/?text=' . $title . '%20' . $url ),
		'linkedin' => array( 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url ),
		'x'        => array( 'X', 'https://x.com/intent/post?url=' . $url . '&text=' . $title ),
	);
	?>
	<div class="sft-blog-share" aria-label="<?php esc_attr_e( 'Share this article', 'safestore-minimal' ); ?>">
		<span class="sft-blog-share__label"><?php esc_html_e( 'Share', 'safestore-minimal' ); ?></span>
		<?php foreach ( $links as $key => $link ) : ?>
			<a class="sft-blog-share__btn sft-blog-share__btn--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $link[1] ); ?>" target="_blank" rel="noopener noreferrer nofollow" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: network name */ __( 'Share on %s', 'safestore-minimal' ), $link[0] ) ); ?>">
				<?php echo esc_html( $link[0] ); ?>
			</a>
		<?php endforeach; ?>
		<button type="button" class="sft-blog-share__btn sft-blog-share__btn--copy" data-sft-copy-link="<?php echo esc_url( get_permalink() ); ?>" data-sft-copied="<?php esc_attr_e( 'Link copied', 'safestore-minimal' ); ?>">
			<?php esc_html_e( 'Copy link', 'safestore-minimal' ); ?>
		</button>
	</div>
	<?php
}

/**
 * Related posts: same categories first, topped up with the latest posts.
 *
 * @param int $count Number of posts.
 * @return WP_Post[]
 */
function safestore_blog_related_posts( $count = 3 ) {
	$post_id = get_the_ID();
	$cats    = wp_get_post_categories( $post_id );
	$found   = array();

	if ( ! empty( $cats ) ) {
		$found = get_posts(
			array(
				'post_type'           => 'post',
				'posts_per_page'      => $count,
				'post__not_in'        => array( $post_id ),
				'category__in'        => $cats,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
	}

	if ( count( $found ) < $count ) {
		$exclude = array_merge( array( $post_id ), wp_list_pluck( $found, 'ID' ) );
		$found   = array_merge(
			$found,
			get_posts(
				array(
					'post_type'           => 'post',
					'posts_per_page'      => $count - count( $found ),
					'post__not_in'        => $exclude,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				)
			)
		);
	}

	return $found;
}

/* --------------------------------------------------------------------------
 * Site wiring
 * ----------------------------------------------------------------------- */

/** Blog link on the HTML sitemap page (Company group). */
add_filter(
	'safestore_minimal_sitemap_groups',
	static function ( $groups ) {
		foreach ( $groups as &$group ) {
			if ( isset( $group['title'] ) && __( 'Company', 'safestore-minimal' ) === $group['title'] ) {
				$group['links'][] = array(
					'label' => safestore_blog_title(),
					'url'   => safestore_blog_url(),
				);
				break;
			}
		}
		unset( $group );
		return $groups;
	}
);

/** Body classes for styling hooks. */
add_filter(
	'body_class',
	static function ( $classes ) {
		if ( safestore_is_blog_view() ) {
			$classes[] = 'sft-blog-view';
		}
		return $classes;
	}
);

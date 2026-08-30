<?php
/**
 * Home — Featured Products mobile slider: arrows + dots over the scroll-snap
 * track. Inlined in the footer, home page only, inert above the breakpoint.
 *
 * @package SafeStore_Minimal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Must match the max-width: 480px block in style.css. */
const SAFESTORE_HOME_SLIDER_MQ = '(max-width: 480px)';

/**
 * Does this request render the Featured Products row?
 *
 * @return bool
 */
function safestore_home_slider_active() {
	if ( ! is_front_page() && ! is_page_template( 'page-home.php' ) ) {
		return false;
	}

	// No WooCommerce means no [products] output.
	return class_exists( 'WooCommerce' );
}

/**
 * Slider behaviour, with labels and breakpoint injected.
 *
 * @return string
 */
function safestore_home_slider_js() {
	$text = array(
		'carousel' => __( 'Featured products', 'safestore-minimal' ),
		'prev'     => __( 'Previous product', 'safestore-minimal' ),
		'next'     => __( 'Next product', 'safestore-minimal' ),
		/* translators: %d: product position in the slider. */
		'goTo'     => __( 'Go to product %d', 'safestore-minimal' ),
	);

	$js = <<<'JS'
(function () {
	'use strict';

	var MQ = matchMedia(__MQ__);
	var TEXT = __TEXT__;
	var GLYPH = { prev: '15 5 8 12 15 19', next: '9 5 16 12 9 19' };
	var built = false;

	function arrow(dir) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'sft-slider-arrow sft-slider-arrow--' + dir;
		btn.setAttribute('aria-label', TEXT[dir]);
		btn.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="' + GLYPH[dir] + '"/></svg>';
		return btn;
	}

	function build() {
		if (built || !MQ.matches) return;

		var section = document.querySelector('.sft-bestsellers');
		var track = section && section.querySelector('ul.products');
		if (!track) return;

		var cards = [].filter.call(track.children, function (el) {
			return el.tagName === 'LI';
		});
		if (cards.length < 2) return;

		built = true;
		var smooth = !matchMedia('(prefers-reduced-motion: reduce)').matches;

		// A scrollable region must be focusable and announced.
		track.tabIndex = 0;
		track.setAttribute('role', 'group');
		track.setAttribute('aria-roledescription', 'carousel');
		track.setAttribute('aria-label', TEXT.carousel);

		var prev = arrow('prev');
		var next = arrow('next');

		var dotList = document.createElement('div');
		dotList.className = 'sft-slider-dots';

		var dots = cards.map(function (card, index) {
			var dot = document.createElement('button');
			dot.type = 'button';
			dot.className = 'sft-slider-dot';
			dot.setAttribute('aria-label', TEXT.goTo.replace('%d', index + 1));
			dot.onclick = function () { goTo(index); };
			dotList.appendChild(dot);
			return dot;
		});

		var controls = document.createElement('div');
		controls.className = 'sft-slider-controls';
		controls.appendChild(prev);
		controls.appendChild(dotList);
		controls.appendChild(next);
		track.parentNode.insertBefore(controls, track.nextSibling);

		// Card nearest the left edge — where scroll-snap settles.
		function currentIndex() {
			var origin = track.scrollLeft + track.offsetLeft;
			var best = 0;
			var shortest = Infinity;
			for (var i = 0; i < cards.length; i++) {
				var gap = Math.abs(cards[i].offsetLeft - origin);
				if (gap < shortest) {
					shortest = gap;
					best = i;
				}
			}
			return best;
		}

		function goTo(index) {
			var card = cards[Math.max(0, Math.min(index, cards.length - 1))];
			if (!card) return;
			track.scrollTo({
				left: card.offsetLeft - track.offsetLeft,
				behavior: smooth ? 'smooth' : 'auto'
			});
		}

		function sync() {
			var active = currentIndex();
			dots.forEach(function (dot, i) {
				dot.classList.toggle('is-active', i === active);
				if (i === active) {
					dot.setAttribute('aria-current', 'true');
				} else {
					dot.removeAttribute('aria-current');
				}
			});
			// 1px tolerance for sub-pixel track widths.
			prev.disabled = track.scrollLeft <= 1;
			next.disabled = track.scrollLeft >= track.scrollWidth - track.clientWidth - 1;
		}

		prev.onclick = function () { goTo(currentIndex() - 1); };
		next.onclick = function () { goTo(currentIndex() + 1); };

		var queued = false;
		track.addEventListener('scroll', function () {
			if (queued) return;
			queued = true;
			requestAnimationFrame(function () {
				queued = false;
				sync();
			});
		}, { passive: true });

		addEventListener('resize', sync, { passive: true });
		sync();
	}

	build();

	// Covers a window dragged narrow, or a tablet rotated.
	if (MQ.addEventListener) {
		MQ.addEventListener('change', build);
	} else if (MQ.addListener) {
		MQ.addListener(build);
	}
})();
JS;

	return strtr(
		$js,
		array(
			'__MQ__'   => wp_json_encode( SAFESTORE_HOME_SLIDER_MQ ),
			'__TEXT__' => wp_json_encode( $text ),
		)
	);
}

/**
 * Print the slider inline in the footer.
 */
function safestore_home_slider_print() {
	if ( ! safestore_home_slider_active() ) {
		return;
	}

	$js = safestore_home_slider_js();

	// wp_print_inline_script_tag() allows a CSP nonce.
	if ( function_exists( 'wp_print_inline_script_tag' ) ) {
		wp_print_inline_script_tag( $js, array( 'id' => 'safestore-home-slider-js' ) );
		return;
	}

	echo '<script id="safestore-home-slider-js">' . $js . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'safestore_home_slider_print', 20 );

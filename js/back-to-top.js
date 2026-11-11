/**
 * Back-to-top button (inc/back-to-top.php, css/back-to-top.css).
 *
 * - Appears once the visitor is ~one screen down; hides again near the top.
 * - Ring shows how far down the page they are.
 * - Eased scroll with the same feel in every browser (the native
 *   `behavior: 'smooth'` duration varies a lot between engines). Very long
 *   pages jump most of the way first so the glide stays short and readable.
 * - Any wheel / touch / key from the visitor cancels the glide.
 * - prefers-reduced-motion → instant jump.
 * - Keyboard activation hands focus to the top of the page.
 */
(function () {
	'use strict';

	var btn = document.querySelector('.sft-btt');
	if (!btn) {
		return;
	}

	var root = document.documentElement;
	var progress = btn.querySelector('.sft-btt__progress');
	var CIRC = 138.23; // 2πr, r = 22 (css/back-to-top.css)
	var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

	var visible = false;
	var ticking = false;
	var animating = false;
	var frame = 0;
	var lastOffset = -1;

	function scrollY() {
		return window.pageYOffset || root.scrollTop || 0;
	}

	function threshold() {
		return Math.max(400, window.innerHeight * 0.9);
	}

	function setVisible(on) {
		if (on === visible) {
			return;
		}
		visible = on;
		btn.classList.toggle('is-visible', on);
		if (on) {
			btn.removeAttribute('aria-hidden');
			btn.removeAttribute('tabindex');
		} else {
			if (document.activeElement === btn) {
				btn.blur();
			}
			btn.setAttribute('aria-hidden', 'true');
			btn.setAttribute('tabindex', '-1');
		}
	}

	function update() {
		ticking = false;
		var y = scrollY();
		setVisible(y > threshold());

		if (!visible || !progress) {
			return;
		}
		var max = Math.max(1, root.scrollHeight - window.innerHeight);
		var ratio = Math.min(1, Math.max(0, y / max));
		var offset = Math.round(CIRC * (1 - ratio) * 100) / 100;
		if (offset !== lastOffset) {
			lastOffset = offset;
			progress.style.strokeDashoffset = offset;
		}
	}

	function requestUpdate() {
		if (!ticking) {
			ticking = true;
			window.requestAnimationFrame(update);
		}
	}

	/* ---- glide ------------------------------------------------------- */

	function easeInOutCubic(t) {
		return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
	}

	// Explicit 'instant' so html { scroll-behavior: smooth } (style.css) does
	// not turn each of our frames into its own native smooth scroll.
	function jumpTo(y) {
		try {
			window.scrollTo({ top: y, left: 0, behavior: 'instant' });
		} catch (e) {
			window.scrollTo(0, y);
		}
	}

	function stop() {
		if (!animating) {
			return;
		}
		animating = false;
		window.cancelAnimationFrame(frame);
		removeCancelers();
	}

	function onUserIntent(event) {
		// Keys that scroll the page (or Escape) take control back.
		if (event.type === 'keydown') {
			var k = event.key;
			if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' ', 'Spacebar', 'Escape'].indexOf(k) === -1) {
				return;
			}
		}
		stop();
	}

	var cancelEvents = ['wheel', 'touchstart', 'keydown', 'mousedown'];

	function addCancelers() {
		cancelEvents.forEach(function (type) {
			window.addEventListener(type, onUserIntent, { passive: true });
		});
	}

	function removeCancelers() {
		cancelEvents.forEach(function (type) {
			window.removeEventListener(type, onUserIntent, { passive: true });
		});
	}

	function glideToTop(done) {
		var start = scrollY();
		if (start <= 0) {
			done();
			return;
		}

		if (reduceMotion && reduceMotion.matches) {
			jumpTo(0);
			done();
			return;
		}

		// Long pages: jump to ~2.5 screens from the top, then glide.
		var cap = window.innerHeight * 2.5;
		if (start > cap) {
			jumpTo(cap);
			start = cap;
		}

		var duration = Math.min(750, Math.max(380, start * 0.35));
		var t0 = null;
		animating = true;
		addCancelers();

		function step(now) {
			if (!animating) {
				return;
			}
			if (t0 === null) {
				t0 = now;
			}
			var t = Math.min(1, (now - t0) / duration);
			jumpTo(Math.round(start * (1 - easeInOutCubic(t))));
			if (t < 1) {
				frame = window.requestAnimationFrame(step);
			} else {
				stop();
				done();
			}
		}
		frame = window.requestAnimationFrame(step);
	}

	// First link the visitor can actually see (some header links are hidden per breakpoint).
	function focusTop() {
		var links = document.querySelectorAll('header a[href], main a[href]');
		var target = null;
		for (var i = 0; i < links.length; i++) {
			if (links[i].getClientRects().length && getComputedStyle(links[i]).visibility !== 'hidden') {
				target = links[i];
				break;
			}
		}
		if (target) {
			try {
				target.focus({ preventScroll: true });
			} catch (e) {
				target.focus();
			}
		}
	}

	function track(depth) {
		var dl = window.dataLayer;
		if (dl && typeof dl.push === 'function') {
			dl.push({ event: 'back_to_top', scroll_depth_percent: depth });
		}
	}

	btn.addEventListener('click', function (event) {
		event.preventDefault();
		if (animating) {
			return;
		}
		// detail === 0 → activated from the keyboard (Enter / Space).
		var fromKeyboard = event.detail === 0;
		var max = Math.max(1, root.scrollHeight - window.innerHeight);
		track(Math.round(Math.min(1, scrollY() / max) * 100));

		glideToTop(function () {
			if (fromKeyboard) {
				focusTop();
			}
			requestUpdate();
		});
	});

	window.addEventListener('scroll', requestUpdate, { passive: true });
	window.addEventListener('resize', requestUpdate, { passive: true });
	window.addEventListener('pageshow', requestUpdate);
	update();
})();

/**
 * SafeStore mobile & tablet navigation.
 */
(function () {
	'use strict';

	var mq = window.matchMedia('(max-width: 1024px)');
	var header = document.querySelector('.sft-header');
	var bar = document.querySelector('.sft-header-sticky');
	var drawer = document.getElementById('offcanvas-menu');
	var trigger = document.querySelector('.mobile-menu-trigger');
	var catTab = document.querySelector('[data-sft-open-categories]');

	/* ------------------------------------------------------------------
	 * Publish the header's rendered height.
	 * ---------------------------------------------------------------- */

	function syncHeaderHeight() {
		if (!bar) {
			return;
		}
		var h = Math.round(bar.getBoundingClientRect().height);
		if (h > 0) {
			document.documentElement.style.setProperty('--sft-header-h', h + 'px');
		}
	}

	syncHeaderHeight();
	window.addEventListener('resize', syncHeaderHeight, { passive: true });
	window.addEventListener('orientationchange', syncHeaderHeight);

	if (bar && typeof window.ResizeObserver === 'function') {
		new window.ResizeObserver(syncHeaderHeight).observe(bar);
	}

	/* ------------------------------------------------------------------
	 * Categories tab → the drawer that already exists in the header.
	 * ---------------------------------------------------------------- */

	if (catTab && trigger) {
		catTab.addEventListener('click', function (event) {
			event.preventDefault();
			// Never leave the menu opening behind a hidden header, and make
			// sure it anchors to the header's current height.
			showHeader();
			syncHeaderHeight();
			trigger.click();
		});
	}

	// Mirror the drawer's open state onto the tab so the label reads correctly
	// to assistive tech and the active colour matches what the user sees.
	if (catTab && drawer && 'MutationObserver' in window) {
		var observer = new MutationObserver(function () {
			var open = drawer.classList.contains('is-active');
			catTab.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (open) {
				// Covers the header hamburger too, not just the bottom-bar tab.
				showHeader();
				syncHeaderHeight();
				// One panel at a time — opening the drawer dismisses search.
				setSearch(false);
			}
		});
		observer.observe(drawer, { attributes: true, attributeFilter: ['class'] });
	}

	/* ------------------------------------------------------------------
	 * Icon-triggered search.
	 * ---------------------------------------------------------------- */

	var searchToggle = document.querySelector('[data-sft-search-toggle]');
	var searchWrap = document.getElementById('sft-header-search-panel');
	var searchBackdrop = document.querySelector('[data-sft-search-backdrop]');
	var searchField = searchWrap
		? searchWrap.querySelector('input[type="search"], input[name="s"]')
		: null;

	function searchOpen() {
		return !!(header && header.classList.contains('is-search-open'));
	}

	function setSearch(open) {
		if (!header || !searchToggle) {
			return;
		}
		header.classList.toggle('is-search-open', open);
		searchToggle.setAttribute('aria-expanded', open ? 'true' : 'false');

		if (open) {
			showHeader();
			syncHeaderHeight();
			if (searchField) {
				// Focus only once visibility has flipped — iOS Safari drops a
				// focus() call aimed at a still-hidden element.
				window.setTimeout(function () {
					searchField.focus({ preventScroll: true });
				}, 60);
			}
		} else if (searchField && document.activeElement === searchField) {
			searchField.blur();
		}
	}

	if (header && searchToggle) {
		header.classList.add('has-js-search');

		searchToggle.addEventListener('click', function (event) {
			event.preventDefault();
			var next = !searchOpen();
			// One panel at a time — opening search dismisses the drawer.
			if (next && drawerOpen() && trigger) {
				trigger.click();
			}
			setSearch(next);
		});
	}

	if (searchBackdrop) {
		searchBackdrop.addEventListener('click', function () {
			setSearch(false);
		});
	}

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && searchOpen()) {
			setSearch(false);
		}
	});

	// A tap anywhere outside the header dismisses the panel.
	document.addEventListener('click', function (event) {
		if (!searchOpen()) {
			return;
		}
		if (bar && bar.contains(event.target)) {
			return;
		}
		setSearch(false);
	});

	/* ------------------------------------------------------------------
	 * Hide-on-scroll.
	 * ---------------------------------------------------------------- */

	if (!header || !bar) {
		return;
	}

	var REVEAL_ZONE = 120; // Always show the header this close to the top.
	var DELTA = 6;         // Ignore sub-pixel and rubber-band jitter.

	var lastY = window.pageYOffset || 0;
	var ticking = false;
	var hidden = false;

	function drawerOpen() {
		return !!(drawer && drawer.classList.contains('is-active'));
	}

	function headerHasFocus() {
		var active = document.activeElement;
		return !!(active && bar.contains(active));
	}

	function showHeader() {
		if (!header || !hidden) {
			return;
		}
		hidden = false;
		header.classList.remove('is-nav-hidden');
	}

	function hideHeader() {
		if (!header || hidden) {
			return;
		}
		hidden = true;
		header.classList.add('is-nav-hidden');
	}

	function update() {
		ticking = false;

		var y = window.pageYOffset || 0;

		// Elevation: flat at rest, lifted once content passes beneath.
		header.classList.toggle('is-scrolled', y > 8);

		if (!mq.matches) {
			showHeader();
			lastY = y;
			return;
		}

		// Near the top, mid-search, or with a panel open: stay put.
		if (y <= REVEAL_ZONE || drawerOpen() || searchOpen() || headerHasFocus()) {
			showHeader();
			lastY = y;
			return;
		}

		var diff = y - lastY;
		if (Math.abs(diff) < DELTA) {
			return;
		}

		if (diff > 0) {
			hideHeader();
		} else {
			showHeader();
		}

		lastY = y;
	}

	function onScroll() {
		if (ticking) {
			return;
		}
		ticking = true;
		window.requestAnimationFrame(update);
	}

	window.addEventListener('scroll', onScroll, { passive: true });

	// A focused search field must never scroll out of reach.
	bar.addEventListener('focusin', showHeader);

	// Crossing the breakpoint (rotation, desktop resize) resets the state.
	var onChange = function () {
		lastY = window.pageYOffset || 0;
		showHeader();
	};
	if (typeof mq.addEventListener === 'function') {
		mq.addEventListener('change', onChange);
	} else if (typeof mq.addListener === 'function') {
		mq.addListener(onChange);
	}

	// Restoring a page from bfcache can land mid-document with a stale state.
	window.addEventListener('pageshow', onChange);
})();

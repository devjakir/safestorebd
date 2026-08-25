/**
 * Shop filter toolbar behaviour.
 *
 * 1. Dedupe: if the legacy Code Snippets entry (or HUSKY auto-insert) prints a
 *    second filter bar, remove every bar after the first. CSS hides them too
 *    (.sft-shop-filter ~ .sft-shop-filter) — this removes the dead DOM.
 * 2. Mobile: toggle the panel behind the "Filters" button.
 *
 * Re-runs after HUSKY AJAX redraws, which replace parts of the DOM.
 */
(function () {
	'use strict';

	function dedupeBars() {
		var bars = document.querySelectorAll('.sft-shop-filter');
		for (var i = 1; i < bars.length; i++) {
			if (bars[i].parentNode) {
				bars[i].parentNode.removeChild(bars[i]);
			}
		}
	}

	function bindToggle() {
		var bar = document.querySelector('.sft-shop-filter');
		if (!bar) {
			return;
		}

		var toggle = bar.querySelector('.sft-shop-filter__toggle');
		if (!toggle || toggle.dataset.sftBound) {
			return;
		}
		toggle.dataset.sftBound = '1';

		toggle.addEventListener('click', function () {
			var open = bar.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	}

	function init() {
		dedupeBars();
		bindToggle();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	// HUSKY fires jQuery events after AJAX redraws; re-run defensively on a
	// short delay as well so we don't depend on jQuery being present.
	document.addEventListener('woof_ajax_done', init);
	if (window.jQuery) {
		window.jQuery(document).on('woof_ajax_done', init);
	}
})();

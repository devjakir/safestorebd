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

	// Archive intro: clamp long descriptions behind a Read more / Show less toggle (text stays in the DOM for SEO).
	function bindDescToggle() {
		var desc = document.querySelector('.sft-shop-desc');
		var text = desc && desc.querySelector('.sft-shop-desc__text');
		if (!text || desc.dataset.sftBound) {
			return;
		}
		desc.dataset.sftBound = '1';

		desc.classList.add('is-clamped');
		if (text.scrollHeight <= text.clientHeight + 2) {
			desc.classList.remove('is-clamped');
			return;
		}

		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'sft-shop-desc__more';
		btn.setAttribute('aria-controls', text.id);
		btn.setAttribute('aria-expanded', 'false');
		btn.textContent = 'Read more';
		desc.appendChild(btn);

		btn.addEventListener('click', function () {
			var clamped = desc.classList.toggle('is-clamped');
			btn.setAttribute('aria-expanded', clamped ? 'false' : 'true');
			btn.textContent = clamped ? 'Read more' : 'Show less';
		});
	}

	// Collapsible sidebar sections (StarTech style). Price stays open; Availability also folds the On sale card beneath it.
	var SECTIONS = {
		woof_container_product_cat: 'Product categories',
		woof_container_product_brand: 'Product brands',
		woof_checkbox_instock_container: 'Availability'
	};
	var collapsed = {};

	function bindSections() {
		var bar = document.querySelector('.sft-shop-filter');
		if (!bar) {
			return;
		}

		Object.keys(SECTIONS).forEach(function (key) {
			var sec = bar.querySelector('.' + key);
			if (!sec) {
				return;
			}
			sec.classList.toggle('is-collapsed', !!collapsed[key]);
			if (sec.querySelector(':scope > .sft-fsec-toggle')) {
				return;
			}

			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'sft-fsec-toggle';
			btn.setAttribute('aria-label', SECTIONS[key]);
			btn.setAttribute('aria-expanded', collapsed[key] ? 'false' : 'true');
			sec.insertBefore(btn, sec.firstChild);

			btn.addEventListener('click', function () {
				collapsed[key] = sec.classList.toggle('is-collapsed');
				btn.setAttribute('aria-expanded', collapsed[key] ? 'false' : 'true');
			});
		});
	}

	function init() {
		dedupeBars();
		bindToggle();
		bindSections();
	}

	// HUSKY fires no event after AJAX redraws, so watch the filter and results for replaced nodes.
	function observe() {
		if (!window.MutationObserver) {
			return;
		}
		var pending = false;
		var mo = new MutationObserver(function () {
			if (pending) {
				return;
			}
			pending = true;
			window.requestAnimationFrame(function () {
				pending = false;
				init();
			});
		});
		var layout = document.querySelector('.sft-shop-layout') || document.querySelector('.sft-shop');
		if (layout) {
			mo.observe(layout, { childList: true, subtree: true });
		}
	}

	function boot() {
		init();
		bindDescToggle();
		observe();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

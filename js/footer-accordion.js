/**
 * SafeStore footer link-list accordions.
 */
(function () {
	'use strict';

	var groups = document.querySelectorAll('[data-sfx-acc]');
	if (!groups.length) {
		return;
	}

	var mq = window.matchMedia('(max-width: 767px)');
	var wired = false;

	function panelOf(group) {
		return group.querySelector('.sfx-links');
	}

	function buttonOf(group) {
		return group.querySelector('.sfx-acc__btn');
	}

	function collapse(group) {
		var panel = panelOf(group);
		var btn = buttonOf(group);
		if (!panel || !btn) {
			return;
		}
		group.classList.add('is-acc');
		panel.hidden = true;
		btn.setAttribute('aria-expanded', 'false');
	}

	function expandAll(group) {
		var panel = panelOf(group);
		var btn = buttonOf(group);
		if (!panel || !btn) {
			return;
		}
		group.classList.remove('is-acc');
		panel.hidden = false;
		btn.setAttribute('aria-expanded', 'false');
	}

	function toggle(group) {
		var panel = panelOf(group);
		var btn = buttonOf(group);
		if (!panel || !btn) {
			return;
		}
		var open = panel.hidden;
		panel.hidden = !open;
		btn.setAttribute('aria-expanded', open ? 'true' : 'false');
	}

	function wire() {
		if (wired) {
			return;
		}
		wired = true;
		Array.prototype.forEach.call(groups, function (group) {
			var btn = buttonOf(group);
			if (!btn) {
				return;
			}
			btn.addEventListener('click', function () {
				// Only meaningful while collapsed; above the breakpoint the
				// button is not rendered at all.
				if (group.classList.contains('is-acc')) {
					toggle(group);
				}
			});
		});
	}

	function apply() {
		Array.prototype.forEach.call(groups, function (group) {
			if (mq.matches) {
				collapse(group);
			} else {
				expandAll(group);
			}
		});
	}

	wire();
	apply();

	if (typeof mq.addEventListener === 'function') {
		mq.addEventListener('change', apply);
	} else if (typeof mq.addListener === 'function') {
		mq.addListener(apply);
	}
})();

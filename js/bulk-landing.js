/* Bulk landing — kit pre-fill, quote form (AJAX with no-JS POST fallback),
 * FAQ accordion, mobile sticky bar, tracking. No jQuery. */
(function () {
	'use strict';

	var cfg = window.SBL || {};
	var msg = cfg.msg || {};
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* ---- Tracking: Meta Pixel, GA4, dataLayer — each guarded ---------------- */

	var EVENTS = {
		lead: { fb: 'Lead', ga: 'generate_lead' },
		whatsapp: { fb: 'Contact', ga: 'click_whatsapp' },
		phone: { fb: 'Contact', ga: 'click_call' }
	};

	function track(type, params) {
		var ev = EVENTS[type];
		if (!ev) {
			return;
		}
		params = params || {};
		try {
			if (typeof window.fbq === 'function') {
				window.fbq('track', ev.fb, params);
			}
		} catch (e) {}
		try {
			if (typeof window.gtag === 'function') {
				window.gtag('event', ev.ga, params);
			}
		} catch (e) {}
		try {
			window.dataLayer = window.dataLayer || [];
			var push = { event: ev.ga };
			Object.keys(params).forEach(function (k) {
				push[k] = params[k];
			});
			window.dataLayer.push(push);
		} catch (e) {}
	}

	document.addEventListener('click', function (e) {
		var a = e.target.closest ? e.target.closest('a[href]') : null;
		if (!a) {
			return;
		}
		var href = a.getAttribute('href') || '';
		var loc = a.getAttribute('data-sbl-loc') || 'page';
		if (href.indexOf('https://wa.me/') === 0 || href.indexOf('https://api.whatsapp.com/') === 0) {
			track('whatsapp', { location: loc });
		} else if (href.indexOf('tel:') === 0) {
			track('phone', { location: loc });
		}
	});

	/* ---- Helpers ------------------------------------------------------------ */

	var BN = '০১২৩৪৫৬৭৮৯';

	function normalizePhone(raw) {
		var s = String(raw || '').replace(/[০-৯]/g, function (d) {
			return String(BN.indexOf(d));
		});
		var digits = s.replace(/\D+/g, '');
		if (digits.indexOf('8801') === 0) {
			digits = digits.slice(2);
		} else if (digits.length === 10 && digits.charAt(0) === '1') {
			digits = '0' + digits;
		}
		return /^01[3-9]\d{8}$/.test(digits) ? digits : '';
	}

	function scrollToEl(el, then) {
		if (!el) {
			return;
		}
		el.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
		if (then) {
			// Focus after the smooth scroll so the page does not jump.
			window.setTimeout(then, reduceMotion ? 0 : 450);
		}
	}

	/* ---- Quote form --------------------------------------------------------- */

	var form = document.querySelector('[data-sbl-form]');
	var quote = document.getElementById('quote');
	var success = document.querySelector('[data-sbl-success]');
	var items = form ? form.querySelector('#sbl-items') : null;
	var kitField = form ? form.querySelector('[data-sbl-kit-field]') : null;
	var submitBtn = form ? form.querySelector('[data-sbl-submit]') : null;
	var submitLabel = form ? form.querySelector('[data-sbl-submit-label]') : null;
	var attempted = false;

	function focusItems() {
		if (!items) {
			return;
		}
		items.focus({ preventScroll: true });
		var end = items.value.length;
		try {
			items.setSelectionRange(end, end);
		} catch (e) {}
	}

	// "Get a quote for this kit" → fill the textarea and jump to the form.
	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('[data-sbl-kit]') : null;
		if (btn && form && items) {
			e.preventDefault();
			var name = btn.getAttribute('data-sbl-kit');
			var list = btn.getAttribute('data-sbl-kit-items');
			items.value = name + (list ? ' (' + list + ')' : '') + '\nমোট জন: ';
			if (kitField) {
				kitField.value = name;
			}
			clearError('items');
			scrollToEl(quote, focusItems);
			return;
		}

		var go = e.target.closest ? e.target.closest('[data-sbl-goto]') : null;
		if (go && quote) {
			e.preventDefault();
			var prefill = go.getAttribute('data-sbl-prefill');
			if (prefill && items && items.value.trim() === '') {
				items.value = prefill;
			}
			scrollToEl(quote, prefill ? focusItems : null);
			if (window.history && window.history.replaceState) {
				window.history.replaceState(null, '', '#quote');
			}
		}
	});

	function fieldEl(key) {
		return form ? form.querySelector('[name="sbl_' + key + '"]') : null;
	}

	function errorEl(key) {
		return form ? form.querySelector('[data-sbl-error="' + key + '"]') : null;
	}

	function setError(key, text) {
		var el = errorEl(key);
		var input = fieldEl(key);
		if (el) {
			el.textContent = text;
			el.hidden = false;
		}
		if (input) {
			input.setAttribute('aria-invalid', 'true');
		}
	}

	function clearError(key) {
		var el = errorEl(key);
		var input = fieldEl(key);
		if (el) {
			el.textContent = '';
			el.hidden = true;
		}
		if (input) {
			input.removeAttribute('aria-invalid');
		}
	}

	function validate() {
		var errors = {};
		var v = function (key) {
			var el = fieldEl(key);
			return el ? String(el.value || '').trim() : '';
		};

		if (!v('company')) {
			errors.company = msg.company;
		}
		if (!v('name')) {
			errors.name = msg.name;
		}
		if (!normalizePhone(v('phone'))) {
			errors.phone = msg.phone;
		}
		if (!v('district')) {
			errors.district = msg.district;
		}
		if (!v('items')) {
			errors.items = msg.items;
		}

		var file = fieldEl('file');
		if (file && file.files && file.files[0]) {
			var f = file.files[0];
			var ext = (f.name.split('.').pop() || '').toLowerCase();
			if (f.size > (cfg.maxBytes || 5242880) || (cfg.exts || []).indexOf(ext) === -1) {
				errors.file = msg.file;
			}
		}
		return errors;
	}

	function showErrors(errors) {
		['company', 'name', 'phone', 'district', 'items', 'file', 'form'].forEach(function (key) {
			if (errors[key]) {
				setError(key, errors[key]);
			} else {
				clearError(key);
			}
		});
		var first = Object.keys(errors).filter(function (k) {
			return k !== 'form';
		})[0];
		var target = first ? fieldEl(first) : errorEl('form');
		if (target) {
			target.focus({ preventScroll: true });
			target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
		}
	}

	function setLoading(on) {
		if (!submitBtn) {
			return;
		}
		form.classList.toggle('is-loading', on);
		submitBtn.disabled = on;
		submitBtn.setAttribute('aria-busy', on ? 'true' : 'false');
		if (submitLabel) {
			if (!submitLabel.getAttribute('data-default')) {
				submitLabel.setAttribute('data-default', submitLabel.textContent);
			}
			submitLabel.textContent = on ? msg.sending || '…' : submitLabel.getAttribute('data-default');
		}
	}

	function showSuccess() {
		form.hidden = true;
		if (success) {
			success.hidden = false;
			success.focus({ preventScroll: true });
			success.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
		}
	}

	function freshNonce() {
		// The page may come from LiteSpeed / Cloudflare cache with an expired nonce.
		return fetch(cfg.ajaxUrl + '?action=safestore_bulk_lead_nonce', { credentials: 'same-origin', cache: 'no-store' })
			.then(function (r) {
				return r.ok ? r.json() : null;
			})
			.then(function (j) {
				return j && j.success && j.data ? j.data.nonce : '';
			})
			.catch(function () {
				return '';
			});
	}

	if (form && window.fetch && window.FormData && cfg.ajaxUrl) {
		var source = form.querySelector('[data-sbl-source]');
		if (source) {
			source.value = window.location.href.split('#')[0];
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			attempted = true;

			var errors = validate();
			if (Object.keys(errors).length) {
				showErrors(errors);
				return;
			}
			showErrors({});
			setLoading(true);

			freshNonce()
				.then(function (nonce) {
					var data = new FormData(form);
					if (nonce) {
						data.set('sbl_nonce', nonce);
					}
					return fetch(cfg.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' });
				})
				.then(function (r) {
					return r.json().catch(function () {
						return { success: false, data: { errors: { form: msg.server } } };
					});
				})
				.then(function (res) {
					setLoading(false);
					if (res && res.success) {
						var district = fieldEl('district');
						track('lead', {
							content_name: (kitField && kitField.value) || 'bulk_quote',
							district: district ? district.value : ''
						});
						showSuccess();
						return;
					}
					var errs = res && res.data && res.data.errors ? res.data.errors : { form: msg.server };
					showErrors(errs);
				})
				.catch(function () {
					setLoading(false);
					showErrors({ form: msg.server });
				});
		});

		// Live re-check once the visitor has tried to submit.
		form.addEventListener('change', function (e) {
			if (!attempted || !e.target.name) {
				return;
			}
			var key = e.target.name.replace(/^sbl_/, '');
			var errors = validate();
			if (errors[key]) {
				setError(key, errors[key]);
			} else {
				clearError(key);
			}
		});
	}

	// After a no-JS submit (?sbl=ok) count the lead too.
	if (success && !success.hidden) {
		track('lead', { content_name: 'bulk_quote' });
	}

	/* ---- FAQ accordion (panels are open without JS) ---------------------------- */

	var faq = document.querySelector('[data-sbl-faq]');
	if (faq) {
		var buttons = faq.querySelectorAll('.lp-faq__btn');
		Array.prototype.forEach.call(buttons, function (btn) {
			var panel = document.getElementById(btn.getAttribute('aria-controls'));
			if (!panel) {
				return;
			}
			btn.setAttribute('aria-expanded', 'false');
			panel.hidden = true;
			btn.addEventListener('click', function () {
				var open = btn.getAttribute('aria-expanded') === 'true';
				btn.setAttribute('aria-expanded', open ? 'false' : 'true');
				panel.hidden = open;
			});
		});
	}

	/* ---- Mobile sticky bar: hide while the form is on screen ------------------- */

	var sticky = document.querySelector('[data-sbl-sticky]');
	var formCard = document.querySelector('.lp-formcard');
	if (sticky && formCard && 'IntersectionObserver' in window) {
		var io = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					sticky.classList.toggle('is-hidden', entry.isIntersecting);
				});
			},
			{ rootMargin: '0px 0px -40px 0px' }
		);
		io.observe(formCard);
	}
})();

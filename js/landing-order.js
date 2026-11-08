/* Landing pages — shared order-on-page core (window.SafeStoreLP).
 *
 * Every landing template that takes the order on the page itself (product
 * landing, category landing) loads this first. The page script only says what
 * is picked ("cart") and how to show it; this file does the rest:
 *   - taka formatting (English digits, like checkout), bn() for Bangla copy
 *   - tracking (Meta Pixel, GA4, dataLayer) and WhatsApp / call clicks
 *   - address form: district → thana, live delivery charge (Steadfast courier
 *     rules, or WooCommerce's own zones while the courier is not live)
 *   - payment: Cash on Delivery or bKash / Nagad "Pay now" (TrxID optional)
 *   - validation, submit with fetch → WooCommerce JSON → thank-you page
 *   - "অর্ডার করুন" buttons elsewhere ([data-spl-goto]), mobile sticky bar, FAQ
 * Markup: templates/landing/order-form.php (shared data-spl-* hooks). Without
 * JS the form still posts and the server redirects. No jQuery. */
(function (w, d) {
	'use strict';

	var BN = '০১২৩৪৫৬৭৮৯';
	var reduceMotion = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var ZONES = { inside: 'Inside Dhaka City', suburb: 'Dhaka suburbs', outside: 'Outside Dhaka' };

	function bn(value) {
		return String(value).replace(/\d/g, function (c) { return BN.charAt(Number(c)); });
	}
	function taka(amount) {
		var n = Math.round(Number(amount) || 0);
		return '৳' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}
	function $(sel, root) { return (root || d).querySelector(sel); }
	function $$(sel, root) { return Array.prototype.slice.call((root || d).querySelectorAll(sel)); }
	function scrollTo(el) { el.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' }); }
	function setText(list, text) { list.forEach(function (el) { el.textContent = text; }); }

	function readJSON(id) {
		var el = d.getElementById(id);
		try {
			return el ? JSON.parse(el.textContent || '{}') : {};
		} catch (e) {
			return {};
		}
	}

	/* ---- Tracking: each sink guarded ---------------------------------------- */

	function track(fbName, gaName, params) {
		params = params || {};
		try {
			if (typeof w.fbq === 'function') {
				w.fbq('track', fbName, params);
			}
		} catch (e) {}
		try {
			if (typeof w.gtag === 'function') {
				w.gtag('event', gaName, params);
			}
		} catch (e) {}
		try {
			w.dataLayer = w.dataLayer || [];
			var push = { event: gaName };
			Object.keys(params).forEach(function (k) { push[k] = params[k]; });
			w.dataLayer.push(push);
		} catch (e) {}
	}

	function trackContacts(name) {
		d.addEventListener('click', function (e) {
			var a = e.target.closest ? e.target.closest('a[href]') : null;
			if (!a) {
				return;
			}
			var href = a.getAttribute('href') || '';
			var loc = a.getAttribute('data-spl-loc') || 'page';
			if (href.indexOf('https://wa.me/') === 0 || href.indexOf('https://api.whatsapp.com/') === 0) {
				track('Contact', 'click_whatsapp', { location: loc, content_name: name || '' });
			} else if (href.indexOf('tel:') === 0) {
				track('Contact', 'click_call', { location: loc });
			}
		});
	}

	/* ---- Order form ---------------------------------------------------------
	 * opts.form     the <form data-spl-form>
	 * opts.cfg      { thanas, ship: { rates, lanes, sub }, flat: { BD-xx: [first, extra] }, pay }
	 * opts.cart()   → { sub: taka, qty: units, kg: parcel weight } for what is picked now
	 * opts.gap()    → null, or a function that shows what must be picked first
	 * opts.params(extra) → tracking params (begin_checkout, add_to_cart, add_payment_info)
	 * opts.render(cart, delivery) → page-specific output after every refresh
	 * Returns { refresh } for the page to call after its own inputs change. */

	function order(opts) {
		var form = opts.form;
		if (!form) {
			return { refresh: function () {} };
		}
		var cfg = opts.cfg || {};
		var fields = $$('[data-spl-field]', form);
		var district = $('[name="billing_state"]', form);
		var thana = $('[name="billing_city"]', form);
		var alertBox = $('[data-spl-alert]', form);
		var submitBtn = $('[data-spl-submit]', form);
		var submitLabel = $('[data-spl-submit-label]', form);
		var payOpts = $$('[data-spl-pay-opt]', form);
		var mfsPanel = $('[data-spl-mfs]', form);
		var mfsSender = $('[name="sft_mfs_sender"]', form);
		var mfsTrx = $('[name="sft_mfs_trxid"]', form);
		var out = { ship: $$('[data-spl-ship]'), zone: $$('[data-spl-zone]'), total: $$('[data-spl-total]') };
		var gap = opts.gap || function () { return null; };
		var params = opts.params || function (extra) { return extra || {}; };
		var busy = false;

		function payMethod() {
			var p = payOpts.filter(function (i) { return i.checked; })[0];
			return p ? p.value : 'cod';
		}

		/* Delivery charge: the courier's own rules (inc/shipping-courier.php), or — while the
		   courier is not live — what WooCommerce's zones charge (cfg.flat). The order recomputes it. */
		function delivery(c) {
			if (!district || !district.value) {
				return null;
			}
			var f = cfg.flat;
			if (!cfg.ship && f) {
				var r = f[district.value];
				return r ? { cost: Number(r[0]) + Math.max(0, c.qty - 1) * Number(r[1]), zone: district.value === 'BD-13' ? 'Dhaka' : 'Outside Dhaka' } : null;
			}
			var s = cfg.ship;
			if (!s) {
				return null;
			}
			var lane = s.lanes[district.value] || 'outside';
			if (lane === 'inside' && thana && s.sub.indexOf(thana.value) !== -1) {
				lane = 'suburb';
			}
			if (!s.rates[lane]) {
				lane = 'outside';
			}
			var kg = Math.max(1, Math.ceil(Math.round(Number(c.kg) * 100) / 100));
			return { cost: Number(s.rates[lane].first) + (kg - 1) * Number(s.rates[lane].extra), zone: ZONES[lane] };
		}

		function refresh() {
			var c = opts.cart();
			var dl = c.qty > 0 ? delivery(c) : null;
			var mfs = payMethod() === 'mfs';
			setText(out.ship, dl ? (dl.cost > 0 ? taka(dl.cost) : 'Free') : ((cfg.ship || cfg.flat) ? 'Select district' : 'Added at checkout'));
			setText(out.zone, dl ? '(' + dl.zone + ')' : '');
			setText(out.total, taka(c.sub + (dl ? dl.cost : 0)));
			if (submitLabel && !busy) {
				submitLabel.textContent = (mfs ? 'Place order' : 'Confirm order') + (dl ? ' — ' + taka(c.sub + dl.cost) : '');
			}
			if (mfsPanel) {
				mfsPanel.hidden = !mfs;
			}
			$$('[data-spl-note]', form).forEach(function (n) { n.hidden = n.getAttribute('data-spl-note') !== (mfs ? 'mfs' : 'cod'); });
			payOpts.forEach(function (i) {
				var card = i.closest('.spl-pay__opt');
				if (card) {
					card.classList.toggle('is-checked', i.checked);
				}
			});
			if (opts.render) {
				opts.render(c, dl);
			}
		}

		/* Thanas follow the district (values stay the English names the courier uses). */
		function fillThanas() {
			if (!district || !thana) {
				return;
			}
			var list = (cfg.thanas || {})[district.value] || [];
			var keep = thana.value;
			thana.innerHTML = '';
			thana.add(new Option(list.length ? 'Select thana / upazila' : 'Select district first', ''));
			list.forEach(function (t) { thana.add(new Option(t, t, false, t === keep)); });
			thana.disabled = !list.length;
		}

		function digits(v) {
			var s = String(v).replace(/[০-৯]/g, function (c) { return String(BN.indexOf(c)); }).replace(/\D/g, '');
			return s.indexOf('8801') === 0 ? s.slice(2) : s;
		}

		function setError(el, msg) {
			var box = d.getElementById(el.id + '-err');
			el.setAttribute('aria-invalid', msg ? 'true' : 'false');
			if (box) {
				box.textContent = msg || '';
				box.hidden = !msg;
			}
		}

		function check(el) {
			var v = el.value.trim();
			var ok = v !== '' && !(el.name === 'billing_phone' && !/^01[3-9]\d{8}$/.test(digits(v)));
			setError(el, ok ? '' : el.getAttribute('data-err'));
			return ok;
		}

		/* Pay now: the TrxID is optional (pay within 24 h), but once given it must look right and come with the sender number. */
		function checkMfs() {
			if (payMethod() !== 'mfs' || !mfsTrx) {
				return [];
			}
			var bad = [];
			var trx = mfsTrx.value.replace(/[^A-Za-z0-9]/g, '');
			var trxOk = trx === '' || /^[A-Za-z0-9]{8,12}$/.test(trx);
			setError(mfsTrx, trxOk ? '' : mfsTrx.getAttribute('data-err'));
			if (!trxOk) {
				bad.push(mfsTrx);
			}
			if (mfsSender) {
				var senderOk = trx === '' || /^01[3-9]\d{8}$/.test(digits(mfsSender.value));
				setError(mfsSender, senderOk ? '' : mfsSender.getAttribute('data-err'));
				if (!senderOk) {
					bad.push(mfsSender);
				}
			}
			return bad;
		}

		function showAlert(msg) {
			if (!alertBox) {
				return;
			}
			alertBox.textContent = msg || '';
			alertBox.hidden = !msg;
		}

		/* First thing still missing: the page's own pick, then a field; null when complete. */
		function firstGap() {
			return gap() || fields.filter(function (el) { return el.value.trim() === ''; })[0] || null;
		}
		function showGap(g) {
			if (typeof g === 'function') {
				g();
			} else {
				scrollTo(g);
				g.focus({ preventScroll: true });
			}
		}

		function setBusy(on) {
			busy = on;
			form.classList.toggle('is-loading', on);
			[submitBtn, $('[data-spl-sticky-cta]')].forEach(function (b) {
				if (b) {
					b.disabled = on;
				}
			});
			if (on && submitLabel) {
				submitLabel.textContent = 'Placing order…';
			}
			if (!on) {
				refresh();
			}
		}

		/* WooCommerce's error list → the fields it names (data-id), the rest in the alert. */
		function serverErrors(html) {
			var box = d.createElement('div');
			box.innerHTML = String(html || '');
			var rest = [];
			var items = $$('li', box);
			(items.length ? items : [box]).forEach(function (li) {
				var el = li.getAttribute && li.getAttribute('data-id') ? form.querySelector('[name="' + li.getAttribute('data-id') + '"]') : null;
				if (el && el.hasAttribute('data-err')) {
					setError(el, el.getAttribute('data-err'));
				} else if (li.textContent.trim()) {
					rest.push(li.textContent.trim());
				}
			});
			showAlert(rest.length ? rest.join(' ') : 'Please fix the highlighted details and try again.');
			var bad = $('[aria-invalid="true"]', form);
			if (bad) {
				bad.focus();
			} else if (alertBox) {
				scrollTo(alertBox);
			}
		}

		form.noValidate = true; // our inline messages instead of the browser's bubbles
		var token = $('[data-spl-token]', form);
		if (token) {
			token.value = (w.crypto && w.crypto.randomUUID) ? w.crypto.randomUUID().replace(/-/g, '') : String(Date.now()) + Math.random().toString(36).slice(2);
		}

		if (district) {
			district.addEventListener('change', function () { fillThanas(); refresh(); });
			if (district.value && thana && thana.options.length < 2) {
				fillThanas();
			} else if (!district.value && thana) {
				thana.disabled = true;
			}
		}
		if (thana) {
			thana.addEventListener('change', refresh);
		}

		var payTracked = false;
		payOpts.forEach(function (input) {
			input.addEventListener('change', function () {
				refresh();
				if (input.value === 'mfs' && input.checked && !payTracked) {
					payTracked = true;
					track('AddPaymentInfo', 'add_payment_info', params({ payment_type: 'bkash_nagad' }));
				}
			});
		});
		[mfsTrx, mfsSender].forEach(function (el) {
			if (el) {
				el.addEventListener('input', function () { if (el.getAttribute('aria-invalid') === 'true') { checkMfs(); } });
			}
		});
		$$('[data-spl-copy]', form).forEach(function (btn) {
			btn.addEventListener('click', function () {
				var value = btn.getAttribute('data-spl-copy') || '';
				var done = function () {
					btn.textContent = 'Copied ✓';
					setTimeout(function () { btn.textContent = 'Copy'; }, 1800);
				};
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(value).then(done, function () {});
				}
			});
		});

		fields.forEach(function (el) {
			// Check a field once the visitor leaves it; after an error, re-check while typing.
			el.addEventListener('blur', function () { if (el.value.trim() !== '') { check(el); } });
			el.addEventListener('input', function () { if (el.getAttribute('aria-invalid') === 'true') { check(el); } });
		});

		var started = false;
		form.addEventListener('focusin', function (e) {
			if (!started && e.target.hasAttribute && e.target.hasAttribute('data-spl-field')) {
				started = true;
				track('InitiateCheckout', 'begin_checkout', params());
			}
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (busy) {
				return;
			}
			showAlert('');
			var g = gap();
			if (g) {
				showGap(g);
				return;
			}
			var bad = fields.filter(function (el) { return !check(el); }).concat(checkMfs());
			if (bad.length) {
				showAlert(bad.length === 1 ? 'Please fix 1 detail, then try again.' : 'Please fix ' + bad.length + ' details, then try again.');
				bad[0].focus();
				return;
			}
			track('AddToCart', 'add_to_cart', params());
			setBusy(true);
			var data = new FormData(form); // includes inputs outside the <form> that use form="…"
			data.append('spl_ajax', '1');
			fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json' } })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (res && res.result === 'success' && res.redirect) {
						if (submitLabel) {
							submitLabel.textContent = 'Order placed ✓';
						}
						w.location.href = res.redirect;
						return;
					}
					if (res && res.reload) {
						w.location.reload();
						return;
					}
					setBusy(false);
					serverErrors(res && res.messages);
				})
				.catch(function () {
					setBusy(false);
					showAlert('Sorry, the order could not be sent. Check your internet connection and try again, or order on WhatsApp.');
					if (alertBox) {
						scrollTo(alertBox);
					}
				});
		});

		// Back from the thank-you page (bfcache): usable again.
		w.addEventListener('pageshow', function (e) {
			if (e.persisted) {
				setBusy(false);
			}
		});

		// "অর্ডার করুন" buttons elsewhere (sticky bar, final CTA): next missing step, or order.
		$$('[data-spl-goto]').forEach(function (el) {
			el.addEventListener('click', function (e) {
				e.preventDefault();
				var next = firstGap();
				if (next) {
					showGap(next);
				} else if (form.requestSubmit) {
					form.requestSubmit();
				} else if (submitBtn) {
					submitBtn.click();
				}
			});
		});

		refresh();
		return { refresh: refresh };
	}

	/* ---- Mobile sticky bar: hidden while the form (or footer) is on screen ---- */

	function sticky(watchEl) {
		var bar = $('[data-spl-sticky]');
		var watch = [watchEl, $('.lp-footer')].filter(Boolean);
		if (!bar || !watch.length || !('IntersectionObserver' in w)) {
			return;
		}
		var onScreen = [];
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				var i = onScreen.indexOf(en.target);
				if (en.isIntersecting && i === -1) {
					onScreen.push(en.target);
				} else if (!en.isIntersecting && i !== -1) {
					onScreen.splice(i, 1);
				}
			});
			bar.classList.toggle('is-hidden', onScreen.length > 0);
		});
		watch.forEach(function (el) { io.observe(el); });
	}

	/* ---- FAQ accordion (all open without JS; first open with JS) ------------- */

	function faq() {
		var box = $('[data-spl-faq]');
		if (!box) {
			return;
		}
		$$('.lp-faq__btn', box).forEach(function (btn, i) {
			var panel = d.getElementById(btn.getAttribute('aria-controls'));
			var open = i === 0;
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (panel) {
				panel.hidden = !open;
			}
			btn.addEventListener('click', function () {
				var expanded = btn.getAttribute('aria-expanded') === 'true';
				btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
				if (panel) {
					panel.hidden = expanded;
				}
			});
		});
	}

	w.SafeStoreLP = {
		bn: bn,
		taka: taka,
		$: $,
		$$: $$,
		scrollTo: scrollTo,
		readJSON: readJSON,
		track: track,
		trackContacts: trackContacts,
		order: order,
		sticky: sticky,
		faq: faq
	};
})(window, document);

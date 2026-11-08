/* Product landing (Facebook campaign) — gallery, size pills, qty, address form
 * with live delivery charge, order on this page (fetch → WooCommerce checkout →
 * thank-you page), sticky bar, FAQ accordion, tracking. No jQuery. Without JS
 * the form still posts and the server redirects (inc/product-landing-order.php). */
(function () {
	'use strict';

	var dataEl = document.getElementById('spl-data');
	var cfg = {};
	try {
		cfg = dataEl ? JSON.parse(dataEl.textContent || '{}') : {};
	} catch (e) {
		cfg = {};
	}
	var BN = '০১২৩৪৫৬৭৮৯';
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function bn(value) {
		return String(value).replace(/\d/g, function (d) {
			return BN.charAt(Number(d));
		});
	}
	function taka(amount) {
		var n = Math.round(Number(amount) || 0);
		return '৳' + bn(n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ','));
	}

	/* ---- Tracking: Meta Pixel, GA4, dataLayer — each guarded ---------------- */

	function track(fbName, gaName, params) {
		params = params || {};
		try {
			if (typeof window.fbq === 'function') {
				window.fbq('track', fbName, params);
			}
		} catch (e) {}
		try {
			if (typeof window.gtag === 'function') {
				window.gtag('event', gaName, params);
			}
		} catch (e) {}
		try {
			window.dataLayer = window.dataLayer || [];
			var push = { event: gaName };
			Object.keys(params).forEach(function (k) {
				push[k] = params[k];
			});
			window.dataLayer.push(push);
		} catch (e) {}
	}

	function productParams(extra) {
		var p = {
			content_ids: [cfg.sku || String(cfg.id || '')],
			content_name: cfg.name || '',
			content_type: 'product',
			currency: cfg.currency || 'BDT',
			value: Number(cfg.price) || 0
		};
		Object.keys(extra || {}).forEach(function (k) {
			p[k] = extra[k];
		});
		return p;
	}

	if (cfg.id) {
		track('ViewContent', 'view_item', productParams());
	}

	document.addEventListener('click', function (e) {
		var a = e.target.closest ? e.target.closest('a[href]') : null;
		if (!a) {
			return;
		}
		var href = a.getAttribute('href') || '';
		var loc = a.getAttribute('data-spl-loc') || 'page';
		if (href.indexOf('https://wa.me/') === 0 || href.indexOf('https://api.whatsapp.com/') === 0) {
			track('Contact', 'click_whatsapp', { location: loc, content_name: cfg.name || '' });
		} else if (href.indexOf('tel:') === 0) {
			track('Contact', 'click_call', { location: loc });
		}
	});

	/* ---- Gallery ------------------------------------------------------------ */

	var main = document.querySelector('[data-spl-main]');
	var thumbs = Array.prototype.slice.call(document.querySelectorAll('[data-spl-thumb]'));
	thumbs.forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (!main) {
				return;
			}
			var srcset = btn.getAttribute('data-srcset') || '';
			main.setAttribute('src', btn.getAttribute('data-src') || '');
			if (srcset) {
				main.setAttribute('srcset', srcset);
			} else {
				main.removeAttribute('srcset');
			}
			thumbs.forEach(function (b) {
				b.classList.toggle('is-active', b === btn);
				if (b === btn) {
					b.setAttribute('aria-current', 'true');
				} else {
					b.removeAttribute('aria-current');
				}
			});
		});
	});

	/* ---- Order form: size, qty, address, live total, submit on this page ---- */

	var $ = function (sel, root) { return (root || document).querySelector(sel); };
	var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
	var scrollTo = function (el) { el.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' }); };

	var form = $('[data-spl-form]');
	var sizes = $$('[data-spl-size]');
	var sizesBox = $('[data-spl-sizes]');
	var hint = $('[data-spl-size-hint]');
	var hintDefault = hint ? hint.textContent : '';
	var qtyInput = $('[data-spl-qty-input]');
	var fields = $$('[data-spl-field]');
	var district = $('[name="billing_state"]');
	var thana = $('[name="billing_city"]');
	var alertBox = $('[data-spl-alert]');
	var submitBtn = $('[data-spl-submit]');
	var submitLabel = $('[data-spl-submit-label]');
	var out = {};
	['price', 'line', 'sub', 'ship', 'zone', 'total'].forEach(function (k) { out[k] = $$('[data-spl-' + k + ']'); });
	var waLinks = $$('[data-spl-wa]');
	var options = cfg.options || {};
	var ZONES = { inside: 'ঢাকা সিটি', suburb: 'ঢাকার আশপাশ', outside: 'ঢাকার বাইরে' };
	var busy = false;

	function setText(list, text) { list.forEach(function (el) { el.textContent = text; }); }
	function chosen() { return sizes.filter(function (i) { return i.checked; })[0] || null; }
	function opt() { var p = chosen(); return p && options[p.value] ? options[p.value] : null; }
	function unitPrice() { var o = opt(); return o && o.price > 0 ? Number(o.price) : Number(cfg.price) || 0; }

	function clampQty() {
		if (!qtyInput) {
			return 1;
		}
		var o = opt();
		var max = o && o.max > 0 ? o.max : Number(qtyInput.max) || 24;
		var v = Math.min(Math.max(parseInt(qtyInput.value, 10) || 1, 1), max);
		qtyInput.value = String(v);
		qtyInput.max = String(max);
		return v;
	}

	/* Delivery charge with the courier's own rules (inc/shipping-courier.php); the order recomputes it. */
	function delivery(qty) {
		var s = cfg.ship;
		if (!s || !district || !district.value) {
			return null;
		}
		var lane = s.lanes[district.value] || 'outside';
		if (lane === 'inside' && thana && s.sub.indexOf(thana.value) !== -1) {
			lane = 'suburb';
		}
		if (!s.rates[lane]) {
			lane = 'outside';
		}
		var kg = Math.max(1, Math.ceil(Math.round(Number(s.kg) * qty * 100) / 100));
		return { cost: Number(s.rates[lane].first) + (kg - 1) * Number(s.rates[lane].extra), zone: ZONES[lane] };
	}

	function refresh() {
		var o = opt();
		var qty = clampQty();
		var unit = unitPrice();
		var sub = unit * qty;
		var d = delivery(qty);
		sizes.forEach(function (input) {
			var cell = input.closest('.spl-size');
			if (cell) {
				cell.classList.toggle('is-checked', input.checked);
			}
		});
		if (sizesBox && o) {
			sizesBox.classList.remove('is-error');
			if (hint) {
				hint.textContent = hintDefault;
			}
		}
		// Hero: unit price of the size; sticky bar: line total.
		out.price.forEach(function (el) { el.textContent = taka(el.getAttribute('data-spl-price') === 'total' ? sub : unit); });
		setText(out.line, (o ? '· সাইজ ' + bn(o.label) + ' ' : '') + '× ' + bn(qty));
		setText(out.sub, taka(sub));
		setText(out.ship, d ? taka(d.cost) : (cfg.ship ? 'জেলা বেছে নিন' : 'ফোনে জানানো হবে'));
		setText(out.zone, d ? '(' + d.zone + ')' : '');
		setText(out.total, taka(sub + (d ? d.cost : 0)));
		if (submitLabel && !busy) {
			submitLabel.textContent = 'অর্ডার কনফার্ম করুন' + (d ? ' — ' + taka(sub + d.cost) : '');
		}
		if (cfg.waBase && cfg.waText) {
			var href = cfg.waBase + '?text=' + encodeURIComponent(cfg.waText.replace('{size}', o ? o.label : '').replace(/পরিমাণ: [০-৯\d]+/, 'পরিমাণ: ' + bn(qty)));
			waLinks.forEach(function (a) { a.setAttribute('href', href); });
		}
	}

	function sizeError() {
		if (!sizesBox) {
			return;
		}
		sizesBox.classList.add('is-error');
		if (hint) {
			hint.textContent = 'অনুগ্রহ করে আগে সাইজ নির্বাচন করুন।';
		}
		scrollTo(sizesBox);
		var first = sizes.filter(function (i) { return !i.disabled; })[0];
		if (first) {
			first.focus({ preventScroll: true });
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
		thana.add(new Option(list.length ? 'থানা বেছে নিন' : 'আগে জেলা বেছে নিন', ''));
		list.forEach(function (t) { thana.add(new Option(t, t, false, t === keep)); });
		thana.disabled = !list.length;
	}

	function digits(v) {
		var d = String(v).replace(/[০-৯]/g, function (c) { return String(BN.indexOf(c)); }).replace(/\D/g, '');
		return d.indexOf('8801') === 0 ? d.slice(2) : d;
	}

	function setError(el, msg) {
		var box = document.getElementById(el.id + '-err');
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

	function showAlert(msg) {
		if (!alertBox) {
			return;
		}
		alertBox.textContent = msg || '';
		alertBox.hidden = !msg;
	}

	/* First thing still missing: a size, then a field; null when the form is complete. */
	function firstGap() {
		if (sizes.length && !chosen()) {
			return 'size';
		}
		return fields.filter(function (el) { return el.value.trim() === ''; })[0] || null;
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
			submitLabel.textContent = 'অর্ডার হচ্ছে…';
		}
		if (!on) {
			refresh();
		}
	}

	/* WooCommerce's error list → the fields it names (data-id), the rest in the alert. */
	function serverErrors(html) {
		var box = document.createElement('div');
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
		showAlert(rest.length ? rest.join(' ') : 'কিছু তথ্য ঠিক করুন, তারপর আবার চাপুন।');
		var bad = $('[aria-invalid="true"]', form);
		if (bad) {
			bad.focus();
		} else if (alertBox) {
			scrollTo(alertBox);
		}
	}

	if (form) {
		form.noValidate = true; // Bangla messages below instead of the browser's English bubbles
		var token = $('[data-spl-token]');
		if (token) {
			token.value = (window.crypto && window.crypto.randomUUID) ? window.crypto.randomUUID().replace(/-/g, '') : String(Date.now()) + Math.random().toString(36).slice(2);
		}

		sizes.forEach(function (input) { input.addEventListener('change', refresh); });
		$$('[data-spl-qty]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				qtyInput.value = String((parseInt(qtyInput.value, 10) || 1) + Number(btn.getAttribute('data-spl-qty')));
				refresh();
			});
		});
		if (qtyInput) {
			qtyInput.addEventListener('change', refresh);
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
		fields.forEach(function (el) {
			// Check a field once the visitor leaves it; after an error, re-check while typing.
			el.addEventListener('blur', function () { if (el.value.trim() !== '') { check(el); } });
			el.addEventListener('input', function () { if (el.getAttribute('aria-invalid') === 'true') { check(el); } });
		});

		var started = false;
		form.addEventListener('focusin', function (e) {
			if (!started && e.target.hasAttribute && e.target.hasAttribute('data-spl-field')) {
				started = true;
				track('InitiateCheckout', 'begin_checkout', productParams({ value: unitPrice() * clampQty(), num_items: clampQty() }));
			}
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (busy) {
				return;
			}
			showAlert('');
			if (sizes.length && !chosen()) {
				sizeError();
				return;
			}
			var bad = fields.filter(function (el) { return !check(el); });
			if (bad.length) {
				showAlert(bn(bad.length) + 'টি তথ্য ঠিক করুন, তারপর আবার চাপুন।');
				bad[0].focus();
				return;
			}
			var o = opt();
			track('AddToCart', 'add_to_cart', productParams({ value: unitPrice() * clampQty(), num_items: clampQty(), variant: o ? o.label : '' }));
			setBusy(true);
			var data = new FormData(form);
			data.append('spl_ajax', '1');
			fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json' } })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (res && res.result === 'success' && res.redirect) {
						if (submitLabel) {
							submitLabel.textContent = 'অর্ডার হয়েছে ✓';
						}
						window.location.href = res.redirect;
						return;
					}
					if (res && res.reload) {
						window.location.reload();
						return;
					}
					setBusy(false);
					serverErrors(res && res.messages);
				})
				.catch(function () {
					setBusy(false);
					showAlert('দুঃখিত, অর্ডারটি পাঠানো যায়নি। ইন্টারনেট সংযোগ দেখে আবার চাপুন, অথবা WhatsApp-এ অর্ডার করুন।');
					if (alertBox) {
						scrollTo(alertBox);
					}
				});
		});

		// Back from the thank-you page (bfcache): usable again.
		window.addEventListener('pageshow', function (e) {
			if (e.persisted) {
				setBusy(false);
			}
		});
		refresh();
	}

	/* ---- "অর্ডার করুন" buttons elsewhere (sticky bar, final CTA): next empty step, or order ---- */

	$$('[data-spl-goto]').forEach(function (el) {
		el.addEventListener('click', function (e) {
			if (!form) {
				return;
			}
			e.preventDefault();
			var gap = firstGap();
			if (gap === 'size') {
				sizeError();
			} else if (gap) {
				scrollTo(gap);
				gap.focus({ preventScroll: true });
			} else if (form.requestSubmit) {
				form.requestSubmit();
			} else {
				submitBtn.click();
			}
		});
	});

	/* ---- Sticky bar: hidden while the order form is on screen (keyboard, no overlap) ---- */

	var sticky = $('[data-spl-sticky]');
	var watch = [form || $('[data-spl-pdp]'), $('.lp-footer')].filter(Boolean);
	if (sticky && watch.length && 'IntersectionObserver' in window) {
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
			sticky.classList.toggle('is-hidden', onScreen.length > 0);
		});
		watch.forEach(function (el) { io.observe(el); });
	}

	/* ---- FAQ accordion (all open without JS; first open with JS) ------------- */

	var faq = document.querySelector('[data-spl-faq]');
	if (faq) {
		var buttons = Array.prototype.slice.call(faq.querySelectorAll('.lp-faq__btn'));
		buttons.forEach(function (btn, i) {
			var panel = document.getElementById(btn.getAttribute('aria-controls'));
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
})();

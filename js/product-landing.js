/* Product landing (Facebook campaign) — the page's own parts: gallery, size
 * pills, quantity, its tracking params and WhatsApp text. Address form,
 * delivery charge, payment, submit, sticky bar and FAQ come from the shared
 * order core (js/landing-order.js → window.SafeStoreLP). */
(function () {
	'use strict';

	var L = window.SafeStoreLP;
	if (!L) {
		return;
	}
	var $ = L.$;
	var $$ = L.$$;
	var taka = L.taka;
	var cfg = L.readJSON('spl-data');

	function productParams(extra) {
		var p = {
			content_ids: [cfg.sku || String(cfg.id || '')],
			content_name: cfg.name || '',
			content_type: 'product',
			currency: cfg.currency || 'BDT',
			value: Number(cfg.price) || 0
		};
		Object.keys(extra || {}).forEach(function (k) { p[k] = extra[k]; });
		return p;
	}

	if (cfg.id) {
		L.track('ViewContent', 'view_item', productParams());
	}
	L.trackContacts(cfg.name);

	/* ---- Gallery ------------------------------------------------------------ */

	var main = $('[data-spl-main]');
	var thumbs = $$('[data-spl-thumb]');
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

	/* ---- Size + quantity ---------------------------------------------------- */

	var form = $('[data-spl-form]');
	var sizes = $$('[data-spl-size]');
	var sizesBox = $('[data-spl-sizes]');
	var hint = $('[data-spl-size-hint]');
	var hintDefault = hint ? hint.textContent : '';
	var qtyInput = $('[data-spl-qty-input]');
	var out = { price: $$('[data-spl-price]'), line: $$('[data-spl-line]'), sub: $$('[data-spl-sub]') };
	var waLinks = $$('[data-spl-wa]');
	var options = cfg.options || {};

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

	function sizeError() {
		if (!sizesBox) {
			return;
		}
		sizesBox.classList.add('is-error');
		if (hint) {
			hint.textContent = 'Please select a size first.';
		}
		L.scrollTo(sizesBox);
		var first = sizes.filter(function (i) { return !i.disabled; })[0];
		if (first) {
			first.focus({ preventScroll: true });
		}
	}

	var api = L.order({
		form: form,
		cfg: cfg,
		cart: function () {
			var qty = clampQty();
			return { sub: unitPrice() * qty, qty: qty, kg: (cfg.ship ? Number(cfg.ship.kg) || 0 : 0) * qty };
		},
		gap: function () { return sizes.length && !chosen() ? sizeError : null; },
		params: function (extra) {
			var o = opt();
			var p = { value: unitPrice() * clampQty(), num_items: clampQty(), variant: o ? o.label : '' };
			Object.keys(extra || {}).forEach(function (k) { p[k] = extra[k]; });
			return productParams(p);
		},
		render: function (c) {
			var o = opt();
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
			out.price.forEach(function (el) { el.textContent = taka(el.getAttribute('data-spl-price') === 'total' ? c.sub : unitPrice()); });
			out.line.forEach(function (el) { el.textContent = (o ? '· Size ' + o.label + ' ' : '') + '× ' + c.qty; });
			out.sub.forEach(function (el) { el.textContent = taka(c.sub); });
			if (cfg.waBase && cfg.waText) {
				var href = cfg.waBase + '?text=' + encodeURIComponent(cfg.waText.replace('{size}', o ? o.label : '').replace(/পরিমাণ: [০-৯\d]+/, 'পরিমাণ: ' + L.bn(c.qty)));
				waLinks.forEach(function (a) { a.setAttribute('href', href); });
			}
		}
	});

	sizes.forEach(function (input) { input.addEventListener('change', api.refresh); });
	$$('[data-spl-qty]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			qtyInput.value = String((parseInt(qtyInput.value, 10) || 1) + Number(btn.getAttribute('data-spl-qty')));
			api.refresh();
		});
	});
	if (qtyInput) {
		qtyInput.addEventListener('change', api.refresh);
	}

	L.sticky(form || $('[data-spl-pdp]'));
	L.faq();
})();

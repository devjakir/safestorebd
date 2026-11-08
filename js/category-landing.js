/* Category landing (Facebook campaign) — the page's own parts: "Add" +
 * quantity on every product card, the picked lines in the order summary,
 * guide links that jump to a card, the sticky-bar label, the bulk hint, the
 * WhatsApp text and tracking. Address form, delivery charge, payment, submit,
 * sticky bar and FAQ come from the shared order core (js/landing-order.js).
 * Without JS the quantity inputs (form="spl-order") still post with the form. */
(function () {
	'use strict';

	var L = window.SafeStoreLP;
	if (!L) {
		return;
	}
	var $ = L.$;
	var $$ = L.$$;
	var taka = L.taka;
	var cfg = L.readJSON('scl-data');
	var items = cfg.items || {};

	var form = $('[data-spl-form]');
	var list = $('[data-scl-list]');
	var listErr = $('[data-scl-list-err]');
	var cards = $$('[data-scl-item]');
	var linesBox = $('[data-spl-lines]');
	var emptyHTML = linesBox ? linesBox.innerHTML : '';
	var countEl = $('[data-scl-count]');
	var bulkEl = $('[data-scl-bulk]');
	var stickyLabel = $('[data-scl-sticky-label]');
	var stickyTotal = $('[data-scl-sticky-total]');
	var waLinks = $$('[data-spl-wa]');

	document.documentElement.classList.add('scl-js'); // "Add" buttons instead of bare 0-steppers

	function inputOf(card) { return $('[data-scl-qty]', card); }

	function qtyOf(card) {
		var input = inputOf(card);
		if (!input) {
			return 0;
		}
		var max = Number(input.max) || 99;
		var v = Math.min(Math.max(parseInt(input.value, 10) || 0, 0), max);
		input.value = String(v);
		return v;
	}

	/* Picked lines: [{ id, item, qty }] in card order. */
	function picked() {
		var out = [];
		cards.forEach(function (card) {
			var id = card.getAttribute('data-scl-item');
			var q = qtyOf(card);
			if (q > 0 && items[id]) {
				out.push({ id: id, item: items[id], qty: q });
			}
		});
		return out;
	}

	function trackParams(lines, extra) {
		var p = {
			content_ids: lines.map(function (l) { return l.item.sku || String(l.id); }),
			contents: lines.map(function (l) { return { id: l.item.sku || String(l.id), quantity: l.qty }; }),
			content_type: 'product',
			content_category: cfg.category || '',
			currency: cfg.currency || 'BDT',
			value: lines.reduce(function (s, l) { return s + l.item.price * l.qty; }, 0),
			num_items: lines.reduce(function (s, l) { return s + l.qty; }, 0)
		};
		Object.keys(extra || {}).forEach(function (k) { p[k] = extra[k]; });
		return p;
	}

	L.track('ViewContent', 'view_item_list', trackParams(Object.keys(items).map(function (id) { return { id: id, item: items[id], qty: 1 }; }), { value: 0 }));
	L.trackContacts(cfg.category);

	function listError() {
		if (listErr) {
			listErr.hidden = false;
		}
		if (list) {
			L.scrollTo(list);
		}
		var add = $('[data-scl-add]:not([hidden])') || $('[data-scl-qty]');
		if (add) {
			add.focus({ preventScroll: true });
		}
	}

	function line(text, note, amount) {
		var p = document.createElement('p');
		var span = document.createElement('span');
		var small = document.createElement('small');
		var b = document.createElement('b');
		span.textContent = text + ' ';
		small.textContent = note;
		span.appendChild(small);
		b.textContent = amount;
		p.appendChild(span);
		p.appendChild(b);
		return p;
	}

	var api = L.order({
		form: form,
		cfg: cfg,
		cart: function () {
			var lines = picked();
			return {
				lines: lines,
				sub: lines.reduce(function (s, l) { return s + l.item.price * l.qty; }, 0),
				qty: lines.reduce(function (s, l) { return s + l.qty; }, 0),
				kg: lines.reduce(function (s, l) { return s + (Number(l.item.kg) || 0) * l.qty; }, 0)
			};
		},
		gap: function () { return picked().length ? null : listError; },
		params: function (extra) { return trackParams(picked(), extra); },
		render: function (c, dl) {
			cards.forEach(function (card) {
				var q = qtyOf(card);
				var add = $('[data-scl-add]', card);
				card.classList.toggle('is-picked', q > 0);
				if (add) {
					add.hidden = q > 0;
				}
			});
			if (c.qty > 0 && listErr) {
				listErr.hidden = true;
			}
			if (linesBox) {
				if (c.lines.length) {
					linesBox.textContent = '';
					c.lines.forEach(function (l) { linesBox.appendChild(line(l.item.title, '× ' + l.qty, taka(l.item.price * l.qty))); });
				} else {
					linesBox.innerHTML = emptyHTML;
				}
			}
			if (countEl) {
				countEl.textContent = c.qty ? '· ' + c.qty + (c.qty > 1 ? ' pcs' : ' pc') : '';
			}
			if (bulkEl) {
				bulkEl.hidden = c.qty < (Number(cfg.bulk) || 10);
			}
			if (stickyLabel) {
				stickyLabel.textContent = c.qty ? 'অর্ডার করুন · ' + c.qty + ' পিস' : 'ভেস্ট বেছে নিন';
			}
			if (stickyTotal) {
				stickyTotal.hidden = !c.qty;
				stickyTotal.textContent = c.qty ? taka(c.sub + (dl ? dl.cost : 0)) : '';
			}
			if (cfg.waBase && cfg.waText) {
				var txt = c.lines.map(function (l) { return l.item.name + ' × ' + l.qty; }).join('\n');
				var msg = cfg.waText.replace('{items}\n', txt ? txt + '\n' : '');
				waLinks.forEach(function (a) { a.setAttribute('href', cfg.waBase + '?text=' + encodeURIComponent(msg)); });
			}
		}
	});

	/* ---- Cards: Add → 1, stepper ±1, typed quantity ------------------------- */

	var tracked = {};
	function changed(card) {
		var id = card.getAttribute('data-scl-item');
		var q = qtyOf(card);
		if (q > 0 && !tracked[id] && items[id]) {
			tracked[id] = true;
			L.track('AddToCart', 'add_to_cart', trackParams([{ id: id, item: items[id], qty: q }]));
		}
		api.refresh();
	}

	cards.forEach(function (card) {
		var input = inputOf(card);
		if (!input) {
			return;
		}
		var add = $('[data-scl-add]', card);
		if (add) {
			add.addEventListener('click', function () {
				input.value = '1';
				changed(card);
				input.focus();
			});
		}
		$$('[data-scl-step]', card).forEach(function (btn) {
			btn.addEventListener('click', function () {
				input.value = String((parseInt(input.value, 10) || 0) + Number(btn.getAttribute('data-scl-step')));
				changed(card);
				if (qtyOf(card) === 0 && add) {
					add.focus(); // the stepper hides at 0
				}
			});
		});
		input.addEventListener('change', function () { changed(card); });
	});

	/* ---- Guide links: jump to the card and point at it ---------------------- */

	$$('[data-scl-jump]').forEach(function (a) {
		a.addEventListener('click', function (e) {
			var card = document.getElementById((a.getAttribute('href') || '').slice(1));
			if (!card) {
				return;
			}
			e.preventDefault();
			L.scrollTo(card);
			card.classList.remove('is-flash');
			void card.offsetWidth; // restart the highlight
			card.classList.add('is-flash');
			var target = $('[data-scl-add]:not([hidden])', card) || inputOf(card);
			if (target) {
				target.focus({ preventScroll: true });
			}
		});
	});

	L.sticky(form);
	L.faq();
})();

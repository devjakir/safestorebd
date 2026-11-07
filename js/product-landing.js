/* Product landing (Facebook campaign) — gallery, size pills, qty, WhatsApp
 * text, sticky bar, FAQ accordion, tracking. No jQuery. The order form works
 * without this file (plain GET → cart → checkout); this only polishes it. */
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

	/* ---- Order form: sizes, qty, WhatsApp text, labels ---------------------- */

	var form = document.querySelector('[data-spl-form]');
	var sizes = Array.prototype.slice.call(document.querySelectorAll('[data-spl-size]'));
	var sizesBox = document.querySelector('[data-spl-sizes]');
	var hint = document.querySelector('[data-spl-size-hint]');
	var hintDefault = hint ? hint.textContent : '';
	var qtyInput = document.querySelector('[data-spl-qty-input]');
	var prices = Array.prototype.slice.call(document.querySelectorAll('[data-spl-price]'));
	var submitLabel = document.querySelector('[data-spl-submit-label]');
	var waLinks = Array.prototype.slice.call(document.querySelectorAll('[data-spl-wa]'));
	var options = cfg.options || {};

	function chosen() {
		for (var i = 0; i < sizes.length; i++) {
			if (sizes[i].checked) {
				return sizes[i];
			}
		}
		return null;
	}

	function clampQty() {
		if (!qtyInput) {
			return 1;
		}
		var pick = chosen();
		var max = pick && options[pick.value] && options[pick.value].max > 0 ? options[pick.value].max : Number(qtyInput.max) || 24;
		var v = parseInt(qtyInput.value, 10);
		if (isNaN(v) || v < 1) {
			v = 1;
		}
		if (v > max) {
			v = max;
		}
		qtyInput.value = String(v);
		qtyInput.max = String(max);
		return v;
	}

	function updateWhatsApp(label) {
		if (!cfg.waBase || !cfg.waText) {
			return;
		}
		var text = cfg.waText.replace('{size}', label || '');
		var qty = clampQty();
		text = text.replace(/পরিমাণ: [০-৯\d]+/, 'পরিমাণ: ' + bn(qty));
		var href = cfg.waBase + '?text=' + encodeURIComponent(text);
		waLinks.forEach(function (a) {
			a.setAttribute('href', href);
		});
	}

	function refresh() {
		var pick = chosen();
		var label = pick && options[pick.value] ? options[pick.value].label : '';
		var price = pick && options[pick.value] && options[pick.value].price > 0 ? options[pick.value].price : cfg.price;
		var qty = clampQty();
		sizes.forEach(function (input) {
			var cell = input.closest('.spl-size');
			if (cell) {
				cell.classList.toggle('is-checked', input.checked);
			}
		});
		if (sizesBox && pick) {
			sizesBox.classList.remove('is-error');
			if (hint) {
				hint.textContent = hintDefault;
			}
		}
		if (price) {
			// Hero shows the unit price of the chosen size; the sticky bar shows the line total.
			prices.forEach(function (el) {
				el.textContent = el.getAttribute('data-spl-price') === 'total' ? taka(Number(price) * qty) : taka(price);
			});
		}
		if (submitLabel) {
			submitLabel.textContent = label ? 'অর্ডার করুন — সাইজ ' + bn(label) : 'অর্ডার করুন — ক্যাশ অন ডেলিভারি';
		}
		updateWhatsApp(label);
	}

	sizes.forEach(function (input) {
		input.addEventListener('change', refresh);
		// Native "please select" bubble is English; show the Bangla hint and scroll instead.
		input.addEventListener('invalid', function (e) {
			e.preventDefault();
			if (sizesBox) {
				sizesBox.classList.add('is-error');
				if (hint) {
					hint.textContent = 'অনুগ্রহ করে আগে সাইজ নির্বাচন করুন।';
				}
				sizesBox.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
			}
		});
	});

	Array.prototype.slice.call(document.querySelectorAll('[data-spl-qty]')).forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (!qtyInput) {
				return;
			}
			var v = parseInt(qtyInput.value, 10) || 1;
			qtyInput.value = String(v + Number(btn.getAttribute('data-spl-qty')));
			refresh();
		});
	});
	if (qtyInput) {
		qtyInput.addEventListener('change', refresh);
	}

	if (form) {
		form.addEventListener('submit', function () {
			var pick = chosen();
			track('AddToCart', 'add_to_cart', productParams({
				value: (pick && options[pick.value] && options[pick.value].price > 0 ? options[pick.value].price : Number(cfg.price) || 0) * clampQty(),
				num_items: clampQty(),
				variant: pick && options[pick.value] ? options[pick.value].label : ''
			}));
			var btns = [document.querySelector('[data-spl-submit]'), document.querySelector('[data-spl-sticky-cta]')];
			window.setTimeout(function () {
				btns.forEach(function (b) {
					if (b) {
						b.setAttribute('disabled', 'disabled');
					}
				});
				if (submitLabel) {
					submitLabel.textContent = 'চেকআউটে নেওয়া হচ্ছে…';
				}
			}, 0);
			// Back button: re-enable so the form can be used again.
			window.addEventListener('pageshow', function () {
				btns.forEach(function (b) {
					if (b) {
						b.removeAttribute('disabled');
					}
				});
				refresh();
			}, { once: true });
		});
		refresh();
	}

	/* ---- "অর্ডার করুন" links elsewhere on the page → scroll to the form ------ */

	Array.prototype.slice.call(document.querySelectorAll('[data-spl-goto]')).forEach(function (a) {
		a.addEventListener('click', function (e) {
			var target = document.getElementById('order');
			if (!target) {
				return;
			}
			e.preventDefault();
			(sizesBox || target).scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
			var first = sizes.filter(function (i) { return !i.disabled; })[0];
			if (first && !chosen()) {
				first.focus({ preventScroll: true });
			}
		});
	});

	/* ---- Sticky bar: hide while the real order button is on screen ----------- */

	var sticky = document.querySelector('[data-spl-sticky]');
	var cta = document.querySelector('[data-spl-submit]') || document.querySelector('[data-spl-pdp]');
	var footer = document.querySelector('.lp-footer');
	if (sticky && 'IntersectionObserver' in window) {
		var seen = { cta: false, footer: false };
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.target === cta) {
					seen.cta = en.isIntersecting;
				} else if (en.target === footer) {
					seen.footer = en.isIntersecting;
				}
			});
			sticky.classList.toggle('is-hidden', seen.cta || seen.footer);
		}, { threshold: 0.4 });
		if (cta) {
			io.observe(cta);
		}
		if (footer) {
			io.observe(footer);
		}
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

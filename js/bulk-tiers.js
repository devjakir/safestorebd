// Product page bulk price table (inc/bulk-tier-pricing.php): highlights the
// tier for the chosen quantity, shows "your price" and the next tier, and lets
// a tier row set the quantity. Variable products follow the chosen variation.
(function ($) {
  var box = document.querySelector('[data-sft-bulk]');
  if (!box) {
    return;
  }

  var cfg;
  try {
    cfg = JSON.parse(box.getAttribute('data-sft-bulk'));
  } catch (e) {
    return;
  }
  if (!cfg || !cfg.tiers || !cfg.tiers.length) {
    return;
  }

  var scope = box.closest('.summary') || document;
  var form = scope.querySelector('form.cart') || document.querySelector('form.cart');
  var statusEl = box.querySelector('[data-sft-bulk-status]');
  var baseEl = box.querySelector('[data-sft-bulk-base]');
  var rows = Array.prototype.slice.call(box.querySelectorAll('.sft-bulk__tier'));
  var base = Number(cfg.base) || 0;
  var stockNote = '';

  // Same shape as wc_price(): currency position + separators from WooCommerce, zeros trimmed.
  function money(n) {
    var rounded = Math.round(n * 100) / 100;
    var fixed = rounded.toFixed(rounded % 1 ? 2 : 0).split('.');
    var text = fixed[0].replace(/\B(?=(\d{3})+(?!\d))/g, cfg.thousand) + (fixed[1] ? cfg.decimal + fixed[1] : '');
    switch (cfg.pos) {
      case 'left_space':
        return cfg.symbol + ' ' + text;
      case 'right':
        return text + cfg.symbol;
      case 'right_space':
        return text + ' ' + cfg.symbol;
      default:
        return cfg.symbol + text;
    }
  }

  function fill(tpl, args) {
    var i = 0;
    return tpl.replace(/%(\d+\$)?[sd]/g, function (m, pos) {
      var idx = pos ? parseInt(pos, 10) - 1 : i++;
      return args[idx] !== undefined ? args[idx] : '';
    });
  }

  function qtyInput() {
    return form ? form.querySelector('input.qty') : null;
  }

  function currentQty() {
    var input = qtyInput();
    var q = input ? parseInt(input.value, 10) : 1;
    return q > 0 ? q : 1;
  }

  // Tiers that are cheaper than the current base, in order.
  function liveTiers() {
    var out = [];
    var prev = base;
    cfg.tiers.forEach(function (t) {
      if (t.price < prev) {
        out.push(t);
        prev = t.price;
      }
    });
    return out;
  }

  function render() {
    var qty = currentQty();
    var tiers = liveTiers();
    var tier = null;
    var next = null;
    tiers.forEach(function (t) {
      if (qty >= t.min) {
        tier = t;
      } else if (!next) {
        next = t;
      }
    });
    var price = tier ? tier.price : base;

    if (baseEl) {
      baseEl.textContent = money(base);
    }

    rows.forEach(function (row) {
      var min = parseInt(row.getAttribute('data-min'), 10);
      var rowPrice = Number(row.getAttribute('data-price')) || 0;
      if (rowPrice) {
        var usable = rowPrice < base;
        row.hidden = !usable;
        var off = row.querySelector('[data-sft-bulk-off]');
        if (off && usable) {
          off.textContent = fill(cfg.i18n.saveRow, [money(base - rowPrice)]);
        }
      }
      var active = tier ? min === tier.min : min === 1;
      row.classList.toggle('is-active', active);
      var btn = row.querySelector('button');
      if (btn) {
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
      }
    });

    var parts = [];
    if (tier || qty > 1) {
      var line = fill(cfg.i18n.yourPrice, [money(price), qty, money(price * qty)]);
      if (tier) {
        line += ' · ' + fill(cfg.i18n.save, [money((base - price) * qty)]);
      }
      parts.push(line);
    }
    if (next) {
      var more = next.min - qty;
      if (more > 0 && more <= Math.max(10, qty)) {
        parts.push(fill(cfg.i18n.next, [more, money(next.price)]));
      }
    } else if (tier) {
      parts.push(cfg.i18n.best);
    }
    if (stockNote) {
      parts.push(stockNote);
    }

    if (statusEl) {
      statusEl.textContent = '';
      parts.forEach(function (p, i) {
        var span = document.createElement('span');
        span.textContent = p;
        if (i > 0) {
          span.className = 'sft-bulk__hint';
        }
        statusEl.appendChild(span);
      });
      statusEl.hidden = !parts.length;
    }

    box.classList.toggle('has-tier', !!tier);
    updateSticky(tier ? price : 0, qty);
  }

  // Mobile sticky bar: "৳140/pc × 60" next to the price.
  function updateSticky(price, qty) {
    var info = document.querySelector('.sft-sticky-atc__info');
    if (!info) {
      return;
    }
    var el = info.querySelector('.sft-sticky-atc__bulk');
    if (!price) {
      if (el) {
        el.remove();
      }
      return;
    }
    if (!el) {
      el = document.createElement('span');
      el.className = 'sft-sticky-atc__bulk';
      info.appendChild(el);
    }
    el.textContent = money(price) + '/' + cfg.i18n.pc + ' × ' + qty;
  }

  function setQty(q) {
    var input = qtyInput();
    if (!input) {
      return;
    }
    var max = input.max !== '' ? parseFloat(input.max) : Infinity;
    stockNote = '';
    if (q > max) {
      stockNote = fill(cfg.i18n.stock, [max]);
      q = max;
    }
    input.value = q;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  box.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-sft-bulk-pick]');
    if (!btn) {
      return;
    }
    setQty(parseInt(btn.getAttribute('data-sft-bulk-pick'), 10));
    var input = qtyInput();
    if (input && window.matchMedia('(max-width: 768px)').matches) {
      input.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });

  document.addEventListener('input', function (e) {
    if (e.target.matches && e.target.matches('form.cart input.qty')) {
      if (e.isTrusted) {
        stockNote = '';
      }
      render();
    }
  });
  document.addEventListener('change', function (e) {
    if (e.target.matches && e.target.matches('form.cart input.qty')) {
      render();
    }
  });

  if (cfg.variable && form && $) {
    $(form).on('found_variation', function (e, variation) {
      var p = variation && Number(variation.display_price);
      if (p > 0) {
        base = p;
        render();
      }
    });
    $(form).on('reset_data', function () {
      base = Number(cfg.base) || 0;
      render();
    });
  }

  render();
})(window.jQuery);

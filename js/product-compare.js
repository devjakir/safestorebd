/**
 * SafeStoreBD — Product compare (floating bar + compare page).
 * Shared localStorage key with PDP toggle: sft_compare.
 */
(function () {
  'use strict';

  if (window.__sftProductCompare) {
    return;
  }
  window.__sftProductCompare = true;

  var CFG = window.sftCompare || {};
  var I18N = CFG.i18n || {};
  var MAX = Math.max(2, parseInt(CFG.max, 10) || 4);
  var KEY = 'sft_compare';
  var DISMISS_KEY = 'sft_compare_bar_dismissed';
  var EVENT = 'sft:compare-change';
  var cache = {};
  var fetchTimer = null;

  function t(key, fallback) {
    return typeof I18N[key] === 'string' && I18N[key] !== '' ? I18N[key] : fallback;
  }

  function announce(message) {
    var node = document.getElementById('sft-compare-live');
    if (!node) {
      return;
    }
    node.textContent = '';
    window.setTimeout(function () {
      node.textContent = message;
    }, 40);
  }

  function readIds() {
    try {
      var raw = window.localStorage.getItem(KEY);
      var list = raw ? JSON.parse(raw) : [];
      if (!Array.isArray(list)) {
        return [];
      }
      var out = [];
      var seen = {};
      for (var i = 0; i < list.length; i++) {
        var id = String(list[i] || '').replace(/\D/g, '');
        if (!id || seen[id]) {
          continue;
        }
        seen[id] = true;
        out.push(id);
        if (out.length >= MAX) {
          break;
        }
      }
      return out;
    } catch (err) {
      return [];
    }
  }

  function writeIds(ids) {
    try {
      window.localStorage.setItem(KEY, JSON.stringify(ids));
    } catch (err) {
      /* private mode / quota */
    }
    try {
      window.dispatchEvent(
        new CustomEvent(EVENT, {
          detail: { ids: ids.slice() },
        })
      );
    } catch (err2) {
      /* IE / old — ignore */
    }
  }

  function isDismissed() {
    try {
      return window.sessionStorage.getItem(DISMISS_KEY) === '1';
    } catch (err) {
      return false;
    }
  }

  function setDismissed(value) {
    try {
      if (value) {
        window.sessionStorage.setItem(DISMISS_KEY, '1');
      } else {
        window.sessionStorage.removeItem(DISMISS_KEY);
      }
    } catch (err) {
      /* ignore */
    }
  }

  var Store = {
    key: KEY,
    max: MAX,
    getIds: readIds,
    has: function (id) {
      return readIds().indexOf(String(id)) !== -1;
    },
    add: function (id) {
      id = String(id || '');
      if (!id) {
        return { ok: false, reason: 'empty' };
      }
      var list = readIds();
      if (list.indexOf(id) !== -1) {
        return { ok: true, ids: list, duplicate: true };
      }
      if (list.length >= MAX) {
        return { ok: false, reason: 'full', ids: list };
      }
      list.push(id);
      setDismissed(false);
      writeIds(list);
      return { ok: true, ids: list, added: true };
    },
    remove: function (id) {
      id = String(id || '');
      var list = readIds().filter(function (x) {
        return x !== id;
      });
      writeIds(list);
      return { ok: true, ids: list };
    },
    toggle: function (id) {
      id = String(id || '');
      if (!id) {
        return { ok: false, reason: 'empty' };
      }
      if (Store.has(id)) {
        var removed = Store.remove(id);
        removed.removed = true;
        return removed;
      }
      return Store.add(id);
    },
    clear: function () {
      writeIds([]);
      return { ok: true, ids: [] };
    },
  };

  window.SftCompare = Store;

  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function fetchProducts(ids) {
    ids = (ids || []).map(String).filter(Boolean);
    if (!ids.length) {
      return Promise.resolve([]);
    }

    var missing = ids.filter(function (id) {
      return !cache[id];
    });

    var resolveFromCache = function () {
      return ids.map(function (id) {
        return cache[id];
      }).filter(Boolean);
    };

    if (!missing.length) {
      return Promise.resolve(resolveFromCache());
    }

    if (!CFG.restUrl) {
      return Promise.resolve(resolveFromCache());
    }

    var url = CFG.restUrl + (CFG.restUrl.indexOf('?') === -1 ? '?' : '&') + 'ids=' + encodeURIComponent(missing.join(','));
    return fetch(url, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    })
      .then(function (res) {
        if (!res.ok) {
          throw new Error('compare fetch failed');
        }
        return res.json();
      })
      .then(function (data) {
        var products = (data && data.products) || [];
        var found = {};
        for (var i = 0; i < products.length; i++) {
          var p = products[i];
          if (p && p.id) {
            cache[String(p.id)] = p;
            found[String(p.id)] = true;
          }
        }
        // Drop IDs that were requested but no longer resolve (deleted products).
        var current = readIds();
        var next = current.filter(function (id) {
          return missing.indexOf(id) === -1 || !!found[id];
        });
        if (next.length !== current.length) {
          writeIds(next);
        }
        return resolveFromCache();
      });
  }

  /* ------------------------------------------------------------------ */
  /* Floating bar                                                         */
  /* ------------------------------------------------------------------ */

  function barRoot() {
    return document.querySelector('[data-sft-compare-bar]');
  }

  function setBodyCompareOpen(open) {
    document.documentElement.classList.toggle('sft-compare-bar-open', open);
  }

  function renderSlots(products, ids) {
    var list = document.querySelector('[data-sft-compare-slots]');
    if (!list) {
      return;
    }

    var byId = {};
    for (var i = 0; i < products.length; i++) {
      byId[String(products[i].id)] = products[i];
    }

    var html = '';
    for (var s = 0; s < MAX; s++) {
      var id = ids[s];
      var p = id ? byId[id] : null;
      if (p) {
        html +=
          '<li class="sft-compare-bar__slot is-filled" data-id="' +
          escapeHtml(String(p.id)) +
          '">' +
          '<a class="sft-compare-bar__thumb" href="' +
          escapeHtml(p.url) +
          '" title="' +
          escapeHtml(p.name) +
          '">' +
          '<img src="' +
          escapeHtml(p.image) +
          '" alt="" width="56" height="56" loading="lazy" decoding="async" />' +
          '</a>' +
          '<button type="button" class="sft-compare-bar__remove" data-sft-compare-remove="' +
          escapeHtml(String(p.id)) +
          '" aria-label="' +
          escapeHtml(t('remove', 'Remove') + ': ' + p.name) +
          '">' +
          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>' +
          '</button>' +
          '</li>';
      } else {
        html +=
          '<li class="sft-compare-bar__slot is-empty" aria-hidden="true">' +
          '<span class="sft-compare-bar__placeholder" title="' +
          escapeHtml(t('slotEmpty', 'Add product')) +
          '">+</span>' +
          '</li>';
      }
    }
    list.innerHTML = html;
  }

  function updateBarCta(count) {
    var cta = document.querySelector('[data-sft-compare-cta]');
    if (!cta) {
      return;
    }
    cta.setAttribute('href', CFG.compareUrl || '/compare/');
    if (count >= 2) {
      cta.classList.remove('is-disabled');
      cta.removeAttribute('aria-disabled');
      cta.textContent = t('compare', 'Compare') + ' (' + count + ')';
    } else {
      cta.classList.add('is-disabled');
      cta.setAttribute('aria-disabled', 'true');
      cta.textContent = t('addMore', 'Add 1 more to compare');
    }
  }

  function refreshBar() {
    var root = barRoot();
    if (!root) {
      return;
    }

    var ids = readIds();
    var countNode = root.querySelector('[data-sft-compare-count]');
    if (countNode) {
      countNode.textContent = t('countLabel', '%d of %d')
        .replace('%d', String(ids.length))
        .replace('%d', String(MAX));
    }

    if (!ids.length || isDismissed()) {
      root.hidden = true;
      setBodyCompareOpen(false);
      if (!ids.length) {
        renderSlots([], []);
      }
      return;
    }

    root.hidden = false;
    setBodyCompareOpen(true);
    updateBarCta(ids.length);

    fetchProducts(ids).then(function (products) {
      var freshIds = readIds();
      renderSlots(products, freshIds);
      updateBarCta(freshIds.length);
      if (!freshIds.length) {
        root.hidden = true;
        setBodyCompareOpen(false);
      }
    });
  }

  function scheduleBarRefresh() {
    window.clearTimeout(fetchTimer);
    fetchTimer = window.setTimeout(refreshBar, 40);
  }

  /* ------------------------------------------------------------------ */
  /* Compare page                                                         */
  /* ------------------------------------------------------------------ */

  function pageRoot() {
    return document.querySelector('[data-sft-compare-page]');
  }

  function uniqueAttrNames(products) {
    var names = [];
    var seen = {};
    for (var i = 0; i < products.length; i++) {
      var attrs = products[i].attributes || [];
      for (var j = 0; j < attrs.length; j++) {
        var name = attrs[j].name;
        if (!name || seen[name]) {
          continue;
        }
        seen[name] = true;
        names.push(name);
      }
    }
    return names;
  }

  function attrValue(product, name) {
    var attrs = product.attributes || [];
    for (var i = 0; i < attrs.length; i++) {
      if (attrs[i].name === name) {
        return attrs[i].value || '—';
      }
    }
    return '—';
  }

  function renderEmptyPage(root, mode) {
    var title =
      mode === 'single'
        ? t('needMoreTitle', 'Add one more product')
        : t('emptyTitle', 'No products to compare');
    var text =
      mode === 'single'
        ? t('needMoreText', 'Comparison works best with at least two products.')
        : t('emptyText', 'Add products from a product page using Compare.');
    var shop = CFG.shopUrl || '/shop/';

    var singleCard = '';
    if (mode === 'single' && root._singleProduct) {
      var p = root._singleProduct;
      singleCard =
        '<div class="sft-compare-page__single">' +
        '<a class="sft-compare-page__single-card" href="' +
        escapeHtml(p.url) +
        '">' +
        '<img src="' +
        escapeHtml(p.image) +
        '" alt="" width="96" height="96" loading="lazy" />' +
        '<div><strong>' +
        escapeHtml(p.name) +
        '</strong><div class="sft-compare-page__price">' +
        (p.priceHtml || '') +
        '</div></div>' +
        '</a>' +
        '<button type="button" class="sft-compare-page__link-btn" data-sft-compare-remove="' +
        escapeHtml(String(p.id)) +
        '">' +
        escapeHtml(t('remove', 'Remove')) +
        '</button>' +
        '</div>';
    }

    root.innerHTML =
      '<div class="sft-compare-page__empty">' +
      '<h2 class="sft-compare-page__empty-title">' +
      escapeHtml(title) +
      '</h2>' +
      '<p class="sft-compare-page__empty-text">' +
      escapeHtml(text) +
      '</p>' +
      singleCard +
      '<a class="sft-compare-page__shop" href="' +
      escapeHtml(shop) +
      '">' +
      escapeHtml(t('browseShop', 'Browse shop')) +
      '</a>' +
      '</div>';
  }

  function renderTable(root, products) {
    var attrNames = uniqueAttrNames(products);
    var head =
      '<thead><tr><th scope="col" class="sft-compare-table__feature">' +
      escapeHtml(t('compareProducts', 'Compare products')) +
      '</th>';
    for (var h = 0; h < products.length; h++) {
      var ph = products[h];
      head +=
        '<th scope="col" class="sft-compare-table__product">' +
        '<div class="sft-compare-table__product-head">' +
        '<button type="button" class="sft-compare-table__remove" data-sft-compare-remove="' +
        escapeHtml(String(ph.id)) +
        '" aria-label="' +
        escapeHtml(t('remove', 'Remove') + ': ' + ph.name) +
        '">' +
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>' +
        '</button>' +
        '<a class="sft-compare-table__img" href="' +
        escapeHtml(ph.url) +
        '"><img src="' +
        escapeHtml(ph.image) +
        '" alt="" width="120" height="120" loading="lazy" decoding="async" /></a>' +
        '<a class="sft-compare-table__name" href="' +
        escapeHtml(ph.url) +
        '">' +
        escapeHtml(ph.name) +
        '</a>' +
        '</div></th>';
    }
    head += '</tr></thead>';

    function row(label, cellsHtml) {
      return (
        '<tr><th scope="row">' +
        escapeHtml(label) +
        '</th>' +
        cellsHtml +
        '</tr>'
      );
    }

    var body = '<tbody>';

    var priceCells = '';
    for (var i = 0; i < products.length; i++) {
      priceCells +=
        '<td class="sft-compare-page__price">' + (products[i].priceHtml || '—') + '</td>';
    }
    body += row(t('price', 'Price'), priceCells);

    var stockCells = '';
    for (var s = 0; s < products.length; s++) {
      var inStock = !!products[s].inStock;
      stockCells +=
        '<td><span class="sft-compare-stock ' +
        (inStock ? 'is-in' : 'is-out') +
        '">' +
        escapeHtml(products[s].stockStatus || (inStock ? 'In stock' : 'Out of stock')) +
        '</span></td>';
    }
    body += row(t('availability', 'Availability'), stockCells);

    var skuCells = '';
    for (var k = 0; k < products.length; k++) {
      skuCells += '<td>' + escapeHtml(products[k].sku || '—') + '</td>';
    }
    body += row(t('sku', 'SKU'), skuCells);

    var catCells = '';
    for (var c = 0; c < products.length; c++) {
      var cats = products[c].categories || [];
      catCells += '<td>' + escapeHtml(cats.length ? cats.join(', ') : '—') + '</td>';
    }
    body += row(t('category', 'Category'), catCells);

    for (var a = 0; a < attrNames.length; a++) {
      var name = attrNames[a];
      var cells = '';
      for (var p = 0; p < products.length; p++) {
        cells += '<td>' + escapeHtml(attrValue(products[p], name)) + '</td>';
      }
      body += row(name, cells);
    }

    var actionCells = '';
    for (var x = 0; x < products.length; x++) {
      var px = products[x];
      actionCells +=
        '<td><a class="sft-compare-table__cta" href="' +
        escapeHtml(px.url) +
        '">' +
        escapeHtml(px.addToCartText || t('viewComparison', 'View')) +
        '</a></td>';
    }
    body += row(t('actions', 'Actions'), actionCells);
    body += '</tbody>';

    root.innerHTML =
      '<div class="sft-compare-page__toolbar">' +
      '<p class="sft-compare-page__summary">' +
      escapeHtml(
        t('countLabel', '%d of %d')
          .replace('%d', String(products.length))
          .replace('%d', String(MAX))
      ) +
      '</p>' +
      '<button type="button" class="sft-compare-page__clear" data-sft-compare-clear>' +
      escapeHtml(t('clearAll', 'Clear all')) +
      '</button>' +
      '</div>' +
      '<div class="sft-compare-table-wrap">' +
      '<table class="sft-compare-table">' +
      head +
      body +
      '</table>' +
      '</div>';
  }

  function refreshPage() {
    var root = pageRoot();
    if (!root) {
      return;
    }

    var ids = readIds();
    root.innerHTML =
      '<p class="sft-compare-page__loading">' + escapeHtml(t('loading', 'Loading comparison…')) + '</p>';

    if (!ids.length) {
      renderEmptyPage(root, 'empty');
      return;
    }

    fetchProducts(ids)
      .then(function (products) {
        ids = readIds();
        products = products.filter(function (p) {
          return ids.indexOf(String(p.id)) !== -1;
        });
        if (!products.length) {
          renderEmptyPage(root, 'empty');
          return;
        }
        if (products.length === 1) {
          root._singleProduct = products[0];
          renderEmptyPage(root, 'single');
          return;
        }
        root._singleProduct = null;
        renderTable(root, products);
      })
      .catch(function () {
        root.innerHTML =
          '<p class="sft-compare-page__error">' +
          escapeHtml(t('loadError', 'Could not load products. Please try again.')) +
          '</p>';
      });
  }

  /* ------------------------------------------------------------------ */
  /* PDP helper: View comparison link                                     */
  /* ------------------------------------------------------------------ */

  function syncPdpViewLink() {
    var btn = document.querySelector('[data-sft-compare]');
    if (!btn) {
      return;
    }
    var ids = readIds();
    var productId = String(CFG.productId || (window.sftPdpActions && window.sftPdpActions.productId) || '');
    var active = productId && ids.indexOf(productId) !== -1;
    var link = document.querySelector('[data-sft-compare-view]');

    if (active && ids.length >= 1) {
      if (!link) {
        link = document.createElement('a');
        link.className = 'sft-pdp-actions__view-compare';
        link.setAttribute('data-sft-compare-view', '');
        link.href = CFG.compareUrl || '/compare/';
        btn.insertAdjacentElement('afterend', link);
      }
      link.hidden = false;
      link.href = CFG.compareUrl || '/compare/';
      link.textContent =
        ids.length >= 2
          ? t('viewComparison', 'View comparison') + ' (' + ids.length + ')'
          : t('viewComparison', 'View comparison');
    } else if (link) {
      link.hidden = true;
    }
  }

  /* ------------------------------------------------------------------ */
  /* Events                                                               */
  /* ------------------------------------------------------------------ */

  function onStoreChange() {
    scheduleBarRefresh();
    if (CFG.isPage) {
      refreshPage();
    }
    syncPdpViewLink();
  }

  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!target || !target.closest) {
      return;
    }

    var removeBtn = target.closest('[data-sft-compare-remove]');
    if (removeBtn) {
      event.preventDefault();
      var rid = removeBtn.getAttribute('data-sft-compare-remove');
      Store.remove(rid);
      announce(t('removed', 'Removed from compare'));
      return;
    }

    if (target.closest('[data-sft-compare-clear]')) {
      event.preventDefault();
      Store.clear();
      announce(t('removed', 'Removed from compare'));
      return;
    }

    if (target.closest('[data-sft-compare-dismiss]')) {
      event.preventDefault();
      setDismissed(true);
      scheduleBarRefresh();
      return;
    }

    var cta = target.closest('[data-sft-compare-cta]');
    if (cta && cta.classList.contains('is-disabled')) {
      event.preventDefault();
      announce(t('addMore', 'Add 1 more to compare'));
    }
  });

  window.addEventListener(EVENT, onStoreChange);
  window.addEventListener('storage', function (event) {
    if (event.key === KEY) {
      onStoreChange();
    }
  });

  // Initial paint.
  scheduleBarRefresh();
  if (CFG.isPage) {
    refreshPage();
  }
  syncPdpViewLink();
})();

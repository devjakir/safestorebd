/**
 * Wishlist page renderer + account sync (see inc/wishlist.php).
 * Shares the "sft_wishlist" localStorage key with js/pdp-actions.js.
 */
(function () {
  'use strict';

  var CFG = window.sftWishlist || {};
  var I18N = CFG.i18n || {};
  var KEY = 'sft_wishlist';
  var EVENT = 'sft:wishlist-change';

  function t(k, f) {
    return I18N[k] || f;
  }

  function read() {
    try {
      var list = JSON.parse(window.localStorage.getItem(KEY) || '[]');
      return Array.isArray(list) ? list.map(String) : [];
    } catch (e) {
      return [];
    }
  }

  function write(list, silent) {
    try {
      window.localStorage.setItem(KEY, JSON.stringify(list));
    } catch (e) {}
    if (!silent) {
      try {
        window.dispatchEvent(new CustomEvent(EVENT, { detail: { ids: list.slice() } }));
      } catch (e) {}
    }
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  /* ---------------------------------------------------------------- */
  /* Account sync (logged-in only)                                     */
  /* ---------------------------------------------------------------- */
  function sync(mode) {
    if (!CFG.syncUrl || !window.fetch) {
      return Promise.resolve(read());
    }
    var body = new URLSearchParams();
    body.set('ids', read().join(','));
    body.set('mode', mode);
    return fetch(CFG.syncUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': CFG.nonce || '' },
      body: body
    })
      .then(function (r) {
        return r.ok ? r.json() : null;
      })
      .then(function (data) {
        if (data && Array.isArray(data.ids)) {
          write(data.ids.map(String), true);
          try {
            window.dispatchEvent(new CustomEvent('sft:wishlist-synced'));
          } catch (e) {}
          return data.ids.map(String);
        }
        return read();
      })
      .catch(function () {
        return read();
      });
  }

  // Changes made elsewhere on the page (PDP toggle) → save to the account.
  window.addEventListener(EVENT, function () {
    if (CFG.syncUrl) {
      sync('replace');
    }
  });

  /* ---------------------------------------------------------------- */
  /* Page                                                              */
  /* ---------------------------------------------------------------- */
  var root = document.querySelector('[data-sft-wishlist-page]');
  var countEl = document.querySelector('[data-sft-wishlist-count]');

  function setCount(n) {
    if (!countEl) {
      return;
    }
    countEl.textContent = n === 1 ? t('countOne', '1 saved product') : t('count', '%d saved products').replace('%d', n);
  }

  function renderEmpty() {
    setCount(0);
    root.innerHTML =
      '<div class="sft-wishlist-empty">' +
      '<svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>' +
      '<h2 class="sft-wishlist-empty__title">' + esc(t('emptyTitle', 'Your wishlist is empty')) + '</h2>' +
      '<p class="sft-wishlist-empty__text">' + esc(t('emptyText', '')) + '</p>' +
      '<a class="sft-wishlist-empty__cta" href="' + esc(CFG.shopUrl || '/shop/') + '">' + esc(t('shop', 'Browse products')) + '</a>' +
      '</div>';
  }

  function card(p) {
    var cta = p.ajaxAdd
      ? '<a href="' + esc(p.cartUrl) + '" data-quantity="1" data-product_id="' + p.id + '" data-product_name="' + esc(p.name) + '" class="button add_to_cart_button ajax_add_to_cart sft-wishlist-card__cta" rel="nofollow">' + esc(p.cartText) + '</a>'
      : '<a href="' + esc(p.url) + '" class="button sft-wishlist-card__cta sft-wishlist-card__cta--ghost">' + esc(p.cartText) + '</a>';
    return (
      '<li class="sft-wishlist-card" data-id="' + p.id + '">' +
      '<a class="sft-wishlist-card__media" href="' + esc(p.url) + '" tabindex="-1" aria-hidden="true">' +
      '<img src="' + esc(p.image) + '"' + (p.imageSrcset ? ' srcset="' + esc(p.imageSrcset) + '" sizes="(max-width: 600px) 46vw, 240px"' : '') + ' alt="" width="300" height="300" loading="lazy" decoding="async">' +
      '</a>' +
      '<div class="sft-wishlist-card__body">' +
      '<h2 class="sft-wishlist-card__title"><a href="' + esc(p.url) + '">' + esc(p.name) + '</a></h2>' +
      '<div class="sft-wishlist-card__price">' + (p.priceHtml || '') + '</div>' +
      (p.inStock ? '' : '<p class="sft-wishlist-card__stock">' + esc(t('outOfStock', 'Out of stock')) + '</p>') +
      '<div class="sft-wishlist-card__actions">' + cta +
      '<button type="button" class="sft-wishlist-card__remove" data-sft-wl-remove="' + p.id + '" aria-label="' + esc(t('removeName', 'Remove %s').replace('%s', p.name)) + '">' + esc(t('remove', 'Remove')) + '</button>' +
      '</div></div></li>'
    );
  }

  function render(ids) {
    if (!ids.length) {
      renderEmpty();
      return;
    }
    var url = CFG.productsUrl + (CFG.productsUrl.indexOf('?') === -1 ? '?' : '&') + 'ids=' + encodeURIComponent(ids.join(','));
    fetch(url, { credentials: 'same-origin' })
      .then(function (r) {
        if (!r.ok) {
          throw new Error('wishlist');
        }
        return r.json();
      })
      .then(function (data) {
        var products = (data && data.products) || [];
        // Drop IDs that no longer resolve (deleted / unpublished products).
        var alive = products.map(function (p) {
          return String(p.id);
        });
        if (alive.length !== ids.length) {
          write(ids.filter(function (id) {
            return alive.indexOf(id) !== -1;
          }));
        }
        if (!products.length) {
          renderEmpty();
          return;
        }
        setCount(products.length);
        root.innerHTML = '<ul class="sft-wishlist-grid">' + products.map(card).join('') + '</ul>';
      })
      .catch(function () {
        root.innerHTML = '<p class="sft-wishlist-page__error">' + esc(t('error', 'Could not load your wishlist.')) + '</p>';
      });
  }

  if (!root) {
    // Product page, logged in: pull the account copy so the toggle is right.
    if (CFG.syncUrl) {
      sync('merge');
    }
    return;
  }

  root.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-sft-wl-remove]');
    if (!btn) {
      return;
    }
    var id = String(btn.getAttribute('data-sft-wl-remove'));
    write(read().filter(function (x) {
      return x !== id;
    }));
    var li = btn.closest('.sft-wishlist-card');
    if (li) {
      li.parentNode.removeChild(li);
    }
    var left = root.querySelectorAll('.sft-wishlist-card').length;
    if (!left) {
      renderEmpty();
    } else {
      setCount(left);
    }
    if (countEl) {
      countEl.setAttribute('aria-live', 'polite');
    }
  });

  sync('merge').then(render);
})();

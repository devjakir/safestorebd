/**
 * SafeStoreBD — PDP action bar (compare, wishlist, share menu).
 * Vanilla JS, deferred, no dependencies. localStorage only — no network calls.
 */
(function () {
  'use strict';

  if (window.__sftPdpActions) {
    return;
  }
  window.__sftPdpActions = true;

  var CFG = window.sftPdpActions || {};
  var I18N = CFG.i18n || {};
  var PRODUCT_ID = String(CFG.productId || '');
  var MAX_COMPARE = 4;
  var WL_KEY = 'sft_wishlist';
  var CMP_KEY = 'sft_compare';

  function t(key, fallback) {
    return typeof I18N[key] === 'string' && I18N[key] !== '' ? I18N[key] : fallback;
  }

  function readList(key) {
    try {
      var raw = window.localStorage.getItem(key);
      var list = raw ? JSON.parse(raw) : [];
      return Array.isArray(list) ? list.map(String) : [];
    } catch (err) {
      return [];
    }
  }

  function writeList(key, list) {
    try {
      window.localStorage.setItem(key, JSON.stringify(list));
    } catch (err) {
      /* private mode / quota — ignore */
    }
  }

  function announce(message) {
    var node = document.getElementById('sft-pdp-actions-live');
    if (!node) {
      node = document.createElement('div');
      node.id = 'sft-pdp-actions-live';
      node.className = 'sft-pdp-actions__live';
      node.setAttribute('role', 'status');
      node.setAttribute('aria-live', 'polite');
      node.setAttribute('aria-atomic', 'true');
      document.body.appendChild(node);
    }
    node.textContent = '';
    window.setTimeout(function () {
      node.textContent = message;
    }, 40);
  }

  /* ------------------------------------------------------------------ */
  /* Wishlist / Compare                                                   */
  /* ------------------------------------------------------------------ */

  function setToggleState(btn, active, activeLabel, inactiveLabel) {
    if (!btn) {
      return;
    }
    btn.classList.toggle('is-active', active);
    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    var label = btn.querySelector('.sft-pdp-actions__label');
    if (label) {
      label.textContent = active ? activeLabel : inactiveLabel;
    }
    btn.setAttribute('aria-label', active ? activeLabel : inactiveLabel);
  }

  function syncWishlist() {
    var btn = document.querySelector('[data-sft-wishlist]');
    if (!btn || !PRODUCT_ID) {
      return;
    }
    var list = readList(WL_KEY);
    var active = list.indexOf(PRODUCT_ID) !== -1;
    setToggleState(
      btn,
      active,
      t('wishlistRemove', 'Remove from wishlist'),
      t('wishlistAdd', 'Add to wishlist')
    );
  }

  function syncCompare() {
    var btn = document.querySelector('[data-sft-compare]');
    if (!btn || !PRODUCT_ID) {
      return;
    }
    var list = readList(CMP_KEY);
    var active = list.indexOf(PRODUCT_ID) !== -1;
    setToggleState(
      btn,
      active,
      t('compareRemove', 'Remove from compare'),
      t('compareAdd', 'Compare')
    );
  }

  function toggleWishlist() {
    if (!PRODUCT_ID) {
      return;
    }
    var list = readList(WL_KEY);
    var idx = list.indexOf(PRODUCT_ID);
    if (idx === -1) {
      list.push(PRODUCT_ID);
      writeList(WL_KEY, list);
      syncWishlist();
      announce(t('wishlistAdded', 'Added to wishlist'));
      return;
    }
    list.splice(idx, 1);
    writeList(WL_KEY, list);
    syncWishlist();
    announce(t('wishlistRemoved', 'Removed from wishlist'));
  }

  function toggleCompare() {
    if (!PRODUCT_ID) {
      return;
    }
    var list = readList(CMP_KEY);
    var idx = list.indexOf(PRODUCT_ID);
    if (idx !== -1) {
      list.splice(idx, 1);
      writeList(CMP_KEY, list);
      syncCompare();
      announce(t('compareRemoved', 'Removed from compare'));
      return;
    }
    if (list.length >= MAX_COMPARE) {
      announce(t('compareFull', 'Compare list is full (max 4)'));
      return;
    }
    list.push(PRODUCT_ID);
    writeList(CMP_KEY, list);
    syncCompare();
    announce(t('compareAdded', 'Added to compare'));
  }

  /* ------------------------------------------------------------------ */
  /* Share menu                                                           */
  /* ------------------------------------------------------------------ */

  function shareRoot() {
    return document.querySelector('[data-sft-share]');
  }

  function setShareOpen(open) {
    var root = shareRoot();
    if (!root) {
      return;
    }
    var trigger = root.querySelector('[data-sft-share-trigger]');
    var panel = root.querySelector('[data-sft-share-panel]');
    root.classList.toggle('is-open', open);
    if (trigger) {
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    if (panel) {
      panel.hidden = !open;
    }
  }

  function toggleShare(event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    var root = shareRoot();
    if (!root) {
      return;
    }
    setShareOpen(!root.classList.contains('is-open'));
  }

  function closeShare() {
    setShareOpen(false);
  }

  /* ------------------------------------------------------------------ */
  /* Events                                                               */
  /* ------------------------------------------------------------------ */

  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!target || !target.closest) {
      return;
    }

    if (target.closest('[data-sft-wishlist]')) {
      event.preventDefault();
      toggleWishlist();
      return;
    }

    if (target.closest('[data-sft-compare]')) {
      event.preventDefault();
      toggleCompare();
      return;
    }

    if (target.closest('[data-sft-share-trigger]')) {
      toggleShare(event);
      return;
    }

    var root = shareRoot();
    if (root && root.classList.contains('is-open') && !target.closest('[data-sft-share]')) {
      closeShare();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeShare();
    }
  });

  syncWishlist();
  syncCompare();
  setShareOpen(false);
})();

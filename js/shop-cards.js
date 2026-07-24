/**
 * SafeStoreBD — Shop product-card enhancements.
 *
 * Category list: shown clamped to 2 lines with an ellipsis. When the full list
 * overflows, a "Show more / Show less" toggle is revealed so shoppers can
 * expand it without breaking the card layout. Pure vanilla JS, no dependencies.
 */
(function () {
  'use strict';

  if (window.__sftShopCards) {
    return;
  }
  window.__sftShopCards = true;

  function each(list, fn) {
    for (var i = 0; i < list.length; i++) {
      fn(list[i]);
    }
  }

  function bindToggle(wrap) {
    if (wrap.getAttribute('data-sft-cat-init')) {
      return;
    }
    var cat = wrap.querySelector('.sft-product-card__cat');
    var btn = wrap.querySelector('.sft-product-card__cat-toggle');
    if (!cat || !btn) {
      return;
    }
    wrap.setAttribute('data-sft-cat-init', '1');

    btn.addEventListener('click', function () {
      var expanded = wrap.classList.toggle('is-expanded');
      btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      btn.textContent = expanded
        ? (btn.getAttribute('data-less') || 'Show less')
        : (btn.getAttribute('data-more') || 'Show more');
    });
  }

  // Reveal the toggle only when the clamped category actually overflows.
  function refresh() {
    each(document.querySelectorAll('.sft-product-card__cat-wrap'), function (wrap) {
      if (wrap.classList.contains('is-expanded')) {
        return; // leave the toggle visible so it can be collapsed
      }
      var cat = wrap.querySelector('.sft-product-card__cat');
      var btn = wrap.querySelector('.sft-product-card__cat-toggle');
      if (!cat || !btn) {
        return;
      }
      btn.hidden = (cat.scrollHeight - cat.clientHeight) <= 1;
    });
  }

  function init() {
    each(document.querySelectorAll('.sft-product-card__cat-wrap'), bindToggle);
    refresh();
  }

  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  ready(function () {
    init();
    // Line wrapping (and therefore overflow) changes with viewport width.
    var t;
    window.addEventListener('resize', function () {
      clearTimeout(t);
      t = setTimeout(refresh, 150);
    });
  });
})();

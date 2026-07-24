/**
 * SafeStoreBD — Add to Cart success toast.
 *
 * One singleton toast confirms every add to cart:
 *   • Archive/shop AJAX adds  → WooCommerce fires `added_to_cart`.
 *   • Single-product adds     → the form is intercepted and sent over AJAX
 *                               (no page reload), then `added_to_cart` is
 *                               triggered so the same path runs.
 *   • Standard non-AJAX adds  → PHP flags the product name in the session and
 *                               the toast shows once on the next page load.
 *
 * The header cart count/total stay in sync through WooCommerce cart fragments.
 * Auto-dismisses after a few seconds (paused while hovered/focused), and can be
 * closed with the × button, "Continue shopping", or the Escape key.
 */
(function () {
  'use strict';

  // Guard against double initialisation (duplicate enqueue / listeners).
  if (window.__sftCartToastReady) {
    return;
  }
  window.__sftCartToastReady = true;

  var P = window.sftCartToast || {};
  var AJAX_URL = P.ajaxUrl || '';
  var REDIRECT = P.redirectAfterAdd === true || P.redirectAfterAdd === 'yes' || P.redirectAfterAdd === '1';
  var I18N = P.i18n || {};
  var DEFAULT_NAME = I18N.defaultName || 'Item';
  var DURATION = 5000;
  var $ = window.jQuery;

  var el = null;
  var nameEl = null;
  var barEl = null;
  var timer = null;
  var hideTimer = null;
  var remaining = DURATION;
  var startedAt = 0;
  var paused = false;
  var lastTrigger = null;

  function onReady(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  // Resolve + wire the toast element the first time it is needed.
  function build() {
    if (el) {
      return el;
    }
    el = document.getElementById('sft-cart-toast');
    if (!el) {
      return null;
    }
    nameEl = el.querySelector('.sft-cart-toast__name');
    barEl = el.querySelector('.sft-cart-toast__progress');

    var closeBtn = el.querySelector('.sft-cart-toast__close');
    if (closeBtn) {
      closeBtn.addEventListener('click', dismiss);
    }
    var cont = el.querySelector('[data-sft-continue]');
    if (cont) {
      cont.addEventListener('click', dismiss);
    }

    // Pause the auto-dismiss while the shopper interacts with the toast.
    el.addEventListener('mouseenter', pause);
    el.addEventListener('mouseleave', resume);
    el.addEventListener('focusin', pause);
    el.addEventListener('focusout', function (e) {
      if (!el.contains(e.relatedTarget)) {
        resume();
      }
    });
    return el;
  }

  function show(name) {
    if (!build()) {
      return;
    }
    if (hideTimer) {
      clearTimeout(hideTimer);
      hideTimer = null;
    }
    if (nameEl) {
      nameEl.textContent = name || DEFAULT_NAME;
    }
    el.hidden = false;
    void el.offsetWidth; // reflow so the entrance transition runs every time
    el.classList.add('is-visible');
    restartBar();
    remaining = DURATION;
    startTimer();
  }

  function startTimer() {
    clearTimeout(timer);
    paused = false;
    startedAt = Date.now();
    if (barEl) {
      barEl.style.animationPlayState = 'running';
    }
    timer = setTimeout(dismiss, remaining);
  }

  function pause() {
    if (paused || !el || el.hidden) {
      return;
    }
    paused = true;
    clearTimeout(timer);
    remaining -= (Date.now() - startedAt);
    if (remaining < 0) {
      remaining = 0;
    }
    if (barEl) {
      barEl.style.animationPlayState = 'paused';
    }
  }

  function resume() {
    if (!paused || !el || el.hidden) {
      return;
    }
    startTimer();
  }

  function restartBar() {
    if (!barEl) {
      return;
    }
    barEl.classList.remove('is-counting');
    void barEl.offsetWidth;
    barEl.style.animationPlayState = 'running';
    barEl.classList.add('is-counting');
  }

  function dismiss() {
    if (!el || el.hidden) {
      return;
    }
    clearTimeout(timer);
    var returnFocus = lastTrigger && el.contains(document.activeElement);
    el.classList.remove('is-visible');
    if (barEl) {
      barEl.classList.remove('is-counting');
    }
    hideTimer = setTimeout(function () {
      el.hidden = true;
    }, 320);
    if (returnFocus) {
      try {
        lastTrigger.focus();
      } catch (e) {}
    }
  }

  function nameFromButton($btn) {
    if ($btn && $btn.length) {
      var card = $btn.closest('.sft-product-card');
      if (card.length) {
        var t = card.find('.sft-product-card__title').text();
        if (t && t.trim()) {
          return t.trim();
        }
      }
      var attr = $btn.attr('data-product_name');
      if (attr) {
        return attr.trim();
      }
    }
    var pt = document.querySelector('.product_title, h1.entry-title');
    return (pt && pt.textContent.trim()) ? pt.textContent.trim() : DEFAULT_NAME;
  }

  // Escape closes the toast.
  document.addEventListener('keydown', function (e) {
    if ((e.key === 'Escape' || e.key === 'Esc') && el && !el.hidden) {
      dismiss();
    }
  });

  onReady(function () {
    build();
    if (P.justAdded) {
      show(P.justAdded);
    }
  });

  // The WooCommerce events + AJAX below need jQuery (bundled by WooCommerce).
  if (!$) {
    return;
  }

  // 1) Archive AJAX adds — and our single-product trigger below — show the toast.
  $(document.body).on('added_to_cart', function (e, fragments, cartHash, $btn) {
    lastTrigger = ($btn && $btn.get) ? $btn.get(0) : null;
    show(nameFromButton($btn));
  });

  // 2) Single-product form → add via AJAX so there is no page reload.
  $(document).on('submit', 'form.cart', function (e) {
    if (!AJAX_URL || REDIRECT) {
      return; // fall back to the native submit / redirect-to-cart behaviour
    }

    var $form = $(this);
    if ($form.hasClass('grouped_form')) {
      return; // grouped products: let WooCommerce handle the multi-row submit
    }

    var $btn = $form.find('.single_add_to_cart_button').first();
    if (!$btn.length || $btn.is('.disabled, [disabled]')) {
      return; // not purchasable in this state
    }

    var productId = $form.find('[name="add-to-cart"]').val() || $btn.val() || $form.data('product_id');
    if (!productId) {
      return; // unknown product — let WooCommerce handle it natively
    }

    var variationId = $form.find('[name="variation_id"]').val();
    if ($form.hasClass('variations_form') && (!variationId || variationId === '0')) {
      return; // no variation chosen yet — let WooCommerce show its own validation
    }

    e.preventDefault();

    if ($btn.data('sftBusy')) {
      return; // block rapid duplicate submits
    }
    $btn.data('sftBusy', true).addClass('loading');
    lastTrigger = $btn.get(0);

    var data = {
      product_id: productId,
      quantity: $form.find('[name="quantity"]').val() || 1
    };
    if (variationId) {
      data.variation_id = variationId;
    }
    $form.find('[name^="attribute_"]').each(function () {
      data[this.name] = $(this).val();
    });

    $.ajax({
      url: AJAX_URL,
      type: 'POST',
      data: data,
      dataType: 'json',
      success: function (resp) {
        if (!resp) {
          return;
        }
        if (resp.error && resp.product_url) {
          window.location = resp.product_url; // e.g. must choose options
          return;
        }
        // Update the header cart (and any mini-cart) immediately, then let
        // WooCommerce cache the fragments and run the shared toast path.
        if (resp.fragments) {
          $.each(resp.fragments, function (key, value) {
            $(key).replaceWith(value);
          });
        }
        $(document.body).trigger('added_to_cart', [resp.fragments || {}, resp.cart_hash, $btn]);
      },
      complete: function () {
        $btn.removeClass('loading').data('sftBusy', false);
      }
    });
  });
})();

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
  var ERROR_DURATION = 9000;
  var AJAX_TIMEOUT = 20000;
  var $ = window.jQuery;

  var el = null;
  var titleEl = null;
  var nameEl = null;
  var barEl = null;
  var viewCartEl = null;
  var retryEl = null;
  var retryFn = null;
  var timer = null;
  var hideTimer = null;
  var remaining = DURATION;
  var startedAt = 0;
  var paused = false;
  var lastTrigger = null;

  function t(key, fallback) {
    return I18N[key] || fallback;
  }

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
    titleEl = el.querySelector('.sft-cart-toast__title');
    nameEl = el.querySelector('.sft-cart-toast__name');
    barEl = el.querySelector('.sft-cart-toast__progress');
    viewCartEl = el.querySelector('[data-sft-view-cart]');
    retryEl = el.querySelector('[data-sft-retry]');

    var closeBtn = el.querySelector('.sft-cart-toast__close');
    if (closeBtn) {
      closeBtn.addEventListener('click', dismiss);
    }
    var cont = el.querySelector('[data-sft-continue]');
    if (cont) {
      cont.addEventListener('click', dismiss);
    }
    if (retryEl) {
      retryEl.addEventListener('click', function () {
        var fn = retryFn;
        dismiss();
        if (fn) {
          fn();
        }
      });
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

  // One renderer for both states, so a success can never inherit error copy.
  function render(state, title, line, duration) {
    if (!build()) {
      return;
    }
    if (hideTimer) {
      clearTimeout(hideTimer);
      hideTimer = null;
    }
    var isError = state === 'error';
    el.classList.toggle('is-error', isError);
    el.setAttribute('role', isError ? 'alert' : 'status');
    if (titleEl) {
      titleEl.textContent = title;
    }
    if (nameEl) {
      nameEl.textContent = line;
    }
    if (viewCartEl) {
      viewCartEl.hidden = isError;
    }
    if (retryEl) {
      retryEl.hidden = !isError || !retryFn;
    }
    el.hidden = false;
    void el.offsetWidth; // reflow so the entrance transition runs every time
    el.classList.add('is-visible');
    restartBar();
    remaining = duration;
    startTimer();
  }

  function show(name) {
    retryFn = null;
    render('success', t('addedTitle', 'Added to cart'), name || DEFAULT_NAME, DURATION);
  }

  function showError(message, retry) {
    retryFn = typeof retry === 'function' ? retry : null;
    render('error', t('errorTitle', 'Not added to cart'), message, ERROR_DURATION);
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
        var cardTitle = card.find('.sft-product-card__title').text();
        if (cardTitle && cardTitle.trim()) {
          return cardTitle.trim();
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

  // Exposed for other theme scripts (e.g. the mobile sticky add-to-cart bar).
  window.sftCartToastShowError = showError;

  /* ------------------------------------------------------------------ */
  /* Variation gate                                                      */
  /* ------------------------------------------------------------------ */
  // WooCommerce 11 writes variation_id as soon as a size is picked, but only
  // re-enables the button after a setTimeout + slideDown (show_variation). A
  // click inside that window used to slip through half-ready. Keep Add to
  // cart / Buy now truly [disabled] until show_variation confirms a
  // purchasable variation that matches the hidden variation_id.
  function currentVariationId($form) {
    var v = $form.find('input[name="variation_id"]').val();
    return v && v !== '0' ? String(v) : '';
  }

  function isVariationReady($form) {
    var ready = $form.data('sftVariationReady');
    return !!ready && ready === currentVariationId($form);
  }

  function initVariationGate($form) {
    if ($form.data('sftGate')) {
      return;
    }
    $form.data('sftGate', true);

    // Only Add to cart is hard-disabled. Buy now stays clickable so the size
    // guard (js/footwear-sizing.js) can still show "Please select a size".
    var $atc = $form.find('.single_add_to_cart_button');
    var isFootwear = String($form.data('footwear')) === '1' || $form.hasClass('sft-variations-form--footwear');
    var readyLabel = $.trim($atc.first().text()) || t('addToCart', 'Add to cart');
    var selectLabel = isFootwear ? t('selectSize', 'Select a size') : t('selectOptions', 'Select options');

    function setState(state) {
      var locked = state !== 'ready';
      $atc.prop('disabled', locked).attr('aria-disabled', locked ? 'true' : 'false');
      $atc.toggleClass('sft-atc-needs-selection', state === 'select');
      $atc.toggleClass('sft-atc-unavailable', state === 'unavailable');
      $atc.text(state === 'select' ? selectLabel : (state === 'unavailable' ? t('unavailable', 'Unavailable') : readyLabel));
      $form.trigger('sft_variation_gate', [state]);
    }

    function lock() {
      $form.removeData('sftVariationReady');
      setState('select');
    }

    $form.on('reset_data hide_variation', lock);
    $form.on('show_variation', function (e, variation, purchasable) {
      var id = variation && variation.variation_id ? String(variation.variation_id) : '';
      if (purchasable && id && id === currentVariationId($form)) {
        $form.data('sftVariationReady', id);
        setState('ready');
      } else {
        $form.removeData('sftVariationReady');
        setState(id ? 'unavailable' : 'select');
      }
    });

    // Initial state: WooCommerce runs its first check_variations ~100ms after
    // init and fires show_variation for a pre-selected default, if any.
    if (!isVariationReady($form)) {
      lock();
    }
  }

  function initVariationGates() {
    $('form.variations_form').each(function () {
      initVariationGate($(this));
    });
  }
  $(initVariationGates);
  // Quick-view / AJAX-injected forms.
  $(document.body).on('wc_variation_form', 'form.variations_form', function () {
    initVariationGate($(this));
  });

  /* ------------------------------------------------------------------ */
  /* Stale fragment refresh guard                                        */
  /* ------------------------------------------------------------------ */
  // cart-fragments.js may start get_refreshed_fragments on page load. If that
  // response lands *after* our add-to-cart response it paints the old count
  // back into the header. Track in-flight refreshes and re-sync once they end.
  var refreshInflight = 0;
  var resyncAfterRefresh = false;
  $(document).ajaxSend(function (e, xhr, opts) {
    if (!opts || !opts.url || String(opts.url).indexOf('get_refreshed_fragments') === -1) {
      return;
    }
    refreshInflight++;
    xhr.always(function () {
      refreshInflight = Math.max(0, refreshInflight - 1);
      if (!refreshInflight && resyncAfterRefresh) {
        resyncAfterRefresh = false;
        $(document.body).trigger('wc_fragment_refresh');
      }
    });
  });

  function applyFragments(fragments) {
    $.each(fragments || {}, function (key, value) {
      $(key).replaceWith(value);
    });
  }

  /* ------------------------------------------------------------------ */
  /* Toast for every successful add                                      */
  /* ------------------------------------------------------------------ */
  // Archive AJAX adds (WooCommerce core) and our single-product adds below.
  $(document.body).on('added_to_cart', function (e, fragments, cartHash, $btn) {
    lastTrigger = ($btn && $btn.get) ? $btn.get(0) : null;
    show(nameFromButton($btn));
  });

  // Buy now must reach PHP as a normal submit so it can redirect to checkout;
  // remember which button was pressed for browsers without event.submitter.
  $(document).on('click', 'form.cart .sft-pdp-buy-now', function () {
    $(this).closest('form').data('sftBuyNow', true);
  });
  $(document).on('click', 'form.cart .single_add_to_cart_button', function () {
    $(this).closest('form').removeData('sftBuyNow');
  });

  /* ------------------------------------------------------------------ */
  /* Single-product AJAX add (queued)                                    */
  /* ------------------------------------------------------------------ */
  var queue = [];
  var running = false;
  var lastKey = '';
  var lastAt = 0;
  var DOUBLE_CLICK_MS = 250;

  // Fall back to a normal form post (WooCommerce then shows its own notice).
  function nativeSubmit($form, $btn) {
    $form.data('sftNative', true);
    var form = $form.get(0);
    var btn = $btn && $btn.get(0);
    if (form.requestSubmit) {
      try {
        if (btn) {
          btn.disabled = false;
        }
        form.requestSubmit(btn && btn.form === form ? btn : undefined);
        return;
      } catch (e) {}
    }
    if (btn && btn.name && !$form.find('input[type="hidden"][name="' + btn.name + '"]').length) {
      $('<input type="hidden">').attr('name', btn.name).val(btn.value).appendTo($form);
    }
    form.submit();
  }

  function runNext() {
    var job = queue.shift();
    if (!job) {
      running = false;
      return;
    }
    running = true;
    job.$btn.addClass('loading').attr('aria-busy', 'true');

    var retry = function () {
      nativeSubmit(job.$form, job.$btn);
    };

    $.ajax({
      url: AJAX_URL,
      type: 'POST',
      data: job.data,
      dataType: 'json',
      timeout: AJAX_TIMEOUT
    })
      .done(function (resp) {
        if (!resp || resp.error || !resp.fragments) {
          // Nothing was added (stock limit, missing option, expired session…).
          showError(t('errorGeneric', 'This item could not be added. Please check the size and quantity, then try again.'), retry);
          $(document.body).trigger('wc_fragment_refresh');
          return;
        }
        applyFragments(resp.fragments);
        if (refreshInflight) {
          resyncAfterRefresh = true;
        }
        // Lets cart-fragments.js cache the new fragments + hash, then toasts.
        $(document.body).trigger('added_to_cart', [resp.fragments, resp.cart_hash, job.$btn]);
      })
      .fail(function (xhr, status) {
        showError(
          status === 'timeout'
            ? t('errorTimeout', 'The connection is slow and we could not confirm the item was added. Check your cart or try again.')
            : t('errorNetwork', 'Network problem: the item was not added. Please try again.'),
          retry
        );
        // The add may still have gone through server-side; resync the header.
        $(document.body).trigger('wc_fragment_refresh');
      })
      .always(function () {
        job.$btn.removeClass('loading').removeAttr('aria-busy');
        runNext();
      });
  }

  function allOptionsChosen($form) {
    var ok = true;
    $form.find('select[name^="attribute_"]').each(function () {
      if (!$(this).val()) {
        ok = false;
      }
    });
    return ok;
  }

  // Run fn once the variation gate reports "ready" (max ~2s).
  function waitForGate($form, fn) {
    var done = false;
    var handler = function (e, state) {
      if (state !== 'ready' || done) {
        return;
      }
      done = true;
      $form.off('sft_variation_gate', handler);
      fn();
    };
    $form.on('sft_variation_gate', handler);
    setTimeout(function () {
      $form.off('sft_variation_gate', handler);
    }, 2000);
  }

  function nativeOrAjax($form, $btn, isBuyNow) {
    if (isBuyNow) {
      nativeSubmit($form, $btn);
      return;
    }
    if ($form.get(0).requestSubmit) {
      $form.get(0).requestSubmit($btn.get(0) && $btn.get(0).form === $form.get(0) ? $btn.get(0) : undefined);
    } else {
      $form.trigger('submit');
    }
  }

  $(document).on('submit', 'form.cart', function (e) {
    var $form = $(this);

    if ($form.data('sftNative')) {
      $form.removeData('sftNative');
      return; // explicit fallback to the normal post
    }
    if (!AJAX_URL || REDIRECT) {
      return; // fall back to the native submit / redirect-to-cart behaviour
    }
    if ($form.hasClass('grouped_form')) {
      return; // grouped products: let WooCommerce handle the multi-row submit
    }

    var submitter = e.originalEvent && e.originalEvent.submitter;
    var isBuyNow = (submitter && $(submitter).hasClass('sft-pdp-buy-now')) || $form.data('sftBuyNow');
    var isVariable = $form.hasClass('variations_form');

    if (isVariable && !isVariationReady($form)) {
      // Never post a half-ready form. If the options are all chosen and
      // WooCommerce is only finishing its show_variation step, retry once the
      // gate opens; otherwise the size guard has already shown its message.
      e.preventDefault();
      $form.removeData('sftBuyNow');
      if (allOptionsChosen($form)) {
        var $retryBtn = $(submitter || $form.find(isBuyNow ? '.sft-pdp-buy-now' : '.single_add_to_cart_button').first());
        waitForGate($form, function () {
          if (isBuyNow) {
            $form.data('sftBuyNow', true);
          }
          nativeOrAjax($form, $retryBtn, isBuyNow);
        });
      } else if (!$form.find('[data-sft-size-panel]').length) {
        showError(t('selectOptionsFirst', 'Please choose the product options first.'));
      }
      return;
    }

    if (isBuyNow) {
      $form.removeData('sftBuyNow');
      return; // Buy now: native submit → PHP adds to cart and redirects to checkout
    }

    var $btn = $form.find('.single_add_to_cart_button').first();
    if (!$btn.length || $btn.is('.disabled')) {
      return; // not purchasable in this state — WooCommerce handles it
    }

    var productId = $form.find('[name="add-to-cart"]').val() || $btn.val() || $form.data('product_id');
    if (!productId) {
      return; // unknown product — let WooCommerce handle it natively
    }

    e.preventDefault();

    var variationId = isVariable ? currentVariationId($form) : '';
    var data = {
      product_id: variationId || productId,
      quantity: $form.find('[name="quantity"]').val() || 1
    };
    if (variationId) {
      data.variation_id = variationId;
    }
    $form.find('[name^="attribute_"]').each(function () {
      data[this.name] = $(this).val();
    });

    // Swallow an accidental double-click (same payload within 250ms); every
    // deliberate add — even rapid ones — is queued and sent in order.
    var key = $.param(data);
    var now = Date.now();
    if (key === lastKey && now - lastAt < DOUBLE_CLICK_MS) {
      return;
    }
    lastKey = key;
    lastAt = now;

    lastTrigger = $btn.get(0);
    queue.push({ $form: $form, $btn: $btn, data: data });
    if (!running) {
      runNext();
    }
  });
})();

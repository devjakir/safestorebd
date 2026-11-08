/**
 * Mobile sticky add-to-cart bar (see inc/pdp-sticky-bar.php).
 */
(function ($) {
  'use strict';

  var MQ = window.matchMedia('(max-width: 768px)');

  $(function () {
    var bar = document.querySelector('[data-sft-sticky-atc]');
    var $form = $('.sft-pdp form.cart, .summary form.cart').first();
    if (!bar || !$form.length) {
      return;
    }

    var $priceOut = $(bar).find('[data-sft-sticky-price]');
    var $sizeOut = $(bar).find('[data-sft-sticky-size]');
    var basePrice = $priceOut.html();
    var isVariable = bar.getAttribute('data-variable') === '1';
    var mainBtn = $form.find('.single_add_to_cart_button').get(0);
    var mainVisible = true;

    function mainAtc() {
      return $form.find('.single_add_to_cart_button').first();
    }

    function sizeSelect() {
      var $s = $form.find('select[name="attribute_pa_size"], select[name="attribute_size"]').first();
      return $s.length ? $s : $form.find('select[name^="attribute_"]').first();
    }

    /* ---- show / hide ------------------------------------------------- */
    function update() {
      var on = MQ.matches && !mainVisible;
      if (on === !bar.hidden) {
        return;
      }
      if (on) {
        bar.hidden = false;
        void bar.offsetWidth;
        bar.classList.add('is-visible');
      } else {
        bar.classList.remove('is-visible');
        bar.hidden = true;
      }
      document.body.classList.toggle('sft-sticky-atc-on', on);
    }

    if (mainBtn && 'IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        // Visible, or still below the fold (not reached yet) → no bar.
        var entry = entries[0];
        mainVisible = entry.isIntersecting || entry.boundingClientRect.top > 0;
        update();
      }, { threshold: 0 }).observe(mainBtn);
    } else {
      return; // old browsers: no sticky bar, page works as before
    }
    if (MQ.addEventListener) {
      MQ.addEventListener('change', update);
    } else if (MQ.addListener) {
      MQ.addListener(update);
    }

    /* ---- mirror state ------------------------------------------------ */
    function syncSize() {
      if (!isVariable || !$sizeOut.length) {
        return;
      }
      var $s = sizeSelect();
      var label = $s.val() ? $.trim($s.find('option:selected').text()) : '';
      $sizeOut.text(label ? (bar.getAttribute('data-size-prefix') || 'Size:') + ' ' + label : (bar.getAttribute('data-select-label') || 'Select a size'));
      $sizeOut.toggleClass('is-chosen', !!label);
    }

    $form.on('found_variation', function (e, variation) {
      if (variation && variation.price_html) {
        $priceOut.html(variation.price_html);
      }
      syncSize();
    });
    $form.on('reset_data hide_variation', function () {
      $priceOut.html(basePrice);
      syncSize();
    });
    $form.on('change', 'select[name^="attribute_"]', syncSize);
    syncSize();

    /* ---- actions ----------------------------------------------------- */
    function goToSizes() {
      var target = $form.find('[data-sft-size-panel]').get(0) || $form.get(0);
      try {
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      } catch (e) {
        target.scrollIntoView();
      }
      // No size yet: the size guard on Buy now shows its inline message
      // (it blocks the submit, so nothing is bought).
      var $buy = $form.find('.sft-pdp-buy-now').first();
      if ($buy.length && !sizeSelect().val()) {
        $buy.trigger('click');
      }
    }

    $(bar).on('click', '[data-sft-sticky-add]', function () {
      var $atc = mainAtc();
      if (isVariable && !sizeSelect().val()) {
        goToSizes();
        return;
      }
      if ($atc.prop('disabled')) {
        // Size picked, WooCommerce still resolving it: add as soon as it is ready.
        var onGate = function (e, state) {
          if (state === 'ready') {
            $form.off('sft_variation_gate', onGate);
            mainAtc().trigger('click');
          }
        };
        $form.on('sft_variation_gate', onGate);
        setTimeout(function () {
          $form.off('sft_variation_gate', onGate);
        }, 2000);
        return;
      }
      $atc.trigger('click');
    });

    $(bar).on('click', '[data-sft-sticky-buy]', function () {
      var $buy = $form.find('.sft-pdp-buy-now').first();
      if (isVariable && !sizeSelect().val()) {
        goToSizes();
        return;
      }
      if ($buy.length) {
        $buy.trigger('click');
      } else {
        mainAtc().trigger('click');
      }
    });
  });
})(jQuery);

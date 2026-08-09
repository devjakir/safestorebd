/**
 * PDP Safety Shoes size selector (simple products).
 * — Button grid above Add to cart
 * — Syncs hidden input for cart meta
 * — Blocks Add to cart / Buy now until a size (39–44) is chosen
 */
(function ($) {
  'use strict';

  var cfg = window.safestorePdpShoeSize || {};
  var fieldName = cfg.field || 'safestore_shoe_size';
  var msg = (cfg.i18n && cfg.i18n.selectSize) || 'Please select a size';

  function init() {
    var $panel = $('[data-sft-shoe-size-panel]').first();
    if (!$panel.length) {
      return;
    }

    var $form = $panel.closest('form.cart');
    if (!$form.length) {
      return;
    }

    var $input = $form.find('input[name="' + fieldName + '"]');
    var $error = $panel.find('[data-sft-shoe-size-error]');
    var $atc = $form.find('.single_add_to_cart_button');
    var $buy = $form.find('.sft-pdp-buy-now');

    function selectedSize() {
      return $.trim(String($input.val() || ''));
    }

    function setSelected(size) {
      $input.val(size);
      $panel.find('.sft-pdp-shoe-size__swatch').each(function () {
        var on = String($(this).data('size')) === String(size);
        $(this).toggleClass('is-selected active', on);
        $(this).attr('aria-selected', on ? 'true' : 'false');
      });
      if (size) {
        $panel.removeClass('is-invalid');
        $error.prop('hidden', true);
        $atc.removeClass('disabled').prop('disabled', false);
        $buy.removeClass('disabled').attr('aria-disabled', 'false');
      } else {
        $atc.addClass('disabled').prop('disabled', true);
        $buy.addClass('disabled').attr('aria-disabled', 'true');
      }
    }

    function showError() {
      $panel.addClass('is-invalid');
      $error.prop('hidden', false);
      window.alert(msg);
      $panel.find('.sft-pdp-shoe-size__swatch').first().trigger('focus');
    }

    // Start gated until a size is chosen.
    setSelected(selectedSize());

    $panel.on('click', '.sft-pdp-shoe-size__swatch', function (e) {
      e.preventDefault();
      var size = String($(this).data('size') || '');
      if (!size) {
        return;
      }
      // Toggle off if clicking the same size.
      if (selectedSize() === size) {
        setSelected('');
        return;
      }
      setSelected(size);
    });

    function guard(e) {
      if (selectedSize()) {
        return;
      }
      e.preventDefault();
      e.stopImmediatePropagation();
      showError();
    }

    $form.on('click', '.single_add_to_cart_button, .sft-pdp-buy-now', guard);
    $form.on('submit', function (e) {
      if (selectedSize()) {
        return;
      }
      e.preventDefault();
      showError();
    });

    // Convert simple "Buy now" link into a size-aware checkout submit.
    if ($buy.is('a')) {
      $buy.on('click', function (e) {
        if (!selectedSize()) {
          e.preventDefault();
          showError();
          return;
        }
        e.preventDefault();
        // Submit cart form to checkout via buy-now flag.
        if (!$form.find('input[name="safestore_buy_now"]').length) {
          $form.append('<input type="hidden" name="safestore_buy_now" value="1">');
        }
        $form.trigger('submit');
      });
    }
  }

  $(init);
})(jQuery);

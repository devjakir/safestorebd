/**
 * Footwear PDP — size swatches, stock/SKU/price state, ATC gate.
 */
(function ($) {
  'use strict';

  var cfg = window.safestoreFootwear || {};
  var i18n = cfg.i18n || {};

  function getForm() {
    return $('form.variations_form.sft-variations-form--footwear, form.variations_form[data-footwear="1"]').first();
  }

  function getSizeSelect($form) {
    return $form
      .find('select[name="attribute_pa_size"], select.sft-size-swatches__select')
      .first();
  }

  function syncSwatchState($wrap, value) {
    $wrap.find('.sft-size-swatch').each(function () {
      var $btn = $(this);
      var selected = String($btn.data('value')) === String(value);
      $btn.toggleClass('is-selected', selected);
      $btn.attr('aria-selected', selected ? 'true' : 'false');
    });
  }

  function setActionsEnabled($form, enabled) {
    var $atc = $form.find('.single_add_to_cart_button');
    var $buy = $form.find('.sft-pdp-buy-now');

    $atc.toggleClass('disabled', !enabled);
    $atc.prop('disabled', !enabled);
    $atc.attr('aria-disabled', enabled ? 'false' : 'true');

    if ($buy.is('button')) {
      $buy.prop('disabled', !enabled);
      $buy.toggleClass('disabled', !enabled);
    } else {
      $buy.toggleClass('disabled', !enabled);
      $buy.attr('aria-disabled', enabled ? 'false' : 'true');
    }
  }

  function updateQtyLimits($form, variation) {
    var $qty = $form.find('.quantity input.qty');
    if (!$qty.length || !variation) {
      return;
    }

    if (typeof variation.min_qty !== 'undefined' && variation.min_qty !== '') {
      $qty.attr('min', variation.min_qty);
    }
    if (typeof variation.max_qty !== 'undefined' && variation.max_qty !== '') {
      $qty.attr('max', variation.max_qty);
      var current = parseInt($qty.val(), 10) || 1;
      var max = parseInt(variation.max_qty, 10);
      if (!isNaN(max) && max > 0 && current > max) {
        $qty.val(max);
      }
    }
    if (typeof variation.step !== 'undefined' && variation.step !== '') {
      $qty.attr('step', variation.step);
    }
  }

  function updateMetaPanel($form, variation) {
    var $meta = $form.find('[data-sft-size-meta]');
    var $prompt = $meta.find('.sft-pdp-size__prompt');
    var $stock = $meta.find('[data-sft-size-stock]');
    var $sku = $meta.find('[data-sft-size-sku]');

    if (!$meta.length) {
      return;
    }

    if (!variation) {
      $prompt.prop('hidden', false);
      $stock.prop('hidden', true).text('').removeClass('is-oos is-ok');
      $sku.prop('hidden', true).text('');
      return;
    }

    $prompt.prop('hidden', true);

    var stockText = variation.safestore_stock_html || '';
    if (!stockText && variation.is_in_stock) {
      stockText = i18n.inStock || 'In stock for this size';
    }
    if (!variation.is_in_stock) {
      stockText = variation.safestore_stock_html || i18n.outOfStock || 'Out of stock for this size';
    }

    $stock
      .text(stockText)
      .prop('hidden', !stockText)
      .toggleClass('is-oos', !variation.is_in_stock)
      .toggleClass('is-ok', !!variation.is_in_stock);

    var skuText = variation.safestore_sku_html || '';
    if (!skuText && variation.sku) {
      skuText = (i18n.skuPrefix || 'SKU: %s').replace('%s', variation.sku);
    }
    if (!skuText && variation.safestore_sku) {
      skuText = (i18n.skuPrefix || 'SKU: %s').replace('%s', variation.safestore_sku);
    }
    $sku.text(skuText).prop('hidden', !skuText);
  }

  function hasValidSelection($form) {
    var variationId = $form.find('input[name="variation_id"]').val();
    var sizeVal = getSizeSelect($form).val();
    return !!(sizeVal && variationId && variationId !== '0');
  }

  function initSwatches() {
    var $form = getForm();
    if (!$form.length) {
      return;
    }

    // Start gated until a purchasable size is chosen.
    setActionsEnabled($form, false);
    updateMetaPanel($form, null);

    $form.on('click', '.sft-size-swatch', function (e) {
      e.preventDefault();
      var $btn = $(this);
      if ($btn.is(':disabled') || $btn.hasClass('is-oos')) {
        return;
      }

      var value = String($btn.data('value'));
      var $wrap = $btn.closest('.sft-size-swatches');
      var $select = getSizeSelect($form);

      if (!$select.length) {
        return;
      }

      // Toggle off if clicking the same selected size.
      if (String($select.val()) === value) {
        $select.val('').trigger('change');
        syncSwatchState($wrap, '');
        return;
      }

      $select.val(value).trigger('change');
      syncSwatchState($wrap, value);
    });

    $form.on('woocommerce_update_variation_values check_variations', function () {
      var $select = getSizeSelect($form);
      var $wrap = $form.find('.sft-size-swatches').first();
      if ($select.length && $wrap.length) {
        syncSwatchState($wrap, $select.val());
      }
      if (!hasValidSelection($form)) {
        setActionsEnabled($form, false);
      }
    });

    $form.on('found_variation', function (event, variation) {
      var purchasable = !!(
        variation &&
        variation.is_purchasable &&
        variation.is_in_stock &&
        variation.variation_id
      );

      updateQtyLimits($form, variation);
      updateMetaPanel($form, variation);
      setActionsEnabled($form, purchasable);

      var $wrap = $form.find('.sft-size-swatches').first();
      if (variation && variation.safestore_size) {
        syncSwatchState($wrap, variation.safestore_size);
      } else {
        syncSwatchState($wrap, getSizeSelect($form).val());
      }
    });

    $form.on('reset_data hide_variation', function () {
      var $wrap = $form.find('.sft-size-swatches').first();
      syncSwatchState($wrap, '');
      updateMetaPanel($form, null);
      setActionsEnabled($form, false);
    });

    $form.on('click', '.single_add_to_cart_button, button.sft-pdp-buy-now', function (e) {
      if (hasValidSelection($form)) {
        return;
      }
      e.preventDefault();
      e.stopImmediatePropagation();
      $form.find('.sft-pdp-size').addClass('is-invalid');
      window.alert(i18n.selectSize || 'Please choose a size.');
      $form.find('.sft-size-swatch:not(:disabled):not(.is-oos)').first().trigger('focus');
    });

    $form.on('change', 'select[name="attribute_pa_size"], select.sft-size-swatches__select', function () {
      $form.find('.sft-pdp-size').removeClass('is-invalid');
    });

    // Ensure cart payload includes attribute_pa_size + variation_id on native submit.
    $form.on('submit', function (e) {
      if (!hasValidSelection($form)) {
        e.preventDefault();
        $form.find('.sft-pdp-size').addClass('is-invalid');
        window.alert(i18n.selectSize || 'Please choose a size.');
        return;
      }

      // Hidden selects are fine; keep attribute fields enabled for POST.
      getSizeSelect($form).prop('disabled', false);
    });
  }

  function initSizeMatrix() {
    var $matrix = $('.sft-size-matrix');
    if (!$matrix.length) {
      return;
    }

    $matrix.on('click', '.sft-size-matrix__submit', function () {
      var $btn = $(this);
      var items = [];

      $matrix.find('.sft-size-matrix__qty').each(function () {
        var $input = $(this);
        var qty = parseInt($input.val(), 10) || 0;
        var variationId = parseInt($input.data('variation-id'), 10) || 0;
        var max = parseInt($input.attr('max'), 10);
        if (!isNaN(max) && max >= 0 && qty > max) {
          qty = max;
          $input.val(max);
        }
        if (variationId && qty > 0) {
          items.push({
            variation_id: variationId,
            quantity: qty
          });
        }
      });

      if (!items.length) {
        window.alert(i18n.noQty || 'Enter a quantity for at least one size.');
        return;
      }

      var original = $btn.text();
      $btn.prop('disabled', true).text(i18n.adding || 'Adding…');

      $.ajax({
        url: cfg.ajaxUrl,
        method: 'POST',
        dataType: 'json',
        data: {
          action: 'safestore_add_sizes',
          nonce: cfg.nonce,
          items: items
        }
      })
        .done(function (response) {
          if (!response || !response.success) {
            var msg =
              response && response.data && response.data.message
                ? response.data.message
                : 'Could not add sizes.';
            window.alert(msg);
            return;
          }

          if (response.data && response.data.fragments) {
            $.each(response.data.fragments, function (selector, html) {
              $(selector).replaceWith(html);
            });
          }

          $(document.body).trigger('wc_fragment_refresh');
          $(document.body).trigger('added_to_cart', [
            response.data.fragments || {},
            response.data.cart_hash || '',
            $btn
          ]);

          $matrix.find('.sft-size-matrix__qty').val(0);
          window.alert(response.data.message || 'Added to cart.');
        })
        .fail(function (xhr) {
          var msg = 'Could not add sizes.';
          if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
            msg = xhr.responseJSON.data.message;
          }
          window.alert(msg);
        })
        .always(function () {
          $btn.prop('disabled', false).text(original);
        });
    });
  }

  $(function () {
    initSwatches();
    initSizeMatrix();
  });
})(jQuery);

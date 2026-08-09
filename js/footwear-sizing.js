/**
 * Footwear sizing — size swatches + multi-size cart add.
 */
(function ($) {
  'use strict';

  var cfg = window.safestoreFootwear || {};
  var i18n = cfg.i18n || {};

  function syncSwatchState($wrap, value) {
    $wrap.find('.sft-size-swatch').each(function () {
      var $btn = $(this);
      var selected = String($btn.data('value')) === String(value);
      $btn.toggleClass('is-selected', selected);
      $btn.attr('aria-selected', selected ? 'true' : 'false');
    });
  }

  function updateQtyLimits(variation) {
    var $qty = $('form.variations_form .quantity input.qty');
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

  function updateStockHint(variation) {
    var $form = $('form.variations_form');
    var $hint = $form.find('.sft-size-stock');
    if (!$hint.length) {
      $hint = $('<p class="sft-size-stock" aria-live="polite"></p>');
      $form.find('.sft-size-swatches__hint').after($hint);
    }

    if (!variation || !variation.is_in_stock) {
      $hint.addClass('is-oos').text(i18n.selectSize || 'Please choose a size.');
      return;
    }

    if (variation.safestore_stock_html) {
      $hint.removeClass('is-oos').text(variation.safestore_stock_html);
    } else if (typeof variation.safestore_stock_qty === 'number') {
      var template = i18n.stockHint || '%d in stock for this size';
      $hint.removeClass('is-oos').text(template.replace('%d', String(variation.safestore_stock_qty)));
    } else {
      $hint.removeClass('is-oos').text('');
    }
  }

  function initSwatches() {
    var $form = $('form.variations_form');
    if (!$form.length) {
      return;
    }

    $form.on('click', '.sft-size-swatch', function (e) {
      e.preventDefault();
      var $btn = $(this);
      if ($btn.is(':disabled') || $btn.hasClass('is-oos')) {
        return;
      }

      var value = String($btn.data('value'));
      var $wrap = $btn.closest('.sft-size-swatches');
      var $select = $wrap.nextAll('select.sft-size-swatches__select').first();
      if (!$select.length) {
        $select = $wrap.siblings('select.sft-size-swatches__select').first();
      }
      if (!$select.length) {
        $select = $form.find('select[name="attribute_pa_size"]');
      }

      $select.val(value).trigger('change');
      syncSwatchState($wrap, value);
    });

    $form.on('woocommerce_update_variation_values', function () {
      var $select = $form.find('select[name="attribute_pa_size"], select.sft-size-swatches__select').first();
      var $wrap = $form.find('.sft-size-swatches').first();
      if ($select.length && $wrap.length) {
        syncSwatchState($wrap, $select.val());
      }
    });

    $form.on('found_variation', function (event, variation) {
      updateQtyLimits(variation);
      updateStockHint(variation);
    });

    $form.on('reset_data', function () {
      var $wrap = $form.find('.sft-size-swatches').first();
      syncSwatchState($wrap, '');
      $form.find('.sft-size-stock').text('');
    });

    // Validate size before native submit / Buy now (non-AJAX fallback).
    $form.on('submit', function (e) {
      var $select = $form.find('select[name="attribute_pa_size"], select.sft-size-swatches__select').first();
      if ($select.length && !$select.val()) {
        e.preventDefault();
        window.alert(i18n.selectSize || 'Please choose a size.');
      }
    });

    $form.on('click', 'button.sft-pdp-buy-now', function (e) {
      var $select = $form.find('select[name="attribute_pa_size"], select.sft-size-swatches__select').first();
      var variationId = $form.find('input[name="variation_id"]').val();
      if (($select.length && !$select.val()) || !variationId || variationId === '0') {
        e.preventDefault();
        window.alert(i18n.selectSize || 'Please choose a size.');
      }
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

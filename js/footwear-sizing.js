/**
 * Custom size swatches for WooCommerce variable products (Safety Shoes).
 *
 * 1) Hide the native Size dropdown (kept in DOM for order processing)
 * 2) Build clickable swatches from that dropdown's options
 * 3) Sync selection back into the hidden dropdown
 * 4) Block Add to Cart / Buy now until a size is selected
 * 5) Styled via css/footwear-sizing.css
 */
(function ($) {
  'use strict';

  var cfg = window.safestoreFootwear || {};
  var i18n = cfg.i18n || {};
  var ALERT_MSG = i18n.selectSize || 'Please select a size first';

  function getForm() {
    return $(
      'form.variations_form.sft-variations-form--footwear, form.variations_form[data-footwear="1"]'
    ).first();
  }

  function getSizeSelect($form) {
    var $select = $form.find('select.sft-size-dropdown').first();
    if ($select.length) {
      return $select;
    }
    return $form
      .find('select[name="attribute_pa_size"], select[data-attribute_name="attribute_pa_size"]')
      .first();
  }

  function hasSizeSelected($form) {
    var $select = getSizeSelect($form);
    return !!( $select.length && $select.val() );
  }

  function setActiveSwatch($panel, value) {
    $panel.find('.sft-size-swatch').each(function () {
      var $btn = $(this);
      var on = String($btn.data('value')) === String(value);
      $btn.toggleClass('is-selected active', on);
      $btn.attr('aria-selected', on ? 'true' : 'false');
    });
  }

  function clearError($panel) {
    $panel.removeClass('is-invalid sft-size-error');
  }

  function showError($panel) {
    $panel.addClass('is-invalid sft-size-error');
    window.alert(ALERT_MSG);
    $panel.find('.sft-size-swatch:not(:disabled)').first().trigger('focus');
  }

  /**
   * Step 2: read options from the hidden dropdown and build swatch buttons.
   */
  function buildSwatches($form) {
    var $select = getSizeSelect($form);
    if (!$select.length) {
      return null;
    }

    // Place / move host directly above the Add to Cart controls.
    var $host = $form.find('[data-sft-size-host]').first();
    if (!$host.length) {
      $host = $('<div class="sft-size-swatches-host" data-sft-size-host></div>');
    }

    var $atcRow = $form.find('.woocommerce-variation-add-to-cart').first();
    if ($atcRow.length) {
      $atcRow.before($host);
    } else {
      var $atcBtn = $form.find('.single_add_to_cart_button').first();
      if ($atcBtn.length) {
        $atcBtn.before($host);
      } else {
        $form.append($host);
      }
    }

    $host.empty().removeAttr('hidden').show();

    var $panel = $(
      '<div class="sft-size-swatches-panel" data-sft-size-panel>' +
        '<div class="sft-size-swatches-panel__header">' +
          '<span class="sft-size-swatches-panel__label">Size</span>' +
          '<span class="sft-size-swatches-panel__hint">Select a size</span>' +
        '</div>' +
        '<div class="sft-size-swatches" role="listbox" aria-label="Select size"></div>' +
      '</div>'
    );

    var $list = $panel.find('.sft-size-swatches');
    var selected = String($select.val() || '');

    $select.find('option').each(function () {
      var $opt = $(this);
      var value = String($opt.attr('value') || '');
      if (!value) {
        return; // skip placeholder "Choose an option"
      }

      var label = $.trim($opt.text()) || value;
      var disabled = !!$opt.prop('disabled');
      var $btn = $('<button type="button" class="sft-size-swatch" role="option"></button>');
      $btn.text(label);
      $btn.attr('data-value', value);
      $btn.attr('aria-selected', value === selected ? 'true' : 'false');

      if (disabled) {
        $btn.addClass('is-oos').prop('disabled', true).attr('aria-disabled', 'true');
      }
      if (value === selected) {
        $btn.addClass('is-selected active');
      }

      $list.append($btn);
    });

    $host.append($panel);

    // Step 3: click → highlight + update hidden dropdown for WooCommerce.
    $list.on('click', '.sft-size-swatch', function (e) {
      e.preventDefault();
      var $btn = $(this);
      if ($btn.is(':disabled') || $btn.hasClass('is-oos')) {
        return;
      }

      var value = String($btn.data('value'));
      clearError($panel);

      if (String($select.val()) === value) {
        // Allow deselect.
        $select.val('').trigger('change');
        setActiveSwatch($panel, '');
        return;
      }

      $select.val(value).trigger('change');
      setActiveSwatch($panel, value);
    });

    // Keep swatches in sync if WooCommerce resets the form.
    $form.on('woocommerce_update_variation_values reset_data hide_variation', function () {
      setActiveSwatch($panel, String($select.val() || ''));
      if (!hasSizeSelected($form)) {
        clearError($panel);
      }
    });

    $form.on('found_variation', function () {
      setActiveSwatch($panel, String($select.val() || ''));
      clearError($panel);
    });

    return $panel;
  }

  /**
   * Step 4: mandatory validation on Add to Cart / Buy now.
   */
  function bindValidation($form, $panel) {
    function guard(e) {
      if (hasSizeSelected($form)) {
        clearError($panel);
        return;
      }
      e.preventDefault();
      e.stopImmediatePropagation();
      showError($panel);
    }

    $form.on('click', '.single_add_to_cart_button, button.sft-pdp-buy-now, a.sft-pdp-buy-now', guard);
    $form.on('submit', function (e) {
      if (hasSizeSelected($form)) {
        return;
      }
      e.preventDefault();
      showError($panel);
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
          items.push({ variation_id: variationId, quantity: qty });
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
            window.alert(
              (response && response.data && response.data.message) || 'Could not add sizes.'
            );
            return;
          }
          if (response.data && response.data.fragments) {
            $.each(response.data.fragments, function (selector, html) {
              $(selector).replaceWith(html);
            });
          }
          $(document.body).trigger('wc_fragment_refresh');
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
    var $form = getForm();
    if ($form.length) {
      var $panel = buildSwatches($form);
      if ($panel && $panel.length) {
        bindValidation($form, $panel);
      }
    }
    initSizeMatrix();
  });
})(jQuery);

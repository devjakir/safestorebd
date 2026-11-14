/**
 * Color swatches (inc/color-swatches.php).
 *
 * The chips are rendered server-side; this keeps them and WooCommerce's hidden
 * Color <select> in step: chip click → select value + change event, and any
 * WooCommerce update (Clear, cross-attribute disabling) → chip state.
 * Keyboard: a radiogroup with roving tabindex — arrows move and select.
 */
(function ($) {
  'use strict';

  function initGroup($form, $wrap) {
    if ($wrap.data('sftSwatchInit')) {
      return;
    }
    $wrap.data('sftSwatchInit', true);

    var selectId = String($wrap.attr('data-select') || '');
    var $select = selectId ? $form.find('select').filter(function () { return this.id === selectId; }) : $();
    if (!$select.length) {
      $select = $wrap.siblings('select').first();
    }
    if (!$select.length) {
      return;
    }

    var $current = $wrap.find('[data-sft-swatch-current]');
    var placeholder = $wrap.attr('data-placeholder') || '';

    function swatches() {
      return $wrap.find('.sft-swatch');
    }

    function optionFor(value) {
      return $select.find('option').filter(function () { return this.value === value; });
    }

    function sync() {
      var val = String($select.val() || '');
      var name = '';
      var $all = swatches();

      $all.each(function () {
        var $btn = $(this);
        var value = String($btn.attr('data-value'));
        var $opt = optionFor(value);
        // Server-side sold out, or an option WooCommerce removed/disabled for this combination.
        var off = $btn.attr('data-oos') === '1' || !$opt.length || $opt.prop('disabled');
        var on = !off && value === val;

        $btn.toggleClass('is-oos', !!off).prop('disabled', !!off).attr('aria-disabled', off ? 'true' : 'false');
        $btn.toggleClass('is-selected', on).attr('aria-checked', on ? 'true' : 'false');
        if (on) {
          name = $btn.attr('data-name') || $btn.text();
        }
      });

      // Roving tabindex: the selected chip, else the first usable one.
      var $focusable = $all.filter('.is-selected');
      if (!$focusable.length) {
        $focusable = $all.not(':disabled').first();
      }
      $all.attr('tabindex', '-1');
      $focusable.attr('tabindex', '0');

      $current.text(name || placeholder).toggleClass('is-empty', !name);
    }

    function choose($btn) {
      if (!$btn.length || $btn.is(':disabled')) {
        return;
      }
      var value = String($btn.attr('data-value'));
      if (String($select.val() || '') !== value) {
        $select.val(value).trigger('change');
      }
      sync();
    }

    $wrap.on('click', '.sft-swatch', function (e) {
      e.preventDefault();
      choose($(this));
    });

    $wrap.on('keydown', '.sft-swatch', function (e) {
      var key = e.key;
      var step = (key === 'ArrowRight' || key === 'ArrowDown') ? 1 : (key === 'ArrowLeft' || key === 'ArrowUp') ? -1 : 0;
      if (key === ' ' || key === 'Enter') {
        e.preventDefault();
        choose($(this));
        return;
      }
      if (!step) {
        return;
      }
      e.preventDefault();
      var $usable = swatches().not(':disabled');
      var i = $usable.index(this);
      var $next = $usable.eq((i + step + $usable.length) % $usable.length);
      choose($next);
      $next.trigger('focus');
    });

    $select.on('change', sync);
    $form.on('woocommerce_update_variation_values reset_data', function () {
      // WooCommerce rewrites options after its own handlers run.
      window.setTimeout(sync, 0);
    });

    sync();
  }

  function init(form) {
    var $form = $(form);
    $form.find('[data-sft-swatches]').each(function () {
      initGroup($form, $(this));
    });
  }

  $(function () {
    $('form.variations_form').each(function () {
      init(this);
    });
  });

  // Variation forms initialised later (quick view, AJAX-loaded content).
  $(document.body).on('wc_variation_form', 'form.variations_form', function () {
    init(this);
  });
})(jQuery);

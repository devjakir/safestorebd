/**
 * SafeStoreBD — Multi-email contact chooser.
 *
 * Markup is a native <details> disclosure (works without JS). This script adds:
 *   - one open chooser at a time
 *   - close on outside click / Escape
 *   - arrow-key focus between mailto options
 *   - drop-up vs drop-down based on available viewport space
 */
(function () {
  'use strict';

  if (window.__sftEmailChooser) {
    return;
  }
  window.__sftEmailChooser = true;

  var ROOT_SEL = '[data-sft-email-chooser]';
  var OPTION_SEL = '.sft-contact-email-chooser__option';

  /**
   * @param {HTMLDetailsElement} root
   * @return {HTMLElement[]}
   */
  function optionsOf(root) {
    return Array.prototype.slice.call(root.querySelectorAll(OPTION_SEL));
  }

  /**
   * Keep aria-expanded in sync for AT that does not expose <details> state.
   *
   * @param {HTMLDetailsElement} root
   */
  function syncExpanded(root) {
    var trigger = root && root.querySelector('.sft-contact-email-chooser__trigger');
    if (trigger) {
      trigger.setAttribute('aria-expanded', root.open ? 'true' : 'false');
    }
  }

  /**
   * @param {HTMLDetailsElement} root
   * @param {boolean} open
   */
  function setOpen(root, open) {
    if (!root) {
      return;
    }
    root.open = !!open;
    syncExpanded(root);
    if (open) {
      placePanel(root);
    }
  }

  /**
   * Flip the panel above or below the trigger so it stays in view.
   *
   * @param {HTMLDetailsElement} root
   */
  function placePanel(root) {
    var panel = root.querySelector('.sft-contact-email-chooser__panel');
    var trigger = root.querySelector('.sft-contact-email-chooser__trigger');
    if (!panel || !trigger) {
      return;
    }

    root.classList.remove('is-drop-down');
    // Measure after a frame so the open panel has layout.
    window.requestAnimationFrame(function () {
      var triggerRect = trigger.getBoundingClientRect();
      var panelHeight = panel.offsetHeight || 0;
      var spaceAbove = triggerRect.top;
      var spaceBelow = window.innerHeight - triggerRect.bottom;
      var preferDown = spaceAbove < panelHeight + 12 && spaceBelow > spaceAbove;
      root.classList.toggle('is-drop-down', preferDown);
    });
  }

  /**
   * @param {HTMLDetailsElement} except
   */
  function closeOthers(except) {
    var all = document.querySelectorAll(ROOT_SEL);
    for (var i = 0; i < all.length; i++) {
      if (all[i] !== except && all[i].open) {
        all[i].open = false;
      }
    }
  }

  /**
   * @param {HTMLDetailsElement} root
   * @param {number} index
   */
  function focusOption(root, index) {
    var items = optionsOf(root);
    if (!items.length) {
      return;
    }
    var next = (index + items.length) % items.length;
    items[next].focus();
  }

  document.addEventListener('toggle', function (event) {
    var root = event.target;
    if (!root || !root.matches || !root.matches(ROOT_SEL)) {
      return;
    }
    syncExpanded(root);
    if (root.open) {
      closeOthers(root);
      placePanel(root);
    }
  }, true);

  // Initialise aria-expanded on every chooser present at load.
  document.querySelectorAll(ROOT_SEL).forEach(function (root) {
    syncExpanded(root);
  });

  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!target || !target.closest) {
      return;
    }

    var option = target.closest(OPTION_SEL);
    if (option) {
      var chooser = option.closest(ROOT_SEL);
      // Allow the mailto: navigation; close the menu for a clean return.
      if (chooser) {
        window.setTimeout(function () {
          setOpen(chooser, false);
        }, 0);
      }
      return;
    }

    var open = document.querySelector(ROOT_SEL + '[open]');
    if (open && !target.closest(ROOT_SEL)) {
      setOpen(open, false);
    }
  });

  document.addEventListener('keydown', function (event) {
    var open = document.querySelector(ROOT_SEL + '[open]');
    if (!open) {
      return;
    }

    var key = event.key;
    if (key === 'Escape') {
      event.preventDefault();
      setOpen(open, false);
      var trigger = open.querySelector('.sft-contact-email-chooser__trigger');
      if (trigger) {
        trigger.focus();
      }
      return;
    }

    var items = optionsOf(open);
    if (!items.length) {
      return;
    }

    var active = document.activeElement;
    var index = items.indexOf(active);
    var onTrigger = !!(active && active.closest && active.closest('.sft-contact-email-chooser__trigger'));

    if (key === 'ArrowDown') {
      event.preventDefault();
      focusOption(open, onTrigger || index < 0 ? 0 : index + 1);
      return;
    }

    if (key === 'ArrowUp') {
      event.preventDefault();
      focusOption(open, onTrigger || index < 0 ? items.length - 1 : index - 1);
      return;
    }

    if (key === 'Home') {
      event.preventDefault();
      focusOption(open, 0);
      return;
    }

    if (key === 'End') {
      event.preventDefault();
      focusOption(open, items.length - 1);
    }
  });

  window.addEventListener('resize', function () {
    var open = document.querySelector(ROOT_SEL + '[open]');
    if (open) {
      placePanel(open);
    }
  });
})();

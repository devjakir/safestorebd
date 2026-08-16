/**
 * SafeStoreBD — Site-wide copy to clipboard.
 *
 * Two ways a value becomes copyable:
 *
 *   1. Explicit — any element carrying `data-sft-copy="<value>"` is handled by
 *      the delegated click listener below. The shared contact component
 *      (safestore_contact_item / safestore_contact_line) renders its buttons
 *      this way, so the footer and the homepage support bar are covered on
 *      every page.
 *   2. Automatic — plain `tel:` / `mailto:` / `wa.me` links anywhere in the
 *      page content (policy pages, contact page, careers, …) get a small copy
 *      button appended after them, so a contact detail is copyable site-wide
 *      without touching every template.
 *
 * Progressive enhancement: without JS the links keep working exactly as before.
 * Uses the async Clipboard API where available and falls back to a hidden
 * textarea + execCommand on insecure origins (plain-HTTP staging, older
 * in-app browsers).
 *
 * Vanilla JS, no dependencies.
 */
(function () {
  'use strict';

  if (window.__sftCopyClipboard) {
    return;
  }
  window.__sftCopyClipboard = true;

  var CFG = window.sftCopy || {};
  var I18N = CFG.i18n || {};

  // How long the confirmation bubble stays visible before reverting.
  var FEEDBACK_MS = 2400;

  var COPY_ICON =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
    '<rect x="9" y="9" width="11" height="11" rx="2"/>' +
    '<path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>';

  var DONE_ICON =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
    '<path d="M20 6 9 17l-5-5"/></svg>';

  /**
   * Translated string with an English fallback.
   *
   * @param {string} key
   * @param {string} fallback
   * @return {string}
   */
  function t(key, fallback) {
    return typeof I18N[key] === 'string' && I18N[key] !== '' ? I18N[key] : fallback;
  }

  function each(list, fn) {
    for (var i = 0; i < list.length; i++) {
      fn(list[i], i);
    }
  }

  /* ---------------------------------------------------------------------
   * Clipboard write
   * ------------------------------------------------------------------ */

  /**
   * Legacy copy path for insecure contexts, where navigator.clipboard is
   * undefined. Restores the user's existing selection afterwards.
   *
   * @param {string} text
   * @return {Promise}
   */
  function legacyWrite(text) {
    return new Promise(function (resolve, reject) {
      var field = document.createElement('textarea');
      field.value = text;
      field.setAttribute('readonly', '');
      field.setAttribute('aria-hidden', 'true');
      field.style.position = 'fixed';
      field.style.top = '0';
      field.style.left = '-9999px';
      field.style.opacity = '0';
      document.body.appendChild(field);

      var selection = document.getSelection();
      var previous = selection && selection.rangeCount > 0 ? selection.getRangeAt(0) : null;

      field.select();
      if (field.setSelectionRange) {
        field.setSelectionRange(0, field.value.length);
      }

      var ok = false;
      try {
        ok = document.execCommand('copy');
      } catch (err) {
        ok = false;
      }

      document.body.removeChild(field);

      if (previous && selection) {
        selection.removeAllRanges();
        selection.addRange(previous);
      }

      if (ok) {
        resolve();
      } else {
        reject(new Error('sft-copy-failed'));
      }
    });
  }

  /**
   * @param {string} text
   * @return {Promise}
   */
  function writeText(text) {
    if (navigator.clipboard && window.isSecureContext && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(text).catch(function () {
        // Permission denied / not focused — the legacy path often still works.
        return legacyWrite(text);
      });
    }
    return legacyWrite(text);
  }

  /* ---------------------------------------------------------------------
   * Feedback — button state + one shared live region
   * ------------------------------------------------------------------ */

  /**
   * The polite live region rendered in the footer. Created on demand so the
   * script also works if the markup hook is filtered off.
   *
   * @return {HTMLElement}
   */
  function liveRegion() {
    var node = document.getElementById('sft-copy-live');
    if (!node) {
      node = document.createElement('div');
      node.id = 'sft-copy-live';
      node.className = 'sft-copy-live';
      node.setAttribute('role', 'status');
      node.setAttribute('aria-live', 'polite');
      node.setAttribute('aria-atomic', 'true');
      document.body.appendChild(node);
    }
    return node;
  }

  function announce(message) {
    var node = liveRegion();
    // Clearing first makes screen readers re-announce an identical message.
    node.textContent = '';
    window.setTimeout(function () {
      node.textContent = message;
    }, 60);
  }

  /**
   * Restore a trigger after its confirmation bubble is dismissed.
   *
   * @param {HTMLElement} trigger
   */
  function clearFlash(trigger) {
    if (!trigger) {
      return;
    }
    if (trigger.__sftTimer) {
      window.clearTimeout(trigger.__sftTimer);
      trigger.__sftTimer = null;
    }
    trigger.classList.remove('is-copied', 'is-copy-failed');
    var label = trigger.getAttribute('data-sft-copy-title');
    if (label) {
      trigger.setAttribute('title', label);
      trigger.setAttribute('aria-label', label);
    }
  }

  /**
   * Flip a trigger into its transient "Copied" / "Press Ctrl+C" state.
   *
   * The visible bubble uses a short confirmation. The live region keeps the
   * longer “{value} copied” wording for screen readers. The native `title`
   * attribute is removed for the duration — updating it on tap made mobile
   * browsers flash their own tooltip and dismiss it as soon as the gesture
   * ended, which looked like the confirmation disappearing immediately.
   *
   * @param {HTMLElement} trigger
   * @param {boolean}     ok
   * @param {string}      message
   */
  function flash(trigger, ok, message) {
    var stateClass = ok ? 'is-copied' : 'is-copy-failed';
    var visual = ok ? t('copied', 'Copied!') : message;
    var others = document.querySelectorAll('.sft-copy-btn.is-copied, .sft-copy-btn.is-copy-failed');

    each(others, function (btn) {
      if (btn !== trigger) {
        clearFlash(btn);
      }
    });

    if (trigger.__sftTimer) {
      window.clearTimeout(trigger.__sftTimer);
      trigger.__sftTimer = null;
    }

    var flashNode = trigger.querySelector('.sft-copy-btn__flash');
    if (flashNode) {
      flashNode.textContent = visual;
    }
    trigger.classList.add(stateClass);
    trigger.removeAttribute('title');
    trigger.setAttribute('aria-label', visual);

    trigger.__sftTimer = window.setTimeout(function () {
      clearFlash(trigger);
    }, FEEDBACK_MS);

    announce(message);
  }

  /* ---------------------------------------------------------------------
   * Copy trigger handling (event delegation — works for markup added later)
   * ------------------------------------------------------------------ */

  /**
   * Value a trigger should place on the clipboard. Falls back to the text of
   * the element referenced by `data-sft-copy-target` when the attribute is
   * empty, so a value rendered elsewhere can be copied without duplicating it.
   *
   * @param {HTMLElement} trigger
   * @return {string}
   */
  function valueFor(trigger) {
    var value = trigger.getAttribute('data-sft-copy') || '';
    if (value !== '') {
      return value;
    }

    var selector = trigger.getAttribute('data-sft-copy-target');
    var target = selector ? document.querySelector(selector) : null;

    return target ? (target.textContent || '').trim() : '';
  }

  function handleTrigger(trigger) {
    var value = valueFor(trigger);
    if (value === '' || trigger.__sftCopying) {
      return;
    }

    trigger.__sftCopying = true;
    writeText(value).then(
      function () {
        trigger.__sftCopying = false;
        flash(trigger, true, trigger.getAttribute('data-sft-copy-done') || t('copied', 'Copied!'));
      },
      function () {
        trigger.__sftCopying = false;
        flash(trigger, false, t('failed', 'Press Ctrl+C to copy'));
      }
    );
  }

  document.addEventListener('click', function (event) {
    var trigger = event.target && event.target.closest ? event.target.closest('[data-sft-copy]') : null;
    if (!trigger) {
      return;
    }

    // Buttons only ever copy; on a non-button trigger keep modified clicks
    // (new tab, save) doing what the browser would normally do.
    if (trigger.tagName !== 'BUTTON' && (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    handleTrigger(trigger);
  });

  // Capture-phase pointerdown:
  //  1. Strip `title` before the browser can show its native tooltip on tap.
  //  2. Dismiss an open confirmation when the user interacts elsewhere.
  document.addEventListener('pointerdown', function (event) {
    var target = event.target;
    var trigger = target && target.closest ? target.closest('[data-sft-copy]') : null;

    if (trigger) {
      trigger.removeAttribute('title');
    }

    var active = document.querySelector('.sft-copy-btn.is-copied, .sft-copy-btn.is-copy-failed');
    if (active && active !== trigger) {
      clearFlash(active);
    }
  }, true);

  function restoreTitleIfIdle(event) {
    var trigger = event.target && event.target.closest ? event.target.closest('[data-sft-copy]') : null;
    if (!trigger || trigger.__sftCopying || trigger.classList.contains('is-copied') || trigger.classList.contains('is-copy-failed')) {
      return;
    }
    var label = trigger.getAttribute('data-sft-copy-title');
    if (label) {
      trigger.setAttribute('title', label);
    }
  }

  document.addEventListener('pointerup', restoreTitleIfIdle);
  document.addEventListener('pointercancel', restoreTitleIfIdle);

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') {
      return;
    }
    var active = document.querySelector('.sft-copy-btn.is-copied, .sft-copy-btn.is-copy-failed');
    if (active) {
      clearFlash(active);
    }
  });

  /* ---------------------------------------------------------------------
   * Automatic enhancement of plain contact links
   * ------------------------------------------------------------------ */

  var AUTO_SELECTOR = 'a[href^="tel:"], a[href^="mailto:"], a[href*="wa.me/"], a[href*="api.whatsapp.com/send"]';

  /**
   * Copyable value + accessible wording for a contact link.
   *
   * @param {HTMLAnchorElement} link
   * @return {{value: string, noun: string}|null}
   */
  function describeLink(link) {
    var href = link.getAttribute('href') || '';

    if (href.indexOf('mailto:') === 0) {
      // Drop any ?subject= / ?body= parameters — only the address is useful.
      var address = href.slice(7).split('?')[0];
      try {
        address = decodeURIComponent(address);
      } catch (err) {
        /* keep the raw value */
      }
      address = address.trim();
      return address ? { value: address, noun: t('nounEmail', 'Email address') } : null;
    }

    if (href.indexOf('tel:') === 0) {
      var phone = href.slice(4).replace(/[^\d+]/g, '');
      return phone ? { value: phone, noun: t('nounPhone', 'Phone number') } : null;
    }

    // wa.me/<digits> and api.whatsapp.com/send?phone=<digits>
    var wa = href.match(/wa\.me\/(\d+)/) || href.match(/[?&]phone=(\d+)/);
    if (wa && wa[1]) {
      return { value: '+' + wa[1], noun: t('nounWhatsapp', 'WhatsApp number') };
    }

    return null;
  }

  /**
   * Build a copy button for a value.
   *
   * @param {string} value
   * @param {string} noun    e.g. "Email address" — used for the accessible name.
   * @param {string} variant Extra modifier class.
   * @return {HTMLButtonElement}
   */
  function buildButton(value, noun, variant) {
    var copyLabel = t('copy', 'Copy %s').replace('%s', noun.toLowerCase());
    var doneLabel = t('copiedNoun', '%s copied').replace('%s', noun);

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'sft-copy-btn' + (variant ? ' ' + variant : '');
    btn.setAttribute('data-sft-copy', value);
    btn.setAttribute('data-sft-copy-done', doneLabel);
    btn.setAttribute('data-sft-copy-title', copyLabel);
    btn.setAttribute('aria-label', copyLabel);
    btn.setAttribute('title', copyLabel);
    btn.innerHTML =
      '<span class="sft-copy-btn__icon sft-copy-btn__icon--copy" aria-hidden="true">' + COPY_ICON + '</span>' +
      '<span class="sft-copy-btn__icon sft-copy-btn__icon--done" aria-hidden="true">' + DONE_ICON + '</span>' +
      '<span class="sft-copy-btn__flash" aria-hidden="true"></span>';

    return btn;
  }

  /**
   * Append a copy button after every plain contact link in `root` that does
   * not already have one.
   *
   * @param {ParentNode} root
   */
  function enhance(root) {
    var links = root.querySelectorAll ? root.querySelectorAll(AUTO_SELECTOR) : [];

    each(links, function (link) {
      if (link.getAttribute('data-sft-copy-init')) {
        return;
      }
      // The shared contact component ships its own server-rendered button, and
      // the floating WhatsApp widget is a launcher rather than a contact detail.
      // PDP Need help? units ship their own paired copy control — never
      // append a sibling that would break the flex/grid layout.
      if (link.closest('.sft-contact-item, .sft-wa, .sft-pdp-contact, .sft-copy-skip, [data-sft-copy-skip]')) {
        link.setAttribute('data-sft-copy-init', '1');
        return;
      }

      var info = describeLink(link);
      if (!info) {
        return;
      }

      link.setAttribute('data-sft-copy-init', '1');
      var btn = buildButton(info.value, info.noun, 'sft-copy-btn--inline');

      if (link.parentNode) {
        link.parentNode.insertBefore(btn, link.nextSibling);
      }
    });
  }

  /**
   * Watch for markup injected after load (WooCommerce fragments, AJAX
   * templates) so late contact links get the same treatment.
   */
  function observe() {
    if (!window.MutationObserver) {
      return;
    }

    var pending = null;
    var observer = new MutationObserver(function (records) {
      var relevant = false;
      each(records, function (record) {
        each(record.addedNodes, function (node) {
          if (node.nodeType === 1) {
            relevant = true;
          }
        });
      });

      if (!relevant || pending) {
        return;
      }
      pending = window.setTimeout(function () {
        pending = null;
        enhance(document.body);
      }, 200);
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  function init() {
    enhance(document.body);
    observe();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Small public surface so other theme scripts can reuse the same plumbing.
  window.sftCopyToClipboard = {
    copy: writeText,
    enhance: enhance,
    button: buildButton
  };
})();

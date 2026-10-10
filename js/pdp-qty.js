// PDP quantity − / + buttons; respects the input's min, max and step.
(function () {
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.sft-qty-btn');
    if (!btn) {
      return;
    }

    var input = btn.parentNode.querySelector('input.qty');
    if (!input || input.disabled || input.readOnly) {
      return;
    }

    var step = parseFloat(input.step) || 1;
    var min = input.min !== '' ? parseFloat(input.min) : 1;
    var max = input.max !== '' ? parseFloat(input.max) : Infinity;
    var value = parseFloat(input.value) || 0;

    value += btn.classList.contains('sft-qty-btn--plus') ? step : -step;
    value = Math.min(Math.max(value, min), max);

    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });
})();

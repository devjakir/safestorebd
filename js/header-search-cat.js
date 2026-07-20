(function () {
	var root = document.querySelector('[data-sft-search-cat]');
	if (!root) return;

	var trigger = root.querySelector('.sft-search-cat__trigger');
	var panel = root.querySelector('.sft-search-cat__panel');
	var hidden = root.querySelector('input[name="product_cat"]');
	var valueEl = root.querySelector('.sft-search-cat__value');
	var options = Array.from(root.querySelectorAll('.sft-search-cat__option'));
	if (!trigger || !panel || !hidden || !valueEl || options.length === 0) return;

	function isOpen() {
		return root.classList.contains('is-open');
	}

	function open() {
		panel.hidden = false;
		trigger.setAttribute('aria-expanded', 'true');
		root.classList.add('is-open');
		var current = options.find(function (o) {
			return o.classList.contains('is-selected');
		});
		(current || options[0]).focus();
	}

	function close() {
		panel.hidden = true;
		trigger.setAttribute('aria-expanded', 'false');
		root.classList.remove('is-open');
		trigger.focus();
	}

	function toggle() {
		if (isOpen()) close();
		else open();
	}

	function selectOption(btn) {
		var slug = btn.getAttribute('data-value') || '';
		var label = btn.querySelector('.sft-search-cat__option-label');
		var text = label ? label.textContent.trim() : btn.textContent.trim();
		hidden.value = slug;
		valueEl.textContent = text;
		options.forEach(function (o) {
			var sel = o === btn;
			o.classList.toggle('is-selected', sel);
			o.setAttribute('aria-selected', sel ? 'true' : 'false');
		});
		close();
	}

	function focusSibling(from, delta) {
		var i = options.indexOf(from);
		if (i < 0) return;
		var next = options[i + delta];
		if (next) next.focus();
	}

	trigger.addEventListener('click', function (e) {
		e.preventDefault();
		e.stopPropagation();
		toggle();
	});

	trigger.addEventListener('keydown', function (e) {
		if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
		e.preventDefault();
		if (!isOpen()) {
			open();
			return;
		}
		if (options.indexOf(document.activeElement) < 0) {
			(e.key === 'ArrowDown' ? options[0] : options[options.length - 1]).focus();
			return;
		}
		focusSibling(document.activeElement, e.key === 'ArrowDown' ? 1 : -1);
	});

	options.forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.stopPropagation();
			selectOption(btn);
		});
		btn.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				e.preventDefault();
				close();
				return;
			}
			if (e.key === 'ArrowDown') {
				e.preventDefault();
				focusSibling(btn, 1);
				return;
			}
			if (e.key === 'ArrowUp') {
				e.preventDefault();
				focusSibling(btn, -1);
				return;
			}
			if (e.key === 'Home') {
				e.preventDefault();
				options[0].focus();
				return;
			}
			if (e.key === 'End') {
				e.preventDefault();
				options[options.length - 1].focus();
			}
		});
	});

	document.addEventListener('click', function (e) {
		if (!isOpen()) return;
		if (!root.contains(e.target)) close();
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && isOpen()) close();
	});
})();

// Mobile menu design JS 
document.addEventListener('DOMContentLoaded', () => {
  // Select mobile structure elements securely
  const triggerBtn = document.querySelector('.mobile-menu-trigger');
  const closeBtn = document.querySelector('.mobile-menu-close');
  const slidePanel = document.getElementById('offcanvas-menu');
  const screenOverlay = document.getElementById('menu-overlay');

  // Open/Close Drawer Toggle Function
  function handleMenuToggle() {
    const isCurrentlyOpen = slidePanel.classList.toggle('is-active');
    screenOverlay.classList.toggle('is-active');
    triggerBtn.classList.toggle('is-open', isCurrentlyOpen);
    triggerBtn.setAttribute('aria-expanded', isCurrentlyOpen);

    // Prevent the background page from scrolling behind the menu overlay
    document.body.style.overflow = isCurrentlyOpen ? 'hidden' : '';

    // Return focus to the trigger when closing (a11y)
    if (!isCurrentlyOpen) triggerBtn.focus({ preventScroll: true });
  }

  if (triggerBtn && closeBtn && screenOverlay && slidePanel) {
    triggerBtn.addEventListener('click', handleMenuToggle);
    closeBtn.addEventListener('click', handleMenuToggle);
    screenOverlay.addEventListener('click', handleMenuToggle);

    // Close the drawer with the Escape key (a11y / desktop-class UX)
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && slidePanel.classList.contains('is-active')) {
        handleMenuToggle();
      }
    });
  }

  // --- Scoped Multi-Level Mobile Accordion Engine ---
  // Targets menu items with children strictly inside the off-canvas panel container wrapper
  const mobileParentItems = document.querySelectorAll('#offcanvas-menu .menu-item-has-children');

  mobileParentItems.forEach(item => {
    // 1. Create a clean toggle trigger button component
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'dropdown-toggle-btn';
    toggleBtn.setAttribute('aria-label', 'Toggle Child Submenu');
    toggleBtn.setAttribute('aria-expanded', 'false');

    // 2. Insert the toggle button next to the category link anchor node
    item.appendChild(toggleBtn);

    // 3. Bind click events to the arrow trigger
    toggleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation(); // Stops clicks from firing parent redirect URLs

      const isOpen = item.classList.toggle('sub-menu-open');
      toggleBtn.setAttribute('aria-expanded', isOpen);

      // Close neighboring sibling items on the exact same nested level
      const rowSiblings = item.parentElement.children;
      for (let sibling of rowSiblings) {
        if (sibling !== item && sibling.classList.contains('sub-menu-open')) {
          sibling.classList.remove('sub-menu-open');
          const siblingBtn = sibling.querySelector('.dropdown-toggle-btn');
          if (siblingBtn) siblingBtn.setAttribute('aria-expanded', 'false');
        }
      }
    });
  });
});

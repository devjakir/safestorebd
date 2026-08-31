(function () {
	const slider = document.querySelector('.hero-slider');
	if (!slider) return;

	const slides = Array.from(slider.querySelectorAll('.hero-slide'));
	const dots = Array.from(slider.querySelectorAll('.hero-dot'));
	if (slides.length < 2) return;

	const AUTOPLAY_MS = 6000;
	let current = 0;
	let timer = null;

	function hydrateImage(slide) {
		const img = slide.querySelector('img.hero-slide-product[data-src]');
		if (!img) return;
		const src = img.getAttribute('data-src');
		if (!src) return;
		img.src = src;
		const srcset = img.getAttribute('data-srcset');
		const sizes = img.getAttribute('data-sizes');
		if (srcset) img.srcset = srcset;
		if (sizes) img.sizes = sizes;
		img.removeAttribute('data-src');
		img.removeAttribute('data-srcset');
		img.removeAttribute('data-sizes');
	}

	function prefetchNeighbors(index) {
		const next = slides[(index + 1) % slides.length];
		const prev = slides[(index - 1 + slides.length) % slides.length];
		if (next) hydrateImage(next);
		if (prev) hydrateImage(prev);
	}

	function go(index) {
		current = (index + slides.length) % slides.length;
		hydrateImage(slides[current]);
		slides.forEach(function (slide, i) {
			const active = i === current;
			slide.classList.toggle('is-active', active);
			slide.setAttribute('aria-hidden', active ? 'false' : 'true');
		});
		dots.forEach(function (dot, i) {
			const active = i === current;
			dot.classList.toggle('is-active', active);
			// aria-current: these are buttons in a group, not tabs.
			if (active) {
				dot.setAttribute('aria-current', 'true');
			} else {
				dot.removeAttribute('aria-current');
			}
		});
		prefetchNeighbors(current);
	}

	function next() { go(current + 1); }

	function start() {
		stop();
		timer = window.setInterval(next, AUTOPLAY_MS);
	}
	function stop() {
		if (timer) {
			window.clearInterval(timer);
			timer = null;
		}
	}

	dots.forEach(function (dot) {
		dot.addEventListener('click', function () {
			go(parseInt(dot.dataset.slide, 10) || 0);
			start();
		});
	});

	slider.addEventListener('mouseenter', stop);
	slider.addEventListener('mouseleave', start);
	slider.addEventListener('focusin', stop);
	slider.addEventListener('focusout', start);

	document.addEventListener('visibilitychange', function () {
		if (document.hidden) stop(); else start();
	});

	// Arrow-key navigation across the slide-picker buttons.
	const dotList = slider.querySelector('.hero-slider-dots');
	if (dotList) {
		dotList.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
				e.preventDefault();
				go(current + (e.key === 'ArrowRight' ? 1 : -1));
				const activeDot = dots[current];
				if (activeDot) activeDot.focus();
			}
		});
	}

	// Warm the next slide after first paint so autoplay does not hitch.
	const warm = function () {
		prefetchNeighbors(0);
	};
	if ('requestIdleCallback' in window) {
		window.requestIdleCallback(warm, { timeout: 2500 });
	} else {
		window.setTimeout(warm, 1200);
	}

	// Respect the user's reduced-motion preference: no autoplay, manual nav still works
	const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (!prefersReducedMotion) {
		start();
	}
})();

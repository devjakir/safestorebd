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

	// ------------------------------------------------------------------
	// Touch / pen / mouse-drag swipe.
	//
	// Slides cross-fade from an absolutely-positioned stack, so the slide
	// cannot follow the finger without rebuilding the slider as a translate
	// track. The gesture therefore commits to next/prev and lets the existing
	// fade run.
	//
	// Vertical scrolling is protected two ways: the viewport declares
	// `touch-action: pan-y`, so the browser keeps owning vertical panning and
	// these listeners can stay passive (no preventDefault, no scroll jank);
	// and the gesture direction-locks on the first ~10px of movement, so a
	// mostly-vertical drag never navigates.
	// ------------------------------------------------------------------
	const viewport = slider.querySelector('.hero-slider-viewport') || slider;

	const LOCK_PX = 10;      // movement before we decide the axis
	const DISTANCE_PX = 45;  // committed swipe distance
	const VELOCITY = 0.35;   // px/ms — a fast flick wins on a shorter distance

	let tracking = false;
	let axisDecided = false;
	let isHorizontal = false;
	let startX = 0;
	let startY = 0;
	let startTime = 0;
	let lastX = 0;
	let suppressClick = false;

	function pointFrom(e) {
		if (e.touches && e.touches.length) return e.touches[0];
		if (e.changedTouches && e.changedTouches.length) return e.changedTouches[0];
		return e;
	}

	function dragStart(e) {
		// Ignore multi-touch (pinch/zoom) and secondary mouse buttons.
		if (e.touches && e.touches.length > 1) return;
		if (e.pointerType === 'mouse' && e.button !== 0) return;

		const p = pointFrom(e);
		tracking = true;
		axisDecided = false;
		isHorizontal = false;
		startX = lastX = p.clientX;
		startY = p.clientY;
		startTime = Date.now();
		stop();
	}

	function dragMove(e) {
		if (!tracking) return;
		const p = pointFrom(e);
		const dx = p.clientX - startX;
		const dy = p.clientY - startY;
		lastX = p.clientX;

		if (!axisDecided && (Math.abs(dx) > LOCK_PX || Math.abs(dy) > LOCK_PX)) {
			axisDecided = true;
			isHorizontal = Math.abs(dx) > Math.abs(dy);
			// A vertical drag is the user scrolling the page — let go of it.
			if (!isHorizontal) tracking = false;
		}
	}

	function dragEnd() {
		if (!tracking) return;
		tracking = false;

		const dx = lastX - startX;
		const elapsed = Math.max(1, Date.now() - startTime);
		const speed = Math.abs(dx) / elapsed;
		const committed = isHorizontal && (Math.abs(dx) >= DISTANCE_PX || speed >= VELOCITY);

		if (committed) {
			// Swipe left => next slide, swipe right => previous.
			go(current + (dx < 0 ? 1 : -1));
			// The drag started on a slide that is usually one big link —
			// stop the browser turning this gesture into a navigation.
			suppressClick = true;
			window.setTimeout(function () { suppressClick = false; }, 0);
		}

		start();
	}

	function dragCancel() {
		if (!tracking) return;
		tracking = false;
		start();
	}

	if (window.PointerEvent) {
		viewport.addEventListener('pointerdown', dragStart, { passive: true });
		viewport.addEventListener('pointermove', dragMove, { passive: true });
		viewport.addEventListener('pointerup', dragEnd, { passive: true });
		viewport.addEventListener('pointercancel', dragCancel, { passive: true });
		viewport.addEventListener('pointerleave', dragCancel, { passive: true });
	} else {
		viewport.addEventListener('touchstart', dragStart, { passive: true });
		viewport.addEventListener('touchmove', dragMove, { passive: true });
		viewport.addEventListener('touchend', dragEnd, { passive: true });
		viewport.addEventListener('touchcancel', dragCancel, { passive: true });
	}

	// Swallow the click that follows a committed swipe, so swiping over a CTA
	// never opens the product page.
	viewport.addEventListener('click', function (e) {
		if (!suppressClick) return;
		e.preventDefault();
		e.stopPropagation();
	}, true);

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

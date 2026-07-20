(function () {
	const slider = document.querySelector('.hero-slider');
	if (!slider) return;

	const slides = Array.from(slider.querySelectorAll('.hero-slide'));
	const dots = Array.from(slider.querySelectorAll('.hero-dot'));
	if (slides.length < 2) return;

	const AUTOPLAY_MS = 6000;
	let current = 0;
	let timer = null;

	function go(index) {
		current = (index + slides.length) % slides.length;
		slides.forEach(function (slide, i) {
			const active = i === current;
			slide.classList.toggle('is-active', active);
			slide.setAttribute('aria-hidden', active ? 'false' : 'true');
		});
		dots.forEach(function (dot, i) {
			const active = i === current;
			dot.classList.toggle('is-active', active);
			dot.setAttribute('aria-selected', active ? 'true' : 'false');
		});
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

	// Keyboard navigation on the dot tablist (WAI-ARIA carousel pattern)
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

	// Respect the user's reduced-motion preference: no autoplay, manual nav still works
	const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (!prefersReducedMotion) {
		start();
	}
})();

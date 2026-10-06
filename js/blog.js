/**
 * SafeStoreBD — blog article enhancements (single post only).
 *   • Reading progress bar under the header.
 *   • "Copy link" button: native share sheet on phones, clipboard elsewhere.
 */
(function () {
	'use strict';

	/* Reading progress ------------------------------------------------- */
	var bar = document.querySelector('.sft-blog-progress span');
	var article = document.querySelector('.sft-blog-article');

	if (bar && article) {
		var ticking = false;

		var update = function () {
			ticking = false;
			var rect = article.getBoundingClientRect();
			var total = rect.height - window.innerHeight;
			var done = total > 0 ? Math.min(1, Math.max(0, -rect.top / total)) : 1;
			bar.style.transform = 'scaleX(' + done.toFixed(4) + ')';
		};

		var onScroll = function () {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(update);
			}
		};

		window.addEventListener('scroll', onScroll, { passive: true });
		window.addEventListener('resize', onScroll, { passive: true });
		update();
	}

	/* Copy / share link ------------------------------------------------ */
	var copyBtn = document.querySelector('[data-sft-copy-link]');
	if (!copyBtn) {
		return;
	}

	var label = copyBtn.textContent;

	var flash = function (text) {
		copyBtn.textContent = text;
		copyBtn.classList.add('is-done');
		window.setTimeout(function () {
			copyBtn.textContent = label;
			copyBtn.classList.remove('is-done');
		}, 2000);
	};

	var fallbackCopy = function (url) {
		var field = document.createElement('textarea');
		field.value = url;
		field.setAttribute('readonly', '');
		field.style.position = 'fixed';
		field.style.opacity = '0';
		document.body.appendChild(field);
		field.select();
		try {
			document.execCommand('copy');
		} catch (e) {}
		document.body.removeChild(field);
	};

	copyBtn.addEventListener('click', function () {
		var url = copyBtn.getAttribute('data-sft-copy-link');
		var done = copyBtn.getAttribute('data-sft-copied') || 'Copied';
		var coarse = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;

		if (coarse && navigator.share) {
			navigator.share({ title: document.title, url: url }).catch(function () {});
			return;
		}

		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(url).then(
				function () { flash(done); },
				function () { fallbackCopy(url); flash(done); }
			);
		} else {
			fallbackCopy(url);
			flash(done);
		}
	});
})();

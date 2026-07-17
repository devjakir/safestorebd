/**
 * SafeStore WhatsApp Chat widget.
 * Vanilla JS, no dependencies, loaded deferred. Progressive enhancement:
 * without JS the floating button is a plain wa.me link that still works.
 */
(function () {
	'use strict';

	var root = document.querySelector('.sft-wa');
	if (!root) {
		return;
	}

	var fab = root.querySelector('.sft-wa__fab');
	var panel = root.querySelector('.sft-wa__panel');
	var closeBtn = root.querySelector('.sft-wa__close');
	var statusEl = root.querySelector('.sft-wa__status');
	var offlineNote = root.querySelector('.sft-wa__offline-note');
	var mode = root.getAttribute('data-mode') || 'panel';

	var config = {};
	try {
		config = JSON.parse(root.getAttribute('data-config') || '{}');
	} catch (e) {
		config = {};
	}

	/* ------------------------------------------------------------------
	 * Online/offline status from business hours, evaluated in the
	 * business timezone so it is correct for every visitor worldwide.
	 * ---------------------------------------------------------------- */

	function businessNow() {
		var tz = config.timezone || 'Asia/Dhaka';

		// Raw UTC offset (e.g. "+06:00") — WP sites configured with a manual
		// offset instead of a named zone. Compute directly, since not every
		// engine accepts offsets as an Intl timeZone.
		var offsetMatch = /^([+-])(\d{2}):(\d{2})$/.exec(tz);
		if (offsetMatch) {
			var sign = offsetMatch[1] === '-' ? -1 : 1;
			var offsetMin = sign * (parseInt(offsetMatch[2], 10) * 60 + parseInt(offsetMatch[3], 10));
			var shifted = new Date(Date.now() + offsetMin * 60000);
			return { day: shifted.getUTCDay(), minutes: shifted.getUTCHours() * 60 + shifted.getUTCMinutes() };
		}

		try {
			var parts = new Intl.DateTimeFormat('en-US', {
				timeZone: tz,
				weekday: 'short',
				hour: '2-digit',
				minute: '2-digit',
				hour12: false
			}).formatToParts(new Date());
			var map = {};
			parts.forEach(function (p) { map[p.type] = p.value; });
			var dayIndex = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].indexOf(map.weekday);
			var hour = parseInt(map.hour, 10);
			if (hour === 24) { hour = 0; } // Some engines report midnight as 24 with hour12:false.
			return { day: dayIndex, minutes: hour * 60 + parseInt(map.minute, 10) };
		} catch (e) {
			var d = new Date();
			return { day: d.getDay(), minutes: d.getHours() * 60 + d.getMinutes() };
		}
	}

	function toMinutes(hhmm, fallback) {
		var m = /^(\d{1,2}):(\d{2})$/.exec(hhmm || '');
		if (!m) {
			return fallback;
		}
		return parseInt(m[1], 10) * 60 + parseInt(m[2], 10);
	}

	function isOnline() {
		if (config.alwaysOnline) {
			return true;
		}
		var now = businessNow();
		var closedDays = Array.isArray(config.closedDays) ? config.closedDays : [];
		if (now.day < 0 || closedDays.indexOf(now.day) !== -1) {
			return false;
		}
		var open = toMinutes(config.openTime, 9 * 60);
		var close = toMinutes(config.closeTime, 20 * 60);
		if (open === close) {
			return true; // Treat identical times as always open.
		}
		if (open < close) {
			return now.minutes >= open && now.minutes < close;
		}
		// Overnight schedule (e.g. 21:00–06:00).
		return now.minutes >= open || now.minutes < close;
	}

	function refreshStatus() {
		var online = isOnline();
		root.classList.toggle('is-online', online);
		if (statusEl) {
			statusEl.textContent = online
				? (statusEl.getAttribute('data-online-text') || 'Online now')
				: (statusEl.getAttribute('data-offline-text') || 'Offline');
		}
		if (offlineNote) {
			offlineNote.hidden = online;
		}
	}

	refreshStatus();
	window.setInterval(refreshStatus, 60000);

	/* ------------------------------------------------------------------
	 * Panel open/close behaviour.
	 * ---------------------------------------------------------------- */

	if (mode !== 'panel' || !panel || !fab) {
		return; // Direct mode: the FAB is a plain link.
	}

	var open = false;

	function setOpen(next, focusTarget) {
		open = next;
		panel.hidden = !open;
		root.classList.toggle('is-open', open);
		fab.setAttribute('aria-expanded', open ? 'true' : 'false');
		if (open) {
			refreshStatus();
			var target = panel.querySelector('.sft-wa__start');
			if (target) {
				target.focus({ preventScroll: true });
			}
		} else if (focusTarget !== false) {
			fab.focus({ preventScroll: true });
		}
	}

	fab.addEventListener('click', function (event) {
		event.preventDefault();
		setOpen(!open);
	});

	fab.addEventListener('keydown', function (event) {
		if (event.key === ' ' || event.key === 'Spacebar') {
			event.preventDefault();
			setOpen(!open);
		}
	});

	if (closeBtn) {
		closeBtn.addEventListener('click', function () {
			setOpen(false);
		});
	}

	document.addEventListener('keydown', function (event) {
		if (open && event.key === 'Escape') {
			setOpen(false);
		}
	});

	document.addEventListener('click', function (event) {
		if (open && !root.contains(event.target)) {
			setOpen(false, false);
		}
	});
})();

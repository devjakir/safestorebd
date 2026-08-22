/**
 * PDP side-by-side image zoom (Daraz / Amazon pattern).
 *
 * A lens follows the cursor over the main gallery image while a flyout panel
 * to the right paints the high-resolution crop under the lens.
 *
 * Design notes:
 * - Desktop only. Gated on (min-width: 992px) AND a fine hover pointer, so
 *   touch devices never pay for it and never get a stuck lens.
 * - The active image is resolved on every hover, not cached at init. That is
 *   what makes variation swaps and flexslider slide changes work with no extra
 *   wiring — whatever is on screen when the cursor arrives is what we zoom.
 * - Theme CSS puts `object-fit: cover` on gallery images (style.css), so the
 *   visible area is a centred crop of the source. Geometry below maps cursor
 *   position through that crop; skipping it would offset the magnified view.
 */
(function () {
  'use strict';

  var MIN_WIDTH = 992;   // keep in sync with css/pdp-zoom.css
  var TARGET_ZOOM = 2.15; // mid close-up — sharp flyout, readable select area
  var MIN_ZOOM = 1.8;
  var LENS_MAX_RATIO = 0.5; // select area ~half the photo, not a tiny crop

  // The panel is sized to the product-details column so it lands flush with the
  // layout instead of at an arbitrary width. FLYOUT_MIN is a *preferred* floor
  // applied where there is room; FLYOUT_FLOOR is the hard cut-off below which
  // zooming is skipped entirely rather than rendered clipped.
  var FLYOUT_GAP = 24;      // fallback offset when the summary column is missing
  var FLYOUT_MIN = 520;
  var FLYOUT_FLOOR = 320;
  var VIEWPORT_MARGIN = 16; // never let the panel touch the window edge

  var desktopQuery = window.matchMedia('(min-width: ' + MIN_WIDTH + 'px)');
  var pointerQuery = window.matchMedia('(hover: hover) and (pointer: fine)');

  var media = null;    // .sft-pdp__col--media (positioning context for flyout)
  var summary = null;  // .sft-pdp__col--summary (the panel's size template)
  var gallery = null;  // .woocommerce-product-gallery (positioning context for lens)
  var lens = null;
  var flyout = null;
  var enabled = false;

  var session = null;  // { img, url, geo } while the cursor is over an image
  var pointer = { x: 0, y: 0 };
  var frame = 0;
  var cache = {};      // large-image url -> { ready, ok, w, h, waiting[] }

  /* ----------------------------------------------------------------------
     Helpers
     ---------------------------------------------------------------------- */

  function clamp(value, min, max) {
    if (max < min) {
      return min;
    }
    return value < min ? min : (value > max ? max : value);
  }

  function activeImage() {
    if (!gallery) {
      return null;
    }
    var slide = gallery.querySelector('.woocommerce-product-gallery__image.flex-active-slide') ||
      gallery.querySelector('.woocommerce-product-gallery__image');

    return slide ? slide.querySelector('img') : null;
  }

  /**
   * Element the lens is positioned against. Clipping to the image stage rather
   * than the gallery root keeps the lens — and its dimming ring — off the
   * thumbnail strip, which is a sibling inside the same gallery box.
   * Flexslider wraps the gallery on init, so this is resolved per hover.
   */
  function currentStage() {
    return gallery.querySelector('.flex-viewport') ||
      gallery.querySelector('.woocommerce-product-gallery__wrapper') ||
      gallery;
  }

  /**
   * Highest-resolution source for the image currently on screen.
   * WooCommerce keeps this on data-large_image and refreshes it on variation
   * change; the gallery anchor href is the fallback for older markup.
   */
  function largeSource(img) {
    var link = img.closest('a');

    return img.getAttribute('data-large_image') ||
      (link ? link.getAttribute('href') : '') ||
      img.currentSrc ||
      img.getAttribute('src') ||
      '';
  }

  function naturalSize(img, loaded) {
    var w = parseInt(img.getAttribute('data-large_image_width'), 10);
    var h = parseInt(img.getAttribute('data-large_image_height'), 10);

    if (!w || !h) {
      w = loaded.w;
      h = loaded.h;
    }

    return { w: w || 0, h: h || 0 };
  }

  function preload(url, done) {
    var entry = cache[url];

    if (entry && entry.ready) {
      done(entry);
      return;
    }

    if (!entry) {
      entry = cache[url] = { ready: false, ok: false, w: 0, h: 0, waiting: [] };

      var probe = new Image();

      probe.onload = function () {
        entry.ready = true;
        entry.ok = true;
        entry.w = probe.naturalWidth;
        entry.h = probe.naturalHeight;
        flush(entry);
      };

      probe.onerror = function () {
        entry.ready = true;
        entry.ok = false;
        flush(entry);
      };

      probe.src = url;
    }

    entry.waiting.push(done);
  }

  function flush(entry) {
    var waiting = entry.waiting.splice(0);

    for (var i = 0; i < waiting.length; i++) {
      waiting[i](entry);
    }
  }

  /**
   * Where the source image actually paints inside its box, given object-fit.
   * Returns box-relative coordinates; for `cover` the rect is larger than the
   * box (overflow is cropped), for `contain` it is smaller (letterboxed).
   */
  function paintedRect(boxW, boxH, natW, natH, fit) {
    if (!natW || !natH || fit === 'fill') {
      return { x: 0, y: 0, w: boxW, h: boxH };
    }

    var scale = fit === 'contain' || fit === 'scale-down' ?
      Math.min(boxW / natW, boxH / natH) :
      Math.max(boxW / natW, boxH / natH);

    var w = natW * scale;
    var h = natH * scale;

    return { x: (boxW - w) / 2, y: (boxH - h) / 2, w: w, h: h };
  }

  /* ----------------------------------------------------------------------
     Layer construction
     ---------------------------------------------------------------------- */

  function build() {
    if (lens) {
      return;
    }

    lens = document.createElement('div');
    lens.className = 'sft-zoom-lens';
    lens.setAttribute('aria-hidden', 'true');
    gallery.appendChild(lens);

    flyout = document.createElement('div');
    flyout.className = 'sft-zoom-flyout';
    flyout.setAttribute('aria-hidden', 'true');
    media.appendChild(flyout);
  }

  /* ----------------------------------------------------------------------
     Geometry
     ---------------------------------------------------------------------- */

  function measure(img, natural) {
    var imgRect = img.getBoundingClientRect();

    var boxW = imgRect.width;
    var boxH = imgRect.height;

    if (boxW < 2 || boxH < 2) {
      return null;
    }

    var stage = currentStage();

    if (lens.parentNode !== stage) {
      stage.appendChild(lens);
    }

    var fit = window.getComputedStyle(img).objectFit || 'fill';
    var painted = paintedRect(boxW, boxH, natural.w, natural.h, fit);

    // Panel placement. Measured off the product-details column rather than
    // hard-coded, so the panel spans exactly that section at any container
    // width and stays aligned if the hero grid ratio ever changes.
    var mediaRect = media.getBoundingClientRect();
    var summaryRect = summary ? summary.getBoundingClientRect() : null;

    var left;
    var width;

    // Guard against a stacked layout (summary below media), where matching the
    // column would put the panel underneath the image.
    if (summaryRect && summaryRect.width > 1 && summaryRect.left > mediaRect.left) {
      left = summaryRect.left - mediaRect.left;
      width = summaryRect.width;
    } else {
      left = mediaRect.width + FLYOUT_GAP;
      width = Math.max(boxW, FLYOUT_MIN);
    }

    // Grow to the preferred minimum, then hold the panel inside the window.
    // Order matters: the window clamp has to win, or a narrow desktop gets a
    // panel running off-screen.
    var room = document.documentElement.clientWidth - VIEWPORT_MARGIN - (mediaRect.left + left);

    width = Math.min(Math.max(width, FLYOUT_MIN), room);

    var flyoutW = Math.round(width);
    var flyoutH = Math.round(boxH);

    if (flyoutW < FLYOUT_FLOOR) {
      return null;
    }

    flyout.style.left = Math.round(left) + 'px';
    flyout.style.top = Math.round(imgRect.top - mediaRect.top) + 'px';
    flyout.style.width = flyoutW + 'px';
    flyout.style.height = flyoutH + 'px';

    // Visible slice of the painted image: the box intersected with the paint.
    var areaX = Math.max(0, painted.x);
    var areaY = Math.max(0, painted.y);
    var areaW = Math.min(boxW, painted.x + painted.w) - areaX;
    var areaH = Math.min(boxH, painted.y + painted.h) - areaY;

    if (areaW < 2 || areaH < 2) {
      return null;
    }

    // Stay near native resolution so the flyout does not pixelate, then
    // keep the lens around half the photo — not a postage-stamp crop.
    var resolutionCap = painted.w > 0 ? natural.w / painted.w : TARGET_ZOOM;
    var zoom = Math.min(TARGET_ZOOM, Math.max(MIN_ZOOM, resolutionCap));

    zoom = Math.max(zoom, flyoutW / areaW, flyoutH / areaH);

    var compact = Math.max(
      flyoutW / (areaW * LENS_MAX_RATIO),
      flyoutH / (areaH * LENS_MAX_RATIO)
    );
    zoom = Math.min(TARGET_ZOOM, Math.max(zoom, compact));
    zoom = Math.max(zoom, flyoutW / areaW, flyoutH / areaH);

    var lensW = flyoutW / zoom;
    var lensH = flyoutH / zoom;

    // Travel limits, resolved once per measure so apply() only clamps.
    // Upper bounds are the image box (0 .. boxW - lensW) intersected with the
    // painted area, which matters when object-fit letterboxes the source.
    var minX = Math.max(0, areaX);
    var minY = Math.max(0, areaY);
    var maxX = Math.min(boxW - lensW, areaX + areaW - lensW);
    var maxY = Math.min(boxH - lensH, areaY + areaH - lensH);

    lens.style.width = lensW + 'px';
    lens.style.height = lensH + 'px';

    flyout.style.backgroundSize = (painted.w * zoom) + 'px ' + (painted.h * zoom) + 'px';

    return {
      img: img,
      stage: stage,
      natural: natural,
      painted: painted,
      zoom: zoom,
      boxW: boxW,
      boxH: boxH,
      lensW: lensW,
      lensH: lensH,
      minX: minX,
      minY: minY,
      maxX: maxX < minX ? minX : maxX,
      maxY: maxY < minY ? minY : maxY
    };
  }

  function apply() {
    frame = 0;

    if (!session || !session.geo) {
      return;
    }

    var geo = session.geo;

    // Re-read on every paint: the media column is sticky, so scrolling moves
    // the image under a stationary cursor.
    var imgRect = geo.img.getBoundingClientRect();

    // The gallery settles a frame after flexslider init (pdp-gallery.js locks
    // the stage height on rAF) and variation swaps can resize it. Clamping
    // against bounds measured before that is what let the lens walk past the
    // image edge, so stale geometry is rebuilt rather than trusted.
    if (Math.abs(imgRect.width - geo.boxW) > 1 || Math.abs(imgRect.height - geo.boxH) > 1) {
      geo = measure(geo.img, geo.natural);

      if (!geo) {
        stop();
        return;
      }

      session.geo = geo;
      imgRect = geo.img.getBoundingClientRect();
    }

    var stageRect = geo.stage.getBoundingClientRect();

    var cursorX = pointer.x - imgRect.left;
    var cursorY = pointer.y - imgRect.top;

    var lensX = clamp(cursorX - geo.lensW / 2, geo.minX, geo.maxX);
    var lensY = clamp(cursorY - geo.lensH / 2, geo.minY, geo.maxY);

    lens.style.transform = 'translate3d(' +
      (lensX + (imgRect.left - stageRect.left)) + 'px,' +
      (lensY + (imgRect.top - stageRect.top)) + 'px,0)';

    // Lens top-left, expressed in the magnified image's coordinate space.
    var bgX = (lensX - geo.painted.x) * geo.zoom;
    var bgY = (lensY - geo.painted.y) * geo.zoom;

    flyout.style.backgroundPosition = (-bgX) + 'px ' + (-bgY) + 'px';
  }

  function schedule() {
    if (!frame) {
      frame = window.requestAnimationFrame(apply);
    }
  }

  /* ----------------------------------------------------------------------
     Session lifecycle
     ---------------------------------------------------------------------- */

  function start(img) {
    var url = largeSource(img);

    if (!url || img.classList.contains('woocommerce-placeholder')) {
      return;
    }

    session = { img: img, url: url, geo: null };

    preload(url, function (entry) {
      // Bail if the cursor left, or moved to another slide, while loading.
      if (!session || session.img !== img || session.url !== url || !entry.ok) {
        return;
      }

      var geo = measure(img, naturalSize(img, entry));

      if (!geo) {
        return;
      }

      session.geo = geo;
      flyout.style.backgroundImage = 'url("' + url.replace(/"/g, '\\"') + '")';

      media.classList.add('sft-zoom-on');
      lens.classList.add('is-active');
      flyout.classList.add('is-active');

      apply();
    });
  }

  function stop() {
    if (!session) {
      return;
    }

    session = null;

    if (frame) {
      window.cancelAnimationFrame(frame);
      frame = 0;
    }

    media.classList.remove('sft-zoom-on');
    lens.classList.remove('is-active');
    flyout.classList.remove('is-active');
  }

  /* ----------------------------------------------------------------------
     Events
     ---------------------------------------------------------------------- */

  function onPointerMove(event) {
    if (event.pointerType && event.pointerType !== 'mouse') {
      stop();
      return;
    }

    var slide = event.target.closest ?
      event.target.closest('.woocommerce-product-gallery__image') :
      null;

    if (!slide) {
      stop();
      return;
    }

    var img = slide.querySelector('img');

    if (!img) {
      stop();
      return;
    }

    pointer.x = event.clientX;
    pointer.y = event.clientY;

    if (!session || session.img !== img) {
      stop();
      start(img);
      return;
    }

    schedule();
  }

  function onPointerLeave() {
    stop();
  }

  function onScroll() {
    if (session && session.geo) {
      schedule();
    }
  }

  function onResize() {
    // Sizes are cached per session; drop it and let the next move rebuild.
    stop();
  }

  function onKeyDown(event) {
    if (event.key === 'Escape') {
      stop();
    }
  }

  function enable() {
    if (enabled || !media || !gallery) {
      return;
    }

    build();
    enabled = true;

    gallery.addEventListener('pointermove', onPointerMove);
    gallery.addEventListener('pointerleave', onPointerLeave);
    // The lightbox takes over on click — get out of its way.
    gallery.addEventListener('click', onPointerLeave);
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onResize);
    document.addEventListener('keydown', onKeyDown);
  }

  function disable() {
    if (!enabled) {
      return;
    }

    stop();
    enabled = false;

    gallery.removeEventListener('pointermove', onPointerMove);
    gallery.removeEventListener('pointerleave', onPointerLeave);
    gallery.removeEventListener('click', onPointerLeave);
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('resize', onResize);
    document.removeEventListener('keydown', onKeyDown);
  }

  function sync() {
    if (desktopQuery.matches && pointerQuery.matches) {
      enable();
    } else {
      disable();
    }
  }

  function listen(query, handler) {
    if (query.addEventListener) {
      query.addEventListener('change', handler);
    } else if (query.addListener) {
      query.addListener(handler);
    }
  }

  /* ----------------------------------------------------------------------
     Boot
     ---------------------------------------------------------------------- */

  function init() {
    media = document.querySelector('.sft-pdp__col--media');
    summary = document.querySelector('.sft-pdp__col--summary');
    gallery = media ? media.querySelector('.woocommerce-product-gallery') : null;

    if (!media || !gallery) {
      return;
    }

    sync();
    listen(desktopQuery, sync);
    listen(pointerQuery, sync);

    // Gallery height is locked one frame after flexslider init (pdp-gallery.js),
    // and variation swaps replace the image; either way the next hover
    // re-measures, so we only need to drop any stale session.
    if (typeof window.jQuery !== 'undefined') {
      window.jQuery(document.body).on(
        'wc-product-gallery-after-init woocommerce_gallery_init_zoom found_variation reset_data',
        stop
      );
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

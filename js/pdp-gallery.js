/**
 * Keep PDP main image in a consistent square frame (WooCommerce flexslider sets inline heights).
 */
(function () {
  function targetSize(gallery) {
    return Math.round(gallery.getBoundingClientRect().width);
  }

  function lockGallery() {
    var gallery = document.querySelector('.sft-pdp .woocommerce-product-gallery');
    if (!gallery) {
      return;
    }

    var size = targetSize(gallery);
    var viewport = gallery.querySelector('.flex-viewport');
    var wrapper = gallery.querySelector('.woocommerce-product-gallery__wrapper');
    var stage = viewport || wrapper;

    if (!stage) {
      return;
    }

    stage.style.setProperty('width', '100%', 'important');
    stage.style.setProperty('height', size + 'px', 'important');
    stage.style.setProperty('max-height', size + 'px', 'important');

    gallery.querySelectorAll('.woocommerce-product-gallery__image').forEach(function (slide) {
      slide.style.height = '100%';
    });
  }

  function scheduleLock() {
    window.requestAnimationFrame(lockGallery);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scheduleLock);
  } else {
    scheduleLock();
  }

  window.addEventListener('resize', scheduleLock);

  if (typeof jQuery !== 'undefined') {
    jQuery(document.body).on('woocommerce_gallery_init_zoom wc-product-gallery-after-init', scheduleLock);
  }
})();

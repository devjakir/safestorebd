/**
 * Cart page: quantity − / + buttons and automatic "Update cart".
 *
 * WooCommerce's own cart.js does the real work: it enables "Update cart" when
 * a quantity changes and, on click, posts the form by AJAX and swaps in the new
 * table and totals. Here a quantity change simply presses that button for the
 * shopper after a short pause, so a changed quantity can never be left
 * un-applied before "Proceed to checkout". Without JavaScript the plain
 * "Update cart" button still works (it is only hidden by the sft-cart-auto
 * class this script adds).
 *
 * Markup: woocommerce/cart/cart.php + inc/cart-page.php. Styles: css/page-cart.css.
 */
( function ( $ ) {
	'use strict';

	if ( ! $ ) {
		return;
	}

	var FORM = '.woocommerce-cart-form';
	var TYPE_DELAY = 800; // ms after the last keystroke
	var STEP_DELAY = 450; // ms after the last − / + press (lets people tap a few times)

	var timer = null;
	var autoRun = false;

	document.documentElement.classList.add( 'sft-cart-auto' );

	function num( value, fallback ) {
		var n = parseFloat( value );
		return isNaN( n ) ? fallback : n;
	}

	// True when any quantity differs from what the server last rendered.
	function hasChanges( $form ) {
		var changed = false;
		$form.find( 'input.qty' ).each( function () {
			if ( this.type !== 'hidden' && String( this.value ) !== String( this.defaultValue ) ) {
				changed = true;
			}
		} );
		return changed;
	}

	function valid( $form ) {
		var ok = true;
		$form.find( 'input.qty' ).each( function () {
			if ( this.type === 'hidden' ) {
				return;
			}
			// Empty or half-typed values wait for the shopper to finish.
			if ( this.value === '' || isNaN( parseFloat( this.value ) ) || ( this.checkValidity && ! this.checkValidity() ) ) {
				ok = false;
			}
		} );
		return ok;
	}

	function runUpdate() {
		timer = null;
		var $form = $( FORM );
		if ( ! $form.length || ! hasChanges( $form ) || ! valid( $form ) ) {
			return;
		}
		// A request is already running (blockUI adds .processing): try again after it.
		if ( $form.hasClass( 'processing' ) ) {
			schedule( 300 );
			return;
		}
		patchScroll();
		autoRun = true;
		$form.find( ':input[name="update_cart"]' ).prop( 'disabled', false ).trigger( 'click' );
	}

	/*
	 * After an automatic update WooCommerce would print "Cart updated." and
	 * scroll the page up to it, which yanks the shopper away from the row they
	 * are editing. For automatic runs keep errors (e.g. a stock limit) exactly
	 * as WooCommerce shows them, but drop the plain success message and the
	 * scroll. Patched on first use: woocommerce.js defines $.scroll_to_notices
	 * on DOM ready, after this file has run.
	 */
	function patchScroll() {
		var original = $.scroll_to_notices;
		if ( typeof original !== 'function' || original.sftCart ) {
			return;
		}
		var patched = function () {
			if ( autoRun ) {
				autoRun = false;
				if ( ! $( '.woocommerce-cart .woocommerce-error' ).length ) {
					$( '.woocommerce-cart .woocommerce-notices-wrapper .woocommerce-message' ).remove();
					return;
				}
			}
			return original.apply( this, arguments );
		};
		patched.sftCart = true;
		$.scroll_to_notices = patched;
	}

	function schedule( delay ) {
		window.clearTimeout( timer );
		timer = window.setTimeout( runUpdate, delay );
	}

	// − / + buttons.
	$( document ).on( 'click', FORM + ' .sft-cart-qty-btn', function ( e ) {
		e.preventDefault();
		var input = $( this ).closest( '.quantity' ).find( 'input.qty' ).get( 0 );
		if ( ! input || input.disabled || input.readOnly || input.type === 'hidden' ) {
			return;
		}

		var step = num( input.step, 1 ) || 1;
		// The cart allows 0 (= remove), but the minus button stops at 1:
		// removing is the × button's job, never an accidental extra tap.
		var min = Math.max( num( input.min, 1 ), 1 );
		var max = input.max !== '' ? num( input.max, Infinity ) : Infinity;
		var plus = this.classList.contains( 'sft-cart-qty-btn--plus' );
		var next = Math.min( Math.max( num( input.value, min ) + ( plus ? step : -step ), min ), max );

		if ( String( next ) === String( input.value ) ) {
			return;
		}
		input.value = next;
		$( input ).trigger( 'change' ); // enables WooCommerce's Update button
		schedule( STEP_DELAY );
	} );

	// Typing in the box.
	$( document ).on( 'input', FORM + ' input.qty', function () {
		schedule( TYPE_DELAY );
	} );
	$( document ).on( 'change', FORM + ' input.qty', function ( e ) {
		if ( e.originalEvent ) { // real blur / spinner change, not our own trigger
			schedule( 150 );
		}
	} );

	/*
	 * "Proceed to checkout" pressed while a quantity change is still waiting
	 * (or saving): save it first, then continue, so checkout gets the new qty.
	 */
	var goTo = '';
	$( document ).on( 'click', '.wc-proceed-to-checkout .checkout-button', function ( e ) {
		var $form = $( FORM );
		if ( ! $form.length || ( ! hasChanges( $form ) && ! $form.hasClass( 'processing' ) ) ) {
			return;
		}
		if ( hasChanges( $form ) && ! valid( $form ) ) {
			return; // Let WooCommerce / the browser deal with the bad value.
		}
		e.preventDefault();
		goTo = this.href;
		if ( hasChanges( $form ) ) {
			window.clearTimeout( timer );
			runUpdate();
		}
	} );
	$( document.body ).on( 'updated_wc_div', function () {
		if ( ! goTo ) {
			return;
		}
		var href = goTo;
		goTo = '';
		// Stay on the cart if the update reported a problem (e.g. stock limit).
		if ( ! $( '.woocommerce-cart .woocommerce-error' ).length ) {
			window.location.href = href;
		}
	} );

	// Enter in a quantity box: WooCommerce updates at once; drop our pending run.
	$( document ).on( 'keypress', FORM + ' input.qty', function ( e ) {
		if ( e.which === 13 ) {
			window.clearTimeout( timer );
		}
	} );

} )( window.jQuery );

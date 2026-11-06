/**
 * Checkout: rebuild the Thana / Upazila select whenever the District changes.
 *
 * Data comes from inc/checkout-thana.php (window.sftThana). The District field
 * is a selectWoo widget, which fires jQuery "change" only, so the listeners are
 * jQuery ones, delegated from body because WooCommerce can re-render the field.
 */
( function ( $ ) {
	'use strict';

	if ( ! $ || ! window.sftThana ) {
		return;
	}

	var map = window.sftThana.map || {};
	var i18n = window.sftThana.i18n || {};

	function hasSelectWoo( $el ) {
		return !! $.fn.selectWoo && $el.hasClass( 'select2-hidden-accessible' );
	}

	function enhance( $city ) {
		if ( ! $.fn.selectWoo ) {
			return;
		}
		if ( hasSelectWoo( $city ) ) {
			$city.selectWoo( 'destroy' );
		}
		$city.selectWoo( {
			width: '100%',
			placeholder: $city.attr( 'data-placeholder' ) || '',
			// Short lists (most districts) need no search box; Dhaka's does.
			minimumResultsForSearch: 10,
		} );
	}

	function render( keepValue ) {
		var $city = $( '#billing_city' );
		if ( ! $city.length || ! $city.is( 'select' ) ) {
			return;
		}

		var district = $( '#billing_state' ).val() || '';
		var list = map[ district ] || [];
		var current = $city.val() || '';
		var placeholder = list.length ? i18n.choose : i18n.districtFirst;

		$city.empty().append( new Option( placeholder, '' ) );
		for ( var i = 0; i < list.length; i++ ) {
			$city.append( new Option( list[ i ], list[ i ] ) );
		}

		$city
			.val( keepValue && list.indexOf( current ) !== -1 ? current : '' )
			.prop( 'disabled', ! list.length )
			.attr( 'data-placeholder', placeholder )
			// selectWoo reads data-* through jQuery's cache, not the attribute.
			.data( 'placeholder', placeholder );

		// A new district invalidates the old thana; drop any stale state classes.
		$city.closest( '.form-row' ).removeClass( 'woocommerce-validated woocommerce-invalid woocommerce-invalid-required-field' );

		enhance( $city );
	}

	// The "Additional information" card (order notes removed) still prints a
	// styled wrapper holding only hidden order-attribution inputs. CSS hides it
	// via :has(); this covers browsers without :has() support.
	function hideEmptyCards() {
		$( '.woocommerce-checkout .woocommerce-additional-fields' ).each( function () {
			var $card = $( this );
			var visible = $card.find( 'h3, .form-row, label, p, textarea, select, button, input:not([type="hidden"])' ).length;
			if ( ! visible && ! $.trim( $card.text() ) ) {
				$card.hide();
			}
		} );
	}

	$( function () {
		render( true );
		hideEmptyCards();

		var lastDistrict = $( '#billing_state' ).val() || '';

		$( document.body ).on( 'change', '#billing_state', function () {
			var district = $( this ).val() || '';
			if ( district === lastDistrict ) {
				return; // Re-fired change (autofill, other scripts): keep the pick.
			}
			lastDistrict = district;
			render( false );
		} );

		// WooCommerce swaps the District element when the country refreshes.
		$( document.body ).on( 'country_to_state_changed', function () {
			render( true );
		} );
	} );
} )( window.jQuery );

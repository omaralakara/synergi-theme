/*
 * services.js — the card deck in section 02.
 *
 * Loaded by inc/assets.php as "synergi-section-services", deferred, only on
 * pages that declare the section. Depends on the "synergi-main" handle.
 * Markup: sections/services.php. Styling: assets/css/sections/services.css.
 *
 * All this does is rotate a data-syn-deck-position number on each card. The
 * geometry — where position 3 sits, how dark it is — is written once, in the
 * stylesheet. Nothing here reads or writes a style property, so changing how
 * the fan looks never means opening this file.
 *
 * Ported from design-source/assets/js/main.js. Three changes: elements are
 * found by data attribute rather than class; the active card is tracked with
 * the same attribute the CSS uses instead of a second is-active class that
 * nothing styled; and there is no autoplay to pause, so unlike the hero this
 * script does nothing at all until someone asks it to.
 *
 * With JavaScript off none of this runs and the six cards stay stacked down the
 * page, in order, with the controls hidden (services.css section 10).
 */

( function () {
	'use strict';

	var deck = document.querySelector( '[data-syn-service-deck]' );

	if ( ! deck ) {
		return;
	}

	var cards = Array.prototype.slice.call( deck.querySelectorAll( '[data-syn-service-card]' ) );
	var previous = deck.querySelector( '[data-syn-service-prev]' );
	var next = deck.querySelector( '[data-syn-service-next]' );
	var status = deck.querySelector( '[data-syn-service-status]' );
	var viewport = deck.querySelector( '.syn-services__viewport' );

	// One card is not a deck. Leave the markup exactly as the server sent it.
	if ( cards.length < 2 || ! viewport ) {
		return;
	}

	function log( message ) {
		if ( window.synDebug ) {
			// eslint-disable-next-line no-console
			console.log( '[synergi] services: ' + message );
		}
	}

	var activeIndex = 0;
	var template = ( status && status.getAttribute( 'data-syn-service-status' ) ) || '';

	/**
	 * Brings one card to the front and fans the rest out behind it.
	 *
	 * @param {number}  index    Card to show. Wraps at both ends.
	 * @param {boolean} announce Whether to update the live region. False on the
	 *                           first call, so a screen reader is not told about
	 *                           a card that has not moved.
	 */
	function showCard( index, announce ) {
		activeIndex = ( index + cards.length ) % cards.length;

		cards.forEach( function ( card, cardIndex ) {
			var active = cardIndex === activeIndex;
			var position = ( cardIndex - activeIndex + cards.length ) % cards.length;

			card.setAttribute( 'data-syn-deck-position', String( position ) );

			/*
			 * The cards behind the front one are visually stacked and mostly
			 * covered, so they are taken out of both the accessibility tree and
			 * the tab order — otherwise Tab walks through six "Explore" links,
			 * five of which point at something the reader cannot see.
			 */
			card.setAttribute( 'aria-hidden', String( ! active ) );
			card.inert = ! active;
		} );

		if ( announce && status ) {
			var name = cards[ activeIndex ].querySelector( '.syn-services__name' );

			if ( name ) {
				/*
				 * The sentence comes from the server already translated, with %s
				 * where the service name goes — so this never assembles English.
				 */
				status.textContent = template.replace( '%s', name.textContent.trim() );
			}
		}
	}


	if ( previous ) {
		previous.addEventListener( 'click', function () {
			showCard( activeIndex - 1, true );
		} );
	}

	if ( next ) {
		next.addEventListener( 'click', function () {
			showCard( activeIndex + 1, true );
		} );
	}

	/* ------------------------------------------------------------------
	 * Keyboard. The viewport carries tabindex="0" and an aria-label saying
	 * the arrow keys work, so they have to.
	 * ------------------------------------------------------------------ */

	viewport.addEventListener( 'keydown', function ( event ) {
		if ( 'ArrowLeft' === event.key ) {
			showCard( activeIndex - 1, true );
		} else if ( 'ArrowRight' === event.key ) {
			showCard( activeIndex + 1, true );
		} else if ( 'Home' === event.key ) {
			showCard( 0, true );
		} else if ( 'End' === event.key ) {
			showCard( cards.length - 1, true );
		} else {
			return;
		}

		// Only after one of the four matched, so every other key still works.
		event.preventDefault();
	} );

	/* ------------------------------------------------------------------
	 * Pointer. A swipe moves the deck; a tap still opens the link under it.
	 * ------------------------------------------------------------------ */

	// A tap on a touchscreen almost always drifts a few pixels. Anything under
	// this stays a tap, so the links inside the card keep working.
	var TAP_SLOP_PX = 12;
	var SWIPE_PX = 45;

	var startX = null;
	var pointerId = null;
	var distance = 0;
	var suppressClick = false;
	var captured = false;

	viewport.addEventListener( 'pointerdown', function ( event ) {
		if ( undefined !== event.button && 0 !== event.button ) {
			return;
		}

		startX = event.clientX;
		pointerId = event.pointerId;
		distance = 0;
		suppressClick = false;
		captured = false;
		viewport.classList.add( 'syn-is-dragging' );

		/*
		 * Deliberately NOT capturing the pointer here. Capturing on pointerdown
		 * retargets the following pointerup AND click to this element, so a tap
		 * on a link inside the card would never reach the link. Capture is
		 * taken in pointermove instead, once the gesture is actually a drag.
		 */
	} );

	viewport.addEventListener(
		'pointermove',
		function ( event ) {
			if ( null === startX || event.pointerId !== pointerId ) {
				return;
			}

			distance = event.clientX - startX;

			if ( Math.abs( distance ) > TAP_SLOP_PX ) {
				if ( ! captured && viewport.setPointerCapture ) {
					viewport.setPointerCapture( event.pointerId );
					captured = true;
				}

				// Stops the browser turning the drag into a text selection or a
				// native image drag. Needs passive: false to be allowed.
				event.preventDefault();
			}
		},
		{ passive: false }
	);

	function finishPointer( event ) {
		if ( null === startX || event.pointerId !== pointerId ) {
			return;
		}

		var travelled = distance || event.clientX - startX;
		var advance = 'pointerup' === event.type && Math.abs( travelled ) >= SWIPE_PX;

		if ( captured && viewport.releasePointerCapture ) {
			viewport.releasePointerCapture( event.pointerId );
		}

		captured = false;
		viewport.classList.remove( 'syn-is-dragging' );
		startX = null;
		pointerId = null;
		distance = 0;
		suppressClick = 'pointerup' === event.type && Math.abs( travelled ) >= TAP_SLOP_PX;

		if ( advance ) {
			showCard( activeIndex + ( travelled < 0 ? 1 : -1 ), true );
			log( 'swiped to card ' + activeIndex );
		}
	}

	viewport.addEventListener( 'pointerup', finishPointer );
	viewport.addEventListener( 'pointercancel', finishPointer );

	/*
	 * Cards are mostly links and text, so a swipe usually starts on one of them.
	 * Without this the browser begins a native drag, which swallows both the
	 * swipe and the click that should follow a tap.
	 */
	viewport.addEventListener( 'dragstart', function ( event ) {
		event.preventDefault();
	} );

	// Capture phase: the click has to be stopped before it reaches the link.
	viewport.addEventListener(
		'click',
		function ( event ) {
			if ( ! suppressClick ) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			suppressClick = false;
		},
		true
	);

	/*
	 * The deck positions are already in the markup, so this first call is only
	 * here to set aria-hidden and inert — which are deliberately absent from the
	 * server's output, because with JavaScript off nothing would ever clear them
	 * and five of the six services would be unreachable.
	 */
	showCard( 0, false );
	log( cards.length + ' cards ready' );
}() );

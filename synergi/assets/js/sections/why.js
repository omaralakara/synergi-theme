/*
 * why.js — the 3D reason deck in section 05.
 *
 * Loaded by inc/assets.php as "synergi-section-why", deferred, only on pages
 * that declare the section. Depends on the "synergi-main" handle.
 * Markup: sections/why.php. Styling: assets/css/sections/why.css.
 *
 * Like the services deck, all this does is rotate a position number on each
 * card; where position 2 sits and how faded it is are written once, in the
 * stylesheet. Nothing here reads or writes a style property.
 *
 * Unlike the services deck, this one moves on its own — which means it has to
 * know when to stop. It stops when the reader points at it, tabs into it,
 * chooses a card, scrolls it off screen, switches tab, or asks for reduced
 * motion. That list is the whole reason this file is longer than the deck logic
 * needs to be, and every entry on it is deliberate: an animation that keeps
 * running off-screen is one CLAUDE.md §6 calls out by name.
 *
 * Ported from design-source/assets/js/why-section.js, which already got this
 * right — the observer, the visibility check and the reduced-motion guard are
 * all the original's. Restructured into named parts and given the theme's
 * data-attribute names.
 *
 * With JavaScript off nothing here runs and why.css leaves the section as a
 * two-by-two grid of four readable cards.
 */

( function () {
	'use strict';

	var root = document.querySelector( '[data-syn-why]' );

	if ( ! root ) {
		return;
	}

	var cards = Array.prototype.slice.call( root.querySelectorAll( '[data-syn-why-card]' ) );
	var pages = Array.prototype.slice.call( root.querySelectorAll( '[data-syn-why-go]' ) );
	var stage = root.querySelector( '[data-syn-why-stage]' );
	var counter = root.querySelector( '[data-syn-why-current]' );
	var status = root.querySelector( '[data-syn-why-status]' );

	// One button per card, or the pairing below is wrong and it is better to
	// leave the server's markup alone than to half-drive it.
	if ( cards.length < 2 || cards.length !== pages.length ) {
		return;
	}

	var template = ( status && status.getAttribute( 'data-syn-why-status' ) ) || '';
	var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	var ADVANCE_MS = 3500;
	var TAP_SLOP_PX = 12;
	var SWIPE_PX = 45;

	var activeIndex = 0;
	var timer = null;
	var onScreen = ! ( 'IntersectionObserver' in window );

	function log( message ) {
		if ( window.synDebug ) {
			// eslint-disable-next-line no-console
			console.log( '[synergi] why: ' + message );
		}
	}

	function wrap( index ) {
		return ( index + cards.length ) % cards.length;
	}

	function pad( number ) {
		return number < 10 ? '0' + number : String( number );
	}

	/**
	 * Turns the deck so that one card faces the reader.
	 *
	 * @param {number}  index    Card to bring to the front. Wraps at both ends.
	 * @param {boolean} announce Whether to update the live region. False when the
	 *                           deck turned by itself — a card changing every
	 *                           three seconds is not worth interrupting a screen
	 *                           reader for, and the reader did not ask for it.
	 * @return {void}
	 */
	function show( index, announce ) {
		activeIndex = wrap( index );

		cards.forEach( function ( card, cardIndex ) {
			var position = wrap( cardIndex - activeIndex );
			var active = 0 === position;

			card.setAttribute( 'data-syn-why-position', String( position ) );
			card.classList.toggle( 'syn-is-active', active );

			/*
			 * The cards behind the front one keep their photograph on screen but
			 * their words are faded out, so their text is hidden from assistive
			 * technology to match. The live region below is what reports the
			 * change instead.
			 */
			card.setAttribute( 'aria-hidden', String( ! active ) );
		} );

		pages.forEach( function ( page, pageIndex ) {
			var active = pageIndex === activeIndex;

			page.classList.toggle( 'syn-is-active', active );
			page.setAttribute( 'aria-pressed', String( active ) );
		} );

		if ( counter ) {
			counter.textContent = pad( activeIndex + 1 );
		}

		if ( announce && status && template ) {
			var title = cards[ activeIndex ].querySelector( '.syn-why__card-title' );

			if ( title ) {
				// The sentence arrives translated from the server with %1$s and
				// %2$s where the number and the heading go.
				status.textContent = template
					.replace( '%1$s', String( activeIndex + 1 ) )
					.replace( '%2$s', title.textContent.trim() );
			}
		}
	}

	/* ------------------------------------------------------------------
	 * Advancing on its own, and every reason to stop
	 * ------------------------------------------------------------------ */

	function stop() {
		window.clearInterval( timer );
		timer = null;
	}

	function start() {
		stop();

		// Three ways this is not wanted: the reader asked for no motion, the tab
		// is not being looked at, or the section is not on screen.
		if ( reducedMotion.matches || document.hidden || ! onScreen ) {
			return;
		}

		timer = window.setInterval( function () {
			show( activeIndex + 1, false );
		}, ADVANCE_MS );
	}

	/**
	 * Moves the deck because a person asked it to, and restarts the clock so the
	 * card they chose gets its full turn rather than the remainder of someone
	 * else's.
	 *
	 * @param {number} index Card to show.
	 * @return {void}
	 */
	function select( index ) {
		show( index, true );
		start();
	}

	pages.forEach( function ( page, index ) {
		page.addEventListener( 'click', function () {
			select( index );
		} );
	} );

	var suppressClick = false;

	cards.forEach( function ( card, index ) {
		card.addEventListener( 'click', function ( event ) {
			// A swipe that ended on this card is not a tap on it.
			if ( suppressClick ) {
				event.preventDefault();

				return;
			}

			if ( index !== activeIndex ) {
				select( index );
			}
		} );
	} );

	if ( stage ) {
		stage.addEventListener( 'keydown', function ( event ) {
			if ( 'ArrowLeft' === event.key ) {
				select( activeIndex - 1 );
			} else if ( 'ArrowRight' === event.key ) {
				select( activeIndex + 1 );
			} else if ( 'Home' === event.key ) {
				select( 0 );
			} else if ( 'End' === event.key ) {
				select( cards.length - 1 );
			} else {
				return;
			}

			// Only after one of the four matched, so every other key still works.
			event.preventDefault();
		} );

		// Pointing at it means reading it. Leaving starts the clock again.
		stage.addEventListener( 'mouseenter', stop );
		stage.addEventListener( 'mouseleave', start );
	}

	// Tabbing in means reading it too, and focus can land on the pagination as
	// well as the stage — so this pair is on the section, not the stage.
	root.addEventListener( 'focusin', stop );
	root.addEventListener( 'focusout', function ( event ) {
		if ( ! root.contains( event.relatedTarget ) ) {
			start();
		}
	} );

	document.addEventListener( 'visibilitychange', function () {
		if ( document.hidden ) {
			stop();
		} else {
			start();
		}
	} );

	reducedMotion.addEventListener( 'change', start );

	/*
	 * Off-screen is the important one. Without it this section would keep
	 * rotating four photographs for as long as the tab stays open, on a page
	 * where it is one of twelve (CLAUDE.md §6: animation pauses off-screen).
	 */
	if ( 'IntersectionObserver' in window ) {
		new window.IntersectionObserver(
			function ( entries ) {
				onScreen = entries[ 0 ].isIntersecting;

				if ( onScreen ) {
					start();
				} else {
					stop();
				}
			},
			{ rootMargin: '80px 0px', threshold: 0.12 }
		).observe( root );
	}

	/* ------------------------------------------------------------------
	 * Swipe
	 * ------------------------------------------------------------------ */

	var startX = null;
	var pointerId = null;
	var captured = false;

	if ( stage ) {
		stage.addEventListener( 'pointerdown', function ( event ) {
			if ( ! event.isPrimary || ( undefined !== event.button && 0 !== event.button ) ) {
				return;
			}

			startX = event.clientX;
			pointerId = event.pointerId;
			captured = false;

			/*
			 * Capture is taken in pointermove once the gesture is a drag, not
			 * here — the same reason as the other two decks. Capturing on
			 * pointerdown retargets the click and the cards stop being tappable.
			 */
		} );

		stage.addEventListener( 'pointermove', function ( event ) {
			if ( null === startX || event.pointerId !== pointerId || captured ) {
				return;
			}

			if ( Math.abs( event.clientX - startX ) > TAP_SLOP_PX && stage.setPointerCapture ) {
				stage.setPointerCapture( event.pointerId );
				captured = true;
			}
		} );

		stage.addEventListener( 'pointerup', function ( event ) {
			if ( null === startX || event.pointerId !== pointerId ) {
				return;
			}

			var travelled = event.clientX - startX;

			if ( captured && stage.releasePointerCapture ) {
				stage.releasePointerCapture( event.pointerId );
			}

			captured = false;
			startX = null;
			pointerId = null;

			if ( Math.abs( travelled ) < SWIPE_PX ) {
				return;
			}

			// Cleared on the next turn of the event loop, by which time the click
			// this swipe would otherwise have fired has been and gone.
			suppressClick = true;
			window.setTimeout( function () {
				suppressClick = false;
			}, 0 );

			select( activeIndex + ( travelled < 0 ? 1 : -1 ) );
		} );

		stage.addEventListener( 'pointercancel', function () {
			startX = null;
			pointerId = null;
			captured = false;
		} );

		// Cards are photographs, so a swipe usually starts on one. Without this
		// the browser begins a native image drag and swallows the gesture.
		stage.addEventListener( 'dragstart', function ( event ) {
			event.preventDefault();
		} );
	}

	/*
	 * The deck is already in its opening position in the markup, so this changes
	 * nothing on screen. It is here to set aria-hidden on the three cards behind
	 * the front one, which is deliberately absent from the server's output —
	 * with JavaScript off nothing would ever clear it and three of the four
	 * reasons would be unreachable.
	 */
	show( 0, false );
	start();
	log( cards.length + ' reasons ready' );
}() );

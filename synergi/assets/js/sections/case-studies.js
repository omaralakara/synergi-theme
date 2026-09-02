/*
 * case-studies.js — the arrows on the scrollable variant (added 2 Sep).
 *
 * Loaded by inc/assets.php as "synergi-section-case-studies", deferred, only on
 * pages that declare the section. Markup: sections/case-studies.php. Styling:
 * assets/css/sections/case-studies.css, section 9.
 *
 * Deliberately NOT the blog band's animated track. The row here is a native
 * overflow scroller with scroll-snap: touch swipes it and the browser owns the
 * physics, so all this file does is page the row when an arrow is clicked,
 * disable an arrow at its end, and hide both when every card already fits.
 * With JavaScript off the row still scrolls; the arrows just never appear.
 */

( function () {
	'use strict';

	var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	function log( message ) {
		if ( window.synDebug ) {
			// eslint-disable-next-line no-console
			console.log( '[synergi] case-studies: ' + message );
		}
	}

	document.querySelectorAll( '[data-syn-cases-scroller]' ).forEach( function ( scroller ) {
		var track = scroller.querySelector( '[data-syn-cases-track]' );
		var previous = scroller.querySelector( '[data-syn-cases-prev]' );
		var next = scroller.querySelector( '[data-syn-cases-next]' );

		if ( ! track || ! previous || ! next ) {
			return;
		}

		/**
		 * Card-to-card distance, including the gap — measured fresh on every
		 * click because the row is short and a stale width after a resize would
		 * page it wrong.
		 *
		 * @return {number} Pixels one page moves.
		 */
		function step() {
			var cards = track.children;

			if ( cards.length > 1 ) {
				return cards[ 1 ].getBoundingClientRect().left - cards[ 0 ].getBoundingClientRect().left;
			}

			return track.clientWidth;
		}

		function page( direction ) {
			track.scrollBy( {
				left: direction * step(),
				behavior: reducedMotion.matches ? 'auto' : 'smooth',
			} );
		}

		/**
		 * Disables an arrow at its end of the row, and hides both when there is
		 * nothing to scroll — arrows on a row that fits would be lying.
		 * scrollLeft is used magnitude-only so the same test holds under RTL,
		 * where browsers count it negative.
		 *
		 * @return {void}
		 */
		function sync() {
			var overflow = track.scrollWidth - track.clientWidth;
			var travelled = Math.abs( track.scrollLeft );

			scroller.classList.toggle( 'syn-is-static', overflow < 2 );
			previous.disabled = travelled < 2;
			next.disabled = travelled > overflow - 2;
		}

		previous.addEventListener( 'click', function () {
			page( -1 );
		} );

		next.addEventListener( 'click', function () {
			page( 1 );
		} );

		// rAF-throttled: scroll fires continuously through a swipe and the sync
		// is cheap, but there is no reason to run it more than once a frame.
		var frame = 0;

		track.addEventListener(
			'scroll',
			function () {
				if ( frame ) {
					return;
				}

				frame = window.requestAnimationFrame( function () {
					frame = 0;
					sync();
				} );
			},
			{ passive: true }
		);

		if ( 'ResizeObserver' in window ) {
			new window.ResizeObserver( sync ).observe( track );
		} else {
			window.addEventListener( 'resize', sync, { passive: true } );
		}

		sync();
		log( track.children.length + ' cards in the scroller' );
	} );
}() );

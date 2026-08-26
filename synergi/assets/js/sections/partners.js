/*
 * partners.js — the drifting, draggable logo strip in section 07.
 *
 * Loaded by inc/assets.php as "synergi-section-partners", deferred, only on
 * pages that declare the section. Depends on the "synergi-main" handle.
 * Markup: sections/partners.php. Styling: assets/css/sections/partners.css.
 *
 * What it does, in one sentence: wraps the authored track in a strip, puts a
 * copy of the track either side of it, and moves that strip's transform left
 * for ever, jumping back by exactly one track's width whenever it has travelled
 * one — so the row appears endless while only one element ever moves.
 *
 * It stops when the section is off screen, the tab is not being looked at,
 * someone is dragging it, someone is pointing at it, someone has tabbed into
 * it, or reduced motion is asked for. The first two are CLAUDE.md §6; the two
 * after that are what makes an endlessly moving row readable at all.
 *
 * Ported from the [data-partner-marquee] block in design-source/assets/js/main.js.
 * Three differences, each commented where it happens: the logos are list items
 * rather than hrefless anchors, so the pause-on-focus moved to the strip, which
 * is genuinely focusable; hovering pauses too; and the drag no longer has to
 * cancel a click, because there is nothing left to click.
 *
 * With JavaScript off nothing here runs and partners.css leaves one still row
 * of logos, which is a perfectly good way to show a list of partners.
 */

( function () {
	'use strict';

	var marquee = document.querySelector( '[data-syn-partners-marquee]' );

	if ( ! marquee ) {
		return;
	}

	var track = marquee.querySelector( '[data-syn-partners-track]' );

	if ( ! track ) {
		return;
	}

	var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	// Pixels per millisecond. Slow enough to read a logo as it goes past.
	var SPEED = 0.052;
	// A pointer that moved less than this was a press, not a drag.
	var DRAG_SLOP_PX = 4;
	// After a long pause the clock has run on; this caps the catch-up jump.
	var MAX_STEP_MS = 48;

	var strip = null;
	var loopWidth = 0;
	var offset = 0;

	var pointerId = null;
	var pointerStartX = 0;
	var startOffset = 0;
	var dragging = false;

	var pointedAt = false;
	var focused = false;
	var onScreen = ! ( 'IntersectionObserver' in window );

	var frameId = 0;
	var previousFrame = null;

	function log( message ) {
		if ( window.synDebug ) {
			// eslint-disable-next-line no-console
			console.log( '[synergi] partners: ' + message );
		}
	}

	/* ------------------------------------------------------------------
	 * Building the endless strip
	 * ------------------------------------------------------------------ */

	strip = document.createElement( 'div' );
	strip.className = 'syn-partners__strip';
	track.before( strip );
	strip.append( track );

	/*
	 * One copy before and one after. Before matters as much as after: the strip
	 * rests at -loopWidth, so dragging it to the right has to reveal something,
	 * and without a leading copy it would reveal the page.
	 *
	 * The copies are aria-hidden, so the row is announced once rather than three
	 * times, and their images are already in the page's cache.
	 */
	[ 'beforebegin', 'afterend' ].forEach( function ( position ) {
		var copy = track.cloneNode( true );

		copy.setAttribute( 'aria-hidden', 'true' );
		copy.removeAttribute( 'data-syn-partners-track' );
		track.insertAdjacentElement( position, copy );
	} );

	/**
	 * Keeps the offset inside one track's width of its resting place, so the
	 * number never grows without bound over a long visit.
	 *
	 * @return {void}
	 */
	function normalize() {
		if ( ! loopWidth ) {
			return;
		}

		while ( offset <= -loopWidth * 2 ) {
			offset += loopWidth;
		}

		while ( offset >= 0 ) {
			offset -= loopWidth;
		}
	}

	/**
	 * Moves the strip. The only place this file writes a style.
	 *
	 * @param {number} next Offset in pixels.
	 * @return {void}
	 */
	function setOffset( next ) {
		offset = next;
		normalize();
		strip.style.transform = 'translate3d(' + offset + 'px, 0, 0)';
	}

	/* ------------------------------------------------------------------
	 * Drifting, and every reason to stop
	 * ------------------------------------------------------------------ */

	function shouldMove() {
		return (
			loopWidth > 0 &&
			onScreen &&
			! document.hidden &&
			! reducedMotion.matches &&
			! dragging &&
			! pointedAt &&
			! focused
		);
	}

	function stop() {
		if ( frameId ) {
			window.cancelAnimationFrame( frameId );
			frameId = 0;
		}

		previousFrame = null;
	}

	function schedule() {
		if ( frameId || ! shouldMove() ) {
			return;
		}

		frameId = window.requestAnimationFrame( step );
	}

	function step( timestamp ) {
		frameId = 0;

		if ( ! shouldMove() ) {
			previousFrame = null;

			return;
		}

		if ( null === previousFrame ) {
			previousFrame = timestamp;
		}

		var elapsed = Math.min( timestamp - previousFrame, MAX_STEP_MS );

		previousFrame = timestamp;
		setOffset( offset - ( elapsed * SPEED ) );
		schedule();
	}

	/**
	 * Re-reads the track's width and puts the strip back where it was, in
	 * proportion, so a resize does not throw the row to a different logo.
	 *
	 * @param {boolean} reset True to park it at its resting place instead.
	 * @return {void}
	 */
	function measure( reset ) {
		var nextWidth = track.getBoundingClientRect().width;

		if ( ! nextWidth ) {
			return;
		}

		var progress = loopWidth ? ( offset + loopWidth ) / loopWidth : 0;

		loopWidth = nextWidth;
		setOffset( reset ? -loopWidth : -loopWidth + ( progress * loopWidth ) );
		schedule();
	}

	var measureFrame = 0;

	// Scheduled rather than run inside the observer, so the measurement and the
	// transform that follows it never land in the middle of the layout step.
	function requestMeasure() {
		if ( measureFrame ) {
			return;
		}

		measureFrame = window.requestAnimationFrame( function () {
			measureFrame = 0;
			measure( false );
		} );
	}

	/* ------------------------------------------------------------------
	 * Dragging
	 * ------------------------------------------------------------------ */

	marquee.addEventListener( 'pointerdown', function ( event ) {
		if ( undefined !== event.button && 0 !== event.button ) {
			return;
		}

		pointerId = event.pointerId;
		pointerStartX = event.clientX;
		startOffset = offset;
		dragging = true;
		marquee.classList.add( 'syn-is-dragging' );

		if ( marquee.setPointerCapture ) {
			marquee.setPointerCapture( event.pointerId );
		}

		stop();
	} );

	marquee.addEventListener(
		'pointermove',
		function ( event ) {
			if ( event.pointerId !== pointerId ) {
				return;
			}

			var distance = event.clientX - pointerStartX;

			if ( Math.abs( distance ) <= DRAG_SLOP_PX ) {
				return;
			}

			setOffset( startOffset + distance );

			// Not passive: this is the gesture, and letting the browser also
			// treat it as a scroll would fight the drag.
			event.preventDefault();
		},
		{ passive: false }
	);

	function endDrag( event ) {
		if ( event.pointerId !== pointerId ) {
			return;
		}

		if ( marquee.releasePointerCapture ) {
			marquee.releasePointerCapture( event.pointerId );
		}

		marquee.classList.remove( 'syn-is-dragging' );
		pointerId = null;
		dragging = false;
		schedule();
	}

	marquee.addEventListener( 'pointerup', endDrag );
	marquee.addEventListener( 'pointercancel', endDrag );

	// The row is nine photographs of logos, so a drag usually starts on one.
	// Without this the browser begins a native image drag and eats the gesture.
	marquee.addEventListener( 'dragstart', function ( event ) {
		event.preventDefault();
	} );

	/* ------------------------------------------------------------------
	 * Pausing for a reader
	 * ------------------------------------------------------------------ */

	marquee.addEventListener( 'mouseenter', function () {
		pointedAt = true;
		stop();
	} );

	marquee.addEventListener( 'mouseleave', function () {
		pointedAt = false;
		schedule();
	} );

	/*
	 * The strip itself is the tab stop — nothing inside it is focusable, because
	 * the logos are not links. This is the only way a keyboard user can stop a
	 * row that otherwise never stops moving.
	 */
	marquee.addEventListener( 'focus', function () {
		focused = true;
		stop();
	} );

	marquee.addEventListener( 'blur', function () {
		focused = false;
		schedule();
	} );

	/* ------------------------------------------------------------------
	 * When not to run at all
	 * ------------------------------------------------------------------ */

	if ( 'ResizeObserver' in window ) {
		new window.ResizeObserver( requestMeasure ).observe( track );
	} else {
		window.addEventListener( 'resize', requestMeasure, { passive: true } );
	}

	if ( 'IntersectionObserver' in window ) {
		new window.IntersectionObserver(
			function ( entries ) {
				onScreen = entries[ 0 ].isIntersecting;

				if ( onScreen ) {
					schedule();
				} else {
					stop();
				}
			},
			{ rootMargin: '100px 0px', threshold: 0.01 }
		).observe( marquee );
	}

	document.addEventListener( 'visibilitychange', function () {
		if ( document.hidden ) {
			stop();
		} else {
			schedule();
		}
	} );

	reducedMotion.addEventListener( 'change', function () {
		if ( reducedMotion.matches ) {
			stop();
		} else {
			schedule();
		}
	} );

	measure( true );
	schedule();
	log( 'strip ready, loop ' + Math.round( loopWidth ) + 'px' );
}() );

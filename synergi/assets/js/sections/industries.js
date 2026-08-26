/*
 * industries.js — the industry queue in section 04.
 *
 * Loaded by inc/assets.php as "synergi-section-industries", deferred, only on
 * pages that declare the section. Depends on the "synergi-main" handle.
 * Markup: sections/industries.php. Styling: assets/css/sections/industries.css.
 *
 * This is the most involved script in the theme, so it is worth saying up front
 * what it does and does not do.
 *
 * It does NOT size or position anything. Card widths are flex-grow values that
 * industries.css keys off a data-syn-industries-position attribute, and
 * flex-grow interpolates — so advancing the queue is a rotation of six numbers
 * and the browser animates the rest.
 *
 * What it DOES do is cover the one thing CSS cannot: when you pick the fourth
 * card, the three it skipped have to leave the front and reappear at the back.
 * Moving them in the DOM is instant, and an instant move in the middle of a
 * 1100ms width animation is a jump. So for the length of the move each departing
 * card is shadowed by a ghost — an empty span carrying that card's photograph as
 * a background — which grows at the tail while the real card collapses at the
 * front. The rail's width never changes, and the queue reads as having wrapped
 * around. When the animation ends the DOM is reordered for real and the ghosts
 * are dropped.
 *
 * Ported from design-source/assets/js/main.js. Same technique and the same
 * timings; restructured into named steps, because the original is one 200-line
 * function and its own comments record a bug ("end-of-animation shake") being
 * chased through it.
 *
 * Below 48rem none of that applies: one card shows at a time and moving is a
 * crossfade, which is the branch at the top of moveTo().
 *
 * With JavaScript off nothing here runs, and industries.css leaves the section
 * as a plain grid of six captioned photographs.
 */

( function () {
	'use strict';

	var queue = document.querySelector( '[data-syn-industries-queue]' );

	if ( ! queue ) {
		return;
	}

	var rail = queue.querySelector( '[data-syn-industries-rail]' );
	var cards = Array.prototype.slice.call( queue.querySelectorAll( '[data-syn-industries-card]' ) );
	var previous = queue.querySelector( '[data-syn-industries-prev]' );
	var next = queue.querySelector( '[data-syn-industries-next]' );
	var status = queue.querySelector( '[data-syn-industries-status]' );

	if ( ! rail || cards.length < 2 ) {
		return;
	}

	var template = ( status && status.getAttribute( 'data-syn-industries-status' ) ) || '';
	var compact = window.matchMedia( '(max-width: 47.99rem)' );
	var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	// Matches --syn-industries-step-duration in industries.css. The two have to
	// agree: this one decides when the DOM is reordered, that one decides when
	// the cards have finished moving.
	var STEP_MS = 1100;
	var CROSSFADE_MS = 140;
	var CROSSFADE_SETTLE_MS = 260;
	var SWIPE_PX = 44;
	var TAP_SLOP_PX = 12;

	// The queue's running order. cards keeps DOM order and never changes; this
	// is the order they are shown in, and index 0 is the card at the front.
	var order = cards.slice();
	var moving = false;
	var queued = null;

	function log( message ) {
		if ( window.synDebug ) {
			// eslint-disable-next-line no-console
			console.log( '[synergi] industries: ' + message );
		}
	}

	function buttonFor( card ) {
		return card.querySelector( '[data-syn-industries-go]' );
	}

	function duration( ms ) {
		return reducedMotion.matches ? 0 : ms;
	}

	/**
	 * Writes the running order onto the cards.
	 *
	 * The only place any state reaches the DOM. Everything visual follows from
	 * the position attribute and the active class.
	 *
	 * @return {void}
	 */
	function sync() {
		order.forEach( function ( card, position ) {
			var active = 0 === position;
			var button = buttonFor( card );

			card.setAttribute( 'data-syn-industries-position', String( position ) );
			card.classList.toggle( 'syn-is-active', active );

			if ( button ) {
				button.setAttribute( 'aria-pressed', String( active ) );
			}
		} );
	}

	function announce( moveFocus ) {
		var card = order[ 0 ];
		var title = card.querySelector( '.syn-industries__card-title' );
		var button = buttonFor( card );

		if ( status && template && title ) {
			status.textContent = template.replace( '%s', title.textContent.trim() );
		}

		// Only when the move came from the keyboard or a control. Stealing focus
		// after a tap would scroll the page out from under the reader.
		if ( moveFocus && button ) {
			button.focus( { preventScroll: true } );
		}
	}

	function settle( moveFocus ) {
		moving = false;
		announce( moveFocus );

		if ( queued ) {
			var pending = queued;
			queued = null;
			moveTo( pending, moveFocus );
		}
	}

	/**
	 * Puts the cards back in running order in the DOM, once nothing is moving.
	 *
	 * @param {Element[]} departing Cards that were on their way to the back.
	 * @param {Element[]} ghosts    The spans standing in for them.
	 * @return {void}
	 */
	function reorder( departing, ghosts ) {
		freezeTransitions( cards.concat( ghosts ) );

		order.forEach( function ( card ) {
			rail.append( card );
		} );

		departing.forEach( function ( card ) {
			card.classList.remove( 'syn-is-exiting' );
		} );

		ghosts.forEach( function ( ghost ) {
			ghost.remove();
		} );

		sync();

		// Read a layout property so the browser applies all of the above before
		// transitions come back on. Without it the un-freezing is batched with
		// the changes and the cards animate from their old geometry.
		void rail.offsetWidth;

		window.requestAnimationFrame( function () {
			thawTransitions( cards );
		} );
	}

	function freezeTransitions( elements ) {
		elements.forEach( function ( element ) {
			element.style.transition = 'none';
		} );
	}

	function thawTransitions( elements ) {
		elements.forEach( function ( element ) {
			element.style.transition = '';
		} );
	}

	/**
	 * Measures how wide each departing card will be once it reaches the back.
	 *
	 * Done by briefly applying the new order, reading the widths, and putting
	 * everything back — all with transitions off, so none of it is seen. The
	 * ghosts need these numbers before the animation starts.
	 *
	 * @param {Element[]} nextOrder The order the queue is moving to.
	 * @param {Element[]} departing Cards leaving the front.
	 * @return {number[]} A width in pixels per departing card.
	 */
	function measureTailWidths( nextOrder, departing ) {
		var previousOrder = order;

		freezeTransitions( cards );

		order = nextOrder;
		order.forEach( function ( card ) {
			rail.append( card );
		} );
		sync();
		void rail.offsetWidth;

		var widths = departing.map( function ( card ) {
			return card.getBoundingClientRect().width;
		} );

		order = previousOrder;
		order.forEach( function ( card ) {
			rail.append( card );
		} );
		sync();
		void rail.offsetWidth;

		thawTransitions( cards );

		return widths;
	}

	/**
	 * Builds the stand-ins that hold the tail's width open during a move.
	 *
	 * @param {Element[]} departing Cards leaving the front.
	 * @return {Element[]} One ghost per departing card, already in the rail.
	 */
	function makeGhosts( departing ) {
		return departing.map( function ( card ) {
			var ghost = document.createElement( 'span' );
			var photo = card.querySelector( '.syn-industries__photo' );
			var source = photo && ( photo.currentSrc || photo.src );

			ghost.className = 'syn-industries__placeholder';
			ghost.setAttribute( 'aria-hidden', 'true' );

			if ( source ) {
				// The one place this script touches a style property. It is the
				// card's own photograph, which cannot be known from a stylesheet.
				ghost.style.backgroundImage = 'url("' + source + '")';
			}

			rail.append( ghost );

			return ghost;
		} );
	}

	/**
	 * Crossfades to a card. The whole of the move, on a phone.
	 *
	 * @param {Element[]} nextOrder The order to move to.
	 * @param {boolean}   moveFocus Whether to move focus after settling.
	 * @return {void}
	 */
	function crossfadeTo( nextOrder, moveFocus ) {
		rail.classList.add( 'syn-is-changing' );

		window.setTimeout( function () {
			order = nextOrder;
			order.forEach( function ( card ) {
				rail.append( card );
			} );
			sync();

			window.requestAnimationFrame( function () {
				rail.classList.remove( 'syn-is-changing' );
			} );

			window.setTimeout( function () {
				settle( moveFocus );
			}, duration( CROSSFADE_SETTLE_MS ) );
		}, duration( CROSSFADE_MS ) );
	}

	/**
	 * Brings a card to the front of the queue.
	 *
	 * @param {Element} card      The card to show.
	 * @param {boolean} moveFocus Whether to move focus to it afterwards. True
	 *                            when the move came from a control or a key,
	 *                            false when it came from a tap.
	 * @return {void}
	 */
	function moveTo( card, moveFocus ) {
		if ( ! card || order.indexOf( card ) === -1 ) {
			return;
		}

		// Mid-move clicks are remembered rather than dropped or run on top of
		// each other, which is what a queue of six invites people to do.
		if ( moving ) {
			queued = card;
			return;
		}

		var selected = order.indexOf( card );

		if ( 0 === selected ) {
			if ( moveFocus ) {
				var button = buttonFor( card );

				if ( button ) {
					button.focus( { preventScroll: true } );
				}
			}

			return;
		}

		moving = true;

		var departing = order.slice( 0, selected );
		var nextOrder = order.slice( selected ).concat( departing );

		log( 'moving ' + departing.length + ' card(s) to the back' );

		if ( compact.matches ) {
			crossfadeTo( nextOrder, moveFocus );

			return;
		}

		var tailWidths = measureTailWidths( nextOrder, departing );
		var ghosts = makeGhosts( departing );
		var stepMs = duration( STEP_MS );

		rail.style.setProperty( '--syn-industries-step-duration', stepMs + 'ms' );
		void rail.offsetWidth;

		window.requestAnimationFrame( function () {
			departing.forEach( function ( card ) {
				card.classList.add( 'syn-is-exiting' );
			} );

			order = nextOrder;
			sync();

			ghosts.forEach( function ( ghost, index ) {
				ghost.style.flexBasis = tailWidths[ index ] + 'px';
			} );

			/*
			 * Scheduled from the frame that STARTS the transitions rather than
			 * from the call site, plus a small buffer. On a busy main thread a
			 * timer started earlier can fire while the cards are still visibly
			 * moving and snap them to their final geometry — which is the shake
			 * the design source's own comment describes chasing.
			 */
			window.setTimeout( function () {
				reorder( departing, ghosts );
				settle( moveFocus );
			}, stepMs ? stepMs + 100 : 0 );
		} );
	}

	/* ------------------------------------------------------------------
	 * Controls
	 * ------------------------------------------------------------------ */

	if ( previous ) {
		previous.addEventListener( 'click', function () {
			moveTo( order[ order.length - 1 ], true );
		} );
	}

	if ( next ) {
		next.addEventListener( 'click', function () {
			moveTo( order[ 1 ], true );
		} );
	}

	var suppressClick = false;

	cards.forEach( function ( card ) {
		var button = buttonFor( card );

		if ( ! button ) {
			return;
		}

		button.addEventListener( 'click', function ( event ) {
			// A swipe that ended on this button is not a tap on it.
			if ( suppressClick ) {
				event.preventDefault();

				return;
			}

			moveTo( card, false );
		} );
	} );

	/* ------------------------------------------------------------------
	 * Keyboard. The rail is reachable through the card buttons, so the arrow
	 * keys work from wherever focus already is inside it.
	 * ------------------------------------------------------------------ */

	rail.addEventListener( 'keydown', function ( event ) {
		if ( 'ArrowLeft' === event.key ) {
			moveTo( order[ order.length - 1 ], true );
		} else if ( 'ArrowRight' === event.key ) {
			moveTo( order[ 1 ], true );
		} else if ( 'Home' === event.key ) {
			moveTo( cards[ 0 ], true );
		} else if ( 'End' === event.key ) {
			moveTo( cards[ cards.length - 1 ], true );
		} else {
			return;
		}

		// Only after one of the four matched, so every other key still works.
		event.preventDefault();
	} );

	/* ------------------------------------------------------------------
	 * Swipe, on phones only — above that width every card is already one
	 * click away.
	 * ------------------------------------------------------------------ */

	var startX = null;
	var pointerId = null;
	var captured = false;

	rail.addEventListener( 'pointerdown', function ( event ) {
		if ( ! compact.matches || ! event.isPrimary ) {
			return;
		}

		startX = event.clientX;
		pointerId = event.pointerId;
		captured = false;

		/*
		 * Capture is taken in pointermove, once the gesture is actually a drag —
		 * the same reason as the services deck. Capturing here would retarget
		 * the click and the card buttons would stop responding to taps.
		 */
	} );

	rail.addEventListener( 'pointermove', function ( event ) {
		if ( null === startX || event.pointerId !== pointerId || captured ) {
			return;
		}

		if ( Math.abs( event.clientX - startX ) > TAP_SLOP_PX && rail.setPointerCapture ) {
			rail.setPointerCapture( event.pointerId );
			captured = true;
		}
	} );

	rail.addEventListener( 'pointerup', function ( event ) {
		if ( null === startX || event.pointerId !== pointerId ) {
			return;
		}

		var travelled = event.clientX - startX;

		if ( captured && rail.releasePointerCapture ) {
			rail.releasePointerCapture( event.pointerId );
		}

		captured = false;
		startX = null;
		pointerId = null;

		if ( Math.abs( travelled ) < SWIPE_PX ) {
			return;
		}

		// Cleared on the next turn of the event loop, by which time the click
		// this swipe would otherwise have fired has already been and gone.
		suppressClick = true;
		window.setTimeout( function () {
			suppressClick = false;
		}, 0 );

		moveTo( travelled < 0 ? order[ 1 ] : order[ order.length - 1 ], false );
	} );

	rail.addEventListener( 'pointercancel', function () {
		startX = null;
		pointerId = null;
		captured = false;
	} );

	// Cards are photographs, so a swipe usually starts on one. Without this the
	// browser begins a native image drag and swallows the gesture.
	rail.addEventListener( 'dragstart', function ( event ) {
		event.preventDefault();
	} );

	/*
	 * The running order is already in the markup, so this first call changes
	 * nothing on screen. It is here so the DOM and this script's idea of the
	 * order start out provably identical rather than assumed to be.
	 */
	sync();
	log( cards.length + ' industries ready' );
}() );

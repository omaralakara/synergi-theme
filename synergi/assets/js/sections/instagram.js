/*
 * instagram.js — the Instagram post carousel.
 *
 * Loaded by inc/assets.php as "synergi-section-instagram", deferred, only on
 * pages that declare the section. Markup: sections/instagram.php. Styling:
 * assets/css/sections/instagram.css, section 4.
 *
 * This is the blog band's carousel (assets/js/sections/blog.js), ported line
 * for line into this section's own names on 3 Sep, so the three carousels on
 * the Media hub — case studies, articles and posts — page, drag and throw
 * identically. The files are kept separate rather than shared because a
 * section's behaviour lives under its own name (CLAUDE.md §13): a bug seen on
 * this band is found in this file.
 *
 * The track is a flex row wider than its viewport. Paging slides it by whole
 * cards and recycles cards between the two ends, so the row never runs out and
 * there is no first or last. Dragging moves it with the pointer and then throws
 * it to the nearest card boundary.
 *
 * With JavaScript off nothing here runs and instagram.css leaves a plain grid
 * of the first three posts with no arrows.
 */

( function () {
	'use strict';

	var carousel = document.querySelector( '[data-syn-instagram-carousel]' );

	if ( ! carousel ) {
		return;
	}

	var viewport = carousel.querySelector( '[data-syn-instagram-viewport]' );
	var track = carousel.querySelector( '[data-syn-instagram-track]' );
	var previous = carousel.querySelector( '[data-syn-instagram-prev]' );
	var next = carousel.querySelector( '[data-syn-instagram-next]' );
	var status = carousel.querySelector( '[data-syn-instagram-status]' );

	if ( ! viewport || ! track || track.children.length < 2 ) {
		return;
	}

	var cardCount = track.children.length;
	var template = ( status && status.getAttribute( 'data-syn-instagram-status' ) ) || '';
	var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	var EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';
	var TAP_SLOP_PX = 8;
	// How long a flick's speed is projected forward when choosing where to land.
	var THROW_MS = 140;

	// The track's translateX, kept inside (-step, 0] by recycle().
	var offset = 0;
	var settleTimer = 0;
	var queuedSteps = 0;

	var pointerId = null;
	var dragStartX = null;
	var dragBaseOffset = 0;
	var dragging = false;
	var suppressClick = false;
	var samples = [];

	/*
	 * Measured once and re-measured only when the viewport actually changes
	 * size — same reasoning as blog.js: reading layout inside every click forces
	 * a synchronous reflow mid-interaction.
	 */
	var step = 0;
	var perView = 1;

	function log( message ) {
		if ( window.synDebug ) {
			// eslint-disable-next-line no-console
			console.log( '[synergi] instagram: ' + message );
		}
	}

	/**
	 * Re-reads the one card width the whole file works in.
	 *
	 * @return {void}
	 */
	function measure() {
		var first = track.children[ 0 ].getBoundingClientRect();
		var second = track.children[ 1 ] ? track.children[ 1 ].getBoundingClientRect() : null;

		// Card-to-card distance, which includes the gap; not the card's width.
		step = second ? second.left - first.left : first.width;
		perView = step > 0 ? Math.max( 1, Math.round( viewport.getBoundingClientRect().width / step ) ) : 1;
	}

	function render() {
		track.style.transform = 'translateX(' + offset + 'px)';
	}

	/**
	 * Moves cards between the ends of the track so the offset stays within one
	 * card of home. This is what makes the row endless.
	 *
	 * @return {void}
	 */
	function recycle() {
		if ( ! ( step > 0 ) ) {
			return;
		}

		// Bounded so a bad measurement can never spin here.
		var guard = 0;

		while ( offset <= -step && guard < 64 ) {
			track.append( track.children[ 0 ] );
			offset += step;
			guard += 1;
		}

		while ( offset > 0 && guard < 64 ) {
			track.prepend( track.children[ track.children.length - 1 ] );
			offset -= step;
			guard += 1;
		}
	}

	function announce() {
		if ( ! status || ! template ) {
			return;
		}

		/*
		 * The leading card's alt text, which is its caption — the only words a
		 * post has. textContent would read the visually-hidden "View this post"
		 * label on every card instead, which says nothing about which post is
		 * now in front.
		 */
		var image = track.children[ 0 ].querySelector( '.syn-instagram__image' );

		if ( image ) {
			status.textContent = template.replace( '%s', '"' + image.alt.trim() + '"' );
		}
	}

	/**
	 * Shows or hides the arrows. With every card on screen there is nothing to
	 * page through and arrows would misrepresent the content.
	 *
	 * @return {boolean} Whether paging is possible at this width.
	 */
	function syncControls() {
		var pageable = cardCount > perView;

		carousel.classList.toggle( 'syn-is-static', ! pageable );

		[ previous, next ].forEach( function ( button ) {
			if ( button ) {
				button.disabled = ! pageable;
			}
		} );

		return pageable;
	}

	/**
	 * Lands the track on a card boundary and runs anything that was asked for
	 * while it was still gliding.
	 *
	 * @return {void}
	 */
	function settle() {
		settleTimer = 0;
		track.style.transition = 'none';
		recycle();
		render();
		// Forces the style change above to be applied before the transition is
		// handed back, or the browser coalesces the two and the next move has
		// no transition at all.
		void track.offsetWidth;
		track.style.transition = '';
		announce();

		if ( queuedSteps ) {
			var pending = queuedSteps;

			queuedSteps = 0;
			move( pending );
		}
	}

	/**
	 * Reads where the track actually is on screen right now, mid-glide.
	 *
	 * @return {number} Its translateX in pixels.
	 */
	function currentTranslate() {
		var value = window.getComputedStyle( track ).transform;

		if ( ! value || 'none' === value ) {
			return 0;
		}

		var numbers = value.match( /matrix.*\((.+)\)/ );

		if ( ! numbers ) {
			return 0;
		}

		var parts = numbers[ 1 ].split( ',' ).map( Number );

		// matrix3d puts translateX at 13th; matrix at 5th.
		return parts.length > 6 ? parts[ 12 ] : parts[ 4 ];
	}

	// Freezes the track where the eye last saw it, so grabbing mid-glide picks
	// the cards up from there rather than from where they were headed.
	function stopAnimation() {
		if ( settleTimer ) {
			window.clearTimeout( settleTimer );
			settleTimer = 0;
		}

		offset = currentTranslate();
		track.style.transition = 'none';
		render();
	}

	/**
	 * Glides the track to a position.
	 *
	 * @param {number} target Offset in pixels.
	 * @return {void}
	 */
	function animateTo( target ) {
		var distance = Math.abs( target - offset );
		// Longer travel takes longer, within reason, so a two-card page does not
		// feel like a jump and a one-card page does not feel slow.
		var duration = reducedMotion.matches ? 0 : Math.min( 560, Math.max( 300, distance * 0.55 ) );

		if ( settleTimer ) {
			window.clearTimeout( settleTimer );
			settleTimer = 0;
		}

		offset = target;

		if ( 0 === duration ) {
			settle();

			return;
		}

		track.style.transition = 'transform ' + duration + 'ms ' + EASE;
		render();
		settleTimer = window.setTimeout( settle, duration + 60 );
	}

	/**
	 * Pages the track by whole cards.
	 *
	 * @param {number} steps Cards to move by. Negative goes back.
	 * @return {void}
	 */
	function move( steps ) {
		if ( ! steps || ! syncControls() ) {
			return;
		}

		var spare = cardCount - perView;

		if ( spare < 1 || ! ( step > 0 ) ) {
			return;
		}

		/*
		 * Every page starts from a card boundary. A press that arrives mid-glide
		 * is queued for the settle instead: starting from a fractional offset
		 * leaves the track resting between two cards, and it needs more travel
		 * room than the off-screen cards can cover.
		 */
		if ( settleTimer ) {
			var cap = Math.min( spare, Math.floor( cardCount / 2 ) );

			queuedSteps = Math.max( -cap, Math.min( cap, queuedSteps + steps ) );

			return;
		}

		recycle();
		render();

		if ( Math.abs( steps ) > spare ) {
			// Further than the off-screen cards can cover, so animating would run
			// the track onto empty space. Reposition with no transition instead.
			var forward = steps > 0 ? steps : cardCount + steps;

			for ( var i = 0; i < forward; i += 1 ) {
				track.append( track.children[ 0 ] );
			}

			offset = 0;
			settle();

			return;
		}

		if ( steps < 0 ) {
			/*
			 * Going back, the cards that are about to appear are sitting at the
			 * END of the track, so they have to be moved to the front BEFORE the
			 * animation with the offset compensated to match. Without this the
			 * track just slides off empty space and every backwards page shows a
			 * card-sized blank for the length of the glide.
			 */
			var count = -steps;

			for ( var back = 0; back < count; back += 1 ) {
				track.prepend( track.children[ track.children.length - 1 ] );
				offset -= step;
			}

			track.style.transition = 'none';
			render();
			void track.offsetWidth;
			animateTo( offset + ( count * step ) );

			return;
		}

		animateTo( offset - ( steps * step ) );
	}

	if ( next ) {
		next.addEventListener( 'click', function () {
			move( 1 );
		} );
	}

	if ( previous ) {
		previous.addEventListener( 'click', function () {
			move( -1 );
		} );
	}

	/* ------------------------------------------------------------------
	 * Dragging
	 * ------------------------------------------------------------------ */

	viewport.addEventListener( 'pointerdown', function ( event ) {
		if ( ! event.isPrimary ) {
			return;
		}

		stopAnimation();
		queuedSteps = 0;
		recycle();
		render();

		pointerId = event.pointerId;
		dragStartX = event.clientX;
		dragBaseOffset = offset;
		dragging = false;
		samples = [ { x: event.clientX, t: event.timeStamp } ];
	} );

	viewport.addEventListener( 'pointermove', function ( event ) {
		if ( null === dragStartX || event.pointerId !== pointerId ) {
			return;
		}

		var travelled = event.clientX - dragStartX;

		if ( ! dragging ) {
			if ( Math.abs( travelled ) <= TAP_SLOP_PX ) {
				return;
			}

			/*
			 * Capture is taken here, once the gesture is definitely a drag, not
			 * on pointerdown — capturing early retargets the click and the card
			 * links stop working. Same reason as the other decks.
			 */
			viewport.setPointerCapture( event.pointerId );
			dragging = true;
			viewport.classList.add( 'syn-is-dragging' );
			track.style.transition = 'none';
		}

		samples.push( { x: event.clientX, t: event.timeStamp } );

		if ( samples.length > 6 ) {
			samples.shift();
		}

		offset = dragBaseOffset + travelled;
		recycle();
		// Recycling shifts the offset by whole cards, so the base is rebased to
		// match and the track keeps tracking the pointer exactly.
		dragBaseOffset = offset - travelled;
		render();
	} );

	function endDrag( event, cancelled ) {
		if ( null === dragStartX || event.pointerId !== pointerId ) {
			return;
		}

		var wasDragging = dragging;

		if ( wasDragging && viewport.releasePointerCapture ) {
			viewport.releasePointerCapture( event.pointerId );
			viewport.classList.remove( 'syn-is-dragging' );
		}

		dragStartX = null;
		pointerId = null;
		dragging = false;

		if ( ! wasDragging ) {
			return;
		}

		// Cleared on the next turn of the event loop, by which time the click
		// this drag would otherwise have fired has been and gone.
		suppressClick = true;
		window.setTimeout( function () {
			suppressClick = false;
		}, 0 );

		var velocity = 0;

		if ( ! cancelled && samples.length > 1 ) {
			var first = samples[ 0 ];
			var last = samples[ samples.length - 1 ];
			var elapsed = last.t - first.t;

			if ( elapsed > 0 ) {
				velocity = ( last.x - first.x ) / elapsed;
			}
		}

		// Throw it a little past the finger, then snap to whichever card
		// boundary that lands nearest — bounded by the cards actually sitting
		// off screen, so the glide can never run onto empty track.
		var spare = Math.max( 1, cardCount - perView );
		var projected = offset + ( velocity * THROW_MS );
		var glide = step * Math.min( 2, spare );
		var target = Math.max(
			Math.max( offset - glide, -spare * step ),
			Math.min( offset + glide, Math.min( 0, Math.round( projected / step ) * step ) )
		);

		animateTo( target );
	}

	viewport.addEventListener( 'pointerup', function ( event ) {
		endDrag( event, false );
	} );

	viewport.addEventListener( 'pointercancel', function ( event ) {
		endDrag( event, true );
	} );

	// Thumbnails are images, so a drag usually starts on one; without this the
	// browser begins a native image drag and swallows the gesture.
	viewport.addEventListener( 'dragstart', function ( event ) {
		event.preventDefault();
	} );

	// Capture phase, so a drag that ended on a card link never follows it.
	carousel.addEventListener(
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

	/* ------------------------------------------------------------------
	 * Width changes
	 * ------------------------------------------------------------------ */

	/*
	 * A ResizeObserver on the viewport rather than a debounced window listener:
	 * it fires for anything that changes the card width, including the container
	 * being resized without the window being touched, and it does not run on
	 * vertical-only resizes such as a mobile browser's toolbar sliding away.
	 */
	var resizeFrame = 0;
	var lastWidth = 0;

	function onResize() {
		if ( resizeFrame ) {
			return;
		}

		resizeFrame = window.requestAnimationFrame( function () {
			resizeFrame = 0;

			var width = viewport.getBoundingClientRect().width;

			if ( Math.abs( width - lastWidth ) < 1 ) {
				return;
			}

			lastWidth = width;
			stopAnimation();
			measure();

			// The pixel offset no longer lands on a card boundary at the new
			// width, so realign on the leading card.
			offset = 0;
			track.style.transition = 'none';
			render();
			void track.offsetWidth;
			track.style.transition = '';
			syncControls();
		} );
	}

	if ( 'ResizeObserver' in window ) {
		new window.ResizeObserver( onResize ).observe( viewport );
	} else {
		window.addEventListener( 'resize', onResize, { passive: true } );
	}

	measure();
	lastWidth = viewport.getBoundingClientRect().width;
	syncControls();
	announce();
	log( cardCount + ' posts, ' + perView + ' per view' );
}() );

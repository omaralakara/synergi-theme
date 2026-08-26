/*
 * numbers.js — the counting figures and the particle field in section 06.
 *
 * Loaded by inc/assets.php as "synergi-section-numbers", deferred, only on
 * pages that declare the section. Depends on the "synergi-main" handle.
 * Markup: sections/numbers.php. Styling: assets/css/sections/numbers.css.
 *
 * Two independent pieces, in this order because the first must run even if the
 * second cannot:
 *
 *   1. The four figures count up once, the first time they are scrolled into
 *      view. The final value is already in the markup, so this is decoration:
 *      with no script, no IntersectionObserver, or reduced motion asked for,
 *      the correct number is on screen and nothing happens.
 *   2. A drifting particle field on a <canvas> behind the copy. It draws at
 *      30fps and stops completely when the section is off screen, the tab is
 *      not being looked at, or reduced motion is asked for — an animation that
 *      keeps running unseen is one CLAUDE.md §6 names outright. Under reduced
 *      motion it still paints one static frame, because reduced motion means
 *      no movement, not a missing background.
 *
 * Ported from the [data-scale-section] block in design-source/assets/js/main.js.
 * Four things are done differently and each is commented where it happens: the
 * colours come from CSS, the count animates every number in the value rather
 * than one, the resize redraw is scheduled instead of run inside the observer,
 * and the unused dark colourway's branch is gone.
 */

( function () {
	'use strict';

	var root = document.querySelector( '[data-syn-numbers]' );

	if ( ! root ) {
		return;
	}

	var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	function log( message ) {
		if ( window.synDebug ) {
			// eslint-disable-next-line no-console
			console.log( '[synergi] numbers: ' + message );
		}
	}

	/* ------------------------------------------------------------------
	 * 1. The figures
	 * ------------------------------------------------------------------ */

	/**
	 * Counts one figure up to the value already written in the element.
	 *
	 * Every run of digits in the value is counted, not just one, so "10–15%"
	 * runs 0–0% → 10–15% instead of the source's 10–0% → 10–15%. That mattered:
	 * the source held a prefix fixed while the second number climbed, which put
	 * a backwards range ("10–7% direct savings") on screen for most of a second.
	 *
	 * The last frame writes the authored string back verbatim rather than a
	 * rebuilt one, so the figure always ends up exactly as it was typed.
	 *
	 * @param {HTMLElement} element The figure. Its textContent is the target.
	 * @return {void}
	 */
	function countUp( element ) {
		var finalText = element.textContent;
		var matches = finalText.match( /\d+/g );

		if ( ! matches ) {
			return;
		}

		/*
		 * One number written with a separator inside it — a grouped thousand
		 * ("1,200"), a decimal ("99.9%") — would be taken apart by the match
		 * above and counted as two, which puts a figure on screen that was
		 * never true. Those are shown as authored instead: a figure that does
		 * not count is a missing flourish, a figure that counts wrongly is a
		 * wrong number. Digits either side of an en dash or a slash ("10–15%",
		 * "24/7") are a genuine pair and still count.
		 */
		if ( /[0-9][ ,.'][0-9]/.test( finalText ) ) {
			log( 'not counting "' + finalText + '": grouped digits' );

			return;
		}

		var targets = matches.map( Number );
		var duration = 950;
		var started = 0;

		function frame( now ) {
			if ( ! started ) {
				started = now;
			}

			var progress = Math.min( ( now - started ) / duration, 1 );

			if ( progress >= 1 ) {
				element.textContent = finalText;

				return;
			}

			var eased = 1 - Math.pow( 1 - progress, 3 );
			var index = 0;

			element.textContent = finalText.replace( /\d+/g, function () {
				var value = Math.round( targets[ index ] * eased );

				index += 1;

				return String( value );
			} );

			window.requestAnimationFrame( frame );
		}

		window.requestAnimationFrame( frame );
	}

	var figures = Array.prototype.slice.call( root.querySelectorAll( '[data-syn-numbers-count]' ) );

	if ( figures.length && 'IntersectionObserver' in window && ! reducedMotion.matches ) {
		var figureObserver = new window.IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}

					// Once each: a figure that counted again on every scroll past
					// would read as a glitch, not as an effect.
					figureObserver.unobserve( entry.target );
					countUp( entry.target );
				} );
			},
			{ threshold: 0.4 }
		);

		figures.forEach( function ( figure ) {
			figureObserver.observe( figure );
		} );

		log( figures.length + ' figures ready' );
	}

	/* ------------------------------------------------------------------
	 * 2. The particle field
	 * ------------------------------------------------------------------ */

	var canvas = root.querySelector( '[data-syn-numbers-canvas]' );
	var context = canvas && canvas.getContext ? canvas.getContext( '2d', { alpha: true } ) : null;

	if ( ! context ) {
		return;
	}

	/**
	 * Resolves one of the section's colour custom properties to something the
	 * canvas is guaranteed to understand.
	 *
	 * The colours are declared in numbers.css as color-mix() from the §3
	 * palette, so they stay tokens rather than becoming two hexes buried in a
	 * script (CLAUDE.md §2.7). Reading the custom property directly would return
	 * the unresolved color-mix() text; setting it as an element's colour and
	 * reading the colour back is what resolves it. A browser that cannot parse
	 * the mix falls through to the section's own white, which still looks right.
	 *
	 * Side effect: appends and removes a hidden element, once per call, at
	 * start-up only.
	 *
	 * @param {string} property Custom property name, including the two dashes.
	 * @return {string} A colour string, e.g. "rgb(223, 244, 252)".
	 */
	function resolveColor( property ) {
		var probe = document.createElement( 'span' );

		probe.style.display = 'none';
		probe.style.color = 'var(' + property + ')';
		root.appendChild( probe );

		var value = window.getComputedStyle( probe ).color;

		root.removeChild( probe );

		return value;
	}

	var LINK_COLOR = resolveColor( '--syn-numbers-link-color' );
	var DOT_COLOR = resolveColor( '--syn-numbers-dot-color' );

	/*
	 * The source rebuilt an "rgba(r, g, b, a)" string for every link and every
	 * dot on every frame — around 1,400 string allocations at 30fps. The colour
	 * is set once here and the alpha varies through globalAlpha instead, which
	 * multiplies it. Same picture, no garbage.
	 */
	context.strokeStyle = LINK_COLOR;
	context.fillStyle = DOT_COLOR;
	context.lineWidth = 1;

	var FRAME_INTERVAL = 1000 / 30;
	var LINK_DISTANCE = 130;
	var LINK_DISTANCE_SQ = LINK_DISTANCE * LINK_DISTANCE;
	var LINK_ALPHA = 0.3;
	var DOT_ALPHA_SCALE = 1.55;

	var compact = window.matchMedia( '(max-width: 47.99rem)' );

	var canvasWidth = 1;
	var canvasHeight = 1;
	var particles = [];
	var frameId = 0;
	var resizeId = 0;
	var lastStep = 0;
	var onScreen = ! ( 'IntersectionObserver' in window );

	/**
	 * A repeatable pseudo-random number for one particle.
	 *
	 * Deterministic on purpose: the field looks identical on every load, so a
	 * screenshot taken today can be compared with one taken next month, and
	 * nothing here depends on Math.random().
	 *
	 * @param {number} index Particle number.
	 * @param {number} salt  Which property is being generated.
	 * @return {number} Between 0 and 1.
	 */
	function seeded( index, salt ) {
		var value = Math.sin( ( index + 1 ) * ( 12.9898 + salt * 17.17 ) ) * 43758.5453;

		return value - Math.floor( value );
	}

	function createParticles() {
		var count = compact.matches ? 28 : 52;
		var made = [];
		var index;

		for ( index = 0; index < count; index += 1 ) {
			made.push( {
				x: seeded( index, 1 ) * canvasWidth,
				y: seeded( index, 2 ) * canvasHeight,
				driftX: ( seeded( index, 3 ) - 0.5 ) * 14,
				driftY: ( seeded( index, 4 ) - 0.5 ) * 10,
				radius: 1.05 + seeded( index, 5 ) * 1.35,
				alpha: 0.28 + seeded( index, 6 ) * 0.42,
				pulse: seeded( index, 7 ) * Math.PI * 2,
			} );
		}

		particles = made;
	}

	/**
	 * Moves every particle on, wrapping it round the edges.
	 *
	 * @param {number} seconds Time since the last step.
	 * @return {void}
	 */
	function stepParticles( seconds ) {
		var margin = 14;

		particles.forEach( function ( particle ) {
			particle.x += particle.driftX * seconds;
			particle.y += particle.driftY * seconds;

			if ( particle.x < -margin ) {
				particle.x = canvasWidth + margin;
			} else if ( particle.x > canvasWidth + margin ) {
				particle.x = -margin;
			}

			if ( particle.y < -margin ) {
				particle.y = canvasHeight + margin;
			} else if ( particle.y > canvasHeight + margin ) {
				particle.y = -margin;
			}
		} );
	}

	/**
	 * Draws the whole field: the links between near neighbours first, the dots
	 * on top of them.
	 *
	 * @param {number} now Timestamp, used for the dots' slow twinkle.
	 * @return {void}
	 */
	function draw( now ) {
		var first;
		var second;

		context.clearRect( 0, 0, canvasWidth, canvasHeight );

		for ( first = 0; first < particles.length; first += 1 ) {
			for ( second = first + 1; second < particles.length; second += 1 ) {
				var deltaX = particles[ first ].x - particles[ second ].x;
				var deltaY = particles[ first ].y - particles[ second ].y;
				var distanceSq = ( deltaX * deltaX ) + ( deltaY * deltaY );

				if ( distanceSq >= LINK_DISTANCE_SQ ) {
					continue;
				}

				// Fainter the further apart they are, so the field reads as a
				// mesh rather than as a cage.
				context.globalAlpha = ( 1 - ( Math.sqrt( distanceSq ) / LINK_DISTANCE ) ) * LINK_ALPHA;
				context.beginPath();
				context.moveTo( particles[ first ].x, particles[ first ].y );
				context.lineTo( particles[ second ].x, particles[ second ].y );
				context.stroke();
			}
		}

		context.fillStyle = DOT_COLOR;

		particles.forEach( function ( particle ) {
			var twinkle = 0.82 + ( Math.sin( ( now * 0.0011 ) + particle.pulse ) * 0.18 );

			context.globalAlpha = Math.min( particle.alpha * twinkle * DOT_ALPHA_SCALE, 1 );
			context.beginPath();
			context.arc( particle.x, particle.y, particle.radius, 0, Math.PI * 2 );
			context.fill();
		} );

		context.globalAlpha = 1;
	}

	function shouldAnimate() {
		return onScreen && ! reducedMotion.matches && ! document.hidden;
	}

	function stopFrame() {
		if ( ! frameId ) {
			return;
		}

		window.cancelAnimationFrame( frameId );
		frameId = 0;
	}

	function scheduleFrame() {
		if ( frameId || ! shouldAnimate() ) {
			return;
		}

		lastStep = lastStep || window.performance.now();
		frameId = window.requestAnimationFrame( runFrame );
	}

	function runFrame( now ) {
		frameId = 0;

		if ( ! shouldAnimate() ) {
			return;
		}

		var elapsed = now - lastStep;

		// 30fps is plenty for a drift this slow, and halves the work on a
		// 60Hz screen (CLAUDE.md §6). The 120ms cap keeps a particle from
		// jumping the width of the section after the tab has been away.
		if ( elapsed >= FRAME_INTERVAL ) {
			lastStep = now - ( elapsed % FRAME_INTERVAL );
			stepParticles( Math.min( elapsed, 120 ) / 1000 );
			draw( now );
		}

		scheduleFrame();
	}

	/**
	 * Matches the backing store to the section's box and redraws.
	 *
	 * The particles are only rebuilt when the box has genuinely changed size —
	 * a mobile browser's toolbar sliding away must not reshuffle the field.
	 *
	 * @return {void}
	 */
	function resize() {
		var bounds = root.getBoundingClientRect();
		var density = Math.min( window.devicePixelRatio || 1, 1.5 );
		var nextWidth = Math.max( 1, Math.round( bounds.width ) );
		var nextHeight = Math.max( 1, Math.round( bounds.height ) );
		var changed = Math.abs( nextWidth - canvasWidth ) > 24 || Math.abs( nextHeight - canvasHeight ) > 24;

		canvasWidth = nextWidth;
		canvasHeight = nextHeight;

		// Capped at 1.5 rather than the full device ratio: a 3x phone would
		// otherwise paint nine times the pixels for a background texture.
		canvas.width = Math.round( nextWidth * density );
		canvas.height = Math.round( nextHeight * density );
		context.setTransform( density, 0, 0, density, 0, 0 );

		// Resetting the backing store clears the context's state with it.
		context.strokeStyle = LINK_COLOR;
		context.fillStyle = DOT_COLOR;
		context.lineWidth = 1;

		if ( changed || ! particles.length ) {
			createParticles();
		}

		draw( window.performance.now() );
	}

	/*
	 * The source resized and redrew inside the ResizeObserver callback, which
	 * puts canvas work in the middle of the browser's layout step. Here the
	 * observer only asks for a frame, and the work happens on it.
	 */
	function requestResize() {
		if ( resizeId ) {
			return;
		}

		resizeId = window.requestAnimationFrame( function () {
			resizeId = 0;
			resize();
		} );
	}

	if ( 'ResizeObserver' in window ) {
		new window.ResizeObserver( requestResize ).observe( root );
	} else {
		window.addEventListener( 'resize', requestResize, { passive: true } );
	}

	if ( 'IntersectionObserver' in window ) {
		new window.IntersectionObserver(
			function ( entries ) {
				onScreen = entries[ 0 ].isIntersecting;

				if ( onScreen ) {
					// Dropped rather than carried forward, so the field does not
					// lurch by however long it was off screen.
					lastStep = 0;
					scheduleFrame();
				} else {
					stopFrame();
				}
			},
			{ rootMargin: '80px 0px', threshold: 0.01 }
		).observe( root );
	}

	document.addEventListener( 'visibilitychange', function () {
		if ( document.hidden ) {
			stopFrame();
		} else {
			lastStep = 0;
			scheduleFrame();
		}
	} );

	reducedMotion.addEventListener( 'change', function () {
		if ( reducedMotion.matches ) {
			stopFrame();
			// One still frame: the texture stays, the movement goes.
			draw( window.performance.now() );
		} else {
			lastStep = 0;
			scheduleFrame();
		}
	} );

	// Fewer particles on a phone, and the count changes with the breakpoint
	// rather than only on load.
	compact.addEventListener( 'change', function () {
		createParticles();
		draw( window.performance.now() );
	} );

	resize();
	scheduleFrame();
	log( particles.length + ' particles ready' );
}() );

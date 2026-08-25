/*
 * hero.js — the typewriter on the hero's animated line.
 *
 * Loaded by inc/assets.php as "synergi-section-hero", deferred, only on pages
 * that declare the hero. Depends on the "synergi-main" handle.
 * Markup: sections/hero.php. Styling: assets/css/sections/hero.css.
 *
 * Ported from design-source/assets/js/main.js with the same timings. Two
 * changes: it is found by data attribute rather than class, and it stops when
 * the hero scrolls out of view — the original ran its setTimeout chain forever,
 * repainting text nobody was looking at for as long as the tab stayed open
 * (CLAUDE.md §6: animation pauses off-screen).
 *
 * With JavaScript off, or under prefers-reduced-motion, nothing here runs and
 * the line keeps the word sections/hero.php rendered — a complete sentence
 * either way.
 */

( function () {
	'use strict';

	var word = document.querySelector( '[data-syn-typewords]' );

	if ( ! word ) {
		return;
	}

	var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	var words = ( word.getAttribute( 'data-syn-typewords' ) || '' )
		.split( ',' )
		.map( function ( item ) {
			return item.trim();
		} )
		.filter( Boolean );

	// One word is not a cycle, and reduced motion means no cycle at all.
	if ( words.length < 2 || reducedMotion.matches ) {
		return;
	}

	// Timings are the design's, kept exactly: deleting is faster than typing,
	// and the pause on a completed word is the longest beat in the loop.
	var DELETE_MS = 46;
	var TYPE_MS = 68;
	var BEFORE_NEXT_MS = 220;
	var AFTER_COMPLETE_MS = 1350;
	var FIRST_RUN_MS = 1050;

	var wordIndex = 0;
	var charIndex = words[ 0 ].length;
	var deleting = true;
	var timer = 0;
	var visible = true;

	function schedule( delay ) {
		window.clearTimeout( timer );
		timer = window.setTimeout( tick, delay );
	}

	function tick() {
		if ( ! visible ) {
			return;
		}

		word.textContent = words[ wordIndex ].slice( 0, charIndex );

		if ( deleting ) {
			if ( charIndex > 0 ) {
				charIndex -= 1;
				schedule( DELETE_MS );
				return;
			}

			deleting = false;
			wordIndex = ( wordIndex + 1 ) % words.length;
			schedule( BEFORE_NEXT_MS );
			return;
		}

		if ( charIndex < words[ wordIndex ].length ) {
			charIndex += 1;
			schedule( TYPE_MS );
			return;
		}

		deleting = true;
		schedule( AFTER_COMPLETE_MS );
	}

	/*
	 * Pause off-screen. Without IntersectionObserver the loop simply runs, which
	 * is the old behaviour and still correct — just less considerate.
	 */
	if ( 'IntersectionObserver' in window ) {
		var hero = word.closest( '.syn-hero' ) || word;

		new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				var wasVisible = visible;
				visible = entry.isIntersecting;

				if ( visible && ! wasVisible ) {
					schedule( TYPE_MS );
				} else if ( ! visible ) {
					window.clearTimeout( timer );
				}
			} );
		} ).observe( hero );
	}

	// A tab in the background gets no animation frames worth spending either.
	document.addEventListener( 'visibilitychange', function () {
		if ( document.hidden ) {
			window.clearTimeout( timer );
		} else if ( visible ) {
			schedule( TYPE_MS );
		}
	} );

	schedule( FIRST_RUN_MS );
}() );

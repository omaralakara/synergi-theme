/*
 * capabilities.js — turns the service page's capability list into a tab set.
 * Contents: 1 setup · 2 selection · 3 keyboard · 4 boot.
 *
 * Enqueued by inc/assets.php as "synergi-section-capabilities", deferred, only
 * on pages that declare the section. Markup: sections/capabilities.php.
 * Styling: assets/css/sections/capabilities.css.
 *
 * PURELY ADDITIVE. The partial ships every capability visible and the tab rail
 * hidden; this file unhides the rail and hides all but one panel. If it never
 * runs, the page is a readable stacked list rather than a broken one
 * (CLAUDE.md §10). It therefore never creates content, only rearranges it.
 */
( function () {
	'use strict';

	/**
	 * Wires one explorer.
	 *
	 * @param {Element} root The [data-syn-capabilities] element.
	 */
	function setup( root ) {
		var rail = root.querySelector( '[data-syn-capabilities-tabs]' );
		var panels = Array.prototype.slice.call( root.querySelectorAll( '.syn-capabilities__panel' ) );
		var tabs = rail ? Array.prototype.slice.call( rail.querySelectorAll( '[role="tab"]' ) ) : [];

		// One capability is a list of one. A rail to choose between a single
		// option is furniture, so the section stays in its unenhanced shape.
		if ( ! rail || tabs.length < 2 || tabs.length !== panels.length ) {
			return;
		}

		rail.hidden = false;
		root.setAttribute( 'data-syn-enhanced', '' );

		/**
		 * Shows one panel and marks its tab selected.
		 *
		 * @param {number}  index    Which capability.
		 * @param {boolean} withFocus Whether to move focus to the tab.
		 */
		function select( index, withFocus ) {
			tabs.forEach( function ( tab, i ) {
				var on = i === index;

				tab.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				tab.tabIndex = on ? 0 : -1;
				panels[ i ].hidden = ! on;
			} );

			if ( withFocus ) {
				tabs[ index ].focus();
			}
		}

		tabs.forEach( function ( tab, index ) {
			tab.addEventListener( 'click', function () {
				select( index, false );
			} );

			tab.addEventListener( 'keydown', function ( event ) {
				var last = tabs.length - 1;
				var next = null;

				// Vertical rail, so Up and Down are the primary pair. Left and
				// Right are accepted too: the rail lies down on narrow screens
				// and a reader should not have to know which way it is facing.
				switch ( event.key ) {
					case 'ArrowUp':
					case 'ArrowLeft':
						next = index === 0 ? last : index - 1;
						break;
					case 'ArrowDown':
					case 'ArrowRight':
						next = index === last ? 0 : index + 1;
						break;
					case 'Home':
						next = 0;
						break;
					case 'End':
						next = last;
						break;
					default:
						return;
				}

				event.preventDefault();
				select( next, true );
			} );
		} );

		select( 0, false );

		if ( window.synDebug && window.console ) {
			window.console.log( '[synergi] capabilities: ' + tabs.length + ' capabilities wired' );
		}
	}

	function boot() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-syn-capabilities]' ), setup );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );

/*
 * offices.js — loads an office's map, and only when somebody asks for it.
 *
 * Loaded by inc/assets.php as "synergi-section-offices", deferred, only on
 * pages that declare the section. Depends on the "synergi-main" handle.
 * Markup: sections/offices.php. Styling: assets/css/sections/offices.css.
 *
 * This is the entire behaviour of the band: click the button, an iframe is
 * built from the address already on the card and inserted above it. Nothing
 * reaches Google before that click, which is what keeps CLAUDE.md §2.6 true on
 * a page that shows five maps — the theme makes no external request of its own,
 * and the several hundred kilobytes a Maps embed weighs are never spent on a
 * reader who did not want one.
 *
 * The button is replaced rather than left in place: once the map is on screen
 * the button has nothing left to do, and a control that does nothing is worse
 * than no control. The "Open in Google Maps" link stays, because a map in a
 * 15rem box is not a substitute for directions.
 *
 * Rules: vanilla JS only, no jQuery, no libraries, no build step. Debug logging
 * is gated on window.synDebug, which inc/assets.php sets from SYN_DEBUG.
 */

( function () {
	'use strict';

	var maps = Array.prototype.slice.call( document.querySelectorAll( '[data-syn-office-map]' ) );

	if ( ! maps.length ) {
		return;
	}

	function log( message ) {
		if ( window.synDebug ) {
			// eslint-disable-next-line no-console
			console.log( '[synergi] offices: ' + message );
		}
	}

	/**
	 * Swaps the button for the map it describes.
	 *
	 * @param {HTMLElement} map    The [data-syn-office-map] wrapper.
	 * @param {HTMLElement} button The button that was pressed.
	 * @return {void}
	 */
	function show( map, button ) {
		var query = map.getAttribute( 'data-syn-map-query' ) || '';
		var title = map.getAttribute( 'data-syn-map-title' ) || 'Map';

		if ( ! query ) {
			return;
		}

		var frame = document.createElement( 'div' );
		frame.className = 'syn-offices__map-frame';

		var iframe = document.createElement( 'iframe' );

		/*
		 * The embed endpoint, which needs no API key. encodeURIComponent rather
		 * than string concatenation of a raw address: an address contains
		 * commas, spaces and the occasional ampersand, and one unescaped
		 * ampersand silently truncates the query.
		 */
		iframe.src = 'https://www.google.com/maps?q=' + encodeURIComponent( query ) + '&output=embed';
		iframe.title = title;
		iframe.loading = 'lazy';
		iframe.referrerPolicy = 'no-referrer-when-downgrade';
		iframe.allowFullscreen = true;

		frame.appendChild( iframe );
		map.insertBefore( frame, map.firstChild );

		button.parentNode.removeChild( button );

		/*
		 * Focus would otherwise be left on an element that no longer exists,
		 * which drops a keyboard user back to the top of the document. The
		 * frame takes it instead, so the next Tab continues from the map.
		 */
		frame.setAttribute( 'tabindex', '-1' );
		frame.focus();

		log( 'loaded the map for "' + query + '"' );
	}

	maps.forEach( function ( map ) {
		var button = map.querySelector( '[data-syn-map-show]' );

		if ( ! button ) {
			return;
		}

		button.addEventListener( 'click', function () {
			show( map, button );
		} );
	} );

	log( maps.length + ' offices ready' );
}() );

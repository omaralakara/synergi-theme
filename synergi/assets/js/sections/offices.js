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
 * The button stays and becomes the way back out (2 Sep 2026 — it used to
 * remove itself, which left an open map nobody could close). Closing hides the
 * frame rather than destroying it, so reopening costs nothing: the embed was
 * already paid for the moment it was first asked for. The "Open in Google
 * Maps" link stays throughout, because a map in a 15rem box is not a
 * substitute for directions.
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
	 * Builds the map frame the first time it is asked for.
	 *
	 * @param {HTMLElement} map The [data-syn-office-map] wrapper.
	 * @return {HTMLElement|null} The frame, or null when there is no query.
	 */
	function build( map ) {
		var query = map.getAttribute( 'data-syn-map-query' ) || '';
		var title = map.getAttribute( 'data-syn-map-title' ) || 'Map';

		if ( ! query ) {
			return null;
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

		log( 'loaded the map for "' + query + '"' );

		return frame;
	}

	maps.forEach( function ( map ) {
		var button = map.querySelector( '[data-syn-map-show]' );

		if ( ! button ) {
			return;
		}

		var frame     = null;
		var showLabel = button.textContent;
		var hideLabel = map.getAttribute( 'data-syn-map-hide' ) || 'Hide map';

		button.addEventListener( 'click', function () {
			if ( ! frame ) {
				frame = build( map );

				if ( ! frame ) {
					return;
				}
			} else {
				frame.hidden = ! frame.hidden;
			}

			var open = ! frame.hidden;

			button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			button.textContent = open ? hideLabel : showLabel;

			/*
			 * On open, focus moves to the frame so the next Tab continues from
			 * the map. On close it simply stays on the button that was pressed —
			 * which is also where "show" now points again.
			 */
			if ( open ) {
				frame.setAttribute( 'tabindex', '-1' );
				frame.focus();
			}
		} );
	} );

	log( maps.length + ' offices ready' );
}() );

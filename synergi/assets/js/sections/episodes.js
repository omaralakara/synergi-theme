/*
 * episodes.js — swaps a poster for its player, and only when asked.
 *
 * Loaded by inc/assets.php as "synergi-section-episodes", deferred, only on
 * pages that declare the section. Depends on the "synergi-main" handle.
 * Markup: sections/episodes.php. Styling: assets/css/sections/episodes.css.
 *
 * The same shape as assets/js/sections/offices.js, which does this for a map:
 * a button, an iframe built on the click, and nothing reaching the third party
 * before it. The two are kept separate rather than merged because they share
 * only their shape — different providers, different URLs, different failure
 * modes — and a "load some iframe" helper covering both would be harder to read
 * than either (CLAUDE.md §13, boring beats clever).
 *
 * Seven players on one page is several megabytes and seven external requests
 * before anyone has watched anything. This is what stops that being the cost of
 * opening the podcast page.
 *
 * Rules: vanilla JS only, no jQuery, no libraries, no build step. Debug logging
 * is gated on window.synDebug, which inc/assets.php sets from SYN_DEBUG.
 */

( function () {
	'use strict';

	var cards = Array.prototype.slice.call( document.querySelectorAll( '[data-syn-episode]' ) );

	if ( ! cards.length ) {
		return;
	}

	function log( message ) {
		if ( window.synDebug ) {
			// eslint-disable-next-line no-console
			console.log( '[synergi] episodes: ' + message );
		}
	}

	/**
	 * Replaces a card's poster with its player and starts it.
	 *
	 * @param {HTMLElement} card  The [data-syn-episode] article.
	 * @param {HTMLElement} frame The box the poster fills.
	 * @return {void}
	 */
	function play( card, frame ) {
		var id = card.getAttribute( 'data-syn-episode-id' ) || '';

		if ( ! id ) {
			return;
		}

		var iframe = document.createElement( 'iframe' );

		/*
		 * youtube-nocookie.com, not youtube.com: the same video and the same
		 * embed, on the host that does not set tracking cookies until playback
		 * begins. autoplay=1 because the person just pressed play — asking them
		 * to press it twice would be the wrong kind of faithful.
		 */
		iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent( id ) + '?autoplay=1&rel=0';
		iframe.title = card.getAttribute( 'data-syn-episode-title' ) || 'Video';
		iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
		iframe.referrerPolicy = 'strict-origin-when-cross-origin';
		iframe.allowFullscreen = true;

		// Everything the poster was — the picture, the scrim and the button —
		// goes at once, so the player is not layered over a still frame.
		frame.textContent = '';
		frame.appendChild( iframe );

		/*
		 * Focus was on the button, which no longer exists. Moving it to the
		 * player keeps a keyboard user where they were rather than dropping
		 * them back to the top of the document.
		 */
		iframe.setAttribute( 'tabindex', '-1' );
		iframe.focus();

		log( 'playing ' + id );
	}

	cards.forEach( function ( card ) {
		var frame  = card.querySelector( '[data-syn-episode-frame]' );
		var button = card.querySelector( '[data-syn-episode-play]' );

		if ( ! frame || ! button ) {
			return;
		}

		button.addEventListener( 'click', function () {
			play( card, frame );
		} );
	} );

	log( cards.length + ' videos ready' );
}() );

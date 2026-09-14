/*
 * consent.js — the cookie consent banner: shows it to a visitor who has not
 * chosen, stores the choice, and tells the Google tags about it.
 *
 * Loaded by inc/assets.php as the "synergi-consent" handle, deferred, in the
 * footer, with no dependencies. Markup: parts/consent.php and the footer's
 * [data-syn-consent-open] button. Styling: assets/css/parts/consent.css.
 *
 * The Consent Mode default and the returning-visitor update are NOT here: they
 * are printed inline at the top of <head> by inc/consent.php, because this
 * file runs after the tags have already started.
 *
 * Rules: vanilla JS only, no jQuery, no libraries, no build step. Debug logging
 * is gated on window.synDebug (CLAUDE.md §13).
 */

( function () {
	'use strict';

	var banner = document.querySelector( '[data-syn-consent]' );

	if ( ! banner ) {
		return;
	}

	var cookieName = banner.getAttribute( 'data-syn-consent-cookie' ) || 'syn_consent';
	var maxAge = parseInt( banner.getAttribute( 'data-syn-consent-max-age' ), 10 ) || 15724800;
	var choices = banner.querySelector( '[data-syn-consent-choices]' );
	var manageButton = banner.querySelector( '[data-syn-consent-action="manage"]' );
	var saveButton = banner.querySelector( '[data-syn-consent-action="save"]' );
	var boxes = Array.prototype.slice.call(
		banner.querySelectorAll( '[data-syn-consent-category]' )
	);
	var openers = Array.prototype.slice.call(
		document.querySelectorAll( '[data-syn-consent-open]' )
	);

	/*
	 * Each category's cookies, removed when that category is refused. Denying
	 * consent stops Google setting new ones, but does nothing about the cookies a
	 * visitor who accepted last month already carries — withdrawing has to be as
	 * real as giving.
	 */
	var cookiePrefixes = {
		analytics: [ '_ga' ],
		marketing: [ '_gcl' ]
	};

	var returnFocusTo = null;

	function log( message ) {
		if ( window.synDebug ) {
			// eslint-disable-next-line no-console
			console.log( '[synergi] consent: ' + message );
		}
	}

	/*
	 * The same shim inc/consent.php prints. gtag() must push the arguments
	 * object itself, not an array copy — Google's tags ignore arrays.
	 */
	function gtag() {
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push( arguments );
	}

	/* ------------------------------------------------------------------
	 * Stored choice
	 * ------------------------------------------------------------------ */

	/**
	 * Reads the stored choice.
	 *
	 * @return {?Object} { analytics: boolean, marketing: boolean }, or null when
	 *                   the visitor has not chosen yet.
	 */
	function readChoice() {
		var match = document.cookie.match( new RegExp( '(?:^|; )' + cookieName + '=([^;]*)' ) );

		if ( ! match ) {
			return null;
		}

		return {
			analytics: match[ 1 ].indexOf( 'analytics:granted' ) !== -1,
			marketing: match[ 1 ].indexOf( 'marketing:granted' ) !== -1
		};
	}

	function writeChoice( choice ) {
		var value = [ 'analytics', 'marketing' ]
			.map( function ( category ) {
				return category + ':' + ( choice[ category ] ? 'granted' : 'denied' );
			} )
			.join( '|' );

		document.cookie =
			cookieName + '=' + value +
			'; path=/; max-age=' + maxAge + '; SameSite=Lax' +
			( window.location.protocol === 'https:' ? '; Secure' : '' );
	}

	/**
	 * Expires every cookie whose name starts with one of the prefixes.
	 *
	 * Google sets its cookies on the registrable domain (".synergi.ae"), not the
	 * exact host, and a cookie can only be removed with the domain it was set
	 * with — so each is expired on the host and on every parent domain.
	 *
	 * @param {string[]} prefixes Cookie name prefixes, e.g. [ '_ga' ].
	 * @return {void}
	 */
	function clearCookies( prefixes ) {
		var parts = window.location.hostname.split( '.' );
		var domains = [ '' ];

		for ( var i = 0; i < parts.length - 1; i++ ) {
			domains.push( '; domain=.' + parts.slice( i ).join( '.' ) );
		}

		document.cookie.split( '; ' ).forEach( function ( pair ) {
			var name = pair.split( '=' )[ 0 ];

			var matches = prefixes.some( function ( prefix ) {
				return name.indexOf( prefix ) === 0;
			} );

			if ( ! matches ) {
				return;
			}

			domains.forEach( function ( domain ) {
				document.cookie = name + '=; path=/; max-age=0' + domain;
			} );

			log( 'removed cookie ' + name );
		} );
	}

	/**
	 * Stores a choice and applies it to the page.
	 *
	 * The dataLayer event is for a GTM container, if one is ever set
	 * (inc/integrations.php): a tag that is not Google's own can fire on
	 * syn_consent_update instead of reading Consent Mode.
	 *
	 * @param {Object} choice { analytics: boolean, marketing: boolean }
	 * @return {void}
	 */
	function applyChoice( choice ) {
		var analytics = choice.analytics ? 'granted' : 'denied';
		var marketing = choice.marketing ? 'granted' : 'denied';

		writeChoice( choice );

		gtag( 'consent', 'update', {
			analytics_storage: analytics,
			ad_storage: marketing,
			ad_user_data: marketing,
			ad_personalization: marketing
		} );

		window.dataLayer.push( {
			event: 'syn_consent_update',
			syn_consent_analytics: analytics,
			syn_consent_marketing: marketing
		} );

		Object.keys( cookiePrefixes ).forEach( function ( category ) {
			if ( ! choice[ category ] ) {
				clearCookies( cookiePrefixes[ category ] );
			}
		} );

		log( 'analytics=' + analytics + ', marketing=' + marketing );
	}

	/* ------------------------------------------------------------------
	 * Banner
	 * ------------------------------------------------------------------ */

	/*
	 * "Manage choices" steps aside once the choices are open: it has done its
	 * job, and "Save choices" takes its place in the row rather than pushing it
	 * onto a line of its own. Whoever opens the choices therefore moves focus
	 * into them, so it is never left on a button that just disappeared.
	 */
	function setChoicesOpen( open ) {
		choices.hidden = ! open;
		saveButton.hidden = ! open;
		manageButton.hidden = open;
		manageButton.setAttribute( 'aria-expanded', String( open ) );

		if ( ! open ) {
			return;
		}

		// Tick the boxes to match what is stored, so reopening shows the truth.
		var stored = readChoice() || { analytics: false, marketing: false };

		boxes.forEach( function ( box ) {
			box.checked = !! stored[ box.getAttribute( 'data-syn-consent-category' ) ];
		} );
	}

	/**
	 * Shows the banner.
	 *
	 * On a first visit it appears without taking focus: it is not a modal, and
	 * pulling a keyboard user away from the page they came to read is worse than
	 * the banner waiting its turn. Opened from "Cookie settings" it is the thing
	 * the visitor just asked for, so focus goes straight to the choices.
	 *
	 * @param {?HTMLElement} opener The button that opened it, or null.
	 * @return {void}
	 */
	function show( opener ) {
		returnFocusTo = opener;
		banner.hidden = false;

		// One frame at the hidden starting position, or the entrance transition
		// has nothing to transition from.
		window.requestAnimationFrame( function () {
			banner.classList.add( 'syn-is-visible' );
		} );

		if ( opener ) {
			setChoicesOpen( true );
			boxes[ 0 ].focus();
		}
	}

	function hide() {
		banner.classList.remove( 'syn-is-visible' );
		banner.hidden = true;
		setChoicesOpen( false );

		if ( returnFocusTo ) {
			returnFocusTo.focus();
			returnFocusTo = null;
		}
	}

	banner.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-syn-consent-action]' );

		if ( ! button ) {
			return;
		}

		var action = button.getAttribute( 'data-syn-consent-action' );

		if ( action === 'manage' ) {
			setChoicesOpen( true );
			boxes[ 0 ].focus();
			return;
		}

		var choice = { analytics: action === 'accept', marketing: action === 'accept' };

		if ( action === 'save' ) {
			boxes.forEach( function ( box ) {
				choice[ box.getAttribute( 'data-syn-consent-category' ) ] = box.checked;
			} );
		}

		applyChoice( choice );
		hide();
	} );

	/*
	 * Escape closes the banner only when a choice already exists — that is,
	 * when it was reopened from the footer. A first visit has to end in an
	 * answer, or the visitor would be asked again on every page.
	 */
	banner.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' && readChoice() ) {
			hide();
		}
	} );

	// The footer button is printed hidden, because without this script it
	// would be a control that does nothing.
	openers.forEach( function ( opener ) {
		opener.hidden = false;

		opener.addEventListener( 'click', function () {
			show( opener );
		} );
	} );

	if ( ! readChoice() ) {
		show( null );
	}
}() );

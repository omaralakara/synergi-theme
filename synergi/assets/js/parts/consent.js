/*
 * consent.js — the cookie consent dialog: opens it for a visitor who has not
 * chosen, stores the choice, tells the Google tags about it, and loads the
 * LinkedIn Insight Tag only when Marketing is allowed.
 *
 * Loaded by inc/assets.php as the "synergi-consent" handle, deferred, in the
 * footer, with no dependencies. Markup: parts/consent.php and the footer's
 * [data-syn-consent-open] button. Styling: assets/css/parts/consent.css.
 *
 * The Google Consent Mode default and the returning-visitor update are NOT
 * here: they are printed inline at the top of <head> by inc/consent.php,
 * because this file runs after the Google tags have already started. LinkedIn
 * is the opposite case — it has no consent mode, so it is not on the page at
 * all until this file adds it.
 *
 * Rules: vanilla JS only, no jQuery, no libraries, no build step. Debug logging
 * is gated on window.synDebug (CLAUDE.md §13).
 */

( function () {
	'use strict';

	var dialog = document.querySelector( '[data-syn-consent]' );

	if ( ! dialog ) {
		return;
	}

	var cookieName = dialog.getAttribute( 'data-syn-consent-cookie' ) || 'syn_consent';
	var maxAge = parseInt( dialog.getAttribute( 'data-syn-consent-max-age' ), 10 ) || 15724800;
	var linkedInPartner = dialog.getAttribute( 'data-syn-linkedin-partner' );
	var choices = dialog.querySelector( '[data-syn-consent-choices]' );
	var manageButton = dialog.querySelector( '[data-syn-consent-action="manage"]' );
	var saveButton = dialog.querySelector( '[data-syn-consent-action="save"]' );
	var boxes = Array.prototype.slice.call(
		dialog.querySelectorAll( '[data-syn-consent-category]' )
	);
	var openers = Array.prototype.slice.call(
		document.querySelectorAll( '[data-syn-consent-open]' )
	);

	/*
	 * Each category's first-party cookies, removed when that category is
	 * refused. Denying consent stops new ones being set, but does nothing about
	 * the cookies a visitor who accepted last month already carries —
	 * withdrawing has to be as real as giving. LinkedIn's own cookies live on
	 * linkedin.com, which no script on this site can reach; its tag not loading
	 * is what stops them.
	 */
	var cookiePrefixes = {
		analytics: [ '_ga' ],
		marketing: [ '_gcl', 'li_' ]
	};

	var returnFocusTo = null;
	var linkedInLoaded = false;

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

	/* ------------------------------------------------------------------
	 * Tags
	 * ------------------------------------------------------------------ */

	/**
	 * Adds the LinkedIn Insight Tag to the page, once.
	 *
	 * LinkedIn's standard snippet, as it ran in ASE snippet 9378, minus its
	 * <noscript> pixel: a visitor without JavaScript can never consent, so the
	 * pixel has no lawful case left. Does nothing when no partner ID is
	 * configured (inc/integrations.php).
	 *
	 * A tag already loaded cannot be unloaded. A visitor who withdraws
	 * Marketing keeps it until the next page, which then never adds it.
	 *
	 * @return {void}
	 */
	function loadLinkedIn() {
		if ( ! linkedInPartner || linkedInLoaded ) {
			return;
		}

		linkedInLoaded = true;

		window._linkedin_partner_id = linkedInPartner;
		window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
		window._linkedin_data_partner_ids.push( linkedInPartner );

		if ( ! window.lintrk ) {
			window.lintrk = function ( a, b ) {
				window.lintrk.q.push( [ a, b ] );
			};
			window.lintrk.q = [];
		}

		var script = document.createElement( 'script' );
		script.async = true;
		script.src = 'https://snap.licdn.com/li.lms-analytics/insight.min.js';
		document.head.appendChild( script );

		log( 'LinkedIn Insight Tag loaded for partner ' + linkedInPartner );
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

		if ( choice.marketing ) {
			loadLinkedIn();
		}

		Object.keys( cookiePrefixes ).forEach( function ( category ) {
			if ( ! choice[ category ] ) {
				clearCookies( cookiePrefixes[ category ] );
			}
		} );

		log( 'analytics=' + analytics + ', marketing=' + marketing );
	}

	/* ------------------------------------------------------------------
	 * Dialog
	 * ------------------------------------------------------------------ */

	/*
	 * "Manage choices" steps aside once the choices are open: it has done its
	 * job, and "Save choices" takes its place in the row. Whoever opens the
	 * choices therefore moves focus into them, so it is never left on a button
	 * that just disappeared.
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
	 * Opens the dialog.
	 *
	 * On a first visit focus goes to the dialog itself (it carries
	 * tabindex="-1"), so the question is read before any answer. Opened from
	 * "Cookie settings" it is the thing the visitor just asked for, so focus
	 * goes straight to the choices.
	 *
	 * @param {?HTMLElement} opener The button that opened it, or null.
	 * @return {void}
	 */
	function open( opener ) {
		returnFocusTo = opener;

		if ( ! dialog.open ) {
			dialog.showModal();
		}

		document.documentElement.classList.add( 'syn-consent-open' );

		if ( opener ) {
			setChoicesOpen( true );
			boxes[ 0 ].focus();
		} else {
			dialog.focus();
		}
	}

	// Every way the dialog closes — a decision, or Escape on a reopened one —
	// ends here, so the page is always handed back in the same state.
	dialog.addEventListener( 'close', function () {
		document.documentElement.classList.remove( 'syn-consent-open' );
		setChoicesOpen( false );

		if ( returnFocusTo ) {
			returnFocusTo.focus();
			returnFocusTo = null;
		}
	} );

	/*
	 * Escape closes the dialog only when a choice already exists — that is,
	 * when it was reopened from the footer. A first visit has to end in an
	 * answer. (Browsers may let a second Escape through regardless; the visitor
	 * is then simply asked again on the next page, with nothing set.)
	 */
	dialog.addEventListener( 'cancel', function ( event ) {
		if ( ! readChoice() ) {
			event.preventDefault();
		}
	} );

	dialog.addEventListener( 'click', function ( event ) {
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
		dialog.close();
	} );

	// The footer button is printed hidden, because without this script it
	// would be a control that does nothing.
	openers.forEach( function ( opener ) {
		opener.hidden = false;

		opener.addEventListener( 'click', function () {
			open( opener );
		} );
	} );

	var stored = readChoice();

	if ( ! stored ) {
		open( null );
	} else if ( stored.marketing ) {
		loadLinkedIn();
	}
}() );

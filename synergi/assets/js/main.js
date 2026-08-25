/*
 * main.js — shared front-end behaviour: the header's scrolled state, the
 * narrow-screen navigation panel, and the submenu toggles.
 *
 * Loaded by inc/assets.php as the "synergi-main" handle, deferred, in the
 * footer. Every section script (Stage 5) depends on this handle, so helpers
 * defined here are available to them.
 *
 * Markup: header.php, parts/nav.php, inc/nav.php.
 * Styling: assets/css/parts/header.css.
 *
 * Ported from design-source/assets/js/main.js. Two deliberate changes:
 *   - The breakpoint listener at the end is new. The original set the panel's
 *     inert state on load and on toggle but never on resize, so a viewport
 *     dragged from narrow to wide while the menu was closed left the whole
 *     desktop navigation inert — present, styled, and unusable.
 *   - Elements are found by data attribute rather than by class, so renaming a
 *     class in header.css cannot silently disable the navigation.
 *
 * Rules: vanilla JS only, no jQuery, no libraries, no build step. Debug logging
 * is gated on window.synDebug, which inc/assets.php sets from SYN_DEBUG — no
 * console output ever reaches production (CLAUDE.md §13).
 */

( function () {
	'use strict';

	var header = document.querySelector( '[data-syn-header]' );
	var menuToggle = document.querySelector( '.syn-menu-toggle' );
	var navigation = document.getElementById( 'syn-primary-nav' );
	var submenuToggles = Array.prototype.slice.call(
		document.querySelectorAll( '.syn-submenu-toggle' )
	);

	// Must match the breakpoint header.css switches the nav to a panel at.
	var narrowScreen = window.matchMedia( '(max-width: 74rem)' );
	var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	function log( message ) {
		if ( window.synDebug ) {
			// eslint-disable-next-line no-console
			console.log( '[synergi] ' + message );
		}
	}

	if ( ! header ) {
		log( 'no [data-syn-header] on this page; header behaviour skipped' );
	}

	/* ------------------------------------------------------------------
	 * Header scrolled state
	 * ------------------------------------------------------------------ */

	function setHeaderState() {
		if ( ! header ) {
			return;
		}

		header.classList.toggle( 'syn-is-scrolled', window.scrollY > 24 );
	}

	window.addEventListener( 'scroll', setHeaderState, { passive: true } );
	setHeaderState();

	/* ------------------------------------------------------------------
	 * Submenus
	 * ------------------------------------------------------------------ */

	function closeSubmenus( except ) {
		submenuToggles.forEach( function ( toggle ) {
			if ( toggle === except ) {
				return;
			}

			toggle.setAttribute( 'aria-expanded', 'false' );

			var parent = toggle.closest( '.syn-has-submenu' );

			if ( parent ) {
				parent.classList.remove( 'syn-is-open' );
			}
		} );
	}

	submenuToggles.forEach( function ( toggle ) {
		toggle.addEventListener( 'click', function () {
			var isOpen = toggle.getAttribute( 'aria-expanded' ) === 'true';

			closeSubmenus( toggle );
			toggle.setAttribute( 'aria-expanded', String( ! isOpen ) );

			var parent = toggle.closest( '.syn-has-submenu' );

			if ( parent ) {
				parent.classList.toggle( 'syn-is-open', ! isOpen );
			}
		} );
	} );

	// A click anywhere outside a submenu closes whichever one is open.
	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest( '.syn-has-submenu' ) ) {
			closeSubmenus();
		}
	} );

	/*
	 * Tabbing out of an open submenu closes it.
	 *
	 * header.css used to open submenus on :focus-within, which handled this for
	 * free but also fought the button — see the note in its section 5. Now that
	 * .syn-is-open is the only thing that opens a submenu, closing on focus
	 * leaving is this script's job, or a keyboard user tabs past an open panel
	 * that never shuts.
	 *
	 * relatedTarget is where focus is going. It is null when focus leaves the
	 * document entirely (another window, the browser chrome), and closing in
	 * that case would be wrong — the panel should still be there on return.
	 */
	document.addEventListener( 'focusout', function ( event ) {
		var item = event.target.closest( '.syn-has-submenu' );

		if ( ! item || ! event.relatedTarget ) {
			return;
		}

		if ( ! item.contains( event.relatedTarget ) ) {
			closeSubmenus();
		}
	} );

	/* ------------------------------------------------------------------
	 * Narrow-screen navigation panel
	 * ------------------------------------------------------------------ */

	/**
	 * Takes the panel out of the tab order while it is off-screen.
	 *
	 * Without this the links are still focusable when the panel is closed, so a
	 * keyboard user tabs through an invisible menu. inert also hides them from
	 * assistive technology, which visibility:hidden alone does not guarantee
	 * during the transition.
	 */
	function syncNavigationInteractivity() {
		if ( ! navigation || ! menuToggle ) {
			return;
		}

		navigation.inert =
			narrowScreen.matches &&
			menuToggle.getAttribute( 'aria-expanded' ) !== 'true';
	}

	/*
	 * Both strings come off the button as data attributes rather than living in
	 * this file, because a string written here could never be translated —
	 * the theme ships no script translations and CLAUDE.md §12 requires every
	 * user-facing string to go through the "synergi" text domain.
	 */
	function setMenuLabel( isOpen ) {
		var label = menuToggle.querySelector( '[data-syn-menu-label]' );

		if ( ! label ) {
			return;
		}

		var next = isOpen
			? menuToggle.getAttribute( 'data-syn-label-close' )
			: menuToggle.getAttribute( 'data-syn-label-open' );

		if ( next ) {
			label.textContent = next;
		}
	}

	function closeMenu() {
		if ( ! menuToggle || ! navigation ) {
			return;
		}

		menuToggle.setAttribute( 'aria-expanded', 'false' );
		setMenuLabel( false );
		navigation.classList.remove( 'syn-is-open' );

		if ( header ) {
			header.classList.remove( 'syn-is-menu-open' );
		}

		document.body.classList.remove( 'syn-menu-open' );
		closeSubmenus();
		syncNavigationInteractivity();
	}

	if ( menuToggle && navigation ) {
		menuToggle.addEventListener( 'click', function () {
			var willOpen = menuToggle.getAttribute( 'aria-expanded' ) !== 'true';

			menuToggle.setAttribute( 'aria-expanded', String( willOpen ) );
			setMenuLabel( willOpen );
			navigation.classList.toggle( 'syn-is-open', willOpen );

			if ( header ) {
				header.classList.toggle( 'syn-is-menu-open', willOpen );
			}

			document.body.classList.toggle( 'syn-menu-open', willOpen );
			syncNavigationInteractivity();

			if ( ! willOpen ) {
				return;
			}

			// Focus waits for the panel to finish arriving, because focusing a
			// still-transitioning element scrolls it into view mid-animation.
			var focusDelay = reducedMotion.matches ? 0 : 320;

			window.setTimeout( function () {
				var first = navigation.querySelector( 'a, button' );

				if ( first ) {
					first.focus();
				}
			}, focusDelay );
		} );

		// Tapping a destination closes the panel. Submenu toggles are buttons,
		// not links, so they are not caught here and stay open as you drill in.
		navigation.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( 'a' ) ) {
				closeMenu();
			}
		} );
	}

	/* ------------------------------------------------------------------
	 * Keyboard
	 * ------------------------------------------------------------------ */

	document.addEventListener( 'keydown', function ( event ) {
		var menuIsOpen =
			!! menuToggle && menuToggle.getAttribute( 'aria-expanded' ) === 'true';

		if ( event.key === 'Escape' ) {
			closeSubmenus();

			if ( menuIsOpen ) {
				closeMenu();
				menuToggle.focus();
			}

			return;
		}

		// While the panel covers the screen, Tab must not reach the page behind
		// it. inert handles the reverse case; this handles the open case.
		if ( event.key !== 'Tab' || ! menuIsOpen || ! narrowScreen.matches ) {
			return;
		}

		var focusable = [ menuToggle ]
			.concat(
				Array.prototype.slice.call( navigation.querySelectorAll( 'a, button' ) )
			)
			.filter( function ( element ) {
				return element.getClientRects().length > 0 && ! element.disabled;
			} );

		if ( ! focusable.length ) {
			return;
		}

		var first = focusable[ 0 ];
		var last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	} );

	/* ------------------------------------------------------------------
	 * Breakpoint changes
	 * ------------------------------------------------------------------ */

	/*
	 * Crossing the breakpoint has to reset the panel. Going wide with the menu
	 * open would leave the body unscrollable and the header stuck in its
	 * menu-open colour; going wide with it closed would leave the navigation
	 * inert, which is the bug this fixes in the design's original script.
	 */
	narrowScreen.addEventListener( 'change', function ( event ) {
		if ( ! event.matches ) {
			closeMenu();
		}

		syncNavigationInteractivity();
		log( 'breakpoint change: narrow=' + event.matches );
	} );

	syncNavigationInteractivity();
}() );

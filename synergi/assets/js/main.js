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
	var submenuItems = Array.prototype.slice.call(
		document.querySelectorAll( '.syn-has-submenu' )
	);

	// Must match the breakpoint header.css switches the nav to a panel at.
	var narrowScreen = window.matchMedia( '(max-width: 74rem)' );
	var hoverCapable = window.matchMedia( '(hover: hover)' );
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

	/**
	 * Whether this pointer opens submenus by hovering rather than by tapping.
	 *
	 * Both halves matter. (hover: hover) keeps touch devices out — a tablet in
	 * landscape is wider than the breakpoint but only emulates hover for a
	 * single frame, so a hover-driven menu would be unopenable there. The width
	 * check keeps a narrow desktop window on the tap path, because below the
	 * breakpoint the nav is a full-screen panel, not a bar.
	 *
	 * @return {boolean}
	 */
	function usesHoverMenus() {
		return hoverCapable.matches && ! narrowScreen.matches;
	}

	submenuToggles.forEach( function ( toggle ) {
		toggle.addEventListener( 'click', function () {
			/*
			 * On a hovering pointer the panel is already open — hover opened it
			 * before the click could land, and header.css governs it from there.
			 * Toggling a class here would fight :hover and produce the bug this
			 * replaced: a chevron that spins while the panel does not move.
			 * Keyboard users reach the same open state through :focus-within.
			 */
			if ( usesHoverMenus() ) {
				return;
			}

			var isOpen = toggle.getAttribute( 'aria-expanded' ) === 'true';

			closeSubmenus( toggle );
			toggle.setAttribute( 'aria-expanded', String( ! isOpen ) );

			var parent = toggle.closest( '.syn-has-submenu' );

			if ( parent ) {
				parent.classList.toggle( 'syn-is-open', ! isOpen );
			}
		} );
	} );

	/*
	 * Keep aria-expanded truthful while hover is doing the opening.
	 *
	 * CSS opens the panel on its own, but a screen reader only knows what the
	 * attribute says — without this it would announce "collapsed" over an open
	 * menu. mouseenter/mouseleave rather than mouseover/mouseout because the
	 * former do not fire again for every child element crossed.
	 */
	submenuItems.forEach( function ( item ) {
		var toggle = item.querySelector( '.syn-submenu-toggle' );

		item.addEventListener( 'mouseenter', function () {
			if ( ! usesHoverMenus() ) {
				return;
			}

			item.classList.remove( 'syn-is-suppressed' );

			if ( toggle ) {
				toggle.setAttribute( 'aria-expanded', 'true' );
			}
		} );

		item.addEventListener( 'mouseleave', function () {
			if ( ! usesHoverMenus() ) {
				return;
			}

			// Clearing on the way out is what lets a panel dismissed with
			// Escape open again the next time the pointer arrives.
			item.classList.remove( 'syn-is-suppressed' );

			if ( toggle ) {
				toggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	} );

	/**
	 * Dismisses whichever submenu is open without moving pointer or focus.
	 *
	 * WCAG 1.4.13 requires content shown on hover to be dismissible in place.
	 * :hover cannot be cancelled from script, so the class is what cancels it —
	 * header.css hangs its hover rules off :not(.syn-is-suppressed).
	 *
	 * @return {void}
	 */
	function suppressSubmenus() {
		submenuItems.forEach( function ( item ) {
			var isOpen =
				item.classList.contains( 'syn-is-open' ) ||
				item.contains( document.activeElement ) ||
				( item.matches && item.matches( ':hover' ) );

			if ( ! isOpen ) {
				return;
			}

			item.classList.add( 'syn-is-suppressed' );
			item.classList.remove( 'syn-is-open' );

			var toggle = item.querySelector( '.syn-submenu-toggle' );

			if ( toggle ) {
				toggle.setAttribute( 'aria-expanded', 'false' );
			}
		} );
	}

	// A click anywhere outside a submenu closes whichever one is open.
	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest( '.syn-has-submenu' ) ) {
			closeSubmenus();
		}
	} );

	/*
	 * Tabbing out of an open submenu tidies up after it.
	 *
	 * On a hovering pointer :focus-within already closes the panel the moment
	 * focus leaves, so this is not what shuts it there — it clears the Escape
	 * suppression and resets aria-expanded, which CSS cannot do. On the tap path
	 * there is no :focus-within rule and this IS what closes the panel, so a
	 * keyboard user on a touch device does not tab past one that never shuts.
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
			item.classList.remove( 'syn-is-suppressed' );
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
			suppressSubmenus();
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

/*
 * ---------------------------------------------------------------------------
 * Scroll reveal
 *
 * Shared by every homepage section, which is why it lives here rather than in
 * any one of them. An element with .syn-reveal starts faded and slightly low;
 * .syn-is-visible puts it in place. hero.css and each section stylesheet only
 * have to name the class.
 *
 * Ported from design-source/assets/js/main.js, including its failsafe sweep,
 * whose reasoning is worth keeping: IntersectionObserver alone left two holes.
 * A fast scroll outruns its callbacks, so a section could sit blank for over a
 * second; and anything already scrolled past at load never intersects at all,
 * so on a refresh with a restored scroll position those sections stayed
 * invisible permanently. Re-running the geometry test on each scroll frame
 * closes both.
 *
 * Under prefers-reduced-motion, or with no IntersectionObserver, everything is
 * marked visible immediately — reduced motion means no movement, never hidden
 * content (CLAUDE.md §6).
 * ---------------------------------------------------------------------------
 */

( function () {
	'use strict';

	var items = Array.prototype.slice.call( document.querySelectorAll( '.syn-reveal' ) );

	if ( ! items.length ) {
		return;
	}

	var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	if ( reduced.matches || ! ( 'IntersectionObserver' in window ) ) {
		items.forEach( function ( item ) {
			item.classList.add( 'syn-is-visible' );
		} );

		return;
	}

	var pending = new Set( items );
	var frame = 0;
	var lastY = window.scrollY;

	function show( item, instant ) {
		if ( ! pending.has( item ) ) {
			return;
		}

		pending.delete( item );

		// Already well inside the viewport: the fade would only ever be
		// perceived as a blank gap, so the content is simply there.
		if ( instant ) {
			item.classList.add( 'syn-is-instant' );
		}

		item.classList.add( 'syn-is-visible' );
		observer.unobserve( item );
	}

	var observer = new IntersectionObserver(
		function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					show( entry.target, false );
				}
			} );
		},
		{ rootMargin: '0px 0px -5% 0px', threshold: 0 }
	);

	function sweep() {
		frame = 0;

		if ( ! pending.size ) {
			return;
		}

		var viewport = window.innerHeight;

		// Past roughly a third of a screen between frames the scroll is a
		// fling: a fade would land mid-screen and read as a blank block.
		var travelled = Math.abs( window.scrollY - lastY );
		var flinging = travelled > viewport * 0.3;
		lastY = window.scrollY;

		var limit = viewport * ( flinging ? 1.6 : 0.95 );

		Array.prototype.slice.call( pending ).forEach( function ( item ) {
			var rect = item.getBoundingClientRect();

			if ( rect.top >= limit ) {
				return;
			}

			show( item, flinging || rect.top < viewport * 0.6 );
		} );

		if ( ! pending.size ) {
			window.removeEventListener( 'scroll', schedule );
			window.removeEventListener( 'resize', schedule );
		}
	}

	function schedule() {
		if ( frame ) {
			return;
		}

		frame = window.requestAnimationFrame( sweep );
	}

	items.forEach( function ( item ) {
		observer.observe( item );
	} );

	window.addEventListener( 'scroll', schedule, { passive: true } );
	window.addEventListener( 'resize', schedule );
	schedule();
}() );

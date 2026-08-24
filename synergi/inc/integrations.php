<?php
/**
 * Third-party measurement loading — Google Tag Manager / GA4.
 *
 * Loaded by functions.php. This is the theme's ONLY external front-end request
 * (CLAUDE.md §2.6) and its only injection point for tags: future pixels go into
 * the GTM container, never into theme code. Nothing else belongs in this file.
 *
 * Stage 1 ships the loader with no container ID configured, so it outputs
 * nothing at all. Stage 8 sets the ID and verifies the tag fires.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the configured GTM container ID, or an empty string.
 *
 * The ID is configuration, not design, so it lives in a constant or an option —
 * never hard-coded in a template (CLAUDE.md §11). Order of precedence:
 *   1. define( 'SYN_GTM_ID', 'GTM-XXXXXXX' ); in wp-config.php
 *   2. the "syn_gtm_id" option, so it can be set per environment from wp-admin
 *
 * The format check is the sanitiser: anything that is not a container ID is
 * treated as "not configured" rather than injected into a script tag.
 *
 * @return string Validated container ID, or '' when none is configured.
 */
function syn_gtm_container_id() {
	$id = defined( 'SYN_GTM_ID' ) ? (string) SYN_GTM_ID : (string) get_option( 'syn_gtm_id', '' );
	$id = trim( $id );

	return preg_match( '/^GTM-[A-Z0-9]{4,}$/', $id ) ? $id : '';
}

add_action( 'wp_footer', 'syn_render_gtm_loader', 20 );
/**
 * Injects the GTM container after first paint.
 *
 * The analytics payload is ~556 KB. Loading it the standard way (a script in
 * <head>) would put that in front of the LCP budget, so the tag is appended
 * only once the page has loaded and the browser is idle — measurement still
 * happens, it just never competes with rendering (CLAUDE.md §6, §11).
 *
 * No <noscript> iframe fallback is emitted: a deferred loader and a noscript
 * pixel measure different populations, and the theme does not run scripts for
 * visitors who have JS off. Revisit only if analytics asks for it.
 *
 * Side effects: echoes an inline script into the footer. Outputs nothing when
 * no container is configured.
 *
 * @return void
 */
function syn_render_gtm_loader() {
	$container_id = syn_gtm_container_id();

	if ( '' === $container_id ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-integrations: no GTM container configured -->\n";
		}

		return;
	}

	// Not wp_enqueue_script(): the request must not be made by the browser's
	// preloader at all, which only an at-runtime-injected element achieves.
	?>
	<script id="syn-gtm-loader">
	( function () {
		var loaded = false;

		function synLoadGtm() {
			if ( loaded ) {
				return;
			}
			loaded = true;

			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push( { 'gtm.start': new Date().getTime(), event: 'gtm.js' } );

			var tag = document.createElement( 'script' );
			tag.async = true;
			tag.src = 'https://www.googletagmanager.com/gtm.js?id=<?php echo esc_js( $container_id ); ?>';
			document.head.appendChild( tag );
		}

		// Whichever comes first: the browser going idle after load, or the
		// visitor interacting — an interaction means the page is already usable.
		if ( 'requestIdleCallback' in window ) {
			window.addEventListener( 'load', function () {
				window.requestIdleCallback( synLoadGtm, { timeout: 4000 } );
			} );
		} else {
			window.addEventListener( 'load', synLoadGtm );
		}

		[ 'scroll', 'pointerdown', 'keydown' ].forEach( function ( event ) {
			window.addEventListener( event, synLoadGtm, { once: true, passive: true } );
		} );
	}() );
	</script>
	<?php
}

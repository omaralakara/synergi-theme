<?php
/**
 * Front-end asset loading.
 *
 * Loaded by functions.php. Owns every wp_enqueue_* call the theme makes:
 * the always-on base stylesheet and script, plus the conditional per-section
 * assets. Sections never enqueue anything themselves (CLAUDE.md §4) — from
 * Stage 5, inc/sections.php reports which sections a page uses through the
 * "syn_page_sections" filter and this file does the enqueueing.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'syn_enqueue_base_assets' );
/**
 * Enqueues the two assets every page needs: base.css and main.js.
 *
 * base.css is the only always-on stylesheet (CLAUDE.md §6). main.js is deferred
 * so nothing blocks render, and declares no dependencies — the theme never
 * depends on jQuery (CLAUDE.md §2.4).
 *
 * Side effects: registers and enqueues the "synergi-base" style and
 * "synergi-main" script handles.
 *
 * @return void
 */
function syn_enqueue_base_assets() {

	wp_enqueue_style(
		'synergi-base',
		SYN_URI . 'assets/css/base.css',
		array(),
		syn_asset_version( 'assets/css/base.css' )
	);

	wp_enqueue_script(
		'synergi-main',
		SYN_URI . 'assets/js/main.js',
		array(),
		syn_asset_version( 'assets/js/main.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	// Gives section scripts (Stage 5) a single flag to gate debug logging on,
	// so no console.log ever ships to production (CLAUDE.md §13).
	wp_add_inline_script(
		'synergi-main',
		'window.synDebug = ' . ( SYN_DEBUG ? 'true' : 'false' ) . ';',
		'before'
	);

	syn_asset_debug_note( 'base: synergi-base, synergi-main' );
}

add_action( 'wp_enqueue_scripts', 'syn_enqueue_section_assets', 20 );
/**
 * Enqueues the CSS and JS belonging to the sections this page renders.
 *
 * Runs at priority 20 so the section registry (inc/sections.php, Stage 5) has
 * already been populated. Until that file exists the filter returns an empty
 * array and this function does nothing — the hook is wired now so Stage 5 adds
 * a registry, not plumbing.
 *
 * A section named "hero" maps to assets/css/sections/hero.css and
 * assets/js/sections/hero.js. Files that do not exist are skipped silently in
 * production and reported in the SYN_DEBUG comment, because a section with no
 * JS is normal, not an error.
 *
 * Side effects: enqueues one style and one script handle per section.
 *
 * @return void
 */
function syn_enqueue_section_assets() {

	/**
	 * Filters the list of section slugs rendered on the current page.
	 *
	 * @param string[] $sections Section slugs, e.g. array( 'hero', 'cta' ).
	 */
	$sections = (array) apply_filters( 'syn_page_sections', array() );

	foreach ( $sections as $section ) {
		$slug = sanitize_key( $section );

		if ( '' === $slug ) {
			continue;
		}

		$css = 'assets/css/sections/' . $slug . '.css';
		$js  = 'assets/js/sections/' . $slug . '.js';

		if ( file_exists( SYN_DIR . $css ) ) {
			wp_enqueue_style(
				'synergi-section-' . $slug,
				SYN_URI . $css,
				array( 'synergi-base' ),
				syn_asset_version( $css )
			);
			syn_asset_debug_note( 'section css: ' . $slug );
		} else {
			syn_asset_debug_note( 'section css MISSING: ' . $css );
		}

		if ( file_exists( SYN_DIR . $js ) ) {
			wp_enqueue_script(
				'synergi-section-' . $slug,
				SYN_URI . $js,
				array( 'synergi-main' ),
				syn_asset_version( $js ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
			syn_asset_debug_note( 'section js: ' . $slug );
		}
	}
}

/**
 * Builds a cache-busting version string for a theme asset.
 *
 * File modification time beats the theme version here: a deploy that changes a
 * stylesheet without bumping SYN_VERSION would otherwise serve stale CSS from
 * the LiteSpeed cache. Falls back to SYN_VERSION if the file is unreadable.
 *
 * @param string $relative_path Path relative to the theme root, no leading slash.
 * @return string Version string for wp_enqueue_style()/wp_enqueue_script().
 */
function syn_asset_version( $relative_path ) {
	$file = SYN_DIR . ltrim( $relative_path, '/' );

	if ( file_exists( $file ) ) {
		return (string) filemtime( $file );
	}

	return SYN_VERSION;
}

/**
 * Records one line for the SYN_DEBUG asset report, and returns the whole report.
 *
 * Kept in a static so the wp_head printer can read what the enqueue functions
 * decided, which is what makes "why is this CSS missing?" answerable from
 * view-source (CLAUDE.md §13).
 *
 * @param string|null $note Line to record, or null to read the report.
 * @return string[] Every note recorded so far.
 */
function syn_asset_debug_note( $note = null ) {
	static $notes = array();

	if ( null !== $note ) {
		$notes[] = $note;
	}

	return $notes;
}

add_action( 'wp_head', 'syn_print_asset_debug_comment', 999 );
/**
 * Prints the asset report as an HTML comment when SYN_DEBUG is on.
 *
 * Side effects: echoes to the page head. Outputs nothing on production.
 *
 * @return void
 */
function syn_print_asset_debug_comment() {
	if ( ! SYN_DEBUG ) {
		return;
	}

	echo "\n<!-- syn-assets\n";
	foreach ( syn_asset_debug_note() as $note ) {
		echo '     ' . esc_html( $note ) . "\n";
	}
	echo "-->\n";
}

add_filter( 'wp_preload_resources', 'syn_preload_brand_font' );
/**
 * Preloads the one font file the theme ships (CLAUDE.md §6).
 *
 * Montserrat is declared in theme.json, which emits the @font-face rule inside
 * the global-styles inline CSS. Without a preload the browser cannot discover
 * the file until it has parsed that CSS, which costs a round trip on the LCP
 * text. Uses core's wp_preload_resources filter so the link prints at wp_head
 * priority 1, ahead of every stylesheet.
 *
 * The href must match theme.json's resolved src byte for byte — no version
 * query string — or the browser treats them as two files and downloads both.
 * crossorigin is mandatory even same-origin: fonts are always fetched in CORS
 * mode, and a preload without it is discarded and refetched.
 *
 * @param array[] $resources Resources core will preload.
 * @return array[] The list with the brand font appended.
 */
function syn_preload_brand_font( $resources ) {
	$resources[] = array(
		'href'        => SYN_URI . 'assets/fonts/montserrat-latin.woff2',
		'as'          => 'font',
		'type'        => 'font/woff2',
		'crossorigin' => 'anonymous',
	);

	return $resources;
}

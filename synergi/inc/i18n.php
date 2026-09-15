<?php
/**
 * Languages — what the theme needs to know about Polylang, in one place.
 *
 * Loaded by functions.php straight after inc/setup.php. Depended on by
 * footer.php, parts/lang-switch.php, parts/consent.php, inc/sections.php,
 * inc/records.php (one record store per language), inc/assets.php (the Arabic
 * font preload) and every section that prints a stored page address.
 *
 * Polylang is optional. Every helper here degrades to single-language
 * behaviour when the plugin is inactive, so the English site never depends on
 * it and a deactivated plugin cannot take the navigation down with it. The
 * decision to use Polylang (free, the WTC Saudi precedent, separate post per
 * language so the hand-built fields work untouched) is recorded in
 * synergi-architecture-explained.md §5.
 *
 * WHY THIS FILE EXISTS RATHER THAN pll_*() CALLS IN TEMPLATES. Every Polylang
 * function is guarded by function_exists(), and a template that wrote that
 * guard twenty times would be twenty places to get it wrong. Here it is
 * written once per question the theme asks.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/**
 * The language the site falls back to. English, whatever Polylang says, until
 * the day that decision changes — and then it changes in Polylang, not here.
 */
define( 'SYN_LANGUAGE_FALLBACK', 'en' );

add_action( 'after_setup_theme', 'syn_load_textdomain' );
/**
 * Loads the theme's own translations from languages/.
 *
 * WordPress also looks in wp-content/languages/themes/ on its own; this adds
 * the folder the repo ships, so a translation travels with the theme rather
 * than living only on one server (CLAUDE.md §1: everything about how the site
 * reads lives in a file).
 *
 * Side effects: registers the "synergi" text domain path.
 *
 * @return void
 */
function syn_load_textdomain() {
	load_theme_textdomain( 'synergi', SYN_DIR . 'languages' );
}

add_filter( 'request', 'syn_resolve_page_in_language' );
/**
 * Sends a page URL to the page in the language of the request.
 *
 * Every Arabic page shares its slug with its English twin (/ar/contact-us/ and
 * /contact-us/), which Polylang allows on save but WordPress cannot resolve:
 * it looks pages up by path with a plain SQL query, finds two candidates with
 * identical paths, and returns whichever the database lists first — the
 * English one. Polylang's own fix rewrites the "pagename" to the translation's
 * path, which here is the same path, so it lands on the same page. Verified on
 * staging 15 Sep 2026: /ar/our-services/accounting/ resolved the language as
 * Arabic and the page as English.
 *
 * So the lookup is finished here, on the "request" filter, before WP_Query
 * runs: whatever page answers at the path, ask Polylang for its twin in the
 * requested language and query that page by ID instead. The default language
 * carries no "lang" variable in its URLs, so it is filled in explicitly and
 * an English URL can never land on an Arabic page either.
 *
 * @param array $vars Query variables parsed from the URL.
 * @return array The same variables, with "page_id" set when a twin was found.
 */
function syn_resolve_page_in_language( $vars ) {
	if ( empty( $vars['pagename'] ) || ! syn_has_languages() || ! function_exists( 'pll_get_post' ) ) {
		return $vars;
	}

	$language = ! empty( $vars['lang'] ) ? sanitize_key( $vars['lang'] ) : syn_language_default();
	$page     = get_page_by_path( $vars['pagename'], OBJECT, 'page' );

	if ( ! $page ) {
		return $vars;
	}

	$translated = (int) pll_get_post( $page->ID, $language );

	if ( $translated && $translated !== (int) $page->ID ) {
		$vars['page_id'] = $translated;
		unset( $vars['pagename'] );
	}

	return $vars;
}

/**
 * Whether Polylang is active and has at least one language configured.
 *
 * @return bool
 */
function syn_has_languages() {
	return function_exists( 'pll_languages_list' ) && (bool) pll_languages_list();
}

/**
 * The default language slug.
 *
 * @return string e.g. "en".
 */
function syn_language_default() {
	if ( function_exists( 'pll_default_language' ) ) {
		$default = pll_default_language();
		if ( is_string( $default ) && '' !== $default ) {
			return $default;
		}
	}

	return SYN_LANGUAGE_FALLBACK;
}

/**
 * The language of the current request.
 *
 * On the front end this is the language Polylang resolved from the URL. In
 * wp-admin it is the language the editor is looking at: a "lang" query
 * argument when the screen carries one, otherwise the default — which is what
 * the site-records screen uses to decide which store it is editing.
 *
 * @return string Language slug, e.g. "ar".
 */
function syn_language_current() {
	if ( ! syn_has_languages() ) {
		return syn_language_default();
	}

	if ( is_admin() ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen state, no action taken.
		$requested = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : '';

		return in_array( $requested, pll_languages_list(), true ) ? $requested : syn_language_default();
	}

	$current = pll_current_language();

	return is_string( $current ) && '' !== $current ? $current : syn_language_default();
}

/**
 * Whether the current request is in the default language.
 *
 * @return bool
 */
function syn_language_is_default() {
	return syn_language_current() === syn_language_default();
}

/**
 * Every configured language, keyed by slug.
 *
 * @return array<string,array{slug:string,name:string,locale:string,rtl:bool}>
 */
function syn_languages() {
	if ( ! syn_has_languages() ) {
		return array(
			SYN_LANGUAGE_FALLBACK => array(
				'slug'   => SYN_LANGUAGE_FALLBACK,
				'name'   => 'English',
				'locale' => 'en_US',
				'rtl'    => false,
			),
		);
	}

	$languages = array();

	foreach ( (array) pll_languages_list( array( 'fields' => null ) ) as $language ) {
		$languages[ $language->slug ] = array(
			'slug'   => $language->slug,
			'name'   => $language->name,
			'locale' => $language->locale,
			'rtl'    => (bool) $language->is_rtl,
		);
	}

	return $languages;
}

/**
 * A stored page address, resolved into the language of the current request.
 *
 * The theme stores addresses as paths relative to home — "/contact-us/" in a
 * link field, a site record or a footer column — because CLAUDE.md §12 forbids
 * baking a domain in. On an Arabic page that path has to lead to the Arabic
 * contact page, not the English one, and this is the one function that knows
 * how: find the page that answers at the English path, ask Polylang for its
 * translation, and return that page's own permalink.
 *
 * Anything that is not a same-site path is returned untouched: an external
 * URL, a mailto:, a fragment, an empty string. A path with no page behind it
 * (a blog archive, say) is passed through home_url(), exactly as before.
 *
 * @param string $url A path relative to home, or a full URL.
 * @return string An absolute URL.
 */
function syn_local_url( $url ) {
	$url = trim( (string) $url );

	if ( '' === $url ) {
		return '';
	}

	$home = untrailingslashit( home_url() );

	// A full URL on this site is treated as its path; any other URL is not ours.
	if ( 0 === strpos( $url, $home . '/' ) || $url === $home ) {
		$url = substr( $url, strlen( $home ) );
		$url = '' === $url ? '/' : $url;
	} elseif ( preg_match( '#^[a-z][a-z0-9+.-]*:|^//|^\##i', $url ) ) {
		return $url;
	}

	if ( '/' !== $url[0] ) {
		return $url;
	}

	if ( ! syn_has_languages() || syn_language_is_default() ) {
		return home_url( $url );
	}

	$path = trim( strtok( $url, '?#' ), '/' );

	if ( '' === $path ) {
		return function_exists( 'pll_home_url' ) ? pll_home_url( syn_language_current() ) : home_url( '/' );
	}

	/*
	 * Polylang lets an English page and its Arabic twin share a slug, so this
	 * lookup may land on either. It does not matter which: pll_get_post()
	 * returns the twin in the language asked for, or the page itself when it
	 * already is that language.
	 */
	$page = get_page_by_path( $path, OBJECT, array( 'page', 'post' ) );

	if ( ! $page ) {
		return home_url( $url );
	}

	$translated = pll_get_post( $page->ID, syn_language_current() );

	if ( $translated && 'publish' === get_post_status( $translated ) ) {
		return get_permalink( $translated );
	}

	return get_permalink( $page );
}

/**
 * The address of a page named by its English path, in the current language.
 *
 * A thin wrapper so a template reads "the contact page" rather than a string
 * of slashes: syn_page_url( 'contact-us' ).
 *
 * @param string $path Page path without leading or trailing slashes.
 * @return string
 */
function syn_page_url( $path ) {
	return syn_local_url( '/' . trim( (string) $path, '/' ) . '/' );
}

/**
 * The other languages this request could be read in.
 *
 * What parts/lang-switch.php renders. Each entry links to the translation of
 * the current page when one exists, and otherwise to that language's home,
 * which is Polylang's own convention and keeps the switcher from ever 404ing.
 *
 * @return array<int,array{slug:string,name:string,locale:string,rtl:bool,url:string}>
 */
function syn_language_switch_targets() {
	if ( ! syn_has_languages() || ! function_exists( 'pll_the_languages' ) ) {
		return array();
	}

	$current = syn_language_current();
	$targets = array();

	foreach ( (array) pll_the_languages( array( 'raw' => 1, 'hide_if_empty' => 0 ) ) as $language ) {
		if ( $language['slug'] === $current ) {
			continue;
		}

		$targets[] = array(
			'slug'   => $language['slug'],
			'name'   => $language['name'],
			'locale' => $language['locale'],
			'rtl'    => ! empty( $language['is_rtl'] ),
			'url'    => $language['url'],
		);
	}

	return $targets;
}

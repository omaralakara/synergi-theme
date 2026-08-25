<?php
/**
 * Removal of core front-end cruft the theme does not use.
 *
 * Loaded by functions.php. Every removal here is payload the theme never asks
 * for; nothing here changes markup the theme itself emits, so no template
 * depends on this file. It also ships the jQuery audit tool (CLAUDE.md §2.4).
 *
 * Not done here, deliberately: disabling the file editor. That is
 * define( 'DISALLOW_FILE_EDIT', true ); in wp-config.php — a theme cannot
 * enforce it, because deactivating the theme would silently re-enable it.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'syn_remove_emoji_support' );
/**
 * Stops WordPress emitting the emoji detection script, styles and filters.
 *
 * ~15 KB of JS plus an inline style block on every page, for a polyfill no
 * supported browser needs.
 *
 * Side effects: removes the core emoji actions and filters, and the TinyMCE
 * emoji plugin.
 *
 * @return void
 */
function syn_remove_emoji_support() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'embed_head', 'print_emoji_detection_script' );

	// Core moved the emoji stylesheet from print_emoji_styles() to
	// wp_enqueue_emoji_styles() in 6.4; both are removed so the theme keeps
	// working either side of that change.
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_action( 'admin_print_styles', 'wp_enqueue_emoji_styles' );

	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

	add_filter( 'tiny_mce_plugins', 'syn_remove_emoji_tinymce_plugin' );
}

/**
 * Drops the emoji plugin from the TinyMCE plugin list.
 *
 * @param array $plugins TinyMCE plugin slugs.
 * @return array Plugin slugs without "wpemoji".
 */
function syn_remove_emoji_tinymce_plugin( $plugins ) {
	if ( ! is_array( $plugins ) ) {
		return array();
	}

	return array_diff( $plugins, array( 'wpemoji' ) );
}

add_action( 'init', 'syn_remove_oembed_cruft' );
/**
 * Removes the oEmbed discovery links and the embed host script.
 *
 * The theme embeds nothing and no page needs to be discoverable as an oEmbed
 * provider. Embedding external media in post content still works — this only
 * removes the machinery for other sites embedding us.
 *
 * Side effects: removes two core hooks.
 *
 * @return void
 */
function syn_remove_oembed_cruft() {
	// Core registers the discovery links TWICE in default-filters.php: once at
	// priority 4 and once at the default 10. Removing only the default leaves
	// the priority-4 copy printing, which is what happened until 25 Aug — both
	// have to go, and a bare remove_action() only ever matches priority 10.
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 4 );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
}

add_action( 'wp_enqueue_scripts', 'syn_dequeue_core_styles', 100 );
/**
 * Dequeues core stylesheets the theme replaces with its own tokens.
 *
 * Runs at priority 100 so it wins against anything that enqueues late.
 *
 * "wp-block-library" itself is NOT dequeued: posts are written in the block
 * editor and need core block layout rules. Only the opinionated theme layer on
 * top of it, and the classic-theme compatibility sheet, are removed — both
 * carry colours and font sizes that would fight theme.json (CLAUDE.md §2.7).
 *
 * Side effects: dequeues two style handles on the front end.
 *
 * @return void
 */
function syn_dequeue_core_styles() {
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );

	// Part of the oEmbed machinery removed above; the script has no other use.
	wp_dequeue_script( 'wp-embed' );
}

add_action( 'wp_print_footer_scripts', 'syn_audit_jquery_dependents', 999 );
/**
 * Lists every enqueued handle that still depends on jQuery.
 *
 * This is a diagnostic, NOT a removal. jQuery stays registered: WPForms
 * declares it as a front-end dependency and deregistering silently breaks form
 * validation (verified 20 Aug 2026, CLAUDE.md §2.4). jQuery is dropped only
 * once this audit prints an empty list — realistically when the form stack is
 * consolidated.
 *
 * Enable by adding define( 'SYNERGI_AUDIT_JQUERY', true ); to wp-config.php on
 * staging. Prints an HTML comment and writes the same list to the PHP error log
 * with the [synergi] prefix.
 *
 * Side effects: echoes an HTML comment; writes to the error log.
 *
 * @return void
 */
function syn_audit_jquery_dependents() {
	if ( ! defined( 'SYNERGI_AUDIT_JQUERY' ) || ! SYNERGI_AUDIT_JQUERY ) {
		return;
	}

	$jquery_handles = array( 'jquery', 'jquery-core', 'jquery-migrate' );
	$scripts        = wp_scripts();
	$dependents     = array();

	// done[] is the list of handles that actually printed on this page,
	// dependencies included — queue[] would only show top-level enqueues.
	foreach ( $scripts->registered as $handle => $script ) {
		if ( ! in_array( $handle, $scripts->done, true ) ) {
			continue;
		}

		if ( array_intersect( $jquery_handles, (array) $script->deps ) ) {
			$dependents[] = $handle;
		}
	}

	sort( $dependents );

	$report = $dependents
		? implode( ', ', $dependents )
		: 'none — jQuery can now be deregistered';

	$request = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

	echo "\n<!-- syn-jquery-audit: " . esc_html( $report ) . " -->\n";
	error_log( '[synergi] jQuery dependents on ' . $request . ': ' . $report );
}

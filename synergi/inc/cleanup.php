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

/*
 * ---------------------------------------------------------------------------
 * Instagram Feed Pro — load its stylesheet only where a feed renders.
 *
 * Measured on staging 25 Aug: sbi-styles.min.css is 93.7 KB and loads on every
 * page. The CSS budget is 120 KB (CLAUDE.md §6), so one plugin was spending 78%
 * of it — on About Us, which has no feed. Exactly one page on the site embeds a
 * feed at all: the front page.
 *
 * The plugin already loads its JAVASCRIPT this way; only the stylesheet is
 * unconditional. So this is not a new pattern being imposed on the plugin, it
 * is the plugin's own pattern applied to the one asset that missed it.
 *
 * How it stays safe. Nothing here inspects post content or guesses where a feed
 * might be — guessing is what breaks feeds embedded through a widget, a
 * template call or a shortcode inside meta. Instead the stylesheet is dropped
 * before the head is printed and re-enqueued the moment an Instagram feed
 * actually renders, whatever renders it. A style enqueued that late prints in
 * the footer (core's print_late_styles()), so a feed can never end up unstyled;
 * the worst case is that its CSS arrives slightly after the markup, on the one
 * page that has one.
 *
 * To turn the whole thing off:
 *     add_filter( 'syn_conditional_instagram_assets', '__return_false' );
 * ---------------------------------------------------------------------------
 */

/**
 * The Instagram Feed Pro stylesheet handles this file manages.
 *
 * ONLY the front-end feed stylesheet. "sbi-blocks-styles" was in this list
 * until 25 Aug and must never come back: it is the block EDITOR's chrome for
 * the Instagram block, and the plugin registers it as
 *
 *     wp_register_style( 'sbi-blocks-styles', 'css/sb-blocks.css',
 *                        array( 'wp-edit-blocks' ), SBIVER );
 *
 * That dependency chains — wp-edit-blocks pulls wp-block-editor-content, which
 * pulls wp-reset-editor-styles, which pulls wp-admin's own common.css and
 * forms.css. common.css carries
 *
 *     body { font-family: -apple-system, BlinkMacSystemFont, ...; font-size: 13px }
 *
 * which loads after global styles and beats Montserrat on equal specificity.
 * The plugin only ever enqueues this handle on enqueue_block_editor_assets, so
 * it never reaches the front end on its own; restoring it here is what put it
 * there, and it cost the homepage 91 KB of admin CSS and its brand font.
 *
 * The front-end feed is painted entirely by sbi_styles.
 *
 * @return string[] Style handles.
 */
function syn_instagram_style_handles() {
	return array( 'sbi_styles' );
}

/**
 * Whether the theme should defer the plugin's stylesheet on this request.
 *
 * @return bool
 */
function syn_should_defer_instagram_styles() {
	if ( is_admin() || is_customize_preview() ) {
		return false;
	}

	/**
	 * Filters whether Instagram Feed Pro's CSS is deferred until a feed renders.
	 *
	 * @param bool $enabled Default true.
	 */
	return (bool) apply_filters( 'syn_conditional_instagram_assets', true );
}

add_action( 'wp_enqueue_scripts', 'syn_defer_instagram_styles', 100 );
/**
 * Dequeues the Instagram stylesheet before the document head is printed.
 *
 * Priority 100 so it runs after the plugin has enqueued.
 *
 * Side effects: dequeues style handles; writes to the SYN_DEBUG asset report.
 *
 * @return void
 */
function syn_defer_instagram_styles() {
	if ( ! syn_should_defer_instagram_styles() ) {
		return;
	}

	foreach ( syn_instagram_style_handles() as $handle ) {
		if ( wp_style_is( $handle, 'enqueued' ) ) {
			wp_dequeue_style( $handle );
			syn_asset_debug_note( 'deferred until used: ' . $handle );
		}
	}
}

/**
 * Puts the Instagram stylesheet back, to print in the footer.
 *
 * Called the moment a feed renders. Safe to call repeatedly — wp_enqueue_style()
 * on an already-enqueued handle is a no-op.
 *
 * Side effects: enqueues style handles.
 *
 * @return void
 */
function syn_restore_instagram_styles() {
	foreach ( syn_instagram_style_handles() as $handle ) {
		if ( wp_style_is( $handle, 'registered' ) && ! wp_style_is( $handle, 'enqueued' ) ) {
			wp_enqueue_style( $handle );
			syn_asset_debug_note( 'restored, feed rendered: ' . $handle );
		}
	}
}

add_filter( 'do_shortcode_tag', 'syn_restore_instagram_styles_for_shortcode', 10, 2 );
/**
 * Restores the stylesheet when an Instagram shortcode runs.
 *
 * Covers every route a shortcode can take — post content, a text widget, a
 * do_shortcode() call in a template — because this filter fires for all of them.
 *
 * @param string $output The shortcode's output.
 * @param string $tag    The shortcode name.
 * @return string The output, unchanged.
 */
function syn_restore_instagram_styles_for_shortcode( $output, $tag ) {
	if ( 'instagram-feed' === $tag || 0 === strpos( $tag, 'instagram-feed' ) ) {
		syn_restore_instagram_styles();
	}

	return $output;
}

add_filter( 'render_block', 'syn_restore_instagram_styles_for_block', 10, 2 );
/**
 * Restores the stylesheet when an Instagram block renders.
 *
 * @param string $content The block's rendered output.
 * @param array  $block   The parsed block.
 * @return string The content, unchanged.
 */
function syn_restore_instagram_styles_for_block( $content, $block ) {
	$name = $block['blockName'] ?? '';

	if ( is_string( $name ) && ( 0 === strpos( $name, 'sbi/' ) || false !== strpos( $name, 'instagram-feed' ) ) ) {
		syn_restore_instagram_styles();
	}

	return $content;
}

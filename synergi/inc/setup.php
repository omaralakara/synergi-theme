<?php
/**
 * Theme supports, navigation menus and image sizes.
 *
 * Loaded by functions.php on every request.
 * Everything the theme registers with WordPress core lives here — templates and
 * inc/assets.php depend on these registrations (nav menu locations, thumbnail
 * sizes, editor stylesheet).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'syn_setup' );
/**
 * Declares theme support and registers navigation menu locations.
 *
 * Side effects: registers two nav menu locations, sets the featured-image size,
 * adds one extra image size, and registers assets/css/base.css as the editor
 * stylesheet.
 *
 * No text domain is loaded here: WordPress loads translations for the "synergi"
 * domain just-in-time from wp-content/languages/themes/, and the theme ships no
 * languages/ directory of its own until the Arabic phase (CLAUDE.md §12).
 *
 * @return void
 */
function syn_setup() {

	// Yoast owns <title>, but core still needs this support to render the tag
	// at all (CLAUDE.md §8 — the theme itself outputs no head metadata).
	add_theme_support( 'title-tag' );

	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );

	// Semantic markup from core-generated output (search form, comment list,
	// captions) — required for the landmark/heading rules in CLAUDE.md §8.
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Lets core emit width/height-free responsive embeds instead of fixed
	// iframes, so post content stays inside the container on small screens.
	add_theme_support( 'responsive-embeds' );

	// Lets core's wide and full alignments resolve against the container widths
	// theme.json sets in Stage 2, instead of overflowing the content column.
	add_theme_support( 'align-wide' );

	// The block editor should render content with the same base rules as the
	// front end. Both calls are required: without the editor-styles support flag
	// core ignores every registered editor stylesheet, so the --syn-* alias layer
	// in base.css never reaches the canvas (Stage 1 carry-forward, wired up here
	// in Stage 2 now that base.css has real content).
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/base.css' );

	register_nav_menus(
		array(
			'primary' => esc_html__( 'Primary navigation', 'synergi' ),
			'footer'  => esc_html__( 'Footer links', 'synergi' ),
		)
	);

	/*
	 * Image sizes. Two ratios only, because every extra registered size is
	 * another derivative written for every upload.
	 *   - featured image: 16:9, used by single.php and archive cards (Stage 4)
	 *   - syn-card:       16:9 at card width, used by listing sections (Stage 5)
	 * Changing either value later requires regenerating thumbnails — decide once.
	 */
	set_post_thumbnail_size( 1200, 675, true );
	add_image_size( 'syn-card', 720, 405, true );
}

add_filter( 'block_editor_settings_all', 'syn_add_editor_font_faces' );
/**
 * Injects the theme.json @font-face rules into the block editor canvas.
 *
 * Core prints font faces for the front end on wp_head (wp_print_font_faces at
 * priority 50) but registers no admin or editor equivalent. A classic/hybrid
 * theme's canvas therefore inherits the global-styles rule
 * "font-family: var(--wp--preset--font-family--brand)" with no matching
 * @font-face, so Montserrat cannot load and the canvas silently falls down the
 * fallback stack. Verified on staging 25 Aug: none of the eight editor style
 * entries contained an @font-face rule.
 *
 * The styles array is the channel used because it is the only one that reaches
 * the canvas in both the iframed and the non-iframed editor; admin_print_styles
 * lands in the admin document head, which an iframed canvas never sees.
 *
 * No values are restated here — the CSS comes from core's own font-face printer
 * reading theme.json, so the font stays single-sourced (CLAUDE.md §2.7).
 *
 * Side effects: appends one entry to the block editor styles array. Runs only
 * in the editor; the front end is untouched.
 *
 * @param array $settings Block editor settings.
 * @return array Settings with the font-face CSS appended.
 */
function syn_add_editor_font_faces( $settings ) {

	ob_start();
	wp_print_font_faces();
	$style_tag = ob_get_clean();

	// wp_print_font_faces() emits a wrapped <style> element; the editor styles
	// array expects bare CSS.
	$css = trim( (string) preg_replace( '#</?style[^>]*>#i', '', $style_tag ) );

	if ( '' === $css ) {
		return $settings;
	}

	$settings['styles'][] = array( 'css' => $css );

	return $settings;
}

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
	// front end. base.css is an empty placeholder until Stage 2 — registering it
	// now means Stage 2 only has to fill the file, not wire it up.
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

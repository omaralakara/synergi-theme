<?php
/**
 * Template Name: Homepage (rebuild)
 *
 * The homepage composition, assembled from sections/.
 *
 * TEMPORARY LOCATION. This is a page template rather than front-page.php on
 * purpose: pointing front-page.php at the live homepage while only some of the
 * twelve sections exist would replace a finished Elementor page with a
 * half-built one. Sections are verified here, on a draft page, exactly as the
 * previous developer used draft page #10479.
 *
 * When all twelve sections are done and checked, this file's body becomes
 * front-page.php and this template is deleted. The composition is written once
 * so there is nothing to keep in sync in the meantime.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, sections/*.php.
 *
 * The hero owns this page's one <h1> (CLAUDE.md §8), so nothing here renders
 * parts/page-header.php.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/*
 * Declared BEFORE get_header(), because assets are enqueued during wp_head()
 * and a section declared after that renders unstyled. inc/sections.php prints
 * a comment naming the mistake if this ever slips below the call.
 *
 * Sections are added to this list as they are built, in the design's own
 * order — 01 hero, 02 core services, 03 shared services, 04 industries,
 * 05 why synergi, 06 numbers, 07 partners, 08 locations, 09 blog,
 * 10 instagram, 11 podcast, 12 final CTA.
 */
syn_use_sections( array( 'hero' ) );

get_header();

/*
 * The hero photograph is a real attachment rather than a path, so it goes
 * through core and gets srcset and sizes for free (CLAUDE.md §6). Passing 0
 * renders the hero on its flat ink background, which is a legitimate fallback
 * rather than a broken image.
 */
syn_section(
	'hero',
	array(
		'image_id' => (int) apply_filters( 'syn_hero_image_id', 0 ),
	)
);

get_footer();

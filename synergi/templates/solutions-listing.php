<?php
/**
 * Template Name: Our Solutions listing
 *
 * The page at /our-solutions/ — every solution in one grid, each card a way into
 * that solution's own page.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/solution-fields.php, inc/records.php, parts/page-header.php, sections/*.php.
 *
 * The sibling of templates/services-listing.php, and identical in shape on
 * purpose — a reader moving from Our Services to Our Solutions should find the
 * same page with different words on it. Two small templates rather than one
 * template with a switch: each is thirty lines that say plainly which record
 * they read, and a reader debugging Our Solutions never has to work out which
 * branch they are in (CLAUDE.md §13).
 *
 * THE GRID IS THE RECORD. It reads the "solutions" record through
 * syn_solutions(), so adding a solution at Settings → Site records puts it here,
 * on every other solution page's "keep exploring" band and in the menus at once.
 * There is deliberately no field on this page for listing solutions.
 *
 * THE COLOURS COME FROM syn_solutions(), which numbers the whole record once and
 * hands each solution one of the six accents. That is what keeps a solution the
 * same colour here as it is at the foot of every sibling page, and what carries
 * the colour language across from Our Services rather than ending the journey in
 * a row of identical blue tiles (asked for 31 Aug).
 *
 * parts/page-header.php owns this page's one <h1> (CLAUDE.md §8) and nothing
 * below emits another. The title, the meta description and the canonical are
 * Yoast's — the theme emits none of them, so there are no fields for them here.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_id = get_the_ID();

/*
 * Declared BEFORE get_header(), because assets are enqueued during wp_head() and
 * a section declared after that renders unstyled.
 */
syn_use_sections( array( 'offers', 'final-cta' ) );

get_header();

get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => syn_field( 'solutions_list_eyebrow', $syn_id ),
		'lede'    => syn_field( 'solutions_list_lede', $syn_id ),
		'image'   => syn_field_image_id( 'solutions_list_image', $syn_id ),
		'cta'     => syn_field_link( 'solutions_list_cta', $syn_id ),
	)
);

/*
 * No <main> here: header.php opens <main id="main-content"> and footer.php
 * closes it (CLAUDE.md §8, one <main>).
 */
syn_section(
	'offers',
	array(
		'eyebrow' => __( 'How we engage', 'synergi' ),
		'heading' => syn_field( 'solutions_list_heading', $syn_id ),
		'lede'    => syn_field( 'solutions_list_grid_lede', $syn_id ),
		'items'   => function_exists( 'syn_solutions' ) ? syn_solutions() : array(),
		'empty'   => syn_field( 'solutions_list_empty', $syn_id ),
	)
);

/*
 * Neither the "why" band nor the figures band renders here, for the reason
 * templates/services-listing.php gives at the same point: a listing page sits
 * between two pages that already carry them (asked for 31 Aug).
 */
syn_section( 'final-cta' );

get_footer();

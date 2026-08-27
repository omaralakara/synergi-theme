<?php
/**
 * Template Name: People page
 *
 * One template, two pages: /our-leadership/ carries the board and the strategic
 * advisors, /engagement-team/ carries the delivery team. They differ in what an
 * editor typed and in nothing else — the same claim templates/service.php makes
 * about the six service lines, made a second time on a different shape of page.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/about-fields.php, inc/records.php, parts/page-header.php,
 * sections/people.php, sections/final-cta.php.
 *
 * parts/page-header.php owns this page's one <h1> (CLAUDE.md §8). Group
 * headings inside the people section are <h2> and every person is an <h3>, so
 * no level is ever skipped whatever an editor types.
 *
 * This file composes and does not draw (CLAUDE.md §4).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_id = get_the_ID();

// Declared before get_header(), or the assets arrive a request too late.
syn_use_sections( array( 'people', 'final-cta' ) );

get_header();

get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => syn_field( 'people_eyebrow', $syn_id ),
		'lede'    => syn_field( 'people_lede', $syn_id ),
		'image'   => (int) get_post_thumbnail_id( $syn_id ),
		'cta'     => syn_field_link( 'people_cta', $syn_id ),
	)
);

/*
 * One flat list. The section groups it: rows sharing a "Group" name sit under
 * one heading, and rows with none sit under a heading that is present for
 * assistive technology and invisible on screen. See sections/people.php.
 */
syn_section(
	'people',
	array(
		'people' => syn_field_rows( 'people', $syn_id ),
	)
);

// The closing band, shared with the homepage and every service page, reading
// the final_cta site record rather than this page's own copy of it.
syn_section( 'final-cta' );

get_footer();

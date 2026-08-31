<?php
/**
 * Template Name: Case studies listing
 *
 * The page that lists every published case study — headline, service line, kind
 * of client and country on each card, with a way into the study itself.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/case-study-fields.php, parts/page-header.php, sections/*.php.
 *
 * THE GRID FILLS ITSELF. sections/case-studies.php asks syn_case_studies() which
 * pages are on templates/case-study.php, so publishing a study adds it here and
 * unpublishing one removes it. There is no list for an editor to maintain and
 * therefore no list to fall out of date — the same reasoning that makes the
 * service lines a record rather than a field (CLAUDE.md §7a).
 *
 * Composed entirely from bands that already exist, so there is no CSS and no
 * JavaScript belonging to this template (CLAUDE.md §4).
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
syn_use_sections( array( 'case-studies', 'final-cta' ) );

get_header();

get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => syn_field( 'case_list_eyebrow', $syn_id ),
		'lede'    => syn_field( 'case_list_lede', $syn_id ),
		'image'   => syn_field_image_id( 'case_list_image', $syn_id ),
	)
);

/*
 * No <main> here: header.php opens <main id="main-content"> and footer.php
 * closes it (CLAUDE.md §8, one <main>).
 *
 * No count: this page shows all of them. A grid that silently stopped at twelve
 * would be a page quietly telling a reader there are only twelve.
 */
syn_section(
	'case-studies',
	array(
		'eyebrow' => __( 'Our work', 'synergi' ),
		'heading' => syn_field( 'case_list_heading', $syn_id ),
		'lede'    => syn_field( 'case_list_grid_lede', $syn_id ),
		'empty'   => syn_field( 'case_list_empty', $syn_id ),
	)
);

syn_section( 'final-cta' );

get_footer();

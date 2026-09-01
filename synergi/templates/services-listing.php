<?php
/**
 * Template Name: Our Services listing
 *
 * The page at /our-services/ — every service line in one grid, each card a way
 * into that line's own page.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/service-fields.php, inc/records.php, parts/page-header.php, sections/*.php.
 *
 * COMPOSED ENTIRELY FROM BANDS THAT ALREADY EXIST, like templates/solution.php
 * before it. There is no CSS and no JavaScript belonging to this template
 * (CLAUDE.md §4): the grid is sections/offers.php, and the two bands under it
 * are the same record-driven bands the homepage and the six service pages
 * render. A listing page is a short page with a good grid on it, not a second
 * design.
 *
 * THE GRID IS THE RECORD. It reads the "services" record, so a seventh service
 * line added at Settings → Site records appears here, on the homepage deck, on
 * every service page's "keep exploring" band and in the menus at once — with no
 * developer and nothing to keep in step (CLAUDE.md §7a). There is deliberately
 * no field on this page for listing services: that would be the second copy this
 * whole architecture exists to avoid.
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

/*
 * The hero. The page title is the <h1> — parts/page-header.php defaults to it,
 * so the heading is never a field an editor could leave empty or fill with
 * something the browser tab disagrees with.
 */
get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => syn_field( 'services_list_eyebrow', $syn_id ),
		'lede'    => syn_field( 'services_list_lede', $syn_id ),
		'image'   => syn_field_image_id( 'services_list_image', $syn_id ),
		'cta'     => syn_field_link( 'services_list_cta', $syn_id ),
	)
);

/*
 * No <main> here: header.php opens <main id="main-content"> and footer.php
 * closes it, so a second one would nest two of the same landmark on one page
 * (CLAUDE.md §8, one <main>).
 *
 * The cards take no accent argument. Each row's own reference IS one of the six
 * accent names, so the six lines arrive here wearing the colours they wear on
 * the homepage and at the foot of every service page — see
 * syn_accent_for_index() in inc/sections.php for why that fallback exists.
 */
syn_section(
	'offers',
	array(
		'eyebrow' => __( 'What we do', 'synergi' ),
		'heading' => syn_field( 'services_list_heading', $syn_id ),
		'lede'    => syn_field( 'services_list_grid_lede', $syn_id ),
		'items'   => function_exists( 'syn_record' ) ? syn_record( 'services' ) : array(),
		'empty'   => syn_field( 'services_list_empty', $syn_id ),
	)
);

/*
 * Neither the "why" band nor the figures band renders here. A listing page is a
 * bridge between the homepage and a detail page, and both of those already carry
 * them — three helpings of the same four figures in one journey was the
 * repetition this removes (asked for 31 Aug).
 */
syn_section( 'final-cta' );

get_footer();

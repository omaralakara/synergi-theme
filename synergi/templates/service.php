<?php
/**
 * Template Name: Service page
 *
 * One template, six service lines. The six pages differ in what an editor typed
 * and which accent their service slug selects — nothing else. That is the whole
 * claim of this stage, and the test of it is that Project Management, which has
 * no page today, is built by entering content and changing no code.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/service-fields.php, inc/records.php, parts/page-header.php, sections/*.php.
 *
 * parts/page-header.php owns this page's one <h1> (CLAUDE.md §8) and nothing
 * below emits another. Every section is skipped when its fields are empty, so a
 * half-filled page is short rather than broken.
 *
 * This file composes and does not draw: there is no section markup here, only
 * the decision about which sections run and what they are handed (CLAUDE.md §4).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_id      = get_the_ID();
$syn_service = sanitize_key( syn_field( 'service_ref', $syn_id ) );

/*
 * Declared BEFORE get_header(), because assets are enqueued during wp_head()
 * and a section declared after that renders unstyled. Every section is listed
 * whether or not it ends up rendering: a page with no case study downloads
 * roughly 2 KB of CSS it does not use, which is a smaller cost than the
 * alternative — resolving every field twice, once to decide and once to render.
 * inc/sections.php prints a comment naming anything declared but never rendered,
 * so the waste stays visible rather than becoming invisible habit.
 */
syn_use_sections( array( 'capabilities', 'process', 'case-study', 'why', 'faq', 'related-services', 'final-cta' ) );

get_header();

/*
 * The hero. The page title is the <h1> — parts/page-header.php defaults to it,
 * so the heading is never a field an editor could leave empty or fill with
 * something the browser tab disagrees with.
 *
 * No figures in the hero: the numbers band further down already says them, and
 * saying them twice on one page made the hero busy without adding a fact
 * (removed 27 Aug, after seeing it rendered).
 */
get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => syn_field( 'service_eyebrow', $syn_id ),
		'lede'    => syn_field( 'service_lede', $syn_id ),
		'image'   => syn_field_image_id( 'service_image', $syn_id ),
		'cta'     => syn_field_link( 'service_cta', $syn_id ),
		'cta_alt' => syn_field_link( 'service_cta_alt', $syn_id ),
	)
);

/*
 * No <main> here: header.php opens <main id="main-content"> and footer.php
 * closes it, so a second one would nest two of the same landmark on one page
 * (CLAUDE.md §8, one <main>).
 */
syn_section(
	'capabilities',
	array(
		'heading' => syn_field( 'capabilities_heading', $syn_id ),
		'service' => $syn_service,
		'items'   => syn_field_rows( 'capabilities', $syn_id ),
	)
);

syn_section(
	'process',
	array(
		'heading' => syn_field( 'process_heading', $syn_id ),
		'lede'    => syn_field( 'process_lede', $syn_id ),
		'steps'   => syn_field_rows( 'process', $syn_id ),
	)
);

syn_section(
	'case-study',
	array(
		'heading' => syn_field( 'case_title', $syn_id ),
		'client'  => syn_field( 'case_client', $syn_id ),
		'brief'   => syn_field( 'case_brief', $syn_id ),
		'image'   => syn_field_image_id( 'case_image', $syn_id ),
		'scope'   => syn_field_rows( 'case_scope', $syn_id ),
		'link'    => syn_field_link( 'case_link', $syn_id ),
	)
);

/*
 * The "why" band, unchanged and untouched. Reusing an approved homepage section
 * is what keeps this page inside the design system by construction rather than
 * by discipline.
 */
syn_section( 'why' );

/*
 * The figures band is deliberately NOT here. "Synergi in numbers" is a
 * company-level claim, and it used to render on seven templates — so a visitor
 * walking from the homepage to a listing to a detail page met the same four
 * figures three times. It now appears on the homepage and About Us only, where
 * the company is what the page is about (asked for 31 Aug). The record is
 * unchanged; only who reads it is.
 */

syn_section(
	'faq',
	array(
		'heading' => syn_field( 'faq_heading', $syn_id ),
		'items'   => syn_field_rows( 'faqs', $syn_id ),
	)
);

/*
 * The other five lines, from the services record. Internal linking that
 * maintains itself: a seventh service added at Settings → Site records
 * appears on all six existing pages with no code change.
 */
$syn_others = syn_other_services( $syn_service );

if ( $syn_others ) {
	syn_section(
		'related-services',
		array(
			'eyebrow' => __( 'Keep exploring', 'synergi' ),
			'heading' => __( 'Our other service lines', 'synergi' ),
			'items'   => $syn_others,
		)
	);
}

// No blog band here. Related insights belong on a service page in principle,
// but only once posts are actually tagged to a service — an untargeted list of
// the five most recent posts is filler, not a related read (removed 27 Aug).
syn_section( 'final-cta' );
get_footer();

<?php
/**
 * Template Name: Solution page
 *
 * One template, five solution pages: shared services design and set-up,
 * build–operate–transfer, systems implementation, carve-out and
 * post-acquisition integration, and fractional leadership.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/solution-fields.php, inc/records.php, parts/page-header.php, sections/*.php.
 *
 * COMPOSED ENTIRELY FROM BANDS THAT ALREADY EXIST. Every section below is one
 * the homepage or the six service pages already render, handed different words.
 * There is no CSS and no JavaScript belonging to this template, because a
 * solution page is a service page about a different kind of engagement rather
 * than a second design (CLAUDE.md §4). If a solution ever needs a shape none of
 * these bands can make, that is a new section with its own three files — not a
 * stylesheet that restyles these.
 *
 * parts/page-header.php owns this page's one <h1> (CLAUDE.md §8) and nothing
 * below emits another. Every section skips itself when its fields are empty, so
 * a half-filled page is short rather than broken.
 *
 * THE TITLE, THE DESCRIPTION AND THE CANONICAL ARE YOAST'S. The theme emits no
 * <title>, meta description, canonical or OG tag anywhere (CLAUDE.md §8), so
 * there are no fields for them here — they are Yoast's boxes on the same edit
 * screen. Adding theme fields for them would put two of each on every page,
 * which is a Search Console error rather than better SEO.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_id = get_the_ID();

/*
 * Which solution this page is. Used once, at the bottom, to keep the page out
 * of its own "other solutions" list.
 */
$syn_solution = sanitize_key( syn_field( 'solution_ref', $syn_id ) );

/*
 * Declared BEFORE get_header(), because assets are enqueued during wp_head()
 * and a section declared after that renders unstyled.
 *
 * The two optional bands are declared only when they will actually render. A
 * solution with no cleared proof and no other solutions to link to therefore
 * downloads neither stylesheet — which is what CLAUDE.md §6's conditional
 * loading is for, and what inc/sections.php's declared-but-never-rendered
 * warning would otherwise be telling us about on half these pages.
 */
$syn_has_proof = '' !== trim( (string) syn_field( 'solution_case_title', $syn_id ) );
$syn_others    = function_exists( 'syn_other_solutions' ) ? syn_other_solutions( $syn_solution ) : array();

$syn_sections = array( 'capabilities', 'process' );

if ( $syn_has_proof ) {
	$syn_sections[] = 'case-study';
}

$syn_sections = array_merge( $syn_sections, array( 'why', 'faq' ) );

if ( $syn_others ) {
	$syn_sections[] = 'related-services';
}

$syn_sections[] = 'final-cta';

syn_use_sections( $syn_sections );

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
		'eyebrow' => syn_field( 'solution_eyebrow', $syn_id ),
		'lede'    => syn_field( 'solution_lede', $syn_id ),
		'image'   => syn_field_image_id( 'solution_image', $syn_id ),
		'cta'     => syn_field_link( 'solution_cta', $syn_id ),
		'cta_alt' => syn_field_link( 'solution_cta_alt', $syn_id ),
	)
);

/*
 * No <main> here: header.php opens <main id="main-content"> and footer.php
 * closes it, so a second one would nest two of the same landmark on one page
 * (CLAUDE.md §8, one <main>).
 *
 * No "service" argument to the capabilities band: the six service accents are
 * the six service lines' own, and lending one to a solution would say this page
 * is that service line. Without it the band takes the brand gradient, which is
 * what its own docblock says happens.
 */
syn_section(
	'capabilities',
	array(
		'eyebrow' => __( 'What it covers', 'synergi' ),
		'heading' => syn_field( 'solution_scope_heading', $syn_id ),
		'items'   => syn_field_rows( 'solution_scope', $syn_id ),
	)
);

syn_section(
	'process',
	array(
		'eyebrow' => __( 'How it runs', 'synergi' ),
		'heading' => syn_field( 'solution_method_heading', $syn_id ),
		'lede'    => syn_field( 'solution_method_lede', $syn_id ),
		'steps'   => syn_field_rows( 'solution_method', $syn_id ),
	)
);

/*
 * The proof. It hides itself when the headline is empty, which is the whole
 * point: a solution with nothing cleared to show says nothing rather than
 * showing an empty frame.
 */
if ( $syn_has_proof ) {
	syn_section(
		'case-study',
		array(
			'heading' => syn_field( 'solution_case_title', $syn_id ),
			'client'  => syn_field( 'solution_case_client', $syn_id ),
			'brief'   => syn_field( 'solution_case_brief', $syn_id ),
			'image'   => syn_field_image_id( 'solution_case_image', $syn_id ),
			'scope'   => syn_field_rows( 'solution_case_scope', $syn_id ),
			'link'    => syn_field_link( 'solution_case_link', $syn_id ),
		)
	);
}

/*
 * The "why" band, unchanged and untouched, exactly as the service pages take it:
 * the same reasons to choose Synergi, edited once at Settings → Site records and
 * never retyped per page (CLAUDE.md §7a).
 */
/*
 * The figures band is deliberately NOT here. "Synergi in numbers" is a
 * company-level claim, and it used to render on seven templates — so a visitor
 * walking from the homepage to a listing to a detail page met the same four
 * figures three times. It now appears on the homepage and About Us only, where
 * the company is what the page is about (asked for 31 Aug). The record is
 * unchanged; only who reads it is.
 */
syn_section( 'why' );

syn_section(
	'faq',
	array(
		'heading' => syn_field( 'solution_faq_heading', $syn_id ),
		'items'   => syn_field_rows( 'solution_faqs', $syn_id ),
	)
);

/*
 * The other solutions, from the solutions record. Internal linking that
 * maintains itself: a sixth solution added at Settings → Site records appears
 * on all five existing pages with no code change.
 *
 * The band is called related-services because that is what it was built for,
 * and it is a grid of named cards with links — the shape, not the subject, is
 * what makes it right here. Its heading and eyebrow are passed, so nothing on
 * screen says "service".
 */
if ( $syn_others ) {
	syn_section(
		'related-services',
		array(
			'eyebrow' => __( 'Keep exploring', 'synergi' ),
			'heading' => __( 'Our other solutions', 'synergi' ),
			'items'   => $syn_others,
		)
	);
}

syn_section( 'final-cta' );

get_footer();

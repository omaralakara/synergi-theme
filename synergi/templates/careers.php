<?php
/**
 * Template Name: Careers page
 *
 * Join the team: why people work here, then the open positions.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/careers-fields.php, inc/records.php, parts/page-header.php, sections/*.php.
 *
 * TWO BANDS, BOTH THIS PAGE'S OWN. sections/perks.php is the reasons to join
 * beside a pair of team photographs; sections/positions.php is the roles.
 * The title band is the one every other page uses (CLAUDE.md §4: a template
 * composes sections).
 *
 * WHAT WAS TAKEN OUT, AND WHY (14 Sep). The first cut also carried the
 * figures band, the hiring-steps band and the closing "send your CV" panel.
 * The business removed all three the same day: applications go to the
 * address on each role, not to a general inbox, so a page-wide CV button
 * would only send people somewhere less specific than the role they were
 * reading. The page therefore ends on the roles, which is where its one
 * action lives.
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
 * Where a role's Apply button goes when the role carries no address of its
 * own: the Contact Us page, whose form already reaches the business. The
 * per-role email is the intended path (CLAUDE.md §13, fail gracefully: a role
 * with the address left blank still has a button that goes somewhere).
 */
$syn_fallback_url = syn_contact_url();

$syn_empty_cta = syn_field_link( 'careers_positions_empty_cta', $syn_id );

if ( '' === trim( (string) $syn_empty_cta['url'] ) ) {
	$syn_empty_cta['url'] = $syn_fallback_url;
}

/*
 * Declared BEFORE get_header(), because assets are enqueued during wp_head()
 * and a section declared after that renders unstyled. Both bands always
 * render — the positions band has its own empty state rather than skipping
 * itself, because a careers page with no vacancies still has to say what to
 * do — so the list is fixed.
 */
syn_use_sections( array( 'perks', 'positions' ) );

get_header();

get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => syn_field( 'careers_eyebrow', $syn_id ),
		'lede'    => syn_field( 'careers_lede', $syn_id ),
		'image'   => syn_field_image_id( 'careers_image', $syn_id ),
		'cta'     => syn_field_link( 'careers_cta', $syn_id ),
	)
);

/*
 * No <main> here: header.php opens <main id="main-content"> and footer.php
 * closes it (CLAUDE.md §8, one <main>).
 */

syn_section(
	'perks',
	array(
		'eyebrow' => syn_field( 'careers_perks_eyebrow', $syn_id ),
		'heading' => syn_field( 'careers_perks_heading', $syn_id ),
		'intro'   => syn_field( 'careers_perks_intro', $syn_id ),
		'image'   => syn_field_image_id( 'careers_perks_image', $syn_id ),
		'image_2' => syn_field_image_id( 'careers_perks_image_2', $syn_id ),
		'items'   => syn_field_rows( 'careers_perks', $syn_id ),
	)
);

syn_section(
	'positions',
	array(
		'eyebrow'       => syn_field( 'careers_positions_eyebrow', $syn_id ),
		'heading'       => syn_field( 'careers_positions_heading', $syn_id ),
		'lede'          => syn_field( 'careers_positions_lede', $syn_id ),
		'items'         => syn_field_rows( 'careers_positions_list', $syn_id ),
		'apply_url'     => $syn_fallback_url,
		'empty_heading' => syn_field( 'careers_positions_empty_heading', $syn_id ),
		'empty_text'    => syn_field( 'careers_positions_empty_text', $syn_id ),
		'empty_cta'     => $syn_empty_cta,
	)
);

get_footer();

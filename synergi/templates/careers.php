<?php
/**
 * Template Name: Careers page
 *
 * Join the team: why people work here, the open positions, how hiring works,
 * the company in figures, and an invitation to write in anyway.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/careers-fields.php, inc/records.php, parts/page-header.php, sections/*.php.
 *
 * TWO BANDS ARE NEW AND FOUR ARE BORROWED. sections/perks.php and
 * sections/positions.php exist for this page; the title band, the process
 * steps, the figures and the closing panel are the ones the service pages
 * already use, with this page's words in them (CLAUDE.md §4: a template
 * composes sections). The figures come from the "figures" site record, so this
 * page can never disagree with the homepage about how big the company is
 * (CLAUDE.md §7a).
 *
 * THE ORDER IS THE ARGUMENT. Reasons to join before the vacancies, so a reader
 * who arrived for one role sees the company first; the hiring steps straight
 * after the roles, because "what happens if I apply" is the question a role
 * raises; the figures after that as the proof; and the open-application panel
 * last, for the reader no role fitted. The band colours alternate with it:
 * dark hero, white, paper, navy, brand blue, paper.
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
 * Where an application goes, resolved once here because three bands need it:
 * the hero's second button, every Apply button that has no address of its own,
 * and the closing panel. A typed inbox wins; without one, the Contact Us page,
 * whose form already reaches the business. Validated with is_email() rather
 * than trusted, so a mistyped inbox degrades to the contact page instead of a
 * broken mailto: (CLAUDE.md §13, fail gracefully).
 */
$syn_apply_email = trim( (string) syn_field( 'careers_apply_email', $syn_id ) );
$syn_apply_email = is_email( $syn_apply_email ) ? $syn_apply_email : '';
$syn_apply_url   = '' !== $syn_apply_email ? 'mailto:' . $syn_apply_email : syn_contact_url();

$syn_cta     = syn_field_link( 'careers_cta', $syn_id );
$syn_cta_alt = syn_field_link( 'careers_cta_alt', $syn_id );

if ( '' === trim( (string) $syn_cta_alt['url'] ) ) {
	$syn_cta_alt['url'] = $syn_apply_url;
}

$syn_closing_cta = syn_field_link( 'careers_closing_cta', $syn_id );

if ( '' === trim( (string) $syn_closing_cta['url'] ) ) {
	$syn_closing_cta['url'] = $syn_apply_url;
}

/*
 * Declared BEFORE get_header(), because assets are enqueued during wp_head()
 * and a section declared after that renders unstyled. Every band on this page
 * always renders — the positions band has its own empty state rather than
 * skipping itself, because a careers page with no vacancies still has to say
 * what to do — so the list is fixed.
 */
syn_use_sections( array( 'perks', 'positions', 'process', 'numbers', 'final-cta' ) );

get_header();

get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => syn_field( 'careers_eyebrow', $syn_id ),
		'lede'    => syn_field( 'careers_lede', $syn_id ),
		'image'   => syn_field_image_id( 'careers_image', $syn_id ),
		'cta'     => $syn_cta,
		'cta_alt' => $syn_cta_alt,
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
		'apply_url'     => $syn_apply_url,
		'apply_label'   => $syn_closing_cta['label'],
		'empty_heading' => syn_field( 'careers_positions_empty_heading', $syn_id ),
		'empty_text'    => syn_field( 'careers_positions_empty_text', $syn_id ),
	)
);

syn_section(
	'process',
	array(
		'eyebrow' => syn_field( 'careers_process_eyebrow', $syn_id ),
		'heading' => syn_field( 'careers_process_heading', $syn_id ),
		'lede'    => syn_field( 'careers_process_lede', $syn_id ),
		'steps'   => syn_field_rows( 'careers_process_steps', $syn_id ),
	)
);

syn_section(
	'numbers',
	array(
		'eyebrow' => syn_field( 'careers_numbers_eyebrow', $syn_id ),
		'title'   => syn_field( 'careers_numbers_heading', $syn_id ),
		'lead'    => syn_field( 'careers_numbers_lead', $syn_id ),
	)
);

/*
 * secondary is passed empty on purpose: the record's second button, if the
 * business ever adds one, is a client action and does not belong on a page
 * whose one ask is a CV.
 */
syn_section(
	'final-cta',
	array(
		'eyebrow'   => syn_field( 'careers_closing_eyebrow', $syn_id ),
		'title'     => syn_field( 'careers_closing_heading', $syn_id ),
		'body'      => syn_field( 'careers_closing_body', $syn_id ),
		'primary'   => $syn_closing_cta,
		'secondary' => array(
			'label' => '',
			'url'   => '',
		),
		'note'      => syn_field( 'careers_closing_note', $syn_id ),
	)
);

get_footer();

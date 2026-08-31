<?php
/**
 * Template Name: Global Locations
 *
 * The page at /global-locations/ — every delivery office, as photographs first
 * and then as addresses you can actually use.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/global-locations-fields.php, inc/records.php, parts/page-header.php,
 * sections/*.php.
 *
 * COMPOSED ENTIRELY FROM BANDS THAT ALREADY EXIST, so there is no CSS and no
 * JavaScript belonging to this template (CLAUDE.md §4). The photographic band is
 * the homepage's; the address cards are Contact Us's. That is the point rather
 * than a shortcut: all three pages read the same "locations" record, so an
 * office that moves is retyped once and moves on every page at once
 * (CLAUDE.md §7a).
 *
 * WHY THE CARDS LINK TO CONTACT. sections/locations.php defaults every card's
 * link to /global-locations/ — which is this page. Left alone, six cards would
 * link to the page the reader is already on. The link is therefore pointed at
 * the contact page instead, through a field so it can be changed without a
 * developer.
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
 * Both bands read the same record, so it is fetched once and the page decides
 * from it which bands can render at all. With an empty record neither is
 * declared and neither stylesheet is downloaded (CLAUDE.md §6).
 */
$syn_offices = function_exists( 'syn_record' ) ? syn_record( 'locations' ) : array();

$syn_sections = array();

if ( $syn_offices ) {
	$syn_sections[] = 'locations';
	$syn_sections[] = 'offices';
}

$syn_sections[] = 'final-cta';

syn_use_sections( $syn_sections );

get_header();

get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => syn_field( 'locations_eyebrow', $syn_id ),
		'lede'    => syn_field( 'locations_lede', $syn_id ),
		'image'   => syn_field_image_id( 'locations_image', $syn_id ),
	)
);

/*
 * No <main> here: header.php opens <main id="main-content"> and footer.php
 * closes it (CLAUDE.md §8, one <main>).
 */
if ( $syn_offices ) {

	/*
	 * The photographic band. "places" is deliberately not passed: the section
	 * builds its own rows from the record, including which hub is not open yet
	 * and what badge it wears instead of a link. Passing the raw record here
	 * would throw that away and have to reimplement it.
	 */
	$syn_link = syn_field_link( 'locations_card_link', $syn_id );

	syn_section(
		'locations',
		array(
			'eyebrow'   => syn_field( 'locations_map_eyebrow', $syn_id ),
			'title'     => syn_field( 'locations_map_heading', $syn_id ),
			'lead'      => syn_field( 'locations_map_lede', $syn_id ),
			'link_url'  => '' !== trim( (string) ( $syn_link['url'] ?? '' ) ) ? $syn_link['url'] : syn_contact_url(),
			'link_text' => $syn_link['label'] ?? '',
		)
	);

	// The addresses, phone numbers and maps — the same band Contact Us renders,
	// reading the same record.
	syn_section(
		'offices',
		array(
			'eyebrow' => syn_field( 'locations_offices_eyebrow', $syn_id ),
			'heading' => syn_field( 'locations_offices_heading', $syn_id ),
			'lede'    => syn_field( 'locations_offices_lede', $syn_id ),
			'places'  => $syn_offices,
		)
	);
}

syn_section( 'final-cta' );

get_footer();

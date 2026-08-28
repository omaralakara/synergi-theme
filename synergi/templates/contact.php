<?php
/**
 * Template Name: Contact page
 *
 * Contact Us: every office with its address and how to reach it, the enquiry
 * form, and the company's social accounts.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/contact-fields.php, inc/records.php, parts/page-header.php, sections/*.php.
 *
 * parts/page-header.php owns this page's one <h1> (CLAUDE.md §8) and nothing
 * below emits another. Every band skips itself when it has nothing, so a page
 * with no form and no social accounts is a page of offices rather than a page
 * of empty headings.
 *
 * THE OFFICES AND THE ACCOUNTS ARE RECORDS, NOT PAGE FIELDS. An office address
 * and a LinkedIn URL are facts about the business, and the footer, Global
 * Locations and the market pages will all want them. Typed here they would be
 * typed again everywhere else, and then they would disagree (CLAUDE.md §7a).
 * Both are edited at Settings → Site records.
 *
 * THE MAPS DO NOT LOAD UNTIL SOMEBODY ASKS. CLAUDE.md §2.6 forbids the theme
 * making external requests from the front end, and five embedded Google maps
 * would be five of them plus several hundred kilobytes before a reader has done
 * anything. sections/offices.php ships an address and a link instead, and swaps
 * in the map on a click. See that file for the full reasoning.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_id = get_the_ID();

/*
 * Declared BEFORE get_header(), because assets are enqueued during wp_head()
 * and a section declared after that renders unstyled.
 *
 * The two optional bands are declared only when they will actually render, so a
 * page with no form shortcode set does not download the form band's stylesheet.
 */
$syn_form_shortcode = syn_field( 'contact_form_shortcode', $syn_id );
$syn_offices        = function_exists( 'syn_record' ) ? syn_record( 'locations' ) : array();
$syn_accounts       = function_exists( 'syn_record' ) ? syn_record( 'social' ) : array();

$syn_sections = array();

if ( $syn_offices ) {
	$syn_sections[] = 'offices';
}

if ( '' !== trim( (string) $syn_form_shortcode ) ) {
	$syn_sections[] = 'enquiry';
}

if ( $syn_accounts ) {
	$syn_sections[] = 'social';
}

$syn_sections[] = 'final-cta';

syn_use_sections( $syn_sections );

get_header();

get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => syn_field( 'contact_eyebrow', $syn_id ),
		'lede'    => syn_field( 'contact_lede', $syn_id ),
		'image'   => syn_field_image_id( 'contact_image', $syn_id ),
	)
);

/*
 * No <main> here: header.php opens <main id="main-content"> and footer.php
 * closes it, so a second one would nest two of the same landmark on one page
 * (CLAUDE.md §8, one <main>).
 *
 * No hero buttons either. The page a "Talk to our team" button would point at
 * is this one, and the form is one scroll below.
 */

if ( $syn_offices ) {
	syn_section(
		'offices',
		array(
			'eyebrow' => syn_field( 'contact_offices_eyebrow', $syn_id ),
			'heading' => syn_field( 'contact_offices_heading', $syn_id ),
			'lede'    => syn_field( 'contact_offices_lede', $syn_id ),
			'places'  => $syn_offices,
		)
	);
}

if ( '' !== trim( (string) $syn_form_shortcode ) ) {
	syn_section(
		'enquiry',
		array(
			'eyebrow'   => syn_field( 'contact_form_eyebrow', $syn_id ),
			'heading'   => syn_field( 'contact_form_heading', $syn_id ),
			'lede'      => syn_field( 'contact_form_lede', $syn_id ),
			'note'      => syn_field( 'contact_form_note', $syn_id ),
			'shortcode' => $syn_form_shortcode,
		)
	);
}

if ( $syn_accounts ) {
	syn_section(
		'social',
		array(
			'eyebrow'  => syn_field( 'contact_social_eyebrow', $syn_id ),
			'heading'  => syn_field( 'contact_social_heading', $syn_id ),
			'lede'     => syn_field( 'contact_social_lede', $syn_id ),
			'accounts' => $syn_accounts,
		)
	);
}

// The closing band, the same record every other designed page reads.
syn_section( 'final-cta' );

get_footer();

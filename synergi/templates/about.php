<?php
/**
 * Template Name: About Us
 *
 * The company page: who Synergi is, what it is for, what it values, how it got
 * here, and the figures that back it up.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/about-fields.php, inc/records.php, parts/page-header.php, sections/*.php.
 *
 * parts/page-header.php owns this page's one <h1> (CLAUDE.md §8) and nothing
 * below emits another. Every section skips itself when its fields are empty, so
 * a half-filled page is short rather than broken.
 *
 * This file composes and does not draw: there is no section markup here, only
 * the decision about which sections run and what they are handed (CLAUDE.md §4).
 *
 * WHY THE FIGURES BAND TAKES NO ARGUMENTS. It reads the "figures" site record
 * directly, exactly as the homepage and the six service pages do. The business
 * asked for "the same numbers as the homepage, one set, used everywhere"
 * (CLAUDE.md §7a), and handing this page its own copy of them here is precisely
 * the drift that request was about.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_id = get_the_ID();

/*
 * Declared BEFORE get_header(), because assets are enqueued during wp_head()
 * and a section declared after that renders unstyled. inc/sections.php prints a
 * comment under SYN_DEBUG naming anything declared but never rendered, so a
 * page that skips its journey band shows that in view-source rather than
 * quietly downloading a stylesheet for nothing.
 */
syn_use_sections( array( 'story', 'values', 'journey', 'numbers', 'final-cta' ) );

get_header();

/*
 * The hero. The page title is the <h1>, and the photograph is the page's
 * Featured Image — the same decision templates/service.php took, so a picture
 * never has two owners (CLAUDE.md §7b).
 */
get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => syn_field( 'about_eyebrow', $syn_id ),
		'lede'    => syn_field( 'about_lede', $syn_id ),
		'image'   => (int) get_post_thumbnail_id( $syn_id ),
		'cta'     => syn_field_link( 'about_cta', $syn_id ),
		'cta_alt' => syn_field_link( 'about_cta_alt', $syn_id ),
	)
);

/*
 * No <main> here: header.php opens <main id="main-content"> and footer.php
 * closes it, so a second one would nest two of the same landmark on one page
 * (CLAUDE.md §8, one <main>).
 */

/**
 * The paragraphs repeater, flattened to a plain list of strings.
 *
 * A repeater row is always an array; sections/story.php wants sentences. This
 * turns [ [ 'text' => 'Synergi is…' ] ] into [ 'Synergi is…' ] and drops blank
 * rows on the way, so an editor who clears one does not get a gap in the story.
 *
 * @param string $key    Repeater key.
 * @param int    $postid Page ID.
 * @return string[]
 */
$syn_about_paragraphs = static function ( $key, $postid ) {
	$out = array();

	foreach ( syn_field_rows( $key, $postid ) as $row ) {
		$text = trim( (string) ( $row['text'] ?? '' ) );

		if ( '' !== $text ) {
			$out[] = $text;
		}
	}

	return $out;
};

syn_section(
	'story',
	array(
		'heading'    => syn_field( 'story_heading', $syn_id ),
		'paragraphs' => $syn_about_paragraphs( 'story_paragraphs', $syn_id ),
		'pillars'    => syn_field_rows( 'story_pillars', $syn_id ),
	)
);

syn_section(
	'values',
	array(
		'eyebrow' => __( 'What we stand for', 'synergi' ),
		'heading' => syn_field( 'values_heading', $syn_id ),
		'intro'   => syn_field( 'values_intro', $syn_id ),
		'image'   => syn_field_image_id( 'values_image', $syn_id ),
		'items'   => syn_field_rows( 'values', $syn_id ),
	)
);

syn_section(
	'journey',
	array(
		'eyebrow' => __( 'How we got here', 'synergi' ),
		'heading' => syn_field( 'journey_heading', $syn_id ),
		'lede'    => syn_field( 'journey_lede', $syn_id ),
		'image'   => syn_field_image_id( 'journey_image', $syn_id ),
	)
);

// The figures record and the closing band, both shared with the homepage and
// every service page. See the file header for why neither takes arguments.
syn_section( 'numbers' );
syn_section( 'final-cta' );

get_footer();

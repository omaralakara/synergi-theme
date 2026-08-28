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
syn_use_sections( array( 'story', 'process', 'values', 'journey', 'why', 'numbers', 'final-cta' ) );

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
		'eyebrow'    => __( 'Who we are', 'synergi' ),
		'heading'    => syn_field( 'story_heading', $syn_id ),
		'paragraphs' => $syn_about_paragraphs( 'story_paragraphs', $syn_id ),
		'pillars'    => syn_field_rows( 'story_pillars', $syn_id ),
	)
);

/*
 * Our approach, on the band the six service pages already use for "how we
 * work" (28 Aug). Not a section of its own: this band is a heading, a line and
 * a numbered list, which is exactly what sections/process.php is, and its own
 * default heading is the company deck's phrase — "From assessment to action" —
 * because the deck is where that partial's copy came from in the first place.
 *
 * It replaces the old /our-approach/ page as the place this is said. That page
 * is 1,118 words of material the business says no longer describes what
 * happens; the three stages below are read off the 2026 company overview
 * instead. See inc/about-fields.php for which pages of it.
 */
syn_section(
	'process',
	array(
		'eyebrow' => __( 'Our approach', 'synergi' ),
		'heading' => syn_field( 'approach_heading', $syn_id ),
		'lede'    => syn_field( 'approach_lede', $syn_id ),
		'steps'   => syn_field_rows( 'approach_steps', $syn_id ),
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

/*
 * The timeline is typed milestones since 28 Aug; the picture is what the band
 * falls back to when none are, so a page mid-migration is never empty.
 */
syn_section(
	'journey',
	array(
		'eyebrow'    => __( 'How we got here', 'synergi' ),
		'heading'    => syn_field( 'journey_heading', $syn_id ),
		'lede'       => syn_field( 'journey_lede', $syn_id ),
		'milestones' => syn_field_rows( 'journey_milestones', $syn_id ),
		'image'      => syn_field_image_id( 'journey_image', $syn_id ),
	)
);

/*
 * The three record-driven bands that close the page, none of them taking
 * arguments and all three shared with the homepage and the six service pages.
 * See the file header for why.
 *
 * "Why companies choose Synergi" is here rather than being written again as
 * About Us copy (28 Aug). It already answers the question this page raises —
 * the reader has just been told what the company is, what it values and where
 * it has been — and it is the same paragraph on eight pages, edited once at
 * Settings → Site records. Writing an About-only version of it would be a
 * second copy of the same claim, drifting from the first within a month, which
 * is the failure CLAUDE.md §7a exists to prevent.
 */
syn_section( 'why' );
syn_section( 'numbers' );
syn_section( 'final-cta' );

get_footer();

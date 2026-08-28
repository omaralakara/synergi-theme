<?php
/**
 * Template Name: Podcast page
 *
 * The Executive Podcast Series: what it is, the episodes, the webinars.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/podcast-fields.php, inc/records.php, parts/page-header.php, sections/*.php.
 *
 * WHY THIS PAGE GOT A TEMPLATE. It was the last landing page still rendering a
 * saved Elementor blob, and the blob had two faults no amount of editing would
 * stop coming back: it carried its own hero on top of the template's, so the
 * page said its own name four times before the first sentence; and its six
 * episode addresses had been saved run together into one unbroken string, so
 * WordPress auto-embedded none of them and five episodes were invisible. Both
 * are structural, and both go away once the episodes are rows in a field
 * instead of URLs in a paragraph.
 *
 * parts/page-header.php owns this page's one <h1> (CLAUDE.md §8) and nothing
 * below emits another. Every band skips itself when its rows are empty.
 *
 * COMPOSED FROM EXISTING BANDS BAR ONE. The hero, the "about" band and the
 * closing call to action are all bands other pages already render. Only
 * sections/episodes.php is new, because nothing in the theme rendered a video
 * and a page about a podcast needs to.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_id = get_the_ID();

$syn_episodes = syn_field_rows( 'podcast_episodes', $syn_id );
$syn_webinars = syn_field_rows( 'podcast_webinars', $syn_id );

/*
 * Declared BEFORE get_header(), because assets are enqueued during wp_head()
 * and a section declared after that renders unstyled. The episode band's files
 * are asked for once however many times it renders.
 */
$syn_sections = array( 'story' );

if ( $syn_episodes || $syn_webinars ) {
	$syn_sections[] = 'episodes';
}

$syn_sections[] = 'final-cta';

syn_use_sections( $syn_sections );

get_header();

get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => syn_field( 'podcast_eyebrow', $syn_id ),
		'lede'    => syn_field( 'podcast_lede', $syn_id ),
		'image'   => (int) get_post_thumbnail_id( $syn_id ),
		'cta'     => syn_field_link( 'podcast_cta', $syn_id ),
	)
);

/*
 * No <main> here: header.php opens <main id="main-content"> and footer.php
 * closes it (CLAUDE.md §8, one <main>).
 */

/**
 * The paragraphs repeater, flattened to a plain list of strings.
 *
 * The same four lines templates/about.php and templates/market.php carry, local
 * to each file on purpose: a shared helper for it would be a function nobody
 * could name.
 *
 * @param string $key Repeater key.
 * @return string[]
 */
$syn_paragraphs = static function ( $key ) use ( $syn_id ) {
	$out = array();

	foreach ( syn_field_rows( $key, $syn_id ) as $row ) {
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
		'eyebrow'    => syn_field( 'podcast_about_eyebrow', $syn_id ),
		'heading'    => syn_field( 'podcast_about_heading', $syn_id ),
		'paragraphs' => $syn_paragraphs( 'podcast_about_paragraphs' ),
	)
);

/*
 * The two video bands. They alternate surface because they sit one on top of
 * the other, and two identical stripes would read as one very long section —
 * the template decides that, not a field (CLAUDE.md §7c).
 */
syn_section(
	'episodes',
	array(
		'eyebrow' => syn_field( 'podcast_episodes_eyebrow', $syn_id ),
		'heading' => syn_field( 'podcast_episodes_heading', $syn_id ),
		'lede'    => syn_field( 'podcast_episodes_lede', $syn_id ),
		'tone'    => 'paper',
		'items'   => $syn_episodes,
	)
);

syn_section(
	'episodes',
	array(
		'eyebrow' => syn_field( 'podcast_webinars_eyebrow', $syn_id ),
		'heading' => syn_field( 'podcast_webinars_heading', $syn_id ),
		'lede'    => syn_field( 'podcast_webinars_lede', $syn_id ),
		'tone'    => 'white',
		'items'   => $syn_webinars,
	)
);

// The closing band, the same record every other designed page reads. It
// replaces the "Looking to strengthen your business operations?" panel the
// Elementor page carried, which said the same thing in its own words.
syn_section( 'final-cta' );

get_footer();

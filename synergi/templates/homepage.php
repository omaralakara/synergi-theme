<?php
/**
 * Template Name: Homepage (rebuild)
 *
 * The homepage composition, assembled from sections/.
 *
 * TEMPORARY LOCATION. This is a page template rather than front-page.php on
 * purpose: pointing front-page.php at the live homepage while only some of the
 * twelve sections exist would replace a finished Elementor page with a
 * half-built one. Sections are verified here, on a draft page, exactly as the
 * previous developer used draft page #10479.
 *
 * When all twelve sections are done and checked, this file's body becomes
 * front-page.php and this template is deleted. The composition is written once
 * so there is nothing to keep in sync in the meantime.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, sections/*.php.
 *
 * The hero owns this page's one <h1> (CLAUDE.md §8), so nothing here renders
 * parts/page-header.php.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/*
 * Declared BEFORE get_header(), because assets are enqueued during wp_head()
 * and a section declared after that renders unstyled. inc/sections.php prints
 * a comment naming the mistake if this ever slips below the call.
 *
 * Sections are added to this list as they are built, in the design's own
 * order — 01 hero, 02 core services, 03 shared services, 04 industries,
 * 05 why synergi, 06 numbers, 07 partners, 08 locations, 09 blog,
 * 10 instagram, 11 podcast, 12 final CTA.
 */
syn_use_sections( array( 'hero', 'services', 'shared-services', 'industries', 'why', 'numbers', 'partners', 'locations', 'blog', 'instagram', 'podcast', 'final-cta' ) );

get_header();

/*
 * The hero photograph is a real attachment rather than a path, so it goes
 * through core and gets srcset, sizes and the attachment's own alt text for
 * free (CLAUDE.md §6 and §8).
 *
 * It comes from the page's Featured Image. That is deliberate: the photograph
 * is content, so it belongs in the database and in reach of someone who does
 * not edit PHP (CLAUDE.md §1) — and the Featured Image box is already in the
 * editor sidebar, so it needs no UI of its own. Stage 6's hero field, when it
 * exists, passes image_id explicitly and takes precedence over this.
 *
 * get_queried_object_id() rather than the loop, because this runs outside it.
 * With no Featured Image set the call returns 0 and sections/hero.php renders
 * the hero on its flat ink background — a legitimate fallback, not a broken
 * image.
 */
/*
 * Stage 6b. From here down the bands are handed their words rather than
 * carrying them, and this file is where the two meet.
 *
 * Each syn_field() call returns what an editor typed, or — when the box is
 * empty — the approved string registered as that field's default in
 * inc/homepage-fields.php. Those defaults are the exact strings the partials
 * were already printing, so a homepage with nothing typed into it renders
 * byte for byte what it rendered before this stage. Clearing a box restores
 * the approved wording rather than emptying the page (CLAUDE.md §7c).
 *
 * The four bands NOT given arguments here — why, numbers, locations and the
 * final CTA — are the ones that render on the service pages too. They read
 * their own site records directly, because a value shared by seven pages
 * cannot come from one page's postmeta (CLAUDE.md §7a).
 */
$syn_home_id = get_queried_object_id();

/**
 * One repeater column, flattened to a plain list of strings.
 *
 * The hero's cycling words and the shared-services markets and pills are lists
 * of single values, but a repeater row is always an array. This turns
 * [ [ 'word' => 'cost' ] ] into [ 'cost' ], and drops blank rows on the way so
 * an editor who leaves one empty does not get a gap in the sentence.
 *
 * @param string $key    Field key.
 * @param int    $post_id Page ID.
 * @param string $column Which subfield to take.
 * @return string[]
 */
$syn_home_list = static function ( $key, $post_id, $column ) {
	$out = array();

	foreach ( syn_field_rows( $key, $post_id ) as $row ) {
		$value = trim( (string) ( $row[ $column ] ?? '' ) );

		if ( '' !== $value ) {
			$out[] = $value;
		}
	}

	return $out;
};

/*
 * The hero photograph is a real attachment rather than a path, so it goes
 * through core and gets srcset, sizes and the attachment's own alt text for
 * free (CLAUDE.md §6 and §8). It stays the page's Featured Image and has no
 * field of its own: the box is already in the editor sidebar, and a second
 * control would give one picture two owners.
 */
$syn_hero_words   = $syn_home_list( 'home_hero_motion_words', $syn_home_id, 'word' );
$syn_hero_buttons = array();

foreach ( syn_field_rows( 'home_hero_buttons', $syn_home_id ) as $syn_row ) {
	// Half a button is not a button — the same rule parts/page-header.php uses.
	if ( '' === trim( (string) ( $syn_row['label'] ?? '' ) ) || '' === trim( (string) ( $syn_row['url'] ?? '' ) ) ) {
		continue;
	}

	$syn_hero_buttons[] = array(
		'label' => $syn_row['label'],
		'url'   => $syn_row['url'],
		'style' => 'light' === ( $syn_row['style'] ?? '' ) ? 'light' : 'primary',
	);
}

syn_section(
	'hero',
	array_filter(
		array(
			'image_id'     => (int) apply_filters( 'syn_hero_image_id', get_post_thumbnail_id( $syn_home_id ) ),
			'title_lead'   => syn_field( 'home_hero_title_lead', $syn_home_id ),
			'title_focus'  => syn_field( 'home_hero_title_focus', $syn_home_id ),
			'lead'         => syn_field( 'home_hero_lead', $syn_home_id ),
			'motion_lead'  => syn_field( 'home_hero_motion_lead', $syn_home_id ),
			'motion_words' => $syn_hero_words,
			'buttons'      => $syn_hero_buttons,
		),
		/*
		 * An empty list means "nothing was typed", not "show nothing", so it is
		 * dropped and the partial's own approved list renders. Scalars are never
		 * empty here: syn_field() has already substituted the default.
		 */
		static function ( $value ) {
			return ! is_array( $value ) || $value;
		}
	)
);

syn_section(
	'services',
	array(
		'eyebrow' => syn_field( 'home_services_eyebrow', $syn_home_id ),
		'title'   => syn_field( 'home_services_title', $syn_home_id ),
		'lead'    => syn_field( 'home_services_lead', $syn_home_id ),
	)
);

$syn_shared_args = array(
	'eyebrow' => syn_field( 'home_shared_eyebrow', $syn_home_id ),
	'title'   => syn_field( 'home_shared_title', $syn_home_id ),
);

$syn_shared_markets = $syn_home_list( 'home_shared_markets', $syn_home_id, 'name' );
$syn_shared_chips   = $syn_home_list( 'home_shared_chips', $syn_home_id, 'name' );

if ( $syn_shared_markets ) {
	$syn_shared_args['markets'] = $syn_shared_markets;
}

if ( $syn_shared_chips ) {
	$syn_shared_args['chips'] = $syn_shared_chips;
}

// Both halves or neither, so a half-filled pair cannot produce a button with
// nowhere to go.
$syn_shared_cta_label = syn_field( 'home_shared_cta_label', $syn_home_id );
$syn_shared_cta_url   = syn_field( 'home_shared_cta_url', $syn_home_id );

if ( '' !== $syn_shared_cta_label && '' !== $syn_shared_cta_url ) {
	$syn_shared_args['cta'] = array(
		'label' => $syn_shared_cta_label,
		'url'   => $syn_shared_cta_url,
	);
}

syn_section( 'shared-services', $syn_shared_args );

$syn_industry_cards = array();

foreach ( syn_field_rows( 'home_industries_cards', $syn_home_id ) as $syn_row ) {
	if ( '' === trim( (string) ( $syn_row['title'] ?? '' ) ) ) {
		continue;
	}

	$syn_industry_cards[] = array(
		'image_id'    => (int) ( $syn_row['image'] ?? 0 ),
		'title'       => $syn_row['title'],
		'preview'     => $syn_row['preview'] ?? '',
		'description' => $syn_row['description'] ?? '',
	);
}

syn_section(
	'industries',
	$syn_industry_cards
		? array(
			'eyebrow' => syn_field( 'home_industries_eyebrow', $syn_home_id ),
			'title'   => syn_field( 'home_industries_title', $syn_home_id ),
			'cards'   => $syn_industry_cards,
		)
		: array(
			'eyebrow' => syn_field( 'home_industries_eyebrow', $syn_home_id ),
			'title'   => syn_field( 'home_industries_title', $syn_home_id ),
		)
);

// Reads the "why" and "why_cards" records itself — seven pages, one source.
syn_section( 'why' );

// Reads the "figures" record itself, since Stage 6a.
syn_section( 'numbers' );

$syn_partner_logos = array();

foreach ( syn_field_rows( 'home_partners_logos', $syn_home_id ) as $syn_row ) {
	$syn_logo_id = (int) ( $syn_row['image'] ?? 0 );

	if ( $syn_logo_id ) {
		$syn_partner_logos[] = array( 'image_id' => $syn_logo_id );
	}
}

$syn_partner_args = array(
	'eyebrow' => syn_field( 'home_partners_eyebrow', $syn_home_id ),
	'title'   => syn_field( 'home_partners_title', $syn_home_id ),
	'lead'    => syn_field( 'home_partners_lead', $syn_home_id ),
);

if ( $syn_partner_logos ) {
	$syn_partner_args['logos'] = $syn_partner_logos;
}

syn_section( 'partners', $syn_partner_args );

// Reads the "locations" record itself — the homepage, Contact Us and Global
// Locations all show the same offices (CLAUDE.md §7a).
syn_section( 'locations' );

$syn_blog_args = array(
	'eyebrow'   => syn_field( 'home_blog_eyebrow', $syn_home_id ),
	'title'     => syn_field( 'home_blog_title', $syn_home_id ),
	'link_text' => syn_field( 'home_blog_link_text', $syn_home_id ),
);

$syn_blog_url = syn_field( 'home_blog_link_url', $syn_home_id );

// Left empty the partial resolves the Posts page itself, which is a better
// default than anything that could be typed here.
if ( '' !== $syn_blog_url ) {
	$syn_blog_args['link_url'] = $syn_blog_url;
}

syn_section( 'blog', $syn_blog_args );

syn_section(
	'instagram',
	array(
		'eyebrow'   => syn_field( 'home_instagram_eyebrow', $syn_home_id ),
		'title'     => syn_field( 'home_instagram_title', $syn_home_id ),
		'link_text' => syn_field( 'home_instagram_link_text', $syn_home_id ),
		'link_url'  => syn_field( 'home_instagram_link_url', $syn_home_id ),
	)
);

$syn_podcast_args = array(
	'eyebrow' => syn_field( 'home_podcast_eyebrow', $syn_home_id ),
	'title'   => syn_field( 'home_podcast_title', $syn_home_id ),
	'lead'    => syn_field( 'home_podcast_lead', $syn_home_id ),
	'body'    => syn_field( 'home_podcast_body', $syn_home_id ),
	'badge'   => syn_field( 'home_podcast_badge', $syn_home_id ),
);

$syn_podcast_image = syn_field_image_id( 'home_podcast_image', $syn_home_id );

if ( $syn_podcast_image ) {
	$syn_podcast_args['image_id'] = $syn_podcast_image;
}

syn_section( 'podcast', $syn_podcast_args );

// Reads the "final_cta" record itself — seven pages, one closing band.
syn_section( 'final-cta' );

get_footer();

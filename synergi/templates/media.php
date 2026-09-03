<?php
/**
 * Template Name: Media hub
 *
 * The page at /media/ — the case studies, the newest articles, the latest
 * podcast episodes and the Instagram feed, every band one the site already
 * has.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/media-fields.php, parts/page-header.php, sections/*.php.
 *
 * COMPOSED ENTIRELY FROM BANDS THAT ALREADY EXIST, so there is no CSS and no
 * JavaScript belonging to this template (CLAUDE.md §4).
 *
 * WHAT THIS PAGE IS, AND WHAT IT IS NOT. /media/ was a 30-word stub, and
 * synergi-build-plan.md §6 decision 4 asked whether Blog, Media and Executive
 * Podcast should become one destination. The answer taken on 31 Aug was the
 * cheap half of it: Media becomes the hub in the MENU — the podcast, the case
 * studies and the blog are its children there — while the page itself shows the
 * two things the business named, the articles and the social feed. It does not
 * try to be a filtered index of everything, because each of those things
 * already has an archive of its own that does the job better.
 *
 * The blog band shows the newest posts and links to /blog/ for the rest, so
 * nothing here needs maintaining as posts are published.
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
 * The latest three podcast episodes, read from the podcast page's own episodes
 * field rather than retyped here, so publishing an episode there updates this
 * band with no edit anywhere (CLAUDE.md §7a). The newest episode sits at the
 * top of that repeater, so the first three rows are the latest three. Fetched
 * before syn_use_sections() because the band is only declared when there is
 * something to show.
 */
$syn_podcast_id       = function_exists( 'syn_podcast_page_id' ) ? syn_podcast_page_id() : 0;
$syn_podcast_episodes = $syn_podcast_id ? array_slice( syn_field_rows( 'podcast_episodes', $syn_podcast_id ), 0, 3 ) : array();

/*
 * Declared BEFORE get_header(), because assets are enqueued during wp_head() and
 * a section declared after that renders unstyled.
 *
 * The Instagram band is declared unconditionally because it decides for itself
 * whether the feed plugin is present: with the plugin gone it renders its
 * heading and follow button and omits the feed, which is a tidy section rather
 * than a broken one.
 */
$syn_sections = array( 'case-studies', 'blog' );

if ( $syn_podcast_episodes ) {
	$syn_sections[] = 'episodes';
}

$syn_sections[] = 'instagram';
$syn_sections[] = 'final-cta';

syn_use_sections( $syn_sections );

get_header();

get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => syn_field( 'media_eyebrow', $syn_id ),
		'lede'    => syn_field( 'media_lede', $syn_id ),
		'image'   => syn_field_image_id( 'media_image', $syn_id ),
	)
);

/*
 * No <main> here: header.php opens <main id="main-content"> and footer.php
 * closes it (CLAUDE.md §8, one <main>).
 *
 * link_url is passed only when an editor has filled it. Left empty, the band
 * resolves the Posts page set in Settings → Reading, which is a better default
 * than any path typed here — same reasoning as the homepage.
 */
$syn_blog_args = array(
	'eyebrow'   => syn_field( 'media_blog_eyebrow', $syn_id ),
	'title'     => syn_field( 'media_blog_heading', $syn_id ),
	'link_text' => syn_field( 'media_blog_link_text', $syn_id ),
	'count'     => 6,
);

$syn_blog_url = syn_field( 'media_blog_link_url', $syn_id );

if ( '' !== trim( (string) $syn_blog_url ) ) {
	$syn_blog_args['link_url'] = $syn_blog_url;
}

/*
 * Case studies above the blog — added 2 Sep at the business's request, so the
 * Media hub leads with proof before opinion. The scrollable variant, like the
 * blog band below it: six recent studies in a row that swipes and pages, with
 * the full grid behind the View-all button.
 */
syn_section(
	'case-studies',
	array(
		'count'     => 6,
		'scroll'    => true,
		'lede'      => __( 'How organizations across the Gulf run their operations with Synergi.', 'synergi' ),
		'link_url'  => home_url( '/case-studies/' ),
		'link_text' => __( 'View all case studies', 'synergi' ),
	)
);

syn_section( 'blog', $syn_blog_args );

/*
 * The latest three episodes, with the button through to the full podcast page
 * — added 3 Sep at the business's request. tone stays white: the blog band
 * above ends in a hairline, and the Instagram band below sits on paper, so
 * white is what keeps the three reading as separate stripes (a composition
 * decision, so it is the template's, not a field's — CLAUDE.md §7c).
 */
if ( $syn_podcast_episodes ) {
	syn_section(
		'episodes',
		array(
			'eyebrow'   => __( 'Listen', 'synergi' ),
			'heading'   => __( 'Latest Podcast Episodes', 'synergi' ),
			'lede'      => __( 'Senior leaders on how leadership, strategy and operations align to drive business impact across the MENA region.', 'synergi' ),
			'tone'      => 'white',
			'items'     => $syn_podcast_episodes,
			'link_url'  => get_permalink( $syn_podcast_id ),
			'link_text' => __( 'View all episodes', 'synergi' ),
		)
	);
}

syn_section(
	'instagram',
	array(
		'eyebrow'   => syn_field( 'media_social_eyebrow', $syn_id ),
		'title'     => syn_field( 'media_social_heading', $syn_id ),
		'link_text' => syn_field( 'media_social_link_text', $syn_id ),
		'link_url'  => syn_field( 'media_social_link_url', $syn_id ),
	)
);

syn_section( 'final-cta' );

get_footer();

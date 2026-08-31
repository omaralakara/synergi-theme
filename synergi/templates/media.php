<?php
/**
 * Template Name: Media hub
 *
 * The page at /media/ — the newest articles and the Instagram feed, on the two
 * bands the homepage already uses.
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
 * Declared BEFORE get_header(), because assets are enqueued during wp_head() and
 * a section declared after that renders unstyled.
 *
 * The Instagram band is declared unconditionally because it decides for itself
 * whether the feed plugin is present: with the plugin gone it renders its
 * heading and follow button and omits the feed, which is a tidy section rather
 * than a broken one.
 */
syn_use_sections( array( 'blog', 'instagram', 'final-cta' ) );

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

syn_section( 'blog', $syn_blog_args );

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

<?php
/**
 * One portal role's page: /careers/<title>-<id>/.
 *
 * Loaded by: the Synergi Careers plugin. Its template_include filter looks
 * for exactly this path in the theme (locate_template(
 * 'synergi-careers/single-vacancy.php' )) before falling back to its own
 * templates/single-vacancy.php, so the folder name and the file name are the
 * plugin's contract, not the theme's choice (CLAUDE.md §4 would otherwise
 * put it under templates/).
 * Depends on: header.php, footer.php, inc/sections.php, inc/careers-feed.php,
 * inc/careers-fields.php, parts/page-header.php, sections/positions.php.
 *
 * WHY THE THEME OVERRIDES IT. The plugin's own template opens a second
 * <main id="main-content"> inside the one header.php already opened, and
 * loads none of the theme's stylesheets — the theme enqueues a section's CSS
 * only when a template declares it before get_header(), which a plugin
 * template cannot know. This file declares the positions band the way every
 * other template does, so the role page is styled by positions.css and
 * page-header.css and by nothing else new (CLAUDE.md §6, §13).
 *
 * WHAT STAYS THE PLUGIN'S. The route, the 301 from an old title, the 404 for
 * a role that is not open, the <title>, Yoast's canonical and Open Graph, the
 * sitemap entry and the JobPosting markup in <head> are all the plugin's and
 * happen before and around this file. This file only draws the page.
 *
 * parts/page-header.php owns the page's one <h1> (CLAUDE.md §8): the role's
 * title, under the careers page's own eyebrow. The band is the flat navy one,
 * not the photographic hero — a role page is read for the role, and the
 * careers page's photograph is one click back.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_role = function_exists( 'syn_careers_feed_role' ) ? syn_careers_feed_role() : null;

/*
 * The plugin only sends an open role here, but a template must not trust
 * that: if the feed changed between the route and the render, this is a 404,
 * not a page with an empty card (CLAUDE.md §13, fail gracefully).
 */
if ( ! $syn_role ) {
	status_header( 404 );
	nocache_headers();
	get_template_part( '404' );
	return;
}

/*
 * The careers page in this language, for the eyebrow above the title and the
 * way back under the card. Found by its template, so a retitled or re-slugged
 * page still answers; with none published, the eyebrow is the field's own
 * default and the button goes to /careers/ resolved in the current language.
 */
$syn_careers_id = syn_careers_page_id();
$syn_eyebrow    = syn_field( 'careers_eyebrow', $syn_careers_id ? $syn_careers_id : null );
$syn_back_url   = ( $syn_careers_id ? get_permalink( $syn_careers_id ) : syn_page_url( 'careers' ) ) . '#positions';

// Declared BEFORE get_header(), or the band renders unstyled (inc/sections.php).
syn_use_sections( array( 'positions' ) );

get_header();

get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => $syn_eyebrow,
		'title'   => $syn_role['title'],
		'lede'    => $syn_role['summary'],
	)
);

/*
 * No <main> here: header.php opens <main id="main-content"> and footer.php
 * closes it (CLAUDE.md §8, one <main>).
 */

syn_section(
	'positions',
	array(
		'single' => true,
		'items'  => array( $syn_role ),
		'back'   => array(
			'url'   => $syn_back_url,
			'label' => __( 'See all open positions', 'synergi' ),
		),
	)
);

get_footer();

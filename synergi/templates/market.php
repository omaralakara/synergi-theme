<?php
/**
 * Template Name: Market page
 *
 * One template for every market page — the Markets overview, Saudi Arabia, the
 * United Arab Emirates, and any country added later that has genuinely
 * market-specific content to carry.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/market-fields.php, inc/records.php, parts/page-header.php, sections/*.php.
 *
 * WHY THESE PAGES EXIST. The homepage is a clean international Synergi
 * homepage; it should not be competing with itself for Saudi, UAE, GCC and Gulf
 * at once. The market pages carry that intent instead, one keyword cluster
 * each, and each one has to be a useful destination in its own right rather
 * than a funnel back to the homepage.
 *
 * COMPOSED ENTIRELY FROM BANDS THAT ALREADY EXIST. Every section below is one
 * the homepage or the service pages already render, handed different words:
 * the page header, the story band, the services deck, the industries queue, the
 * why deck, the case study, the figures, the FAQ, the blog band, the related
 * grid and the closing CTA. There is no market.css and no market.js, and there
 * is no new visual direction — a market page is a relative of the approved
 * homepage by construction, not by discipline (CLAUDE.md §4).
 *
 * ONE <h1>, AND IT IS THE PAGE TITLE. parts/page-header.php emits it and
 * nothing below emits another (CLAUDE.md §8). The SEO title is Yoast's and is
 * free to differ from it — as are the meta description, the canonical, the
 * focus keyword and the social image. The theme emits none of those anywhere,
 * so there are no fields for them here; two of each in one <head> is a Search
 * Console error rather than better SEO.
 *
 * THE URL IS THE PAGE SLUG. /markets/saudi-arabia/ is this page published under
 * a "Markets" parent page — a field WordPress already has, and one that stays
 * domain-agnostic for the move to synergibpo.com (CLAUDE.md §12). Nothing here
 * writes a URL.
 *
 * BREADCRUMBS ARE NOT EMITTED HERE, and that is a gap rather than a decision:
 * Yoast's breadcrumb belongs in parts/page-header.php so every template gets
 * it at once, and that file is shared. See the handover note for what it needs.
 *
 * NO hreflang. The launch is English-only; hreflang comes with the Arabic
 * versions, and pointing it at markets rather than languages would be wrong in
 * a way that is hard to unpick later.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_id = get_the_ID();

/*
 * Which market this page is. Used once, near the bottom, to keep the page out
 * of its own "other markets" list.
 */
$syn_market = sanitize_key( syn_field( 'market_ref', $syn_id ) );

/*
 * Declared BEFORE get_header(), because assets are enqueued during wp_head()
 * and a section declared after that renders unstyled.
 *
 * The three optional bands are declared only when they will actually render. A
 * market page with no cleared proof, no insights band switched on and no
 * sibling markets therefore downloads none of those three stylesheets — which
 * is what CLAUDE.md §6's conditional loading is for, and what
 * inc/sections.php's declared-but-never-rendered warning would otherwise be
 * telling us about on most of these pages.
 */
$syn_has_proof    = '' !== trim( (string) syn_field( 'market_case_title', $syn_id ) );
$syn_blog_heading = syn_field( 'market_blog_heading', $syn_id );
$syn_others       = function_exists( 'syn_other_markets' ) ? syn_other_markets( $syn_market ) : array();

$syn_sections = array( 'story', 'services', 'industries', 'why' );

if ( $syn_has_proof ) {
	$syn_sections[] = 'case-study';
}

$syn_sections = array_merge( $syn_sections, array( 'numbers', 'faq' ) );

if ( '' !== $syn_blog_heading ) {
	$syn_sections[] = 'blog';
}

if ( $syn_others ) {
	$syn_sections[] = 'related-services';
}

$syn_sections[] = 'final-cta';

syn_use_sections( $syn_sections );

get_header();

get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => syn_field( 'market_eyebrow', $syn_id ),
		'lede'    => syn_field( 'market_lede', $syn_id ),
		'image'   => syn_field_image_id( 'market_image', $syn_id ),
		'cta'     => syn_field_link( 'market_cta', $syn_id ),
		'cta_alt' => syn_field_link( 'market_cta_alt', $syn_id ),
	)
);

/*
 * No <main> here: header.php opens <main id="main-content"> and footer.php
 * closes it, so a second one would nest two of the same landmark on one page
 * (CLAUDE.md §8, one <main>).
 */

/**
 * One repeater column, flattened to a plain list of strings.
 *
 * A repeater row is always an array; the story band wants sentences. This turns
 * [ [ 'text' => 'Organizations here…' ] ] into [ 'Organizations here…' ] and
 * drops blank rows on the way, so an editor who clears one does not get a gap.
 *
 * The same helper templates/homepage.php and templates/about.php both carry. It
 * is four lines and local to each file on purpose: a shared helper for it would
 * be a function nobody could name.
 *
 * @param string $key    Repeater key.
 * @param string $column Which subfield to take.
 * @return string[]
 */
$syn_market_list = static function ( $key, $column ) use ( $syn_id ) {
	$out = array();

	foreach ( syn_field_rows( $key, $syn_id ) as $row ) {
		$value = trim( (string) ( $row[ $column ] ?? '' ) );

		if ( '' !== $value ) {
			$out[] = $value;
		}
	}

	return $out;
};

/*
 * 2. THE INTRODUCTION. The About Us story band: a heading, a standfirst, short
 * columns, and up to two cards that turn over. It skips itself when nothing is
 * typed, which is why a market page can be published with the hero and the
 * services alone.
 */
syn_section(
	'story',
	array(
		'eyebrow'    => syn_field( 'market_eyebrow', $syn_id ),
		'heading'    => syn_field( 'market_story_heading', $syn_id ),
		'paragraphs' => $syn_market_list( 'market_story_paragraphs', 'text' ),
		'pillars'    => syn_field_rows( 'market_story_pillars', $syn_id ),
	)
);

/*
 * 3. THE SERVICES. The editor chooses which lines this market leads with and in
 * what order; everything ON the card — its name, its summary, its icon and
 * where it links — comes from the services record, so a service renamed once is
 * renamed on every market page (CLAUDE.md §7a).
 *
 * The card's "slug" is the record's icon where one is set, because the services
 * deck uses that one value for both the icon file and the accent. Falling back
 * to the reference means a service line with no icon still renders, just
 * without one.
 *
 * Choosing nothing shows every service line, which is the right default for the
 * Markets overview: it is about all of them.
 */
$syn_market_service_refs = $syn_market_list( 'market_services', 'service' );
$syn_service_rows        = function_exists( 'syn_record' ) ? syn_record( 'services' ) : array();
$syn_service_cards       = array();

foreach ( $syn_service_rows as $syn_row ) {
	$syn_ref  = sanitize_key( $syn_row['slug'] ?? '' );
	$syn_name = trim( (string) ( $syn_row['name'] ?? '' ) );

	if ( '' === $syn_ref || '' === $syn_name ) {
		continue;
	}

	if ( $syn_market_service_refs && ! in_array( $syn_ref, $syn_market_service_refs, true ) ) {
		continue;
	}

	$syn_service_cards[ $syn_ref ] = array(
		'slug'    => sanitize_key( $syn_row['icon'] ?? '' ) ? sanitize_key( $syn_row['icon'] ) : $syn_ref,
		'name'    => $syn_name,
		'summary' => trim( (string) ( $syn_row['summary'] ?? '' ) ),
		'url'     => trim( (string) ( $syn_row['url'] ?? '' ) ),
	);
}

/*
 * Put the cards in the editor's order rather than the record's. The loop above
 * walks the record, because that is where a card's words live; this reorders
 * what it found, so "lead with Technology & AI here" is a row moved in the
 * editor and nothing else.
 */
if ( $syn_market_service_refs ) {
	$syn_ordered = array();

	foreach ( $syn_market_service_refs as $syn_ref ) {
		if ( isset( $syn_service_cards[ $syn_ref ] ) ) {
			$syn_ordered[] = $syn_service_cards[ $syn_ref ];
		}
	}

	$syn_service_cards = $syn_ordered;
} else {
	$syn_service_cards = array_values( $syn_service_cards );
}

/*
 * With an empty services record there is nothing to build a card from, and the
 * deck's own hard-coded six would then render — six service lines that may not
 * be the six this market offers. Passing an explicit empty list is the
 * instruction not to: the band renders nothing at all.
 */
syn_section(
	'services',
	array(
		'eyebrow' => syn_field( 'market_services_eyebrow', $syn_id ),
		'title'   => syn_field( 'market_services_heading', $syn_id ),
		'lead'    => syn_field( 'market_services_lead', $syn_id ),
		'cards'   => $syn_service_cards,
	)
);

/*
 * 4. THE INDUSTRIES. The homepage's photographic queue with this market's own
 * selection. Left empty, the "cards" key is dropped entirely rather than passed
 * as an empty array — the two mean different things to that band, and here
 * "nothing typed" should fall through to the site-wide list rather than render
 * an empty queue.
 */
$syn_industry_cards = array();

foreach ( syn_field_rows( 'market_industries_cards', $syn_id ) as $syn_row ) {
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

$syn_industry_args = array(
	'eyebrow' => syn_field( 'market_industries_eyebrow', $syn_id ),
	'title'   => syn_field( 'market_industries_heading', $syn_id ),
);

if ( $syn_industry_cards ) {
	$syn_industry_args['cards'] = $syn_industry_cards;
}

syn_section( 'industries', $syn_industry_args );

/*
 * 5. WHY SYNERGI HERE. Passed only when this market has its own answer; with
 * nothing typed the band reads the site-wide "why" record instead, exactly as
 * it does on the homepage and the service pages. A market with nothing specific
 * to say should say the general thing rather than a reworded copy of it.
 *
 * Each key is added only when it has a value, because the band treats "not
 * passed" and "passed empty" as different instructions — the second would blank
 * a heading rather than fall back to the record.
 */
$syn_why_cards = array();

foreach ( syn_field_rows( 'market_why_cards', $syn_id ) as $syn_row ) {
	if ( '' === trim( (string) ( $syn_row['title'] ?? '' ) ) ) {
		continue;
	}

	$syn_why_cards[] = array(
		'image_id'    => (int) ( $syn_row['image'] ?? 0 ),
		'title'       => $syn_row['title'],
		'short'       => '' !== trim( (string) ( $syn_row['short'] ?? '' ) ) ? $syn_row['short'] : $syn_row['title'],
		'description' => $syn_row['description'] ?? '',
	);
}

$syn_why_args = array();

foreach ( array( 'eyebrow' => 'market_why_eyebrow', 'title' => 'market_why_heading', 'intro' => 'market_why_intro' ) as $syn_arg => $syn_key ) {
	$syn_value = syn_field( $syn_key, $syn_id );

	if ( '' !== $syn_value ) {
		$syn_why_args[ $syn_arg ] = $syn_value;
	}
}

if ( $syn_why_cards ) {
	$syn_why_args['cards'] = $syn_why_cards;
}

syn_section( 'why', $syn_why_args );

/*
 * 6. THE PROOF. Hides itself when the headline is empty, which is what the
 * brief asks for: a market with nothing verified shows nothing. The figures
 * band under it is the shared record, the same numbers as everywhere else.
 */
if ( $syn_has_proof ) {
	syn_section(
		'case-study',
		array(
			'heading' => syn_field( 'market_case_title', $syn_id ),
			'client'  => syn_field( 'market_case_client', $syn_id ),
			'brief'   => syn_field( 'market_case_brief', $syn_id ),
			'image'   => syn_field_image_id( 'market_case_image', $syn_id ),
			'scope'   => syn_field_rows( 'market_case_scope', $syn_id ),
			'link'    => syn_field_link( 'market_case_link', $syn_id ),
		)
	);
}

syn_section( 'numbers' );

/*
 * 7. THE QUESTIONS. The band emits its FAQPage structured data from the same
 * rows it renders, so the schema exists exactly when the questions are on the
 * page — and not at all when Yoast is already emitting one for this URL.
 */
syn_section(
	'faq',
	array(
		'heading' => syn_field( 'market_faq_heading', $syn_id ),
		'items'   => syn_field_rows( 'market_faqs', $syn_id ),
	)
);

/*
 * 8. RELATED INSIGHTS. Off unless a heading has been typed — see
 * inc/market-fields.php for why the heading is the switch, and why this band
 * cannot yet be narrowed to one market's articles.
 */


if ( '' !== $syn_blog_heading ) {
	$syn_blog_args = array(
		'eyebrow'   => syn_field( 'market_blog_eyebrow', $syn_id ),
		'title'     => $syn_blog_heading,
		'link_text' => syn_field( 'market_blog_link_text', $syn_id ),
	);

	$syn_blog_url = syn_field( 'market_blog_link_url', $syn_id );

	// Left empty the band resolves the Posts page itself, which is a better
	// default than anything that could be typed here.
	if ( '' !== $syn_blog_url ) {
		$syn_blog_args['link_url'] = $syn_blog_url;
	}

	syn_section( 'blog', $syn_blog_args );
}

/*
 * 9. THE OTHER MARKETS, from the markets record. Internal linking that
 * maintains itself: a new market page added at Settings → Site records appears
 * on every existing one with no code change, and each card's anchor text is the
 * market's name rather than "read more".
 *
 * The band is called related-services because that is what it was built for,
 * and it is a grid of named cards with links — the shape, not the subject, is
 * what makes it right here. Its heading and eyebrow are passed, so nothing on
 * screen says "service".
 */


if ( $syn_others ) {
	syn_section(
		'related-services',
		array(
			'eyebrow' => __( 'Where we work', 'synergi' ),
			'heading' => __( 'Our other markets', 'synergi' ),
			'items'   => $syn_others,
		)
	);
}

syn_section( 'final-cta' );

get_footer();

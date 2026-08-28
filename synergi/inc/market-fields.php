<?php
/**
 * The markets site record and the field groups that feed templates/market.php.
 *
 * Loaded by functions.php after inc/records.php and inc/fields.php, whose
 * engines this uses and does not extend. A sibling of inc/service-fields.php
 * and inc/solution-fields.php, for the same reason: those files are HOW a field
 * works, this is WHICH fields exist on a market page.
 *
 * WHAT A MARKET PAGE IS FOR. The homepage is a clean international Synergi
 * homepage and stops trying to rank for Saudi, UAE, GCC and Gulf at once; the
 * market pages carry the location intent instead, one keyword cluster each.
 * Everything below exists to let an editor write a genuinely different page per
 * market — not to let them clone one and swap the country name, which is the
 * failure mode these pages have everywhere else on the web.
 *
 * NOTHING NEW IS DRAWN HERE. A market page is composed from bands the homepage
 * and the service pages already render: the page header, the story band, the
 * services deck, the industries queue, the why deck, the case study, the
 * figures, the FAQ, the blog band, the related grid and the closing CTA. There
 * is no market.css and no market.js.
 *
 * NO SEO FIELDS, DELIBERATELY. The brief asks for SEO title, meta description,
 * canonical, focus keyword and social-sharing fields. Every one of those is
 * already a field on the same edit screen — Yoast's — and the theme emits no
 * <title>, meta description, canonical or OG tag of its own (CLAUDE.md §8).
 * Adding theme fields for them would put two of each in the <head>, which is a
 * Search Console error rather than better SEO. The market's URL is its page
 * slug under a Markets parent page, so it is a field WordPress already has too.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The template every field group below is scoped to. */
define( 'SYN_MARKET_TEMPLATE', 'templates/market.php' );

add_action( 'syn_register_records', 'syn_register_market_records' );
/**
 * Registers the "markets" site record.
 *
 * Registered from this file rather than from inc/records.php, which fires the
 * "syn_register_records" action precisely so a record can live next to the
 * pages that read it.
 *
 * Side effects: registers one site record.
 *
 * @return void
 */
function syn_register_market_records() {

	syn_register_record(
		array(
			'id'          => 'markets',
			'title'       => __( 'Markets', 'synergi' ),
			'description' => __( 'The markets that have a page of their own. The order here is the order they appear in. Adding one here puts it in the “other markets” list on every other market page.', 'synergi' ),
			'read_by'     => __( 'each market page and the Markets overview.', 'synergi' ),
			'row_noun'    => __( 'Market', 'synergi' ),
			'button'      => __( 'Add market', 'synergi' ),
			'row_label'   => 'name',
			'min_rows'    => 1,
			'max_rows'    => 20,
			'fields'      => array(
				array(
					'key'         => 'name',
					'type'        => 'text',
					'label'       => __( 'Market', 'synergi' ),
					'description' => __( 'As it should read in the menu and on the card, e.g. Saudi Arabia.', 'synergi' ),
					'max_length'  => 60,
				),
				array(
					'key'         => 'slug',
					'type'        => 'text',
					'label'       => __( 'Reference', 'synergi' ),
					'description' => __( 'A short lowercase name, e.g. saudi-arabia. It is what a market page uses to leave itself out of its own “other markets” list. Lowercase letters and dashes only.', 'synergi' ),
					'max_length'  => 60,
				),
				array(
					'key'         => 'summary',
					'type'        => 'textarea',
					'label'       => __( 'One-line summary', 'synergi' ),
					'description' => __( 'One sentence about what Synergi does in this market, as it appears on the card. Write a different one for each — the same sentence with the country swapped is what makes a set of location pages thin.', 'synergi' ),
					'max_length'  => 200,
					'rows'        => 2,
				),
				array(
					'key'         => 'url',
					'type'        => 'url',
					'label'       => __( 'Page address', 'synergi' ),
					'description' => __( 'Where the card links to, e.g. /markets/saudi-arabia/.', 'synergi' ),
					'placeholder' => '/markets/',
				),
			),
		)
	);
}

/**
 * Every market in the record except the one named.
 *
 * The mirror of syn_other_services() and syn_other_solutions(): internal
 * linking that maintains itself, so a new market page appears on every existing
 * one without a code change.
 *
 * @param string $exclude Reference of the market to leave out — this page's own.
 * @return array[] Rows from the markets record, in record order.
 */
function syn_other_markets( $exclude ) {
	if ( ! function_exists( 'syn_record' ) ) {
		return array();
	}

	$exclude = sanitize_key( $exclude );
	$others  = array();

	foreach ( syn_record( 'markets' ) as $market ) {
		$slug = sanitize_key( $market['slug'] ?? '' );

		if ( '' === $slug || $slug === $exclude || '' === ( $market['name'] ?? '' ) ) {
			continue;
		}

		$others[] = $market;
	}

	return $others;
}

/**
 * The service lines, as choices for a select.
 *
 * Read from the "services" site record so a seventh line added at Settings →
 * Site records becomes selectable on every market page with no code change.
 *
 * The list always opens with an empty prompt, which is not decoration: a select
 * with no choices at all is a field an editor cannot use, and the record is
 * empty on a fresh install. With the prompt in it the group registers cleanly,
 * the row is simply ignored until somebody picks something, and inc/fields.php
 * never has to log a malformed field.
 *
 * @return array<string,string> Reference => name.
 */
function syn_market_service_choices() {
	$choices = array( '' => __( '— choose a service line —', 'synergi' ) );

	if ( ! function_exists( 'syn_record' ) ) {
		return $choices;
	}

	foreach ( syn_record( 'services' ) as $service ) {
		$slug = sanitize_key( $service['slug'] ?? '' );
		$name = trim( (string) ( $service['name'] ?? '' ) );

		if ( '' !== $slug && '' !== $name ) {
			$choices[ $slug ] = $name;
		}
	}

	return $choices;
}

add_action( 'syn_register_fields', 'syn_register_market_fields' );
/**
 * Registers the eight field groups a market page carries.
 *
 * One group per band, in the order the bands appear down the page, so the edit
 * screen reads in the same order the page does.
 *
 * ON DEFAULTS. Headings and labels carry them, because a band with an empty
 * heading is the failure CLAUDE.md §7c names. The market-specific content does
 * not: a default paragraph about "this market" would be exactly the templated
 * filler these pages must not become, and a default that mentioned an office, a
 * client or a regulation would be a claim about the business that nobody has
 * verified. An empty repeater is safe — every band below skips itself when it
 * has nothing, so an unfinished market page is short rather than broken.
 *
 * Side effects: registers eight field groups on templates/market.php.
 *
 * @return void
 */
function syn_register_market_fields() {

	/*
	 * 1. INTRO — the hero band.
	 *
	 * No heading field: parts/page-header.php uses the page title as the <h1>,
	 * so a market page has exactly one and it can never disagree with the
	 * browser tab or the menu. The SEO title is Yoast's and is free to differ
	 * from it — see the file header for why there is no field for it here.
	 */
	syn_register_field_group(
		array(
			'id'          => 'market_intro',
			'title'       => __( 'Market — intro', 'synergi' ),
			'description' => __( 'The band at the top of the page. The page title is the heading, so it is not repeated here. The SEO title, meta description, focus keyword and social image are Yoast’s fields, further down this screen.', 'synergi' ),
			'templates'   => array( SYN_MARKET_TEMPLATE ),
			'fields'      => array(
				array(
					'key'         => 'market_ref',
					'type'        => 'text',
					'label'       => __( 'Market', 'synergi' ),
					'description' => __( 'The reference of this market, exactly as typed at Settings → Site records, e.g. saudi-arabia. It is what keeps this page out of its own “other markets” list.', 'synergi' ),
					'default'     => '',
					'max_length'  => 60,
				),
				array(
					'key'         => 'market_eyebrow',
					'type'        => 'text',
					'label'       => __( 'Eyebrow', 'synergi' ),
					'description' => __( 'The small line above the heading, e.g. Markets or Saudi Arabia.', 'synergi' ),
					'default'     => __( 'Markets', 'synergi' ),
					'max_length'  => 40,
				),
				array(
					'key'         => 'market_lede',
					'type'        => 'textarea',
					'label'       => __( 'Opening sentence', 'synergi' ),
					'description' => __( 'One or two sentences saying what Synergi does for organizations in this market. This is the first thing a reader and a search engine see, so write it for this market and not for the company in general.', 'synergi' ),
					'default'     => '',
					'rows'        => 3,
					'max_length'  => 320,
				),
				array(
					'key'         => 'market_image',
					'type'        => 'image',
					'label'       => __( 'Hero photograph', 'synergi' ),
					'description' => __( 'Optional. Fills the band behind the heading. Without one the band stays on the flat navy. Choose one with meaningful alt text already set on it.', 'synergi' ),
				),
				array(
					'key'     => 'market_cta',
					'type'    => 'link',
					'label'   => __( 'Main button', 'synergi' ),
					'default' => array(
						'url'   => '',
						'label' => __( 'Talk to our team', 'synergi' ),
					),
				),
				array(
					'key'     => 'market_cta_alt',
					'type'    => 'link',
					'label'   => __( 'Second button', 'synergi' ),
					'default' => array(
						'url'   => '',
						'label' => '',
					),
				),
			),
		)
	);

	/*
	 * 2. INTRODUCTION — the About Us story band, which is a heading, a
	 * standfirst, short columns and up to two statement cards. It is the right
	 * shape for "why Synergi is relevant to organizations here" because it can
	 * hold several short paragraphs without turning into an essay.
	 */
	syn_register_field_group(
		array(
			'id'          => 'market_story',
			'title'       => __( 'Market — introduction', 'synergi' ),
			'description' => __( 'What organizations in this market actually need, and where Synergi fits. Write about real business needs, not about the country. Leave it empty and the band is skipped.', 'synergi' ),
			'templates'   => array( SYN_MARKET_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'market_story_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => '',
					'max_length' => 90,
				),
				array(
					'key'         => 'market_story_paragraphs',
					'type'        => 'repeater',
					'label'       => __( 'Paragraphs', 'synergi' ),
					'description' => __( 'The first is set larger, as the opening line. One paragraph per row.', 'synergi' ),
					'row_noun'    => __( 'Paragraph', 'synergi' ),
					'button'      => __( 'Add paragraph', 'synergi' ),
					'row_label'   => 'text',
					'min_rows'    => 1,
					'max_rows'    => 8,
					'default'     => array(),
					'subfields'   => array(
						array(
							'key'   => 'text',
							'type'  => 'textarea',
							'label' => __( 'Paragraph', 'synergi' ),
							'rows'  => 4,
						),
					),
				),
				array(
					'key'         => 'market_story_pillars',
					'type'        => 'repeater',
					'label'       => __( 'Statement cards', 'synergi' ),
					'description' => __( 'Optional. Up to two, shown as photographic cards that turn over on a desktop to show the words. Use them for something specific about this market — not for the company mission, which is on About Us.', 'synergi' ),
					'row_noun'    => __( 'Statement', 'synergi' ),
					'button'      => __( 'Add statement', 'synergi' ),
					'row_label'   => 'title',
					'min_rows'    => 1,
					'max_rows'    => 4,
					'default'     => array(),
					'subfields'   => array(
						array(
							'key'        => 'title',
							'type'       => 'text',
							'label'      => __( 'Heading', 'synergi' ),
							'max_length' => 60,
						),
						array(
							'key'   => 'body',
							'type'  => 'textarea',
							'label' => __( 'Statement', 'synergi' ),
							'rows'  => 5,
						),
						array(
							'key'         => 'image',
							'type'        => 'image',
							'label'       => __( 'Photograph', 'synergi' ),
							'description' => __( 'Optional. The front of the card, with the heading over it.', 'synergi' ),
						),
					),
				),
			),
		)
	);

	/*
	 * 3. SERVICES — which service lines this market page puts forward.
	 *
	 * A repeater of one select rather than six checkboxes, for two reasons: the
	 * order of the rows is the order of the cards, so an editor can lead with
	 * whichever line matters most here; and the choices come from the services
	 * record, so a seventh service line is selectable the day it is added.
	 *
	 * Only the reference is stored. The name, summary, icon and address all come
	 * from the record when the page renders, so a service renamed once is
	 * renamed on every market page (CLAUDE.md §7a).
	 */
	syn_register_field_group(
		array(
			'id'          => 'market_services',
			'title'       => __( 'Market — services', 'synergi' ),
			'description' => __( 'Which service lines this market leads with, in order. Each card links to that service’s own page. Choose none and every service line is shown.', 'synergi' ),
			'templates'   => array( SYN_MARKET_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'market_services_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'What we run', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'market_services_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Services in this market', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'market_services_lead',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => '',
					'rows'       => 3,
					'max_length' => 320,
				),
				array(
					'key'         => 'market_services',
					'type'        => 'repeater',
					'label'       => __( 'Service lines', 'synergi' ),
					'description' => __( 'One per row, in the order they should appear. The wording on each card is the service line’s own, edited at Settings → Site records.', 'synergi' ),
					'row_noun'    => __( 'Service line', 'synergi' ),
					'button'      => __( 'Add service line', 'synergi' ),
					'row_label'   => 'service',
					'min_rows'    => 1,
					'max_rows'    => 12,
					'default'     => array(),
					'subfields'   => array(
						array(
							'key'     => 'service',
							'type'    => 'select',
							'label'   => __( 'Service line', 'synergi' ),
							'choices' => syn_market_service_choices(),
						),
					),
				),
			),
		)
	);

	/*
	 * 4. INDUSTRIES — the homepage's photographic queue, with this market's own
	 * selection in it. Per market, because the sectors that matter in one are
	 * not the sectors that matter in another; that difference is most of what
	 * stops these pages being the same page twice.
	 */
	syn_register_field_group(
		array(
			'id'          => 'market_industries',
			'title'       => __( 'Market — industries', 'synergi' ),
			'description' => __( 'The sectors Synergi serves in this market. Leave it empty and the band shows the site-wide list instead.', 'synergi' ),
			'templates'   => array( SYN_MARKET_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'market_industries_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Industries', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'market_industries_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Sectors we work in here', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'       => 'market_industries_cards',
					'type'      => 'repeater',
					'label'     => __( 'Industries', 'synergi' ),
					'row_noun'  => __( 'Industry', 'synergi' ),
					'button'    => __( 'Add industry', 'synergi' ),
					'row_label' => 'title',
					'min_rows'  => 1,
					'max_rows'  => 12,
					'default'   => array(),
					'subfields' => array(
						array(
							'key'        => 'title',
							'type'       => 'text',
							'label'      => __( 'Industry', 'synergi' ),
							'max_length' => 60,
						),
						array(
							'key'         => 'preview',
							'type'        => 'text',
							'label'       => __( 'Short label', 'synergi' ),
							'description' => __( 'Two or three words, shown over the photograph.', 'synergi' ),
							'max_length'  => 40,
						),
						array(
							'key'   => 'description',
							'type'  => 'textarea',
							'label' => __( 'One sentence', 'synergi' ),
							'rows'  => 3,
						),
						array(
							'key'         => 'image',
							'type'        => 'image',
							'label'       => __( 'Photograph', 'synergi' ),
							'description' => __( 'Choose one with meaningful alt text already set on it.', 'synergi' ),
						),
					),
				),
			),
		)
	);

	/*
	 * 5. WHY — the deck of reasons, in this market's own words.
	 *
	 * The site-wide version reads the "why" record and renders on eight pages.
	 * This group overrides it for one market, and exists because "why Synergi in
	 * Saudi Arabia" is a genuinely different answer from "why Synergi". Left
	 * empty the record's version renders, which is the right default: a market
	 * with nothing specific to say should say the general thing rather than a
	 * reworded copy of it.
	 */
	syn_register_field_group(
		array(
			'id'          => 'market_why',
			'title'       => __( 'Market — why Synergi here', 'synergi' ),
			'description' => __( 'Reasons that are true of this market specifically. Leave it all empty and the site-wide “Why Synergi” band renders instead. Do not claim an office, a team or a client in a location unless it is verified.', 'synergi' ),
			'templates'   => array( SYN_MARKET_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'market_why_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => '',
					'max_length' => 40,
				),
				array(
					'key'        => 'market_why_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => '',
					'max_length' => 90,
				),
				array(
					'key'        => 'market_why_intro',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => '',
					'rows'       => 3,
					'max_length' => 400,
				),
				array(
					'key'         => 'market_why_cards',
					'type'        => 'repeater',
					'label'       => __( 'Reasons', 'synergi' ),
					'description' => __( 'Shown as a deck of photographic cards. Four reads best.', 'synergi' ),
					'row_noun'    => __( 'Reason', 'synergi' ),
					'button'      => __( 'Add reason', 'synergi' ),
					'row_label'   => 'title',
					'min_rows'    => 1,
					'max_rows'    => 8,
					'default'     => array(),
					'subfields'   => array(
						array(
							'key'        => 'title',
							'type'       => 'text',
							'label'      => __( 'Reason', 'synergi' ),
							'max_length' => 120,
						),
						array(
							'key'         => 'short',
							'type'        => 'text',
							'label'       => __( 'Short name', 'synergi' ),
							'description' => __( 'Two or three words, read out to screen readers on the card’s button.', 'synergi' ),
							'max_length'  => 60,
						),
						array(
							'key'   => 'description',
							'type'  => 'textarea',
							'label' => __( 'One sentence', 'synergi' ),
							'rows'  => 3,
						),
						array(
							'key'         => 'image',
							'type'        => 'image',
							'label'       => __( 'Photograph', 'synergi' ),
							'description' => __( 'Choose one with meaningful alt text already set on it.', 'synergi' ),
						),
					),
				),
			),
		)
	);

	/*
	 * 6. PROOF — one case study, on the service pages' band. It hides itself
	 * when the headline is empty, which is exactly what the brief asks for: a
	 * market with no verified proof shows none rather than an empty frame.
	 */
	syn_register_field_group(
		array(
			'id'          => 'market_proof',
			'title'       => __( 'Market — proof', 'synergi' ),
			'description' => __( 'One piece of verified evidence from this market. Leave the headline empty and the whole band disappears — which is the right thing to do until there is something cleared to show. Never invent a client, a statistic or a result.', 'synergi' ),
			'templates'   => array( SYN_MARKET_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'market_case_title',
					'type'       => 'text',
					'label'      => __( 'Headline', 'synergi' ),
					'default'    => '',
					'max_length' => 120,
				),
				array(
					'key'         => 'market_case_client',
					'type'        => 'text',
					'label'       => __( 'Client type', 'synergi' ),
					'description' => __( 'What kind of organisation it was, never its name — e.g. “A Saudi workforce solutions provider”.', 'synergi' ),
					'default'     => '',
					'max_length'  => 120,
				),
				array(
					'key'     => 'market_case_brief',
					'type'    => 'textarea',
					'label'   => __( 'The situation', 'synergi' ),
					'default' => '',
					'rows'    => 5,
				),
				array(
					'key'         => 'market_case_image',
					'type'        => 'image',
					'label'       => __( 'Photograph', 'synergi' ),
					'description' => __( 'Optional. Choose one with meaningful alt text already set on it.', 'synergi' ),
				),
				array(
					'key'       => 'market_case_scope',
					'type'      => 'repeater',
					'label'     => __( 'What was delivered', 'synergi' ),
					'row_noun'  => __( 'Item', 'synergi' ),
					'button'    => __( 'Add item', 'synergi' ),
					'row_label' => 'item',
					'min_rows'  => 1,
					'max_rows'  => 12,
					'default'   => array(),
					'subfields' => array(
						array(
							'key'        => 'item',
							'type'       => 'text',
							'label'      => __( 'Delivered', 'synergi' ),
							'max_length' => 160,
						),
					),
				),
				array(
					'key'     => 'market_case_link',
					'type'    => 'link',
					'label'   => __( 'Read more', 'synergi' ),
					'default' => array(
						'url'   => '',
						'label' => '',
					),
				),
			),
		)
	);

	/*
	 * 7. QUESTIONS — the FAQ band, which also emits the FAQPage structured data
	 * unless Yoast is already emitting it on the same URL. The brief asks for
	 * the schema only when the questions are visible on the page; the band
	 * emits both together or neither, because the schema is built from the same
	 * rows it renders (CLAUDE.md §8).
	 */
	syn_register_field_group(
		array(
			'id'          => 'market_questions',
			'title'       => __( 'Market — questions', 'synergi' ),
			'description' => __( 'Real questions a buyer in this market asks. Answers accept links and basic formatting. Leave it empty and the band is skipped, along with its structured data.', 'synergi' ),
			'templates'   => array( SYN_MARKET_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'market_faq_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Questions we are asked', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'       => 'market_faqs',
					'type'      => 'repeater',
					'label'     => __( 'Questions', 'synergi' ),
					'row_noun'  => __( 'Question', 'synergi' ),
					'button'    => __( 'Add question', 'synergi' ),
					'row_label' => 'question',
					'min_rows'  => 1,
					'max_rows'  => 20,
					'default'   => array(),
					'subfields' => array(
						array(
							'key'        => 'question',
							'type'       => 'text',
							'label'      => __( 'Question', 'synergi' ),
							'max_length' => 200,
						),
						array(
							'key'   => 'answer',
							'type'  => 'html',
							'label' => __( 'Answer', 'synergi' ),
							'rows'  => 5,
						),
					),
				),
			),
		)
	);

	/*
	 * 8. INSIGHTS — the homepage's blog band.
	 *
	 * The heading is the switch: empty means the band does not render, and it is
	 * empty by default. That is deliberate. The band shows the most recent posts
	 * and cannot yet be filtered to a market, so switching it on before there is
	 * anything relevant to show would put five general articles under a heading
	 * promising market insight — filler, which is the one thing these pages must
	 * not be. Selecting posts by hand needs a field type the theme does not have
	 * and a section that can take a list; both are real work, and neither is
	 * pretended at here.
	 */
	syn_register_field_group(
		array(
			'id'          => 'market_insights',
			'title'       => __( 'Market — related insights', 'synergi' ),
			'description' => __( 'The latest articles, under a heading of your choosing. Leave the heading empty — as it is by default — and the band does not appear at all. Turn it on once there are articles worth showing to a reader in this market.', 'synergi' ),
			'templates'   => array( SYN_MARKET_TEMPLATE ),
			'fields'      => array(
				array(
					'key'         => 'market_blog_heading',
					'type'        => 'text',
					'label'       => __( 'Section heading', 'synergi' ),
					'description' => __( 'Empty means the band is hidden.', 'synergi' ),
					'default'     => '',
					'max_length'  => 90,
				),
				array(
					'key'        => 'market_blog_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Insights', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'market_blog_link_text',
					'type'       => 'text',
					'label'      => __( 'Link label', 'synergi' ),
					'default'    => __( 'View all articles', 'synergi' ),
					'max_length' => 60,
				),
				/*
				 * "text" and not "url". A url is a leaf type a repeater
				 * subfield may have; a top-level field may not, and
				 * inc/fields.php drops one that tries — silently to an editor,
				 * loudly in the log. inc/homepage-fields.php declares the same
				 * field the same way for the same reason.
				 */
				array(
					'key'         => 'market_blog_link_url',
					'type'        => 'text',
					'label'       => __( 'Link address', 'synergi' ),
					'description' => __( 'Optional. Leave empty to use the Posts page set in Settings → Reading.', 'synergi' ),
					'placeholder' => '/blog/',
					'max_length'  => 200,
				),
			),
		)
	);
}

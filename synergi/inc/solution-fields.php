<?php
/**
 * The solutions site record and the field groups that feed templates/solution.php.
 *
 * Loaded by functions.php after inc/records.php and inc/fields.php, whose
 * engines this uses and does not extend. A sibling of inc/service-fields.php,
 * for the same reason: those files are HOW a field works, this is WHICH fields
 * exist on a solution page.
 *
 * NOTHING NEW IS DRAWN HERE. A solution page is composed entirely from bands
 * the six service pages already render — capabilities, process, case study, why,
 * numbers, FAQ, related and the closing CTA. There is no solutions.css, no
 * solutions.js and no sections/solution-*.php, because a solution page is a
 * service page about a different kind of engagement, not a second design
 * (CLAUDE.md §4: a second file that restyles an existing component is the
 * failure mode this project exists to escape).
 *
 * WHY THE LIST OF SOLUTIONS IS A RECORD AND THE PAGE COPY IS NOT. CLAUDE.md
 * §7a's test is "if this changes, how many pages should change with it?". The
 * five solutions appear on all five solution pages and on the listing page, so
 * they are one record edited at Settings → Site records. What a single solution
 * covers, how it runs and what it has proved are that page's words and nobody
 * else's, so they are postmeta.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The template every field group below is scoped to. */
define( 'SYN_SOLUTION_TEMPLATE', 'templates/solution.php' );

add_action( 'syn_register_records', 'syn_register_solution_records' );
/**
 * Registers the "solutions" site record.
 *
 * Deliberately registered from this file rather than from inc/records.php:
 * records.php fires the "syn_register_records" action precisely so a record can
 * live next to the pages that read it, and editing a shared file to add one
 * would be a merge conflict waiting for whoever is working in it.
 *
 * Side effects: registers one site record.
 *
 * @return void
 */
function syn_register_solution_records() {

	syn_register_record(
		array(
			'id'          => 'solutions',
			'title'       => __( 'Solutions', 'synergi' ),
			'description' => __( 'The solutions Synergi offers. The order here is the order they appear in. Adding one here puts it on every solution page and on the Our Solutions listing.', 'synergi' ),
			'read_by'     => __( 'each solution page, the Our Solutions listing, and the market pages.', 'synergi' ),
			'row_noun'    => __( 'Solution', 'synergi' ),
			'button'      => __( 'Add solution', 'synergi' ),
			'row_label'   => 'name',
			'min_rows'    => 1,
			'max_rows'    => 12,
			'fields'      => array(
				array(
					'key'         => 'name',
					'type'        => 'text',
					'label'       => __( 'Name', 'synergi' ),
					'description' => __( 'As it should read in the menu and on the card, e.g. Shared services design & set-up.', 'synergi' ),
					'max_length'  => 80,
				),
				array(
					'key'         => 'slug',
					'type'        => 'text',
					'label'       => __( 'Reference', 'synergi' ),
					'description' => __( 'A short lowercase name, e.g. shared-services-design. It is what a solution page uses to leave itself out of its own “other solutions” list. Lowercase letters and dashes only.', 'synergi' ),
					'max_length'  => 60,
				),
				array(
					'key'         => 'summary',
					'type'        => 'textarea',
					'label'       => __( 'One-line summary', 'synergi' ),
					'description' => __( 'One sentence, as it appears on the card.', 'synergi' ),
					'max_length'  => 200,
					'rows'        => 2,
				),
				array(
					'key'         => 'url',
					'type'        => 'url',
					'label'       => __( 'Page address', 'synergi' ),
					'description' => __( 'Where the card links to, e.g. /our-solutions/shared-services-design/.', 'synergi' ),
					'placeholder' => '/our-solutions/',
				),
			),
		)
	);
}

/**
 * Every solution in the record, each with the accent its card wears.
 *
 * One reader for the record, used by both the "keep exploring" band at the foot
 * of a solution page and the Our Solutions listing, so the two can never
 * disagree about which solutions exist or what colour each one is.
 *
 * THE ACCENT IS ASSIGNED HERE, not by whoever renders the cards, and that is the
 * whole point of this function. A solution page leaves itself out of its own
 * "other solutions" band; if the accent were picked from position in that
 * shortened list, Systems Implementation would be violet on one page and
 * magenta on the next. Numbering the full record once means a solution keeps its
 * colour everywhere it appears, which is what makes the colours read as
 * navigation rather than decoration (asked for 31 Aug).
 *
 * Rows with no name or no reference are dropped rather than numbered, so a
 * half-filled row an editor has not finished does not silently consume a colour
 * and shift every card after it.
 *
 * @return array[] Rows from the solutions record, in record order, each with a
 *                 sanitised "slug" and an "accent" added.
 */
function syn_solutions() {
	if ( ! function_exists( 'syn_record' ) ) {
		return array();
	}

	$solutions = array();

	foreach ( syn_record( 'solutions' ) as $solution ) {
		$slug = sanitize_key( $solution['slug'] ?? '' );

		if ( '' === $slug || '' === trim( (string) ( $solution['name'] ?? '' ) ) ) {
			continue;
		}

		$solution['slug']   = $slug;
		$solution['accent'] = syn_accent_for_index( count( $solutions ) );

		$solutions[] = $solution;
	}

	return $solutions;
}

/**
 * Every solution in the record except the one named.
 *
 * The mirror of syn_other_services(), and it exists for the same reason: a
 * solution page's "keep exploring" list is internal linking that maintains
 * itself. A sixth solution added at Settings → Site records appears on all five
 * existing pages with no code change.
 *
 * Filters syn_solutions() rather than the record, so the accents were already
 * settled against the full list before this page took itself out of it.
 *
 * @param string $exclude Reference of the solution to leave out — this page's own.
 * @return array[] Rows from the solutions record, in record order.
 */
function syn_other_solutions( $exclude ) {
	$exclude = sanitize_key( $exclude );
	$others  = array();

	foreach ( syn_solutions() as $solution ) {
		if ( $solution['slug'] === $exclude ) {
			continue;
		}

		$others[] = $solution;
	}

	return $others;
}

add_action( 'syn_register_fields', 'syn_register_solution_fields' );
/**
 * Registers the five field groups a solution page carries.
 *
 * One group per band, in the order the bands appear down the page, so the edit
 * screen reads in the same order the page does.
 *
 * ON DEFAULTS. Headings carry them, because a band with an empty heading is the
 * failure CLAUDE.md §7c names. The content repeaters deliberately do not: five
 * solutions with five different offers cannot share a default without one of
 * them being wrong, and writing plausible-sounding scope for an engagement
 * nobody has described would be inventing claims about the business. An empty
 * repeater is safe here — every band below skips itself when it has nothing,
 * so an unfinished page is short rather than broken.
 *
 * Side effects: registers five field groups on templates/solution.php.
 *
 * @return void
 */
function syn_register_solution_fields() {

	/*
	 * 1. INTRO — the hero band.
	 *
	 * "Solution" is the only field here that is not copy, and it is not a design
	 * control either: it names which of the solutions this page is, which is how
	 * the page leaves itself out of its own "other solutions" list. An editor
	 * picks a solution, never a colour (CLAUDE.md §7c).
	 *
	 * No heading field, and no SEO title: parts/page-header.php uses the page
	 * title as the <h1>, so the heading can never disagree with the browser tab
	 * or the menu, and the SEO title is Yoast's — the theme emits no <title>,
	 * meta description, canonical or OG tag of its own (CLAUDE.md §8).
	 */
	syn_register_field_group(
		array(
			'id'          => 'solution_intro',
			'title'       => __( 'Solution — intro', 'synergi' ),
			'description' => __( 'The band at the top of the page. The page title is the heading, so it is not repeated here. The SEO title, meta description and social image are Yoast’s fields, further down this screen.', 'synergi' ),
			'templates'   => array( SYN_SOLUTION_TEMPLATE ),
			'fields'      => array(
				array(
					'key'         => 'solution_ref',
					'type'        => 'text',
					'label'       => __( 'Solution', 'synergi' ),
					'description' => __( 'The reference of this solution, exactly as typed at Settings → Site records, e.g. shared-services-design. It is what keeps this page out of its own “other solutions” list.', 'synergi' ),
					'default'     => '',
					'max_length'  => 60,
				),
				array(
					'key'        => 'solution_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Our Solutions', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'         => 'solution_lede',
					'type'        => 'textarea',
					'label'       => __( 'Opening sentence', 'synergi' ),
					'description' => __( 'One or two sentences. This is the first thing a reader and a search engine see.', 'synergi' ),
					'default'     => __( 'A defined engagement, run end to end by a team that has done it before.', 'synergi' ),
					'rows'        => 3,
					'max_length'  => 320,
				),
				array(
					'key'         => 'solution_image',
					'type'        => 'image',
					'label'       => __( 'Hero photograph', 'synergi' ),
					'description' => __( 'Optional. Fills the band behind the heading. Without one the band stays on the flat navy. Choose one with meaningful alt text already set on it.', 'synergi' ),
				),
				array(
					'key'     => 'solution_cta',
					'type'    => 'link',
					'label'   => __( 'Main button', 'synergi' ),
					'default' => array(
						'url'   => '',
						'label' => __( 'Talk to our team', 'synergi' ),
					),
				),
				array(
					'key'     => 'solution_cta_alt',
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
	 * 2. SCOPE — what the solution covers, on the service pages' capabilities
	 * band. Same partial, same tab rail, same fallback with JavaScript off.
	 */
	syn_register_field_group(
		array(
			'id'          => 'solution_scope',
			'title'       => __( 'Solution — what it covers', 'synergi' ),
			'description' => __( 'The parts of the engagement, shown as a list beside a panel. Leave it empty and the band is skipped.', 'synergi' ),
			'templates'   => array( SYN_SOLUTION_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'solution_scope_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'What this solution covers', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'       => 'solution_scope',
					'type'      => 'repeater',
					'label'     => __( 'What it covers', 'synergi' ),
					'row_noun'  => __( 'Area', 'synergi' ),
					'button'    => __( 'Add area', 'synergi' ),
					'row_label' => 'title',
					'min_rows'  => 1,
					'max_rows'  => 12,
					'default'   => array(),
					'subfields' => array(
						array(
							'key'        => 'title',
							'type'       => 'text',
							'label'      => __( 'Area', 'synergi' ),
							'max_length' => 80,
						),
						array(
							'key'   => 'description',
							'type'  => 'textarea',
							'label' => __( 'What it means', 'synergi' ),
							'rows'  => 4,
						),
						array(
							'key'         => 'tags',
							'type'        => 'text',
							'label'       => __( 'Tags', 'synergi' ),
							'description' => __( 'Optional. A few words each, separated by commas.', 'synergi' ),
							'max_length'  => 160,
						),
					),
				),
			),
		)
	);

	/*
	 * 3. METHOD — how the engagement runs, on the same numbered band About Us
	 * and the six service pages use. An <ol>, so the order is the meaning.
	 */
	syn_register_field_group(
		array(
			'id'          => 'solution_method',
			'title'       => __( 'Solution — how it runs', 'synergi' ),
			'description' => __( 'The stages of the engagement, in order. The numbers are drawn from that order, so moving a stage renumbers it.', 'synergi' ),
			'templates'   => array( SYN_SOLUTION_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'solution_method_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'How the engagement runs', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'solution_method_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => '',
					'rows'       => 3,
					'max_length' => 320,
				),
				array(
					'key'       => 'solution_method',
					'type'      => 'repeater',
					'label'     => __( 'Stages', 'synergi' ),
					'row_noun'  => __( 'Stage', 'synergi' ),
					'button'    => __( 'Add stage', 'synergi' ),
					'row_label' => 'title',
					'min_rows'  => 1,
					'max_rows'  => 10,
					'default'   => array(),
					'subfields' => array(
						array(
							'key'        => 'title',
							'type'       => 'text',
							'label'      => __( 'Stage', 'synergi' ),
							'max_length' => 80,
						),
						array(
							'key'   => 'description',
							'type'  => 'textarea',
							'label' => __( 'What happens', 'synergi' ),
							'rows'  => 4,
						),
					),
				),
			),
		)
	);

	/*
	 * 4. PROOF — one case study, on the service pages' band.
	 *
	 * The whole band hides itself when the headline is empty, which is how a
	 * page with no cleared proof stays honest rather than showing an empty
	 * frame. The client field asks for a TYPE of organisation and not a name,
	 * for the reason sections/case-study.php sets out: none of the real client
	 * names is cleared for the public site.
	 */
	syn_register_field_group(
		array(
			'id'          => 'solution_proof',
			'title'       => __( 'Solution — proof', 'synergi' ),
			'description' => __( 'One piece of evidence. Leave the headline empty and the whole band disappears — which is the right thing to do until there is something verified to show.', 'synergi' ),
			'templates'   => array( SYN_SOLUTION_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'solution_case_title',
					'type'       => 'text',
					'label'      => __( 'Headline', 'synergi' ),
					'default'    => '',
					'max_length' => 120,
				),
				array(
					'key'         => 'solution_case_client',
					'type'        => 'text',
					'label'       => __( 'Client type', 'synergi' ),
					'description' => __( 'What kind of organisation it was, never its name — e.g. “A UAE hospitality group”.', 'synergi' ),
					'default'     => '',
					'max_length'  => 120,
				),
				array(
					'key'        => 'solution_case_brief',
					'type'       => 'textarea',
					'label'      => __( 'The situation', 'synergi' ),
					'default'    => '',
					'rows'       => 5,
				),
				array(
					'key'         => 'solution_case_image',
					'type'        => 'image',
					'label'       => __( 'Photograph', 'synergi' ),
					'description' => __( 'Optional. Choose one with meaningful alt text already set on it.', 'synergi' ),
				),
				array(
					'key'       => 'solution_case_scope',
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
					'key'     => 'solution_case_link',
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
	 * 5. QUESTIONS — the FAQ band, which also emits the FAQPage structured data
	 * unless Yoast is already emitting it on the same URL (CLAUDE.md §8: two
	 * FAQPage blocks on one page is a Search Console error, not a bonus).
	 */
	syn_register_field_group(
		array(
			'id'          => 'solution_questions',
			'title'       => __( 'Solution — questions', 'synergi' ),
			'description' => __( 'Real questions a buyer asks about this engagement. Answers accept links and basic formatting. Leave it empty and the band is skipped, along with its structured data.', 'synergi' ),
			'templates'   => array( SYN_SOLUTION_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'solution_faq_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Questions we are asked', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'       => 'solution_faqs',
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
}

/** The template the Our Solutions listing page uses. */
define( 'SYN_SOLUTIONS_LISTING_TEMPLATE', 'templates/solutions-listing.php' );

add_action( 'syn_register_fields', 'syn_register_solutions_listing_fields' );
/**
 * Registers the one field group the Our Solutions listing page carries.
 *
 * The mirror of syn_register_services_listing_fields(), and the same rule holds:
 * there is NO field here for listing the solutions. They exist once, at
 * Settings → Site records, and syn_solutions() is what reads them — including
 * the accent each one wears, so a solution is the same colour here as it is at
 * the foot of every sibling page (CLAUDE.md §7a).
 *
 * Side effects: registers one field group on templates/solutions-listing.php.
 *
 * @return void
 */
function syn_register_solutions_listing_fields() {

	syn_register_field_group(
		array(
			'id'          => 'solutions_list',
			'title'       => __( 'Our Solutions — the page', 'synergi' ),
			'description' => __( 'The top of the page and the wording over the grid. The grid itself is the solutions at Settings → Site records, so adding one there adds a card here.', 'synergi' ),
			'templates'   => array( SYN_SOLUTIONS_LISTING_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'solutions_list_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Our Solutions', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'         => 'solutions_list_lede',
					'type'        => 'textarea',
					'label'       => __( 'Opening sentence', 'synergi' ),
					'description' => __( 'One or two sentences under the page title. This is the first thing a reader and a search engine see.', 'synergi' ),
					'default'     => __( 'Defined engagements, run end to end by teams that have done them before.', 'synergi' ),
					'rows'        => 3,
					'max_length'  => 320,
				),
				array(
					'key'         => 'solutions_list_image',
					'type'        => 'image',
					'label'       => __( 'Hero photograph', 'synergi' ),
					'description' => __( 'Optional. Fills the band behind the page title. Without one the band stays on the flat navy. Choose one with meaningful alt text already set on it.', 'synergi' ),
				),
				array(
					'key'     => 'solutions_list_cta',
					'type'    => 'link',
					'label'   => __( 'Button', 'synergi' ),
					'default' => array(
						'url'   => '',
						'label' => '',
					),
				),
				array(
					'key'        => 'solutions_list_heading',
					'type'       => 'text',
					'label'      => __( 'Heading over the grid', 'synergi' ),
					'default'    => __( 'Our solutions', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'         => 'solutions_list_grid_lede',
					'type'        => 'textarea',
					'label'       => __( 'Sentence over the grid', 'synergi' ),
					'description' => __( 'Optional.', 'synergi' ),
					'default'     => '',
					'rows'        => 2,
					'max_length'  => 240,
				),
				array(
					'key'         => 'solutions_list_empty',
					'type'        => 'textarea',
					'label'       => __( 'When the record is empty', 'synergi' ),
					'description' => __( 'Shown in place of the grid if no solutions have been added at Settings → Site records, so the page is never a heading over nothing.', 'synergi' ),
					'default'     => __( 'Our solutions are being updated. Please get in touch and we will talk you through the options.', 'synergi' ),
					'rows'        => 3,
					'max_length'  => 320,
				),
			),
		)
	);
}

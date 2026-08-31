<?php
/**
 * The case studies — the single study, and the page that lists them.
 *
 * Loaded by functions.php, after inc/fields.php (whose engine registers the
 * groups) and inc/service-fields.php (whose syn_service_name() turns a stored
 * reference into a name). Read by templates/case-study.php,
 * templates/case-studies-listing.php and sections/case-studies.php.
 *
 * WHY PAGES AND NOT A POST TYPE. stage-6-remaining-plan.md's decision D4 holds
 * case studies as a custom post type back to a stage of their own, after the
 * templates are proven and probably after the domain move. Nothing here changes
 * that decision: a case study is an ordinary page on an ordinary template, so it
 * has the URL an editor gives it, it needs no rewrite rules flushing, and it
 * cannot invent URLs nobody asked for (CLAUDE.md §2.8). The listing finds them
 * by asking which pages use the template, so there is no second list to keep in
 * step with the first.
 *
 * If they do become a post type later, syn_case_studies() is the one function
 * that has to change — every caller asks it for cards, not for posts.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The template a single case study uses. */
define( 'SYN_CASE_STUDY_TEMPLATE', 'templates/case-study.php' );

/** The template the page listing them uses. */
define( 'SYN_CASE_STUDIES_TEMPLATE', 'templates/case-studies-listing.php' );

/**
 * The published case studies, as cards ready to render.
 *
 * Finds them by the template they are on rather than from a list an editor
 * maintains, so publishing a case study adds it to every grid on the site and
 * unpublishing one removes it. There is nothing to forget to update.
 *
 * Ordered by the page's own Order box first and by date second, so an editor can
 * pin a study to the front through Page Attributes without having to re-date it,
 * and everything unpinned falls in newest-first behind it.
 *
 * Side effects: runs one secondary WP_Query and primes the postmeta cache for
 * the pages it returns.
 *
 * @param array $args {
 *     Optional.
 *
 *     @type int    $count   How many to return. -1 (default) is all of them.
 *     @type string $service Service reference, to return only that line's.
 *     @type int    $exclude A page ID to leave out — a case study's own.
 * }
 * @return array[] Cards in syn_case_study_card()'s shape, in order.
 */
function syn_case_studies( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'count'   => -1,
			'service' => '',
			'exclude' => 0,
		)
	);

	$count   = (int) $args['count'];
	$service = sanitize_key( $args['service'] );
	$exclude = absint( $args['exclude'] );

	/*
	 * The template is stored in the page's own _wp_page_template meta by core,
	 * so this is a plain meta comparison and not a direct database query
	 * (CLAUDE.md §5). Both clauses are exact matches on indexed meta keys.
	 */
	$meta = array(
		array(
			'key'     => '_wp_page_template',
			'value'   => SYN_CASE_STUDY_TEMPLATE,
			'compare' => '=',
		),
	);

	if ( '' !== $service ) {
		$meta[] = array(
			'key'     => SYN_META_PREFIX . 'case_service',
			'value'   => $service,
			'compare' => '=',
		);
	}

	$query_args = array(
		'post_type'              => 'page',
		'post_status'            => 'publish',
		'posts_per_page'         => $count > 0 ? $count : -1,
		'orderby'                => array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		),

		// No pagination on any of these grids, so the SQL_CALC_FOUND_ROWS count
		// would be work nobody reads (CLAUDE.md §6).
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'meta_query'             => $meta, // phpcs:ignore WordPress.DB.SlowMetaQuery.slow_query -- the only way to find pages by their template; the set is small and the alternative is a hand-maintained list that drifts.
	);

	if ( $exclude ) {
		$query_args['post__not_in'] = array( $exclude );
	}

	$query = new WP_Query( $query_args );
	$cards = array();

	foreach ( $query->posts as $post ) {
		$cards[] = syn_case_study_card( (int) $post->ID );
	}

	/*
	 * No debug comment echoed here on purpose. A template calls this before
	 * get_header() to decide whether a band exists at all, and anything echoed
	 * at that point lands ahead of the doctype. sections/case-studies.php prints
	 * the "nothing found" comment instead, where it is inside the document and
	 * next to the band it explains (CLAUDE.md §13).
	 */
	return $cards;
}

/**
 * One case study, as the grid wants it.
 *
 * The service line arrives as a reference and leaves as a name, looked up in the
 * services record rather than stored twice — renaming a line at
 * Settings → Site records renames it on every card (CLAUDE.md §7a). A reference
 * the record no longer has resolves to "" and the card simply omits that line
 * rather than printing a slug at a reader.
 *
 * The picture falls back to the page's Featured Image, so a case study that has
 * one already needs no second choice made about it.
 *
 * @param int $post_id The case study page.
 * @return array Card: title, url, summary, service, client, country, code
 *               (strings) and image, flag (attachment IDs).
 */
function syn_case_study_card( $post_id ) {
	$post_id = (int) $post_id;

	$image = syn_field_image_id( 'case_image', $post_id );

	if ( ! $image ) {
		$image = (int) get_post_thumbnail_id( $post_id );
	}

	return array(
		'title'   => get_the_title( $post_id ),
		'url'     => (string) get_permalink( $post_id ),
		'summary' => (string) syn_field( 'case_lede', $post_id ),
		'service' => syn_service_name( syn_field( 'case_service', $post_id ) ),
		'client'  => (string) syn_field( 'case_client', $post_id ),
		'country' => (string) syn_field( 'case_country', $post_id ),
		'code'    => (string) syn_field( 'case_code', $post_id ),
		'flag'    => syn_field_image_id( 'case_flag', $post_id ),
		'image'   => $image,
	);
}

add_action( 'syn_register_fields', 'syn_register_case_study_fields' );
/**
 * Registers the field groups the two case-study templates carry.
 *
 * ON DEFAULTS. Labels and headings carry them, because a band with an empty
 * heading is the failure CLAUDE.md §7c names. The facts about a study do not:
 * a default client type or a default country would be a claim about work nobody
 * has described, and inventing one is worse than an empty line. Every one of
 * them is optional in the card, which is why an unfinished study renders short
 * rather than broken.
 *
 * ON CLIENT NAMES. Same rule as sections/case-study.php states at length: the
 * company profile identifies real clients and none of them is cleared for the
 * public site, so the field asks for the KIND of organisation. Nothing here can
 * enforce that — but nothing here encourages the opposite either.
 *
 * Side effects: registers three field groups on templates/case-study.php and one
 * on templates/case-studies-listing.php.
 *
 * @return void
 */
function syn_register_case_study_fields() {

	/*
	 * 1. THE STUDY'S FACTS — the hero, and the four things a card shows.
	 *
	 * No heading field: parts/page-header.php uses the page title as the <h1>,
	 * so the headline can never disagree with the browser tab or with the card
	 * on the listing page. The SEO title and meta description are Yoast's boxes
	 * further down the same screen — the theme emits neither (CLAUDE.md §8).
	 */
	syn_register_field_group(
		array(
			'id'          => 'case_intro',
			'title'       => __( 'Case study — the facts', 'synergi' ),
			'description' => __( 'The band at the top of the page, and the four things this study shows on the Case studies grid. The page title is the headline, so it is not repeated here.', 'synergi' ),
			'templates'   => array( SYN_CASE_STUDY_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'case_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Case study', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'         => 'case_lede',
					'type'        => 'textarea',
					'label'       => __( 'Opening sentence', 'synergi' ),
					'description' => __( 'One or two sentences saying what the engagement was. This is the first thing a reader and a search engine see.', 'synergi' ),
					'default'     => '',
					'rows'        => 3,
					'max_length'  => 320,
				),
				array(
					'key'         => 'case_image',
					'type'        => 'image',
					'label'       => __( 'Photograph', 'synergi' ),
					'description' => __( 'Fills the band behind the headline, and is the picture on the Case studies grid. Without one the band stays on the flat navy and the card shows no picture. Choose one with meaningful alt text already set on it.', 'synergi' ),
				),
				array(
					'key'         => 'case_service',
					'type'        => 'text',
					'label'       => __( 'Service line', 'synergi' ),
					'description' => __( 'The reference of the service line this study belongs to, exactly as typed at Settings → Site records, e.g. human-resources. It is what shows above the headline on the card, and what lets a service page show only its own studies later.', 'synergi' ),
					'default'     => '',
					'max_length'  => 60,
				),
				array(
					'key'         => 'case_client',
					'type'        => 'text',
					'label'       => __( 'Kind of client', 'synergi' ),
					'description' => __( 'A description of the organisation, never its name — e.g. a private equity portfolio company. No client on the company profile has been cleared for the public site.', 'synergi' ),
					'default'     => '',
					'max_length'  => 120,
				),
				array(
					'key'         => 'case_country',
					'type'        => 'text',
					'label'       => __( 'Country', 'synergi' ),
					'description' => __( 'Where the work was delivered, e.g. United Arab Emirates.', 'synergi' ),
					'default'     => '',
					'max_length'  => 60,
				),
				array(
					'key'         => 'case_code',
					'type'        => 'text',
					'label'       => __( 'Country code', 'synergi' ),
					'description' => __( 'Two letters, e.g. AE, SA, QA. Shown on the card when no flag has been chosen below.', 'synergi' ),
					'default'     => '',
					'max_length'  => 3,
					'placeholder' => 'AE',
				),
				/*
				 * The flag as a picture, for the reason inc/records.php gives at
				 * the locations record: an emoji flag renders as a pair of
				 * letters on Windows, which is most of this audience, and the
				 * theme drawing its own would mean approximating the Saudi flag,
				 * which bears the shahada. The same uploads the locations record
				 * already uses are the ones to choose here.
				 */
				array(
					'key'         => 'case_flag',
					'type'        => 'image',
					'label'       => __( 'Country flag', 'synergi' ),
					'description' => __( 'Optional. Shown on the card beside the country name. Without one the card shows the country code instead. Use the same flag images the locations already use.', 'synergi' ),
				),
			),
		)
	);

	/*
	 * 2. THE OUTCOME — the figures this engagement produced, on the same counting
	 * band the homepage uses.
	 *
	 * Per page, not the "figures" site record, and that is the CLAUDE.md §7a test
	 * answered rather than ignored: the record holds facts about the business
	 * that must be identical everywhere, and "cut onboarding from 21 days to 4"
	 * is a fact about one engagement. If it changed, exactly one page should
	 * change with it.
	 */
	syn_register_field_group(
		array(
			'id'          => 'case_outcome',
			'title'       => __( 'Case study — the outcome', 'synergi' ),
			'description' => __( 'The numbers this engagement produced. Leave the list empty and the whole band is skipped.', 'synergi' ),
			'templates'   => array( SYN_CASE_STUDY_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'case_outcome_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'The outcome', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'case_outcome_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => '',
					'rows'       => 3,
					'max_length' => 320,
				),
				array(
					'key'       => 'case_outcome_figures',
					'type'      => 'repeater',
					'label'     => __( 'Figures', 'synergi' ),
					'row_noun'  => __( 'Figure', 'synergi' ),
					'button'    => __( 'Add figure', 'synergi' ),
					'row_label' => 'label',
					'min_rows'  => 1,
					'max_rows'  => 6,
					'default'   => array(),
					'subfields' => array(
						array(
							'key'         => 'value',
							'type'        => 'text',
							'label'       => __( 'The number', 'synergi' ),
							'description' => __( 'Exactly as it should read, including any sign or symbol, e.g. 40% or 4 days.', 'synergi' ),
							'max_length'  => 20,
						),
						array(
							'key'        => 'label',
							'type'       => 'text',
							'label'      => __( 'What it measures', 'synergi' ),
							'max_length' => 80,
						),
					),
				),
			),
		)
	);

	/*
	 * 3. THE FOOT — the band of other case studies, and the closing question.
	 */
	syn_register_field_group(
		array(
			'id'          => 'case_more',
			'title'       => __( 'Case study — more studies', 'synergi' ),
			'description' => __( 'The band at the foot of the page. It fills itself from the other published case studies, so there is nothing to keep up to date here beyond the wording.', 'synergi' ),
			'templates'   => array( SYN_CASE_STUDY_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'case_more_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Keep exploring', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'case_more_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'More case studies', 'synergi' ),
					'max_length' => 90,
				),
			),
		)
	);

	/*
	 * 4. THE LISTING PAGE. One group, because the page is a hero and a grid and
	 * the grid fills itself.
	 */
	syn_register_field_group(
		array(
			'id'          => 'case_list',
			'title'       => __( 'Case studies — the page', 'synergi' ),
			'description' => __( 'The top of the page, and the wording over the grid. The grid itself fills from every published case study, so nothing lists them by hand.', 'synergi' ),
			'templates'   => array( SYN_CASE_STUDIES_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'case_list_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Proof', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'         => 'case_list_lede',
					'type'        => 'textarea',
					'label'       => __( 'Opening sentence', 'synergi' ),
					'description' => __( 'One or two sentences under the page title.', 'synergi' ),
					'default'     => __( 'Engagements we have run, and what changed as a result.', 'synergi' ),
					'rows'        => 3,
					'max_length'  => 320,
				),
				array(
					'key'         => 'case_list_image',
					'type'        => 'image',
					'label'       => __( 'Hero photograph', 'synergi' ),
					'description' => __( 'Optional. Fills the band behind the page title. Without one the band stays on the flat navy.', 'synergi' ),
				),
				array(
					'key'        => 'case_list_heading',
					'type'       => 'text',
					'label'      => __( 'Heading over the grid', 'synergi' ),
					'default'    => __( 'Our work', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'         => 'case_list_grid_lede',
					'type'        => 'textarea',
					'label'       => __( 'Sentence over the grid', 'synergi' ),
					'description' => __( 'Optional.', 'synergi' ),
					'default'     => '',
					'rows'        => 2,
					'max_length'  => 240,
				),
				array(
					'key'         => 'case_list_empty',
					'type'        => 'textarea',
					'label'       => __( 'When there are none yet', 'synergi' ),
					'description' => __( 'Shown in place of the grid before the first case study is published, so the page is never a heading over nothing.', 'synergi' ),
					'default'     => __( 'Our first case studies are being written up. In the meantime, our team can talk you through comparable engagements.', 'synergi' ),
					'rows'        => 3,
					'max_length'  => 320,
				),
			),
		)
	);
}

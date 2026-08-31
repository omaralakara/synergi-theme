<?php
/**
 * The field groups that feed templates/service.php.
 *
 * Loaded by functions.php after inc/fields.php, whose engine this uses and does
 * not extend. Nothing here is machinery — it is five declarations describing
 * what an editor may type on a service page, and the engine does the rest.
 *
 * Separate from fields.php on the same reasoning that separated records.php:
 * fields.php is HOW a field works, this is WHICH fields exist. Adding a seventh
 * service line, or a solutions template at 6d, is an edit here and nowhere else.
 *
 * Replaces the SYN_DEBUG test bench that lived at the end of fields.php through
 * Stage 6a — it existed only because there was no real template to attach a box
 * to, and templates/service.php is now that template.
 *
 * Every group is scoped to templates/service.php, so none of these boxes appears
 * on an ordinary page (CLAUDE.md §7c). Every field carries a default, so a page
 * with nothing typed still renders the approved copy rather than empty headings.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The template every group below is scoped to. */
define( 'SYN_SERVICE_TEMPLATE', 'templates/service.php' );

add_action( 'syn_register_fields', 'syn_register_service_fields' );
/**
 * Registers the five field groups a service page carries.
 *
 * Side effects: registers five field groups on the service template.
 *
 * @return void
 */
function syn_register_service_fields() {

	/*
	 * 1. INTRO — the hero band.
	 *
	 * "Service line" is the only field on the page that is not copy, and it is
	 * not a design control either: it names which of the six lines this page is,
	 * which decides the accent gradient and which five appear under "keep
	 * exploring". An editor picks a service, never a colour (CLAUDE.md §7c).
	 */
	syn_register_field_group(
		array(
			'id'          => 'service_intro',
			'title'       => __( 'Service — intro', 'synergi' ),
			'description' => __( 'The band at the top of the page. The page title is the heading, so it is not repeated here.', 'synergi' ),
			'templates'   => array( SYN_SERVICE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'         => 'service_ref',
					'type'        => 'text',
					'label'       => __( 'Service line', 'synergi' ),
					'description' => __( 'Which of the six lines this page is: accounting, human-resources, marketing, procurement, project-management or technology-ai. Sets the accent and the related-services list.', 'synergi' ),
					'default'     => 'human-resources',
					'max_length'  => 40,
				),
				array(
					'key'        => 'service_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Our Services', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'         => 'service_lede',
					'type'        => 'textarea',
					'label'       => __( 'Opening sentence', 'synergi' ),
					'description' => __( 'One or two sentences. This is the first thing a reader and a search engine see.', 'synergi' ),
					'default'     => __( 'End-to-end solutions that align with your organizational goals, improve experience, and keep operations compliant.', 'synergi' ),
					'rows'        => 3,
					'max_length'  => 320,
				),
				array(
					'key'           => 'service_image',
					'type'          => 'image',
					'label'         => __( 'Photograph', 'synergi' ),
					'description'   => __( 'Sits beside the heading. Choose one with meaningful alt text already set on it.', 'synergi' ),
					'fallback_slug' => 'hero-dubai-team',
				),
				array(
					'key'     => 'service_cta',
					'type'    => 'link',
					'label'   => __( 'Main button', 'synergi' ),
					'default' => array(
						'url'   => '',
						'label' => __( 'Talk to our team', 'synergi' ),
					),
				),
				array(
					'key'     => 'service_cta_alt',
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
	 * 2. CAPABILITIES — what the page is for.
	 *
	 * Tags are one comma-separated text field rather than a nested repeater:
	 * repeaters do not nest (CLAUDE.md §7c, keep it boring), and three short
	 * words per row does not justify the machinery that would make them.
	 */
	syn_register_field_group(
		array(
			'id'          => 'service_capabilities',
			'title'       => __( 'Service — capabilities', 'synergi' ),
			'description' => __( 'The list of what this service line covers. The order here is the order on the page.', 'synergi' ),
			'templates'   => array( SYN_SERVICE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'capabilities_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'What this service covers', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'       => 'capabilities',
					'type'      => 'repeater',
					'label'     => __( 'Capabilities', 'synergi' ),
					'row_noun'  => __( 'Capability', 'synergi' ),
					'button'    => __( 'Add capability', 'synergi' ),
					'row_label' => 'title',
					'min_rows'  => 1,
					'max_rows'  => 16,
					'subfields' => array(
						array(
							'key'        => 'title',
							'type'       => 'text',
							'label'      => __( 'Title', 'synergi' ),
							'max_length' => 90,
						),
						array(
							'key'   => 'description',
							'type'  => 'textarea',
							'label' => __( 'Description', 'synergi' ),
							'rows'  => 4,
						),
						array(
							'key'         => 'tags',
							'type'        => 'text',
							'label'       => __( 'Tags', 'synergi' ),
							'description' => __( 'Two or three short words, separated by commas. Optional.', 'synergi' ),
							'max_length'  => 120,
						),
					),
				),
			),
		)
	);

	/*
	 * 3. PROCESS — the methodology. Same on every service page by default,
	 * because it is the company's method rather than the service's, but
	 * overridable where a line genuinely works differently.
	 */
	syn_register_field_group(
		array(
			'id'          => 'service_process',
			'title'       => __( 'Service — how we work', 'synergi' ),
			'description' => __( 'The delivery method. Leave untouched to show the standard four steps.', 'synergi' ),
			'templates'   => array( SYN_SERVICE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'process_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'From assessment to action', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'process_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => __( 'Our methodology pairs data-driven diagnostics with disciplined project management, turning insights into structured, milestone-led execution.', 'synergi' ),
					'rows'       => 3,
					'max_length' => 320,
				),
				array(
					'key'       => 'process',
					'type'      => 'repeater',
					'label'     => __( 'Steps', 'synergi' ),
					'row_noun'  => __( 'Step', 'synergi' ),
					'button'    => __( 'Add step', 'synergi' ),
					'row_label' => 'title',
					'min_rows'  => 1,
					'max_rows'  => 6,
					'default'   => array(
						array(
							'title'       => 'Assess',
							'description' => 'An assessment and process evaluation of the function as it runs today.',
						),
						array(
							'title'       => 'Design',
							'description' => 'Target operating model, location strategy and a technology enablement report.',
						),
						array(
							'title'       => 'Build',
							'description' => 'Business case and financial model, then the team, tooling and governance to deliver it.',
						),
						array(
							'title'       => 'Operate',
							'description' => 'Milestone-led execution with reporting, and a transfer path when you want it back.',
						),
					),
					'subfields' => array(
						array(
							'key'        => 'title',
							'type'       => 'text',
							'label'      => __( 'Step name', 'synergi' ),
							'max_length' => 60,
						),
						array(
							'key'   => 'description',
							'type'  => 'textarea',
							'label' => __( 'What happens', 'synergi' ),
							'rows'  => 3,
						),
					),
				),
			),
		)
	);

	/*
	 * 4. CASE STUDY — one proof point per service, asked for directly by the
	 * business (the content architecture sheet, 27 Aug).
	 *
	 * Postmeta and not a post type: a study featured on the HR page belongs to
	 * the HR page, which is CLAUDE.md §7a's test answered. The case-study
	 * archive is a separate, later stage and these link into it when it lands.
	 *
	 * "Client" is a TYPE, never a name, until names are cleared for publication.
	 * The description says so where an editor will read it.
	 */
	syn_register_field_group(
		array(
			'id'          => 'service_case',
			'title'       => __( 'Service — case study', 'synergi' ),
			'description' => __( 'One proof point. Leave the title empty and the whole section is skipped.', 'synergi' ),
			'templates'   => array( SYN_SERVICE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'case_title',
					'type'       => 'text',
					'label'      => __( 'Headline', 'synergi' ),
					'default'    => '',
					'max_length' => 120,
				),
				array(
					'key'         => 'case_client',
					'type'        => 'text',
					'label'       => __( 'Client type', 'synergi' ),
					'description' => __( 'What kind of organisation, not which one — e.g. “UAE organisation in early growth · Abu Dhabi”. Never publish a client name without written permission.', 'synergi' ),
					'default'     => '',
					'max_length'  => 140,
				),
				array(
					'key'    => 'case_brief',
					'type'   => 'textarea',
					'label'  => __( 'The situation', 'synergi' ),
					'rows'   => 4,
					'default' => '',
				),
				array(
					'key'   => 'case_image',
					'type'  => 'image',
					'label' => __( 'Photograph', 'synergi' ),
				),
				array(
					'key'       => 'case_scope',
					'type'      => 'repeater',
					'label'     => __( 'Scope of work', 'synergi' ),
					'row_noun'  => __( 'Item', 'synergi' ),
					'button'    => __( 'Add scope item', 'synergi' ),
					'row_label' => 'item',
					'min_rows'  => 1,
					'max_rows'  => 10,
					'subfields' => array(
						array(
							'key'   => 'item',
							'type'  => 'text',
							'label' => __( 'What was delivered', 'synergi' ),
						),
					),
				),
				array(
					'key'     => 'case_link',
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
	 * 5. FAQ — required on every service and solution page (27 Aug).
	 *
	 * The answer is the one leaf in the whole theme that keeps markup, because a
	 * link inside an answer is legitimate content (CLAUDE.md §7b).
	 */
	syn_register_field_group(
		array(
			'id'          => 'service_faq',
			'title'       => __( 'Service — frequently asked', 'synergi' ),
			'description' => __( 'Questions specific to this service. Leave the list empty and the section is skipped.', 'synergi' ),
			'templates'   => array( SYN_SERVICE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'faq_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Frequently asked', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'       => 'faqs',
					'type'      => 'repeater',
					'label'     => __( 'Questions', 'synergi' ),
					'row_noun'  => __( 'Question', 'synergi' ),
					'button'    => __( 'Add question', 'synergi' ),
					'row_label' => 'question',
					'min_rows'  => 1,
					'max_rows'  => 20,
					'subfields' => array(
						array(
							'key'        => 'question',
							'type'       => 'text',
							'label'      => __( 'Question', 'synergi' ),
							'max_length' => 200,
						),
						array(
							'key'         => 'answer',
							'type'        => 'html',
							'label'       => __( 'Answer', 'synergi' ),
							'description' => __( 'A link, bold, or a list is fine here. Anything else is removed when you save.', 'synergi' ),
							'rows'        => 5,
						),
					),
				),
			),
		)
	);
}

/**
 * The accent class for one service line.
 *
 * Written out in full rather than assembled from the slug, exactly as
 * sections/services.php does it, so every class in the stylesheet is findable
 * by searching for it (CLAUDE.md §13, the grep rule).
 *
 * @param string $slug Service slug.
 * @return string Class name, or "" when the slug is not one of the six.
 */
function syn_service_accent_class( $slug ) {
	$classes = array(
		'accounting'         => 'syn-service-hero--accounting',
		'human-resources'    => 'syn-service-hero--human-resources',
		'marketing'          => 'syn-service-hero--marketing',
		'procurement'        => 'syn-service-hero--procurement',
		'project-management' => 'syn-service-hero--project-management',
		'technology-ai'      => 'syn-service-hero--technology-ai',
	);

	$slug = sanitize_key( $slug );

	return isset( $classes[ $slug ] ) ? $classes[ $slug ] : '';
}

/**
 * The other service lines, for the "keep exploring" band.
 *
 * Reads the services record, so a seventh line added at Settings → Site records
 * appears on all six existing pages with no code change. Returns nothing when
 * the record is empty — the section then skips itself rather than rendering an
 * empty grid.
 *
 * @param string $exclude Slug of the current page's service line.
 * @return array[] Rows from the services record, minus the current one.
 */
function syn_other_services( $exclude ) {
	if ( ! function_exists( 'syn_record' ) ) {
		return array();
	}

	$exclude = sanitize_key( $exclude );
	$others  = array();

	foreach ( syn_record( 'services' ) as $service ) {
		$slug = sanitize_key( $service['slug'] ?? '' );

		if ( '' === $slug || $slug === $exclude || '' === ( $service['name'] ?? '' ) ) {
			continue;
		}

		$others[] = $service;
	}

	return $others;
}

/**
 * A service line's name, from its reference.
 *
 * The reference is what a page stores — "human-resources" — because it is
 * stable and a name is not: renaming a line at Settings → Site records must
 * change it everywhere at once, which is the whole reason the six are a record
 * (CLAUDE.md §7a). Anything showing a service line to a reader therefore looks
 * the name up here rather than storing a second copy of it.
 *
 * Returns "" for a reference the record does not have, so a caller can decide
 * between showing nothing and showing the raw reference. It never invents a
 * name by prettifying the slug: a line called "Technology & AI" would come back
 * as "Technology Ai", which is worse than saying nothing.
 *
 * @param string $slug The service reference.
 * @return string The name as an editor typed it, or "".
 */
function syn_service_name( $slug ) {
	$slug = sanitize_key( $slug );

	if ( '' === $slug || ! function_exists( 'syn_record' ) ) {
		return '';
	}

	foreach ( syn_record( 'services' ) as $service ) {
		if ( sanitize_key( $service['slug'] ?? '' ) === $slug ) {
			return trim( (string) ( $service['name'] ?? '' ) );
		}
	}

	return '';
}

/** The template the Our Services listing page uses. */
define( 'SYN_SERVICES_LISTING_TEMPLATE', 'templates/services-listing.php' );

add_action( 'syn_register_fields', 'syn_register_services_listing_fields' );
/**
 * Registers the one field group the Our Services listing page carries.
 *
 * One group, because the page is a hero over a grid and the grid is the
 * "services" record. There is deliberately NO field here for listing the service
 * lines: the six exist once, at Settings → Site records, and a field that let an
 * editor retype them on this page would be the second copy this architecture
 * exists to avoid (CLAUDE.md §7a). The fields below are the words around the
 * grid, and nothing else.
 *
 * Side effects: registers one field group on templates/services-listing.php.
 *
 * @return void
 */
function syn_register_services_listing_fields() {

	syn_register_field_group(
		array(
			'id'          => 'services_list',
			'title'       => __( 'Our Services — the page', 'synergi' ),
			'description' => __( 'The top of the page and the wording over the grid. The grid itself is the service lines at Settings → Site records, so adding a line there adds a card here.', 'synergi' ),
			'templates'   => array( SYN_SERVICES_LISTING_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'services_list_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Our Services', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'         => 'services_list_lede',
					'type'        => 'textarea',
					'label'       => __( 'Opening sentence', 'synergi' ),
					'description' => __( 'One or two sentences under the page title. This is the first thing a reader and a search engine see.', 'synergi' ),
					'default'     => __( 'Six service lines, delivered by teams that run them every day.', 'synergi' ),
					'rows'        => 3,
					'max_length'  => 320,
				),
				array(
					'key'         => 'services_list_image',
					'type'        => 'image',
					'label'       => __( 'Hero photograph', 'synergi' ),
					'description' => __( 'Optional. Fills the band behind the page title. Without one the band stays on the flat navy. Choose one with meaningful alt text already set on it.', 'synergi' ),
				),
				array(
					'key'     => 'services_list_cta',
					'type'    => 'link',
					'label'   => __( 'Button', 'synergi' ),
					'default' => array(
						'url'   => '',
						'label' => '',
					),
				),
				array(
					'key'        => 'services_list_heading',
					'type'       => 'text',
					'label'      => __( 'Heading over the grid', 'synergi' ),
					'default'    => __( 'Our service lines', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'         => 'services_list_grid_lede',
					'type'        => 'textarea',
					'label'       => __( 'Sentence over the grid', 'synergi' ),
					'description' => __( 'Optional.', 'synergi' ),
					'default'     => '',
					'rows'        => 2,
					'max_length'  => 240,
				),
				array(
					'key'         => 'services_list_empty',
					'type'        => 'textarea',
					'label'       => __( 'When the record is empty', 'synergi' ),
					'description' => __( 'Shown in place of the grid if no service lines have been added at Settings → Site records, so the page is never a heading over nothing.', 'synergi' ),
					'default'     => __( 'Our service lines are being updated. Please get in touch and we will point you to the right team.', 'synergi' ),
					'rows'        => 3,
					'max_length'  => 320,
				),
			),
		)
	);
}

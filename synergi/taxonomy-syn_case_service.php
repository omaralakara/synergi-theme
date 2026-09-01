<?php
/**
 * The case studies for one service line.
 *
 * Loaded by the template hierarchy for /case-studies/service/{reference}/ — the
 * URLs the syn_case_service taxonomy exists to provide, and the reason it exists
 * at all (see inc/case-study-post-type.php).
 *
 * Depends on: header.php, footer.php, inc/sections.php,
 * inc/case-study-fields.php, inc/service-fields.php, parts/page-header.php,
 * sections/case-studies.php.
 *
 * It composes the same two bands templates/case-studies-listing.php composes, so
 * a term archive and the full listing are visibly one thing at two scopes rather
 * than two designs (CLAUDE.md §4: reach for a new section only when the page
 * needs a shape none of the existing ones can make — this one needs none).
 *
 * The band is handed its cards directly rather than left to query, because the
 * term is the scope and sections/case-studies.php would otherwise fetch all of
 * them. The reference comes from the term slug, which
 * inc/case-study-post-type.php writes from the _syn_case_service field, so it is
 * always a service reference the services record knows.
 *
 * parts/page-header.php owns this page's one <h1> (CLAUDE.md §8).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_term      = get_queried_object();
$syn_reference = ( $syn_term && ! is_wp_error( $syn_term ) ) ? sanitize_key( $syn_term->slug ) : '';

/*
 * The name comes from the services record rather than the term, so a line
 * renamed at Settings → Site records is renamed here too — the term name is only
 * a fallback for a reference the record has since dropped (CLAUDE.md §7a).
 */
$syn_service_name = syn_service_name( $syn_reference );

if ( '' === $syn_service_name && $syn_term && ! is_wp_error( $syn_term ) ) {
	$syn_service_name = $syn_term->name;
}

$syn_cards = syn_case_studies( array( 'service' => $syn_reference ) );

/*
 * Declared BEFORE get_header(), because assets are enqueued during wp_head() and
 * a section declared after that renders unstyled.
 */
syn_use_sections( array( 'case-studies', 'final-cta' ) );

get_header();

get_template_part(
	'parts/page-header',
	null,
	array(
		'eyebrow' => __( 'Our work', 'synergi' ),
		'title'   => $syn_service_name
			/* translators: %s: a service line, e.g. Human Resources. */
			? sprintf( __( '%s case studies', 'synergi' ), $syn_service_name )
			: __( 'Case studies', 'synergi' ),
		'lede'    => '',
	)
);

syn_section(
	'case-studies',
	array(
		'eyebrow' => __( 'Our work', 'synergi' ),
		'heading' => $syn_service_name
			/* translators: %s: a service line, e.g. Human Resources. */
			? sprintf( __( 'What we have delivered in %s', 'synergi' ), $syn_service_name )
			: __( 'What we have delivered', 'synergi' ),
		'items'   => $syn_cards,

		/*
		 * A term archive only exists because a study points at it, so "empty"
		 * should be unreachable. It is written anyway: a study unpublished after
		 * its term was created lands here, and an empty band with no sentence is
		 * a page that looks broken (CLAUDE.md §7c: every field has a default).
		 */
		'empty'   => __( 'No case studies are published for this service line yet.', 'synergi' ),
	)
);

syn_section( 'final-cta' );

get_footer();

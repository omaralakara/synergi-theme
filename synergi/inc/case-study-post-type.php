<?php
/**
 * The case studies as a post type of their own.
 *
 * Loaded by functions.php BEFORE inc/case-study-fields.php, because that file's
 * field groups are scoped to the post type this one registers.
 *
 * WHY THIS REVERSES A DECISION. stage-6-remaining-plan.md's decision D4 held the
 * post type back to a stage of its own, and inc/case-study-fields.php's header
 * still explains why pages were right at the time: no rewrite rules, no invented
 * URLs, and a listing that finds studies by their template so there is no second
 * list to maintain. That reasoning held for twelve studies written in a week.
 * It stops holding the moment a service page wants to show its own studies
 * automatically, which is what the business asked for on 1 September 2026.
 *
 * WHAT THIS DOES NOT CHANGE — and the whole design turns on it:
 *
 *   /case-studies/{slug}/   twelve existing URLs, unchanged (CLAUDE.md §2.8).
 *                           The rewrite slug is "case-studies" and the studies
 *                           keep their own slugs, so every published URL
 *                           survives the move byte for byte.
 *   /case-studies/          still the ordinary page on
 *                           templates/case-studies-listing.php. has_archive is
 *                           deliberately FALSE so the page keeps that URL and
 *                           keeps its editable intro copy. An archive would have
 *                           taken the URL and thrown the copy away.
 *
 * WHAT IT ADDS:
 *
 *   /case-studies/service/{term}/   one per service line, free, and the reason
 *                                   the taxonomy exists at all.
 *
 * The taxonomy sits under "case-studies/service" rather than "case-studies" so a
 * term can never be mistaken for a study: with both on the same base, WordPress
 * resolves /case-studies/human-resources/ by whichever rewrite rule it reaches
 * first, and which one that is depends on registration order. A segment nobody
 * has to think about is cheaper than a collision somebody debugs at midnight
 * (CLAUDE.md §13).
 *
 * ON THE SERVICE REFERENCE. The editable source of truth stays the
 * _syn_case_service field — a select tied to the services record, already built,
 * already filled on all twelve studies. This file mirrors that value into the
 * taxonomy whenever a study is saved, so the term archives work without a second
 * box for an editor to keep in step with the first (CLAUDE.md §7a: stored once).
 * Read paths still ask syn_case_studies(); nothing queries the taxonomy for
 * content.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The post type holding one written-up engagement. */
define( 'SYN_CASE_STUDY_POST_TYPE', 'syn_case_study' );

/** The taxonomy tying a study to a service line, for its term archive. */
define( 'SYN_CASE_SERVICE_TAXONOMY', 'syn_case_service' );

/**
 * Bumped whenever a rewrite rule below changes, so inc/case-study-post-type.php
 * can flush exactly once after a deploy instead of on every request. Flushing on
 * every load is the classic way to make a site slow invisibly.
 */
define( 'SYN_CASE_STUDY_REWRITE_VERSION', '1' );

add_action( 'init', 'syn_register_case_study_post_type' );
/**
 * Registers the case-study post type and its service taxonomy.
 *
 * Side effects: registers one post type and one taxonomy; schedules a one-off
 * rewrite flush when SYN_CASE_STUDY_REWRITE_VERSION has moved.
 *
 * @return void
 */
function syn_register_case_study_post_type() {
	register_post_type(
		SYN_CASE_STUDY_POST_TYPE,
		array(
			'labels'             => array(
				'name'               => __( 'Case studies', 'synergi' ),
				'singular_name'      => __( 'Case study', 'synergi' ),
				'add_new_item'       => __( 'Add case study', 'synergi' ),
				'edit_item'          => __( 'Edit case study', 'synergi' ),
				'new_item'           => __( 'New case study', 'synergi' ),
				'view_item'          => __( 'View case study', 'synergi' ),
				'search_items'       => __( 'Search case studies', 'synergi' ),
				'not_found'          => __( 'No case studies yet', 'synergi' ),
				'not_found_in_trash' => __( 'No case studies in the bin', 'synergi' ),
				'all_items'          => __( 'All case studies', 'synergi' ),
				'menu_name'          => __( 'Case studies', 'synergi' ),
			),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-portfolio',
			'menu_position'      => 21,

			/*
			 * page-attributes is here for the Order box: syn_case_studies()
			 * orders by menu_order first so an editor can pin a study to the
			 * front of every grid without re-dating it. That behaviour predates
			 * this file and is preserved exactly.
			 */
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions', 'custom-fields' ),
			'has_archive'        => false,
			'rewrite'            => array(
				'slug'       => 'case-studies',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		)
	);

	register_taxonomy(
		SYN_CASE_SERVICE_TAXONOMY,
		array( SYN_CASE_STUDY_POST_TYPE ),
		array(
			'labels'            => array(
				'name'          => __( 'Service lines', 'synergi' ),
				'singular_name' => __( 'Service line', 'synergi' ),
				'menu_name'     => __( 'Service lines', 'synergi' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,

			/*
			 * Hidden from the editor screen on purpose. The _syn_case_service
			 * field is the one place a service is chosen; this taxonomy is
			 * written from it by syn_sync_case_study_service() below. Two boxes
			 * that mean the same thing is how they drift apart.
			 */
			'show_ui'           => false,
			'show_admin_column' => false,
			'rewrite'           => array(
				'slug'       => 'case-studies/service',
				'with_front' => false,
			),
		)
	);

	if ( get_option( 'syn_case_study_rewrite_version' ) !== SYN_CASE_STUDY_REWRITE_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'syn_case_study_rewrite_version', SYN_CASE_STUDY_REWRITE_VERSION );
	}
}

add_action( 'save_post_' . SYN_CASE_STUDY_POST_TYPE, 'syn_sync_case_study_service', 20, 1 );
/**
 * Mirrors a study's _syn_case_service field into the service taxonomy.
 *
 * Runs at priority 20 so inc/fields.php has already saved the field this reads.
 * A study whose reference is empty, or whose reference the services record no
 * longer has, is simply left with no term — it still appears in every grid,
 * because the grids query the field and not the taxonomy. The term exists only
 * to give /case-studies/service/{ref}/ something to list.
 *
 * Side effects: may create a term and does set post terms.
 *
 * @param int $post_id The case study being saved.
 * @return void
 */
function syn_sync_case_study_service( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$reference = sanitize_key( (string) syn_field( 'case_service', $post_id ) );

	if ( '' === $reference ) {
		wp_set_object_terms( $post_id, array(), SYN_CASE_SERVICE_TAXONOMY );

		return;
	}

	$term = get_term_by( 'slug', $reference, SYN_CASE_SERVICE_TAXONOMY );

	if ( ! $term ) {
		// The name comes from the services record, so renaming a line there
		// renames it here too on the next save (CLAUDE.md §7a).
		$name   = syn_service_name( $reference );
		$create = wp_insert_term( $name ? $name : $reference, SYN_CASE_SERVICE_TAXONOMY, array( 'slug' => $reference ) );

		if ( is_wp_error( $create ) ) {
			error_log( '[synergi] could not create service term "' . $reference . '": ' . $create->get_error_message() );

			return;
		}

		$term_id = (int) $create['term_id'];
	} else {
		$term_id = (int) $term->term_id;
	}

	wp_set_object_terms( $post_id, array( $term_id ), SYN_CASE_SERVICE_TAXONOMY );
}

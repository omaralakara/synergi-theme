<?php
/**
 * Stage 8 cutover — what runs AFTER the Synergi theme is activated.
 *
 * Runs on production through Novamira execute-php, straight after
 * Appearance → Themes → Activate. Pass true first to see what it would do,
 * then false.
 *
 *  - Deactivates the Elementor stack. Decided 10 Sep: the theme replaces it.
 *    Deactivated, never deleted — CLAUDE.md §2.9 keeps them for a week as the
 *    rollback — and the list is saved to syn_launch_deactivated_plugins so the
 *    rollback reactivates exactly these.
 *  - Hides case studies and their service archives from search, as on staging
 *    (decided 10 Sep). Yoast only accepts a content-type setting once the post
 *    type is registered, which is why this runs after activation and not
 *    inside the import.
 *  - Purges LiteSpeed.
 *  - Then checks, read-only, everything a visitor or Google would notice.
 *
 * Pair: tools/transfer-import.php runs immediately before the activation.
 *
 * @package SynergiTools
 */

defined( 'ABSPATH' ) || exit;

/**
 * The plugins retired at launch, by their plugin-header Name.
 *
 * By Name rather than file path, because an addon's folder is not always its
 * brand (Element Pack Pro lives in bdthemes-element-pack). Instagram Feed is
 * deliberately absent: inc/instagram.php reads the images it caches.
 *
 * Two groups, both decided 10 Sep: the Elementor stack, and the old theme's
 * own dependencies. Theratio needs Kirki and Meta Box while it is active, so
 * none of these can go before the switch — and the rollback reactivates them
 * from syn_launch_deactivated_plugins. The new theme links to Google Maps
 * without an API key (sections/offices.php), so the key plugin goes too.
 */
const SYN_LAUNCH_RETIRE_PLUGINS = array(
	'Elementor Pro',
	'Elementor Pro Activator',
	'Element Pack Pro',
	'Telephone field for Elementor Forms',
	'Synergi Homepage Assets',
	'Elementor',
	'Slider Revolution',
	'Kirki',
	'Meta Box',
	'API KEY for Google Maps',
);

/**
 * Yoast content-type settings that keep case studies out of search for now.
 * Staging carries exactly these two (verified 10 Sep).
 */
const SYN_LAUNCH_YOAST_HIDDEN = array( 'noindex-syn_case_study', 'noindex-tax-syn_case_service' );

/**
 * Retires the Elementor stack, hides case studies from search, purges the
 * cache, and reports whether the launched site is in the state it should be.
 *
 * Side effects when $dry_run is false: deactivates plugins, writes one option
 * (the rollback list), sets two Yoast options, purges LiteSpeed.
 *
 * @param bool $dry_run True to report without changing anything.
 * @return array Report, ending in SAFE_TO_ANNOUNCE.
 */
function syn_launch_after_activation( $dry_run = true ) {
	if ( 'synergi' !== get_stylesheet() ) {
		return array( 'error' => 'The active theme is "' . get_stylesheet() . '". Activate Synergi first. Nothing was changed.' );
	}

	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	$report = array( 'mode' => $dry_run ? 'DRY RUN — nothing changed' : 'LIVE' );

	// --- 1. retire the Elementor stack -------------------------------------
	$retire = array();
	foreach ( get_plugins() as $file => $data ) {
		if ( in_array( $data['Name'], SYN_LAUNCH_RETIRE_PLUGINS, true ) && is_plugin_active( $file ) ) {
			$retire[ $file ] = $data['Name'];
		}
	}

	$report['plugins'] = array( $dry_run ? 'would_deactivate' : 'deactivated' => array_values( $retire ) );

	if ( ! $dry_run && $retire ) {
		update_option( 'syn_launch_deactivated_plugins', array_keys( $retire ), false );
		deactivate_plugins( array_keys( $retire ) );
	}

	// --- 2. case studies out of search -------------------------------------
	if ( class_exists( 'WPSEO_Options' ) ) {
		foreach ( SYN_LAUNCH_YOAST_HIDDEN as $key ) {
			if ( ! $dry_run ) {
				WPSEO_Options::set( $key, true );
			}
			$report['yoast'][ $key ] = WPSEO_Options::get( $key )
				? 'hidden'
				: ( $dry_run ? 'WOULD HIDE' : 'FAILED — set it in Yoast SEO → Settings → Content types' );
		}
	} else {
		$report['yoast'] = 'Yoast is not loaded — hide case studies by hand in Yoast SEO → Settings';
	}

	// --- 3. cache ----------------------------------------------------------
	if ( ! $dry_run ) {
		do_action( 'litespeed_purge_all' );
		$report['cache'] = 'LiteSpeed purged';
	}

	// --- 4. checks, read-only ----------------------------------------------
	$front     = (int) get_option( 'page_on_front' );
	$blog      = (int) get_option( 'page_for_posts' );
	$locations = get_nav_menu_locations();
	$rules     = array_keys( (array) get_option( 'rewrite_rules', array() ) );
	$connect   = get_page_by_path( 'connect' );
	$form      = json_decode( (string) get_post_field( 'post_content', 7560, 'raw' ), true );

	$form_to = array();
	foreach ( (array) ( $form['settings']['notifications'] ?? array() ) as $n ) {
		$form_to[] = (string) ( $n['email'] ?? '' );
	}

	$still_active = array();
	foreach ( get_plugins() as $file => $data ) {
		if ( in_array( $data['Name'], SYN_LAUNCH_RETIRE_PLUGINS, true ) && is_plugin_active( $file ) ) {
			$still_active[] = $data['Name'];
		}
	}

	$checks = array(
		'front_page_is_rebuild'    => 'homepage-rebuild' === get_post_field( 'post_name', $front ),
		'front_page_not_noindexed' => '' === (string) get_post_meta( $front, '_yoast_wpseo_meta-robots-noindex', true ),
		'posts_page_is_blog'       => 'blog' === get_post_field( 'post_name', $blog ),
		'blog_public'              => 1 === (int) get_option( 'blog_public' ),
		'logo_set'                 => (bool) wp_get_attachment_image_src( (int) get_theme_mod( 'custom_logo' ), 'medium' ),
		'primary_menu_assigned'    => ! empty( $locations['primary'] ) && (bool) wp_get_nav_menu_items( $locations['primary'] ),
		// The theme flushes its own rules on its first load after activation.
		'case_study_rewrites'      => (bool) preg_grep( '#^case-studies/service/#', $rules ),
		'connect_published'        => $connect && 'publish' === $connect->post_status,
		'connect_noindexed'        => $connect && '1' === (string) get_post_meta( $connect->ID, '_yoast_wpseo_meta-robots-noindex', true ),
		'form_7560_has_phone'      => in_array( 'text:Phone', array_map( static function ( $f ) { return ( $f['type'] ?? '' ) . ':' . ( $f['label'] ?? '' ); }, (array) ( $form['fields'] ?? array() ) ), true ),
		'form_7560_notifies_bpo'   => $form_to && ! preg_grep( '/@(?!synergibpo\.com$)/', $form_to ),
		'elementor_stack_retired'  => $dry_run ? 'n/a in dry run' : empty( $still_active ),
	);

	$report['checks']        = $checks;
	$report['front_title']   = class_exists( 'WPSEO_Options' ) && function_exists( 'YoastSEO' ) ? YoastSEO()->meta->for_post( $front )->title : null;
	$report['form_notifies'] = $form_to;

	if ( $still_active && ! $dry_run ) {
		$report['still_active'] = $still_active;
	}

	$report['SAFE_TO_ANNOUNCE'] = ! $dry_run && ! in_array( false, $checks, true );

	return $report;
}

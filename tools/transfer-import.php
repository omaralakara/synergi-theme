<?php
/**
 * Stage 8 content transfer — PRODUCTION side (import).
 *
 * Runs on production through Novamira execute-php, in this order:
 *
 *   1. syn_transfer_import( $url, true )   Dry run. Read every line of it.
 *   2. syn_transfer_import_media( $url )   Repeat until 'remaining' is 0. Adds
 *                                          images to the media library and
 *                                          nothing else, so it can run before
 *                                          the window.
 *   3. syn_transfer_import( $url, false )  The live run. Refuses to start while
 *                                          an image is missing or a URL is
 *                                          contested, so once it starts it only
 *                                          writes to the database and finishes
 *                                          well inside production's 30-second
 *                                          limit.
 *
 * Then activate the Synergi theme at once, and run
 * tools/launch-after-activation.php.
 *
 * Design rules, each of which exists because of something measured:
 *
 *  - Content is matched by SLUG, never by ID. 14 of the 37 objects predate the
 *    Stage 0 clone and already exist on production under the same IDs; the rest
 *    are new. Slug is the only key that is correct for both.
 *  - A post and a top-level page both live at /{slug}/, so the URL decides. A
 *    payload post whose slug belongs to a production page updates that page,
 *    and it stays a page; any other clash stops the live run. An update never
 *    changes an object's type. (10 Sep: the ICXI announcement is a page on
 *    production and a post on staging, and the first dry run would have
 *    created a second, empty object at its URL.)
 *  - Attachments are NEVER carried by ID. Staging and production both minted
 *    IDs 10480-10527 for different images after the clone, so reusing an ID
 *    silently shows the wrong picture. Every image is matched by filename or
 *    downloaded, and every reference is rewritten through the map.
 *  - Meta is written with wp_slash(). update_metadata() unslashes, which eats
 *    the \u escapes inside the repeater JSON — that bug already shipped "u2014"
 *    to eight live pages once.
 *  - Redirects are MERGED, not replaced. Production carries one redirect that
 *    staging does not (page-not-found), and a straight overwrite deletes it.
 *  - page_on_front and page_for_posts are set explicitly. They are options, so
 *    they do not travel with content; without them the old Elementor homepage
 *    stays the front page and /blog/ never lists a post.
 *  - The new theme's logo and menu location go into its own theme_mods row,
 *    which nothing reads until the theme is activated.
 *  - The case-study post type and taxonomy are registered for the length of
 *    the run. The old theme is still active and the new one is what registers
 *    them; without this every service term fails with "invalid taxonomy".
 *
 * @package SynergiTools
 */

defined( 'ABSPATH' ) || exit;

/** The payload shape this importer understands. Bumped with the exporter's. */
const SYN_IMPORT_SCHEMA = 3;

/** The directory name the theme zip installs as, which names its theme_mods row. */
const SYN_IMPORT_THEME = 'synergi';

/**
 * Walks a value and rewrites every attachment ID through the map.
 *
 * Image fields store a bare ID; the repeater groups store IDs inside a JSON
 * string. Both shapes are handled, and a JSON string is returned re-encoded so
 * the stored shape does not change.
 *
 * @param mixed $value Meta value, record fragment, or nested array.
 * @param int[] $map   staging attachment ID => production attachment ID.
 * @return mixed The value with IDs rewritten.
 */
function syn_import_remap( $value, array $map ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $k => $v ) {
			$value[ $k ] = syn_import_remap( $v, $map );
		}
		return $value;
	}

	if ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) {
		$id = (int) $value;
		if ( isset( $map[ $id ] ) ) {
			return is_int( $value ) ? $map[ $id ] : (string) $map[ $id ];
		}
		return $value;
	}

	if ( is_string( $value ) ) {
		$decoded = json_decode( $value, true );
		if ( is_array( $decoded ) ) {
			return wp_json_encode( syn_import_remap( $decoded, $map ) );
		}
	}

	return $value;
}

/**
 * Finds a post by slug alone, at any depth in the hierarchy.
 *
 * NOT get_page_by_path(): that takes a full path, so passing the bare slug of a
 * child page never matches. The 9 Sep dry run found six pages that already
 * exist on production under /our-services/ and /our-solutions/ being reported
 * as new, which would have created duplicates of every service page.
 *
 * Trashed posts are excluded — WordPress renames their slug to "-__trashed",
 * so they cannot collide anyway, and matching one would resurrect it.
 *
 * @param string $slug post_name to find.
 * @param string $type Post type.
 * @return int Post ID, or 0.
 */
function syn_import_find_by_slug( $slug, $type ) {
	global $wpdb;

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			  WHERE post_name = %s AND post_type = %s
			    AND post_status IN ( 'publish', 'draft', 'pending', 'private' )
			  ORDER BY ID ASC LIMIT 1",
			$slug,
			$type
		)
	);
}

/**
 * Finds whatever already answers at /{slug}/ on production.
 *
 * Under /%postname%/ a post and a top-level page share that address, and
 * WordPress's own unique-slug check only compares objects of the same type, so
 * it will happily create a second one there. This is the check it does not make.
 *
 * @param string $slug post_name to look for.
 * @return int ID of the post or top-level page at /{slug}/, or 0.
 */
function syn_import_url_owner( $slug ) {
	global $wpdb;

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			  WHERE post_name = %s
			    AND ( post_type = 'post' OR ( post_type = 'page' AND post_parent = 0 ) )
			    AND post_status IN ( 'publish', 'draft', 'pending', 'private' )
			  ORDER BY ID ASC LIMIT 1",
			$slug
		)
	);
}

/**
 * Decides which production object a payload entry updates, if any.
 *
 * @param array $p One payload post entry.
 * @return array{id:int,conflict:string} id 0 and no conflict means "create".
 */
function syn_import_plan_target( array $p ) {
	$id = syn_import_find_by_slug( $p['slug'], $p['type'] );

	if ( $id || '' !== $p['parent_slug'] || ! in_array( $p['type'], array( 'post', 'page' ), true ) ) {
		return array( 'id' => $id, 'conflict' => '' );
	}

	$owner = syn_import_url_owner( $p['slug'] );

	if ( ! $owner ) {
		return array( 'id' => 0, 'conflict' => '' );
	}

	// The one clash that is expected: a blog post that production holds as a
	// page. Same URL, same article, so the page is updated and stays a page.
	if ( 'post' === $p['type'] && 'page' === get_post_type( $owner ) ) {
		return array( 'id' => $owner, 'conflict' => '' );
	}

	return array(
		'id'       => 0,
		'conflict' => '/' . $p['slug'] . '/ already belongs to ' . get_post_type( $owner ) . ' ' . $owner,
	);
}

/**
 * Finds or creates the production attachment matching a payload entry.
 *
 * Tries filename first — most of the images predate the clone and are already
 * on production under the same filename, so nothing is downloaded. Only
 * genuinely new files are sideloaded from staging.
 *
 * @param array $att     One payload attachment entry.
 * @param bool  $dry_run When true, resolves but never downloads or writes.
 * @return array{id:int,action:string}
 */
function syn_import_attachment( array $att, $dry_run ) {
	global $wpdb;

	$filename = (string) $att['filename'];

	if ( '' === $filename ) {
		return array( 'id' => 0, 'action' => 'skipped (no filename)' );
	}

	// _wp_attached_file holds "2026/09/name.webp"; match on the basename so a
	// different upload month on either side does not cause a duplicate.
	$existing = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta}
			  WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s
			  ORDER BY post_id ASC LIMIT 1",
			'%' . $wpdb->esc_like( $filename )
		)
	);

	if ( $existing ) {
		return array( 'id' => $existing, 'action' => 'matched by filename' );
	}

	if ( $dry_run ) {
		return array( 'id' => 0, 'action' => 'WOULD DOWNLOAD ' . $filename );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	// 15 s, not 60: production stops each call at 30 s, and one slow file must
	// not take the whole batch down with it.
	$tmp = download_url( $att['url'], 15 );

	if ( is_wp_error( $tmp ) ) {
		return array( 'id' => 0, 'action' => 'DOWNLOAD FAILED: ' . $tmp->get_error_message() );
	}

	$new_id = media_handle_sideload(
		array( 'name' => $filename, 'tmp_name' => $tmp ),
		0,
		null,
		array( 'post_title' => $att['title'], 'post_excerpt' => (string) $att['caption'] )
	);

	if ( is_wp_error( $new_id ) ) {
		wp_delete_file( $tmp );
		return array( 'id' => 0, 'action' => 'SIDELOAD FAILED: ' . $new_id->get_error_message() );
	}

	if ( '' !== (string) $att['alt'] ) {
		update_post_meta( $new_id, '_wp_attachment_image_alt', wp_slash( $att['alt'] ) );
	}

	return array( 'id' => (int) $new_id, 'action' => 'downloaded' );
}

/**
 * Fetches and validates the payload.
 *
 * sslverify stays true. The body of this response is written straight into
 * production posts, postmeta, the menu and the redirect table, so an
 * unverified fetch makes anything on the path between the two hosts an author
 * of the live site. If the certificate genuinely fails, fix the certificate —
 * do not turn the check off.
 *
 * @param string $payload_url Where payload.json lives.
 * @return array The decoded payload, or array( 'error' => string ).
 */
function syn_import_fetch_payload( $payload_url ) {
	$res = wp_remote_get( $payload_url, array( 'timeout' => 20, 'sslverify' => true ) );

	if ( is_wp_error( $res ) ) {
		return array( 'error' => 'fetch failed: ' . $res->get_error_message() );
	}

	$payload = json_decode( wp_remote_retrieve_body( $res ), true );

	if ( ! is_array( $payload ) || empty( $payload['posts'] ) ) {
		return array( 'error' => 'payload missing or unreadable (HTTP ' . wp_remote_retrieve_response_code( $res ) . ')' );
	}

	if ( SYN_IMPORT_SCHEMA !== (int) ( $payload['schema'] ?? 0 ) ) {
		return array( 'error' => 'payload schema ' . ( $payload['schema'] ?? '?' ) . ', importer expects ' . SYN_IMPORT_SCHEMA . ': the export and import scripts are from different commits' );
	}

	return $payload;
}

/**
 * Registers the case-study post type and taxonomy for this request only.
 *
 * The import runs while the old theme is still active, and the new theme is
 * what registers them (inc/case-study-post-type.php). Without this,
 * wp_insert_term() and wp_set_object_terms() fail with "invalid taxonomy" and
 * all twelve studies go live with no service line — found in the 10 Sep dry
 * run. Nothing here persists past the request.
 *
 * Rewrite is off on purpose: the theme flushes its own rules on its first load
 * after activation, and a flush from here would build them without it.
 *
 * @return void
 */
function syn_import_register_case_study_types() {
	if ( ! taxonomy_exists( 'syn_case_service' ) ) {
		register_taxonomy( 'syn_case_service', array( 'syn_case_study' ), array( 'public' => false, 'hierarchical' => true, 'rewrite' => false ) );
	}

	if ( ! post_type_exists( 'syn_case_study' ) ) {
		register_post_type( 'syn_case_study', array( 'public' => false, 'rewrite' => false ) );
	}
}

/**
 * Brings every payload image onto production, in time-boxed batches.
 *
 * Production stops each Novamira call at 30 seconds (max_execution_time,
 * measured 10 Sep), and the 10 Sep payload held 59 images production did not
 * have. Downloading them inside the live import would time it out part-way, so
 * they are fetched here first, before the window. Adding files to the media
 * library changes nothing a visitor sees.
 *
 * Call repeatedly until 'remaining' is 0. Files already present are matched by
 * filename and skipped, so a repeat call never duplicates anything.
 *
 * Side effects: downloads files and creates attachments.
 *
 * @param string $payload_url Where payload.json lives.
 * @param int    $max_seconds No new download starts after this many seconds.
 * @return array Report: downloaded, failed, remaining.
 */
function syn_transfer_import_media( $payload_url, $max_seconds = 10 ) {
	$start   = microtime( true );
	$payload = syn_import_fetch_payload( $payload_url );

	if ( isset( $payload['error'] ) ) {
		return $payload;
	}

	$report = array( 'downloaded' => array(), 'failed' => array(), 'remaining' => 0 );

	foreach ( (array) $payload['attachments'] as $att ) {
		$probe = syn_import_attachment( $att, true );

		if ( 0 !== strpos( $probe['action'], 'WOULD DOWNLOAD' ) ) {
			continue;
		}

		if ( microtime( true ) - $start > $max_seconds ) {
			$report['remaining']++;
			continue;
		}

		$result = syn_import_attachment( $att, false );

		if ( 'downloaded' === $result['action'] ) {
			$report['downloaded'][] = $att['filename'];
		} else {
			$report['failed'][ $att['filename'] ] = $result['action'];
			$report['remaining']++;
		}
	}

	$report['seconds'] = round( microtime( true ) - $start, 1 );

	return $report;
}

/**
 * Applies the whole payload.
 *
 * Side effects when $dry_run is false: creates/updates posts, writes postmeta
 * and terms, replaces the Main Menu, merges the redirect table, writes
 * syn_records, replaces the carried forms (keeping a backup of each), writes
 * the Synergi theme's theme_mods, and sets page_on_front and page_for_posts.
 * It never downloads: syn_transfer_import_media() does that first.
 *
 * @param string $payload_url Where to fetch payload.json from.
 * @param bool   $dry_run     True to report without writing.
 * @return array Report.
 */
function syn_transfer_import( $payload_url, $dry_run = true ) {
	$report = array( 'mode' => $dry_run ? 'DRY RUN — nothing written' : 'LIVE' );

	$payload = syn_import_fetch_payload( $payload_url );

	if ( isset( $payload['error'] ) ) {
		return $payload;
	}

	$report['generated'] = $payload['generated'];

	syn_import_register_case_study_types();

	// --- 1. plan, read-only ------------------------------------------------
	// Everything that can stop the run is decided here, before the first
	// write, so a refusal leaves production exactly as it was.
	$map     = array();
	$missing = array();

	foreach ( (array) $payload['attachments'] as $att ) {
		$result = syn_import_attachment( $att, true );
		if ( $result['id'] ) {
			$map[ (int) $att['id'] ] = $result['id'];
		} else {
			$missing[] = $result['action'];
		}
	}

	$report['attachment_summary'] = array(
		'in_payload' => count( (array) $payload['attachments'] ),
		'mapped'     => count( $map ),
		'missing'    => count( $missing ),
	);

	if ( $missing ) {
		$report['attachments_missing'] = $missing;
	}

	$plan      = array();
	$conflicts = array();

	foreach ( (array) $payload['posts'] as $i => $p ) {
		$plan[ $i ] = syn_import_plan_target( $p );
		if ( '' !== $plan[ $i ]['conflict'] ) {
			$conflicts[ $p['slug'] ] = $plan[ $i ]['conflict'];
		}
	}

	if ( $conflicts ) {
		$report['CONFLICTS'] = $conflicts;
	}

	if ( ! $dry_run && ( $missing || $conflicts ) ) {
		$report['error'] = 'Refused before writing anything: ' . count( $missing ) . ' images missing (run syn_transfer_import_media() until remaining is 0), ' . count( $conflicts ) . ' URL conflicts.';
		return $report;
	}

	// --- 2. taxonomy -------------------------------------------------------
	foreach ( (array) $payload['terms'] as $term ) {
		if ( term_exists( $term['slug'], 'syn_case_service' ) ) {
			$report['terms'][ $term['slug'] ] = 'exists';
			continue;
		}
		$report['terms'][ $term['slug'] ] = $dry_run ? 'WOULD CREATE' : 'created';
		if ( ! $dry_run ) {
			$made = wp_insert_term( $term['name'], 'syn_case_service', array( 'slug' => $term['slug'], 'description' => $term['description'] ) );
			if ( is_wp_error( $made ) ) {
				$report['terms'][ $term['slug'] ] = 'FAILED: ' . $made->get_error_message();
			}
		}
	}

	// Content and forms written below came from staging and were signed off
	// there. kses would rewrite them on the way in whenever the running user
	// lacks unfiltered_html; the WordPress importer lifts the filters for the
	// same reason. Restored by kses_init() straight after.
	if ( ! $dry_run ) {
		kses_remove_filters();
	}

	// --- 3. posts, pass one: create or update, no parents yet --------------
	$slug_to_id = array();

	foreach ( (array) $payload['posts'] as $i => $p ) {
		if ( '' !== $plan[ $i ]['conflict'] ) {
			continue;
		}

		$existing = $plan[ $i ]['id'];

		$data = array(
			'post_type'    => $p['type'],
			'post_name'    => $p['slug'],
			'post_title'   => $p['title'],
			'post_status'  => $p['status'],
			'post_excerpt' => (string) $p['excerpt'],
			'menu_order'   => (int) $p['menu_order'],
		);

		// Null content means "leave production's alone" — a templated page, or
		// one staging holds empty. Only the repaired blog posts carry a body.
		if ( null !== $p['content'] ) {
			$data['post_content']        = $p['content'];
			$report['content_written'][] = $p['slug'];
		}

		if ( $existing ) {
			$id = $existing;

			// Never change an existing object's type: a page rewritten as a post
			// leaves the page hierarchy, and every menu item and redirect that
			// points at it (see syn_import_plan_target()).
			unset( $data['post_type'] );
			$kept = get_post_type( $id ) !== $p['type'] ? ', stays a ' . get_post_type( $id ) : '';

			$report['posts'][ $p['slug'] ] = ( $dry_run ? 'WOULD UPDATE' : 'updated' ) . ' (id ' . $id . $kept . ')';
			if ( ! $dry_run ) {
				$data['ID'] = $id;
				wp_update_post( wp_slash( $data ) );
			}
		} else {
			$report['posts'][ $p['slug'] ] = $dry_run ? 'WOULD CREATE' : 'created';
			$id = 0;
			if ( ! $dry_run ) {
				$id = (int) wp_insert_post( wp_slash( $data ) );
			}
		}

		if ( $id ) {
			$slug_to_id[ $p['slug'] ] = $id;
		}

		if ( $dry_run || ! $id ) {
			continue;
		}

		// meta, with every attachment reference rewritten
		foreach ( (array) $p['meta'] as $key => $value ) {
			update_post_meta( $id, $key, wp_slash( syn_import_remap( $value, $map ) ) );
		}

		/*
		 * Belt as well as braces. The export refuses to put these keys in the
		 * payload; this deletes any that production is already carrying of its
		 * own accord — /media/ has held an inherited noindex since 2024, and
		 * nothing else in this run would clear it.
		 */
		foreach ( array( '_yoast_wpseo_meta-robots-noindex', '_yoast_wpseo_meta-robots-nofollow' ) as $never ) {
			if ( '' !== (string) get_post_meta( $id, $never, true ) ) {
				delete_post_meta( $id, $never );
				$report['noindex_cleared'][] = $p['slug'] . ' (' . $never . ')';
			}
		}

		if ( ! empty( $p['terms']['syn_case_service'] ) ) {
			$set = wp_set_object_terms( $id, $p['terms']['syn_case_service'], 'syn_case_service', false );
			if ( is_wp_error( $set ) ) {
				$report['term_failures'][ $p['slug'] ] = $set->get_error_message();
			}
		}
	}

	// --- 4. forms embedded by ID -------------------------------------------
	foreach ( (array) ( $payload['forms'] ?? array() ) as $form_id => $form ) {
		$form_id = (int) $form_id;

		if ( 'wpforms' !== get_post_type( $form_id ) ) {
			$report['forms'][ $form_id ] = 'NOT ON PRODUCTION — the contact page would render no form';
			continue;
		}

		$report['forms'][ $form_id ] = $dry_run ? 'WOULD REPLACE' : 'replaced';

		if ( ! $dry_run ) {
			// Production's own copy is kept for the rollback (launch runbook).
			update_option( 'syn_wpforms_' . $form_id . '_backup_launch', get_post_field( 'post_content', $form_id, 'raw' ), false );
			wp_update_post( wp_slash( array( 'ID' => $form_id, 'post_content' => $form['content'] ) ) );
		}
	}

	if ( ! $dry_run ) {
		kses_init();
	}

	// --- 5. posts, pass two: parents, now that every slug has an ID --------
	if ( ! $dry_run ) {
		foreach ( (array) $payload['posts'] as $p ) {
			if ( '' === $p['parent_slug'] || ! isset( $slug_to_id[ $p['slug'] ] ) ) {
				continue;
			}
			$parent = $slug_to_id[ $p['parent_slug'] ] ?? 0;
			if ( $parent ) {
				wp_update_post( array( 'ID' => $slug_to_id[ $p['slug'] ], 'post_parent' => $parent ) );
			}
		}
	}

	// --- 6. site records ---------------------------------------------------
	$report['records'] = $dry_run
		? 'WOULD WRITE ' . count( (array) $payload['records'] ) . ' records'
		: ( update_option( 'syn_records', syn_import_remap( $payload['records'], $map ) ) ? 'written' : 'unchanged' );

	// --- 7. redirects, merged ---------------------------------------------
	$existing_redirects = (array) get_option( 'wpseo-premium-redirects-base', array() );
	$by_origin          = array();

	foreach ( $existing_redirects as $r ) {
		if ( is_array( $r ) && isset( $r['origin'] ) ) {
			$by_origin[ $r['origin'] ] = $r;
		}
	}

	$added   = 0;
	$changed = 0;
	foreach ( (array) $payload['redirects'] as $r ) {
		if ( ! is_array( $r ) || ! isset( $r['origin'] ) ) {
			continue;
		}
		if ( ! isset( $by_origin[ $r['origin'] ] ) ) {
			$added++;
		} elseif ( ( $by_origin[ $r['origin'] ]['url'] ?? '' ) !== ( $r['url'] ?? '' ) ) {
			$changed++;
		}
		$by_origin[ $r['origin'] ] = $r;
	}

	$report['redirects'] = array(
		'production_had' => count( $existing_redirects ),
		'payload_has'    => count( (array) $payload['redirects'] ),
		'added'          => $added,
		'retargeted'     => $changed,
		'total_after'    => count( $by_origin ),
	);

	if ( ! $dry_run ) {
		$merged = array_values( $by_origin );
		update_option( 'wpseo-premium-redirects-base', $merged );
		update_option( 'wpseo-premium-redirects-export-plain', $merged );
	}

	// --- 8. menu -----------------------------------------------------------
	$menu = wp_get_nav_menu_object( 'Main Menu' );
	$report['menu'] = array( 'items_in_payload' => count( (array) $payload['menu'] ) );

	if ( ! $dry_run ) {
		if ( ! $menu ) {
			$menu_id = wp_create_nav_menu( 'Main Menu' );
			$menu    = wp_get_nav_menu_object( $menu_id );
		}

		// Rebuilt wholesale: the payload is the approved menu, and reconciling
		// item-by-item across two installs is far more failure modes than
		// deleting 30 rows and writing 30 rows.
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $old ) {
			wp_delete_post( $old->ID, true );
		}

		$old_to_new = array();
		foreach ( (array) $payload['menu'] as $item ) {
			$args = array(
				'menu-item-title'       => $item['title'],
				'menu-item-url'         => $item['url'],
				'menu-item-target'      => $item['target'],
				'menu-item-classes'     => implode( ' ', (array) $item['classes'] ),
				'menu-item-attr-title'  => $item['attr_title'],
				'menu-item-description' => $item['description'],
				'menu-item-status'      => 'publish',
				'menu-item-position'    => (int) $item['order'],
				'menu-item-parent-id'   => $old_to_new[ $item['parent'] ] ?? 0,
			);

			if ( 'post_type' === $item['type'] && ! empty( $item['object_slug'] ) ) {
				$target = syn_import_find_by_slug( $item['object_slug'], $item['object'] );
				if ( $target ) {
					$args['menu-item-type']      = 'post_type';
					$args['menu-item-object']    = $item['object'];
					$args['menu-item-object-id'] = $target;
					unset( $args['menu-item-url'] );
				}
			} elseif ( 'custom' !== $item['type'] ) {
				$args['menu-item-type'] = $item['type'];
			}

			$new_id = wp_update_nav_menu_item( $menu->term_id, 0, $args );
			if ( ! is_wp_error( $new_id ) ) {
				$old_to_new[ (int) $item['id'] ] = (int) $new_id;
			}
		}

		$report['menu']['rebuilt'] = count( $old_to_new );
	}

	// --- 9. front page -----------------------------------------------------
	// An option, so it never travels with content. Without this the old
	// Elementor homepage remains the front page after the theme switch.
	$home = $slug_to_id['homepage-rebuild'] ?? syn_import_find_by_slug( 'homepage-rebuild', 'page' );

	$report['front_page'] = array(
		'currently' => (int) get_option( 'page_on_front' ),
		'should_be' => $home,
	);

	if ( ! $dry_run && $home ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home );
		$report['front_page']['set'] = true;
	}

	// --- 10. posts page ----------------------------------------------------
	// Also an option. Production had none (page_for_posts = 0, 10 Sep), so
	// without this /blog/ renders as an ordinary page and never lists a post.
	$blog_slug = (string) ( $payload['reading']['page_for_posts'] ?? '' );
	$blog      = '' !== $blog_slug ? ( $slug_to_id[ $blog_slug ] ?? syn_import_find_by_slug( $blog_slug, 'page' ) ) : 0;

	$report['posts_page'] = array(
		'currently' => (int) get_option( 'page_for_posts' ),
		'should_be' => $blog,
	);

	if ( ! $dry_run && $blog ) {
		update_option( 'page_for_posts', $blog );
		$report['posts_page']['set'] = true;
	}

	// --- 11. the new theme's own settings ----------------------------------
	// Theme mods are stored per theme, so the logo and menu location set on
	// staging's Synergi theme do not exist on production. They are written
	// straight into its theme_mods row, which nothing reads until activation;
	// switch_theme() keeps an existing row rather than seeding a new one, so
	// they survive it. The old theme's own row is left alone for the rollback.
	$logo_staging = (int) ( $payload['theme_mods']['custom_logo'] ?? 0 );
	$logo         = $logo_staging && isset( $map[ $logo_staging ] ) ? (int) $map[ $logo_staging ] : 0;
	$menu_term    = $menu ? (int) $menu->term_id : 0;

	$report['theme_mods'] = array(
		'row'          => 'theme_mods_' . SYN_IMPORT_THEME,
		'custom_logo'  => $logo,
		'primary_menu' => $menu_term,
	);

	if ( ! $dry_run ) {
		$mods = get_option( 'theme_mods_' . SYN_IMPORT_THEME );
		$mods = is_array( $mods ) ? $mods : array();

		if ( $logo ) {
			$mods['custom_logo'] = $logo;
		}
		if ( $menu_term ) {
			$mods['nav_menu_locations'] = array_merge( (array) ( $mods['nav_menu_locations'] ?? array() ), array( 'primary' => $menu_term ) );
		}

		update_option( 'theme_mods_' . SYN_IMPORT_THEME, $mods );
	}

	/*
	 * --- 12. the launch assertions -----------------------------------------
	 *
	 * Things that are individually invisible and collectively fatal: a
	 * noindexed front page, a site-wide "discourage search engines", a front
	 * page that is not the one this run just built, and a blog that lists
	 * nothing. Each fails silently — the site looks fine and quietly loses
	 * search traffic over the following fortnight. Asserted here so the
	 * import's own output says whether the launch is safe.
	 *
	 * These are READ-ONLY even in a live run. blog_public is a Settings →
	 * Reading checkbox and belongs to whoever is running the launch, not to a
	 * content transfer. In a dry run the first and last read FAIL until the
	 * live run sets them; that is expected.
	 */
	$front      = $dry_run ? (int) get_option( 'page_on_front' ) : (int) $home;
	$posts_page = $dry_run ? (int) get_option( 'page_for_posts' ) : (int) $blog;

	$report['ASSERTIONS'] = array(
		'front_page_is_the_rebuild' => $front && $front === (int) $home ? 'PASS' : 'FAIL',
		'front_page_noindex'        => '' === (string) get_post_meta( $front, '_yoast_wpseo_meta-robots-noindex', true ) ? 'PASS' : 'FAIL — clear it before announcing launch',
		'blog_public'               => 1 === (int) get_option( 'blog_public' ) ? 'PASS' : 'FAIL — Settings → Reading → uncheck "Discourage search engines"',
		'posts_page_is_blog'        => $posts_page && $posts_page === (int) $blog ? 'PASS' : 'FAIL',
	);

	$report['SAFE_TO_ANNOUNCE'] = empty( $conflicts ) && ! in_array( 'FAIL', array_map( static function ( $v ) {
		return 0 === strpos( (string) $v, 'FAIL' ) ? 'FAIL' : 'PASS';
	}, $report['ASSERTIONS'] ), true );

	return $report;
}

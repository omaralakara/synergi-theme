<?php
/**
 * Stage 8 content transfer — PRODUCTION side (import).
 *
 * Runs on production through Novamira execute-php. Fetches the payload written
 * by tools/transfer-export.php and applies it. Pass $syn_dry_run = true to get
 * the full report with nothing written — always do that first and read the diff
 * (migration-plan.md runbook, step 4).
 *
 * Design rules, each of which exists because of something measured on 9 Sep:
 *
 *  - Content is matched by SLUG, never by ID. 14 of the 37 objects predate the
 *    Stage 0 clone and already exist on production under the same IDs; the rest
 *    are new. Slug is the only key that is correct for both.
 *  - Attachments are NEVER carried by ID. Staging and production both minted
 *    IDs 10480-10527 for different images after the clone, so reusing an ID
 *    silently shows the wrong picture. Every image is matched by filename or
 *    re-downloaded, and every reference is rewritten through the map.
 *  - Meta is written with wp_slash(). update_metadata() unslashes, which eats
 *    the \u escapes inside the repeater JSON — that bug already shipped "u2014"
 *    to eight live pages once.
 *  - Redirects are MERGED, not replaced. Production carries one redirect that
 *    staging does not (page-not-found), and a straight overwrite deletes it.
 *  - page_on_front is set explicitly. It is an option, so it does not travel
 *    with content, and without this the old Elementor homepage stays the front
 *    page after the theme switch.
 *
 * @package SynergiTools
 */

defined( 'ABSPATH' ) || exit;

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
 * Finds or creates the production attachment matching a payload entry.
 *
 * Tries filename first — most of the 96 images predate the clone and are
 * already on production under the same filename, so nothing is downloaded.
 * Only genuinely new files are sideloaded from staging.
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

	$tmp = download_url( $att['url'], 60 );

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
		@unlink( $tmp );
		return array( 'id' => 0, 'action' => 'SIDELOAD FAILED: ' . $new_id->get_error_message() );
	}

	if ( '' !== (string) $att['alt'] ) {
		update_post_meta( $new_id, '_wp_attachment_image_alt', wp_slash( $att['alt'] ) );
	}

	return array( 'id' => (int) $new_id, 'action' => 'downloaded' );
}

/**
 * Applies the whole payload.
 *
 * Side effects when $dry_run is false: creates/updates posts and attachments,
 * writes postmeta and terms, replaces the Main Menu, merges the redirect table,
 * writes syn_records, and sets page_on_front.
 *
 * @param string $payload_url Where to fetch payload.json from.
 * @param bool   $dry_run     True to report without writing.
 * @return array Report.
 */
function syn_transfer_import( $payload_url, $dry_run = true ) {
	$report = array( 'mode' => $dry_run ? 'DRY RUN — nothing written' : 'LIVE' );

	/*
	 * sslverify stays true. The body of this response is written straight into
	 * production posts, postmeta, the menu and the redirect table, so an
	 * unverified fetch makes anything on the path between the two hosts an
	 * author of the live site. If the certificate genuinely fails, fix the
	 * certificate — do not turn the check off.
	 */
	$res = wp_remote_get( $payload_url, array( 'timeout' => 60, 'sslverify' => true ) );

	if ( is_wp_error( $res ) ) {
		return array( 'error' => 'fetch failed: ' . $res->get_error_message() );
	}

	$payload = json_decode( wp_remote_retrieve_body( $res ), true );

	if ( ! is_array( $payload ) || empty( $payload['posts'] ) ) {
		return array( 'error' => 'payload missing or unreadable' );
	}

	if ( 2 !== (int) ( $payload['schema'] ?? 0 ) ) {
		return array( 'error' => 'unexpected schema version ' . ( $payload['schema'] ?? '?' ) );
	}

	$report['generated'] = $payload['generated'];

	// --- 1. taxonomy -------------------------------------------------------
	foreach ( (array) $payload['terms'] as $term ) {
		if ( term_exists( $term['slug'], 'syn_case_service' ) ) {
			$report['terms'][ $term['slug'] ] = 'exists';
			continue;
		}
		$report['terms'][ $term['slug'] ] = $dry_run ? 'WOULD CREATE' : 'created';
		if ( ! $dry_run ) {
			wp_insert_term( $term['name'], 'syn_case_service', array( 'slug' => $term['slug'], 'description' => $term['description'] ) );
		}
	}

	// --- 2. attachments ----------------------------------------------------
	$map = array();
	foreach ( (array) $payload['attachments'] as $att ) {
		$result = syn_import_attachment( $att, $dry_run );
		if ( $result['id'] ) {
			$map[ (int) $att['id'] ] = $result['id'];
		}
		if ( 0 !== strpos( $result['action'], 'matched' ) ) {
			$report['attachments'][ $att['filename'] ] = $result['action'];
		}
	}
	$report['attachment_summary'] = array(
		'in_payload' => count( (array) $payload['attachments'] ),
		'mapped'     => count( $map ),
		'remapped'   => count( array_filter( $map, static function ( $v, $k ) { return $v !== $k; }, ARRAY_FILTER_USE_BOTH ) ),
	);

	// --- 3. posts, pass one: create or update, no parents yet --------------
	$slug_to_id = array();

	foreach ( (array) $payload['posts'] as $p ) {
		$existing = syn_import_find_by_slug( $p['slug'], $p['type'] );

		$data = array(
			'post_type'    => $p['type'],
			'post_name'    => $p['slug'],
			'post_title'   => $p['title'],
			'post_status'  => $p['status'],
			'post_excerpt' => (string) $p['excerpt'],
			'menu_order'   => (int) $p['menu_order'],
		);

		// Null content means "template-driven, leave production's alone" — that
		// is what preserves the Elementor rollback (CLAUDE.md 2.9).
		if ( null !== $p['content'] ) {
			$data['post_content'] = $p['content'];
		}

		if ( $existing ) {
			$id = $existing;
			$report['posts'][ $p['slug'] ] = $dry_run ? 'WOULD UPDATE (id ' . $id . ')' : 'updated';
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
			wp_set_object_terms( $id, $p['terms']['syn_case_service'], 'syn_case_service', false );
		}
	}

	// --- 4. posts, pass two: parents, now that every slug has an ID --------
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

	// --- 5. site records ---------------------------------------------------
	$report['records'] = $dry_run
		? 'WOULD WRITE ' . count( (array) $payload['records'] ) . ' records'
		: ( update_option( 'syn_records', syn_import_remap( $payload['records'], $map ) ) ? 'written' : 'unchanged' );

	// --- 6. redirects, merged ---------------------------------------------
	$existing_redirects = (array) get_option( 'wpseo-premium-redirects-base', array() );
	$by_origin          = array();

	foreach ( $existing_redirects as $r ) {
		if ( is_array( $r ) && isset( $r['origin'] ) ) {
			$by_origin[ $r['origin'] ] = $r;
		}
	}

	$added = 0;
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

	// --- 7. menu -----------------------------------------------------------
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

		set_theme_mod( 'nav_menu_locations', array_merge( (array) get_theme_mod( 'nav_menu_locations', array() ), array( 'primary' => $menu->term_id ) ) );
		$report['menu']['rebuilt'] = count( $old_to_new );
	}

	// --- 8. front page -----------------------------------------------------
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

	/*
	 * --- 9. the launch assertions ------------------------------------------
	 *
	 * Three things that are individually invisible and collectively fatal: a
	 * noindexed front page, a site-wide "discourage search engines", and a
	 * front page that is not the one this run just built. Every one of them
	 * fails silently — the site looks perfect and disappears from Google over
	 * the following fortnight. Asserted here so the import's own output says
	 * whether the launch is safe, rather than a human remembering to look.
	 *
	 * These are READ-ONLY even in a live run. blog_public is a Settings →
	 * Reading checkbox and belongs to whoever is running the launch, not to a
	 * content transfer.
	 */
	$front = $dry_run ? (int) get_option( 'page_on_front' ) : (int) $home;

	$report['ASSERTIONS'] = array(
		'front_page_is_the_rebuild' => $front && $front === (int) $home ? 'PASS' : 'FAIL',
		'front_page_noindex'        => '' === (string) get_post_meta( $front, '_yoast_wpseo_meta-robots-noindex', true ) ? 'PASS' : 'FAIL — clear it before announcing launch',
		'blog_public'               => 1 === (int) get_option( 'blog_public' ) ? 'PASS' : 'FAIL — Settings → Reading → uncheck "Discourage search engines"',
	);

	$report['SAFE_TO_ANNOUNCE'] = ! in_array( 'FAIL', array_map( static function ( $v ) {
		return 0 === strpos( (string) $v, 'FAIL' ) ? 'FAIL' : 'PASS';
	}, $report['ASSERTIONS'] ), true );

	return $report;
}

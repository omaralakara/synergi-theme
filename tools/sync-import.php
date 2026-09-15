<?php
/**
 * Content sync — STAGING side (import), production → staging.
 *
 * Pair of tools/sync-export.php. Runs on staging through Novamira execute-php,
 * reading the payload the production export PUT into staging's own filesystem:
 *
 *   1. syn_sync_import( $file, true )     Dry run. Read every line of it.
 *   2. syn_sync_import_media( $file )     Repeat until 'remaining' is 0.
 *   3. syn_sync_import( $file, false )    The live run.
 *
 * Everything the launch importer learned the hard way still applies
 * (tools/transfer-import.php header): content matched by slug, never by ID;
 * attachments matched by filename or downloaded, every reference rewritten
 * through the map; meta written with wp_slash(); redirects merged; the menu
 * rebuilt wholesale; options set explicitly.
 *
 * Two things differ from the launch importer:
 *   - a post/page type clash at one URL updates whatever staging already has
 *     there and keeps its type, in either direction (the ICXI announcement is
 *     a page on production and a post on staging);
 *   - before the first write, a snapshot of everything about to change goes
 *     into one option, so the run can be undone without a database restore.
 *
 * @package SynergiTools
 */

defined( 'ABSPATH' ) || exit;

const SYN_SYNC_SCHEMA = 'sync-1';
const SYN_SYNC_THEME  = 'synergi';
const SYN_SYNC_BACKUP = 'syn_sync_backup_20260915';

/**
 * Walks a value and rewrites every attachment ID through the map.
 *
 * @param mixed $value Meta value, record fragment, or nested array.
 * @param int[] $map   Production attachment ID => staging attachment ID.
 * @return mixed
 */
function syn_sync_remap( $value, array $map ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $k => $v ) {
			$value[ $k ] = syn_sync_remap( $v, $map );
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
			return wp_json_encode( syn_sync_remap( $decoded, $map ) );
		}
	}

	return $value;
}

/**
 * Finds a post by slug alone, at any depth, excluding trash.
 *
 * @param string $slug post_name.
 * @param string $type Post type.
 * @return int Post ID or 0.
 */
function syn_sync_find_by_slug( $slug, $type ) {
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
 * Whatever already answers at /{slug}/ — a post or a top-level page.
 *
 * @param string $slug post_name.
 * @return int
 */
function syn_sync_url_owner( $slug ) {
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
 * Decides which staging object a payload entry updates, if any.
 *
 * @param array $p Payload post entry.
 * @return array{id:int,conflict:string}
 */
function syn_sync_plan_target( array $p ) {
	$id = syn_sync_find_by_slug( $p['slug'], $p['type'] );

	if ( $id || '' !== $p['parent_slug'] || ! in_array( $p['type'], array( 'post', 'page' ), true ) ) {
		return array( 'id' => $id, 'conflict' => '' );
	}

	$owner = syn_sync_url_owner( $p['slug'] );

	if ( ! $owner ) {
		return array( 'id' => 0, 'conflict' => '' );
	}

	// Same URL, same article, different type on the two sites: update what is
	// there and let it keep its type.
	if ( in_array( get_post_type( $owner ), array( 'post', 'page' ), true ) ) {
		return array( 'id' => $owner, 'conflict' => '' );
	}

	return array( 'id' => 0, 'conflict' => '/' . $p['slug'] . '/ already belongs to ' . get_post_type( $owner ) . ' ' . $owner );
}

/**
 * Finds or creates the staging attachment matching a payload entry.
 *
 * @param array $att     Payload attachment entry.
 * @param bool  $dry_run Resolve only.
 * @return array{id:int,action:string}
 */
function syn_sync_attachment( array $att, $dry_run ) {
	global $wpdb;

	$filename = (string) $att['filename'];

	if ( '' === $filename ) {
		return array( 'id' => 0, 'action' => 'skipped (no filename)' );
	}

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

	/*
	 * A name ending in -WIDTHxHEIGHT looks like a generated size to WordPress,
	 * so wp_unique_filename() renames it on the way in (synergi-share-1200x630.png
	 * became synergi-share-1200x630-1.png on 15 Sep). Match that spelling too.
	 */
	$dot = strrpos( $filename, '.' );
	if ( false !== $dot ) {
		$existing = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				  WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s
				  ORDER BY post_id ASC LIMIT 1",
				'%' . $wpdb->esc_like( substr( $filename, 0, $dot ) ) . '-%' . $wpdb->esc_like( substr( $filename, $dot ) )
			)
		);

		if ( $existing ) {
			return array( 'id' => $existing, 'action' => 'matched by filename (renamed on upload)' );
		}
	}

	if ( $dry_run ) {
		return array( 'id' => 0, 'action' => 'WOULD DOWNLOAD ' . $filename );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

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
 * Reads and validates the payload from a file on this server.
 *
 * @param string $file Absolute path.
 * @return array Payload or array( 'error' => string ).
 */
function syn_sync_read_payload( $file ) {
	if ( ! is_readable( $file ) ) {
		return array( 'error' => 'payload not readable: ' . $file );
	}

	$payload = json_decode( file_get_contents( $file ), true );

	if ( ! is_array( $payload ) || empty( $payload['posts'] ) ) {
		return array( 'error' => 'payload unreadable: ' . json_last_error_msg() );
	}

	if ( SYN_SYNC_SCHEMA !== ( $payload['schema'] ?? '' ) ) {
		return array( 'error' => 'payload schema ' . ( $payload['schema'] ?? '?' ) . ', importer expects ' . SYN_SYNC_SCHEMA );
	}

	return $payload;
}

/**
 * Brings every payload image onto staging, in time-boxed batches.
 *
 * @param string $file        Payload path.
 * @param int    $max_seconds No new download starts after this.
 * @return array
 */
function syn_sync_import_media( $file, $max_seconds = 10 ) {
	$start   = microtime( true );
	$payload = syn_sync_read_payload( $file );

	if ( isset( $payload['error'] ) ) {
		return $payload;
	}

	$report = array( 'downloaded' => array(), 'failed' => array(), 'remaining' => 0 );

	foreach ( (array) $payload['attachments'] as $att ) {
		$probe = syn_sync_attachment( $att, true );

		if ( 0 !== strpos( $probe['action'], 'WOULD DOWNLOAD' ) ) {
			continue;
		}

		if ( microtime( true ) - $start > $max_seconds ) {
			$report['remaining']++;
			continue;
		}

		$result = syn_sync_attachment( $att, false );

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
 * Snapshot of everything the live run is about to change.
 *
 * @param array $payload The payload.
 * @param array $plan    Planned targets, index-aligned with $payload['posts'].
 * @return array
 */
function syn_sync_snapshot( array $payload, array $plan ) {
	$snap = array(
		'taken'     => gmdate( 'c' ),
		'posts'     => array(),
		'records'   => get_option( 'syn_records' ),
		'redirects' => array(
			'base'  => get_option( 'wpseo-premium-redirects-base' ),
			'plain' => get_option( 'wpseo-premium-redirects-export-plain' ),
			'regex' => get_option( 'wpseo-premium-redirects-export-regex' ),
		),
		'menu'      => array(),
		'options'   => array(
			'page_on_front'   => get_option( 'page_on_front' ),
			'page_for_posts'  => get_option( 'page_for_posts' ),
			'theme_mods'      => get_option( 'theme_mods_' . SYN_SYNC_THEME ),
			'wpseo_social'    => get_option( 'wpseo_social' ),
			'wpforms_settings' => get_option( 'wpforms_settings' ),
		),
		'forms'     => array(),
	);

	foreach ( $plan as $i => $target ) {
		if ( ! $target['id'] ) {
			continue;
		}
		$post = get_post( $target['id'] );
		if ( ! $post ) {
			continue;
		}
		$meta = array();
		foreach ( get_post_meta( $post->ID ) as $key => $values ) {
			if ( 0 === strpos( $key, '_syn_' ) || 0 === strpos( $key, '_yoast_wpseo_' ) || in_array( $key, array( '_thumbnail_id', '_wp_page_template' ), true ) ) {
				$meta[ $key ] = $values[0];
			}
		}
		$snap['posts'][ $post->ID ] = array(
			'post'  => array(
				'post_title'   => $post->post_title,
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
				'post_status'  => $post->post_status,
				'post_name'    => $post->post_name,
				'post_parent'  => $post->post_parent,
				'menu_order'   => $post->menu_order,
			),
			'meta'  => $meta,
			'terms' => array(
				'category'         => wp_get_object_terms( $post->ID, 'category', array( 'fields' => 'slugs' ) ),
				'syn_case_service' => taxonomy_exists( 'syn_case_service' ) ? wp_get_object_terms( $post->ID, 'syn_case_service', array( 'fields' => 'slugs' ) ) : array(),
			),
		);
	}

	$menu = wp_get_nav_menu_object( 'Main Menu' );
	if ( $menu ) {
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
			$snap['menu'][] = array(
				'id'        => $item->ID,
				'parent'    => $item->menu_item_parent,
				'order'     => $item->menu_order,
				'title'     => $item->title,
				'type'      => $item->type,
				'object'    => $item->object,
				'object_id' => $item->object_id,
				'url'       => $item->url,
				'classes'   => $item->classes,
			);
		}
	}

	foreach ( (array) ( $payload['forms'] ?? array() ) as $form_id => $form ) {
		$snap['forms'][ $form_id ] = get_post_field( 'post_content', (int) $form_id, 'raw' );
	}

	return $snap;
}

/**
 * Applies the whole payload.
 *
 * @param string $file    Payload path.
 * @param bool   $dry_run Report only.
 * @return array
 */
function syn_sync_import( $file, $dry_run = true ) {
	$report  = array( 'mode' => $dry_run ? 'DRY RUN — nothing written' : 'LIVE' );
	$payload = syn_sync_read_payload( $file );

	if ( isset( $payload['error'] ) ) {
		return $payload;
	}

	$report['generated'] = $payload['generated'];

	// --- 1. plan, read-only ------------------------------------------------
	$map     = array();
	$missing = array();

	foreach ( (array) $payload['attachments'] as $att ) {
		$result = syn_sync_attachment( $att, true );
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
		$plan[ $i ] = syn_sync_plan_target( $p );
		if ( '' !== $plan[ $i ]['conflict'] ) {
			$conflicts[ $p['slug'] ] = $plan[ $i ]['conflict'];
		}
	}

	if ( $conflicts ) {
		$report['CONFLICTS'] = $conflicts;
	}

	if ( ! $dry_run && ( $missing || $conflicts ) ) {
		$report['error'] = 'Refused before writing anything: ' . count( $missing ) . ' images missing, ' . count( $conflicts ) . ' URL conflicts.';
		return $report;
	}

	// --- 2. snapshot, then taxonomy ---------------------------------------
	if ( ! $dry_run ) {
		update_option( SYN_SYNC_BACKUP, syn_sync_snapshot( $payload, $plan ), false );
		$report['snapshot'] = SYN_SYNC_BACKUP;
	}

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

	if ( ! $dry_run ) {
		kses_remove_filters();
	}

	// --- 3. posts, pass one ------------------------------------------------
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

		if ( null !== $p['content'] ) {
			$data['post_content']        = $p['content'];
			$report['content_written'][] = $p['slug'];
		}

		if ( $existing ) {
			$id = $existing;
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
				$data['post_date'] = $p['date'] ?? current_time( 'mysql' );
				$id                = (int) wp_insert_post( wp_slash( $data ) );
			}
		}

		if ( $id ) {
			$slug_to_id[ $p['slug'] ] = $id;
		}

		if ( $dry_run || ! $id ) {
			continue;
		}

		// Our fields and Yoast's replace staging's set outright: a key staging
		// holds that production no longer does is stale, not extra.
		foreach ( get_post_meta( $id ) as $key => $values ) {
			$ours = 0 === strpos( $key, '_syn_' ) && false === strpos( $key, 'backup' );
			$seo  = 0 === strpos( $key, '_yoast_wpseo_' ) && ! in_array( $key, array( '_yoast_wpseo_meta-robots-noindex', '_yoast_wpseo_meta-robots-nofollow' ), true );
			if ( ( $ours || $seo ) && ! array_key_exists( $key, (array) $p['meta'] ) ) {
				delete_post_meta( $id, $key );
				$report['meta_removed'][] = $p['slug'] . ' ' . $key;
			}
		}

		foreach ( (array) $p['meta'] as $key => $value ) {
			update_post_meta( $id, $key, wp_slash( syn_sync_remap( $value, $map ) ) );
		}

		if ( ! empty( $p['terms']['syn_case_service'] ) ) {
			$set = wp_set_object_terms( $id, $p['terms']['syn_case_service'], 'syn_case_service', false );
			if ( is_wp_error( $set ) ) {
				$report['term_failures'][ $p['slug'] ] = $set->get_error_message();
			}
		}
		if ( 'post' === get_post_type( $id ) && isset( $p['terms']['category'] ) ) {
			$set = wp_set_object_terms( $id, (array) $p['terms']['category'], 'category', false );
			if ( is_wp_error( $set ) ) {
				$report['term_failures'][ $p['slug'] ] = $set->get_error_message();
			}
		}
	}

	// --- 4. forms ----------------------------------------------------------
	foreach ( (array) ( $payload['forms'] ?? array() ) as $form_id => $form ) {
		$form_id = (int) $form_id;

		if ( 'wpforms' !== get_post_type( $form_id ) ) {
			$report['forms'][ $form_id ] = 'NOT ON STAGING';
			continue;
		}

		$report['forms'][ $form_id ] = $dry_run ? 'WOULD REPLACE' : 'replaced';

		if ( ! $dry_run ) {
			wp_update_post( wp_slash( array( 'ID' => $form_id, 'post_content' => $form['content'] ) ) );
		}
	}

	if ( ! $dry_run ) {
		kses_init();
	}

	// --- 5. parents --------------------------------------------------------
	if ( ! $dry_run ) {
		foreach ( (array) $payload['posts'] as $p ) {
			if ( '' === $p['parent_slug'] || ! isset( $slug_to_id[ $p['slug'] ] ) ) {
				continue;
			}
			$parent = $slug_to_id[ $p['parent_slug'] ] ?? syn_sync_find_by_slug( $p['parent_slug'], 'page' );
			if ( $parent ) {
				wp_update_post( array( 'ID' => $slug_to_id[ $p['slug'] ], 'post_parent' => $parent ) );
			}
		}
	}

	// --- 6. records --------------------------------------------------------
	$report['records'] = $dry_run
		? 'WOULD WRITE ' . count( (array) $payload['records'] ) . ' records'
		: ( update_option( 'syn_records', syn_sync_remap( $payload['records'], $map ) ) ? 'written' : 'unchanged' );

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

	$served = array( 'plain' => array(), 'regex' => array() );
	foreach ( $by_origin as $origin => $r ) {
		$format                       = 'regex' === ( $r['format'] ?? 'plain' ) ? 'regex' : 'plain';
		$served[ $format ][ $origin ] = array( 'url' => $r['url'], 'type' => (int) $r['type'] );
	}

	$report['redirects'] = array(
		'staging_had'  => count( $existing_redirects ),
		'payload_has'  => count( (array) $payload['redirects'] ),
		'added'        => $added,
		'retargeted'   => $changed,
		'total_after'  => count( $by_origin ),
	);

	if ( ! $dry_run ) {
		update_option( 'wpseo-premium-redirects-base', array_values( $by_origin ) );
		update_option( 'wpseo-premium-redirects-export-plain', $served['plain'] );
		update_option( 'wpseo-premium-redirects-export-regex', $served['regex'] );
	}

	// --- 8. menu -----------------------------------------------------------
	$menu           = wp_get_nav_menu_object( 'Main Menu' );
	$report['menu'] = array( 'items_in_payload' => count( (array) $payload['menu'] ) );

	if ( ! $dry_run ) {
		if ( ! $menu ) {
			$menu_id = wp_create_nav_menu( 'Main Menu' );
			$menu    = wp_get_nav_menu_object( $menu_id );
		}

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
				$target = syn_sync_find_by_slug( $item['object_slug'], $item['object'] );
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

	// --- 9. front page, posts page ----------------------------------------
	$home = $slug_to_id['homepage-rebuild'] ?? syn_sync_find_by_slug( 'homepage-rebuild', 'page' );
	$report['front_page'] = array( 'currently' => (int) get_option( 'page_on_front' ), 'should_be' => $home );
	if ( ! $dry_run && $home ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home );
	}

	$blog_slug = (string) ( $payload['reading']['page_for_posts'] ?? '' );
	$blog      = '' !== $blog_slug ? ( $slug_to_id[ $blog_slug ] ?? syn_sync_find_by_slug( $blog_slug, 'page' ) ) : 0;
	$report['posts_page'] = array( 'currently' => (int) get_option( 'page_for_posts' ), 'should_be' => $blog );
	if ( ! $dry_run && $blog ) {
		update_option( 'page_for_posts', $blog );
	}

	// --- 10. theme mods and the few options that are content ---------------
	$logo_src  = (int) ( $payload['theme_mods']['custom_logo'] ?? 0 );
	$logo      = $logo_src && isset( $map[ $logo_src ] ) ? (int) $map[ $logo_src ] : 0;
	$menu_term = $menu ? (int) $menu->term_id : 0;
	$share_src = (int) ( $payload['options']['wpseo_social_og_default_image_id'] ?? 0 );
	$share     = $share_src && isset( $map[ $share_src ] ) ? (int) $map[ $share_src ] : 0;

	$report['theme_mods'] = array( 'custom_logo' => $logo, 'primary_menu' => $menu_term );
	$report['options']    = array( 'og_default_image_id' => $share, 'wpforms_disable_css' => $payload['options']['wpforms_disable_css'] ?? null );

	if ( ! $dry_run ) {
		$mods = get_option( 'theme_mods_' . SYN_SYNC_THEME );
		$mods = is_array( $mods ) ? $mods : array();
		if ( $logo ) {
			$mods['custom_logo'] = $logo;
		}
		if ( $menu_term ) {
			$mods['nav_menu_locations'] = array_merge( (array) ( $mods['nav_menu_locations'] ?? array() ), array( 'primary' => $menu_term ) );
		}
		update_option( 'theme_mods_' . SYN_SYNC_THEME, $mods );

		if ( $share ) {
			$social = (array) get_option( 'wpseo_social', array() );
			$social['og_default_image_id'] = $share;
			$social['og_default_image']    = wp_get_attachment_url( $share );
			update_option( 'wpseo_social', $social );
		}

		if ( null !== ( $payload['options']['wpforms_disable_css'] ?? null ) ) {
			$wpf = (array) get_option( 'wpforms_settings', array() );
			$wpf['disable-css'] = $payload['options']['wpforms_disable_css'];
			update_option( 'wpforms_settings', $wpf );
		}

		foreach ( array( 'syn_case_service_children', 'syn_linkedin_partner_id' ) as $opt ) {
			if ( array_key_exists( $opt, (array) $payload['options'] ) && null !== $payload['options'][ $opt ] && false !== $payload['options'][ $opt ] ) {
				update_option( $opt, $payload['options'][ $opt ] );
			}
		}
	}

	// --- 11. retire pages that now redirect --------------------------------
	$keep_published = array( 'connect' );
	$written        = array_map( 'intval', array_values( $slug_to_id ) );

	foreach ( $by_origin as $origin => $r ) {
		if ( 'regex' === ( $r['format'] ?? 'plain' ) ) {
			continue;
		}
		$path = trim( (string) $origin, '/' );
		if ( '' === $path || false !== strpos( $path, '?' ) ) {
			continue;
		}
		$obj = get_page_by_path( $path, OBJECT, array( 'page', 'post' ) );
		if ( ! $obj || 'publish' !== $obj->post_status ) {
			continue;
		}
		$protected = in_array( $obj->post_name, $keep_published, true )
			|| in_array( (int) $obj->ID, $written, true )
			|| (int) $obj->ID === (int) $home
			|| (int) $obj->ID === (int) $blog;
		if ( $protected ) {
			continue;
		}
		$report['retired'][] = ( $dry_run ? 'WOULD DRAFT' : 'drafted' ) . ' /' . $path . '/ (id ' . $obj->ID . ') -> 301 /' . ltrim( (string) $r['url'], '/' );
		if ( ! $dry_run ) {
			wp_update_post( array( 'ID' => (int) $obj->ID, 'post_status' => 'draft' ) );
		}
	}

	return $report;
}

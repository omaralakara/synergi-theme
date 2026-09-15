<?php
/**
 * Content sync — PRODUCTION side (export), production → staging.
 *
 * The reverse of the Stage 8 launch transfer (tools/transfer-export.php), for
 * the day staging has to be brought back level with the live site before new
 * work starts on it. First used 15 Sep 2026, before the Arabic phase.
 *
 * Runs on production through Novamira execute-php and READS ONLY. It writes
 * nothing to production's database or filesystem: the payload is built in
 * memory and PUT straight to a Novamira upload link on staging, so it never
 * touches production's disk and never passes through a chat context.
 *
 * Pair: tools/sync-import.php runs the other half on staging.
 *
 * What travels, matched by slug on the far side exactly as at launch:
 *   - every published page, every page or case study that carries a live
 *     _syn_ field (published or draft), and every published blog post;
 *   - their _syn_ fields, Yoast meta, template and featured image;
 *   - post_content for pages that render from it (a templated page renders
 *     from fields, so its body stays whatever staging holds);
 *   - every attachment those fields reference, by URL, for staging to match by
 *     filename or download;
 *   - the Main Menu, the case-study terms, the nine site records, the redirect
 *     table, the contact form, the front/posts page and the logo.
 *
 * Usage from execute-php on production:
 *   $GLOBALS['syn_sync_upload'] = array( 'url' => ..., 'token' => ..., 'header' => ... );
 *   return include '/path/to/sync-export.php';
 *
 * @package SynergiTools
 */

defined( 'ABSPATH' ) || exit;

/** Where the payload is going. Every URL in carried copy is rewritten to this. */
const SYN_SYNC_TARGET = 'https://staging.synergi.ae';

/** Meta keys that look live but are archival snapshots (see transfer-export.php). */
const SYN_SYNC_SKIP_KEYS = array( '_syn_content_before_posts_page' );

/**
 * Yoast keys that never travel. Staging runs under a deliberate noindex and
 * keeps its own flags; production carries none, and this keeps it that way in
 * both directions whatever the carry test says.
 */
const SYN_SYNC_NEVER_KEYS = array(
	'_yoast_wpseo_meta-robots-noindex',
	'_yoast_wpseo_meta-robots-nofollow',
);

/** WPForms forms the templates embed by ID, carried whole. */
const SYN_SYNC_FORMS = array( 7560 );

/**
 * The content objects to carry.
 *
 * @return int[] Post IDs.
 */
function syn_sync_source_ids() {
	global $wpdb;

	$ids = array_map(
		'intval',
		$wpdb->get_col(
			"SELECT DISTINCT p.ID
			   FROM {$wpdb->posts} p
			   LEFT JOIN {$wpdb->postmeta} m
			     ON m.post_id = p.ID AND m.meta_key LIKE '\\_syn\\_%' AND m.meta_key NOT LIKE '%backup%'
			  WHERE ( p.post_type = 'page' AND p.post_status = 'publish' )
			     OR ( p.post_type IN ( 'page', 'syn_case_study' ) AND p.post_status IN ( 'publish', 'draft' ) AND m.meta_id IS NOT NULL )
			     OR ( p.post_type = 'post' AND p.post_status = 'publish' )
			  ORDER BY p.ID"
		)
	);

	return array_values( array_unique( $ids ) );
}

/**
 * Every attachment ID referenced by the content being carried.
 *
 * @param int[] $post_ids Posts whose meta should be scanned.
 * @return int[] Unique attachment IDs, ascending.
 */
function syn_sync_referenced_attachments( array $post_ids ) {
	$found = array();

	$collect = static function ( $value ) use ( &$collect, &$found ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				$collect( $item );
			}
			return;
		}

		if ( is_numeric( $value ) ) {
			$id = (int) $value;
			if ( $id > 100 && 'attachment' === get_post_type( $id ) ) {
				$found[ $id ] = $id;
			}
			return;
		}

		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				$collect( $decoded );
			}
		}
	};

	foreach ( $post_ids as $post_id ) {
		foreach ( get_post_meta( $post_id ) as $key => $values ) {
			$ours  = 0 === strpos( $key, '_syn_' ) && false === strpos( $key, 'backup' );
			$yoast = '_yoast_wpseo_opengraph-image-id' === $key;
			if ( ! $ours && ! $yoast ) {
				continue;
			}
			$collect( $values );
		}

		$thumb = (int) get_post_thumbnail_id( $post_id );
		if ( $thumb ) {
			$found[ $thumb ] = $thumb;
		}
	}

	$collect( get_option( 'syn_records', array() ) );

	$logo = (int) get_theme_mod( 'custom_logo' );
	if ( $logo ) {
		$found[ $logo ] = $logo;
	}

	$share = (int) ( get_option( 'wpseo_social' )['og_default_image_id'] ?? 0 );
	if ( $share ) {
		$found[ $share ] = $share;
	}

	ksort( $found );

	return array_values( $found );
}

/**
 * Builds the payload and PUTs it to the staging upload link.
 *
 * @return array Summary.
 */
function syn_sync_export() {
	$source = home_url();
	$ids    = syn_sync_source_ids();
	$upload = (array) ( $GLOBALS['syn_sync_upload'] ?? array() );

	if ( empty( $upload['url'] ) || empty( $upload['token'] ) ) {
		return array( 'error' => 'no upload link given: set $GLOBALS[\'syn_sync_upload\'] first' );
	}

	$posts_page = (int) get_option( 'page_for_posts' );

	$payload = array(
		'generated'   => gmdate( 'c' ),
		'source'      => $source,
		'target'      => SYN_SYNC_TARGET,
		'schema'      => 'sync-1',
		'records'     => get_option( 'syn_records', array() ),
		'redirects'   => get_option( 'wpseo-premium-redirects-base', array() ),
		'posts'       => array(),
		'attachments' => array(),
		'menu'        => array(),
		'terms'       => array(),
		'reading'     => array(
			'page_for_posts' => $posts_page ? (string) get_post_field( 'post_name', $posts_page ) : '',
		),
		'theme_mods'  => array(
			'custom_logo' => (int) get_theme_mod( 'custom_logo' ),
		),
		'forms'       => array(),
		'options'     => array(
			'wpseo_social_og_default_image_id' => (int) ( get_option( 'wpseo_social' )['og_default_image_id'] ?? 0 ),
			'wpforms_disable_css'              => get_option( 'wpforms_settings' )['disable-css'] ?? null,
			'syn_case_service_children'        => get_option( 'syn_case_service_children' ),
			'syn_linkedin_partner_id'          => get_option( 'syn_linkedin_partner_id' ),
		),
	);

	$content_carried = array();
	$left_alone      = array();

	foreach ( $ids as $id ) {
		$post = get_post( $id );
		if ( ! $post ) {
			continue;
		}

		$template = (string) get_post_meta( $id, '_wp_page_template', true );
		$meta     = array();

		foreach ( get_post_meta( $id ) as $key => $values ) {
			if ( in_array( $key, SYN_SYNC_SKIP_KEYS, true ) || in_array( $key, SYN_SYNC_NEVER_KEYS, true ) ) {
				continue;
			}

			$carry = ( 0 === strpos( $key, '_syn_' ) && false === strpos( $key, 'backup' ) )
				|| 0 === strpos( $key, '_yoast_wpseo_' )
				|| '_thumbnail_id' === $key
				|| '_wp_page_template' === $key;

			if ( ! $carry ) {
				continue;
			}

			$value = $values[0];
			if ( is_string( $value ) ) {
				$value = str_replace( $source, SYN_SYNC_TARGET, $value );
			}
			$meta[ $key ] = $value;
		}

		$templated = '' !== $template && 'default' !== $template && 0 === strpos( $template, 'templates/' );
		$content   = $post->post_content;

		if ( $templated || '' === trim( $content ) ) {
			$left_alone[] = $post->post_name;
			$content      = null;
		} else {
			$content           = str_replace( $source, SYN_SYNC_TARGET, $content );
			$content_carried[] = $post->post_name;
		}

		$terms = array();
		if ( 'syn_case_study' === $post->post_type ) {
			$terms['syn_case_service'] = wp_get_object_terms( $id, 'syn_case_service', array( 'fields' => 'slugs' ) );
		}
		if ( 'post' === $post->post_type ) {
			$terms['category'] = wp_get_object_terms( $id, 'category', array( 'fields' => 'slugs' ) );
		}

		$payload['posts'][] = array(
			'id'          => $id,
			'type'        => $post->post_type,
			'status'      => $post->post_status,
			'slug'        => $post->post_name,
			'title'       => $post->post_title,
			'content'     => $content,
			'excerpt'     => $post->post_excerpt,
			'date'        => $post->post_date,
			'parent_slug' => $post->post_parent ? get_post_field( 'post_name', $post->post_parent ) : '',
			'menu_order'  => (int) $post->menu_order,
			'template'    => $template,
			'meta'        => $meta,
			'terms'       => $terms,
		);
	}

	$terms = get_terms( array( 'taxonomy' => 'syn_case_service', 'hide_empty' => false ) );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$payload['terms'][] = array(
				'slug'        => $term->slug,
				'name'        => $term->name,
				'description' => $term->description,
			);
		}
	}

	foreach ( syn_sync_referenced_attachments( $ids ) as $att_id ) {
		$file = get_attached_file( $att_id );

		$payload['attachments'][] = array(
			'id'       => $att_id,
			'url'      => wp_get_attachment_url( $att_id ),
			'filename' => $file ? basename( $file ) : '',
			'title'    => get_the_title( $att_id ),
			'alt'      => (string) get_post_meta( $att_id, '_wp_attachment_image_alt', true ),
			'caption'  => wp_get_attachment_caption( $att_id ),
			'mime'     => get_post_mime_type( $att_id ),
		);
	}

	$menu = wp_get_nav_menu_object( 'Main Menu' );
	if ( $menu ) {
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
			$object_slug = '';
			if ( 'post_type' === $item->type && $item->object_id ) {
				$object_slug = (string) get_post_field( 'post_name', (int) $item->object_id );
			}

			$payload['menu'][] = array(
				'id'          => (int) $item->ID,
				'parent'      => (int) $item->menu_item_parent,
				'order'       => (int) $item->menu_order,
				'title'       => $item->title,
				'type'        => $item->type,
				'object'      => $item->object,
				'object_slug' => $object_slug,
				'url'         => str_replace( $source, SYN_SYNC_TARGET, (string) $item->url ),
				'target'      => $item->target,
				'classes'     => array_values( array_filter( (array) $item->classes ) ),
				'attr_title'  => $item->attr_title,
				'description' => $item->description,
			);
		}
	}

	foreach ( SYN_SYNC_FORMS as $form_id ) {
		if ( 'wpforms' !== get_post_type( $form_id ) ) {
			continue;
		}
		$payload['forms'][ (string) $form_id ] = array(
			'title'   => get_the_title( $form_id ),
			'content' => str_replace( $source, SYN_SYNC_TARGET, (string) get_post_field( 'post_content', $form_id, 'raw' ) ),
		);
	}

	$json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false === $json ) {
		return array( 'error' => 'json_encode failed: ' . json_last_error_msg() );
	}

	$res = wp_remote_request(
		$upload['url'],
		array(
			'method'    => 'PUT',
			'timeout'   => 25,
			'sslverify' => true,
			'headers'   => array(
				( $upload['header'] ?? 'X-Novamira-Upload-Token' ) => $upload['token'],
				'Content-Type' => 'application/json',
			),
			'body'      => $json,
		)
	);

	$without_attachments = wp_json_encode( array_diff_key( $payload, array( 'attachments' => 1 ) ) );

	return array(
		'upload_http'        => is_wp_error( $res ) ? 'ERROR ' . $res->get_error_message() : wp_remote_retrieve_response_code( $res ),
		'upload_body'        => is_wp_error( $res ) ? '' : substr( wp_remote_retrieve_body( $res ), 0, 300 ),
		'bytes'              => strlen( $json ),
		'md5'                => md5( $json ),
		'production_urls_left' => substr_count( $without_attachments, 'https://synergi.ae' ) - 1,
		'posts'              => count( $payload['posts'] ),
		'by_type'            => array_count_values( array_column( $payload['posts'], 'type' ) ),
		'content_carried'    => $content_carried,
		'content_left_alone' => count( $left_alone ),
		'attachments'        => count( $payload['attachments'] ),
		'menu_items'         => count( $payload['menu'] ),
		'terms'              => count( $payload['terms'] ),
		'redirects'          => count( (array) $payload['redirects'] ),
		'records'            => array_keys( (array) $payload['records'] ),
		'posts_page'         => $payload['reading']['page_for_posts'],
		'custom_logo'        => $payload['theme_mods']['custom_logo'],
		'forms'              => array_keys( $payload['forms'] ),
		'options'            => $payload['options'],
	);
}

return syn_sync_export();

<?php
/**
 * Stage 8 content transfer — STAGING side (export).
 *
 * Runs on staging through Novamira execute-php. Collects everything that lives
 * only in the staging database (migration-plan.md, "What the script has to
 * carry") and writes it to a JSON file under uploads/, which the production
 * side then fetches over HTTP. The payload never passes through a chat context.
 *
 * Pair: tools/transfer-import.php runs the other half on production.
 * Read migration-plan.md before changing anything here.
 *
 * Verified against staging 9 Sep 2026: 37 posts, 96 attachments, 30 menu items,
 * 5 terms, 9 records, 61 redirects, 162 KB. The only staging.synergi.ae strings
 * left in the payload are the 96 attachment download URLs, which production
 * needs in order to fetch the files, plus the payload's own "source" stamp.
 *
 * @package SynergiTools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Where the payload is going. Every URL in carried copy is rewritten to this.
 *
 * Hard-coded rather than derived: the export runs on staging, so there is no
 * local source for production's domain, and a wrong guess ships broken links.
 */
const SYN_TRANSFER_TARGET = 'https://synergi.ae';

/**
 * Meta keys that look live but are archival snapshots.
 *
 * The Stage 5-7 backups are all named "*backup*" and filtered by substring.
 * This one is not, and the 9 Sep export caught it carrying 36 staging URLs
 * into the payload before it was excluded.
 */
const SYN_TRANSFER_SKIP_KEYS = array( '_syn_content_before_posts_page' );

/**
 * Yoast keys that must never travel, however the carry test is written.
 *
 * The carry test below takes every `_yoast_wpseo_*` key, which is right for
 * titles and descriptions and catastrophic for these two: staging runs the
 * homepage and /media/ under a deliberate page-level noindex, and the same
 * import run that writes this meta also sets the homepage as page_on_front.
 * Carrying them de-indexes the new production homepage silently, and the
 * homepage is ~66% of clicks (207 of 314 in the three months to 7 Sep).
 *
 * migration-plan.md's runbook step 9 clears these by hand afterwards. This
 * constant means the launch does not depend on somebody remembering to.
 */
const SYN_TRANSFER_NEVER_KEYS = array(
	'_yoast_wpseo_meta-robots-noindex',
	'_yoast_wpseo_meta-robots-nofollow',
);

/**
 * The seven blog posts repaired on staging on 9 September, by ID.
 *
 * Listed explicitly rather than swept, because these are the only posts whose
 * production copy is wrong. Every class attribute had been stripped from their
 * markup and their inline <style> blocks had been run through wpautop, which
 * put a literal <br /> on the end of every CSS line — so every declaration was
 * dropped — and hard-wrapped prose became 1,431 real <br> tags. Repaired on
 * staging only: 87.8 KB of stored content became 53.5 KB with zero change to
 * visible text. Production still holds the corrupted copies, and three of the
 * seven are ranking (bpo-in-syria alone: 610 impressions in three months).
 *
 * These carry their post_content, unlike the template-driven pages, because
 * for a blog post the content IS the page.
 */
const SYN_TRANSFER_REPAIRED_POSTS = array( 10398, 10362, 10124, 9975, 9927, 7892, 8946 );

/**
 * The content objects to carry, resolved by slug rather than hard-coded ID.
 *
 * Slug is the join key for the whole transfer: production already holds 13 of
 * these pages under the same slugs, so matching on slug updates those in place
 * and creates only what is genuinely new.
 *
 * @return int[] Post IDs of every page and case study carrying a live field,
 *               plus the seven repaired blog posts.
 */
function syn_transfer_source_ids() {
	global $wpdb;

	$ids = array_map(
		'intval',
		$wpdb->get_col(
			"SELECT DISTINCT p.ID
			   FROM {$wpdb->posts} p
			   JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
			  WHERE m.meta_key LIKE '\\_syn\\_%'
			    AND m.meta_key NOT LIKE '%backup%'
			    AND p.post_type IN ( 'page', 'syn_case_study' )
			    AND p.post_status IN ( 'publish', 'draft' )"
		)
	);

	// Only the ones that still exist and are still published, so a post drafted
	// between now and the launch window does not travel as a surprise.
	foreach ( SYN_TRANSFER_REPAIRED_POSTS as $post_id ) {
		if ( 'publish' === get_post_status( $post_id ) ) {
			$ids[] = (int) $post_id;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Every attachment ID referenced by the content being carried.
 *
 * Image fields store a bare ID; the repeater groups store IDs inside JSON, so
 * the JSON is decoded and walked. syn_records is walked the same way.
 *
 * @param int[] $post_ids Posts whose meta should be scanned.
 * @return int[] Unique attachment IDs, ascending.
 */
function syn_transfer_referenced_attachments( array $post_ids ) {
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
			// Attachment IDs here start in the hundreds; anything smaller is a
			// menu_order, a column count or a year, not an image.
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
			if ( 0 !== strpos( $key, '_syn_' ) || false !== strpos( $key, 'backup' ) ) {
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

	ksort( $found );

	return array_values( $found );
}

/**
 * Builds the transfer payload and writes it to uploads/syn-transfer/.
 *
 * Side effects: creates a directory and a JSON file under uploads, plus an
 * index.html and .htaccess so the directory cannot be browsed.
 *
 * @return array Summary: counts, byte size, the contamination gate, fetch URL.
 */
function syn_transfer_export() {
	$staging = home_url();
	$ids     = syn_transfer_source_ids();

	$payload = array(
		'generated'   => gmdate( 'c' ),
		'source'      => $staging,
		'target'      => SYN_TRANSFER_TARGET,
		'schema'      => 2,
		'records'     => get_option( 'syn_records', array() ),
		'redirects'   => get_option( 'wpseo-premium-redirects-base', array() ),
		'posts'       => array(),
		'attachments' => array(),
		'menu'        => array(),
		'terms'       => array(),
	);

	$dropped_content    = array();
	$suppressed_noindex = array();

	// --- content -----------------------------------------------------------
	foreach ( $ids as $id ) {
		$post = get_post( $id );
		if ( ! $post ) {
			continue;
		}

		$template = (string) get_post_meta( $id, '_wp_page_template', true );
		$meta     = array();

		foreach ( get_post_meta( $id ) as $key => $values ) {
			if ( in_array( $key, SYN_TRANSFER_SKIP_KEYS, true ) ) {
				continue;
			}

			// Checked before the carry test, not inside it, so no future edit to
			// the test can reintroduce a noindex. See SYN_TRANSFER_NEVER_KEYS.
			if ( in_array( $key, SYN_TRANSFER_NEVER_KEYS, true ) ) {
				$suppressed_noindex[] = $post->post_name;
				continue;
			}

			// Carry our own fields and Yoast's. Everything else on these posts
			// is Elementor residue or regenerable, and must not travel.
			$carry = ( 0 === strpos( $key, '_syn_' ) && false === strpos( $key, 'backup' ) )
				|| 0 === strpos( $key, '_yoast_wpseo_' )
				|| '_thumbnail_id' === $key
				|| '_wp_page_template' === $key;

			if ( ! $carry ) {
				continue;
			}

			$value = $values[0];
			if ( is_string( $value ) ) {
				$value = str_replace( $staging, SYN_TRANSFER_TARGET, $value );
			}
			$meta[ $key ] = $value;
		}

		// A page with a custom template renders from fields, never from
		// post_content. Carrying its legacy Elementor markup would overwrite
		// production's live content, which CLAUDE.md §2.9 keeps as the
		// rollback. null tells the importer to leave production's alone.
		$content = $post->post_content;
		if ( '' !== $template && '' !== trim( $content ) ) {
			$dropped_content[] = $post->post_name . ' (' . $template . ')';
			$content           = null;
		} else {
			$content = str_replace( $staging, SYN_TRANSFER_TARGET, $content );
		}

		$terms = array();
		if ( 'syn_case_study' === $post->post_type ) {
			$terms['syn_case_service'] = wp_get_object_terms( $id, 'syn_case_service', array( 'fields' => 'slugs' ) );
		}

		$payload['posts'][] = array(
			'id'          => $id,
			'type'        => $post->post_type,
			'status'      => $post->post_status,
			'slug'        => $post->post_name,
			'title'       => $post->post_title,
			'content'     => $content,
			'excerpt'     => $post->post_excerpt,
			'parent_slug' => $post->post_parent ? get_post_field( 'post_name', $post->post_parent ) : '',
			'menu_order'  => (int) $post->menu_order,
			'template'    => $template,
			'meta'        => $meta,
			'terms'       => $terms,
		);
	}

	// --- taxonomy ----------------------------------------------------------
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

	// --- attachments -------------------------------------------------------
	// Only the URL and metadata travel; production matches by filename or
	// re-downloads, minting its own ID. That is what defuses the 10480-10527
	// collision: both sites used those IDs for different images after the
	// Stage 0 clone, so an ID is never reused across the two installs.
	foreach ( syn_transfer_referenced_attachments( $ids ) as $att_id ) {
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

	// --- navigation --------------------------------------------------------
	$menu            = wp_get_nav_menu_object( 'Main Menu' );
	$menu_rewritten  = 0;

	if ( $menu ) {
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
			// Items are re-pointed by slug on the far side, for the same reason
			// posts are matched by slug: object IDs differ between installs.
			$object_slug = '';
			if ( 'post_type' === $item->type && $item->object_id ) {
				$object_slug = (string) get_post_field( 'post_name', (int) $item->object_id );
			}

			$url = (string) $item->url;
			if ( false !== strpos( $url, $staging ) ) {
				$url = str_replace( $staging, SYN_TRANSFER_TARGET, $url );
				$menu_rewritten++;
			}

			$payload['menu'][] = array(
				'id'          => (int) $item->ID,
				'parent'      => (int) $item->menu_item_parent,
				'order'       => (int) $item->menu_order,
				'title'       => $item->title,
				'type'        => $item->type,
				'object'      => $item->object,
				'object_slug' => $object_slug,
				'url'         => $url,
				'target'      => $item->target,
				'classes'     => array_values( array_filter( (array) $item->classes ) ),
				'attr_title'  => $item->attr_title,
				'description' => $item->description,
			);
		}
	}

	// --- write -------------------------------------------------------------
	$up  = wp_upload_dir();
	$dir = trailingslashit( $up['basedir'] ) . 'syn-transfer';

	if ( ! wp_mkdir_p( $dir ) ) {
		return array( 'error' => 'could not create ' . $dir );
	}

	// The payload holds no secrets, but an indexable JSON dump of the whole
	// content build is still not something to leave browsable.
	file_put_contents( $dir . '/index.html', '' );
	file_put_contents( $dir . '/.htaccess', "Options -Indexes\n" );

	$json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	if ( false === $json ) {
		return array( 'error' => 'json_encode failed: ' . json_last_error_msg() );
	}

	if ( false === file_put_contents( $dir . '/payload.json', $json ) ) {
		return array( 'error' => 'could not write payload.json' );
	}

	// Contamination gate. migration-plan.md requires this checked before every
	// run, not once. The attachment block is excluded because its URLs are the
	// download source and are supposed to point at staging.
	$without_attachments = wp_json_encode( array_diff_key( $payload, array( 'attachments' => 1 ) ) );
	$leaked              = substr_count( $without_attachments, 'staging.synergi.ae' );

	return array(
		// The payload's own "source" stamp is the one expected occurrence.
		'ok'                   => $leaked <= 1,
		'staging_urls_leaked'  => $leaked,
		// Expect exactly homepage-rebuild and media. An empty list means the
		// staging noindex flags were cleared and this gate is no longer
		// protecting anything — check staging rather than assuming.
		'noindex_suppressed'   => array_values( array_unique( $suppressed_noindex ) ),
		'repaired_posts'       => count( array_filter( $payload['posts'], static function ( $p ) { return 'post' === $p['type']; } ) ),
		'posts'                => count( $payload['posts'] ),
		'attachments'          => count( $payload['attachments'] ),
		'menu_items'           => count( $payload['menu'] ),
		'menu_urls_rewritten'  => $menu_rewritten,
		'terms'                => count( $payload['terms'] ),
		'redirects'            => count( (array) $payload['redirects'] ),
		'records'              => array_keys( (array) $payload['records'] ),
		'post_content_dropped' => $dropped_content,
		'bytes'                => strlen( $json ),
		'fetch_url'            => trailingslashit( $up['baseurl'] ) . 'syn-transfer/payload.json',
	);
}

/**
 * Deletes the payload from uploads/ once production has fetched it.
 *
 * The payload has to be publicly fetchable — that is how production gets it —
 * which means that between the export and this call, the entire unlaunched
 * content build is downloadable by anyone who guesses the path: 37 objects of
 * copy, every Yoast title and description, the 61-rule redirect table, all
 * nine site records and the menu. Verified downloadable anonymously on
 * 10 Sep 2026. Run this the moment the import reports success.
 *
 * Side effects: deletes uploads/syn-transfer/ and everything in it.
 *
 * @return array What was removed.
 */
function syn_transfer_cleanup() {
	$up      = wp_upload_dir();
	$dir     = trailingslashit( $up['basedir'] ) . 'syn-transfer';
	$removed = array();

	foreach ( array( 'payload.json', 'index.html', '.htaccess' ) as $file ) {
		if ( file_exists( $dir . '/' . $file ) && unlink( $dir . '/' . $file ) ) {
			$removed[] = $file;
		}
	}

	return array(
		'removed'       => $removed,
		'dir_removed'   => is_dir( $dir ) ? rmdir( $dir ) : true,
		'still_present' => file_exists( $dir . '/payload.json' ),
	);
}

return syn_transfer_export();

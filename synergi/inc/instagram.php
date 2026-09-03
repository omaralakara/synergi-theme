<?php
/**
 * The Instagram posts the feed plugin has already fetched, as plain data.
 *
 * Loaded by functions.php. Read by sections/instagram.php, and by nothing else.
 *
 * WHY THE THEME READS THE PLUGIN'S CACHE RATHER THAN PRINTING ITS SHORTCODE
 * (3 Sep). The band used to drop [instagram-feed] into the page and let the
 * plugin render it, which cost three things: the plugin's own grid could not be
 * made to behave like the blog and case-study carousels beside it, its
 * stylesheet and script were downloaded on every page carrying the band, and
 * instagram.css had to reach into a third party's markup to make it match the
 * rest of the site — the one override layer in the theme, and exactly what
 * CLAUDE.md §4 says not to keep.
 *
 * The plugin still owns everything that is genuinely its job: talking to
 * Instagram, refreshing the access token, and downloading each picture to
 * wp-content/uploads/sb-instagram-feed-images/ as WebP. All of that runs on its
 * own schedule (the sbi_feed_update cron) whether or not a page renders its
 * shortcode, so nothing here changes how fresh the posts are. This file only
 * reads what the plugin has already stored, and the theme draws the cards.
 *
 * That the pictures are already local is what makes this legitimate under
 * CLAUDE.md §2.6: the band makes no external request at all, where the plugin's
 * own output loads its script from the site but its lightbox and analytics from
 * elsewhere.
 *
 * THIS IS PLUGIN-INTERNAL AND IS TREATED AS SUCH. Every step is guarded and any
 * failure returns an empty array, which sections/instagram.php answers by
 * falling back to the plugin's shortcode exactly as before. If a plugin update
 * ever moves the table or the encryption class, the band quietly returns to the
 * old rendering rather than breaking (CLAUDE.md §13, fail gracefully).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/**
 * The newest Instagram posts, newest first.
 *
 * Side effects: one direct database read, cached in a transient for an hour.
 *
 * @param int $count How many to return.
 * @return array[] Each: permalink, image, alt, type (strings). Empty when the
 *                 plugin is missing, has no posts, or has changed shape.
 */
function syn_instagram_posts( $count = 9 ) {
	$count = max( 1, (int) $count );
	$key   = 'syn_instagram_posts_' . $count;
	$cached = get_transient( $key );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$posts = syn_read_instagram_cache( $count );

	/*
	 * An hour, and deliberately not longer: the plugin's own cron refreshes on
	 * roughly that cadence, so this neither serves staler posts than the plugin
	 * has nor repeats the decryption on every page view.
	 */
	set_transient( $key, $posts, HOUR_IN_SECONDS );

	return $posts;
}

/**
 * The uncached read, kept separate so the guard clauses stay legible.
 *
 * @param int $count How many to return.
 * @return array[] Posts, or empty on any failure.
 */
function syn_read_instagram_cache( $count ) {
	global $wpdb;

	// The plugin's decryption. Its absence means the plugin is gone or has been
	// restructured, and either way the theme must not guess at the format.
	if ( ! class_exists( '\InstagramFeed\SB_Instagram_Data_Encryption' ) ) {
		return array();
	}

	$table = $wpdb->prefix . 'sbi_instagram_posts';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- the plugin's own table; checking it exists before reading it, and the read below is cached in a transient.
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return array();
	}

	/*
	 * A few more than asked for, because a video or an album whose local image
	 * never downloaded is skipped below and would otherwise leave the row short.
	 */
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is built from $wpdb->prefix, the only variable is the prepared limit, and the result is cached by the caller.
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT json_data, media_id, sizes FROM {$table} ORDER BY time_stamp DESC LIMIT %d",
			$count + 6
		),
		ARRAY_A
	);

	if ( ! $rows ) {
		return array();
	}

	$encryption = new \InstagramFeed\SB_Instagram_Data_Encryption();
	$uploads    = wp_upload_dir();
	$base_dir   = trailingslashit( $uploads['basedir'] ) . 'sb-instagram-feed-images/';
	$base_url   = trailingslashit( $uploads['baseurl'] ) . 'sb-instagram-feed-images/';
	$posts      = array();

	foreach ( $rows as $row ) {
		if ( count( $posts ) >= $count ) {
			break;
		}

		$data = json_decode( (string) $encryption->decrypt( $row['json_data'] ), true );

		if ( ! is_array( $data ) || empty( $data['permalink'] ) ) {
			continue;
		}

		/*
		 * The plugin stores three widths per post as WebP, named after the
		 * media id. "full" is 640px, which is what a card this size wants. The
		 * file is checked on disk rather than assumed: a post whose image never
		 * downloaded would otherwise render a broken picture.
		 */
		$media_id = (string) $row['media_id'];
		$file     = $media_id . 'full.webp';

		if ( '' === $media_id || ! file_exists( $base_dir . $file ) ) {
			continue;
		}

		/*
		 * The caption is the only text Instagram gives us, so it becomes the
		 * card's alt text — trimmed to one readable line, because a caption can
		 * run to several paragraphs of hashtags and a screen reader would read
		 * every one of them. A post with no caption falls back to a plain
		 * description rather than an empty alt: this picture IS the content
		 * here, so it is not decorative (CLAUDE.md §8).
		 */
		$caption = isset( $data['caption'] ) ? trim( wp_strip_all_tags( (string) $data['caption'] ) ) : '';
		$caption = preg_replace( '/\s+/', ' ', $caption );
		$alt     = '' !== $caption
			? wp_trim_words( $caption, 18, '…' )
			: __( 'A post from the Synergi Instagram account', 'synergi' );

		$posts[] = array(
			'permalink' => (string) $data['permalink'],
			'image'     => $base_url . $file,
			'alt'       => $alt,
			'type'      => (string) ( $data['media_type'] ?? 'IMAGE' ),
		);
	}

	return $posts;
}

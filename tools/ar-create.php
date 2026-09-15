<?php
/**
 * Arabic content builder — runs on staging through Novamira execute-php.
 *
 * Included from the sandbox, then called with translated data. Three helpers:
 *
 *   syn_ar_page( $english_id, $title, $overrides, $content = null )
 *       Creates (or updates) the Arabic twin of an English page: same slug,
 *       same template, same images, same parent (translated), every _syn_
 *       field copied from English and then overlaid with the translated values
 *       in $overrides. Links the two in Polylang.
 *   syn_ar_records( $records )
 *       Writes the Arabic site-record store (syn_records_ar), copying every
 *       non-text column (icons, images, flags, addresses, URLs) from English.
 *   syn_ar_menu( $titles )
 *       Builds the Arabic main menu as a mirror of the English one, item by
 *       item, pointing each at the Arabic twin and assigning it to the primary
 *       location for Arabic.
 *
 * Every write goes through wp_slash(), for the reason inc/fields.php's
 * syn_write_meta() explains: update_post_meta() unslashes, and an unslashed
 * \u escape comes back as bare text. JSON is written with unescaped unicode
 * so the stored rows read as Arabic in the database as well as on the page.
 *
 * @package SynergiTools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Encodes repeater rows the way the theme reads them back.
 *
 * @param array $rows Rows.
 * @return string JSON.
 */
function syn_ar_json( array $rows ) {
	return wp_json_encode( array_values( $rows ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

/**
 * Creates or updates the Arabic twin of an English page.
 *
 * @param int         $en_id     English page ID.
 * @param string      $title     Arabic title.
 * @param array       $overrides Meta key => value. Arrays are repeater rows and
 *                               are JSON-encoded; strings are written as is.
 * @param string|null $content   Arabic post_content, or null to leave empty.
 * @param array       $extra     Optional: 'excerpt', 'slug', 'status'.
 * @return array Report.
 */
function syn_ar_page( $en_id, $title, array $overrides, $content = null, array $extra = array() ) {
	$en = get_post( $en_id );
	if ( ! $en ) {
		return array( 'error' => 'no English post ' . $en_id );
	}

	$ar_id     = (int) pll_get_post( $en_id, 'ar' );
	$parent_ar = $en->post_parent ? (int) pll_get_post( $en->post_parent, 'ar' ) : 0;

	$data = array(
		'post_type'    => $en->post_type,
		'post_title'   => $title,
		'post_name'    => $extra['slug'] ?? $en->post_name,
		'post_status'  => $extra['status'] ?? 'publish',
		'post_parent'  => $parent_ar,
		'menu_order'   => (int) $en->menu_order,
		'post_content' => null === $content ? '' : $content,
		'post_excerpt' => (string) ( $extra['excerpt'] ?? '' ),
		'post_author'  => $en->post_author,
		'post_date'    => $en->post_date,
	);

	kses_remove_filters();

	if ( $ar_id && get_post( $ar_id ) && 'trash' !== get_post_status( $ar_id ) ) {
		$data['ID'] = $ar_id;
		wp_update_post( wp_slash( $data ) );
		$action = 'updated';
	} else {
		// Polylang's slug filter needs to know the language before the insert
		// so the English slug can be reused under /ar/.
		add_filter( 'pll_inserted_post_language', static function () { return 'ar'; } );
		$ar_id  = (int) wp_insert_post( wp_slash( $data ) );
		$action = 'created';
	}

	kses_init();

	if ( ! $ar_id ) {
		return array( 'error' => 'insert failed for ' . $en->post_name );
	}

	pll_set_post_language( $ar_id, 'ar' );
	pll_save_post_translations( array( 'en' => (int) $en_id, 'ar' => $ar_id ) );

	// Polylang may have appended -2 if it decided the slug clashed; put the
	// English slug back, which Polylang's own uniqueness filter allows.
	if ( get_post_field( 'post_name', $ar_id ) !== ( $extra['slug'] ?? $en->post_name ) ) {
		global $wpdb;
		$wpdb->update( $wpdb->posts, array( 'post_name' => $extra['slug'] ?? $en->post_name ), array( 'ID' => $ar_id ) );
		clean_post_cache( $ar_id );
	}

	// Every field, image and template the English page has, then the words.
	$copied = 0;
	foreach ( get_post_meta( $en_id ) as $key => $values ) {
		$carry = ( 0 === strpos( $key, '_syn_' ) && false === strpos( $key, 'backup' ) )
			|| in_array( $key, array( '_wp_page_template', '_thumbnail_id', '_yoast_wpseo_opengraph-image', '_yoast_wpseo_opengraph-image-id' ), true );
		if ( ! $carry ) {
			continue;
		}
		update_post_meta( $ar_id, $key, wp_slash( $values[0] ) );
		$copied++;
	}

	$written = 0;
	foreach ( $overrides as $key => $value ) {
		if ( is_array( $value ) ) {
			// Repeater rows: each translated row is laid over the English row at
			// the same index, so images, addresses, emails and references travel
			// without being retyped, and only the words change.
			$english = json_decode( (string) get_post_meta( $en_id, $key, true ), true );
			$merged  = array();
			foreach ( array_values( $value ) as $i => $row ) {
				$merged[] = is_array( $english ) && isset( $english[ $i ] ) && is_array( $english[ $i ] )
					? array_merge( $english[ $i ], (array) $row )
					: (array) $row;
			}
			$value = syn_ar_json( $merged );
		}
		if ( '' === $value || null === $value ) {
			delete_post_meta( $ar_id, $key );
			continue;
		}
		update_post_meta( $ar_id, $key, wp_slash( $value ) );
		$written++;
	}

	return array(
		'action'   => $action,
		'id'       => $ar_id,
		'url'      => get_permalink( $ar_id ),
		'copied'   => $copied,
		'written'  => $written,
		'parent'   => $parent_ar,
	);
}

/**
 * Writes the Arabic site records, carrying non-text columns from English.
 *
 * @param array $records Record id => rows, with only the translated columns;
 *                       any column omitted is copied from the English row at
 *                       the same index.
 * @return array Report.
 */
function syn_ar_records( array $records ) {
	$en  = (array) get_option( 'syn_records', array() );
	$out = array();

	foreach ( $records as $id => $rows ) {
		$base = isset( $en[ $id ] ) && is_array( $en[ $id ] ) ? array_values( $en[ $id ] ) : array();
		$done = array();
		foreach ( array_values( $rows ) as $i => $row ) {
			$done[] = array_merge( isset( $base[ $i ] ) ? (array) $base[ $i ] : array(), (array) $row );
		}
		$out[ $id ] = $done;
	}

	// Records not translated fall back to English at read time (inc/records.php),
	// so only what was passed is stored.
	update_option( 'syn_records_ar', $out, false );

	return array_map( 'count', $out );
}

/**
 * Builds the Arabic main menu as a mirror of the English one.
 *
 * @param array $titles English item title => Arabic title. An item whose
 *                      English title is not in the map keeps its English title.
 * @param array $skip   English titles to leave out of the Arabic menu.
 * @return array Report.
 */
function syn_ar_menu( array $titles, array $skip = array() ) {
	$en_menu = wp_get_nav_menu_object( 'Main Menu' );
	if ( ! $en_menu ) {
		return array( 'error' => 'no English Main Menu' );
	}

	$ar_menu = wp_get_nav_menu_object( 'Main Menu Arabic' );
	if ( ! $ar_menu ) {
		$ar_id   = wp_create_nav_menu( 'Main Menu Arabic' );
		$ar_menu = wp_get_nav_menu_object( $ar_id );
	}

	foreach ( (array) wp_get_nav_menu_items( $ar_menu->term_id ) as $old ) {
		wp_delete_post( $old->ID, true );
	}

	$old_to_new = array();
	$skipped    = array();
	$made       = 0;

	foreach ( (array) wp_get_nav_menu_items( $en_menu->term_id ) as $item ) {
		$en_title = html_entity_decode( $item->title, ENT_QUOTES, 'UTF-8' );

		if ( in_array( $en_title, $skip, true ) || ( $item->menu_item_parent && ! isset( $old_to_new[ (int) $item->menu_item_parent ] ) && in_array( $item->menu_item_parent, array_keys( $skipped ), true ) ) ) {
			$skipped[ (int) $item->ID ] = $en_title;
			continue;
		}

		$args = array(
			'menu-item-title'     => $titles[ $en_title ] ?? $en_title,
			'menu-item-status'    => 'publish',
			'menu-item-position'  => (int) $item->menu_order,
			'menu-item-parent-id' => $old_to_new[ (int) $item->menu_item_parent ] ?? 0,
			'menu-item-classes'   => implode( ' ', array_filter( (array) $item->classes ) ),
			'menu-item-target'    => $item->target,
		);

		if ( 'post_type' === $item->type && $item->object_id ) {
			$target = (int) pll_get_post( (int) $item->object_id, 'ar' );
			if ( ! $target ) {
				$skipped[ (int) $item->ID ] = $en_title . ' (no Arabic page)';
				continue;
			}
			$args['menu-item-type']      = 'post_type';
			$args['menu-item-object']    = $item->object;
			$args['menu-item-object-id'] = $target;
		} else {
			$args['menu-item-type'] = 'custom';
			$args['menu-item-url']  = syn_local_url_for_language( (string) $item->url );
		}

		$new_id = wp_update_nav_menu_item( $ar_menu->term_id, 0, $args );
		if ( ! is_wp_error( $new_id ) ) {
			$old_to_new[ (int) $item->ID ] = (int) $new_id;
			$made++;
		}
	}

	// The Arabic location: Polylang's own convention for theme_mods, plus its
	// nav_menus option, so both the front end and Appearance -> Menus agree.
	$mods = (array) get_option( 'theme_mods_synergi', array() );
	$mods['nav_menu_locations'] = array_merge( (array) ( $mods['nav_menu_locations'] ?? array() ), array( 'primary___ar' => (int) $ar_menu->term_id, 'primary' => (int) $en_menu->term_id ) );
	update_option( 'theme_mods_synergi', $mods );

	$options = (array) get_option( 'polylang', array() );
	$options['nav_menus']['synergi']['primary'] = array( 'en' => (int) $en_menu->term_id, 'ar' => (int) $ar_menu->term_id );
	update_option( 'polylang', $options );

	return array( 'menu_id' => $ar_menu->term_id, 'items' => $made, 'skipped' => array_values( $skipped ) );
}

/**
 * Rewrites a custom menu URL on this site into its Arabic twin's address.
 *
 * @param string $url URL as stored on the English item.
 * @return string
 */
function syn_local_url_for_language( $url ) {
	$home = untrailingslashit( home_url() );
	if ( 0 !== strpos( $url, $home ) ) {
		return $url;
	}
	$path = trim( substr( $url, strlen( $home ) ), '/' );
	$page = $path ? get_page_by_path( $path ) : null;
	if ( $page ) {
		$ar = pll_get_post( $page->ID, 'ar' );
		if ( $ar ) {
			return get_permalink( $ar );
		}
	}
	return $url;
}

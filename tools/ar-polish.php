<?php
/**
 * Arabic copy polish — applies tools/ar-polish.json to every Arabic string.
 *
 * Two homes for the Arabic copy, one map for both:
 *
 *   1. The theme's own strings, kept in tools/ar-map.json and compiled into
 *      languages/ar.mo. Run locally:
 *        php tools/ar-polish.php map tools/ar-polish.json tools/ar-map.json
 *      then rebuild the .po/.mo with tools/po2mo.php.
 *
 *   2. The database on staging: every Arabic page's title, _syn_ fields and
 *      Yoast title/description, plus the syn_records_ar option. Upload this
 *      file and the map to the Novamira sandbox and call from execute-php:
 *        require '/path/to/ar-polish.php';
 *        return syn_polish_database( '/path/to/ar-polish.json', $dry_run );
 *
 * The map has two parts. "exact" replaces a whole string when it matches
 * exactly — the hand rewrites. "regex" is the glossary, applied afterwards to
 * every string, so a term reads the same on every page (CLAUDE.md §7a's rule
 * for facts, applied to words). Nothing is written to the database unless
 * the string actually changed, and $dry_run reports without writing.
 */

/**
 * Loads the map.
 *
 * @param string $path Path to ar-polish.json.
 * @return array{exact: array<string,string>, regex: array<int,array{0:string,1:string}>}
 */
function syn_polish_map( $path ) {
	$map = json_decode( (string) file_get_contents( $path ), true );

	if ( ! is_array( $map ) || ! isset( $map['exact'], $map['regex'] ) ) {
		throw new RuntimeException( 'ar-polish.json is not the expected shape' );
	}

	return $map;
}

/**
 * Polishes one string.
 *
 * @param string $text The Arabic string.
 * @param array  $map  From syn_polish_map().
 * @return string
 */
function syn_polish_string( $text, $map ) {
	if ( ! is_string( $text ) || '' === $text || ! preg_match( '/\p{Arabic}/u', $text ) ) {
		return $text;
	}

	$out = $text;

	if ( isset( $map['exact'][ $out ] ) ) {
		$out = $map['exact'][ $out ];
	}

	foreach ( $map['regex'] as $pair ) {
		$out = preg_replace( '/' . $pair[0] . '/u', $pair[1], $out );
	}

	return $out;
}

/**
 * Polishes every string in a nested value, returning whether anything changed.
 *
 * @param mixed $value   Array or scalar, edited in place.
 * @param array $map     From syn_polish_map().
 * @param int   $changed Incremented per changed leaf.
 * @return mixed
 */
function syn_polish_walk( $value, $map, &$changed ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $k => $v ) {
			$value[ $k ] = syn_polish_walk( $v, $map, $changed );
		}

		return $value;
	}

	if ( is_string( $value ) ) {
		$next = syn_polish_string( $value, $map );

		if ( $next !== $value ) {
			$changed++;
		}

		return $next;
	}

	return $value;
}

/**
 * Applies the map to tools/ar-map.json (values only), in place.
 *
 * @param string $map_path Path to ar-polish.json.
 * @param string $ar_map   Path to ar-map.json.
 * @return int Strings changed.
 */
function syn_polish_ar_map( $map_path, $ar_map ) {
	$map     = syn_polish_map( $map_path );
	$strings = json_decode( (string) file_get_contents( $ar_map ), true );
	$changed = 0;

	foreach ( $strings as $en => $ar ) {
		// Plural forms are comma-joined in the map; polish each form.
		$next = syn_polish_string( $ar, $map );

		if ( $next !== $ar ) {
			$strings[ $en ] = $next;
			$changed++;
		}
	}

	file_put_contents( $ar_map, json_encode( $strings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "\n" );

	return $changed;
}

/**
 * Applies the map to the Arabic pages and records on a WordPress site.
 *
 * @param string $map_path Path to ar-polish.json.
 * @param bool   $dry_run  Report only.
 * @return array Counts per page and record, and a sample of changes.
 */
function syn_polish_database( $map_path, $dry_run = true ) {
	$map    = syn_polish_map( $map_path );
	$report = array( 'dry_run' => $dry_run, 'pages' => array(), 'records' => 0, 'samples' => array() );

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'lang'           => 'ar',
		)
	);

	foreach ( $pages as $page ) {
		$count = 0;

		$title = syn_polish_string( $page->post_title, $map );

		if ( $title !== $page->post_title ) {
			$count++;

			if ( ! $dry_run ) {
				wp_update_post( array( 'ID' => $page->ID, 'post_title' => wp_slash( $title ) ) );
			}
		}

		foreach ( get_post_meta( $page->ID ) as $key => $values ) {
			$is_syn   = 0 === strpos( $key, '_syn_' );
			$is_yoast = in_array( $key, array( '_yoast_wpseo_title', '_yoast_wpseo_metadesc' ), true );

			if ( ! $is_syn && ! $is_yoast ) {
				continue;
			}

			$raw = $values[0];

			if ( '' === $raw || is_numeric( $raw ) ) {
				continue;
			}

			$decoded = json_decode( $raw, true );

			if ( is_array( $decoded ) ) {
				$leaf = 0;
				$next = syn_polish_walk( $decoded, $map, $leaf );

				if ( $leaf ) {
					$count += $leaf;

					if ( count( $report['samples'] ) < 12 ) {
						$report['samples'][] = $page->post_name . '|' . $key . ' (' . $leaf . ' leaves)';
					}

					if ( ! $dry_run ) {
						update_post_meta( $page->ID, $key, wp_slash( wp_json_encode( $next, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
					}
				}

				continue;
			}

			$next = syn_polish_string( $raw, $map );

			if ( $next !== $raw ) {
				$count++;

				if ( count( $report['samples'] ) < 12 ) {
					$report['samples'][] = $page->post_name . '|' . $key . ': ' . mb_substr( $next, 0, 60 );
				}

				if ( ! $dry_run ) {
					update_post_meta( $page->ID, $key, wp_slash( $next ) );
				}
			}
		}

		if ( $count ) {
			$report['pages'][ $page->post_name ] = $count;
		}
	}

	$records = get_option( 'syn_records_ar', array() );

	if ( is_array( $records ) ) {
		$leaf = 0;
		$next = syn_polish_walk( $records, $map, $leaf );

		$report['records'] = $leaf;

		if ( $leaf && ! $dry_run ) {
			update_option( 'syn_records_ar', $next );
		}
	}

	return $report;
}

// Command line: php tools/ar-polish.php map tools/ar-polish.json tools/ar-map.json
if ( PHP_SAPI === 'cli' && isset( $argv ) && realpath( $argv[0] ) === __FILE__ ) {
	if ( 'map' === ( $argv[1] ?? '' ) && isset( $argv[2], $argv[3] ) ) {
		echo syn_polish_ar_map( $argv[2], $argv[3] ) . " strings changed in " . $argv[3] . "\n";
	} else {
		fwrite( STDERR, "Usage: php tools/ar-polish.php map <ar-polish.json> <ar-map.json>\n" );
		exit( 1 );
	}
}

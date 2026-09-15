<?php
/**
 * Compiles a .po file into a .mo file, and extracts a .pot from the theme.
 *
 * WP-CLI is not available on the host (CLAUDE.md §12) and gettext's msgfmt is
 * not installed on the build machine, so the theme's translations are compiled
 * here. Plain PHP, no dependencies, like the rest of tools/.
 *
 *   php tools/po2mo.php pot  synergi/  synergi/languages/synergi.pot
 *   php tools/po2mo.php mo   synergi/languages/ar.po  synergi/languages/ar.mo
 *
 * The .mo writer follows the GNU gettext binary format exactly (magic,
 * revision, string counts, two offset tables, then the strings) and is verified
 * by loading the result with WordPress's own MO reader on staging.
 *
 * @package SynergiTools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$mode = $argv[1] ?? '';

if ( 'pot' === $mode ) {
	syn_tool_write_pot( $argv[2], $argv[3] );
} elseif ( 'po' === $mode ) {
	syn_tool_write_po( $argv[2], $argv[3], $argv[4] );
} elseif ( 'mo' === $mode ) {
	syn_tool_write_mo( $argv[2], $argv[3] );
} else {
	fwrite( STDERR, "usage: po2mo.php pot <theme-dir> <out.pot> | po <in.pot> <map.json> <out.po> | mo <in.po> <out.mo>\n" );
	exit( 1 );
}

/**
 * Merges a translation map into the .pot to produce a .po.
 *
 * The map is JSON: msgid => msgstr. A string with context is keyed
 * "contextmsgid"; a plural is keyed by its singular and its value is an
 * array of forms. Keeping the translations in a plain map means the Arabic
 * text is written once, by hand, and never has to be escaped by hand.
 *
 * @param string $pot  Template.
 * @param string $map  JSON map.
 * @param string $out  Output .po.
 * @return void
 */
function syn_tool_write_po( $pot, $map, $out ) {
	$entries      = syn_tool_parse_po( $pot );
	$translations = json_decode( file_get_contents( $map ), true );
	$done         = 0;
	$missing      = array();

	// A msgid that wraps across source lines carries its indentation; the map
	// is keyed on the collapsed form so the translator never has to guess it.
	$collapsed = array();
	foreach ( $translations as $k => $v ) {
		$collapsed[ preg_replace( '/\s+/', ' ', $k ) ] = $v;
	}

	$po  = "# Synergi theme — Arabic.\nmsgid \"\"\nmsgstr \"\"\n";
	$po .= "\"Project-Id-Version: Synergi\\n\"\n\"Language: ar\\n\"\n\"MIME-Version: 1.0\\n\"\n\"Content-Type: text/plain; charset=UTF-8\\n\"\n\"Content-Transfer-Encoding: 8bit\\n\"\n";
	$po .= "\"Plural-Forms: nplurals=6; plural=(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5);\\n\"\n\"X-Domain: synergi\\n\"\n\n";

	foreach ( $entries as $e ) {
		$key = ( null !== $e['ctx'] ? $e['ctx'] . "\x04" : '' ) . $e['id'];
		$str = $translations[ $key ] ?? $collapsed[ preg_replace( '/\s+/', ' ', $key ) ] ?? null;

		if ( null !== $e['ctx'] ) {
			$po .= 'msgctxt ' . syn_tool_po_quote( $e['ctx'] ) . "\n";
		}
		$po .= 'msgid ' . syn_tool_po_quote( $e['id'] ) . "\n";

		if ( null !== $e['plural'] ) {
			$po .= 'msgid_plural ' . syn_tool_po_quote( $e['plural'] ) . "\n";
			$forms = is_array( $str ) ? $str : array();
			for ( $i = 0; $i < 6; $i++ ) {
				$po .= 'msgstr[' . $i . '] ' . syn_tool_po_quote( (string) ( $forms[ $i ] ?? ( $forms[ count( $forms ) - 1 ] ?? '' ) ) ) . "\n";
			}
		} else {
			$po .= 'msgstr ' . syn_tool_po_quote( is_string( $str ) ? $str : '' ) . "\n";
		}
		$po .= "\n";

		if ( null === $str ) {
			$missing[] = $e['id'];
		} else {
			$done++;
		}
	}

	file_put_contents( $out, $po );
	echo "$done translated, " . count( $missing ) . " untranslated -> $out\n";
	file_put_contents( $out . '.untranslated.txt', implode( "\n", $missing ) );
}

/**
 * Escapes a string the way a .po file writes it.
 *
 * @param string $s Raw string.
 * @return string Quoted.
 */
function syn_tool_po_quote( $s ) {
	return '"' . str_replace( array( '\\', '"', "\n", "\t" ), array( '\\\\', '\\"', '\\n', '\\t' ), $s ) . '"';
}

/**
 * Scans every PHP file for gettext calls and writes a .pot.
 *
 * @param string $dir Theme directory.
 * @param string $out Output path.
 * @return void
 */
function syn_tool_write_pot( $dir, $out ) {
	$dir     = rtrim( $dir, '/\\' );
	$entries = array();
	$re      = '/\b(__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e|_x|esc_html_x|esc_attr_x|_n|_nx)\s*\(\s*([\'"])((?:\\\\.|(?!\2).)*)\2\s*,\s*(?:([\'"])((?:\\\\.|(?!\4).)*)\4\s*,\s*)?/s';

	$files = array();
	foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) ) as $f ) {
		if ( '.php' === substr( $f->getFilename(), -4 ) ) {
			$files[] = $f->getPathname();
		}
	}
	sort( $files );

	foreach ( $files as $file ) {
		$rel = str_replace( DIRECTORY_SEPARATOR, '/', substr( $file, strlen( $dir ) + 1 ) );
		$src = file_get_contents( $file );
		preg_match_all( $re, $src, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );

		foreach ( $m as $mm ) {
			$fn = $mm[1][0];
			$q  = $mm[2][0];
			$s  = "'" === $q ? str_replace( array( "\\'", '\\\\' ), array( "'", '\\' ), $mm[3][0] ) : stripcslashes( $mm[3][0] );

			$ctx    = in_array( $fn, array( '_x', 'esc_html_x', 'esc_attr_x' ), true ) && isset( $mm[5] ) ? $mm[5][0] : null;
			$plural = in_array( $fn, array( '_n', '_nx' ), true ) && isset( $mm[5] ) ? $mm[5][0] : null;
			$line   = substr_count( substr( $src, 0, $mm[0][1] ), "\n" ) + 1;
			$key    = ( null !== $ctx ? $ctx . "\x04" : '' ) . $s;

			$entries[ $key ]['msgid']   = $s;
			$entries[ $key ]['ctx']     = $ctx;
			$entries[ $key ]['plural']  = $plural;
			$entries[ $key ]['refs'][]  = $rel . ':' . $line;
		}
	}

	$pot  = "# Synergi theme.\nmsgid \"\"\nmsgstr \"\"\n";
	$pot .= "\"Project-Id-Version: Synergi\\n\"\n\"MIME-Version: 1.0\\n\"\n\"Content-Type: text/plain; charset=UTF-8\\n\"\n\"Content-Transfer-Encoding: 8bit\\n\"\n\"X-Domain: synergi\\n\"\n\n";

	foreach ( $entries as $e ) {
		$pot .= '#: ' . implode( ' ', $e['refs'] ) . "\n";
		if ( null !== $e['ctx'] ) {
			$pot .= 'msgctxt ' . syn_tool_po_quote( $e['ctx'] ) . "\n";
		}
		$pot .= 'msgid ' . syn_tool_po_quote( $e['msgid'] ) . "\n";
		if ( null !== $e['plural'] ) {
			$pot .= 'msgid_plural ' . syn_tool_po_quote( $e['plural'] ) . "\n";
			$pot .= "msgstr[0] \"\"\nmsgstr[1] \"\"\n\n";
		} else {
			$pot .= "msgstr \"\"\n\n";
		}
	}

	file_put_contents( $out, $pot );
	echo count( $entries ) . " strings -> $out\n";
}

/**
 * Parses a .po file into entries.
 *
 * @param string $file Path.
 * @return array<int,array{ctx:?string,id:string,plural:?string,str:string[]}>
 */
function syn_tool_parse_po( $file ) {
	$lines   = file( $file, FILE_IGNORE_NEW_LINES );
	$entries = array();
	$cur     = null;
	$field   = null;
	$index   = 0;

	$unquote = static function ( $s ) {
		$s = trim( $s );
		if ( '' === $s || '"' !== $s[0] ) {
			return '';
		}
		return stripcslashes( substr( $s, 1, -1 ) );
	};

	$flush = static function () use ( &$cur, &$entries ) {
		if ( null !== $cur && '' !== $cur['id'] ) {
			$entries[] = $cur;
		}
		$cur = null;
	};

	foreach ( $lines as $line ) {
		$line = rtrim( $line );

		if ( '' === $line ) {
			continue;
		}
		if ( '#' === $line[0] ) {
			continue;
		}

		if ( preg_match( '/^(msgctxt|msgid_plural|msgid|msgstr)(\[(\d+)\])?\s+(.*)$/', $line, $m ) ) {
			$keyword = $m[1];
			$value   = $unquote( $m[4] );

			if ( 'msgctxt' === $keyword || 'msgid' === $keyword ) {
				// A new entry starts at msgctxt, or at msgid when no context preceded it.
				if ( null !== $cur && null !== $field && ( 'msgctxt' === $keyword || 'ctx' !== $field ) ) {
					$flush();
				}
				if ( null === $cur ) {
					$cur = array( 'ctx' => null, 'id' => '', 'plural' => null, 'str' => array() );
				}
			}

			switch ( $keyword ) {
				case 'msgctxt':
					$cur['ctx'] = $value;
					$field      = 'ctx';
					break;
				case 'msgid':
					$cur['id'] = $value;
					$field     = 'id';
					break;
				case 'msgid_plural':
					$cur['plural'] = $value;
					$field         = 'plural';
					break;
				case 'msgstr':
					$index               = isset( $m[3] ) && '' !== $m[3] ? (int) $m[3] : 0;
					$cur['str'][ $index ] = $value;
					$field               = 'str';
					break;
			}
			continue;
		}

		if ( '"' === $line[0] && null !== $cur && null !== $field ) {
			$value = $unquote( $line );
			if ( 'str' === $field ) {
				$cur['str'][ $index ] .= $value;
			} else {
				$cur[ $field ] .= $value;
			}
		}
	}
	$flush();

	return $entries;
}

/**
 * Writes a .mo file from a .po file.
 *
 * @param string $po Input.
 * @param string $mo Output.
 * @return void
 */
function syn_tool_write_mo( $po, $mo ) {
	$entries = syn_tool_parse_po( $po );
	$table   = array();

	// The header entry (empty msgid) carries the plural rule and charset.
	$table[''] = "Content-Type: text/plain; charset=UTF-8\nPlural-Forms: nplurals=6; plural=(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5);\nX-Generator: tools/po2mo.php\n";

	$translated = 0;
	foreach ( $entries as $e ) {
		$strs = $e['str'];
		ksort( $strs );
		if ( '' === implode( '', $strs ) ) {
			continue;
		}
		$key = ( null !== $e['ctx'] ? $e['ctx'] . "\x04" : '' ) . $e['id'] . ( null !== $e['plural'] ? "\x00" . $e['plural'] : '' );

		$table[ $key ] = implode( "\x00", $strs );
		$translated++;
	}

	ksort( $table, SORT_STRING );

	$count   = count( $table );
	$ids     = '';
	$strs    = '';
	$id_tab  = array();
	$str_tab = array();

	foreach ( $table as $id => $str ) {
		$id_tab[]  = array( strlen( $id ), strlen( $ids ) );
		$ids      .= $id . "\x00";
		$str_tab[] = array( strlen( $str ), strlen( $strs ) );
		$strs     .= $str . "\x00";
	}

	$header_size  = 7 * 4;
	$id_tab_off   = $header_size;
	$str_tab_off  = $id_tab_off + $count * 8;
	$ids_off      = $str_tab_off + $count * 8;
	$strs_off     = $ids_off + strlen( $ids );

	$out  = pack( 'V7', 0x950412de, 0, $count, $id_tab_off, $str_tab_off, 0, $strs_off + strlen( $strs ) );
	foreach ( $id_tab as $t ) {
		$out .= pack( 'V2', $t[0], $ids_off + $t[1] );
	}
	foreach ( $str_tab as $t ) {
		$out .= pack( 'V2', $t[0], $strs_off + $t[1] );
	}
	$out .= $ids . $strs;

	file_put_contents( $mo, $out );
	echo "$translated translated of " . count( $entries ) . " entries -> $mo (" . strlen( $out ) . " bytes)\n";
}

<?php
/**
 * Synergi theme bootstrap.
 *
 * Loaded automatically by WordPress on every request, front end and admin.
 * Defines the four theme constants everything else relies on, then hands off to
 * inc/ — this file deliberately contains no logic (CLAUDE.md §4: "Thin. Only
 * require_once calls into inc/.").
 *
 * Depends on: nothing. Everything in inc/ depends on this file's constants.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme version. Bumped by hand, once per stage tag.
 * Used as the asset cache-busting fallback in inc/assets.php.
 */
define( 'SYN_VERSION', '0.3.0' );

/** Absolute filesystem path to the theme root, with trailing slash. */
define( 'SYN_DIR', trailingslashit( get_template_directory() ) );

/** Public URL of the theme root, with trailing slash. */
define( 'SYN_URI', trailingslashit( get_template_directory_uri() ) );

/*
 * Developer mode (CLAUDE.md §13). True on staging, absent/false on production.
 *
 * wp-config.php is the intended owner of this constant — set
 * define( 'SYN_DEBUG', true ); there on staging. The WP_DEBUG fallback exists so
 * a local install with debugging on gets the diagnostics without a second edit;
 * production runs with WP_DEBUG off, so it resolves to false there.
 */
defined( 'SYN_DEBUG' ) || define( 'SYN_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );

require_once SYN_DIR . 'inc/setup.php';
require_once SYN_DIR . 'inc/assets.php';
require_once SYN_DIR . 'inc/nav.php';
require_once SYN_DIR . 'inc/sections.php';

// After sections.php, because an image field falls back to that file's
// syn_attachment_id_by_slug() when an editor has not chosen a picture yet
// (CLAUDE.md §7b).
require_once SYN_DIR . 'inc/fields.php';

// After fields.php, whose repeater UI, leaf sanitisers and row shaper the
// records screen reuses. Page fields and site records are two different things
// with two different stores (CLAUDE.md §7a), which is why they are two files.
require_once SYN_DIR . 'inc/records.php';

// Which fields exist, as opposed to how a field works. Last of the three so it
// can read the records registry when it builds its choices.
require_once SYN_DIR . 'inc/service-fields.php';

// The homepage's own copy, added in Stage 6b. A sibling of service-fields.php
// and for the same reason: the engine is in fields.php, the declarations of
// what an editor may change live next to the pages they belong to.
require_once SYN_DIR . 'inc/homepage-fields.php';

// The About Us family — About Us itself, Our Leadership and Engagement Team.
// Added in Stage 6f, and a sibling of the two files above for the same reason.
require_once SYN_DIR . 'inc/about-fields.php';

// The solutions and the market pages. Each file carries BOTH its site record
// and its page fields, and registers the record through the
// "syn_register_records" action rather than by editing inc/records.php — which
// is what keeps a new page type to new files (CLAUDE.md §4, one concern per
// file; §10, one concern per commit).
require_once SYN_DIR . 'inc/solution-fields.php';
require_once SYN_DIR . 'inc/market-fields.php';
require_once SYN_DIR . 'inc/contact-fields.php';

require_once SYN_DIR . 'inc/cleanup.php';
require_once SYN_DIR . 'inc/integrations.php';

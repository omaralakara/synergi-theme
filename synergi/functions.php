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
require_once SYN_DIR . 'inc/cleanup.php';
require_once SYN_DIR . 'inc/integrations.php';

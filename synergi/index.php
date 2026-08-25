<?php
/**
 * Fallback template.
 *
 * WordPress requires this file for the theme to be valid at all, and falls back
 * to it whenever no more specific template matches. With single.php, page.php,
 * archive.php, search.php and 404.php all present, very little reaches it — the
 * one route that does in normal use is the posts page (/blog/), because the
 * theme ships no home.php.
 *
 * archive.php already renders a post listing and already special-cases
 * is_home(), so this delegates to it rather than keeping a second copy of the
 * same loop for the two to drift apart (CLAUDE.md §13).
 *
 * Loaded by: WordPress template hierarchy. Depends on: archive.php.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

get_template_part( 'archive' );

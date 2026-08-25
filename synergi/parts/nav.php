<?php
/**
 * Primary navigation.
 *
 * Included by header.php through get_template_part(). All of the logic — the
 * class names, the parent toggle button, the wide-submenu rule — lives in
 * inc/nav.php; this file exists so the header reads as a list of parts rather
 * than a function call in the middle of markup.
 *
 * Expects no $args. Styled by assets/css/parts/header.css, driven by
 * assets/js/main.js.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

syn_primary_nav();

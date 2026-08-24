/*
 * main.js — shared front-end behaviour (nav toggle and anything two or more
 * sections need). Section-specific behaviour lives in assets/js/sections/.
 *
 * Loaded by inc/assets.php as the "synergi-main" handle, deferred, in the
 * footer. Every section script depends on this handle, so helpers defined here
 * are available to them.
 *
 * Rules: vanilla JS only, no jQuery, no libraries, no build step. Debug logging
 * is gated on window.synDebug, which inc/assets.php sets from SYN_DEBUG — no
 * console output ever reaches production (CLAUDE.md §13).
 *
 * Stage 1: deliberately empty. The nav behaviour arrives with the header in
 * Stage 3.
 */

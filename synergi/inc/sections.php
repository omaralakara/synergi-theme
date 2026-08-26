<?php
/**
 * The section registry and loader.
 *
 * Loaded by functions.php. A section is three files that share one name:
 *   sections/NAME.php            markup
 *   assets/css/sections/NAME.css styling
 *   assets/js/sections/NAME.js   behaviour, optional
 *
 * A template declares the sections it will render with syn_use_sections(), then
 * renders each one with syn_section(). inc/assets.php reads the declaration
 * through the "syn_page_sections" filter and enqueues only those files, which
 * is what makes CLAUDE.md §6's conditional loading real rather than aspirational.
 *
 * Why declaring is separate from rendering: assets are enqueued during
 * wp_head(), which runs inside get_header() — before any section has rendered.
 * A section that registered itself at render time would always be one request
 * too late. Declaring at the top of the template, before get_header(), is what
 * gets the list there in time.
 *
 * The two lists are cross-checked: rendering an undeclared section, or
 * declaring one that never renders, both print a comment under SYN_DEBUG. That
 * is the failure this split would otherwise hide.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads or appends to the list of sections this request will render.
 *
 * Same shape as syn_asset_debug_note() in inc/assets.php: pass a value to
 * write, pass nothing to read.
 *
 * @param string[]|null $sections Slugs to add, or null to read the list.
 * @return string[] Every slug declared so far, in declaration order.
 */
function syn_declared_sections( $sections = null ) {
	static $declared = array();

	if ( null !== $sections ) {
		foreach ( (array) $sections as $slug ) {
			$slug = sanitize_key( $slug );

			if ( '' !== $slug && ! in_array( $slug, $declared, true ) ) {
				$declared[] = $slug;
			}
		}
	}

	return $declared;
}

/**
 * Records which sections actually rendered, for the SYN_DEBUG cross-check.
 *
 * @param string|null $slug Slug to record, or null to read the list.
 * @return string[] Slugs rendered so far.
 */
function syn_rendered_sections( $slug = null ) {
	static $rendered = array();

	if ( null !== $slug && ! in_array( $slug, $rendered, true ) ) {
		$rendered[] = $slug;
	}

	return $rendered;
}

/**
 * Declares the sections a template is about to render.
 *
 * MUST be called before get_header(), or the assets will not be enqueued.
 *
 * @param string[] $sections Section slugs, in the order they will render.
 * @return void
 */
function syn_use_sections( $sections ) {
	if ( did_action( 'wp_enqueue_scripts' ) ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-sections: declared too late, after wp_enqueue_scripts had already run. Move syn_use_sections() above get_header(). -->\n";
		}

		// Not returned early: the sections still render, they just render
		// unstyled, which is a far louder signal than silently doing nothing.
	}

	syn_declared_sections( $sections );
}

add_filter( 'syn_page_sections', 'syn_filter_page_sections' );
/**
 * Hands the declared list to inc/assets.php.
 *
 * @param string[] $sections Sections from earlier filters.
 * @return string[] Sections with this request's declarations appended.
 */
function syn_filter_page_sections( $sections ) {
	return array_values( array_unique( array_merge( (array) $sections, syn_declared_sections() ) ) );
}

/**
 * The contact page URL, or the home page if it has been renamed.
 *
 * Several sections link to "start a conversation". Resolving through
 * get_permalink() rather than writing the path out keeps CLAUDE.md §12's
 * no-hard-coded-domain rule and means a renamed page cannot leave a dead link
 * in the middle of the homepage.
 *
 * @return string
 */
function syn_contact_url() {
	static $url = null;

	if ( null !== $url ) {
		return $url;
	}

	$page = get_page_by_path( 'contact-us' );
	$url  = $page && 'publish' === $page->post_status ? get_permalink( $page ) : home_url( '/' );

	return $url;
}

/**
 * Renders one section.
 *
 * Passes $args to the partial as $args, exactly as get_template_part() does, so
 * a section reads its inputs the same way a part does.
 *
 * Side effects: echoes the section's markup wrapped in the HTML comments
 * CLAUDE.md §13 requires, so view-source shows which partial built what.
 *
 * @param string $slug Section name — the shared filename, without extension.
 * @param array  $args Optional. Arguments for the section partial.
 * @return void
 */
function syn_section( $slug, $args = array() ) {
	$slug = sanitize_key( $slug );
	$file = 'sections/' . $slug . '.php';

	if ( ! file_exists( SYN_DIR . $file ) ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-section MISSING: " . esc_html( $file ) . " -->\n";
		}

		return;
	}

	if ( SYN_DEBUG && ! in_array( $slug, syn_declared_sections(), true ) ) {
		echo "\n<!-- syn-section: \"" . esc_html( $slug ) . "\" rendered but never declared, so its CSS and JS were not enqueued. Add it to syn_use_sections(). -->\n";
	}

	syn_rendered_sections( $slug );

	echo "\n<!-- syn-section: " . esc_html( $slug ) . " -->\n";
	get_template_part( 'sections/' . $slug, null, $args );
	echo "\n<!-- /syn-section: " . esc_html( $slug ) . " -->\n";
}

add_action( 'wp_footer', 'syn_print_section_debug_comment', 999 );
/**
 * Reports any mismatch between what was declared and what rendered.
 *
 * Side effects: echoes an HTML comment. Outputs nothing on production.
 *
 * @return void
 */
function syn_print_section_debug_comment() {
	if ( ! SYN_DEBUG ) {
		return;
	}

	$declared = syn_declared_sections();
	$rendered = syn_rendered_sections();

	echo "\n<!-- syn-sections\n";
	echo '     declared: ' . esc_html( $declared ? implode( ', ', $declared ) : 'none' ) . "\n";
	echo '     rendered: ' . esc_html( $rendered ? implode( ', ', $rendered ) : 'none' ) . "\n";

	$unused = array_diff( $declared, $rendered );

	if ( $unused ) {
		echo '     DECLARED BUT NEVER RENDERED (their CSS loaded for nothing): ' . esc_html( implode( ', ', $unused ) ) . "\n";
	}

	echo "-->\n";
}

/**
 * Prints one of the theme's own SVG icons inline.
 *
 * Inline rather than <img src>: the six service icons together are under 1.5 KB,
 * so inlining costs less than six HTTP requests (CLAUDE.md §6, requests budget),
 * and an inline SVG inherits currentColor — which is what lets the same file be
 * cyan on a light surface and white on a dark card without the design source's
 * `filter: brightness(0) invert(1)` trick.
 *
 * $slug is checked against an explicit list rather than sanitised into a path.
 * A whitelist cannot be walked out of, which is what CLAUDE.md §5's "no
 * user-controlled file paths" is protecting against; sanitize_key() alone is a
 * filter, not a guarantee.
 *
 * Side effects: echoes markup. Reads each file once per request.
 *
 * @param string $slug  Icon name, matching a file in assets/icons/.
 * @param string $class Optional. Class for the wrapping <span>.
 * @return void
 */
function syn_inline_icon( $slug, $class = '' ) {
	static $cache = array();

	$allowed = array(
		'accounting',
		'human-resources',
		'marketing',
		'procurement',
		'project-management',
		'technology-ai',
	);

	if ( ! in_array( $slug, $allowed, true ) ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-icon: \"" . esc_html( $slug ) . "\" is not in the allowed icon list -->\n";
		}

		return;
	}

	if ( ! isset( $cache[ $slug ] ) ) {
		$file = SYN_DIR . 'assets/icons/' . $slug . '.svg';

		if ( ! file_exists( $file ) ) {
			if ( SYN_DEBUG ) {
				echo "\n<!-- syn-icon MISSING: assets/icons/" . esc_html( $slug ) . ".svg -->\n";
			}

			return;
		}

		$cache[ $slug ] = trim( (string) file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a theme file from a fixed whitelist, not a remote request.
	}

	/*
	 * The icons are decorative next to a visible service name, so they are
	 * hidden from assistive technology rather than given a label that would
	 * make a screen reader read the service name twice (CLAUDE.md §8).
	 */
	printf(
		'<span class="%s" aria-hidden="true">%s</span>',
		esc_attr( $class ),
		wp_kses( $cache[ $slug ], syn_allowed_svg_tags() )
	);
}

/**
 * The tag and attribute whitelist wp_kses() needs to pass an inline SVG through.
 *
 * wp_kses() strips <svg> entirely by default. This lists only the shapes the
 * theme's own icons use — no <script>, no <foreignObject>, no href — so the
 * escaping still holds if an icon file is ever edited carelessly.
 *
 * @return array[] Shape expected by wp_kses().
 */
function syn_allowed_svg_tags() {
	$shape = array(
		'd'                 => true,
		'x'                 => true,
		'y'                 => true,
		'cx'                => true,
		'cy'                => true,
		'r'                 => true,
		'rx'                => true,
		'width'             => true,
		'height'            => true,
		'fill'              => true,
		'stroke'            => true,
		'stroke-width'      => true,
		'stroke-linecap'    => true,
		'stroke-linejoin'   => true,
	);

	return array(
		'svg'    => array_merge(
			$shape,
			array(
				'xmlns'   => true,
				'viewbox' => true,
				'class'   => true,
				'focusable' => true,
			)
		),
		'path'   => $shape,
		'rect'   => $shape,
		'circle' => $shape,
		'g'      => $shape,
	);
}

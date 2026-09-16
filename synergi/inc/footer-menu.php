<?php
/**
 * The footer's link columns, read from the "footer" menu location.
 *
 * Loaded by functions.php after inc/nav.php, whose job this is the other half
 * of: that file owns the header's "primary" menu, this one owns the footer's.
 * Two files rather than one because they share no code — the header is a
 * filter stack over wp_nav_menu(), this is a flat two-level walk — and because
 * a footer question should not mean reading the header's walker (CLAUDE.md §4).
 *
 * Called by footer.php, which loops whatever syn_footer_columns() returns and
 * hands each entry to parts/footer-links.php. Nothing else should call in here.
 *
 * WHY NOT wp_nav_menu(). The footer is not a list of links: it is four labelled
 * groups, and the label is a <p>, not a link, because the four headings are
 * Services / Solutions / Company / Insights and none of them is a page anyone
 * clicks. wp_nav_menu()'s markup has no room for that — a walker would have to
 * suppress the parent's <a>, break the <ul> nesting and rebuild it as four
 * sibling <nav>s, which is not a walker any more, just core's loop rewritten.
 * A direct wp_get_nav_menu_items() walk is a dozen honest lines instead.
 *
 * THE ARRAYS IN syn_footer_fallback_columns() ARE NOT DEAD CODE and must not be
 * tidied away. They are what the footer renders when no menu is assigned to the
 * location — on a fresh install, on a staging database restored without menus,
 * or the morning someone deletes the menu by accident. A site's footer going
 * blank is a worse failure than a footer that is briefly out of date, so the
 * theme keeps a known-good copy of today's footer in the code (CLAUDE.md §13,
 * fail gracefully in production).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/**
 * How many columns the footer grid has room for.
 *
 * FOUR IS NOT A PREFERENCE, IT IS THE STYLESHEET. footer.css sets
 * .syn-footer-grid to "minmax(14rem, 1.2fr) repeat(4, minmax(0, 0.8fr))" — the
 * brand column and exactly four more. A fifth top-level menu item would not
 * shrink the others to fit; it would wrap onto a second implicit row under the
 * brand and read as a mistake at every width.
 *
 * So the walk stops at four and says so in an HTML comment while SYN_DEBUG is
 * on, rather than letting an editor break the layout from Appearance → Menus
 * and have nothing anywhere explain why. Raising this number means changing
 * both breakpoints in footer.css first.
 */
defined( 'SYN_FOOTER_COLUMNS_MAX' ) || define( 'SYN_FOOTER_COLUMNS_MAX', 4 );

/**
 * The footer's link columns, from the menu if there is one and the code if not.
 *
 * Side effects: echoes an HTML comment while SYN_DEBUG is on, saying which of
 * the two sources was used.
 *
 * @return array[] One entry per column, each:
 *                   heading string Column label, plain text.
 *                   links   array[] Rows, each { label: string, url: ?string }.
 *                           A null url renders as plain text, not a link.
 */
function syn_footer_columns() {

	$columns = syn_footer_menu_columns();

	if ( $columns ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-footer: columns from the \"footer\" menu location -->\n";
		}

		return $columns;
	}

	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-footer: no usable \"footer\" menu, falling back to the arrays in inc/footer-menu.php -->\n";
	}

	return syn_footer_fallback_columns();
}

/**
 * The columns a "footer" menu describes, or an empty array when there is none.
 *
 * Each TOP-LEVEL item is a column heading and each of its children is a link in
 * that column. A top-level item is never rendered as a link, whatever URL it
 * carries: the headings are group names, and footer.css styles
 * .syn-footer-heading as a label with no hover, no focus ring and no pointer.
 * Giving an editor a heading that looks clickable and is not would be worse
 * than ignoring the URL, so the URL is ignored and the docs say so.
 *
 * Grandchildren are ignored — the footer is two levels deep and a third would
 * have nowhere to render.
 *
 * Returning an empty array is the signal to fall back, and covers three cases
 * that all mean the same thing to a visitor: no menu assigned, a menu with no
 * items, and a menu whose items are all unusable.
 *
 * Side effects: echoes HTML comments while SYN_DEBUG is on.
 *
 * @return array[] Columns in menu order, capped at SYN_FOOTER_COLUMNS_MAX.
 */
function syn_footer_menu_columns() {

	if ( ! has_nav_menu( 'footer' ) ) {
		return array();
	}

	$locations = get_nav_menu_locations();

	/*
	 * Polylang filters the locations map, so on an Arabic request this is the
	 * Arabic footer menu and its items already point at Arabic pages. That is
	 * why nothing below runs a menu URL through syn_local_url(): the menu has
	 * already answered the language question (CLAUDE.md §12, inc/i18n.php owns
	 * every language question — and here it has nothing left to decide).
	 */
	$items = wp_get_nav_menu_items( $locations['footer'] );

	if ( empty( $items ) ) {
		return array();
	}

	// Children first, so a heading can be skipped without a second pass.
	$children = array();

	foreach ( $items as $item ) {
		$parent = (int) $item->menu_item_parent;

		if ( $parent ) {
			$children[ $parent ][] = $item;
		}
	}

	$columns = array();

	foreach ( $items as $item ) {

		if ( (int) $item->menu_item_parent ) {
			continue;
		}

		$heading = trim( wp_strip_all_tags( (string) $item->title ) );

		if ( '' === $heading ) {
			continue;
		}

		$links = array();

		foreach ( $children[ $item->ID ] ?? array() as $child ) {
			$label = trim( wp_strip_all_tags( (string) $child->title ) );

			if ( '' === $label ) {
				continue;
			}

			/*
			 * A list of rows rather than a label => url map, because two items
			 * in one column may legitimately share a label ("Overview" under
			 * two headings, say) and a map would silently drop the second. A
			 * footer link that vanishes without an error is exactly the class
			 * of bug this whole guard exists to prevent.
			 */
			$links[] = array(
				'label' => $label,
				'url'   => syn_footer_menu_item_url( $child ),
			);
		}

		if ( ! $links ) {
			if ( SYN_DEBUG ) {
				echo "\n<!-- syn-footer: \"" . esc_html( $heading ) . '" has no child items, so no column was rendered for it -->' . "\n";
			}

			continue;
		}

		$columns[] = array(
			'heading' => $heading,
			'links'   => $links,
		);
	}

	if ( count( $columns ) > SYN_FOOTER_COLUMNS_MAX ) {
		if ( SYN_DEBUG ) {
			printf(
				"\n<!-- syn-footer: the menu has %d columns and footer.css has room for %d; the extra ones were dropped -->\n",
				(int) count( $columns ),
				(int) SYN_FOOTER_COLUMNS_MAX
			);
		}

		$columns = array_slice( $columns, 0, SYN_FOOTER_COLUMNS_MAX );
	}

	return $columns;
}

/**
 * Where one footer menu item points, or null when it must not be linked.
 *
 * THIS IS THE LINK-ROT GUARD, and it is the reason the hardcoded arrays were
 * safe and a menu is not. Core drops a menu item whose post has been deleted or
 * trashed, but it keeps one whose post has been moved back to DRAFT, PENDING or
 * PRIVATE — and those pages 404 for a logged-out visitor while still looking
 * perfectly fine to the editor who is viewing the site logged in. The footer is
 * on all 48 URLs, so one such page is 48 broken links that whoever caused them
 * cannot see.
 *
 * Rather than drop the item, this returns null and parts/footer-links.php
 * renders the label as plain text — the treatment already written for a page
 * that does not exist yet. The words stay in the footer, nothing 404s, and the
 * link comes back by itself the moment the page is published again.
 *
 * A custom link cannot be checked — there is no object behind it — so it is
 * taken at face value and resolved through syn_local_url(), which turns a typed
 * "/careers/" into a real address and leaves an external one alone.
 *
 * @param WP_Post $item A menu item from wp_get_nav_menu_items().
 * @return string|null The address, or null to render the label unlinked.
 */
function syn_footer_menu_item_url( $item ) {

	switch ( $item->type ) {

		case 'post_type':
			$post = get_post( (int) $item->object_id );

			if ( ! $post || 'publish' !== $post->post_status ) {
				return null;
			}

			return get_permalink( $post );

		case 'taxonomy':
			$link = get_term_link( (int) $item->object_id, (string) $item->object );

			return is_wp_error( $link ) ? null : $link;

		case 'post_type_archive':
			$link = get_post_type_archive_link( (string) $item->object );

			return $link ? $link : null;

		default:
			$url = trim( (string) $item->url );

			return '' === $url ? null : syn_local_url( $url );
	}
}

/**
 * Today's footer, written out, for when no menu is assigned.
 *
 * READ THE FILE HEADER BEFORE DELETING THIS. It is the graceful fallback, not a
 * leftover from before the menu existed.
 *
 * Written as label => path because that is the form a person can read and check
 * at a glance, and normalised into the row shape on the way out. The paths are
 * language-neutral, so syn_local_url() resolves each into the language being
 * viewed — unlike a menu, where the menu itself is per-language.
 *
 * The list is the one that shipped on 31 Aug, plus Careers (14 Sep). Its
 * ordering decisions are recorded in the commits that made them; the short
 * version is that services lead because they are what the site sells, and every
 * entry is a published page on a theme template.
 *
 * @return array[] Columns, in the shape syn_footer_columns() documents.
 */
function syn_footer_fallback_columns() {

	$columns = array(
		array(
			'heading' => __( 'Services', 'synergi' ),
			'paths'   => array(
				__( 'Human Resources', 'synergi' )    => '/our-services/human-resources/',
				__( 'Technology & AI', 'synergi' )    => '/our-services/technology-ai/',
				__( 'Accounting', 'synergi' )         => '/our-services/accounting/',
				__( 'Marketing', 'synergi' )          => '/our-services/marketing/',
				__( 'Procurement', 'synergi' )        => '/our-services/procurement/',
				__( 'Project Management', 'synergi' ) => '/our-services/project-management/',
			),
		),
		array(
			'heading' => __( 'Solutions', 'synergi' ),
			'paths'   => array(
				__( 'Shared Services', 'synergi' )         => '/our-solutions/shared-services/',
				__( 'Build-Operate-Transfer', 'synergi' )  => '/our-solutions/build-operate-transfer/',
				__( 'Systems Implementation', 'synergi' )  => '/our-solutions/systems-implementation/',
				__( 'Carve-Out & Integration', 'synergi' ) => '/our-solutions/carve-out-integration/',
				__( 'Fractional Leadership', 'synergi' )   => '/our-solutions/fractional-leadership/',
			),
		),
		array(
			'heading' => __( 'Company', 'synergi' ),
			'paths'   => array(
				__( 'About Us', 'synergi' )         => '/about-us/',
				__( 'Engagement Team', 'synergi' )  => '/engagement-team/',
				__( 'Global Locations', 'synergi' ) => '/global-locations/',
				__( 'Markets', 'synergi' )          => '/markets/',
				__( 'Careers', 'synergi' )          => '/careers/',
				__( 'Contact Us', 'synergi' )       => '/contact-us/',
			),
		),
		array(
			'heading' => __( 'Insights', 'synergi' ),
			'paths'   => array(
				__( 'Case Studies', 'synergi' )      => '/case-studies/',
				__( 'Blog', 'synergi' )              => '/blog/',
				__( 'Executive Podcast', 'synergi' ) => '/executive-podcast/',
				__( 'Media', 'synergi' )             => '/media/',
			),
		),
	);

	$out = array();

	foreach ( $columns as $column ) {
		$links = array();

		foreach ( $column['paths'] as $label => $path ) {
			$links[] = array(
				'label' => (string) $label,
				'url'   => syn_local_url( $path ),
			);
		}

		$out[] = array(
			'heading' => $column['heading'],
			'links'   => $links,
		);
	}

	return $out;
}

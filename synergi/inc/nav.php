<?php
/**
 * Primary navigation rendering.
 *
 * Loaded by functions.php. Owns everything that turns the "primary" menu into
 * the markup assets/css/parts/header.css styles and assets/js/main.js drives:
 * the class names, the <button> that replaces a parent item's link, the
 * aria-current flag, and the two-column submenu decision.
 *
 * Called by parts/nav.php. Nothing else should call into this file.
 *
 * Why a filter stack rather than a Walker subclass: every change needed here is
 * a single attribute or class on an element core already emits, and filters keep
 * each of those changes greppable on its own line. A Walker would mean copying
 * core's start_el() wholesale and re-diffing it against every WordPress release.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Child count at which a submenu switches to the two-column variant.
 *
 * Four is the point where a single 15rem column starts to run long against the
 * header: the design ships exactly two submenu widths (15rem one-column,
 * 21rem two-column) and no rule for choosing between them, so the theme picks
 * on item count rather than asking an editor to know. On the menu as it stands
 * only "Our Services" (5 children) crosses it.
 */
defined( 'SYN_SUBMENU_WIDE_MIN' ) || define( 'SYN_SUBMENU_WIDE_MIN', 4 );

/**
 * The class that turns a top-level menu item into the header's button.
 *
 * Typed by an editor into the "CSS Classes" box on Appearance -> Menus. Named
 * here rather than written out at both of its call sites, so the string an
 * editor types and the string the code looks for cannot drift apart.
 */
defined( 'SYN_NAV_CTA_CLASS' ) || define( 'SYN_NAV_CTA_CLASS', 'syn-nav-cta' );

/**
 * Renders the primary navigation, which is also the menu toggle's target.
 *
 * Outputs nothing at all when no menu is assigned to the "primary" location —
 * wp_nav_menu()'s default fallback lists every published page, which is noise
 * rather than navigation.
 *
 * Side effects: echoes markup. Registers no hooks (they are registered at file
 * scope below).
 *
 * @return void
 */
function syn_primary_nav() {

	if ( ! has_nav_menu( 'primary' ) ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-nav: no menu assigned to the \"primary\" location -->\n";
		}

		return;
	}
	?>
	<nav id="syn-primary-nav" class="syn-primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'synergi' ); ?>">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'syn-nav-list',
				'depth'          => 2,
				'fallback_cb'    => false,
			)
		);
		?>
	</nav>
	<?php
}

/**
 * Counts the direct children of every item in the primary menu.
 *
 * Read twice per request (once to mark parents, once to size their submenus) so
 * the result is memoised — wp_get_nav_menu_items() is a full query.
 *
 * @return array<int,int> Menu item ID => number of direct children.
 */
function syn_nav_child_counts() {
	static $counts = null;

	if ( null !== $counts ) {
		return $counts;
	}

	$counts    = array();
	$locations = get_nav_menu_locations();

	if ( empty( $locations['primary'] ) ) {
		return $counts;
	}

	$items = wp_get_nav_menu_items( $locations['primary'] );

	foreach ( (array) $items as $item ) {
		$parent = (int) $item->menu_item_parent;

		if ( $parent ) {
			$counts[ $parent ] = ( $counts[ $parent ] ?? 0 ) + 1;
		}
	}

	return $counts;
}

/**
 * Remembers which top-level item the walker is currently inside.
 *
 * nav_menu_submenu_css_class fires from Walker_Nav_Menu::start_lvl(), which
 * receives a depth but never the parent item — so there is no way to ask "how
 * many children does this submenu have?" from inside that filter. The walker
 * always calls start_el() on the parent before descending into its children,
 * so recording the parent on the way past is enough.
 *
 * Same shape as syn_asset_debug_note() in inc/assets.php: pass a value to
 * write, pass nothing to read.
 *
 * @param WP_Post|null $item Menu item to record, or null to read the last one.
 * @return WP_Post|null The most recently recorded top-level parent.
 */
function syn_nav_current_parent( $item = null ) {
	static $current = null;

	if ( null !== $item ) {
		$current = $item;
	}

	return $current;
}

add_filter( 'nav_menu_css_class', 'syn_nav_item_classes', 10, 4 );
/**
 * Replaces core's menu item classes with the theme's own.
 *
 * Core emits eight or more classes per item (menu-item, menu-item-type-post_type,
 * menu-item-object-page, menu-item-123, and the current-* family). None of them
 * are styled by this theme, and shipping them on every item on every page is
 * roughly 400 wasted bytes per menu — so the list is replaced rather than added
 * to. The one class kept is the current-item marker, because the submenu needs
 * it to stay open on the page it points at when JavaScript is off.
 *
 * @param string[] $classes Core's classes for this item.
 * @param WP_Post  $item    The menu item.
 * @param stdClass $args    wp_nav_menu() arguments.
 * @param int      $depth   Depth of the item, 0 for top level.
 * @return string[] The theme's classes.
 */
function syn_nav_item_classes( $classes, $item, $args, $depth ) {

	if ( 'primary' !== ( $args->theme_location ?? '' ) ) {
		return $classes;
	}

	$out = array( 0 === $depth ? 'syn-nav-list__item' : 'syn-submenu__item' );

	if ( array_intersect( array( 'current-menu-item', 'current-menu-ancestor', 'current-menu-parent' ), (array) $classes ) ) {
		$out[] = 'syn-is-current';
	}

	if ( 0 === $depth && in_array( 'menu-item-has-children', (array) $classes, true ) ) {
		$out[] = 'syn-has-submenu';
		syn_nav_current_parent( $item );
	}

	/*
	 * The one class an editor may type into the "CSS Classes" box on
	 * Appearance -> Menus and have survive this filter, which otherwise replaces
	 * core's list wholesale. It turns a top-level item into the header's button
	 * — Contact Us, as of 28 Aug.
	 *
	 * A whitelist of exactly one rather than "keep anything starting syn-",
	 * because the second is a door into the stylesheet from the menu screen and
	 * this is a link that looks like a button, not a styling field (CLAUDE.md
	 * §7c). syn_nav_link_attributes() below gives it the theme's shared button
	 * classes, so the geometry is base.css's and is not written twice.
	 */
	if ( 0 === $depth && in_array( SYN_NAV_CTA_CLASS, (array) $classes, true ) ) {
		$out[] = SYN_NAV_CTA_CLASS;
	}

	return $out;
}

add_filter( 'nav_menu_submenu_css_class', 'syn_nav_submenu_classes', 10, 3 );
/**
 * Names the submenu <ul> and picks its width variant.
 *
 * @param string[] $classes Core's classes for the submenu.
 * @param stdClass $args    wp_nav_menu() arguments.
 * @param int      $depth   Depth of the submenu.
 * @return string[] The theme's classes.
 */
function syn_nav_submenu_classes( $classes, $args, $depth ) {

	if ( 'primary' !== ( $args->theme_location ?? '' ) ) {
		return $classes;
	}

	$out    = array( 'syn-submenu' );
	$parent = syn_nav_current_parent();
	$counts = syn_nav_child_counts();

	if ( $parent && ( $counts[ $parent->ID ] ?? 0 ) >= SYN_SUBMENU_WIDE_MIN ) {
		$out[] = 'syn-submenu--wide';
	}

	return $out;
}

add_filter( 'nav_menu_link_attributes', 'syn_nav_link_attributes', 10, 4 );
/**
 * Names each menu link and marks the one for the page being viewed.
 *
 * aria-current="page" is what the design's active-item pointer keys off, and it
 * is also the only accessible way to announce "you are here" — the class core
 * adds is invisible to assistive technology.
 *
 * @param array    $atts  Link attributes.
 * @param WP_Post  $item  The menu item.
 * @param stdClass $args  wp_nav_menu() arguments.
 * @param int      $depth Depth of the item.
 * @return array Attributes with the theme's class and aria-current.
 */
function syn_nav_link_attributes( $atts, $item, $args, $depth ) {

	if ( 'primary' !== ( $args->theme_location ?? '' ) ) {
		return $atts;
	}

	$atts['class'] = 0 === $depth ? 'syn-nav-list__link' : 'syn-submenu__link';

	/*
	 * The button item borrows base.css's shared button rather than getting a
	 * second copy of its geometry in header.css. --light is the dark-surface
	 * variant, which is the right one here: the header bar is transparent over
	 * the hero and navy once scrolled, and white-on-navy outline works on both.
	 */
	if ( 0 === $depth && in_array( SYN_NAV_CTA_CLASS, (array) $item->classes, true ) ) {
		$atts['class'] .= ' syn-button syn-button--light';
	}

	if ( ! empty( $item->current ) ) {
		$atts['aria-current'] = 'page';
	}

	return $atts;
}

add_filter( 'walker_nav_menu_start_el', 'syn_nav_parent_as_toggle', 10, 4 );
/**
 * Turns a top-level item that has children into the submenu's toggle button.
 *
 * The design gives a parent item exactly one control — header.css styles
 * .syn-nav-list__link and .syn-submenu-toggle identically and leaves no room in
 * the row for both a link and a separate chevron — so a parent becomes a button
 * and stops linking to its own page.
 *
 * That is a real trade: "About Us", "Our Approach" and "Our Services" are all
 * real pages, and after this they are reachable from the footer and from
 * in-page links but not from the top level of the nav. It follows the approved
 * design, and CLAUDE.md hands final navigation structure to Stage 7 — flagged
 * there rather than decided here.
 *
 * A <button> is used rather than a link with role="button": with JavaScript off
 * the button does nothing, and header.css handles that case by leaving submenus
 * open (hover and focus-within on wide screens, permanently expanded on narrow
 * ones), so no keyboard or pointer user is ever stranded.
 *
 * @param string   $item_output The item's markup so far.
 * @param WP_Post  $item        The menu item.
 * @param int      $depth       Depth of the item.
 * @param stdClass $args        wp_nav_menu() arguments.
 * @return string The markup, with parent links replaced by a toggle button.
 */
function syn_nav_parent_as_toggle( $item_output, $item, $depth, $args ) {

	if ( 'primary' !== ( $args->theme_location ?? '' ) ) {
		return $item_output;
	}

	if ( 0 !== $depth || ! in_array( 'menu-item-has-children', (array) $item->classes, true ) ) {
		return $item_output;
	}

	$label = $item->title;

	if ( '' === trim( wp_strip_all_tags( $label ) ) ) {
		return $item_output;
	}

	ob_start();
	?>
	<button class="syn-submenu-toggle" type="button" aria-expanded="false">
		<?php echo esc_html( $label ); ?>
		<svg class="syn-submenu-toggle__chevron" viewBox="0 0 10 6" width="10" height="6" aria-hidden="true" focusable="false">
			<path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
		</svg>
	</button>
	<?php
	return ob_get_clean();
}

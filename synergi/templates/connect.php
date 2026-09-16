<?php
/**
 * Template Name: Connect (link page)
 *
 * /connect/: the link-in-bio page a printed QR code opens. One card — logo,
 * heading, one sentence, a stack of buttons — centred on the screen.
 *
 * Loaded by: the page editor's Template dropdown.
 * Depends on: inc/sections.php, inc/fields.php, inc/connect-fields.php,
 * sections/connect.php, parts/consent.php.
 *
 * NO HEADER AND NO FOOTER (asked for 16 Sep). Somebody who has just scanned a
 * code at a stand wants the four buttons, not the site's navigation, so this
 * template does not call get_header() or get_footer() and writes the document
 * shell itself. It still calls wp_head() and wp_footer(): Yoast's title and
 * its noindex, the measurement tags and the consent defaults all arrive
 * through them, and a page without them would be a page outside the site's
 * SEO and privacy rules (CLAUDE.md §8, §11). inc/assets.php skips the header
 * and footer stylesheets for this template to match.
 *
 * No skip link: with no navigation there is nothing to skip, and the card is
 * the first thing a keyboard reaches.
 *
 * The consent dialog is kept. It is not chrome — the tags still load here, so
 * the visitor still has to be asked.
 *
 * The stored post content (the old Elementor markup) is not rendered.
 * sections/connect.php owns this page's one <h1> (CLAUDE.md §8).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_id = get_the_ID();

// Before wp_head(), which is where the section's stylesheet is enqueued.
syn_use_sections( array( 'connect' ) );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<main id="main-content">
<?php
syn_section(
	'connect',
	array(
		'logo'    => syn_field_image_id( 'connect_logo', $syn_id ),
		'heading' => syn_field( 'connect_heading', $syn_id ),
		'lede'    => syn_field( 'connect_lede', $syn_id ),
		'links'   => syn_field_rows( 'connect_links', $syn_id ),
	)
);
?>
</main>

<?php get_template_part( 'parts/consent' ); ?>

<?php wp_footer(); ?>
</body>
</html>

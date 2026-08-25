<?php
/**
 * Site head and opening markup.
 *
 * Included by every template through get_header(). Opens <html>, <body> and
 * <main id="main-content">; footer.php closes all three, so the two files are a
 * matched pair — never change one without the other.
 *
 * Styled by assets/css/parts/header.css. Driven by assets/js/main.js, which
 * finds the header through [data-syn-header] and the toggle through
 * .syn-menu-toggle. The navigation itself lives in parts/nav.php.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;
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

<!-- syn-part: skip-link -->
<a class="syn-skip-link" href="#main-content"><?php esc_html_e( 'Skip to content', 'synergi' ); ?></a>
<!-- /syn-part: skip-link -->

<!-- syn-part: header -->
<header class="syn-site-header" data-syn-header>
	<div class="syn-site-header__inner">
		<?php get_template_part( 'parts/brand' ); ?>

		<?php get_template_part( 'parts/nav' ); ?>

		<?php
		/*
		 * The toggle sits after the navigation in the source so that Tab reaches
		 * the brand, then the nav, then the toggle — but it is painted before the
		 * nav panel, which is why header.css gives it the higher z-index rather
		 * than reordering the markup. It is hidden entirely when scripting is
		 * unavailable, because it would have nothing to toggle.
		 */
		?>
		<button
			class="syn-menu-toggle"
			type="button"
			aria-expanded="false"
			aria-controls="syn-primary-nav"
			data-syn-label-open="<?php esc_attr_e( 'Open navigation', 'synergi' ); ?>"
			data-syn-label-close="<?php esc_attr_e( 'Close navigation', 'synergi' ); ?>"
		>
			<span aria-hidden="true"></span>
			<span aria-hidden="true"></span>
			<span class="syn-visually-hidden" data-syn-menu-label><?php esc_html_e( 'Open navigation', 'synergi' ); ?></span>
		</button>
	</div>
</header>
<!-- /syn-part: header -->

<main id="main-content">

<?php
/**
 * Site head and opening markup.
 *
 * Included by every template through get_header(). Opens <html>, <body> and
 * <main id="main-content">; footer.php closes them, so the two files are a
 * matched pair — never change one without the other.
 *
 * Stage 1 placeholder: the real header markup (the .site-header / .nav-list
 * class contract from the design CSS) is built in Stage 3. Nothing here is
 * styled yet, by design.
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
<header class="syn-site-header">
	<div class="syn-site-header__inner">
		<p class="syn-site-header__brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
		</p>

		<?php
		// has_nav_menu() first: wp_nav_menu() falls back to listing every page
		// when no menu is assigned, which is noise on a fresh install.
		if ( has_nav_menu( 'primary' ) ) :
			?>
			<nav class="syn-site-nav" aria-label="<?php esc_attr_e( 'Primary', 'synergi' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'syn-site-nav__list',
						'depth'          => 2,
					)
				);
				?>
			</nav>
		<?php endif; ?>
	</div>
</header>
<!-- /syn-part: header -->

<main id="main-content" class="syn-main">

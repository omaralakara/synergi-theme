<?php
/**
 * Site footer and closing markup.
 *
 * Included by every template through get_footer(). Closes the <main> element
 * that header.php opened, then <body> and <html> — the two files are a matched
 * pair.
 *
 * Stage 1 placeholder: the real footer markup (the .site-footer / .footer-grid
 * class contract from the design CSS) is built in Stage 3.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;
?>
</main><!-- #main-content -->

<!-- syn-part: footer -->
<footer class="syn-site-footer">
	<div class="syn-site-footer__inner">
		<?php if ( has_nav_menu( 'footer' ) ) : ?>
			<nav class="syn-site-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'synergi' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'syn-site-footer__list',
						'depth'          => 1,
					)
				);
				?>
			</nav>
		<?php endif; ?>

		<p class="syn-site-footer__copyright">
			<?php
			printf(
				/* translators: 1: current year, 2: site name */
				esc_html__( '&copy; %1$s %2$s', 'synergi' ),
				esc_html( wp_date( 'Y' ) ),
				esc_html( get_bloginfo( 'name' ) )
			);
			?>
		</p>
	</div>
</footer>
<!-- /syn-part: footer -->

<?php wp_footer(); ?>
</body>
</html>

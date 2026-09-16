<?php
/**
 * Site footer and closing markup.
 *
 * Included by every template through get_footer(). Closes the <main> element
 * that header.php opened, then <body> and <html> — the two files are a matched
 * pair.
 *
 * Styled by assets/css/parts/footer.css. Needs no JavaScript.
 *
 * The link columns come from inc/footer-menu.php, which reads the "footer" menu
 * location and falls back to a written-out copy of today's footer when no menu
 * is assigned. That handover was promised in this docblock from 25 Aug and made
 * on 16 Sep; see docs/footer-menu.md for the menu an admin builds to reproduce
 * it. Everything else on this page — the brand blurb, the address, the four
 * social links and the bottom bar — stays here, because none of it is a menu.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;
?>
</main><!-- #main-content -->

<!-- syn-part: footer -->
<footer class="syn-site-footer">
	<div class="syn-container">

		<div class="syn-footer-grid">

			<div class="syn-footer-brand">
				<?php get_template_part( 'parts/brand' ); ?>

				<p><?php esc_html_e( 'Synergi is a boutique BPO partner delivering personalized, high-impact business solutions', 'synergi' ); ?></p>

				<?php
				/*
				 * The address is the one piece of contact detail the old footer
				 * carried, and it is a real mailbox rather than a page — so it is
				 * written out here rather than resolved from a post. It is the
				 * synergibpo.com address the business already publishes; the
				 * domain move noted in CLAUDE.md §12 does not change it.
				 */
				?>
				<a class="syn-footer-email" href="mailto:info@synergibpo.com">info@synergibpo.com</a>

				<ul class="syn-social-links">
					<?php
					// Icon paths are inline because four small glyphs cost less
					// than four sprite requests, and no icon is used anywhere else.
					$syn_social = array(
						'LinkedIn'  => array(
							'url'  => 'https://www.linkedin.com/company/synergi-ae/',
							'path' => 'M4.98 3.5a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM.5 8h4V21h-4zM8 8h3.8v1.78h.05A4.17 4.17 0 0 1 15.6 7.7c4.06 0 4.8 2.67 4.8 6.14V21h-4v-6.36c0-1.52-.03-3.47-2.12-3.47-2.12 0-2.44 1.65-2.44 3.36V21H8z',
						),
						'Instagram' => array(
							'url'  => 'https://www.instagram.com/synergi.bpo/',
							'path' => 'M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85C2.38 3.92 3.89 2.38 7.15 2.23 8.42 2.17 8.8 2.16 12 2.16zM12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.69.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.2-4.35-2.62-6.78-6.98-6.98C15.67.01 15.26 0 12 0zm0 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.4-11.85a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88z',
						),
						'Facebook'  => array(
							'url'  => 'https://www.facebook.com/profile.php?id=61553776125146',
							'path' => 'M14.5 8.5V6.8c0-.8.2-1.3 1.4-1.3H17V2.6c-.3 0-1.2-.1-2.2-.1-2.2 0-3.7 1.3-3.7 3.8v2.2H8.6v3h2.5V21h3.4v-8.5h2.5l.4-3z',
						),
						'YouTube'   => array(
							'url'  => 'https://www.youtube.com/@SynergiAE',
							'path' => 'M23.5 6.2a3 3 0 0 0-2.12-2.13C19.5 3.55 12 3.55 12 3.55s-7.5 0-9.38.52A3 3 0 0 0 .5 6.2 31.3 31.3 0 0 0 0 12a31.3 31.3 0 0 0 .5 5.8 3 3 0 0 0 2.12 2.13c1.88.52 9.38.52 9.38.52s7.5 0 9.38-.52a3 3 0 0 0 2.12-2.13A31.3 31.3 0 0 0 24 12a31.3 31.3 0 0 0-.5-5.8zM9.55 15.57V8.43L15.82 12z',
						),
					);

					foreach ( $syn_social as $syn_name => $syn_link ) :
						?>
						<li>
							<a href="<?php echo esc_url( $syn_link['url'] ); ?>" rel="noopener noreferrer" target="_blank">
								<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="<?php echo esc_attr( $syn_link['path'] ); ?>"></path></svg>
								<span class="syn-visually-hidden">
									<?php
									printf(
										/* translators: %s: social network name, e.g. LinkedIn */
										esc_html__( 'Synergi on %s', 'synergi' ),
										esc_html( $syn_name )
									);
									?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<?php
			/*
			 * The four link columns. Each is a heading plus a list and nothing
			 * else — see parts/footer-links.php for the $args it takes, and
			 * inc/footer-menu.php for where the columns come from and why a
			 * column heading is never a link.
			 */
			foreach ( syn_footer_columns() as $syn_column ) {
				get_template_part( 'parts/footer-links', null, $syn_column );
			}
			?>

		</div>

		<div class="syn-footer-bottom">
			<p>
				<?php
				printf(
					/* translators: 1: current year, 2: site name */
					esc_html__( 'Copyright © %1$s %2$s. All Rights Reserved.', 'synergi' ),
					esc_html( wp_date( 'Y' ) ),
					esc_html( get_bloginfo( 'name' ) )
				);
				?>
			</p>

			<div>
				<a href="<?php echo esc_url( syn_page_url( 'terms' ) ); ?>"><?php esc_html_e( 'Terms & Conditions', 'synergi' ); ?></a>
				<a href="<?php echo esc_url( syn_page_url( 'privacy-policy' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'synergi' ); ?></a>
				<?php // Reopens the consent banner. Printed hidden; assets/js/parts/consent.js reveals it. ?>
				<button class="syn-footer-consent" type="button" data-syn-consent-open hidden><?php esc_html_e( 'Cookie settings', 'synergi' ); ?></button>
			</div>
		</div>

	</div>
</footer>
<!-- /syn-part: footer -->

<?php get_template_part( 'parts/consent' ); ?>

<?php wp_footer(); ?>
</body>
</html>

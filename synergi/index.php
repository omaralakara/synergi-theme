<?php
/**
 * Fallback template.
 *
 * WordPress falls back to this file whenever no more specific template matches,
 * and requires it for the theme to be valid at all. Stages 4 and 5 add the
 * specific templates (single.php, archive.php, front-page.php); until then this
 * renders everything they will later take over.
 *
 * Loaded by: WordPress template hierarchy.
 * Depends on: header.php, footer.php, parts/page-header.php.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( have_posts() ) :

	/*
	 * The one <h1> on the page, emitted by the template and never typed by an
	 * editor (CLAUDE.md §8). A singular view titles itself; a list view is
	 * titled by its archive.
	 */
	get_template_part(
		'parts/page-header',
		null,
		array( 'title' => is_singular() ? get_the_title() : wp_strip_all_tags( get_the_archive_title() ) )
	);
	?>

	<div class="syn-container syn-container--narrow">
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>

			<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">
				<?php if ( ! is_singular() ) : ?>
					<h2>
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h2>
				<?php endif; ?>

				<div class="syn-entry-content">
					<?php
					if ( is_singular() ) {
						the_content();
					} else {
						the_excerpt();
					}
					?>
				</div>
			</article>

		<?php endwhile; ?>

		<?php the_posts_pagination(); ?>
	</div>

	<?php
else :

	get_template_part(
		'parts/page-header',
		null,
		array( 'title' => __( 'Nothing found', 'synergi' ) )
	);
	?>

	<div class="syn-container syn-container--narrow">
		<div class="syn-entry-content">
			<p><?php esc_html_e( 'No content matched your request.', 'synergi' ); ?></p>
		</div>
	</div>

	<?php
endif;

get_footer();

<?php
/**
 * Fallback template.
 *
 * WordPress falls back to this file whenever no more specific template matches,
 * and requires it for the theme to be valid at all. Stages 4 and 5 add the
 * specific templates (single.php, archive.php, front-page.php); until then this
 * renders everything.
 *
 * Loaded by: WordPress template hierarchy. Depends on: header.php, footer.php.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<?php if ( have_posts() ) : ?>

	<?php
	// The one <h1> on the page, emitted by the template and never typed by an
	// editor (CLAUDE.md §8). Singular views title themselves; list views are
	// titled by the archive.
	?>
	<h1 class="syn-page-title">
		<?php
		if ( is_singular() ) {
			the_title();
		} else {
			the_archive_title();
		}
		?>
	</h1>

	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>

		<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">
			<?php if ( ! is_singular() ) : ?>
				<h2 class="syn-entry-title">
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

<?php else : ?>

	<h1 class="syn-page-title"><?php esc_html_e( 'Nothing found', 'synergi' ); ?></h1>
	<p><?php esc_html_e( 'No content matched your request.', 'synergi' ); ?></p>

<?php endif; ?>

<?php
get_footer();

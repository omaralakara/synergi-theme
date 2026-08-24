<?php
/**
 * Default page template.
 *
 * Renders any page that has not been given a specific template in the editor
 * sidebar. Stage 6 adds templates/service.php, market.php and guide.php for the
 * designed pages; everything else stays here.
 *
 * Loaded by: WordPress template hierarchy. Depends on: header.php, footer.php.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<?php while ( have_posts() ) : ?>
	<?php the_post(); ?>

	<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">
		<?php // The page's single <h1>, from the post title — never from content. ?>
		<h1 class="syn-page-title"><?php the_title(); ?></h1>

		<div class="syn-entry-content">
			<?php the_content(); ?>
		</div>
	</article>

<?php endwhile; ?>

<?php
get_footer();

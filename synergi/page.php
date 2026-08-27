<?php
/**
 * Default page template.
 *
 * Renders any page that has not been given a specific template in the editor
 * sidebar. Stage 6 adds templates/service.php, market.php and guide.php for the
 * designed pages; everything else stays here.
 *
 * Loaded by: WordPress template hierarchy.
 * Depends on: header.php, footer.php, parts/page-header.php.
 * Styled by: assets/css/parts/page-header.css and the .syn-entry-content rules
 * in assets/css/base.css.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">

		<?php
		/*
		 * The excerpt is used as the lede only when an editor has written one.
		 * get_the_excerpt() would otherwise auto-generate one from the first 55
		 * words of the content and print it twice on the page.
		 */
		/*
		 * The Featured Image is what turns this band into the photographic hero
		 * the service pages use (parts/page-header.php decides on its presence).
		 * Passed here so EVERY page gets that header by choosing a picture in the
		 * sidebar, with no template and no developer involved — decided 28 Aug.
		 * A page with no picture still renders the flat navy band, unchanged.
		 */
		get_template_part(
			'parts/page-header',
			null,
			array(
				'lede'  => has_excerpt() ? get_the_excerpt() : '',
				'image' => (int) get_post_thumbnail_id(),
			)
		);
		?>

		<div class="syn-container syn-container--narrow">
			<div class="syn-entry-content">
				<?php the_content(); ?>

				<?php
				wp_link_pages(
					array(
						'before' => '<nav class="syn-page-links" aria-label="' . esc_attr__( 'Page', 'synergi' ) . '">',
						'after'  => '</nav>',
					)
				);
				?>
			</div>
		</div>

	</article>

	<?php
endwhile;

get_footer();

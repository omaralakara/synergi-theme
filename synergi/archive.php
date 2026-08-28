<?php
/**
 * Post listings: /blog/, categories, tags, dates and author archives.
 *
 * Loaded by: WordPress template hierarchy, and by home.php's absence — the
 * posts page (/blog/) falls through to index.php, which delegates here so both
 * routes render identically.
 * Depends on: header.php, footer.php, parts/page-header.php, parts/post-card.php.
 * Styled by: assets/css/parts/page-header.css and assets/css/parts/post.css.
 *
 * Owns the page's one <h1>: the archive's own title, never a post's
 * (CLAUDE.md §8). Each card below it is an <h2>.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

get_header();

/*
 * get_the_archive_title() wraps its output in a <span> and prefixes it
 * ("Category: News"). The band takes plain text and escapes it, so the markup
 * is stripped here. The posts page has no archive title of its own, so it
 * borrows the title of the page assigned to it in Settings > Reading.
 */
$syn_image = 0;

if ( is_home() ) {
	$syn_posts_page = (int) get_option( 'page_for_posts' );
	$syn_title      = $syn_posts_page ? get_the_title( $syn_posts_page ) : __( 'Blog', 'synergi' );
	$syn_lede       = '';

	/*
	 * The blog gets the photographic hero every other page gets, from the
	 * Featured Image on the page assigned to Posts in Settings → Reading
	 * (28 Aug). It was the one landing page still on the flat navy band, for no
	 * reason other than that this template had never been given the picture —
	 * page.php has passed one since the same day. The excerpt comes with it,
	 * because a listing with a sentence of context reads better than a title
	 * alone and there is nowhere else on this page to put one.
	 */
	if ( $syn_posts_page ) {
		$syn_image = (int) get_post_thumbnail_id( $syn_posts_page );
		$syn_lede  = has_excerpt( $syn_posts_page ) ? wp_strip_all_tags( get_the_excerpt( $syn_posts_page ) ) : '';
	}
} else {
	$syn_title = wp_strip_all_tags( get_the_archive_title() );
	$syn_lede  = wp_strip_all_tags( get_the_archive_description() );
}

get_template_part(
	'parts/page-header',
	null,
	array(
		'title' => $syn_title,
		'lede'  => $syn_lede,
		'image' => $syn_image,
	)
);
?>

<div class="syn-container">

	<?php if ( have_posts() ) : ?>

		<div class="syn-card-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'parts/post-card', null, array( 'heading_level' => 2 ) );
			endwhile;
			?>
		</div>

		<?php
		the_posts_pagination(
			array(
				'mid_size'  => 1,
				'prev_text' => esc_html__( 'Previous', 'synergi' ),
				'next_text' => esc_html__( 'Next', 'synergi' ),
			)
		);
		?>

	<?php else : ?>

		<div class="syn-entry-content">
			<p><?php esc_html_e( 'There are no posts here yet.', 'synergi' ); ?></p>
		</div>

	<?php endif; ?>

</div>

<?php
get_footer();

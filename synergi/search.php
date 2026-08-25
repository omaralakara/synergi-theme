<?php
/**
 * Search results.
 *
 * Loaded by: WordPress template hierarchy.
 * Depends on: header.php, footer.php, parts/page-header.php, parts/post-card.php.
 * Styled by: assets/css/parts/page-header.css and assets/css/parts/post.css.
 *
 * Owns the page's one <h1>, which states the query rather than the word
 * "Search" — that is what a person scanning a results page needs to confirm
 * first, and it is what a screen reader announces on arrival.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

get_header();

$syn_query = get_search_query();
$syn_found = (int) $GLOBALS['wp_query']->found_posts;

get_template_part(
	'parts/page-header',
	null,
	array(
		'title' => $syn_query
			/* translators: %s: the search term. */
			? sprintf( __( 'Results for “%s”', 'synergi' ), $syn_query )
			: __( 'Search', 'synergi' ),
		'meta'  => $syn_query
			/* translators: %d: number of results found. */
			? sprintf( _n( '%d result', '%d results', $syn_found, 'synergi' ), $syn_found )
			: '',
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
			<p><?php esc_html_e( 'Nothing matched that search. Try a different wording, or browse the blog.', 'synergi' ); ?></p>

			<?php
			// Core's form, so the theme carries no duplicate markup for it. The
			// html5 support declared in inc/setup.php is what makes it semantic.
			get_search_form();
			?>
		</div>

	<?php endif; ?>

</div>

<?php
get_footer();

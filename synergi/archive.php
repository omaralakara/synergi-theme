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
 * The hero's words are the Posts page's own fields, so the blog is edited the
 * way every other landing page is (31 Aug). syn_blog_hero() in
 * inc/blog-fields.php applies the fallbacks: the field, then the excerpt or the
 * Featured Image that used to be the only source, then nothing.
 *
 * The heading is NOT a field. On the posts page it is the title of the page
 * assigned in Settings > Reading; on a category or tag archive it is the term's
 * own name. Either way it is the thing the reader asked for, and a field here
 * could only disagree with it.
 */
$syn_is_posts_page = is_home();
$syn_hero          = function_exists( 'syn_blog_hero' )
	? syn_blog_hero( $syn_is_posts_page )
	: array(
		'eyebrow' => '',
		'lede'    => '',
		'image'   => 0,
	);

if ( $syn_is_posts_page ) {
	$syn_posts_page = (int) get_option( 'page_for_posts' );
	$syn_title      = $syn_posts_page ? get_the_title( $syn_posts_page ) : __( 'Blog', 'synergi' );
	$syn_lede       = $syn_hero['lede'];
} else {
	/*
	 * get_the_archive_title() wraps its output in a <span> and prefixes it
	 * ("Category: News"). The band takes plain text and escapes it, so the
	 * markup is stripped here. An archive's own description wins over the
	 * blog's sentence when one has been written.
	 */
	$syn_title = wp_strip_all_tags( get_the_archive_title() );
	$syn_lede  = wp_strip_all_tags( get_the_archive_description() );
}

get_template_part(
	'parts/page-header',
	null,
	array(
		'title'   => $syn_title,
		'eyebrow' => $syn_hero['eyebrow'],
		'lede'    => $syn_lede,
		'image'   => $syn_hero['image'],
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

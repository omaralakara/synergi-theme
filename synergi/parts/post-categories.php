<?php
/**
 * The blog's category row.
 *
 * Included by archive.php, above the card grid, on the posts page and on every
 * category archive. Styled by assets/css/parts/post.css (.syn-post-categories),
 * which archive.php already loads as the blog's own layer.
 *
 * It links to the category archives WordPress already publishes at
 * /category/<slug>/ — it invents no URL and adds no query parameter, so nothing
 * here can collide with CLAUDE.md §2.8. On a category archive the current term
 * is marked with aria-current, which is what tells a screen reader which of the
 * links describes the page it is already on (CLAUDE.md §9).
 *
 * Empty categories are left out: three of the eight exist for service lines
 * that have not been written about yet, and a filter that leads to "no posts
 * here yet" is a dead end rather than navigation.
 *
 * No $args.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_cats = get_terms(
	array(
		'taxonomy'   => 'category',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);

// get_terms() returns WP_Error on a broken taxonomy; a listing with no
// categories renders no row at all rather than an empty box.
if ( is_wp_error( $syn_cats ) || count( $syn_cats ) < 2 ) {
	return;
}

$syn_posts_page = (int) get_option( 'page_for_posts' );
$syn_all_url    = $syn_posts_page ? get_permalink( $syn_posts_page ) : home_url( '/' );
?>

<nav class="syn-post-categories" aria-label="<?php esc_attr_e( 'Blog categories', 'synergi' ); ?>">

	<a
		class="syn-post-categories__link"
		href="<?php echo esc_url( $syn_all_url ); ?>"
		<?php echo is_home() ? ' aria-current="page"' : ''; ?>
	><?php esc_html_e( 'All posts', 'synergi' ); ?></a>

	<?php foreach ( $syn_cats as $syn_cat ) : ?>
		<a
			class="syn-post-categories__link"
			href="<?php echo esc_url( get_term_link( $syn_cat ) ); ?>"
			<?php echo is_category( $syn_cat->term_id ) ? ' aria-current="page"' : ''; ?>
		><?php echo esc_html( $syn_cat->name ); ?></a>
	<?php endforeach; ?>

</nav>

<?php
/**
 * Not found.
 *
 * Loaded by: WordPress template hierarchy, on any URL that resolves to nothing.
 * Depends on: header.php, footer.php, parts/page-header.php.
 * Styled by: assets/css/parts/page-header.css and assets/css/parts/post.css.
 *
 * Offers a way onward rather than an apology. The links are resolved through
 * get_permalink() so that a page renamed later cannot turn this template into a
 * second dead end — the one place on the site where that would be embarrassing.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part(
	'parts/page-header',
	null,
	array(
		'title' => __( 'That page has moved or never existed', 'synergi' ),
		'lede'  => __( 'The link may be out of date. Here is the way back.', 'synergi' ),
	)
);

/*
 * Built from pages that actually resolve. A slug that has been changed simply
 * drops out of the list instead of being offered as another broken link.
 */
$syn_wanted = array(
	'about-us'     => __( 'About Us', 'synergi' ),
	'our-services' => __( 'Our Services', 'synergi' ),
	'blog'         => __( 'Our Blog', 'synergi' ),
	'contact-us'   => __( 'Contact Us', 'synergi' ),
);

$syn_links = array();

foreach ( $syn_wanted as $syn_path => $syn_label ) {
	$syn_page = get_page_by_path( $syn_path );

	if ( $syn_page && 'publish' === $syn_page->post_status ) {
		$syn_links[ $syn_label ] = get_permalink( $syn_page );
	}
}
?>

<div class="syn-container syn-container--narrow">
	<div class="syn-entry-content">

		<?php if ( $syn_links ) : ?>
			<ul class="syn-404-links">
				<?php foreach ( $syn_links as $syn_label => $syn_url ) : ?>
					<li><a href="<?php echo esc_url( $syn_url ); ?>"><?php echo esc_html( $syn_label ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php get_search_form(); ?>

	</div>
</div>

<?php
get_footer();

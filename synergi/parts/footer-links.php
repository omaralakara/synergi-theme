<?php
/**
 * One heading-plus-links column of the footer grid.
 *
 * Included by footer.php through get_template_part( 'parts/footer-links', null, $args ).
 * Styled by assets/css/parts/footer.css.
 *
 * Expected $args:
 *   heading string               Required. Column heading, already translated.
 *   links   array<string,?string> Required. Link text => path relative to home
 *                                 (resolved into the current language by
 *                                 syn_local_url(), so one path serves both sites),
 *                                 or null for an entry whose page does not
 *                                 exist yet (see below).
 *
 * Example:
 *   get_template_part( 'parts/footer-links', null, array(
 *       'heading' => __( 'Company', 'synergi' ),
 *       'links'   => array( __( 'About Us', 'synergi' ) => '/about-us/' ),
 *   ) );
 *
 * A null path renders the label as plain text rather than a link. That exists
 * for Project Management, which is listed in the footer on request but has no
 * page behind it yet: linking it would put a 404 in the footer of all 48 URLs,
 * which CLAUDE.md §8 does not allow. Give it a path the day the page exists and
 * it becomes an ordinary link with no other change.
 *
 * Renders nothing when a required key is missing, and says which one in an HTML
 * comment while SYN_DEBUG is on (CLAUDE.md §13: fail loudly in development,
 * gracefully in production).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

foreach ( array( 'heading', 'links' ) as $syn_required ) {
	if ( empty( $args[ $syn_required ] ) ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-part: footer-links missing required arg \"" . esc_html( $syn_required ) . "\" -->\n";
		}

		return;
	}
}
?>
<?php
/*
 * A labelled <nav>, not a heading (10 Sep). The column labels used to be <h2>,
 * which put the same four headings — Services, Solutions, Company, Insights —
 * into the outline of every URL on the site, below each page's own content and
 * identical everywhere. That is boilerplate competing with the page's real
 * sections for the heading structure search engines read (CLAUDE.md §8).
 *
 * The label is still announced: aria-labelledby names each <nav> by its visible
 * text, so a screen reader lists "Services navigation" and the rest in its
 * landmarks menu — the job the heading was doing, without the heading.
 */
$syn_label_id = wp_unique_id( 'syn-footer-nav-' );
?>
<nav class="syn-footer-column" aria-labelledby="<?php echo esc_attr( $syn_label_id ); ?>">
	<p class="syn-footer-heading" id="<?php echo esc_attr( $syn_label_id ); ?>"><?php echo esc_html( $args['heading'] ); ?></p>

	<ul class="syn-footer-links">
		<?php foreach ( (array) $args['links'] as $syn_label => $syn_path ) : ?>
			<li>
				<?php if ( $syn_path ) : ?>
					<a href="<?php echo esc_url( syn_local_url( $syn_path ) ); ?>"><?php echo esc_html( $syn_label ); ?></a>
				<?php else : ?>
					<span class="syn-footer-links__pending"><?php echo esc_html( $syn_label ); ?></span>
					<?php
					if ( SYN_DEBUG ) {
						echo '<!-- syn-part: footer-links "' . esc_html( $syn_label ) . '" has no page yet, rendered unlinked -->';
					}
					?>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

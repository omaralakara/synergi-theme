<?php
/**
 * One heading-plus-links column of the footer grid.
 *
 * Included by footer.php through get_template_part( 'parts/footer-links', null, $args ).
 * Styled by assets/css/parts/footer.css.
 *
 * Expected $args:
 *   heading string  Required. Column heading, plain text.
 *   links   array[] Required. One row per entry, in order, each:
 *                     label string  Required. The words. A row without one is skipped.
 *                     url   ?string The address, ALREADY RESOLVED — see below.
 *                                   Null renders the label as plain text.
 *
 * Example:
 *   get_template_part( 'parts/footer-links', null, array(
 *       'heading' => __( 'Company', 'synergi' ),
 *       'links'   => array(
 *           array( 'label' => __( 'About Us', 'synergi' ), 'url' => home_url( '/about-us/' ) ),
 *       ),
 *   ) );
 *
 * ADDRESSES ARRIVE FINISHED. This part does not call syn_local_url(): its two
 * callers resolve differently and only they can know which is right. The
 * hardcoded fallback holds language-neutral paths and resolves them into the
 * language being viewed; a menu item needs no resolution at all, because
 * Polylang serves a per-language menu whose items already point at the right
 * pages. Resolving here would have run the second one through the first one's
 * lookup. inc/footer-menu.php decides; this file prints.
 *
 * A null url renders the label as plain text rather than a link. It started as
 * the treatment for a page that did not exist yet, and now also catches a menu
 * item whose page has gone back to draft — either way the words stay in the
 * footer and nothing 404s across all 48 URLs, which CLAUDE.md §8 does not
 * allow. The link returns by itself when the page does.
 *
 * A list of rows rather than a label => url map, since 16 Sep: an editor can
 * put the same label under two headings, and a map would have dropped the
 * second one with no error anywhere.
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
		<?php
		foreach ( (array) $args['links'] as $syn_row ) :
			$syn_label = trim( (string) ( $syn_row['label'] ?? '' ) );

			if ( '' === $syn_label ) {
				continue;
			}

			$syn_url = trim( (string) ( $syn_row['url'] ?? '' ) );
			?>
			<li>
				<?php if ( '' !== $syn_url ) : ?>
					<a href="<?php echo esc_url( $syn_url ); ?>"><?php echo esc_html( $syn_label ); ?></a>
				<?php else : ?>
					<span class="syn-footer-links__pending"><?php echo esc_html( $syn_label ); ?></span>
					<?php
					if ( SYN_DEBUG ) {
						echo '<!-- syn-part: footer-links "' . esc_html( $syn_label ) . '" has no published page, rendered unlinked -->';
					}
					?>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php
/**
 * Section 10 — Recent From Instagram.
 *
 * Rendered through syn_section( 'instagram', $args ). Styled by
 * assets/css/sections/instagram.css. No script of its own.
 *
 * A heading, a follow button, and the feed plugin's own output dropped in
 * underneath. The theme owns the frame; the plugin owns the pictures.
 *
 * Expected $args (all optional — the defaults are the approved copy):
 *   eyebrow   string Small label above the heading.
 *   title     string The section's <h2>.
 *   link_url  string The profile to follow.
 *   link_text string The button's label.
 *   shortcode string The feed plugin's shortcode. Rendered only if the
 *                    shortcode is actually registered, so deactivating the
 *                    plugin leaves a tidy section rather than the literal text
 *                    "[instagram-feed]" on the homepage.
 *
 * Example:
 *   syn_section( 'instagram', array( 'title' => 'Life at Synergi' ) );
 *
 * The feed is a third party's markup. instagram.css reaches into it to make it
 * match the rest of the page — the one place in the theme that styles anything
 * it did not render — and says so at the top of that file.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow   = $args['eyebrow'] ?? __( 'Life at Synergi', 'synergi' );
$syn_title     = $args['title'] ?? __( 'Recent From Instagram', 'synergi' );
$syn_link_url  = $args['link_url'] ?? 'https://www.instagram.com/synergi.bpo';
$syn_link_text = $args['link_text'] ?? __( 'Follow Synergi', 'synergi' );
$syn_shortcode = $args['shortcode'] ?? '[instagram-feed feed=1]';

/*
 * The tag is pulled out of the shortcode so its registration can be checked
 * before anything is rendered. A missing plugin then costs the reader an empty
 * section, not a line of raw shortcode text (CLAUDE.md §13: fail gracefully in
 * production, loudly in development).
 */
$syn_feed = '';

if ( $syn_shortcode && preg_match( '/^\[\s*([a-z0-9_-]+)/i', $syn_shortcode, $syn_tag ) ) {
	if ( shortcode_exists( $syn_tag[1] ) ) {
		$syn_feed = do_shortcode( $syn_shortcode );
	} elseif ( SYN_DEBUG ) {
		echo "\n<!-- syn-section instagram: no shortcode registered for \"" . esc_html( $syn_tag[1] ) . "\", feed omitted -->\n";
	}
}

$syn_uid = wp_unique_id( 'syn-instagram-' );
?>
<section class="syn-instagram syn-section" id="instagram" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-instagram__heading syn-reveal">
			<div class="syn-instagram__heading-copy">
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
				<h2 class="syn-instagram__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_title ); ?></h2>
			</div>

			<?php if ( $syn_link_url && $syn_link_text ) : ?>
				<?php
				/*
				 * target="_blank" needs rel="noopener" or the opened tab can
				 * reach back through window.opener. The label says where it
				 * goes, so a screen reader is not surprised by the new tab.
				 */
				?>
				<a
					class="syn-button syn-button--outline syn-instagram__follow"
					href="<?php echo esc_url( $syn_link_url ); ?>"
					target="_blank"
					rel="noopener"
				>
					<?php echo esc_html( $syn_link_text ); ?>
					<span aria-hidden="true">&rarr;</span>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $syn_feed ) : ?>
			<div class="syn-instagram__feed syn-reveal">
				<?php
				// Already run through the shortcode API, which escapes its own
				// output; wp_kses_post() here would strip the feed's markup.
				echo $syn_feed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		<?php endif; ?>

	</div>
</section>

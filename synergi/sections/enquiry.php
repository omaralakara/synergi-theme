<?php
/**
 * Contact section — the enquiry form.
 *
 * Rendered through syn_section( 'enquiry', $args ). Styled by
 * assets/css/sections/enquiry.css. No script: the form brings its own.
 *
 * Expected $args:
 *   eyebrow   string Small label above the heading.
 *   heading   string The section's <h2>.
 *   lede      string One or two lines under it.
 *   note      string Optional small print under the form.
 *   shortcode string The form's shortcode, e.g. [wpforms id="7560"].
 *                    Nothing renders without one.
 *
 * Example:
 *   syn_section( 'enquiry', array( 'shortcode' => '[wpforms id="7560"]' ) );
 *
 * THE THEME DOES NOT BUILD THE FORM. CLAUDE.md §11 puts forms through the
 * consolidated form plugin, because a form is not markup — it is validation,
 * spam handling, storage, notification and a CRM connection through Bit
 * Integrations, and every one of those is a thing a plugin already does and a
 * theme should not start doing. This band is a heading, a paragraph and a place
 * to put the shortcode.
 *
 * Which is also why the shortcode is a field rather than a constant: swapping
 * the form, or moving from WPForms to whatever the form stack consolidates on,
 * should be an edit on the Contact page and not a deploy.
 *
 * do_shortcode() on an editor-supplied string is safe in the way that matters
 * here — the string comes from a capability-checked field on one page, not from
 * a visitor — and it is the only way to render a plugin's form at all. The
 * value is sanitised on save as plain text, so it cannot carry markup of its
 * own (CLAUDE.md §5).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow   = trim( (string) ( $args['eyebrow'] ?? '' ) );
$syn_heading   = trim( (string) ( $args['heading'] ?? '' ) );
$syn_lede      = trim( (string) ( $args['lede'] ?? '' ) );
$syn_note      = trim( (string) ( $args['note'] ?? '' ) );
$syn_shortcode = trim( (string) ( $args['shortcode'] ?? '' ) );

/*
 * No shortcode, no band. A contact page with a heading saying "send us a
 * message" and nothing to send it with is worse than one that simply lists the
 * offices — and the offices band above it already gives a reader a way through.
 */
if ( '' === $syn_shortcode ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section enquiry: no form shortcode set on this page, so the band was skipped -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-enquiry-' );
?>
<section class="syn-enquiry syn-section" id="enquiry" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container syn-enquiry__inner">

		<div class="syn-enquiry__copy syn-reveal">
			<?php if ( '' !== $syn_eyebrow ) : ?>
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<?php endif; ?>

			<h2 class="syn-enquiry__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_lede ) : ?>
				<p class="syn-enquiry__lede"><?php echo esc_html( $syn_lede ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $syn_note ) : ?>
				<p class="syn-enquiry__note"><?php echo esc_html( $syn_note ); ?></p>
			<?php endif; ?>
		</div>

		<div class="syn-enquiry__form syn-reveal">
			<?php
			/*
			 * The plugin's own output, and the one place in the theme where
			 * markup arrives from somewhere else. It is not escaped, because
			 * escaping a form would render it as text — the shortcode itself is
			 * the sanitised value, and what it expands to is the plugin's
			 * responsibility.
			 */
			echo do_shortcode( $syn_shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a form plugin's own rendered output; the editor-supplied shortcode string is sanitised on save.
			?>
		</div>

	</div>
</section>

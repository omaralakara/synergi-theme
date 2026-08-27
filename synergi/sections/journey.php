<?php
/**
 * About section — the company timeline.
 *
 * Rendered through syn_section( 'journey', $args ). Styled by
 * assets/css/sections/journey.css. No script.
 *
 * Expected $args:
 *   eyebrow string Small label above the heading.
 *   heading string The section's <h2>.
 *   lede    string Optional sentence under the heading.
 *   image   int    Attachment ID for the timeline graphic. Nothing renders
 *                  without it — see below.
 *
 * Example:
 *   syn_section( 'journey', array( 'heading' => 'Our Journey', 'image' => 10222 ) );
 *
 * WHY THE PICTURE IS REQUIRED AND THE HEADING IS NOT. This band is a graphic
 * with a label on it. Without the graphic there is nothing to label, so the
 * section skips itself entirely rather than printing a heading over white space
 * (CLAUDE.md §7c).
 *
 * The graphic carries information rather than decorating, so its alt text
 * matters and comes from the media library entry, where a person can fix it
 * without touching code (CLAUDE.md §8). The field description says so.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
$syn_heading = trim( (string) ( $args['heading'] ?? '' ) );
$syn_lede    = trim( (string) ( $args['lede'] ?? '' ) );
$syn_image   = (int) ( $args['image'] ?? 0 );

if ( ! $syn_image ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section journey: no timeline picture chosen, so the band was skipped -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-journey-' );
?>
<section class="syn-journey syn-section" id="journey" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-journey__head syn-reveal">
			<?php if ( '' !== $syn_eyebrow ) : ?>
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<?php endif; ?>

			<h2 class="syn-journey__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_lede ) : ?>
				<p class="syn-journey__lede"><?php echo esc_html( $syn_lede ); ?></p>
			<?php endif; ?>
		</div>

		<figure class="syn-journey__figure syn-reveal">
			<?php
			/*
			 * "full" rather than "large": this is a wide diagram whose small
			 * print has to stay readable, and "large" caps at 1024px, which
			 * would upscale on any ordinary desktop. The smaller crops stay in
			 * srcset underneath, so a phone still fetches a phone-sized file.
			 */
			echo wp_get_attachment_image(
				$syn_image,
				'full',
				false,
				array(
					'class'    => 'syn-journey__image',
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => '(max-width: 82rem) 100vw, 82rem',
				)
			);
			?>
		</figure>

	</div>
</section>

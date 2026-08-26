<?php
/**
 * Section 11 — The Synergi Executive Podcast.
 *
 * Rendered through syn_section( 'podcast', $args ). Styled by
 * assets/css/sections/podcast.css. No script.
 *
 * Copy on one side, cover art on the other, on a pale blue band.
 *
 * Expected $args (all optional — the defaults are the approved copy):
 *   eyebrow    string Small label above the heading.
 *   title      string The section's <h2>.
 *   lead       string The larger sentence under the heading.
 *   body       string The paragraph under that.
 *   note       array  The bordered strip between the copy and the button:
 *                       label string Small uppercase line.
 *                       value string The line under it.
 *   cta        array  label string · url string.
 *   image_slug string Attachment slug for the cover art.
 *   image_id   int    Optional. Overrides image_slug when set.
 *   badge      string The chip over the artwork's corner.
 *
 * Example:
 *   syn_section( 'podcast', array( 'title' => 'The Synergi podcast' ) );
 *
 * Alt text comes from the attachment (CLAUDE.md §8).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'Conversations for people who run businesses', 'synergi' );
$syn_title   = $args['title'] ?? __( 'The Synergi Executive Podcast', 'synergi' );
$syn_lead    = $args['lead'] ?? __( 'Senior leaders unpack the decisions behind stronger operations across MENA.', 'synergi' );
$syn_body    = $args['body'] ?? __( 'Practical perspectives on leadership, shared services, BPO, and operational transformation, grounded in real-world experience.', 'synergi' );

$syn_note = $args['note'] ?? array(
	'label' => __( 'Executive series', 'synergi' ),
	'value' => __( 'Leadership · Strategy · Operations', 'synergi' ),
);

$syn_cta = $args['cta'] ?? array(
	'label' => __( 'Explore the Podcast', 'synergi' ),
	'url'   => home_url( '/executive-podcast/' ),
);

$syn_badge = $args['badge'] ?? __( 'Business media · MENA', 'synergi' );

$syn_image_id = isset( $args['image_id'] )
	? (int) $args['image_id']
	: syn_attachment_id_by_slug( $args['image_slug'] ?? 'synergi-executive-podcast' );

if ( ! $syn_image_id && SYN_DEBUG ) {
	echo "\n<!-- syn-section podcast: no attachment for the cover art -->\n";
}

$syn_uid = wp_unique_id( 'syn-podcast-' );
?>
<section class="syn-podcast syn-section" id="podcast" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container syn-podcast__grid">

		<div class="syn-podcast__copy syn-reveal">
			<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<h2 class="syn-podcast__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_title ); ?></h2>
			<p class="syn-podcast__lead"><?php echo esc_html( $syn_lead ); ?></p>
			<p class="syn-podcast__body"><?php echo esc_html( $syn_body ); ?></p>

			<?php if ( ! empty( $syn_note['label'] ) || ! empty( $syn_note['value'] ) ) : ?>
				<div class="syn-podcast__note">
					<?php if ( ! empty( $syn_note['label'] ) ) : ?>
						<span class="syn-podcast__note-label"><?php echo esc_html( $syn_note['label'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $syn_note['value'] ) ) : ?>
						<strong class="syn-podcast__note-value"><?php echo esc_html( $syn_note['value'] ); ?></strong>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $syn_cta['url'] ) && ! empty( $syn_cta['label'] ) ) : ?>
				<a class="syn-button syn-button--primary" href="<?php echo esc_url( $syn_cta['url'] ); ?>">
					<?php echo esc_html( $syn_cta['label'] ); ?>
					<span aria-hidden="true">&rarr;</span>
				</a>
			<?php endif; ?>
		</div>

		<figure class="syn-podcast__art syn-reveal">
			<?php
			if ( $syn_image_id ) {
				/*
				 * The artwork is never wider than 44rem and is the narrower
				 * column above 61.25rem, so sizes says both rather than letting
				 * the browser assume the full viewport (CLAUDE.md §6).
				 */
				echo wp_get_attachment_image(
					$syn_image_id,
					'large',
					false,
					array(
						'class'    => 'syn-podcast__image',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(max-width: 61.25rem) min(100vw, 44rem), 44rem',
					)
				);
			}
			?>
			<?php if ( $syn_badge ) : ?>
				<figcaption class="syn-podcast__badge"><?php echo esc_html( $syn_badge ); ?></figcaption>
			<?php endif; ?>
		</figure>

	</div>
</section>

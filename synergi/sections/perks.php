<?php
/**
 * Careers section — why people work here.
 *
 * Rendered through syn_section( 'perks', $args ). Styled by
 * assets/css/sections/perks.css. No script: the reveal is base.css's shared
 * IntersectionObserver and the rest is hover state.
 *
 * A white band: one tall photograph on the start side, and on the end side
 * the heading over the reasons set as a large numbered list, one hairline
 * between each.
 *
 * Expected $args:
 *   eyebrow string  Small label above the heading.
 *   heading string  The section's <h2>.
 *   intro   string  Optional. One or two sentences under the heading.
 *   image   int     Optional attachment ID. The photograph.
 *   items   array[] The reasons, in order, each:
 *                     title       string Required. The reason's <h3>.
 *                     description string One or two sentences.
 *
 * Example:
 *   syn_section( 'perks', array(
 *       'heading' => 'Why people build their careers here',
 *       'items'   => array( array( 'title' => 'Room to grow', 'description' => '…' ) ),
 *   ) );
 *
 * A LIST, NOT CARDS (14 Sep). The first cut was a grid of ticked cards beside
 * two photographs on white, and the business did not like it: it read as a
 * features panel, the same shape as every SaaS pricing page. The reasons are
 * now an ordered list with a large numeral each, so the band reads as a
 * considered argument in the company's own voice rather than a checklist.
 * It sat on deep navy for an afternoon; the business asked for the
 * background gone, so it is white.
 *
 * The numeral is drawn by CSS from the list's own numbering, not written into
 * the markup: a reason moved in the editor renumbers itself, and a screen
 * reader is already told this is an ordered list.
 *
 * The photograph is decorative next to the reasons — its alt text comes from
 * the attachment as everywhere else (CLAUDE.md §8).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
$syn_heading = trim( (string) ( $args['heading'] ?? '' ) );
$syn_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$syn_image   = (int) ( $args['image'] ?? 0 );

$syn_items = array();

foreach ( (array) ( $args['items'] ?? array() ) as $syn_row ) {
	if ( ! is_array( $syn_row ) ) {
		continue;
	}

	$syn_title = trim( (string) ( $syn_row['title'] ?? '' ) );

	if ( '' === $syn_title ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-section perks: a reason with no title was skipped -->\n";
		}

		continue;
	}

	$syn_items[] = array(
		'title'       => $syn_title,
		'description' => trim( (string) ( $syn_row['description'] ?? '' ) ),
	);
}

// A reasons band with no reasons is a heading over nothing. It skips itself
// rather than rendering that (CLAUDE.md §7c).
if ( ! $syn_items || '' === $syn_heading ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section perks: no heading or no reasons to render -->\n";
	}

	return;
}

/*
 * Without a photograph the copy takes the whole width rather than leaving an
 * empty column beside it. Written out in full so both names can be found
 * verbatim in perks.css (CLAUDE.md §13, the grep rule).
 */
$syn_inner_class = $syn_image
	? 'syn-container syn-perks__inner syn-perks__inner--with-photo'
	: 'syn-container syn-perks__inner';

$syn_uid = wp_unique_id( 'syn-perks-' );
?>
<section class="syn-perks syn-section" id="why-join" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="<?php echo esc_attr( $syn_inner_class ); ?>">

		<?php if ( $syn_image ) : ?>
			<div class="syn-perks__media syn-reveal">
				<?php
				echo wp_get_attachment_image(
					$syn_image,
					'large',
					false,
					array(
						'class'    => 'syn-perks__image',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(max-width: 61.99rem) 100vw, 30rem',
					)
				);
				?>
			</div>
		<?php endif; ?>

		<div class="syn-perks__copy">

			<div class="syn-perks__head syn-reveal">
				<?php if ( '' !== $syn_eyebrow ) : ?>
					<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
				<?php endif; ?>

				<h2 class="syn-perks__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

				<?php if ( '' !== $syn_intro ) : ?>
					<p class="syn-perks__intro"><?php echo esc_html( $syn_intro ); ?></p>
				<?php endif; ?>
			</div>

			<ol class="syn-perks__list syn-reveal">
				<?php foreach ( $syn_items as $syn_item ) : ?>
					<li class="syn-perks__item">
						<h3 class="syn-perks__item-title"><?php echo esc_html( $syn_item['title'] ); ?></h3>

						<?php if ( '' !== $syn_item['description'] ) : ?>
							<p class="syn-perks__item-text"><?php echo esc_html( $syn_item['description'] ); ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>

		</div>

	</div>
</section>

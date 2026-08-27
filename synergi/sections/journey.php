<?php
/**
 * Journey section — how the company got here.
 *
 * Rendered through syn_section( 'journey', $args ). Styled by
 * assets/css/sections/journey.css. No script: the reveal is base.css's shared
 * IntersectionObserver and the rest is hover and focus states.
 *
 * Expected $args:
 *   eyebrow    string  Optional label above the heading.
 *   heading    string  The band's <h2>.
 *   lede       string  Optional line under the heading.
 *   milestones array[] Optional. The timeline, oldest first, each:
 *                        year  string Shown above the stop. A row without one
 *                                     still renders; a row without a title does
 *                                     not, since the title is the milestone.
 *                        title string What happened.
 *                        note  string Optional line under it.
 *   image      int     Optional attachment ID. The fallback, and only used when
 *                      there are no milestones — see below.
 *
 * Example:
 *   syn_section( 'journey', array(
 *       'heading'    => 'Our Journey',
 *       'milestones' => array( array( 'year' => '2022', 'title' => 'Ideation' ) ),
 *   ) );
 *
 * TWO SHAPES, AND WHY. This band was a single flat photograph of the company
 * deck's timeline slide — a picture of text, which cannot be read by a screen
 * reader, cannot reflow on a phone, cannot be searched, and cannot be corrected
 * without opening a design tool. It is now typed milestones rendered as a real
 * timeline (28 Aug). The picture stays as the fallback so a page that has one
 * and no milestones renders exactly what it rendered before, and so the band is
 * never empty during the changeover.
 *
 * The heading levels are <h2> for the band and <h3> per milestone, so the
 * outline is unbroken whatever an editor types (CLAUDE.md §8).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
$syn_heading = trim( (string) ( $args['heading'] ?? '' ) );
$syn_lede    = trim( (string) ( $args['lede'] ?? '' ) );
$syn_image   = (int) ( $args['image'] ?? 0 );

$syn_stops = array();

foreach ( (array) ( $args['milestones'] ?? array() ) as $syn_row ) {
	if ( ! is_array( $syn_row ) ) {
		continue;
	}

	$syn_title = trim( (string) ( $syn_row['title'] ?? '' ) );

	/* The milestone IS the title. A year on its own is a label with nothing to
	   label, so the row is dropped rather than drawn as an empty stop. */
	if ( '' === $syn_title ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-section journey: a milestone with no title was skipped -->\n";
		}

		continue;
	}

	$syn_stops[] = array(
		'year'  => trim( (string) ( $syn_row['year'] ?? '' ) ),
		'title' => $syn_title,
		'note'  => trim( (string) ( $syn_row['note'] ?? '' ) ),
	);
}

if ( ! $syn_stops && ! $syn_image ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section journey: no milestones typed and no picture chosen, so the band was skipped -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-journey-' );
?>
<section class="syn-journey syn-section" id="journey" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-journey__head syn-reveal">
			<?php if ( '' !== $syn_eyebrow ) : ?>
				<p class="syn-eyebrow syn-journey__eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<?php endif; ?>

			<h2 class="syn-journey__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_lede ) : ?>
				<p class="syn-journey__lede"><?php echo esc_html( $syn_lede ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $syn_stops ) : ?>
			<?php
			/*
			 * An ordered list, because the order is the meaning: this is a
			 * sequence of years, not a set of cards that happen to be beside
			 * each other. The line between the stops is drawn by CSS on each
			 * stop rather than as one long rule across the track, so a timeline
			 * that wraps onto a second row — or stacks on a phone — never
			 * leaves a line running through empty space.
			 */
			?>
			<ol class="syn-journey__track">
				<?php foreach ( $syn_stops as $syn_index => $syn_stop ) : ?>
					<li class="syn-journey__stop syn-reveal" style="--syn-journey-order: <?php echo (int) $syn_index; ?>;">
						<?php if ( '' !== $syn_stop['year'] ) : ?>
							<p class="syn-journey__year"><?php echo esc_html( $syn_stop['year'] ); ?></p>
						<?php endif; ?>

						<?php
						/*
						 * Decoration: the dot and the line are the drawing of a
						 * sequence the markup already states as a numbered
						 * list, so reading them out would say it twice.
						 */
						?>
						<span class="syn-journey__marker" aria-hidden="true"></span>

						<h3 class="syn-journey__milestone"><?php echo esc_html( $syn_stop['title'] ); ?></h3>

						<?php if ( '' !== $syn_stop['note'] ) : ?>
							<p class="syn-journey__note"><?php echo esc_html( $syn_stop['note'] ); ?></p>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php else : ?>
			<figure class="syn-journey__figure syn-reveal">
				<?php
				/*
				 * The fallback: the deck's timeline slide as a picture, for a
				 * page whose milestones have not been typed yet. 'full'
				 * because the figure spans the container, with the smaller
				 * crops still in srcset underneath (CLAUDE.md §6).
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
		<?php endif; ?>

	</div>
</section>

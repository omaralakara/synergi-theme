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
 *                        year  string Shown in the stop's disc. A row without
 *                                     one still renders; a row without a title
 *                                     does not, since the title is the milestone.
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
 * THE YEAR IS IN THE DISC (28 Aug). The deck draws each year inside the circle
 * on the track, with the milestone's name on a dotted lead above or below it,
 * and the band now draws the same thing. The disc is therefore real text and
 * not a decoration: the marker that used to be aria-hidden has become the
 * element the year is printed in, so nothing is repeated and nothing is hidden.
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
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
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
			 * each other.
			 *
			 * The wrapper is a scroll container, and it exists for the case the
			 * old CSS comment already worried about: the company adds a country
			 * every year or so, and a timeline that needs a developer for its
			 * twelfth stop is a timeline that stops being updated. Eight stops
			 * fit a desktop without scrolling; more of them scroll rather than
			 * squeezing every name into four characters. It carries no
			 * tabindex on purpose — browsers make a scrollable region
			 * keyboard-focusable on their own, and below the horizontal
			 * breakpoint the track is stacked and there is nothing to scroll.
			 */
			?>
			<div class="syn-journey__scroller">
				<ol class="syn-journey__track">
					<?php foreach ( $syn_stops as $syn_index => $syn_stop ) : ?>
						<li class="syn-journey__stop syn-reveal" style="--syn-journey-order: <?php echo (int) $syn_index; ?>;">
							<?php
							/*
							 * The disc. It holds the year when there is one and
							 * is an empty circle when there is not, which keeps
							 * a year-less milestone on the track instead of
							 * dropping it a row out of line.
							 */
							?>
							<span class="syn-journey__marker">
								<?php if ( '' !== $syn_stop['year'] ) : ?>
									<span class="syn-journey__year"><?php echo esc_html( $syn_stop['year'] ); ?></span>
								<?php endif; ?>
							</span>

							<div class="syn-journey__copy">
								<h3 class="syn-journey__milestone"><?php echo esc_html( $syn_stop['title'] ); ?></h3>

								<?php if ( '' !== $syn_stop['note'] ) : ?>
									<p class="syn-journey__note"><?php echo esc_html( $syn_stop['note'] ); ?></p>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
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

<?php
/**
 * Careers section — why people work here.
 *
 * Rendered through syn_section( 'perks', $args ). Styled by
 * assets/css/sections/perks.css. No script: the reveal is base.css's shared
 * IntersectionObserver and the rest is hover state.
 *
 * Copy and a pair of photographs on one side, a grid of reasons on the other.
 *
 * Expected $args:
 *   eyebrow string  Small label above the heading.
 *   heading string  The section's <h2>.
 *   intro   string  Optional. One or two sentences under the heading.
 *   image   int     Optional attachment ID. The larger photograph.
 *   image_2 int     Optional attachment ID. The smaller one, over its corner.
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
 * WHY THIS IS NOT sections/values.php. The values band is the company's
 * principles arranged as a wheel around one picture — a diagram, and one the
 * About page owns. This is a list of concrete reasons with two photographs of
 * the team, and it has to read as an argument rather than an emblem. Same
 * card language, different job (CLAUDE.md §4, one section per design).
 *
 * The two photographs are decorative next to the reasons — their alt text
 * comes from the attachment as everywhere else (CLAUDE.md §8), and the pair
 * is one figure with no caption because the heading already says what it is.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
$syn_heading = trim( (string) ( $args['heading'] ?? '' ) );
$syn_intro   = trim( (string) ( $args['intro'] ?? '' ) );
$syn_image   = (int) ( $args['image'] ?? 0 );
$syn_image_2 = (int) ( $args['image_2'] ?? 0 );

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

// The second photograph only makes sense over the corner of the first. On its
// own it takes the first's place, so the band never shows a small picture
// hanging off an empty space.
if ( ! $syn_image && $syn_image_2 ) {
	$syn_image   = $syn_image_2;
	$syn_image_2 = 0;
}

$syn_uid = wp_unique_id( 'syn-perks-' );
?>
<section class="syn-perks syn-section" id="why-join" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container syn-perks__inner">

		<div class="syn-perks__lead syn-reveal">
			<?php if ( '' !== $syn_eyebrow ) : ?>
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<?php endif; ?>

			<h2 class="syn-perks__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_intro ) : ?>
				<p class="syn-perks__intro"><?php echo esc_html( $syn_intro ); ?></p>
			<?php endif; ?>

			<?php if ( $syn_image ) : ?>
				<div class="syn-perks__photos">
					<div class="syn-perks__photo syn-perks__photo--main">
						<?php
						echo wp_get_attachment_image(
							$syn_image,
							'large',
							false,
							array(
								'class'    => 'syn-perks__image',
								'loading'  => 'lazy',
								'decoding' => 'async',
								'sizes'    => '(max-width: 61.99rem) 100vw, 34rem',
							)
						);
						?>
					</div>

					<?php if ( $syn_image_2 ) : ?>
						<div class="syn-perks__photo syn-perks__photo--inset">
							<?php
							echo wp_get_attachment_image(
								$syn_image_2,
								'medium_large',
								false,
								array(
									'class'    => 'syn-perks__image',
									'loading'  => 'lazy',
									'decoding' => 'async',
									'sizes'    => '(max-width: 61.99rem) 45vw, 16rem',
								)
							);
							?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<ul class="syn-perks__list syn-reveal">
			<?php foreach ( $syn_items as $syn_item ) : ?>
				<li class="syn-perks__item">
					<span class="syn-perks__mark" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" focusable="false">
							<path d="M5 12.5l4.5 4.5L19 7.5" />
						</svg>
					</span>
					<h3 class="syn-perks__item-title"><?php echo esc_html( $syn_item['title'] ); ?></h3>

					<?php if ( '' !== $syn_item['description'] ) : ?>
						<p class="syn-perks__item-text"><?php echo esc_html( $syn_item['description'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>

<?php
/**
 * About section — the values the company works to.
 *
 * Rendered through syn_section( 'values', $args ). Styled by
 * assets/css/sections/values.css. No script: the wheel is drawn with two custom
 * properties and a transform, so it needs nothing to run.
 *
 * Expected $args:
 *   eyebrow string  Small label above the heading.
 *   heading string  The section's <h2>.
 *   intro   string  One sentence under the heading.
 *   image   int     Optional attachment ID. The picture at the centre of the
 *                   wheel on a wide screen, and above the values on a narrow one.
 *   items   array[] The values, in order, each:
 *                     title       string The value's <h3>.
 *                     description string One sentence.
 *
 * Example:
 *   syn_section( 'values', array(
 *       'heading' => 'Our Values',
 *       'items'   => array( array( 'title' => 'Agility', 'description' => '…' ) ),
 *   ) );
 *
 * A WHEEL, NOT A LIST (28 Aug). On a wide screen the values are set as discs
 * around the photograph, on a dashed ring — the shape the business already uses
 * for this slide, and the thing a grid of six equal boxes could not say: that
 * these are facets of one company rather than a ranked list. Below 66rem, and
 * whenever the wheel would not fit, the same markup is the card grid it was
 * before. Nothing is hidden in either state and there is no second copy of the
 * values anywhere.
 *
 * WHY THE COUNT DECIDES THE LAYOUT. The discs sit on a circle of fixed radius,
 * so the space between two of them shrinks as more are added: at seven they
 * touch and at eight they overlap. Rather than shrink the type until it cannot
 * be read, a wheel is only drawn for four, five or six values and any other
 * number keeps the grid at every width. The business has six.
 *
 * The counter beside each value is drawn by CSS from the list's own numbering,
 * not written into the markup: a value moved in the editor renumbers itself,
 * and a screen reader is already told this is an ordered list.
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
			echo "\n<!-- syn-section values: a value with no name was skipped -->\n";
		}

		continue;
	}

	$syn_items[] = array(
		'title'       => $syn_title,
		'description' => trim( (string) ( $syn_row['description'] ?? '' ) ),
	);
}

// A values band with no values is a heading over nothing. It skips itself
// rather than rendering that (CLAUDE.md §7c).
if ( ! $syn_items ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section values: no values to render -->\n";
	}

	return;
}

$syn_count = count( $syn_items );

/*
 * Both class names are written out in full rather than assembled, so each one
 * can be found verbatim in values.css and in this file (CLAUDE.md §13, the grep
 * rule). See the file header for why four to six is the range.
 */
$syn_section_class = ( $syn_count >= 4 && $syn_count <= 6 )
	? 'syn-values syn-section syn-values--wheel'
	: 'syn-values syn-section';

if ( SYN_DEBUG && ( $syn_count < 4 || $syn_count > 6 ) ) {
	echo "\n<!-- syn-section values: " . (int) $syn_count . " values, so the grid is used at every width. The wheel is drawn for four to six. -->\n";
}

$syn_uid = wp_unique_id( 'syn-values-' );
?>
<section class="<?php echo esc_attr( $syn_section_class ); ?>" id="values" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-values__head syn-reveal">
			<?php if ( '' !== $syn_eyebrow ) : ?>
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<?php endif; ?>

			<h2 class="syn-values__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_intro ) : ?>
				<p class="syn-values__intro"><?php echo esc_html( $syn_intro ); ?></p>
			<?php endif; ?>
		</div>

		<?php
		/*
		 * The count is handed to CSS rather than to a class per size, because
		 * the angle every disc is placed at is a division by it: one number in,
		 * one formula in the stylesheet, and a seventh value cannot produce a
		 * layout nobody wrote.
		 */
		?>
		<div class="syn-values__wheel syn-reveal" style="--syn-values-count: <?php echo (int) $syn_count; ?>;">

			<?php if ( $syn_image ) : ?>
				<div class="syn-values__hub">
					<?php
					echo wp_get_attachment_image(
						$syn_image,
						'large',
						false,
						array(
							'class'    => 'syn-values__image',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '(max-width: 65.99rem) 100vw, 20rem',
						)
					);
					?>
				</div>
			<?php endif; ?>

			<ol class="syn-values__list">
				<?php foreach ( $syn_items as $syn_index => $syn_item ) : ?>
					<li class="syn-values__item" style="--syn-values-index: <?php echo (int) $syn_index; ?>;">
						<div class="syn-values__item-inner">
							<h3 class="syn-values__item-title"><?php echo esc_html( $syn_item['title'] ); ?></h3>

							<?php if ( '' !== $syn_item['description'] ) : ?>
								<p class="syn-values__item-text"><?php echo esc_html( $syn_item['description'] ); ?></p>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>

		</div>

	</div>
</section>

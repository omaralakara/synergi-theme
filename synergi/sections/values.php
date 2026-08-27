<?php
/**
 * About section — the values the company works to.
 *
 * Rendered through syn_section( 'values', $args ). Styled by
 * assets/css/sections/values.css. No script.
 *
 * Expected $args:
 *   eyebrow string  Small label above the heading.
 *   heading string  The section's <h2>.
 *   intro   string  One sentence under the heading.
 *   image   int     Optional attachment ID, shown under the heading column.
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

$syn_uid = wp_unique_id( 'syn-values-' );
?>
<section class="syn-values syn-section" id="values" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container syn-values__inner">

		<div class="syn-values__head syn-reveal">
			<?php if ( '' !== $syn_eyebrow ) : ?>
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<?php endif; ?>

			<h2 class="syn-values__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_intro ) : ?>
				<p class="syn-values__intro"><?php echo esc_html( $syn_intro ); ?></p>
			<?php endif; ?>

			<?php if ( $syn_image ) : ?>
				<div class="syn-values__media">
					<?php
					echo wp_get_attachment_image(
						$syn_image,
						'large',
						false,
						array(
							'class'    => 'syn-values__image',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '(max-width: 62rem) 100vw, 30rem',
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>

		<ol class="syn-values__list syn-reveal">
			<?php foreach ( $syn_items as $syn_item ) : ?>
				<li class="syn-values__item">
					<h3 class="syn-values__item-title"><?php echo esc_html( $syn_item['title'] ); ?></h3>

					<?php if ( '' !== $syn_item['description'] ) : ?>
						<p class="syn-values__item-text"><?php echo esc_html( $syn_item['description'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>

	</div>
</section>

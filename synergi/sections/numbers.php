<?php
/**
 * Section 06 — Synergi in Numbers.
 *
 * Rendered through syn_section( 'numbers', $args ). Styled by
 * assets/css/sections/numbers.css, driven by assets/js/sections/numbers.js.
 *
 * A full-width brand-blue band with a drifting particle field behind it, a
 * heading, and four company figures that count up the first time they are
 * scrolled into view.
 *
 * Expected $args (all optional — the defaults are the approved copy):
 *   eyebrow string  Small label above the heading.
 *   title   string  The section's <h2>.
 *   lead    string  The paragraph under the heading.
 *   stats   array[] One entry per figure, in reading order, each:
 *                     value string The figure exactly as it should end up,
 *                                  e.g. "50+", "10–15%". Every run of digits
 *                                  in it counts up; everything else is left
 *                                  alone. See numbers.js.
 *                     label string What the figure counts.
 *
 * Example:
 *   syn_section( 'numbers', array( 'title' => 'Synergi in numbers' ) );
 *
 * The figures are written out in full here rather than assembled from a target
 * and a suffix. The page therefore shows the real number with JavaScript off,
 * under reduced motion, and before the script runs — the count is decoration
 * on top of a correct value, never the thing that produces it.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'Company figures', 'synergi' );
$syn_title   = $args['title'] ?? __( 'Synergi in Numbers', 'synergi' );
$syn_lead    = $args['lead'] ?? __( 'One regional partner, measured by the outcomes it delivers for businesses across the Gulf and beyond.', 'synergi' );

$syn_stats = $args['stats'] ?? array(
	array(
		'value' => __( '50+', 'synergi' ),
		'label' => __( 'clients we have served', 'synergi' ),
	),
	array(
		'value' => __( '5', 'synergi' ),
		'label' => __( 'global delivery locations', 'synergi' ),
	),
	array(
		'value' => __( '100+', 'synergi' ),
		'label' => __( 'years of combined experience', 'synergi' ),
	),
	array(
		'value' => __( '10–15%', 'synergi' ),
		'label' => __( 'direct savings', 'synergi' ),
	),
);

$syn_stats = array_values( array_filter( (array) $syn_stats, 'is_array' ) );

if ( ! $syn_stats ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section numbers: no figures to render -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-numbers-' );
?>
<section class="syn-numbers syn-section" id="numbers" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title" data-syn-numbers>

	<?php
	/*
	 * Decoration with no meaning, so it is hidden from assistive technology and
	 * carries no fallback content. numbers.css keeps it out of the document
	 * entirely where scripting is off, since nothing would ever paint it.
	 */
	?>
	<canvas class="syn-numbers__particles" data-syn-numbers-canvas aria-hidden="true"></canvas>

	<div class="syn-container syn-numbers__content">

		<div class="syn-numbers__heading syn-reveal">
			<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<h2 class="syn-numbers__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_title ); ?></h2>
			<p class="syn-numbers__lead"><?php echo esc_html( $syn_lead ); ?></p>
		</div>

		<ul class="syn-numbers__stats syn-reveal" aria-label="<?php esc_attr_e( 'Synergi company figures', 'synergi' ); ?>">
			<?php
			foreach ( $syn_stats as $syn_index => $syn_stat ) :
				$syn_value = $syn_stat['value'] ?? '';
				$syn_label = $syn_stat['label'] ?? '';

				if ( '' === $syn_value || '' === $syn_label ) {
					if ( SYN_DEBUG ) {
						echo "\n<!-- syn-section numbers: figure " . (int) $syn_index . " needs both a value and a label -->\n";
					}

					continue;
				}
				?>
				<li class="syn-numbers__stat">
					<strong class="syn-numbers__figure" data-syn-numbers-count><?php echo esc_html( $syn_value ); ?></strong>
					<span class="syn-numbers__label"><?php echo esc_html( $syn_label ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>

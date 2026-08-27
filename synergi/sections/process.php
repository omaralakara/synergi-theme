<?php
/**
 * Service section — how we work.
 *
 * Rendered through syn_section( 'process', $args ). Styled by
 * assets/css/sections/process.css. No script: it is a numbered list on a dark
 * band, and nothing about it needs behaviour.
 *
 * Expected $args:
 *   heading string  The section's <h2>.
 *   eyebrow string  Small label above the heading.
 *   lede    string  One sentence under the heading.
 *   steps   array[] One entry per step, in order, each:
 *                     title       string Required.
 *                     description string Required.
 *
 * Example:
 *   syn_section( 'process', array( 'steps' => syn_field_rows( 'process' ) ) );
 *
 * The steps render as an <ol>. The sequence is the meaning here — assess before
 * design, design before build — so it is expressed in the markup rather than
 * left to the numbers being read off the screen (CLAUDE.md §8, semantics).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'How we work', 'synergi' );
$syn_heading = $args['heading'] ?? __( 'From assessment to action', 'synergi' );
$syn_lede    = $args['lede'] ?? '';
$syn_steps   = $args['steps'] ?? array();

$syn_clean = array();

foreach ( (array) $syn_steps as $syn_step ) {
	if ( ! is_array( $syn_step ) ) {
		continue;
	}

	$syn_title = trim( (string) ( $syn_step['title'] ?? '' ) );
	$syn_body  = trim( (string) ( $syn_step['description'] ?? '' ) );

	if ( '' === $syn_title ) {
		continue;
	}

	$syn_clean[] = array(
		'title'       => $syn_title,
		'description' => $syn_body,
	);
}

if ( ! $syn_clean ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section process: no steps to render -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-process-' );
?>
<section class="syn-process syn-section" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<?php
	/*
	 * Decoration with no meaning, hidden from assistive technology and carrying
	 * no fallback content — the same treatment the numbers band gives its
	 * particle canvas.
	 */
	?>
	<span class="syn-process__glow" aria-hidden="true"></span>

	<div class="syn-container syn-process__inner">

		<div class="syn-process__head syn-reveal">
			<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<h2 class="syn-process__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_lede ) : ?>
				<p class="syn-process__lede"><?php echo esc_html( $syn_lede ); ?></p>
			<?php endif; ?>
		</div>

		<ol class="syn-process__steps syn-reveal">
			<?php foreach ( $syn_clean as $syn_index => $syn_step ) : ?>
				<li class="syn-process__step">
					<span class="syn-process__number" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $syn_index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
					<h3 class="syn-process__step-title"><?php echo esc_html( $syn_step['title'] ); ?></h3>

					<?php if ( '' !== $syn_step['description'] ) : ?>
						<p class="syn-process__step-body"><?php echo esc_html( $syn_step['description'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>

	</div>
</section>

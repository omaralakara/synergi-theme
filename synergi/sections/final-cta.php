<?php
/**
 * Section 12 — the closing call to action.
 *
 * Rendered through syn_section( 'final-cta', $args ). Styled by
 * assets/css/sections/final-cta.css. No script.
 *
 * The last thing on the homepage: one sentence, one paragraph, two buttons.
 *
 * Expected $args (all optional — the defaults are the approved copy):
 *   eyebrow   string Small label above the heading.
 *   title     string The section's <h2>.
 *   body      string The paragraph under it.
 *   primary   array  label string · url string. The main action.
 *   secondary array  label string · url string. Optional.
 *   note      string The small line under the buttons.
 *
 * Example:
 *   syn_section( 'final-cta', array( 'title' => 'Ready when you are.' ) );
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'Your next operational move', 'synergi' );
$syn_title   = $args['title'] ?? __( 'Make the work behind growth easier to run.', 'synergi' );
$syn_body    = $args['body'] ?? __( 'Synergi runs the functions behind your growth — customer support, HR, finance, procurement, project management, marketing, and IT — for companies across the UAE and the wider Gulf, with outsourcing delivery tailored to how you operate.', 'synergi' );

$syn_primary = $args['primary'] ?? array(
	'label' => __( 'Start a Conversation', 'synergi' ),
	'url'   => home_url( '/contact-us/' ),
);

$syn_secondary = $args['secondary'] ?? array(
	'label' => __( 'Explore Our Services', 'synergi' ),
	'url'   => '#services',
);

$syn_note = $args['note'] ?? __( 'No generic package. Start with what your business needs next.', 'synergi' );

$syn_uid = wp_unique_id( 'syn-final-cta-' );
?>
<section class="syn-final-cta syn-section" id="contact" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container syn-final-cta__inner">

		<div class="syn-final-cta__copy syn-reveal">
			<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<h2 class="syn-final-cta__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_title ); ?></h2>
			<p class="syn-final-cta__body"><?php echo esc_html( $syn_body ); ?></p>
		</div>

		<div class="syn-final-cta__actions syn-reveal">
			<?php if ( ! empty( $syn_primary['url'] ) && ! empty( $syn_primary['label'] ) ) : ?>
				<a class="syn-button syn-button--primary" href="<?php echo esc_url( $syn_primary['url'] ); ?>">
					<?php echo esc_html( $syn_primary['label'] ); ?>
					<span aria-hidden="true">&rarr;</span>
				</a>
			<?php endif; ?>

			<?php if ( ! empty( $syn_secondary['url'] ) && ! empty( $syn_secondary['label'] ) ) : ?>
				<?php
				/*
				 * --outline, not --light: this section is a light band, so the
				 * secondary action is navy on paper. --light is the dark-surface
				 * variant and its white text would be invisible here.
				 */
				?>
				<a class="syn-button syn-button--outline" href="<?php echo esc_url( $syn_secondary['url'] ); ?>">
					<?php echo esc_html( $syn_secondary['label'] ); ?>
					<span aria-hidden="true">&searr;</span>
				</a>
			<?php endif; ?>

			<?php if ( $syn_note ) : ?>
				<p class="syn-final-cta__note"><?php echo esc_html( $syn_note ); ?></p>
			<?php endif; ?>
		</div>

	</div>
</section>

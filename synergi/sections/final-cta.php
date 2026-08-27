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

/*
 * Where this band's words come from, in order: $args, then the "final_cta"
 * site record, then the approved copy below.
 *
 * A record and not page fields because this band closes the homepage AND all
 * six service pages with the same wording (CLAUDE.md §7a). Wired up in Stage 6b
 * alongside the "why" band, for the same reason and in the same shape.
 *
 * An empty record falls through to the strings below, so nothing on any of the
 * seven pages moves until somebody deliberately types something.
 */
$syn_cta = function_exists( 'syn_record' ) ? syn_record( 'final_cta' ) : array();

/**
 * One value from the record, or the approved default when it is blank.
 *
 * @param array  $record  The record group.
 * @param string $key     Column name.
 * @param string $default Approved copy to fall back to.
 * @return string
 */
$syn_cta_value = static function ( $record, $key, $default ) {
	return isset( $record[ $key ] ) && '' !== $record[ $key ] ? $record[ $key ] : $default;
};

$syn_eyebrow = $args['eyebrow'] ?? $syn_cta_value( $syn_cta, 'eyebrow', __( 'Your next operational move', 'synergi' ) );
$syn_title   = $args['title'] ?? $syn_cta_value( $syn_cta, 'title', __( 'Make the work behind growth easier to run.', 'synergi' ) );
$syn_body    = $args['body'] ?? $syn_cta_value( $syn_cta, 'body', __( 'Synergi runs the functions behind your growth — customer support, HR, finance, procurement, project management, marketing, and IT — for companies across the UAE and the wider Gulf, with outsourcing delivery tailored to how you operate.', 'synergi' ) );

/*
 * A button is dropped when either half is missing — see the markup below. The
 * record is read here as a pair so that filling in only an address, or only
 * words, cannot produce half a link.
 */
$syn_primary = $args['primary'] ?? array(
	'label' => $syn_cta_value( $syn_cta, 'primary_label', __( 'Start a Conversation', 'synergi' ) ),
	'url'   => $syn_cta_value( $syn_cta, 'primary_url', home_url( '/contact-us/' ) ),
);

/*
 * The second button is the one an editor may legitimately want gone, so an
 * address typed into the record replaces the default outright rather than
 * merging with it: if either half of the pair is filled in, the pair is theirs.
 */
$syn_has_secondary_record = '' !== ( $syn_cta['secondary_label'] ?? '' ) || '' !== ( $syn_cta['secondary_url'] ?? '' );

$syn_secondary = $args['secondary'] ?? ( $syn_has_secondary_record
	? array(
		'label' => $syn_cta['secondary_label'] ?? '',
		'url'   => $syn_cta['secondary_url'] ?? '',
	)
	: array(
		'label' => __( 'Explore Our Services', 'synergi' ),
		'url'   => '#services',
	) );

$syn_note = $args['note'] ?? $syn_cta_value( $syn_cta, 'note', __( 'No generic package. Start with what your business needs next.', 'synergi' ) );

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

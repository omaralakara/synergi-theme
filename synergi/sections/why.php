<?php
/**
 * Section 05 — why companies choose Synergi.
 *
 * Rendered through syn_section( 'why', $args ). Styled by
 * assets/css/sections/why.css, driven by assets/js/sections/why.js.
 *
 * Copy on one side, a deck of four photographic cards on the other, turning in
 * 3D. It advances on its own every few seconds and stops as soon as anyone
 * touches it, points at it, tabs into it, scrolls it off screen, or switches
 * tab.
 *
 * Expected $args (all optional — the defaults are the approved copy):
 *   eyebrow string  Small label above the heading.
 *   title   string  The section's <h2>.
 *   intro   string  The paragraph under the heading.
 *   cards   array[] One entry per reason, in deck order, each:
 *                     slug        string Attachment slug for the photograph.
 *                     image_id    int    Optional. Overrides slug when set.
 *                     title       string The card's <h3>.
 *                     short       string Two or three words naming the reason,
 *                                        for the pagination button's label.
 *                     description string One sentence under the heading.
 *
 * Example:
 *   syn_section( 'why', array( 'title' => 'Why the Gulf works with us' ) );
 *
 * Alt text comes from the attachment (CLAUDE.md §8), so it is fixable in the
 * media library rather than here.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'Why Synergi', 'synergi' );
$syn_title   = $args['title'] ?? __( 'Why Companies Choose Synergi', 'synergi' );
$syn_intro   = $args['intro'] ?? __( 'Synergi is more than just an outsourcing provider; we are a trusted partner across the Gulf. By combining international standards with regional expertise, we help keep business processes secure, efficient, and compliant.', 'synergi' );

$syn_cards = $args['cards'] ?? array(
	array(
		'slug'        => 'gulf-markets',
		'title'       => __( 'Experience across multiple Gulf markets', 'synergi' ),
		'short'       => __( 'Gulf market experience', 'synergi' ),
		'description' => __( 'Regional context informs how teams, controls, and delivery models are designed.', 'synergi' ),
	),
	array(
		'slug'        => 'customized-strategy',
		'title'       => __( 'Customized outsourcing strategies', 'synergi' ),
		'short'       => __( 'Customized strategies', 'synergi' ),
		'description' => __( 'Each operating model is shaped around the organization, its maturity, and its priorities.', 'synergi' ),
	),
	array(
		'slug'        => 'compliance',
		'title'       => __( 'Compliance with UAE and Gulf regulations', 'synergi' ),
		'short'       => __( 'Regulatory compliance', 'synergi' ),
		'description' => __( 'Governance and local requirements are built into processes and delivery controls.', 'synergi' ),
	),
	array(
		'slug'        => 'scalable-operations',
		'title'       => __( 'Flexible operations that grow with your business', 'synergi' ),
		'short'       => __( 'Scalable operations', 'synergi' ),
		'description' => __( 'Consulting, manpower augmentation, and BPO can scale as requirements change.', 'synergi' ),
	),
);

$syn_cards = array_values( array_filter( (array) $syn_cards, 'is_array' ) );
$syn_total = count( $syn_cards );

if ( ! $syn_total ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section why: no cards to render -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-why-' );

/*
 * The live region's sentence, handed to why.js in a data attribute so the
 * script substitutes into the translated string rather than building an English
 * one of its own. Two placeholders: the reason's number and its heading.
 */
/* translators: 1: position in the deck, e.g. 2. 2: the reason's heading. */
$syn_status_template = __( 'Showing reason %1$s: %2$s.', 'synergi' );
?>
<section class="syn-why syn-section" id="why" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title" data-syn-why>
	<div class="syn-container">
		<div class="syn-why__layout">

			<div class="syn-why__copy syn-reveal">
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
				<h2 class="syn-why__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_title ); ?></h2>
				<p class="syn-why__intro"><?php echo esc_html( $syn_intro ); ?></p>

				<?php
				/*
				 * A running count, not a control — the pagination below does the
				 * choosing. Hidden from assistive technology because the live
				 * region already says which card is showing, in words.
				 */
				?>
				<div class="syn-why__controls">
					<p class="syn-why__counter" aria-hidden="true">
						<span class="syn-why__counter-current" data-syn-why-current><?php echo esc_html( sprintf( '%02d', 1 ) ); ?></span>
						<span><?php echo esc_html( sprintf( '/ %02d', $syn_total ) ); ?></span>
					</p>
				</div>
			</div>

			<div class="syn-why__showcase syn-reveal">

				<?php
				/*
				 * tabindex="0" with an arrow-key hint in the label, so the deck is
				 * operable from the keyboard without every card being a tab stop.
				 * The pagination buttons below are the other way in.
				 */
				?>
				<div
					class="syn-why__stage"
					data-syn-why-stage
					tabindex="0"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %s: number of cards, e.g. four. */ __( '%s reasons to choose Synergi. Use the arrow keys to change the featured card.', 'synergi' ), number_format_i18n( $syn_total ) ) ); ?>"
				>
					<div class="syn-why__deck">
						<?php
						foreach ( $syn_cards as $syn_index => $syn_card ) :
							$syn_card_title = $syn_card['title'] ?? '';

							if ( ! $syn_card_title ) {
								if ( SYN_DEBUG ) {
									echo "\n<!-- syn-section why: card " . (int) $syn_index . " has no title -->\n";
								}

								continue;
							}

							$syn_image_id = isset( $syn_card['image_id'] )
								? (int) $syn_card['image_id']
								: syn_attachment_id_by_slug( $syn_card['slug'] ?? '' );

							/*
							 * Deck position and the active state are rendered here, not
							 * left to why.js. The script is deferred, so a deck applied
							 * in JavaScript would land after first paint and all four
							 * cards would visibly swing into place.
							 */
							$syn_active = 0 === $syn_index;
							?>
							<article
								class="<?php echo esc_attr( $syn_active ? 'syn-why__card syn-is-active' : 'syn-why__card' ); ?>"
								data-syn-why-card
								data-syn-why-position="<?php echo (int) $syn_index; ?>"
							>
								<?php
								if ( $syn_image_id ) {
									/*
									 * The cards are portrait and never wider than 22rem,
									 * so sizes says so rather than letting the browser
									 * assume the full viewport and fetch four times the
									 * pixels it needs (CLAUDE.md §6).
									 */
									echo wp_get_attachment_image(
										$syn_image_id,
										'medium_large',
										false,
										array(
											'class'    => 'syn-why__photo',
											'loading'  => 'lazy',
											'decoding' => 'async',
											'sizes'    => '(max-width: 47.99rem) 76vw, 22rem',
										)
									);
								}
								?>

								<div class="syn-why__card-copy">
									<span class="syn-why__card-number" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $syn_index + 1 ) ); ?></span>
									<h3 class="syn-why__card-title"><?php echo esc_html( $syn_card_title ); ?></h3>
									<p class="syn-why__card-text"><?php echo esc_html( $syn_card['description'] ?? '' ); ?></p>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="syn-why__pagination">
					<?php foreach ( $syn_cards as $syn_index => $syn_card ) : ?>
						<button
							class="<?php echo esc_attr( 0 === $syn_index ? 'syn-why__page syn-is-active' : 'syn-why__page' ); ?>"
							type="button"
							data-syn-why-go="<?php echo (int) $syn_index; ?>"
							aria-pressed="<?php echo 0 === $syn_index ? 'true' : 'false'; ?>"
							aria-label="<?php printf( /* translators: 1: position in the deck, e.g. 2. 2: short name for the reason, e.g. Regulatory compliance. */ esc_attr__( 'Show reason %1$s: %2$s', 'synergi' ), esc_attr( number_format_i18n( $syn_index + 1 ) ), esc_attr( $syn_card['short'] ?? $syn_card['title'] ?? '' ) ); ?>"
						>
							<span aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $syn_index + 1 ) ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>

				<p
					class="syn-visually-hidden"
					data-syn-why-status="<?php echo esc_attr( $syn_status_template ); ?>"
					aria-live="polite"
					aria-atomic="true"
				>
					<?php
					printf(
						esc_html( $syn_status_template ),
						esc_html( number_format_i18n( 1 ) ),
						esc_html( $syn_cards[0]['title'] ?? '' )
					);
					?>
				</p>

			</div>
		</div>
	</div>
</section>

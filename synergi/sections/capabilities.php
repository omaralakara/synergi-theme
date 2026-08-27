<?php
/**
 * Service section — what this service line covers.
 *
 * Rendered through syn_section( 'capabilities', $args ). Styled by
 * assets/css/sections/capabilities.css, enhanced by
 * assets/js/sections/capabilities.js.
 *
 * A list of capability names beside a panel showing the selected one. The
 * pattern is the homepage services deck's, not a new idea — which is what keeps
 * the service page a relative of the homepage rather than a second design.
 *
 * Expected $args:
 *   heading string  The section's <h2>. Required in practice; defaults if absent.
 *   eyebrow string  Small label above the heading.
 *   service string  Optional. One of the six service slugs. Selects the accent
 *                   in capabilities.css; anything else falls back to the brand
 *                   gradient. Never a colour — an editor picks a service.
 *   engagements array[] Optional. The engagement-type chips, each a string.
 *   items   array[] One entry per capability, in reading order, each:
 *                     title       string Required.
 *                     description string Required.
 *                     tags        string Optional, comma separated.
 *
 * Example:
 *   syn_section( 'capabilities', array( 'items' => syn_field_rows( 'capabilities' ) ) );
 *
 * WITH JAVASCRIPT OFF every capability renders in full, stacked, and the tab
 * rail never appears — the rail ships hidden and the script unhides it. The
 * content is therefore never produced by the script, only rearranged by it
 * (CLAUDE.md §10, renders correctly with JavaScript disabled).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow  = $args['eyebrow'] ?? __( 'What we do', 'synergi' );
$syn_heading  = $args['heading'] ?? __( 'What this service covers', 'synergi' );
$syn_items    = $args['items'] ?? array();
$syn_chips    = $args['engagements'] ?? array(
	__( 'Consulting', 'synergi' ),
	__( 'Manpower Augmentation', 'synergi' ),
	__( 'BPO', 'synergi' ),
);

// A capability with no title or no description is a half-filled field row, not
// content. Dropping it here keeps the "is there anything to show" test honest.
$syn_clean = array();

foreach ( (array) $syn_items as $syn_item ) {
	if ( ! is_array( $syn_item ) ) {
		continue;
	}

	$syn_title = trim( (string) ( $syn_item['title'] ?? '' ) );
	$syn_body  = trim( (string) ( $syn_item['description'] ?? '' ) );

	if ( '' === $syn_title || '' === $syn_body ) {
		continue;
	}

	$syn_clean[] = array(
		'title'       => $syn_title,
		'description' => $syn_body,
		'tags'        => array_filter( array_map( 'trim', explode( ',', (string) ( $syn_item['tags'] ?? '' ) ) ) ),
	);
}

if ( ! $syn_clean ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section capabilities: no capabilities to render -->\n";
	}

	return;
}

$syn_uid     = wp_unique_id( 'syn-capabilities-' );
$syn_service = sanitize_key( $args['service'] ?? '' );
?>
<section class="syn-capabilities syn-section" data-service="<?php echo esc_attr( $syn_service ); ?>" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-capabilities__head syn-reveal">
			<div>
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
				<h2 class="syn-capabilities__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>
			</div>

			<?php if ( $syn_chips ) : ?>
				<div class="syn-capabilities__engagements">
					<p class="syn-capabilities__engagements-label"><?php esc_html_e( 'Delivered as', 'synergi' ); ?></p>
					<ul class="syn-capabilities__chips">
						<?php foreach ( $syn_chips as $syn_chip ) : ?>
							<li class="syn-capabilities__chip"><?php echo esc_html( $syn_chip ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>

		<div class="syn-capabilities__explorer syn-reveal" data-syn-capabilities>

			<?php
			/*
			 * Ships hidden. capabilities.js removes the attribute, which is what
			 * makes the enhancement additive: no script, no rail, every panel
			 * still on the page and readable.
			 */
			?>
			<ul class="syn-capabilities__tabs" role="tablist" aria-orientation="vertical" aria-label="<?php esc_attr_e( 'Capabilities', 'synergi' ); ?>" data-syn-capabilities-tabs hidden>
				<?php foreach ( $syn_clean as $syn_index => $syn_item ) : ?>
					<li class="syn-capabilities__tab-item" role="presentation">
						<button
							class="syn-capabilities__tab"
							type="button"
							role="tab"
							id="<?php echo esc_attr( $syn_uid . '-tab-' . $syn_index ); ?>"
							aria-controls="<?php echo esc_attr( $syn_uid . '-panel-' . $syn_index ); ?>"
							aria-selected="<?php echo 0 === $syn_index ? 'true' : 'false'; ?>"
							tabindex="<?php echo 0 === $syn_index ? '0' : '-1'; ?>"
						>
							<span class="syn-capabilities__tab-number"><?php echo esc_html( str_pad( (string) ( $syn_index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<span class="syn-capabilities__tab-title"><?php echo esc_html( $syn_item['title'] ); ?></span>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="syn-capabilities__panels" data-syn-capabilities-panels>
				<?php foreach ( $syn_clean as $syn_index => $syn_item ) : ?>
					<article
						class="syn-capabilities__panel"
						id="<?php echo esc_attr( $syn_uid . '-panel-' . $syn_index ); ?>"
						role="tabpanel"
						aria-labelledby="<?php echo esc_attr( $syn_uid . '-tab-' . $syn_index ); ?>"
						tabindex="0"
					>
						<span class="syn-capabilities__rule" aria-hidden="true"></span>
						<h3 class="syn-capabilities__panel-title"><?php echo esc_html( $syn_item['title'] ); ?></h3>
						<p class="syn-capabilities__panel-body"><?php echo esc_html( $syn_item['description'] ); ?></p>

						<?php if ( $syn_item['tags'] ) : ?>
							<ul class="syn-capabilities__tags">
								<?php foreach ( $syn_item['tags'] as $syn_tag ) : ?>
									<li class="syn-capabilities__tag"><?php echo esc_html( $syn_tag ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>

		</div>

	</div>
</section>

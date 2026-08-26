<?php
/**
 * Section 07 — Our Partners.
 *
 * Rendered through syn_section( 'partners', $args ). Styled by
 * assets/css/sections/partners.css, driven by assets/js/sections/partners.js.
 *
 * A heading and a single row of partner logos that drifts sideways for ever and
 * can be dragged. The logos are not links: the design has never had anywhere for
 * them to go, so they are list items, not anchors with no href.
 *
 * Expected $args (all optional — the defaults are the approved copy):
 *   eyebrow string  Small label above the heading.
 *   title   string  The section's <h2>.
 *   lead    string  The paragraph beside the heading.
 *   logos   array[] One entry per partner, in strip order, each:
 *                     slug     string Attachment slug for the logo.
 *                     image_id int    Optional. Overrides slug when set.
 *
 * Example:
 *   syn_section( 'partners', array( 'title' => 'The platforms behind us' ) );
 *
 * Each logo's name comes from its attachment's alt text (CLAUDE.md §8), so a
 * partner is renamed in the media library rather than here.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'Partner ecosystem', 'synergi' );
$syn_title   = $args['title'] ?? __( 'Our Partners', 'synergi' );
$syn_lead    = $args['lead'] ?? __( 'Leading global platforms and regional specialists, integrated into one delivery model — so the systems behind your operations are always best in class.', 'synergi' );

$syn_logos = $args['logos'] ?? array(
	array( 'slug' => 'partner-innovawave' ),
	array( 'slug' => 'partner-pemo' ),
	array( 'slug' => 'partner-teradix' ),
	array( 'slug' => 'partner-lexzur' ),
	array( 'slug' => 'partner-odoo' ),
	array( 'slug' => 'partner-icxi' ),
	array( 'slug' => 'partner-sap' ),
	array( 'slug' => 'partner-menaitech' ),
	array( 'slug' => 'partner-zoho' ),
);

/*
 * Resolved before anything is echoed, because a strip with one logo in it should
 * not render as a marquee at all and there is no way back once the section has
 * opened.
 */
$syn_resolved = array();

foreach ( (array) $syn_logos as $syn_index => $syn_logo ) {
	if ( ! is_array( $syn_logo ) ) {
		continue;
	}

	$syn_image_id = isset( $syn_logo['image_id'] )
		? (int) $syn_logo['image_id']
		: syn_attachment_id_by_slug( $syn_logo['slug'] ?? '' );

	if ( ! $syn_image_id ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-section partners: no attachment for logo " . (int) $syn_index . ' ("' . esc_html( $syn_logo['slug'] ?? '' ) . '") -->' . "\n";
		}

		continue;
	}

	$syn_resolved[] = $syn_image_id;
}

if ( ! $syn_resolved ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section partners: no logos to render -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-partners-' );
?>
<section class="syn-partners syn-section" id="partners" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">

	<div class="syn-container syn-partners__heading syn-reveal">
		<div class="syn-partners__heading-main">
			<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<h2 class="syn-partners__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_title ); ?></h2>
		</div>
		<div class="syn-partners__heading-aside">
			<p class="syn-partners__lead"><?php echo esc_html( $syn_lead ); ?></p>
		</div>
	</div>

	<?php
	/*
	 * tabindex="0" is what makes the drift pausable from the keyboard. Nothing
	 * inside the strip is focusable — the logos are not links — so without a
	 * stop on the strip itself a keyboard user would have no way to halt
	 * something that never stops moving (WCAG 2.2.2). partners.js pauses on
	 * focus and on hover; reduced motion stops it before it ever starts.
	 */
	?>
	<div
		class="syn-partners__marquee"
		data-syn-partners-marquee
		tabindex="0"
		aria-label="<?php esc_attr_e( 'Partner logos. Drag or swipe to browse.', 'synergi' ); ?>"
	>
		<ul class="syn-partners__track" data-syn-partners-track>
			<?php foreach ( $syn_resolved as $syn_image_id ) : ?>
				<li class="syn-partners__logo" data-syn-partners-logo>
					<?php
					/*
					 * A logo is never drawn wider than 8.4rem, so sizes says so
					 * rather than letting the browser fetch for the full viewport
					 * nine times over (CLAUDE.md §6).
					 */
					echo wp_get_attachment_image(
						$syn_image_id,
						'medium',
						false,
						array(
							'class'    => 'syn-partners__logo-image',
							'loading'  => 'lazy',
							'decoding' => 'async',
							'sizes'    => '8.4rem',
							'draggable' => 'false',
						)
					);
					?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

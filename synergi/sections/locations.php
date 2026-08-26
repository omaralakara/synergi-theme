<?php
/**
 * Section 08 — Our Locations.
 *
 * Rendered through syn_section( 'locations', $args ). Styled by
 * assets/css/sections/locations.css. No script: the whole thing is hover and
 * focus, which CSS does on its own.
 *
 * Five photographs side by side, each a delivery hub. Pointing at one — or
 * tabbing to it — widens it and brings up its call to action. The last one is
 * not open yet, so it is not a link and wears a badge instead.
 *
 * Expected $args (all optional — the defaults are the approved copy):
 *   eyebrow   string  Small label above the heading.
 *   title     string  The section's <h2>.
 *   lead      string  The paragraph beside the heading.
 *   link_url  string  Where "See all our locations" goes.
 *   link_text string  Its label.
 *   places    array[] One entry per hub, in order, each:
 *                       slug     string Attachment slug for the photograph.
 *                       image_id int    Optional. Overrides slug when set.
 *                       city     string The card's <h3>.
 *                       country  string Under the city.
 *                       url      string Where the card goes. Empty means the
 *                                       hub is not open yet: no link, and the
 *                                       badge below shows instead.
 *                       badge    string Optional. Shown only when url is empty.
 *                       action   string The line that appears on hover.
 *
 * Example:
 *   syn_section( 'locations', array( 'title' => 'Where we work' ) );
 *
 * Alt text comes from the attachment (CLAUDE.md §8). URLs go through home_url()
 * so nothing here assumes a domain (CLAUDE.md §12).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow   = $args['eyebrow'] ?? __( 'Regional presence', 'synergi' );
$syn_title     = $args['title'] ?? __( 'Our Locations', 'synergi' );
$syn_lead      = $args['lead'] ?? __( 'Delivery hubs across the Gulf and the Levant, run as one connected team — so the work moves with your business rather than around it.', 'synergi' );
$syn_link_url  = $args['link_url'] ?? home_url( '/global-locations/' );
$syn_link_text = $args['link_text'] ?? __( 'See all our locations', 'synergi' );

$syn_action = __( 'Explore location', 'synergi' );

$syn_places = $args['places'] ?? array(
	array(
		'slug'    => 'location-abu-dhabi',
		'city'    => __( 'Abu Dhabi', 'synergi' ),
		'country' => __( 'United Arab Emirates', 'synergi' ),
		'url'     => $syn_link_url,
		'action'  => $syn_action,
	),
	array(
		'slug'    => 'location-doha',
		'city'    => __( 'Doha', 'synergi' ),
		'country' => __( 'Qatar', 'synergi' ),
		'url'     => $syn_link_url,
		'action'  => $syn_action,
	),
	array(
		'slug'    => 'location-riyadh-2026',
		'city'    => __( 'Riyadh', 'synergi' ),
		'country' => __( 'Saudi Arabia', 'synergi' ),
		'url'     => $syn_link_url,
		'action'  => $syn_action,
	),
	array(
		'slug'    => 'location-beirut-2026',
		'city'    => __( 'Beirut', 'synergi' ),
		'country' => __( 'Lebanon', 'synergi' ),
		'url'     => $syn_link_url,
		'action'  => $syn_action,
	),
	array(
		'slug'    => 'location-damascus-2026',
		'city'    => __( 'Damascus', 'synergi' ),
		'country' => __( 'Syria', 'synergi' ),
		'url'     => '',
		'badge'   => __( 'Coming soon', 'synergi' ),
		'action'  => __( 'Our next delivery hub', 'synergi' ),
	),
);

$syn_places = array_values( array_filter( (array) $syn_places, 'is_array' ) );

if ( ! $syn_places ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section locations: no places to render -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-locations-' );
?>
<section class="syn-locations syn-section" id="locations" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-locations__heading syn-reveal">
			<div class="syn-locations__heading-main">
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
				<h2 class="syn-locations__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_title ); ?></h2>
			</div>
			<div class="syn-locations__heading-aside">
				<p class="syn-locations__lead"><?php echo esc_html( $syn_lead ); ?></p>
				<?php if ( $syn_link_url && $syn_link_text ) : ?>
					<a class="syn-text-link" href="<?php echo esc_url( $syn_link_url ); ?>">
						<?php echo esc_html( $syn_link_text ); ?>
						<span aria-hidden="true">&rarr;</span>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="syn-locations__explorer syn-reveal">
			<ol class="syn-locations__list">
				<?php
				foreach ( $syn_places as $syn_index => $syn_place ) :
					$syn_city = $syn_place['city'] ?? '';

					if ( ! $syn_city ) {
						if ( SYN_DEBUG ) {
							echo "\n<!-- syn-section locations: place " . (int) $syn_index . " has no city -->\n";
						}

						continue;
					}

					$syn_image_id = isset( $syn_place['image_id'] )
						? (int) $syn_place['image_id']
						: syn_attachment_id_by_slug( $syn_place['slug'] ?? '' );

					$syn_country = $syn_place['country'] ?? '';
					$syn_url     = $syn_place['url'] ?? '';
					$syn_badge   = $syn_url ? '' : ( $syn_place['badge'] ?? '' );

					/*
					 * A hub with nowhere to go is a <li> with no anchor inside it,
					 * not a disabled link. The card then cannot be focused, so
					 * locations.css shows its action line permanently rather than
					 * on a focus that can never happen.
					 */
					$syn_classes = 'syn-locations__card';

					if ( ! $syn_url ) {
						$syn_classes .= ' syn-locations__card--soon';
					}
					?>
					<li class="<?php echo esc_attr( $syn_classes ); ?>">
						<?php
						if ( $syn_url ) {
							printf(
								'<a class="syn-locations__link" href="%1$s" aria-label="%2$s">',
								esc_url( $syn_url ),
								esc_attr(
									sprintf(
										/* translators: 1: city, e.g. Doha. 2: country, e.g. Qatar. */
										__( 'Explore Synergi in %1$s, %2$s', 'synergi' ),
										$syn_city,
										$syn_country
									)
								)
							);
						}

						if ( $syn_image_id ) {
							/*
							 * sizes has to describe the card at its WIDEST, not at
							 * rest: the browser picks one candidate at load and
							 * keeps it, so a hint of 26rem — a card's resting fifth
							 * of the container — left a blurred picture the moment
							 * the card grew to about 30rem under the pointer. It
							 * now asks for the hovered width.
							 *
							 * Note for whoever uploads these: the card is portrait
							 * and about 500px tall on a desktop, so a 2x screen
							 * wants roughly 1000px of image HEIGHT. A landscape
							 * photograph 640px tall is upscaled about 45% by
							 * object-fit: cover no matter what this attribute says.
							 * Portrait or square originals of at least 1200x1200
							 * are what this section is built for.
							 */
							echo wp_get_attachment_image(
								$syn_image_id,
								'large',
								false,
								array(
									'class'    => 'syn-locations__photo',
									'loading'  => 'lazy',
									'decoding' => 'async',
									'sizes'    => '(max-width: 47.99rem) 82vw, (max-width: 61.25rem) 40vw, 31rem',
								)
							);
						}
						?>
						<span class="syn-locations__shade" aria-hidden="true"></span>
						<span class="syn-locations__index" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $syn_index + 1 ) ); ?></span>

						<div class="syn-locations__copy">
							<?php if ( $syn_badge ) : ?>
								<span class="syn-locations__badge"><?php echo esc_html( $syn_badge ); ?></span>
							<?php endif; ?>
							<h3 class="syn-locations__city"><?php echo esc_html( $syn_city ); ?></h3>
							<?php if ( $syn_country ) : ?>
								<p class="syn-locations__country"><?php echo esc_html( $syn_country ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $syn_place['action'] ) ) : ?>
								<span class="syn-locations__action">
									<?php echo esc_html( $syn_place['action'] ); ?>
									<?php if ( $syn_url ) : ?>
										<span aria-hidden="true">&nearr;</span>
									<?php endif; ?>
								</span>
							<?php endif; ?>
						</div>

						<?php
						if ( $syn_url ) {
							echo '</a>';
						}
						?>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>

	</div>
</section>

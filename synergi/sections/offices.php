<?php
/**
 * Contact section — every office, its address, how to reach it, and its map.
 *
 * Rendered through syn_section( 'offices', $args ). Styled by
 * assets/css/sections/offices.css, enhanced by assets/js/sections/offices.js.
 *
 * Expected $args:
 *   eyebrow string  Small label above the heading.
 *   heading string  The section's <h2>.
 *   lede    string  One line under it.
 *   places  array[] Rows from the "locations" site record, each:
 *                     city    string Required — the card's <h3>.
 *                     country string Shown beside the code badge.
 *                     code    string Two letters, e.g. AE. The badge.
 *                     address string The street address, and what the map
 *                                    searches for.
 *                     email   string Optional. Rendered as a mailto link.
 *                     phone   string Optional. Rendered as a tel link.
 *                     entity  string Optional. The registered company name.
 *
 * Example:
 *   syn_section( 'offices', array( 'places' => syn_record( 'locations' ) ) );
 *
 * THE MAP IS NOT LOADED UNTIL SOMEBODY ASKS FOR IT, and that is the whole
 * design of this band. CLAUDE.md §2.6 forbids the theme making external
 * requests from the front end; a Google Maps iframe is one, it weighs several
 * hundred kilobytes, and it sets third-party cookies on a page nobody has
 * interacted with yet. So the card ships with an address, a link that opens
 * Google Maps in a new tab, and — where there is JavaScript to swap it in — a
 * button that loads the embedded map in place. Nothing reaches Google until a
 * person clicks. The rule holds, the map is still there, and the page weighs
 * what it weighs with no offices open.
 *
 * The map link and the map embed are both built from the address rather than
 * from a stored Google URL. A short goo.gl link cannot be checked by eye, it
 * cannot be corrected without opening the map, and it points at a domain the
 * theme would then have hard-coded. An address is content, and it is already
 * on the card.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
$syn_heading = trim( (string) ( $args['heading'] ?? '' ) );
$syn_lede    = trim( (string) ( $args['lede'] ?? '' ) );

$syn_places = array();

foreach ( (array) ( $args['places'] ?? array() ) as $syn_row ) {
	if ( ! is_array( $syn_row ) ) {
		continue;
	}

	$syn_city = trim( (string) ( $syn_row['city'] ?? '' ) );

	// A card with no city is a card with no name. The address alone cannot head
	// it, and a heading is what makes this a card rather than a paragraph.
	if ( '' === $syn_city ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-section offices: a location with no city was skipped -->\n";
		}

		continue;
	}

	$syn_places[] = array(
		'city'    => $syn_city,
		'country' => trim( (string) ( $syn_row['country'] ?? '' ) ),
		'code'    => strtoupper( trim( (string) ( $syn_row['code'] ?? '' ) ) ),
		'flag'    => (int) ( $syn_row['flag'] ?? 0 ),
		'address' => trim( (string) ( $syn_row['address'] ?? '' ) ),
		'email'   => trim( (string) ( $syn_row['email'] ?? '' ) ),
		'phone'   => trim( (string) ( $syn_row['phone'] ?? '' ) ),
		'entity'  => trim( (string) ( $syn_row['entity'] ?? '' ) ),
	);
}

if ( ! $syn_places ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section offices: nothing in the locations record. Add the offices at Settings > Site records. -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-offices-' );
?>
<section class="syn-offices syn-section" id="offices" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-offices__head syn-reveal">
			<?php if ( '' !== $syn_eyebrow ) : ?>
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<?php endif; ?>

			<h2 class="syn-offices__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_lede ) : ?>
				<p class="syn-offices__lede"><?php echo esc_html( $syn_lede ); ?></p>
			<?php endif; ?>
		</div>

		<ul class="syn-offices__list">
			<?php
			foreach ( $syn_places as $syn_index => $syn_place ) :
				$syn_card_id = $syn_uid . '-' . (int) $syn_index;

				/*
				 * What the map looks for. The address plus the country, because
				 * "Hamra street, 2nd Floor" is a street in more than one city
				 * and the country is what disambiguates it. Falls back to the
				 * city when no address has been typed yet, so the button still
				 * points somewhere sensible on a half-filled record.
				 */
				$syn_query = trim(
					( '' !== $syn_place['address'] ? $syn_place['address'] : $syn_place['city'] )
					. ( '' !== $syn_place['country'] ? ', ' . $syn_place['country'] : '' )
				);

				$syn_map_url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $syn_query );
				?>
				<li class="syn-offices__item syn-reveal">
					<article class="syn-offices__card" aria-labelledby="<?php echo esc_attr( $syn_card_id ); ?>-city">

						<p class="syn-offices__place">
							<?php
							/*
							 * The badge: the country's flag where one has been
							 * chosen, its two-letter code where one has not.
							 * Both are decoration — the country is written out
							 * in full right beside them, so a flag with alt
							 * text or a code read aloud would say the same
							 * thing twice (CLAUDE.md §8). The picture is
							 * therefore given an empty alt deliberately rather
							 * than by omission.
							 */
							if ( $syn_place['flag'] ) :
								?>
								<span class="syn-offices__flag">
									<?php
									echo wp_get_attachment_image(
										$syn_place['flag'],
										'thumbnail',
										false,
										array(
											'class'    => 'syn-offices__flag-image',
											'alt'      => '',
											'loading'  => 'lazy',
											'decoding' => 'async',
										)
									);
									?>
								</span>
							<?php elseif ( '' !== $syn_place['code'] ) : ?>
								<span class="syn-offices__code" aria-hidden="true"><?php echo esc_html( $syn_place['code'] ); ?></span>
							<?php endif; ?>

							<?php if ( '' !== $syn_place['country'] ) : ?>
								<span class="syn-offices__country"><?php echo esc_html( $syn_place['country'] ); ?></span>
							<?php endif; ?>
						</p>

						<h3 class="syn-offices__city" id="<?php echo esc_attr( $syn_card_id ); ?>-city"><?php echo esc_html( $syn_place['city'] ); ?></h3>

						<?php if ( '' !== $syn_place['entity'] ) : ?>
							<p class="syn-offices__entity"><?php echo esc_html( $syn_place['entity'] ); ?></p>
						<?php endif; ?>

						<?php if ( '' !== $syn_place['address'] ) : ?>
							<p class="syn-offices__address"><?php echo esc_html( $syn_place['address'] ); ?></p>
						<?php endif; ?>

						<?php if ( '' !== $syn_place['email'] || '' !== $syn_place['phone'] ) : ?>
							<ul class="syn-offices__contacts">
								<?php if ( '' !== $syn_place['email'] ) : ?>
									<li class="syn-offices__contact">
										<a class="syn-offices__contact-link" href="mailto:<?php echo esc_attr( sanitize_email( $syn_place['email'] ) ); ?>"><?php echo esc_html( $syn_place['email'] ); ?></a>
									</li>
								<?php endif; ?>

								<?php if ( '' !== $syn_place['phone'] ) : ?>
									<li class="syn-offices__contact">
										<?php
										/*
										 * A tel: href cannot carry spaces, so the
										 * dialled number is stripped to digits
										 * and a leading plus while the visible
										 * one keeps the grouping that makes it
										 * readable.
										 */
										$syn_dial = preg_replace( '/[^0-9+]/', '', $syn_place['phone'] );
										?>
										<a class="syn-offices__contact-link" href="tel:<?php echo esc_attr( $syn_dial ); ?>"><?php echo esc_html( $syn_place['phone'] ); ?></a>
									</li>
								<?php endif; ?>
							</ul>
						<?php endif; ?>

						<div class="syn-offices__map" data-syn-office-map data-syn-map-query="<?php echo esc_attr( $syn_query ); ?>" data-syn-map-title="<?php echo esc_attr( sprintf( /* translators: %s: city name, e.g. Abu Dhabi. */ __( 'Map of the Synergi office in %s', 'synergi' ), $syn_place['city'] ) ); ?>">

							<?php
							/*
							 * Shown only where a script can swap the map in —
							 * offices.css reveals it inside (scripting: enabled).
							 * A button that cannot do anything is worse than no
							 * button (CLAUDE.md §10).
							 */
							?>
							<button class="syn-offices__map-button" type="button" data-syn-map-show>
								<?php
								printf(
									/* translators: %s: city name, e.g. Abu Dhabi. */
									esc_html__( 'Show map of %s', 'synergi' ),
									esc_html( $syn_place['city'] )
								);
								?>
							</button>

							<a class="syn-offices__map-link" href="<?php echo esc_url( $syn_map_url ); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Open in Google Maps', 'synergi' ); ?>
								<span aria-hidden="true">&#8599;</span>
							</a>
						</div>

					</article>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>

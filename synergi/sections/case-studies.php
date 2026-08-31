<?php
/**
 * Listing section — the case studies, as a grid of cards.
 *
 * Rendered through syn_section( 'case-studies', $args ). Styled by
 * assets/css/sections/case-studies.css. No script.
 *
 * The body of the Case studies listing page, and the "more case studies" band
 * at the foot of a single case study. Each card carries the four things the
 * business asked for on 31 Aug — the headline, the service line, the kind of
 * client and the country — with one link into the study itself.
 *
 * WHERE THE CARDS COME FROM. Nothing is typed twice: they are built from the
 * case-study pages themselves by syn_case_studies() in
 * inc/case-study-fields.php, so publishing a case study puts it on this grid and
 * unpublishing one takes it off. There is no list of case studies for an editor
 * to keep in step with reality.
 *
 * Expected $args (all optional):
 *   eyebrow string  Small label above the heading.
 *   heading string  The section's <h2>.
 *   lede    string  One or two sentences under the heading.
 *   empty   string  Shown in place of the grid when there are none yet.
 *   items   array[] Cards, in order, each in syn_case_study_card()'s shape:
 *                   title, url, summary, service, client, country, code
 *                   (strings) and image, flag (attachment IDs).
 *                   Omit it and the section fetches its own with the three
 *                   arguments below.
 *   count   int     How many to fetch. -1, the default, is all of them.
 *   service string  Optional. A service reference, to show only that line's.
 *   exclude int     Optional. A page ID to leave out — a case study's own, so
 *                   it does not link to itself.
 *
 * Example:
 *   syn_section( 'case-studies', array( 'exclude' => get_the_ID(), 'count' => 3 ) );
 *
 * The card headings are <h3>s: the page's <h1> is its title and this section's
 * heading is its <h2>, so the levels never skip (CLAUDE.md §8).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'Proof', 'synergi' );
$syn_heading = $args['heading'] ?? __( 'Case studies', 'synergi' );
$syn_lede    = trim( (string) ( $args['lede'] ?? '' ) );
$syn_empty   = trim( (string) ( $args['empty'] ?? '' ) );

/*
 * Passed cards win; otherwise the section asks for its own. The fallback is what
 * lets a template render the band with no arguments at all; the override is what
 * would let a service page hand it a hand-picked three later.
 */
if ( isset( $args['items'] ) ) {
	$syn_cards = (array) $args['items'];
} elseif ( function_exists( 'syn_case_studies' ) ) {
	$syn_cards = syn_case_studies(
		array(
			'count'   => isset( $args['count'] ) ? (int) $args['count'] : -1,
			'service' => (string) ( $args['service'] ?? '' ),
			'exclude' => (int) ( $args['exclude'] ?? 0 ),
		)
	);
} else {
	$syn_cards = array();
}

$syn_uid = wp_unique_id( 'syn-case-studies-' );
?>
<section class="syn-case-studies syn-section" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-case-studies__head syn-reveal">
			<?php if ( '' !== $syn_eyebrow ) : ?>
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<?php endif; ?>

			<h2 class="syn-case-studies__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_lede ) : ?>
				<p class="syn-case-studies__lede"><?php echo esc_html( $syn_lede ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( ! $syn_cards ) : ?>

			<?php
			/*
			 * No case studies published yet is an ordinary state on the day this
			 * page goes live, so it says so in the editor's own words rather than
			 * rendering a heading over an empty grid (CLAUDE.md §7c).
			 */
			if ( '' !== $syn_empty ) :
				?>
				<p class="syn-case-studies__empty syn-reveal"><?php echo esc_html( $syn_empty ); ?></p>
				<?php
			endif;

			if ( SYN_DEBUG ) {
				echo "\n<!-- syn-section case-studies: no published page is using templates/case-study.php. Create one and choose that template in the page sidebar. -->\n";
			}
			?>

		<?php else : ?>

			<ul class="syn-case-studies__grid syn-reveal">
				<?php
				foreach ( $syn_cards as $syn_card ) :
					$syn_card_title = trim( (string) ( $syn_card['title'] ?? '' ) );
					$syn_card_url   = trim( (string) ( $syn_card['url'] ?? '' ) );

					// A card with no title or nowhere to go is not a card. It is
					// skipped rather than rendered as an empty tile.
					if ( '' === $syn_card_title || '' === $syn_card_url ) {
						continue;
					}

					$syn_card_id = wp_unique_id( 'syn-case-studies-card-' );
					$syn_service = trim( (string) ( $syn_card['service'] ?? '' ) );
					$syn_client  = trim( (string) ( $syn_card['client'] ?? '' ) );
					$syn_country = trim( (string) ( $syn_card['country'] ?? '' ) );
					$syn_code    = trim( (string) ( $syn_card['code'] ?? '' ) );
					$syn_flag    = (int) ( $syn_card['flag'] ?? 0 );
					$syn_image   = (int) ( $syn_card['image'] ?? 0 );
					?>
					<li class="syn-case-studies__item">
						<article class="syn-case-studies__card" aria-labelledby="<?php echo esc_attr( $syn_card_id ); ?>">

							<?php if ( $syn_image ) : ?>
								<div class="syn-case-studies__media">
									<?php
									/*
									 * Through core, so srcset and sizes come free
									 * (CLAUDE.md §6). alt="" because the card's
									 * heading is the link and says the same thing:
									 * a picture repeating it would have a screen
									 * reader announce the study twice
									 * (CLAUDE.md §8).
									 */
									echo wp_get_attachment_image(
										$syn_image,
										'medium_large',
										false,
										array(
											'class'    => 'syn-case-studies__image',
											'alt'      => '',
											'loading'  => 'lazy',
											'decoding' => 'async',
											'sizes'    => '(max-width: 48rem) 100vw, 33vw',
										)
									);
									?>
								</div>
							<?php endif; ?>

							<div class="syn-case-studies__body">

								<?php if ( '' !== $syn_service ) : ?>
									<p class="syn-case-studies__service"><?php echo esc_html( $syn_service ); ?></p>
								<?php endif; ?>

								<?php
								/*
								 * The heading carries the link, so the whole card is
								 * not one enormous tab stop and the link announces
								 * itself with the study's own headline rather than
								 * with "read more" (CLAUDE.md §9).
								 */
								?>
								<h3 class="syn-case-studies__name" id="<?php echo esc_attr( $syn_card_id ); ?>">
									<a class="syn-case-studies__link" href="<?php echo esc_url( $syn_card_url ); ?>"><?php echo esc_html( $syn_card_title ); ?></a>
								</h3>

								<?php if ( '' !== $syn_client ) : ?>
									<p class="syn-case-studies__client"><?php echo esc_html( $syn_client ); ?></p>
								<?php endif; ?>

								<?php if ( '' !== $syn_country || $syn_flag || '' !== $syn_code ) : ?>
									<p class="syn-case-studies__place">
										<?php
										/*
										 * The badge: the country's flag where one has
										 * been chosen, its two-letter code where one
										 * has not. Both are decoration — the country
										 * is written out beside them — so the picture
										 * takes an empty alt deliberately rather than
										 * by omission. Same pattern and same reasoning
										 * as sections/offices.php.
										 */
										if ( $syn_flag ) :
											?>
											<span class="syn-case-studies__flag">
												<?php
												echo wp_get_attachment_image(
													$syn_flag,
													'thumbnail',
													false,
													array(
														'class'    => 'syn-case-studies__flag-image',
														'alt'      => '',
														'loading'  => 'lazy',
														'decoding' => 'async',
													)
												);
												?>
											</span>
										<?php elseif ( '' !== $syn_code ) : ?>
											<span class="syn-case-studies__code" aria-hidden="true"><?php echo esc_html( $syn_code ); ?></span>
										<?php endif; ?>

										<?php if ( '' !== $syn_country ) : ?>
											<span class="syn-case-studies__country"><?php echo esc_html( $syn_country ); ?></span>
										<?php endif; ?>
									</p>
								<?php endif; ?>

								<?php
								/*
								 * The button the business asked for. It points at the
								 * same page the heading does, so it is taken out of the
								 * tab order and hidden from assistive technology: a
								 * screen reader that met both would hear the same
								 * destination twice, once with a worse name
								 * (CLAUDE.md §9). Sighted readers get the button they
								 * expect to click; nobody gets a duplicate.
								 */
								?>
								<p class="syn-case-studies__action">
									<span class="syn-case-studies__button" aria-hidden="true">
										<?php esc_html_e( 'Read the case study', 'synergi' ); ?>
										<span class="syn-case-studies__arrow">&rarr;</span>
									</span>
								</p>

							</div>

						</article>
					</li>
					<?php
				endforeach;
				?>
			</ul>

		<?php endif; ?>

	</div>
</section>

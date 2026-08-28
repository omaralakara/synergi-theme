<?php
/**
 * Media section — a row of videos, each played where it sits.
 *
 * Rendered through syn_section( 'episodes', $args ). Styled by
 * assets/css/sections/episodes.css, driven by assets/js/sections/episodes.js.
 *
 * Expected $args:
 *   eyebrow string  Small label above the heading.
 *   heading string  The section's <h2>.
 *   lede    string  Optional line under it.
 *   tone    string  "paper" or "white". Which of the two surfaces the band
 *                   sits on. Set by the TEMPLATE, never by a field: a page that
 *                   renders this band twice needs the second one to alternate,
 *                   and that is a composition decision rather than something an
 *                   editor should be able to get wrong (CLAUDE.md §7c).
 *   items   array[] One entry per video, in order, each:
 *                     title string Required — the card's <h3>.
 *                     url   string Required — any YouTube address.
 *                     note  string Optional line under the title.
 *                     image int    Optional attachment ID for the poster.
 *
 * Example:
 *   syn_section( 'episodes', array( 'items' => syn_field_rows( 'podcast_episodes' ) ) );
 *
 * NOTHING REACHES YOUTUBE UNTIL SOMEBODY PRESSES PLAY, which is the same rule
 * and the same shape as the maps on Contact Us (sections/offices.php). Seven
 * embedded players is seven external requests, several megabytes and a set of
 * third-party cookies dropped on a reader who has not watched anything — and
 * CLAUDE.md §2.6 does not allow the theme to make external requests at all. So
 * a card is a poster, a title and a play button; the player is built on the
 * click. The poster is an uploaded picture rather than YouTube's own thumbnail,
 * because fetching that thumbnail would itself be the request we are avoiding.
 *
 * The player is youtube-nocookie.com rather than youtube.com. It is the same
 * video and the same embed, and it is the version that does not set tracking
 * cookies until playback starts.
 *
 * Every card also carries a real link to the video, so the page works with
 * JavaScript off and a middle-click still opens the episode (CLAUDE.md §10).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/*
 * syn_youtube_id() lives in inc/sections.php, not here. A section partial is
 * included once per render and this band renders TWICE on the podcast page —
 * episodes, then webinars — so a function declared at this file's scope was a
 * fatal "cannot redeclare" the second time round, and took the page down with
 * it (28 Aug). Nothing that a section defines may live in the section: a
 * partial is markup, and anything reusable belongs where the other shared
 * section helpers are.
 */

$syn_eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
$syn_heading = trim( (string) ( $args['heading'] ?? '' ) );
$syn_lede    = trim( (string) ( $args['lede'] ?? '' ) );
$syn_tone    = 'paper' === ( $args['tone'] ?? '' ) ? 'paper' : 'white';

$syn_items = array();

foreach ( (array) ( $args['items'] ?? array() ) as $syn_row ) {
	if ( ! is_array( $syn_row ) ) {
		continue;
	}

	$syn_title = trim( (string) ( $syn_row['title'] ?? '' ) );
	$syn_video = syn_youtube_id( $syn_row['url'] ?? '' );

	// Both, or neither. A card with no title has nothing to announce itself
	// with, and a card whose address did not parse would be a play button that
	// opens nothing.
	if ( '' === $syn_title || '' === $syn_video ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-section episodes: skipped a row — it needs a title and a YouTube address that contains a video id -->\n";
		}

		continue;
	}

	$syn_items[] = array(
		'title' => $syn_title,
		'video' => $syn_video,
		'note'  => trim( (string) ( $syn_row['note'] ?? '' ) ),
		'image' => (int) ( $syn_row['image'] ?? 0 ),
	);
}

if ( ! $syn_items ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section episodes: no videos to render -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-episodes-' );

/*
 * Both class names are written out in full rather than assembled from $tone, so
 * each is findable verbatim in episodes.css (CLAUDE.md §13, the grep rule).
 */
$syn_class = 'paper' === $syn_tone
	? 'syn-episodes syn-section syn-episodes--paper'
	: 'syn-episodes syn-section';
?>
<section class="<?php echo esc_attr( $syn_class ); ?>" id="<?php echo esc_attr( $syn_uid ); ?>" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-episodes__head syn-reveal">
			<?php if ( '' !== $syn_eyebrow ) : ?>
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<?php endif; ?>

			<h2 class="syn-episodes__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_lede ) : ?>
				<p class="syn-episodes__lede"><?php echo esc_html( $syn_lede ); ?></p>
			<?php endif; ?>
		</div>

		<ul class="syn-episodes__list">
			<?php
			foreach ( $syn_items as $syn_index => $syn_item ) :
				$syn_card_id = $syn_uid . '-' . (int) $syn_index;
				$syn_watch   = 'https://www.youtube.com/watch?v=' . $syn_item['video'];
				?>
				<li class="syn-episodes__item syn-reveal">
					<article
						class="syn-episodes__card"
						data-syn-episode
						data-syn-episode-id="<?php echo esc_attr( $syn_item['video'] ); ?>"
						data-syn-episode-title="<?php echo esc_attr( $syn_item['title'] ); ?>"
						aria-labelledby="<?php echo esc_attr( $syn_card_id ); ?>-title"
					>
						<div class="syn-episodes__frame" data-syn-episode-frame>
							<?php if ( $syn_item['image'] ) : ?>
								<?php
								/*
								 * Decorative: the title sits under the poster
								 * and the play button is labelled with it, so
								 * alt text here would say the same thing a
								 * third time (CLAUDE.md §8).
								 */
								echo wp_get_attachment_image(
									$syn_item['image'],
									'medium_large',
									false,
									array(
										'class'    => 'syn-episodes__poster',
										'alt'      => '',
										'loading'  => 'lazy',
										'decoding' => 'async',
										'sizes'    => '(max-width: 47.99rem) 100vw, 24rem',
									)
								);
								?>
							<?php endif; ?>

							<?php
							/*
							 * A button, not a link: pressing it does something
							 * on this page. The link to YouTube is separate and
							 * sits below, which is what a reader without
							 * JavaScript gets instead — episodes.css hides this
							 * button until there is a script to answer it.
							 */
							?>
							<button class="syn-episodes__play" type="button" data-syn-episode-play>
								<span class="syn-episodes__play-mark" aria-hidden="true"></span>
								<span class="syn-visually-hidden">
									<?php
									printf(
										/* translators: %s: the episode's title. */
										esc_html__( 'Play %s', 'synergi' ),
										esc_html( $syn_item['title'] )
									);
									?>
								</span>
							</button>
						</div>

						<div class="syn-episodes__copy">
							<h3 class="syn-episodes__card-title" id="<?php echo esc_attr( $syn_card_id ); ?>-title"><?php echo esc_html( $syn_item['title'] ); ?></h3>

							<?php if ( '' !== $syn_item['note'] ) : ?>
								<p class="syn-episodes__note"><?php echo esc_html( $syn_item['note'] ); ?></p>
							<?php endif; ?>

							<a class="syn-episodes__link" href="<?php echo esc_url( $syn_watch ); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Watch on YouTube', 'synergi' ); ?>
								<span aria-hidden="true">&#8599;</span>
							</a>
						</div>
					</article>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>

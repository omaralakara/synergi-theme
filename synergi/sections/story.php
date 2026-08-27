<?php
/**
 * About section — who we are, then the mission and the vision.
 *
 * Rendered through syn_section( 'story', $args ). Styled by
 * assets/css/sections/story.css. No script: nothing here moves.
 *
 * Expected $args:
 *   eyebrow    string   Small label above the heading.
 *   heading    string   The section's <h2>. Nothing renders if it is empty and
 *                       there are no paragraphs either.
 *   paragraphs string[] The opening story, one entry per paragraph.
 *   pillars    array[]  The statements under it, each:
 *                         title string The card's <h3>.
 *                         body  string One paragraph.
 *                         image int    Optional attachment ID.
 *
 * Example:
 *   syn_section( 'story', array(
 *       'heading'    => 'Welcome to Synergi’s World',
 *       'paragraphs' => array( 'Synergi is a boutique BPO provider…' ),
 *       'pillars'    => array( array( 'title' => 'Our Mission', 'body' => '…' ) ),
 *   ) );
 *
 * The statements alternate side, which story.css does with row-reverse on every
 * second card — a logical direction, so the Arabic phase mirrors it for free
 * (CLAUDE.md §2.11). Nothing in this file knows which side anything is on.
 *
 * Alt text comes from the attachment, never from here (CLAUDE.md §8).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
$syn_heading = trim( (string) ( $args['heading'] ?? '' ) );

$syn_paragraphs = array();

foreach ( (array) ( $args['paragraphs'] ?? array() ) as $syn_text ) {
	$syn_text = trim( (string) ( is_array( $syn_text ) ? '' : $syn_text ) );

	if ( '' !== $syn_text ) {
		$syn_paragraphs[] = $syn_text;
	}
}

/*
 * Resolved before anything is echoed. A card needs words to be a card, and a
 * heading-only card is worse than no card — but there is no way back once the
 * section has opened, so the filtering happens here rather than in the loop.
 */
$syn_pillars = array();

foreach ( (array) ( $args['pillars'] ?? array() ) as $syn_row ) {
	if ( ! is_array( $syn_row ) ) {
		continue;
	}

	$syn_title = trim( (string) ( $syn_row['title'] ?? '' ) );
	$syn_body  = trim( (string) ( $syn_row['body'] ?? '' ) );

	if ( '' === $syn_title || '' === $syn_body ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-section story: a statement needs both a heading and a paragraph, so one row was skipped -->\n";
		}

		continue;
	}

	$syn_pillars[] = array(
		'title' => $syn_title,
		'body'  => $syn_body,
		'image' => (int) ( $syn_row['image'] ?? 0 ),
	);
}

if ( '' === $syn_heading && ! $syn_paragraphs && ! $syn_pillars ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section story: nothing to render -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-story-' );
?>
<section class="syn-story syn-section" id="who-we-are" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-story__intro syn-reveal">
			<div class="syn-story__intro-head">
				<?php if ( '' !== $syn_eyebrow ) : ?>
					<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
				<?php endif; ?>

				<h2 class="syn-story__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>
			</div>

			<?php if ( $syn_paragraphs ) : ?>
				<div class="syn-story__body">
					<?php foreach ( $syn_paragraphs as $syn_text ) : ?>
						<p class="syn-story__paragraph"><?php echo esc_html( $syn_text ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $syn_pillars ) : ?>
			<div class="syn-story__pillars">
				<?php
				foreach ( $syn_pillars as $syn_index => $syn_pillar ) :
					$syn_pillar_id = $syn_uid . '-pillar-' . (int) $syn_index;
					?>
					<article class="syn-story__pillar syn-reveal" aria-labelledby="<?php echo esc_attr( $syn_pillar_id ); ?>">
						<?php if ( $syn_pillar['image'] ) : ?>
							<div class="syn-story__pillar-media">
								<?php
								/*
								 * "large" rather than "full": the card is never
								 * drawn wider than half the container, so the
								 * widest useful crop is around 640px and the
								 * rest of srcset covers narrow screens
								 * (CLAUDE.md §6).
								 */
								echo wp_get_attachment_image(
									$syn_pillar['image'],
									'large',
									false,
									array(
										'class'    => 'syn-story__pillar-image',
										'loading'  => 'lazy',
										'decoding' => 'async',
										'sizes'    => '(max-width: 62rem) 100vw, 38rem',
									)
								);
								?>
							</div>
						<?php endif; ?>

						<div class="syn-story__pillar-copy">
							<h3 class="syn-story__pillar-title" id="<?php echo esc_attr( $syn_pillar_id ); ?>"><?php echo esc_html( $syn_pillar['title'] ); ?></h3>
							<p class="syn-story__pillar-text"><?php echo esc_html( $syn_pillar['body'] ); ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div>
</section>

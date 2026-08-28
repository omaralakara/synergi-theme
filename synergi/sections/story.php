<?php
/**
 * About section — who we are, then the mission and the vision.
 *
 * Rendered through syn_section( 'story', $args ). Styled by
 * assets/css/sections/story.css. No script: the flip is CSS, driven by hover
 * and focus, so the band works with JavaScript switched off.
 *
 * Expected $args:
 *   eyebrow    string   Small label above the heading.
 *   heading    string   The section's <h2>. Nothing renders if it is empty and
 *                       there is no other copy either.
 *   paragraphs string[] The opening story, one entry per paragraph. The FIRST
 *                       is set as the standfirst; the rest become the short
 *                       columns under it.
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
 * THE STATEMENTS ARE FLIP CARDS (28 Aug). Mission and vision are a photograph
 * wearing their name, and the statement itself is on the back — turned over by
 * pointing at the card or tabbing to it. Two reasons this is not decoration:
 * the two statements run to 340 characters each, and printing both in full
 * turned the top of this page into six paragraphs of unbroken prose; and the
 * photographs are the only pictures the band has.
 *
 * BOTH FACES ARE ALWAYS IN THE DOCUMENT. The back is hidden by a rotation, not
 * by display:none, so a screen reader reads the statement whether or not the
 * card has been turned, and a printout carries it. Below the flip's breakpoint —
 * and on any device without a hovering pointer — there is no rotation at all:
 * the picture sits above the words and nothing has to be discovered.
 *
 * The card carries tabindex="0" for the same reason sections/why.php gives its
 * stage one: without it a sighted keyboard user is the only person who cannot
 * reach the back of the card. It is a focusable region, not a control — there
 * is nothing to activate, and the focus ring is base.css's.
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
 * The first paragraph is the standfirst and the rest are the columns under it.
 * Which paragraph leads is a typographic decision, so it is taken here rather
 * than asked of an editor: a "make this one big" checkbox would be a field that
 * sets layout, which is the one thing fields may not do (CLAUDE.md §7c).
 */
$syn_lede  = $syn_paragraphs ? array_shift( $syn_paragraphs ) : '';
$syn_notes = $syn_paragraphs;

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

if ( '' === $syn_heading && '' === $syn_lede && ! $syn_notes && ! $syn_pillars ) {
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

			<?php if ( '' !== $syn_lede ) : ?>
				<p class="syn-story__lede"><?php echo esc_html( $syn_lede ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $syn_notes ) : ?>
			<?php
			/*
			 * The supporting paragraphs, set as columns with a rule over each.
			 * The same words as before, read three at a time instead of as one
			 * measure running down the page.
			 */
			?>
			<div class="syn-story__notes syn-reveal">
				<?php foreach ( $syn_notes as $syn_text ) : ?>
					<p class="syn-story__note"><?php echo esc_html( $syn_text ); ?></p>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $syn_pillars ) : ?>
			<div class="syn-story__pillars">
				<?php
				foreach ( $syn_pillars as $syn_index => $syn_pillar ) :
					$syn_pillar_id = $syn_uid . '-pillar-' . (int) $syn_index;
					?>
					<article
						class="syn-story__pillar syn-reveal"
						tabindex="0"
						aria-labelledby="<?php echo esc_attr( $syn_pillar_id ); ?>"
					>
						<div class="syn-story__pillar-inner">

							<div class="syn-story__pillar-face syn-story__pillar-face--front">
								<?php if ( $syn_pillar['image'] ) : ?>
									<?php
									/*
									 * "large" rather than "full": the card is
									 * never drawn wider than half the container,
									 * so the widest useful crop is around 640px
									 * and the rest of srcset covers narrow
									 * screens (CLAUDE.md §6).
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
								<?php endif; ?>

								<div class="syn-story__pillar-label">
									<h3 class="syn-story__pillar-title" id="<?php echo esc_attr( $syn_pillar_id ); ?>"><?php echo esc_html( $syn_pillar['title'] ); ?></h3>

									<?php
									/*
									 * Shown only where the card can actually
									 * turn — story.css reveals it inside the
									 * same query that enables the flip, so a
									 * phone is never told to hover.
									 */
									?>
									<span class="syn-story__pillar-hint" aria-hidden="true"><?php esc_html_e( 'Hover to read', 'synergi' ); ?></span>
								</div>
							</div>

							<div class="syn-story__pillar-face syn-story__pillar-face--back">
								<?php
								/*
								 * The name again, on the back. Decoration: the
								 * heading on the front is the real one, and
								 * reading it out twice would tell a screen
								 * reader the card has two headings.
								 */
								?>
								<p class="syn-story__pillar-back-title" aria-hidden="true"><?php echo esc_html( $syn_pillar['title'] ); ?></p>
								<p class="syn-story__pillar-text"><?php echo esc_html( $syn_pillar['body'] ); ?></p>
							</div>

						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div>
</section>

<?php
/**
 * Listing section — everything we offer, as a deck of cards.
 *
 * Rendered through syn_section( 'offers', $args ). Styled by
 * assets/css/sections/offers.css. No script: it is a grid of links, and nothing
 * about it needs behaviour.
 *
 * This is the body of the Our Services and Our Solutions listing pages. Both
 * read a site record, so adding a service line or a solution at
 * Settings → Site records puts it on its listing page, on every sibling page's
 * "keep exploring" band and in the menus, with no developer (CLAUDE.md §7a).
 *
 * WHY THIS IS NOT sections/related-services.php. That band is the small
 * "keep exploring" footer — five wordless tiles at the foot of a page a reader
 * has already read. This is a page's whole content, and it has to answer "what
 * is this one?" for someone who arrived knowing nothing: it carries the summary
 * and the icon, and its cards are sized to be read rather than glanced at. Same
 * accents, same colour language, different job — so a second small partial
 * rather than a variant class on the first (CLAUDE.md §4).
 *
 * WHY THIS IS NOT sections/services.php either: that is the homepage's fanned
 * card deck with previous/next controls, arrow-key handling and swipe. A
 * listing page's reader wants all six at once, not three at a time.
 *
 * Expected $args:
 *   eyebrow string  Small label above the heading.
 *   heading string  The section's <h2>.
 *   lede    string  Optional. One or two sentences under the heading.
 *   items   array[] One card each, in order, each:
 *                     name    string Required. Nothing renders without it.
 *                     summary string Optional. One sentence.
 *                     url     string Optional. Without one the card is not a
 *                                    link — see the note in the loop.
 *                     icon    string Optional. An icon name syn_inline_icon()
 *                                    allows; anything else draws no icon.
 *                     accent  string Optional. One of the six accent names in
 *                                    syn_accent_for_index(). Falls back to the
 *                                    slug, so a service card takes its own
 *                                    colour without being told to.
 *                     slug    string Optional. The row's own reference.
 *   empty   string  Optional. Shown in place of the grid when there are no
 *                            items at all.
 *
 * Example:
 *   syn_section( 'offers', array( 'items' => syn_record( 'services' ) ) );
 *
 * The cards are <h3>s inside a <ul>: the listing page's <h1> is the page title
 * and this section's heading is its <h2>, so the levels never skip
 * (CLAUDE.md §8).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'What we do', 'synergi' );
$syn_heading = $args['heading'] ?? __( 'What we offer', 'synergi' );
$syn_lede    = trim( (string) ( $args['lede'] ?? '' ) );
$syn_empty   = trim( (string) ( $args['empty'] ?? '' ) );

$syn_cards = array();

foreach ( (array) ( $args['items'] ?? array() ) as $syn_item ) {
	if ( ! is_array( $syn_item ) ) {
		continue;
	}

	$syn_name = trim( (string) ( $syn_item['name'] ?? '' ) );

	if ( '' === $syn_name ) {
		continue;
	}

	$syn_slug   = sanitize_key( $syn_item['slug'] ?? '' );
	$syn_accent = sanitize_key( $syn_item['accent'] ?? '' );

	$syn_cards[] = array(
		'name'    => $syn_name,
		'summary' => trim( (string) ( $syn_item['summary'] ?? '' ) ),
		'url'     => trim( (string) ( $syn_item['url'] ?? '' ) ),
		'icon'    => sanitize_key( $syn_item['icon'] ?? '' ),

		// Same fallback as the "keep exploring" band, and for the same reason:
		// the six service slugs ARE the six accent names, so a service card
		// needs nothing said about its colour. See inc/sections.php.
		'accent'  => '' !== $syn_accent ? $syn_accent : $syn_slug,
	);
}

$syn_uid = wp_unique_id( 'syn-offers-' );
?>
<section class="syn-offers syn-section" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-offers__head syn-reveal">
			<?php if ( '' !== $syn_eyebrow ) : ?>
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<?php endif; ?>

			<h2 class="syn-offers__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_lede ) : ?>
				<p class="syn-offers__lede"><?php echo esc_html( $syn_lede ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( ! $syn_cards ) : ?>
			<?php
			/*
			 * An empty record is a content problem, not a design one, so the page
			 * says so in the editor's own words rather than rendering a heading
			 * over nothing (CLAUDE.md §7c, §13). SYN_DEBUG says where to fix it.
			 */
			if ( '' !== $syn_empty ) :
				?>
				<p class="syn-offers__empty syn-reveal"><?php echo esc_html( $syn_empty ); ?></p>
				<?php
			endif;

			if ( SYN_DEBUG ) {
				echo "\n<!-- syn-section offers: no items were passed. Fill the record at Settings > Site records. -->\n";
			}
			?>
		<?php else : ?>
			<ul class="syn-offers__grid syn-reveal">
				<?php foreach ( $syn_cards as $syn_card ) : ?>
					<li class="syn-offers__item">
						<?php
						/*
						 * A card with no address is still shown, as plain text.
						 * Hiding a service line because nobody has filled its URL
						 * in yet would make the record quietly wrong rather than
						 * visibly incomplete (CLAUDE.md §13).
						 */
						$syn_tag = '' !== $syn_card['url'] ? 'a' : 'div';
						?>
						<<?php echo esc_html( $syn_tag ); ?>
							class="syn-offers__card"
							data-accent="<?php echo esc_attr( $syn_card['accent'] ); ?>"
							<?php echo '' !== $syn_card['url'] ? 'href="' . esc_url( $syn_card['url'] ) . '"' : ''; ?>
						>
							<?php if ( '' !== $syn_card['icon'] ) : ?>
								<?php syn_inline_icon( $syn_card['icon'], 'syn-offers__icon' ); ?>
							<?php endif; ?>

							<h3 class="syn-offers__name"><?php echo esc_html( $syn_card['name'] ); ?></h3>

							<?php if ( '' !== $syn_card['summary'] ) : ?>
								<p class="syn-offers__summary"><?php echo esc_html( $syn_card['summary'] ); ?></p>
							<?php endif; ?>

							<?php if ( '' !== $syn_card['url'] ) : ?>
								<span class="syn-offers__more">
									<?php esc_html_e( 'Explore', 'synergi' ); ?>
									<span class="syn-offers__arrow" aria-hidden="true">&rarr;</span>
								</span>
							<?php endif; ?>
						</<?php echo esc_html( $syn_tag ); ?>>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

	</div>
</section>

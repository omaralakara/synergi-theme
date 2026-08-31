<?php
/**
 * Service section — the other service lines.
 *
 * Rendered through syn_section( 'related-services', $args ). Styled by
 * assets/css/sections/related-services.css. No script.
 *
 * WHY THIS IS NOT sections/services.php. The homepage already has a services
 * section and reusing it here was the first plan, but it does not fit for two
 * concrete reasons rather than a matter of taste. It is a fanned card deck with
 * previous/next controls, arrow-key handling and swipe — roughly 9 KB of CSS and
 * JS to render what this needs to be: five links in a row. And its cards expect
 * a per-card capability list, which the services record does not carry, so every
 * card would render with an empty bullet list under it. A small partial that
 * renders the record's actual shape is the cheaper and more honest answer.
 *
 * Expected $args:
 *   heading string  The section's <h2>.
 *   eyebrow string  Small label above the heading.
 *   items   array[] Rows from the services record, each:
 *                     name    string Required.
 *                     slug    string Required — the card's own reference.
 *                     accent  string Optional. One of the six accent names
 *                             in syn_accent_for_index(). Falls back to the
 *                             slug, which is how a service card takes its
 *                             own colour without being told to.
 *                     summary string Optional.
 *                     url     string Optional. Without one the card is not a link.
 *
 * Example:
 *   syn_section( 'related-services', array( 'items' => syn_other_services( 'marketing' ) ) );
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'Keep exploring', 'synergi' );
$syn_heading = $args['heading'] ?? __( 'Our other service lines', 'synergi' );
$syn_items   = $args['items'] ?? array();

$syn_clean = array();

foreach ( (array) $syn_items as $syn_item ) {
	if ( ! is_array( $syn_item ) ) {
		continue;
	}

	$syn_name   = trim( (string) ( $syn_item['name'] ?? '' ) );
	$syn_slug   = sanitize_key( $syn_item['slug'] ?? '' );
	$syn_accent = sanitize_key( $syn_item['accent'] ?? '' );

	if ( '' === $syn_name ) {
		continue;
	}

	$syn_clean[] = array(
		'name'   => $syn_name,
		'url'    => trim( (string) ( $syn_item['url'] ?? '' ) ),

		/*
		 * A card with no accent of its own falls back to its slug, which is
		 * how the six service lines keep their own colours: their slugs ARE
		 * the six accent names. Anything else — a solution, a market — either
		 * arrives with an accent already chosen for it or matches none of the
		 * six and takes the brand gradient, which is what the CSS does when
		 * no accent rule applies.
		 */
		'accent' => '' !== $syn_accent ? $syn_accent : $syn_slug,
	);
}

if ( ! $syn_clean ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section related-services: nothing in the services record to link to. Add the service lines at Settings > Site records. -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-related-' );
?>
<section class="syn-related syn-section" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-related__head syn-reveal">
			<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<h2 class="syn-related__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>
		</div>

		<ul class="syn-related__grid syn-reveal">
			<?php foreach ( $syn_clean as $syn_item ) : ?>
				<li class="syn-related__item">
					<?php
					/*
					 * A card with no address is still shown, as plain text. The
					 * alternative is hiding a service line because nobody has
					 * filled its URL in yet, which would make the record quietly
					 * wrong rather than visibly incomplete (CLAUDE.md §13).
					 */
					$syn_tag = '' !== $syn_item['url'] ? 'a' : 'div';
					?>
					<<?php echo esc_html( $syn_tag ); ?>
						class="syn-related__card"
						data-accent="<?php echo esc_attr( $syn_item['accent'] ); ?>"
						<?php echo '' !== $syn_item['url'] ? 'href="' . esc_url( $syn_item['url'] ) . '"' : ''; ?>
					>
						<span class="syn-related__name"><?php echo esc_html( $syn_item['name'] ); ?></span>

						<?php if ( '' !== $syn_item['url'] ) : ?>
							<span class="syn-related__more">
								<?php esc_html_e( 'Explore', 'synergi' ); ?>
								<span class="syn-related__arrow" aria-hidden="true">&rarr;</span>
							</span>
						<?php endif; ?>
					</<?php echo esc_html( $syn_tag ); ?>>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>

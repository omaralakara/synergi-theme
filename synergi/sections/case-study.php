<?php
/**
 * Service section — one proof point.
 *
 * Rendered through syn_section( 'case-study', $args ). Styled by
 * assets/css/sections/case-study.css. No script.
 *
 * Asked for directly by the business: a case study under every service. One per
 * page, stored in the page's own fields rather than as a post type — a study
 * featured on the HR page belongs to the HR page, which is CLAUDE.md §7a's
 * question answered. The full case-study archive is a later stage, and the
 * "read more" link points into it when it exists.
 *
 * Expected $args:
 *   heading string  The headline. Nothing renders if this is empty.
 *   eyebrow string  Small label above the heading.
 *   client  string  The client TYPE, never a client name — see below.
 *   brief   string  The situation, one paragraph.
 *   image   int     Attachment ID for the photograph.
 *   scope   array[] What was delivered, each: item string.
 *   link    array   Optional. url and label.
 *
 * Example:
 *   syn_section( 'case-study', array( 'heading' => 'Building an HR function' ) );
 *
 * ON CLIENT NAMES. The company profile identifies real clients. None of them is
 * cleared for the public site, so this section is built to carry a description
 * of the organisation rather than its name, and the field that feeds it says so
 * where an editor will read it. Nothing here enforces that — it cannot — but
 * nothing here encourages the opposite either.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'Case study', 'synergi' );
$syn_heading = trim( (string) ( $args['heading'] ?? '' ) );
$syn_client  = trim( (string) ( $args['client'] ?? '' ) );
$syn_brief   = trim( (string) ( $args['brief'] ?? '' ) );
$syn_image   = (int) ( $args['image'] ?? 0 );
$syn_link    = (array) ( $args['link'] ?? array() );

// No headline means the page has no proof point yet. The section skips itself
// rather than rendering a band with an empty heading in it (CLAUDE.md §7c).
if ( '' === $syn_heading ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section case-study: no headline, so the section was skipped -->\n";
	}

	return;
}

$syn_scope = array();

foreach ( (array) ( $args['scope'] ?? array() ) as $syn_row ) {
	$syn_item = is_array( $syn_row ) ? trim( (string) ( $syn_row['item'] ?? '' ) ) : '';

	if ( '' !== $syn_item ) {
		$syn_scope[] = $syn_item;
	}
}

$syn_uid = wp_unique_id( 'syn-case-' );
?>
<section class="syn-case syn-section" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container syn-case__inner">

		<?php if ( $syn_image ) : ?>
			<div class="syn-case__media syn-reveal">
				<?php
				/*
				 * Through core, so srcset, sizes and the attachment's own alt
				 * text come free (CLAUDE.md §6, §8). No fetchpriority: this is
				 * well below the fold on every service page.
				 */
				echo wp_get_attachment_image(
					$syn_image,
					'large',
					false,
					array(
						'class'   => 'syn-case__image',
						'loading' => 'lazy',
						'sizes'   => '(max-width: 62rem) 100vw, 40vw',
					)
				);
				?>
			</div>
		<?php endif; ?>

		<div class="syn-case__body syn-reveal">
			<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<h2 class="syn-case__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_client ) : ?>
				<p class="syn-case__client"><?php echo esc_html( $syn_client ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $syn_brief ) : ?>
				<p class="syn-case__brief"><?php echo esc_html( $syn_brief ); ?></p>
			<?php endif; ?>

			<?php if ( $syn_scope ) : ?>
				<ul class="syn-case__scope">
					<?php foreach ( $syn_scope as $syn_item ) : ?>
						<li class="syn-case__scope-item">
							<?php
							/*
							 * Drawn, not a glyph: an inline SVG scales, inherits
							 * currentColor and never depends on a font that may
							 * not have the character (CLAUDE.md §6).
							 */
							?>
							<svg class="syn-case__tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
								<path d="M20 6 9 17l-5-5" />
							</svg>
							<span><?php echo esc_html( $syn_item ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php
			$syn_link_url   = trim( (string) ( $syn_link['url'] ?? '' ) );
			$syn_link_label = trim( (string) ( $syn_link['label'] ?? '' ) );

			if ( '' !== $syn_link_url && '' !== $syn_link_label ) :
				?>
				<p class="syn-case__action">
					<a class="syn-button syn-button--outline" href="<?php echo esc_url( $syn_link_url ); ?>"><?php echo esc_html( $syn_link_label ); ?></a>
				</p>
			<?php endif; ?>
		</div>

	</div>
</section>

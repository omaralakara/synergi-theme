<?php
/**
 * Connect section — the link-in-bio card, the whole of /connect/.
 *
 * Rendered through syn_section( 'connect', $args ) by templates/connect.php, and
 * nowhere else: it owns that page's <h1>, so it must never share a page with
 * parts/page-header.php. Styled by assets/css/sections/connect.css. No script.
 *
 * Expected $args:
 *   logo    int     Attachment ID of the colour logo. Optional — the site name
 *                   is printed as text when it is 0.
 *   heading string  Required. The page's <h1>.
 *   lede    string  Optional. One sentence under the heading.
 *   links   array[] Rows, each:
 *                     label string Required — the words on the button.
 *                     url   string Required — absolute, mailto:, or a path on
 *                                  this site starting with /.
 *
 * Example:
 *   syn_section( 'connect', array(
 *       'heading' => 'Connect with Synergi',
 *       'links'   => array( array( 'label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/synergi-ae/' ) ),
 *   ) );
 *
 * Every button opens in the same tab. The page has nothing to come back to —
 * a visitor who scanned a code wants to arrive at LinkedIn, not collect tabs —
 * and a new tab on a phone is a disorienting jump.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_heading = trim( (string) ( $args['heading'] ?? '' ) );
$syn_lede    = trim( (string) ( $args['lede'] ?? '' ) );
$syn_logo_id = absint( $args['logo'] ?? 0 );

if ( '' === $syn_heading ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section connect: missing required arg \"heading\" -->\n";
	}

	return;
}

$syn_links = array();

foreach ( (array) ( $args['links'] ?? array() ) as $syn_row ) {
	if ( ! is_array( $syn_row ) ) {
		continue;
	}

	$syn_label = trim( (string) ( $syn_row['label'] ?? '' ) );
	$syn_url   = trim( (string) ( $syn_row['url'] ?? '' ) );

	// Both halves or neither: a button that goes nowhere, or one that does not
	// say where it goes, is worse than no button.
	if ( '' === $syn_label || '' === $syn_url ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-section connect: a button needs both words and an address, so one row was skipped -->\n";
		}

		continue;
	}

	$syn_links[] = array(
		'label' => $syn_label,
		'href'  => syn_connect_link_url( $syn_url ),
		'icon'  => syn_connect_icon_slug( $syn_url ),
	);
}
?>
<div class="syn-connect">
	<div class="syn-connect__card">

		<div class="syn-connect__brand">
			<?php
			if ( $syn_logo_id ) {
				/*
				 * medium_large rather than full: the original upload is 1808px
				 * wide for a mark painted at 12rem at most, and the srcset core
				 * attaches still lets a 3x phone pick a sharper copy.
				 */
				echo wp_get_attachment_image(
					$syn_logo_id,
					'medium_large',
					false,
					array(
						'class'         => 'syn-connect__logo',
						'alt'           => get_bloginfo( 'name' ),
						'sizes'         => '12rem',
						'loading'       => 'eager',
						'fetchpriority' => 'high',
					)
				);
			} else {
				?>
				<span class="syn-connect__name"><?php bloginfo( 'name' ); ?></span>
				<?php
			}
			?>
		</div>

		<h1 class="syn-connect__title"><?php echo esc_html( $syn_heading ); ?></h1>

		<?php if ( '' !== $syn_lede ) : ?>
			<p class="syn-connect__lede"><?php echo esc_html( $syn_lede ); ?></p>
		<?php endif; ?>

		<?php if ( $syn_links ) : ?>
			<ul class="syn-connect__list">
				<?php foreach ( $syn_links as $syn_link ) : ?>
					<li>
						<a class="syn-connect__link" href="<?php echo esc_url( $syn_link['href'] ); ?>">
							<?php syn_inline_icon( $syn_link['icon'], 'syn-connect__icon' ); ?>
							<span class="syn-connect__label"><?php echo esc_html( $syn_link['label'] ); ?></span>
							<svg class="syn-connect__chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php elseif ( SYN_DEBUG ) : ?>
			<!-- syn-section connect: no usable buttons -->
		<?php endif; ?>

		<footer class="syn-connect__foot">
			<p class="syn-connect__copyright">
				<?php
				printf(
					/* translators: 1: current year, 2: site name */
					esc_html__( '© %1$s %2$s. All rights reserved.', 'synergi' ),
					esc_html( wp_date( 'Y' ) ),
					esc_html( get_bloginfo( 'name' ) )
				);
				?>
			</p>

			<a class="syn-connect__foot-link" href="<?php echo esc_url( syn_connect_link_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'synergi' ); ?></a>

			<?php // Reopens the consent dialog, as the site footer's button does. Printed hidden; consent.js reveals it. ?>
			<button class="syn-connect__foot-link" type="button" data-syn-consent-open hidden><?php esc_html_e( 'Cookie settings', 'synergi' ); ?></button>
		</footer>

	</div>
</div>

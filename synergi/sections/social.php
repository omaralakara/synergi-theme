<?php
/**
 * Contact section — the company's social accounts.
 *
 * Rendered through syn_section( 'social', $args ). Styled by
 * assets/css/sections/social.css. No script.
 *
 * Expected $args:
 *   eyebrow  string  Small label above the heading.
 *   heading  string  The section's <h2>.
 *   lede     string  One line under it.
 *   accounts array[] Rows from the "social" site record, each:
 *                      network string Required — the platform's name.
 *                      handle  string Optional — the account, e.g. @synergi.
 *                      url     string Required — the profile address.
 *
 * Example:
 *   syn_section( 'social', array( 'accounts' => syn_record( 'social' ) ) );
 *
 * LINKS, NOT EMBEDS. Every account is an outbound link and nothing more. A
 * feed widget on this page would be an external request on every load
 * (CLAUDE.md §2.6), several hundred kilobytes of somebody else's JavaScript,
 * and a third-party cookie on a page whose only job is to let a person get in
 * touch. The homepage already carries the Instagram band for anyone who wants
 * to see the feed itself.
 *
 * The accounts are a site record rather than page fields, because the company's
 * LinkedIn address is a fact about the company and the footer will want it too
 * (CLAUDE.md §7a).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = trim( (string) ( $args['eyebrow'] ?? '' ) );
$syn_heading = trim( (string) ( $args['heading'] ?? '' ) );
$syn_lede    = trim( (string) ( $args['lede'] ?? '' ) );

$syn_accounts = array();

foreach ( (array) ( $args['accounts'] ?? array() ) as $syn_row ) {
	if ( ! is_array( $syn_row ) ) {
		continue;
	}

	$syn_network = trim( (string) ( $syn_row['network'] ?? '' ) );
	$syn_url     = trim( (string) ( $syn_row['url'] ?? '' ) );

	// Both halves or neither: a network with no address is a dead card, and an
	// address with no name is a link that says nothing about where it goes.
	if ( '' === $syn_network || '' === $syn_url ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-section social: an account needs both a network and an address, so one row was skipped -->\n";
		}

		continue;
	}

	$syn_accounts[] = array(
		'network' => $syn_network,
		'handle'  => trim( (string) ( $syn_row['handle'] ?? '' ) ),
		'url'     => $syn_url,
	);
}

if ( ! $syn_accounts ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section social: nothing in the social record. Add the accounts at Settings > Site records. -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-social-' );
?>
<section class="syn-social syn-section" id="social" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-social__head syn-reveal">
			<?php if ( '' !== $syn_eyebrow ) : ?>
				<p class="syn-eyebrow syn-social__eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<?php endif; ?>

			<h2 class="syn-social__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_lede ) : ?>
				<p class="syn-social__lede"><?php echo esc_html( $syn_lede ); ?></p>
			<?php endif; ?>
		</div>

		<ul class="syn-social__list syn-reveal">
			<?php foreach ( $syn_accounts as $syn_account ) : ?>
				<li class="syn-social__item">
					<?php
					/*
					 * rel="noopener" on a target of _blank is not optional: it
					 * is what stops the opened page reaching back into this one
					 * through window.opener.
					 */
					?>
					<a class="syn-social__link" href="<?php echo esc_url( $syn_account['url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<span class="syn-social__network"><?php echo esc_html( $syn_account['network'] ); ?></span>

						<?php if ( '' !== $syn_account['handle'] ) : ?>
							<span class="syn-social__handle"><?php echo esc_html( $syn_account['handle'] ); ?></span>
						<?php endif; ?>

						<span class="syn-social__arrow" aria-hidden="true">&#8599;</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>

	</div>
</section>

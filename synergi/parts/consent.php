<?php
/**
 * The cookie consent dialog.
 *
 * Included by footer.php, just before wp_footer(). Styled by
 * assets/css/parts/consent.css; driven by assets/js/parts/consent.js, which
 * opens it for a visitor who has not chosen yet and reopens it from the
 * footer's "Cookie settings" button. What a choice does to the Google tags is
 * in inc/consent.php; the LinkedIn partner ID comes from inc/integrations.php.
 * Takes no $args.
 *
 * A native <dialog>, centred and modal (changed on request 14 Sep, from a card
 * in the corner). showModal() gives the focus containment, the inert page
 * behind and the top layer above the fixed header for free — no script has to
 * reimplement any of it.
 *
 * Printed for every visitor, closed, and opened by script. The server must
 * never decide whether to open it: LiteSpeed caches one copy of each page for
 * everyone, so a dialog opened or omitted by PHP would be cached that way for
 * all visitors. Without JavaScript it stays closed, which is correct — none of
 * the tags it governs can run without JavaScript either.
 *
 * Accept and Reject are deliberately styled alike. A refusal that looks
 * quieter than acceptance is the design pattern European regulators treat as
 * invalid consent.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_linkedin_partner = syn_linkedin_partner_id();
?>
<!-- syn-part: consent -->
<?php
/*
 * tabindex="-1" lets consent.js put focus on the dialog itself when it opens on
 * a first visit, so a screen reader announces the title and question first.
 * Left to itself, showModal() focuses the first control — the Privacy Policy
 * link, which then sits there wearing the focus ring. The autofocus attribute
 * is meant to do this but Chrome ignores it on a <dialog> (tested 14 Sep).
 */
?>
<dialog
	class="syn-consent"
	aria-labelledby="syn-consent-title"
	aria-describedby="syn-consent-text"
	tabindex="-1"
	data-syn-consent
	data-syn-consent-cookie="<?php echo esc_attr( syn_consent_cookie_name() ); ?>"
	data-syn-consent-max-age="<?php echo esc_attr( syn_consent_max_age() ); ?>"
	<?php if ( '' !== $syn_linkedin_partner ) : ?>
	data-syn-linkedin-partner="<?php echo esc_attr( $syn_linkedin_partner ); ?>"
	<?php endif; ?>
>
	<p class="syn-consent__title" id="syn-consent-title"><?php esc_html_e( 'Your privacy choices', 'synergi' ); ?></p>

	<p class="syn-consent__text" id="syn-consent-text">
		<?php esc_html_e( 'We use cookies to understand how visitors use our website and to measure our advertising. Analytics and marketing cookies are only set if you allow them.', 'synergi' ); ?>
		<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'synergi' ); ?></a>
	</p>

	<fieldset class="syn-consent__choices" id="syn-consent-choices" data-syn-consent-choices hidden>
		<legend class="syn-visually-hidden"><?php esc_html_e( 'Cookie categories', 'synergi' ); ?></legend>

		<div class="syn-consent__option">
			<input type="checkbox" id="syn-consent-necessary" checked disabled>
			<label for="syn-consent-necessary">
				<strong><?php esc_html_e( 'Necessary', 'synergi' ); ?></strong>
				<span><?php esc_html_e( 'Keep the website secure and remember this choice. Always on.', 'synergi' ); ?></span>
			</label>
		</div>

		<div class="syn-consent__option">
			<input type="checkbox" id="syn-consent-analytics" data-syn-consent-category="analytics">
			<label for="syn-consent-analytics">
				<strong><?php esc_html_e( 'Analytics', 'synergi' ); ?></strong>
				<span><?php esc_html_e( 'Google Analytics: which pages are read and how visitors find us.', 'synergi' ); ?></span>
			</label>
		</div>

		<div class="syn-consent__option">
			<input type="checkbox" id="syn-consent-marketing" data-syn-consent-category="marketing">
			<label for="syn-consent-marketing">
				<strong><?php esc_html_e( 'Marketing', 'synergi' ); ?></strong>
				<span><?php esc_html_e( 'Google Ads and LinkedIn: whether our advertising reaches the right people.', 'synergi' ); ?></span>
			</label>
		</div>
	</fieldset>

	<div class="syn-consent__actions">
		<button class="syn-consent__button syn-consent__button--solid" type="button" data-syn-consent-action="accept"><?php esc_html_e( 'Accept all', 'synergi' ); ?></button>
		<button class="syn-consent__button syn-consent__button--solid" type="button" data-syn-consent-action="reject"><?php esc_html_e( 'Reject all', 'synergi' ); ?></button>
		<button class="syn-consent__button syn-consent__button--outline" type="button" aria-expanded="false" aria-controls="syn-consent-choices" data-syn-consent-action="manage"><?php esc_html_e( 'Manage choices', 'synergi' ); ?></button>
		<button class="syn-consent__button syn-consent__button--outline" type="button" data-syn-consent-action="save" hidden><?php esc_html_e( 'Save choices', 'synergi' ); ?></button>
	</div>
</dialog>
<!-- /syn-part: consent -->

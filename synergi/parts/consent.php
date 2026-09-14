<?php
/**
 * The cookie consent banner.
 *
 * Included by footer.php, just before wp_footer(). Styled by
 * assets/css/parts/consent.css; driven by assets/js/parts/consent.js, which
 * shows it to a visitor who has not chosen yet and reopens it from the footer's
 * "Cookie settings" button. What a choice does to the Google tags is in
 * inc/consent.php. Takes no $args.
 *
 * Rendered for every visitor with the hidden attribute, and revealed by script.
 * The server must never decide whether to print it: LiteSpeed caches one copy
 * of each page for everyone, so a banner printed or omitted by PHP would be
 * cached that way for all visitors. Without JavaScript it stays hidden, which
 * is correct — the Google tags cannot run without JavaScript either.
 *
 * Accept and Reject are deliberately styled alike. A refusal that looks
 * quieter than acceptance is the design pattern European regulators treat as
 * invalid consent.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- syn-part: consent -->
<section
	class="syn-consent"
	aria-labelledby="syn-consent-title"
	data-syn-consent
	data-syn-consent-cookie="<?php echo esc_attr( syn_consent_cookie_name() ); ?>"
	data-syn-consent-max-age="<?php echo esc_attr( syn_consent_max_age() ); ?>"
	hidden
>
	<p class="syn-consent__title" id="syn-consent-title"><?php esc_html_e( 'Your privacy choices', 'synergi' ); ?></p>

	<p class="syn-consent__text">
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
				<span><?php esc_html_e( 'Google Ads: whether our advertising reaches the right people.', 'synergi' ); ?></span>
			</label>
		</div>
	</fieldset>

	<div class="syn-consent__actions">
		<button class="syn-button syn-button--primary" type="button" data-syn-consent-action="accept"><?php esc_html_e( 'Accept all', 'synergi' ); ?></button>
		<button class="syn-button syn-button--primary" type="button" data-syn-consent-action="reject"><?php esc_html_e( 'Reject all', 'synergi' ); ?></button>
		<button class="syn-button syn-button--outline" type="button" data-syn-consent-action="save" hidden><?php esc_html_e( 'Save choices', 'synergi' ); ?></button>
		<button class="syn-consent__manage" type="button" aria-expanded="false" aria-controls="syn-consent-choices" data-syn-consent-action="manage"><?php esc_html_e( 'Manage choices', 'synergi' ); ?></button>
	</div>
</section>
<!-- /syn-part: consent -->

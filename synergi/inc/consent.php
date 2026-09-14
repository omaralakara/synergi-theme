<?php
/**
 * Cookie consent — Google Consent Mode v2 defaults, set before any tag runs.
 *
 * Loaded by functions.php. Prints one inline script at the top of <head> that
 * tells every Google tag on the page to start in "denied", then promotes the
 * visitor's stored choice if they have made one. The banner that collects the
 * choice is parts/consent.php, driven by assets/js/parts/consent.js; both read
 * the cookie name and lifetime from the two functions below.
 *
 * Decided 14 Sep: every visitor is asked, whatever their country. The theme,
 * not a plugin, because the whole feature is a banner and a few lines of
 * gtag() — it fails the CLAUDE.md §2.10 "takes more than two days" test.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Name of the cookie that remembers the visitor's choice.
 *
 * Its value reads "analytics:granted|marketing:denied" so it is legible in the
 * browser's storage panel without decoding. The one cookie the banner sets is
 * strictly necessary — without it the banner would ask on every page.
 *
 * @return string Cookie name.
 */
function syn_consent_cookie_name() {
	return 'syn_consent';
}

/**
 * How long a choice is remembered before the visitor is asked again.
 *
 * Six months: long enough not to nag a returning buyer, well inside the
 * thirteen months European regulators treat as the ceiling for a consent
 * cookie.
 *
 * @return int Lifetime in seconds.
 */
function syn_consent_max_age() {
	return 182 * DAY_IN_SECONDS;
}

add_action( 'wp_head', 'syn_print_consent_default', 1 );
/**
 * Prints the Consent Mode default and the stored-choice update.
 *
 * Order is the whole point. A gtag('consent', 'default') call only governs tags
 * that read the dataLayer after it, so this must print before both tags live
 * on production (verified 14 Sep): Site Kit's GT-TXBFKV55, printed by core at
 * wp_head priority 9, and ASE snippet 8607 (G-F8BHKGB935), printed at priority
 * 10. Priority 1 puts it ahead of both, and ahead of the GTM loader in
 * inc/integrations.php should a container ever be set.
 *
 * This is "advanced" Consent Mode: the tags still load, but until consent is
 * granted they set no cookies and send only cookieless pings, which Google uses
 * to model the visits it cannot count. The tags belong to Site Kit and ASE, so
 * blocking them outright would mean the theme dequeuing other plugins' scripts.
 *
 * Site Kit's own Consent Mode setting must stay OFF: it prints a second,
 * EU-only default, which would contradict this one.
 *
 * data-no-optimize keeps LiteSpeed's JS combine (on, including inline scripts,
 * on both sites) from moving this into a combined file further down the page.
 *
 * Side effects: echoes an inline <script> into <head>.
 *
 * @return void
 */
function syn_print_consent_default() {
	$cookie = syn_consent_cookie_name();

	// The stored-choice read is repeated here rather than left to consent.js
	// because that file is deferred: by the time it ran, the tags would already
	// have sent a returning visitor's page view as "denied".
	$script = "window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', {
	ad_storage: 'denied',
	ad_user_data: 'denied',
	ad_personalization: 'denied',
	analytics_storage: 'denied',
	functionality_storage: 'granted',
	security_storage: 'granted',
	wait_for_update: 500
});
gtag('set', 'ads_data_redaction', true);
(function () {
	var match = document.cookie.match(/(?:^|; )" . $cookie . "=([^;]*)/);
	if (!match) { return; }
	var analytics = match[1].indexOf('analytics:granted') !== -1 ? 'granted' : 'denied';
	var marketing = match[1].indexOf('marketing:granted') !== -1 ? 'granted' : 'denied';
	gtag('consent', 'update', {
		analytics_storage: analytics,
		ad_storage: marketing,
		ad_user_data: marketing,
		ad_personalization: marketing
	});
}());";

	wp_print_inline_script_tag(
		$script,
		array(
			'id'               => 'syn-consent-default',
			'data-no-optimize' => '1',
		)
	);
}

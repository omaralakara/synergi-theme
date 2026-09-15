<?php
/**
 * The language switch in the header.
 *
 * Included by header.php after parts/nav.php and before the menu toggle, so
 * Tab reaches the brand, the navigation, then the language, then the toggle.
 * Styled by assets/css/parts/header.css (§8). Needs no JavaScript.
 *
 * Expects no $args. Renders nothing at all until Polylang has a second
 * language, so the English-only site never carries an empty <nav>.
 *
 * One link per other language, never a list of every language with the
 * current one greyed out: with two languages that is one link, which is what
 * the header has room for. Each link is labelled in its own language and
 * script — "العربية" on the English site, "English" on the Arabic — because a
 * visitor looking for their language should not have to read the other one to
 * find it. The link carries lang and hreflang so the browser and assistive
 * technology pronounce and announce it correctly, and it points at the
 * translation of the page being read when one exists (inc/i18n.php).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_targets = syn_language_switch_targets();

if ( ! $syn_targets ) {
	return;
}
?>
<!-- syn-part: lang-switch -->
<nav class="syn-lang" aria-label="<?php esc_attr_e( 'Language', 'synergi' ); ?>">
	<?php foreach ( $syn_targets as $syn_target ) : ?>
		<a
			class="syn-lang__link"
			href="<?php echo esc_url( $syn_target['url'] ); ?>"
			lang="<?php echo esc_attr( $syn_target['slug'] ); ?>"
			hreflang="<?php echo esc_attr( $syn_target['slug'] ); ?>"
			dir="<?php echo $syn_target['rtl'] ? 'rtl' : 'ltr'; ?>"
		><?php echo esc_html( $syn_target['name'] ); ?></a>
	<?php endforeach; ?>
</nav>
<!-- /syn-part: lang-switch -->

<?php
/**
 * The field group that feeds templates/connect.php, and the two helpers its
 * section needs to turn a stored address into a link and an icon.
 *
 * Loaded by functions.php after inc/fields.php, whose engine this uses and does
 * not extend. Read by sections/connect.php.
 *
 * /connect/ is the link-in-bio page a printed QR code points at (decided 1 Sep,
 * stage-7-decisions.md §3). Until 16 Sep it was still stored as Elementor
 * markup, and with Elementor retired it rendered as unstyled text inside the
 * site chrome, with two <h1>s. This replaces that with a card of buttons and
 * nothing around it.
 *
 * THE LINKS ARE POSTMETA, NOT THE SOCIAL RECORD. The social record is every
 * account the company keeps; this page is a hand-picked short list that also
 * carries the contact page and the website, which are not accounts at all. A
 * change to it should change this page and no other, which is CLAUDE.md §7a's
 * test for page fields.
 *
 * THE ICON IS READ FROM THE ADDRESS, NOT CHOSEN. A LinkedIn address gets the
 * LinkedIn mark and a page on this site gets the globe, so an editor adding a
 * row cannot pick a mark that lies about where the button goes.
 *
 * The helpers live here rather than in inc/sections.php because only this page
 * uses them, and because production runs a release behind the repo
 * (40bd840): keeping the page to its own files is what lets it ship without
 * the Arabic release.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The template the group below is scoped to. */
define( 'SYN_CONNECT_TEMPLATE', 'templates/connect.php' );

add_action( 'syn_register_fields', 'syn_register_connect_fields' );
/**
 * Registers the one field group the connect page carries.
 *
 * Side effects: registers a field group on templates/connect.php.
 *
 * @return void
 */
function syn_register_connect_fields() {
	syn_register_field_group(
		array(
			'id'          => 'connect_card',
			'title'       => __( 'Connect — the card', 'synergi' ),
			'description' => __( 'The whole page: the logo, the heading, one sentence and the buttons. The page has no header or footer, on purpose — it is what a printed QR code opens.', 'synergi' ),
			'templates'   => array( SYN_CONNECT_TEMPLATE ),
			'fields'      => array(
				array(
					'key'           => 'connect_logo',
					'type'          => 'image',
					'label'         => __( 'Logo', 'synergi' ),
					'description'   => __( 'The colour logo, for a white background. The site logo is white, which is why this page has its own.', 'synergi' ),
					'fallback_slug' => 'original-logo',
				),
				array(
					/*
					 * A heading field rather than the page title, because the
					 * page title is also what the browser tab and the QR code's
					 * preview show ("Connect with Synergi | Official Links"), and
					 * the card wants the shorter line.
					 */
					'key'        => 'connect_heading',
					'type'       => 'text',
					'label'      => __( 'Heading', 'synergi' ),
					'default'    => __( 'Connect with Synergi', 'synergi' ),
					'max_length' => 60,
				),
				array(
					'key'        => 'connect_lede',
					'type'       => 'textarea',
					'label'      => __( 'Sentence under the heading', 'synergi' ),
					'default'    => __( 'Follow our latest updates and learn more about our services.', 'synergi' ),
					'max_length' => 160,
					'rows'       => 2,
				),
				array(
					'key'         => 'connect_links',
					'type'        => 'repeater',
					'label'       => __( 'Buttons', 'synergi' ),
					'description' => __( 'In the order they appear. The icon follows the address: LinkedIn, Instagram, Facebook and YouTube get their own mark, an email address or the contact page gets an envelope, anything else a globe.', 'synergi' ),
					'row_noun'    => __( 'Button', 'synergi' ),
					'button'      => __( 'Add button', 'synergi' ),
					'row_label'   => 'label',
					'min_rows'    => 1,
					'max_rows'    => 8,
					'default'     => array(
						array(
							'label' => __( 'LinkedIn', 'synergi' ),
							'url'   => 'https://www.linkedin.com/company/synergi-ae/',
						),
						array(
							'label' => __( 'Instagram', 'synergi' ),
							'url'   => 'https://www.instagram.com/synergi.bpo/',
						),
						array(
							'label' => __( 'Contact Synergi', 'synergi' ),
							'url'   => '/contact-us/',
						),
						array(
							'label' => __( 'Visit Our Website', 'synergi' ),
							'url'   => '/',
						),
					),
					'subfields'   => array(
						array(
							'key'        => 'label',
							'type'       => 'text',
							'label'      => __( 'Words on the button', 'synergi' ),
							'max_length' => 40,
						),
						array(
							'key'         => 'url',
							'type'        => 'url',
							'label'       => __( 'Address', 'synergi' ),
							'description' => __( 'A full address for another site, or a path starting with / for a page on this one, e.g. /contact-us/.', 'synergi' ),
							'placeholder' => 'https://',
						),
					),
				),
			),
		)
	);
}

/**
 * Turns a stored button address into the address to print.
 *
 * A path on this site goes through syn_local_url() where the language layer
 * exists, so an Arabic copy of the page would link to Arabic pages; on a
 * release without inc/i18n.php it goes through home_url(), which is what that
 * function does for the default language anyway (CLAUDE.md §12: never assume
 * a domain).
 *
 * @param string $url Stored address: absolute, mailto:, or a root-relative path.
 * @return string Raw URL — escape with esc_url() where it is printed.
 */
function syn_connect_link_url( $url ) {
	$url = trim( (string) $url );

	if ( '' === $url || '/' !== $url[0] || 0 === strpos( $url, '//' ) ) {
		return $url;
	}

	return function_exists( 'syn_local_url' ) ? syn_local_url( $url ) : home_url( $url );
}

/**
 * The icon a connect button carries, read from where it goes.
 *
 * @param string $url Stored address, as syn_connect_link_url() receives it.
 * @return string An icon slug syn_inline_icon() allows.
 */
function syn_connect_icon_slug( $url ) {
	$url  = strtolower( trim( (string) $url ) );
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );

	$networks = array(
		'linkedin.com'  => 'social-linkedin',
		'instagram.com' => 'social-instagram',
		'facebook.com'  => 'social-facebook',
		'youtube.com'   => 'social-youtube',
	);

	foreach ( $networks as $domain => $slug ) {
		if ( $host === $domain || str_ends_with( $host, '.' . $domain ) ) {
			return $slug;
		}
	}

	if ( 0 === strpos( $url, 'mailto:' ) || false !== strpos( $url, 'contact' ) ) {
		return 'connect-email';
	}

	return 'connect-website';
}

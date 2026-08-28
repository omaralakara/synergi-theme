<?php
/**
 * The social site record and the field groups that feed templates/contact.php.
 *
 * Loaded by functions.php after inc/records.php and inc/fields.php, whose
 * engines this uses and does not extend. A sibling of inc/service-fields.php,
 * inc/solution-fields.php and inc/market-fields.php.
 *
 * WHAT IS HERE AND WHAT IS NOT. Contact Us shows three things: every office
 * with its address and how to reach it, an enquiry form, and the company's
 * social accounts. Only the headings around them are page fields. The offices
 * are the "locations" record and the accounts are the "social" record, because
 * an office address and a LinkedIn URL are facts about the business that the
 * footer, Global Locations and the market pages will all want, and typing them
 * twice is how they come to disagree (CLAUDE.md §7a).
 *
 * The form is neither. It is a shortcode from the consolidated form plugin,
 * because a form is validation, spam handling, storage, notification and a CRM
 * connection through Bit Integrations — none of which belongs in a theme
 * (CLAUDE.md §11). The shortcode is a field so that swapping the form, or the
 * plugin, is an edit rather than a deploy.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The template every field group below is scoped to. */
define( 'SYN_CONTACT_TEMPLATE', 'templates/contact.php' );

add_action( 'syn_register_records', 'syn_register_contact_records' );
/**
 * Registers the "social" site record.
 *
 * The last of the three records stage-6-scope.md deferred in 6a. It is
 * registered from this file rather than from inc/records.php, which fires the
 * "syn_register_records" action precisely so a record can live next to the
 * pages that read it.
 *
 * Side effects: registers one site record.
 *
 * @return void
 */
function syn_register_contact_records() {

	syn_register_record(
		array(
			'id'          => 'social',
			'title'       => __( 'Social accounts', 'synergi' ),
			'description' => __( 'The company’s accounts on other platforms. The order here is the order they appear in. Add only accounts that are actually maintained — a dead profile linked from the contact page is worse than no link.', 'synergi' ),
			'read_by'     => __( 'Contact Us, and the footer once it is wired.', 'synergi' ),
			'row_noun'    => __( 'Account', 'synergi' ),
			'button'      => __( 'Add account', 'synergi' ),
			'row_label'   => 'network',
			'min_rows'    => 1,
			'max_rows'    => 12,
			'fields'      => array(
				array(
					'key'         => 'network',
					'type'        => 'text',
					'label'       => __( 'Platform', 'synergi' ),
					'description' => __( 'As people call it, e.g. LinkedIn.', 'synergi' ),
					'max_length'  => 40,
				),
				array(
					'key'         => 'handle',
					'type'        => 'text',
					'label'       => __( 'Account name', 'synergi' ),
					'description' => __( 'Optional, e.g. @synergibpo. Shown under the platform’s name.', 'synergi' ),
					'max_length'  => 80,
				),
				array(
					'key'         => 'url',
					'type'        => 'url',
					'label'       => __( 'Profile address', 'synergi' ),
					'description' => __( 'The full address, starting with https://.', 'synergi' ),
					'placeholder' => 'https://www.linkedin.com/company/',
				),
			),
		)
	);
}

add_action( 'syn_register_fields', 'syn_register_contact_fields' );
/**
 * Registers the four field groups a contact page carries.
 *
 * One group per band, in the order the bands appear down the page.
 *
 * Side effects: registers four field groups on templates/contact.php.
 *
 * @return void
 */
function syn_register_contact_fields() {

	/*
	 * 1. INTRO — the band at the top. No heading field: the page title is the
	 * <h1> (CLAUDE.md §8), and the SEO title is Yoast's.
	 */
	syn_register_field_group(
		array(
			'id'          => 'contact_intro',
			'title'       => __( 'Contact — intro', 'synergi' ),
			'description' => __( 'The band at the top of the page. The page title is the heading, so it is not repeated here.', 'synergi' ),
			'templates'   => array( SYN_CONTACT_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'contact_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Contact', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'contact_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => __( 'For general enquiries, proposals and career conversations. We answer from the office closest to the work.', 'synergi' ),
					'rows'       => 3,
					'max_length' => 320,
				),
				array(
					'key'         => 'contact_image',
					'type'        => 'image',
					'label'       => __( 'Hero photograph', 'synergi' ),
					'description' => __( 'Optional. Without one the band stays on the flat navy.', 'synergi' ),
				),
			),
		)
	);

	/*
	 * 2. OFFICES — the headings only. The offices themselves are the locations
	 * record, edited once at Settings → Site records for the reason in the file
	 * header.
	 */
	syn_register_field_group(
		array(
			'id'          => 'contact_offices',
			'title'       => __( 'Contact — offices', 'synergi' ),
			'description' => __( 'The wording around the office cards. The offices, their addresses, emails and phone numbers are edited once at Settings → Site records, because Global Locations and the footer show the same ones.', 'synergi' ),
			'templates'   => array( SYN_CONTACT_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'contact_offices_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Where we are', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'contact_offices_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Our offices', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'contact_offices_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => __( 'Delivery runs from centres onshore and offshore. Each office answers for the work closest to it.', 'synergi' ),
					'rows'       => 3,
					'max_length' => 320,
				),
			),
		)
	);

	/*
	 * 3. ENQUIRY — the form band.
	 *
	 * The shortcode has no default. A guessed form id would either render
	 * somebody else's form or render the shortcode as literal text on the page,
	 * and both are worse than the band skipping itself until an editor pastes
	 * the right one.
	 */
	syn_register_field_group(
		array(
			'id'          => 'contact_enquiry',
			'title'       => __( 'Contact — enquiry form', 'synergi' ),
			'description' => __( 'The form band. Paste the form’s shortcode from the forms plugin; leave it empty and the whole band is skipped.', 'synergi' ),
			'templates'   => array( SYN_CONTACT_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'contact_form_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Send us a message', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'contact_form_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Tell us what you are trying to fix', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'contact_form_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => __( 'A sentence about the problem is more useful to us than a form filled in perfectly. Someone who can answer it will reply.', 'synergi' ),
					'rows'       => 3,
					'max_length' => 320,
				),
				array(
					'key'         => 'contact_form_note',
					'type'        => 'textarea',
					'label'       => __( 'Small print', 'synergi' ),
					'description' => __( 'Optional. Shown under the opening sentence, e.g. how long a reply takes.', 'synergi' ),
					'default'     => '',
					'rows'        => 2,
					'max_length'  => 240,
				),
				array(
					'key'         => 'contact_form_shortcode',
					'type'        => 'text',
					'label'       => __( 'Form shortcode', 'synergi' ),
					'description' => __( 'From the forms plugin, e.g. [wpforms id="7560"]. The band does not render without it.', 'synergi' ),
					'default'     => '',
					'max_length'  => 200,
					'placeholder' => '[wpforms id="0"]',
				),
			),
		)
	);

	/*
	 * 4. SOCIAL — the headings only, for the same reason as the offices.
	 */
	syn_register_field_group(
		array(
			'id'          => 'contact_social',
			'title'       => __( 'Contact — social accounts', 'synergi' ),
			'description' => __( 'The wording around the account cards. The accounts themselves are edited once at Settings → Site records.', 'synergi' ),
			'templates'   => array( SYN_CONTACT_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'contact_social_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Elsewhere', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'contact_social_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Follow Synergi', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'contact_social_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => '',
					'rows'       => 3,
					'max_length' => 320,
				),
			),
		)
	);
}

<?php
/**
 * The Global Locations page's own copy.
 *
 * Loaded by functions.php, after inc/fields.php. Read by
 * templates/global-locations.php.
 *
 * ONE GROUP, AND NO LIST OF OFFICES IN IT. The offices themselves live in the
 * "locations" site record at Settings → Site records, because the homepage,
 * Contact Us and this page all show them and they must never disagree
 * (CLAUDE.md §7a). Everything below is the words around the two bands — an
 * editor changes the sentence, never the addresses, and never in two places.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The template this group is scoped to. */
define( 'SYN_GLOBAL_LOCATIONS_TEMPLATE', 'templates/global-locations.php' );

add_action( 'syn_register_fields', 'syn_register_global_locations_fields' );
/**
 * Registers the one field group the Global Locations page carries.
 *
 * Side effects: registers one field group on templates/global-locations.php.
 *
 * @return void
 */
function syn_register_global_locations_fields() {

	syn_register_field_group(
		array(
			'id'          => 'locations_page',
			'title'       => __( 'Global Locations — the page', 'synergi' ),
			'description' => __( 'The words around the two bands. The offices themselves — their addresses, phone numbers, flags and photographs — are at Settings → Site records, because Contact Us and the homepage show the same ones.', 'synergi' ),
			'templates'   => array( SYN_GLOBAL_LOCATIONS_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'locations_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Where we work', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'         => 'locations_lede',
					'type'        => 'textarea',
					'label'       => __( 'Opening sentence', 'synergi' ),
					'description' => __( 'One or two sentences under the page title.', 'synergi' ),
					'default'     => __( 'A regional footprint with unified leadership and dedicated local teams, delivering consistent support across every market we serve.', 'synergi' ),
					'rows'        => 3,
					'max_length'  => 320,
				),
				array(
					'key'         => 'locations_image',
					'type'        => 'image',
					'label'       => __( 'Hero photograph', 'synergi' ),
					'description' => __( 'Optional. Fills the band behind the page title. Without one the band stays on the flat navy.', 'synergi' ),
				),

				/*
				 * 1. The photographic band, the homepage's own.
				 */
				array(
					'key'        => 'locations_map_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Photograph band — eyebrow', 'synergi' ),
					'default'    => __( 'Our delivery network', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'locations_map_heading',
					'type'       => 'text',
					'label'      => __( 'Photograph band — heading', 'synergi' ),
					'default'    => __( 'Our locations', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'locations_map_lede',
					'type'       => 'textarea',
					'label'      => __( 'Photograph band — sentence', 'synergi' ),
					'default'    => __( 'Onshore and offshore delivery centres across the GCC and beyond, coordinated from one leadership team.', 'synergi' ),
					'rows'       => 3,
					'max_length' => 320,
				),
				/*
				 * The link every photograph carries. It exists as a field for a
				 * concrete reason rather than for flexibility: the band defaults
				 * that link to /global-locations/, which is this page, so left
				 * alone every card on the page would link to the page the reader
				 * is already on. Empty here falls back to the contact page.
				 */
				array(
					'key'         => 'locations_card_link',
					'type'        => 'link',
					'label'       => __( 'Where a location card goes', 'synergi' ),
					'description' => __( 'Leave the address empty and the cards point at the contact page, which is the sensible destination from here. They must not point back at this page.', 'synergi' ),
					'default'     => array(
						'url'   => '',
						'label' => __( 'Talk to our team', 'synergi' ),
					),
				),

				/*
				 * 2. The address cards, Contact Us's own band.
				 */
				array(
					'key'        => 'locations_offices_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Address band — eyebrow', 'synergi' ),
					'default'    => __( 'Find us', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'locations_offices_heading',
					'type'       => 'text',
					'label'      => __( 'Address band — heading', 'synergi' ),
					'default'    => __( 'Our offices', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'         => 'locations_offices_lede',
					'type'        => 'textarea',
					'label'       => __( 'Address band — sentence', 'synergi' ),
					'description' => __( 'Optional.', 'synergi' ),
					'default'     => '',
					'rows'        => 2,
					'max_length'  => 240,
				),
			),
		)
	);
}

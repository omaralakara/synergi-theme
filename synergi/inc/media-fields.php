<?php
/**
 * The Media hub's own copy.
 *
 * Loaded by functions.php, after inc/fields.php. Read by templates/media.php.
 *
 * ONE GROUP, AND NOTHING IN IT LISTS AN ARTICLE. The blog band queries the
 * newest posts itself and links to /blog/ for the rest, so publishing changes
 * this page with no edit anywhere. The fields below are the words around the
 * two bands and nothing else.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The template this group is scoped to. */
define( 'SYN_MEDIA_TEMPLATE', 'templates/media.php' );

add_action( 'syn_register_fields', 'syn_register_media_fields' );
/**
 * Registers the one field group the Media hub carries.
 *
 * Side effects: registers one field group on templates/media.php.
 *
 * @return void
 */
function syn_register_media_fields() {

	syn_register_field_group(
		array(
			'id'          => 'media_page',
			'title'       => __( 'Media — the page', 'synergi' ),
			'description' => __( 'The words around the two bands. The articles come from the blog itself and the pictures from the Instagram feed, so neither needs listing here.', 'synergi' ),
			'templates'   => array( SYN_MEDIA_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'media_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Media', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'         => 'media_lede',
					'type'        => 'textarea',
					'label'       => __( 'Opening sentence', 'synergi' ),
					'description' => __( 'One or two sentences under the page title.', 'synergi' ),
					'default'     => __( 'What our teams are writing, and what we are up to. The podcast and our case studies sit alongside this in the menu above.', 'synergi' ),
					'rows'        => 3,
					'max_length'  => 320,
				),
				array(
					'key'         => 'media_image',
					'type'        => 'image',
					'label'       => __( 'Hero photograph', 'synergi' ),
					'description' => __( 'Optional. Fills the band behind the page title. Without one the band stays on the flat navy.', 'synergi' ),
				),

				/*
				 * 1. The articles.
				 */
				array(
					'key'        => 'media_blog_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Articles band — eyebrow', 'synergi' ),
					'default'    => __( 'Insights & analysis from our teams', 'synergi' ),
					'max_length' => 60,
				),
				array(
					'key'        => 'media_blog_heading',
					'type'       => 'text',
					'label'      => __( 'Articles band — heading', 'synergi' ),
					'default'    => __( 'Read our blog', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'media_blog_link_text',
					'type'       => 'text',
					'label'      => __( 'Articles band — button', 'synergi' ),
					'default'    => __( 'View all articles', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'         => 'media_blog_link_url',
					'type'        => 'url',
					'label'       => __( 'Articles band — button address', 'synergi' ),
					'description' => __( 'Leave it empty and the button points at whichever page is set to Posts in Settings → Reading, which is better than typing a path that could go stale.', 'synergi' ),
					'default'     => '',
					'placeholder' => '/blog/',
				),

				/*
				 * 2. The social feed.
				 */
				array(
					'key'        => 'media_social_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Social band — eyebrow', 'synergi' ),
					'default'    => __( 'Life at Synergi', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'media_social_heading',
					'type'       => 'text',
					'label'      => __( 'Social band — heading', 'synergi' ),
					'default'    => __( 'Recent from Instagram', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'media_social_link_text',
					'type'       => 'text',
					'label'      => __( 'Social band — button', 'synergi' ),
					'default'    => __( 'Follow Synergi', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'         => 'media_social_link_url',
					'type'        => 'url',
					'label'       => __( 'Social band — button address', 'synergi' ),
					'description' => __( 'The profile the button opens.', 'synergi' ),
					'default'     => 'https://www.instagram.com/synergi.bpo',
				),
			),
		)
	);
}

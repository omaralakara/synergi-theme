<?php
/**
 * The field groups that feed templates/podcast.php.
 *
 * Loaded by functions.php after inc/fields.php, whose engine this uses and does
 * not extend. A sibling of inc/service-fields.php and the rest.
 *
 * NO SITE RECORD HERE. The episodes belong to this page and nowhere else, so
 * they are postmeta (CLAUDE.md §7a). That changes the day podcast episodes
 * become the post type the architecture calls for — each episode with its own
 * URL, its own transcript and its own guest — at which point this repeater is
 * replaced by a query and these rows are migrated into it. Until then a
 * repeater is honest about what the page actually is: a list of links to
 * YouTube.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The template every group below is scoped to. */
define( 'SYN_PODCAST_TEMPLATE', 'templates/podcast.php' );

/**
 * The subfields a video row carries, shared by the episodes and the webinars.
 *
 * Written once and used twice, because the two bands are the same thing with
 * different headings, and two copies would drift the first time one of them
 * gained a field.
 *
 * @return array[] Subfield definitions.
 */
function syn_podcast_video_subfields() {
	return array(
		array(
			'key'        => 'title',
			'type'       => 'text',
			'label'      => __( 'Title', 'synergi' ),
			'max_length' => 160,
		),
		array(
			'key'         => 'url',
			'type'        => 'url',
			'label'       => __( 'YouTube address', 'synergi' ),
			'description' => __( 'Paste the address from YouTube — any form works, including the share link. A row whose address has no video in it is skipped.', 'synergi' ),
			'placeholder' => 'https://youtu.be/',
		),
		array(
			'key'         => 'note',
			'type'        => 'text',
			'label'       => __( 'One line', 'synergi' ),
			'description' => __( 'Optional. The guest, the subject, or the date.', 'synergi' ),
			'max_length'  => 160,
		),
		array(
			'key'         => 'image',
			'type'        => 'image',
			'label'       => __( 'Cover picture', 'synergi' ),
			'description' => __( 'Optional. Shown until somebody presses play. Without one the card uses the brand gradient — YouTube’s own thumbnail is deliberately not fetched, because that would load from YouTube on every visit.', 'synergi' ),
		),
	);
}

add_action( 'syn_register_fields', 'syn_register_podcast_fields' );
/**
 * Registers the four field groups a podcast page carries.
 *
 * Side effects: registers four field groups on templates/podcast.php.
 *
 * @return void
 */
function syn_register_podcast_fields() {

	/*
	 * 1. INTRO. No heading field: the page title is the <h1>, and the SEO title
	 * is Yoast's. The photograph is the Featured Image, as on every other
	 * template — one picture, one owner.
	 */
	syn_register_field_group(
		array(
			'id'          => 'podcast_intro',
			'title'       => __( 'Podcast — intro', 'synergi' ),
			'description' => __( 'The band at the top. The page title is the heading and the photograph is the Featured Image in the sidebar.', 'synergi' ),
			'templates'   => array( SYN_PODCAST_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'podcast_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Media', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'podcast_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => __( 'The first BPO-led discussion platform: senior leaders on how leadership, strategy and operations align to drive business impact across the MENA region.', 'synergi' ),
					'rows'       => 3,
					'max_length' => 320,
				),
				array(
					'key'     => 'podcast_cta',
					'type'    => 'link',
					'label'   => __( 'Button', 'synergi' ),
					'default' => array(
						'url'   => '',
						'label' => '',
					),
				),
			),
		)
	);

	/*
	 * 2. ABOUT — rendered through the About Us story band, which is a heading
	 * over a standfirst and short columns. Exactly this shape, already built.
	 */
	syn_register_field_group(
		array(
			'id'          => 'podcast_about',
			'title'       => __( 'Podcast — about the series', 'synergi' ),
			'description' => __( 'What the series is. The first paragraph is set larger, as the opening line.', 'synergi' ),
			'templates'   => array( SYN_PODCAST_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'podcast_about_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'The series', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'podcast_about_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'About the Executive Podcast', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'       => 'podcast_about_paragraphs',
					'type'      => 'repeater',
					'label'     => __( 'Paragraphs', 'synergi' ),
					'row_noun'  => __( 'Paragraph', 'synergi' ),
					'button'    => __( 'Add paragraph', 'synergi' ),
					'row_label' => 'text',
					'min_rows'  => 1,
					'max_rows'  => 8,
					'default'   => array(),
					'subfields' => array(
						array(
							'key'   => 'text',
							'type'  => 'textarea',
							'label' => __( 'Paragraph', 'synergi' ),
							'rows'  => 4,
						),
					),
				),
			),
		)
	);

	/*
	 * 3 and 4. THE TWO VIDEO BANDS. Same fields, different headings — which is
	 * why the subfields are declared once above and used twice here.
	 */
	syn_register_field_group(
		array(
			'id'          => 'podcast_episodes',
			'title'       => __( 'Podcast — episodes', 'synergi' ),
			'description' => __( 'One row per episode, newest first. Each becomes a card that plays where it sits; nothing loads from YouTube until somebody presses play.', 'synergi' ),
			'templates'   => array( SYN_PODCAST_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'podcast_episodes_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Listen', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'podcast_episodes_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Podcast episodes', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'podcast_episodes_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => '',
					'rows'       => 2,
					'max_length' => 320,
				),
				array(
					'key'       => 'podcast_episodes',
					'type'      => 'repeater',
					'label'     => __( 'Episodes', 'synergi' ),
					'row_noun'  => __( 'Episode', 'synergi' ),
					'button'    => __( 'Add episode', 'synergi' ),
					'row_label' => 'title',
					'min_rows'  => 1,
					'max_rows'  => 60,
					'default'   => array(),
					'subfields' => syn_podcast_video_subfields(),
				),
			),
		)
	);

	syn_register_field_group(
		array(
			'id'          => 'podcast_webinars',
			'title'       => __( 'Podcast — webinars', 'synergi' ),
			'description' => __( 'The same, for webinars. Leave it empty and the band does not appear.', 'synergi' ),
			'templates'   => array( SYN_PODCAST_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'podcast_webinars_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Watch', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'podcast_webinars_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Webinars', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'podcast_webinars_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => '',
					'rows'       => 2,
					'max_length' => 320,
				),
				array(
					'key'       => 'podcast_webinars',
					'type'      => 'repeater',
					'label'     => __( 'Webinars', 'synergi' ),
					'row_noun'  => __( 'Webinar', 'synergi' ),
					'button'    => __( 'Add webinar', 'synergi' ),
					'row_label' => 'title',
					'min_rows'  => 1,
					'max_rows'  => 60,
					'default'   => array(),
					'subfields' => syn_podcast_video_subfields(),
				),
			),
		)
	);
}

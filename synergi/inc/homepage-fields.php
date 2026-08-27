<?php
/**
 * The homepage's own editable copy — Stage 6b.
 *
 * Loaded by functions.php. Registers one field group per homepage band, all
 * scoped to templates/homepage.php, and read by that template which hands the
 * values down to the section partials.
 *
 * WHY THESE ARE FIELDS AND NOT RECORDS
 *
 * CLAUDE.md §7a's test is "if this changes, how many pages should change with
 * it?". Every band below renders on the homepage and nowhere else, so the
 * answer is one, and one page means postmeta. The four bands that answered
 * "seven" — the figures, the why band, the closing call to action and the
 * locations — are site records instead, in inc/records.php, and are not
 * repeated here.
 *
 * HOW THE HOMEPAGE STAYS EXACTLY AS APPROVED
 *
 * Every field's registered default is the string the partial already printed.
 * syn_field() returns that default whenever the stored value is empty, so a
 * homepage with nothing typed into it renders byte for byte what it rendered
 * before Stage 6b — verified, not assumed. Clearing a box does not empty the
 * page; it restores the approved wording. The admin says so under each field.
 *
 * WHAT IS DELIBERATELY NOT HERE
 *
 * The hero photograph is the page's Featured Image and always has been, so it
 * is already editable and adding a second control for it would give one picture
 * two owners.
 *
 * The shared-services hub — its six bubbles, the middle one and the numbered
 * delivery steps — is a diagram, not copy. CLAUDE.md §7c: fields carry copy and
 * pictures, never layout. Its heading, paragraphs, markets, benefit chips and
 * button are here; the shape of the picture is not.
 *
 * The blog band's post count and excerpt length are tuning, not words, and stay
 * in the partial for the same reason.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The template every group below is scoped to. */
define( 'SYN_HOMEPAGE_TEMPLATE', 'templates/homepage.php' );

add_action( 'syn_register_fields', 'syn_register_homepage_fields' );
/**
 * Registers the homepage's field groups.
 *
 * One group per band, in the order the bands appear down the page, so the edit
 * screen reads in the same order as the homepage does.
 *
 * Side effects: registers eleven field groups.
 *
 * @return void
 */
function syn_register_homepage_fields() {

	/* 01 — HERO */
	syn_register_field_group(
		array(
			'id'          => 'home_hero',
			'title'       => __( 'Homepage 01 — Hero', 'synergi' ),
			'description' => __( 'The band at the top. The photograph behind it is this page’s Featured Image, set in the sidebar.', 'synergi' ),
			'templates'   => array( SYN_HOMEPAGE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'         => 'home_hero_title_lead',
					'type'        => 'text',
					'label'       => __( 'Heading — first line', 'synergi' ),
					'description' => __( 'The words before the highlighted phrase.', 'synergi' ),
					'default'     => 'BPO Services in UAE & the Gulf to',
					'max_length'  => 120,
				),
				array(
					'key'         => 'home_hero_title_focus',
					'type'        => 'text',
					'label'       => __( 'Heading — highlighted phrase', 'synergi' ),
					'description' => __( 'Rendered in cyan on its own line.', 'synergi' ),
					'default'     => 'Power Your Business',
					'max_length'  => 80,
				),
				array(
					'key'        => 'home_hero_lead',
					'type'       => 'textarea',
					'label'      => __( 'Paragraph', 'synergi' ),
					'default'    => 'Synergi runs and transforms non-core business functions through BPO, consulting, manpower augmentation, and technology-enabled shared services across the Gulf.',
					'rows'       => 3,
					'max_length' => 400,
				),
				array(
					'key'         => 'home_hero_motion_lead',
					'type'        => 'text',
					'label'       => __( 'Animated line — fixed half', 'synergi' ),
					'description' => __( 'The part that does not change, before the cycling word.', 'synergi' ),
					'default'     => 'Helping your business remove',
					'max_length'  => 80,
				),
				array(
					'key'         => 'home_hero_motion_words',
					'type'        => 'repeater',
					'label'       => __( 'Animated line — cycling words', 'synergi' ),
					'description' => __( 'Typed out one after another. The first also shows when JavaScript is off, so make it the strongest one.', 'synergi' ),
					'row_noun'    => __( 'Word', 'synergi' ),
					'button'      => __( 'Add word', 'synergi' ),
					'row_label'   => 'word',
					'min_rows'    => 1,
					'max_rows'    => 8,
					'subfields'   => array(
						array(
							'key'        => 'word',
							'type'       => 'text',
							'label'      => __( 'Word or short phrase', 'synergi' ),
							'max_length' => 40,
						),
					),
				),
				array(
					'key'       => 'home_hero_buttons',
					'type'      => 'repeater',
					'label'     => __( 'Buttons', 'synergi' ),
					'row_noun'  => __( 'Button', 'synergi' ),
					'button'    => __( 'Add button', 'synergi' ),
					'row_label' => 'label',
					'min_rows'  => 1,
					'max_rows'  => 3,
					'subfields' => array(
						array(
							'key'        => 'label',
							'type'       => 'text',
							'label'      => __( 'Text', 'synergi' ),
							'max_length' => 40,
						),
						array(
							'key'         => 'url',
							'type'        => 'url',
							'label'       => __( 'Address', 'synergi' ),
							'placeholder' => '/contact-us/',
						),
						array(
							'key'         => 'style',
							'type'        => 'select',
							'label'       => __( 'Appearance', 'synergi' ),
							'description' => __( 'Solid for the main action, outline for the second.', 'synergi' ),
							'choices'     => array(
								'primary' => __( 'Solid', 'synergi' ),
								'light'   => __( 'Outline', 'synergi' ),
							),
						),
					),
				),
			),
		)
	);

	/* 02 — SERVICES */
	syn_register_field_group(
		array(
			'id'          => 'home_services',
			'title'       => __( 'Homepage 02 — Services', 'synergi' ),
			'description' => __( 'The heading above the six service cards. The cards themselves are the service lines, edited once at Settings → Site records.', 'synergi' ),
			'templates'   => array( SYN_HOMEPAGE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'home_services_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => 'One connected operating model',
					'max_length' => 60,
				),
				array(
					'key'        => 'home_services_title',
					'type'       => 'text',
					'label'      => __( 'Heading', 'synergi' ),
					'default'    => 'Our Core BPO Services',
					'max_length' => 120,
				),
				array(
					'key'        => 'home_services_lead',
					'type'       => 'textarea',
					'label'      => __( 'Paragraph', 'synergi' ),
					'default'    => 'Six specialist functions, coordinated around your business.',
					'rows'       => 2,
					'max_length' => 300,
				),
			),
		)
	);

	/* 03 — SHARED SERVICES */
	syn_register_field_group(
		array(
			'id'          => 'home_shared',
			'title'       => __( 'Homepage 03 — Shared services', 'synergi' ),
			'description' => __( 'The wording around the hub diagram. The diagram itself — its bubbles and the numbered steps — is part of the design and is not edited here.', 'synergi' ),
			'templates'   => array( SYN_HOMEPAGE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'home_shared_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => 'Regional operations',
					'max_length' => 60,
				),
				array(
					'key'        => 'home_shared_title',
					'type'       => 'text',
					'label'      => __( 'Heading', 'synergi' ),
					'default'    => 'Transform Your Business with Shared Services',
					'max_length' => 140,
				),
				array(
					'key'         => 'home_shared_cta_label',
					'type'        => 'text',
					'label'       => __( 'Button — text', 'synergi' ),
					'description' => __( 'Leave both button boxes empty to keep the built-in button.', 'synergi' ),
					'max_length'  => 40,
				),
				array(
					'key'         => 'home_shared_cta_url',
					'type'        => 'text',
					'label'       => __( 'Button — address', 'synergi' ),
					'placeholder' => '/shared-services-uae/',
					'max_length'  => 200,
				),
				array(
					'key'         => 'home_shared_markets',
					'type'        => 'repeater',
					'label'       => __( 'Markets', 'synergi' ),
					'description' => __( 'Shown in one line, separated by dots.', 'synergi' ),
					'row_noun'    => __( 'Market', 'synergi' ),
					'button'      => __( 'Add market', 'synergi' ),
					'row_label'   => 'name',
					'min_rows'    => 1,
					'max_rows'    => 12,
					'subfields'   => array(
						array(
							'key'        => 'name',
							'type'       => 'text',
							'label'      => __( 'Market', 'synergi' ),
							'max_length' => 40,
						),
					),
				),
				array(
					'key'         => 'home_shared_chips',
					'type'        => 'repeater',
					'label'       => __( 'Benefit pills', 'synergi' ),
					'description' => __( 'The small rounded labels under the paragraph.', 'synergi' ),
					'row_noun'    => __( 'Pill', 'synergi' ),
					'button'      => __( 'Add pill', 'synergi' ),
					'row_label'   => 'name',
					'min_rows'    => 1,
					'max_rows'    => 10,
					'subfields'   => array(
						array(
							'key'        => 'name',
							'type'       => 'text',
							'label'      => __( 'Benefit', 'synergi' ),
							'max_length' => 60,
						),
					),
				),
			),
		)
	);

	/* 04 — INDUSTRIES */
	syn_register_field_group(
		array(
			'id'          => 'home_industries',
			'title'       => __( 'Homepage 04 — Industries', 'synergi' ),
			'description' => __( 'The sectors queue. Leave the whole list empty to keep the built-in seven.', 'synergi' ),
			'templates'   => array( SYN_HOMEPAGE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'home_industries_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => 'Sector expertise',
					'max_length' => 60,
				),
				array(
					'key'        => 'home_industries_title',
					'type'       => 'text',
					'label'      => __( 'Heading', 'synergi' ),
					'default'    => 'Industries We Serve Across the Gulf',
					'max_length' => 140,
				),
				array(
					'key'       => 'home_industries_cards',
					'type'      => 'repeater',
					'label'     => __( 'Industries', 'synergi' ),
					'row_noun'  => __( 'Industry', 'synergi' ),
					'button'    => __( 'Add industry', 'synergi' ),
					'row_label' => 'title',
					'min_rows'  => 1,
					'max_rows'  => 12,
					'subfields' => array(
						array(
							'key'        => 'title',
							'type'       => 'text',
							'label'      => __( 'Name', 'synergi' ),
							'max_length' => 80,
						),
						array(
							'key'         => 'preview',
							'type'        => 'text',
							'label'       => __( 'Label over the photograph', 'synergi' ),
							'description' => __( 'Two or three words.', 'synergi' ),
							'max_length'  => 40,
						),
						array(
							'key'        => 'description',
							'type'       => 'textarea',
							'label'      => __( 'One sentence', 'synergi' ),
							'rows'       => 2,
							'max_length' => 240,
						),
						array(
							'key'         => 'image',
							'type'        => 'image',
							'label'       => __( 'Photograph', 'synergi' ),
							'description' => __( 'Choose one with meaningful alt text already set on it.', 'synergi' ),
						),
					),
				),
			),
		)
	);

	/* 07 — PARTNERS */
	syn_register_field_group(
		array(
			'id'          => 'home_partners',
			'title'       => __( 'Homepage 07 — Partners', 'synergi' ),
			'description' => __( 'The logo strip. Each partner’s name is read from its logo’s alt text in the media library, so a partner is renamed there rather than here.', 'synergi' ),
			'templates'   => array( SYN_HOMEPAGE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'home_partners_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => 'Partner ecosystem',
					'max_length' => 60,
				),
				array(
					'key'        => 'home_partners_title',
					'type'       => 'text',
					'label'      => __( 'Heading', 'synergi' ),
					'default'    => 'Our Partners',
					'max_length' => 120,
				),
				array(
					'key'        => 'home_partners_lead',
					'type'       => 'textarea',
					'label'      => __( 'Paragraph', 'synergi' ),
					'default'    => 'Leading global platforms and regional specialists, integrated into one delivery model — so the systems behind your operations are always best in class.',
					'rows'       => 3,
					'max_length' => 400,
				),
				array(
					'key'       => 'home_partners_logos',
					'type'      => 'repeater',
					'label'     => __( 'Logos', 'synergi' ),
					'row_noun'  => __( 'Logo', 'synergi' ),
					'button'    => __( 'Add logo', 'synergi' ),
					'row_label' => 'image',
					'min_rows'  => 1,
					'max_rows'  => 20,
					'subfields' => array(
						array(
							'key'         => 'image',
							'type'        => 'image',
							'label'       => __( 'Logo', 'synergi' ),
							'description' => __( 'The partner’s name must be in this image’s alt text — that is what the strip reads out.', 'synergi' ),
						),
					),
				),
			),
		)
	);

	/* 09 — BLOG */
	syn_register_field_group(
		array(
			'id'          => 'home_blog',
			'title'       => __( 'Homepage 09 — Blog', 'synergi' ),
			'description' => __( 'The heading above the latest posts. The posts themselves are whatever is newest.', 'synergi' ),
			'templates'   => array( SYN_HOMEPAGE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'home_blog_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => 'Insights & analysis from our teams',
					'max_length' => 60,
				),
				array(
					'key'        => 'home_blog_title',
					'type'       => 'text',
					'label'      => __( 'Heading', 'synergi' ),
					'default'    => 'Read Our Blog',
					'max_length' => 120,
				),
				array(
					'key'        => 'home_blog_link_text',
					'type'       => 'text',
					'label'      => __( 'Link text', 'synergi' ),
					'default'    => 'View all articles',
					'max_length' => 40,
				),
				array(
					'key'         => 'home_blog_link_url',
					'type'        => 'text',
					'label'       => __( 'Link address', 'synergi' ),
					'description' => __( 'Leave empty to use the Posts page set in Settings → Reading.', 'synergi' ),
					'placeholder' => '/blog/',
					'max_length'  => 200,
				),
			),
		)
	);

	/* 10 — INSTAGRAM */
	syn_register_field_group(
		array(
			'id'          => 'home_instagram',
			'title'       => __( 'Homepage 10 — Instagram', 'synergi' ),
			'description' => __( 'The heading above the feed. The posts come from the feed plugin.', 'synergi' ),
			'templates'   => array( SYN_HOMEPAGE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'home_instagram_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => 'Life at Synergi',
					'max_length' => 60,
				),
				array(
					'key'        => 'home_instagram_title',
					'type'       => 'text',
					'label'      => __( 'Heading', 'synergi' ),
					'default'    => 'Recent From Instagram',
					'max_length' => 120,
				),
				array(
					'key'        => 'home_instagram_link_text',
					'type'       => 'text',
					'label'      => __( 'Button text', 'synergi' ),
					'default'    => 'Follow Synergi',
					'max_length' => 40,
				),
				array(
					'key'        => 'home_instagram_link_url',
					'type'       => 'text',
					'label'      => __( 'Profile address', 'synergi' ),
					'default'    => 'https://www.instagram.com/synergi.bpo',
					'max_length' => 200,
				),
			),
		)
	);

	/* 11 — PODCAST */
	syn_register_field_group(
		array(
			'id'          => 'home_podcast',
			'title'       => __( 'Homepage 11 — Podcast', 'synergi' ),
			'description' => __( 'The podcast band.', 'synergi' ),
			'templates'   => array( SYN_HOMEPAGE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'home_podcast_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => 'Conversations for people who run businesses',
					'max_length' => 80,
				),
				array(
					'key'        => 'home_podcast_title',
					'type'       => 'text',
					'label'      => __( 'Heading', 'synergi' ),
					'default'    => 'The Synergi Executive Podcast',
					'max_length' => 120,
				),
				array(
					'key'        => 'home_podcast_lead',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => 'Senior leaders unpack the decisions behind stronger operations across MENA.',
					'rows'       => 2,
					'max_length' => 300,
				),
				array(
					'key'        => 'home_podcast_body',
					'type'       => 'textarea',
					'label'      => __( 'Paragraph', 'synergi' ),
					'default'    => 'Practical perspectives on leadership, shared services, BPO, and operational transformation, grounded in real-world experience.',
					'rows'       => 3,
					'max_length' => 400,
				),
				array(
					'key'        => 'home_podcast_badge',
					'type'       => 'text',
					'label'      => __( 'Chip over the artwork', 'synergi' ),
					'default'    => 'Business media · MENA',
					'max_length' => 60,
				),
				array(
					'key'           => 'home_podcast_image',
					'type'          => 'image',
					'label'         => __( 'Cover art', 'synergi' ),
					'fallback_slug' => 'synergi-executive-podcast',
				),
			),
		)
	);
}

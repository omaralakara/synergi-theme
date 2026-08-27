<?php
/**
 * The field groups that feed templates/about.php and templates/people.php.
 *
 * Loaded by functions.php after inc/fields.php, whose engine this uses and does
 * not extend. A sibling of inc/service-fields.php and inc/homepage-fields.php,
 * for the same reason: fields.php is HOW a field works, this is WHICH fields
 * exist on the About Us family of pages.
 *
 * WHY THESE ARE FIELDS AND NOT RECORDS. CLAUDE.md §7a's test is "if this
 * changes, how many pages should change with it?". The welcome copy, the six
 * values and the journey graphic belong to About Us and would be wrong anywhere
 * else, so the answer is one page, and one page means postmeta. The key figures
 * band on the same page answers "several", so it is not here at all — it reads
 * the "figures" site record, exactly as the homepage does.
 *
 * The people fields are postmeta for the same reason and one more:
 * /our-leadership/ and /engagement-team/ hold DIFFERENT people. A record would
 * force them to hold the same list, which is the opposite of what the two pages
 * are for.
 *
 * Every field's registered default is the wording the Elementor page published
 * before the rebuild, so a page migrated with nothing typed into it still reads
 * as approved rather than as empty headings (CLAUDE.md §7c). Photographs have no
 * defaults — an attachment ID is site-specific and has no business in the repo.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The About Us template every group in syn_register_about_fields() is scoped to. */
define( 'SYN_ABOUT_TEMPLATE', 'templates/about.php' );

/** The people template syn_register_people_fields()'s groups are scoped to. */
define( 'SYN_PEOPLE_TEMPLATE', 'templates/people.php' );

add_action( 'syn_register_fields', 'syn_register_about_fields' );
/**
 * Registers the four field groups an About Us page carries.
 *
 * One group per band, in the order the bands appear down the page, so the edit
 * screen reads in the same order the page does.
 *
 * Side effects: registers four field groups on templates/about.php.
 *
 * @return void
 */
function syn_register_about_fields() {

	/*
	 * 1. INTRO — the band at the top.
	 *
	 * No heading field: parts/page-header.php uses the page title as the <h1>,
	 * so the heading can never disagree with the browser tab or the menu
	 * (CLAUDE.md §8). No photograph field either — the hero picture is the
	 * page's Featured Image, the same decision templates/service.php took, so
	 * one picture never has two owners.
	 */
	syn_register_field_group(
		array(
			'id'          => 'about_intro',
			'title'       => __( 'About — intro', 'synergi' ),
			'description' => __( 'The band at the top of the page. The page title is the heading, so it is not repeated here, and the photograph is the Featured Image in the sidebar.', 'synergi' ),
			'templates'   => array( SYN_ABOUT_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'about_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'About Synergi', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'         => 'about_lede',
					'type'        => 'textarea',
					'label'       => __( 'Opening sentence', 'synergi' ),
					'description' => __( 'One or two sentences. This is the first thing a reader and a search engine see.', 'synergi' ),
					'default'     => __( 'We offer end-to-end solutions to enable companies at all stages of maturity to optimize and drive value from their non-core activities.', 'synergi' ),
					'rows'        => 3,
					'max_length'  => 320,
				),
				array(
					'key'     => 'about_cta',
					'type'    => 'link',
					'label'   => __( 'Main button', 'synergi' ),
					'default' => array(
						'url'   => '',
						'label' => __( 'Talk to our team', 'synergi' ),
					),
				),
				array(
					'key'     => 'about_cta_alt',
					'type'    => 'link',
					'label'   => __( 'Second button', 'synergi' ),
					'default' => array(
						'url'   => '',
						'label' => '',
					),
				),
			),
		)
	);

	/*
	 * 2. STORY — who Synergi is, then the mission and the vision.
	 *
	 * The paragraphs are a repeater of one textarea rather than one long box,
	 * because a single box would make the partial split on blank lines and
	 * guess where a paragraph ends. A row is a paragraph, visibly, and an
	 * editor can reorder them.
	 *
	 * Mission and vision are two rows of one repeater rather than six separate
	 * fields: they are the same shape rendered twice, and a third statement
	 * ("our promise", say) should not need a developer.
	 */
	syn_register_field_group(
		array(
			'id'          => 'about_story',
			'title'       => __( 'About — who we are', 'synergi' ),
			'description' => __( 'The opening story, then the mission and vision statements.', 'synergi' ),
			'templates'   => array( SYN_ABOUT_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'story_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Welcome to Synergi’s World', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'       => 'story_paragraphs',
					'type'      => 'repeater',
					'label'     => __( 'Paragraphs', 'synergi' ),
					'row_noun'  => __( 'Paragraph', 'synergi' ),
					'button'    => __( 'Add paragraph', 'synergi' ),
					'row_label' => 'text',
					'min_rows'  => 1,
					'max_rows'  => 10,
					'default'   => array(
						array( 'text' => 'Synergi is a Boutique Business Process Outsourcing (BPO) services provider with a bold ambition: to evolve into a tech-driven “Shared Services as-a-service (SSaaS)” provider.' ),
						array( 'text' => 'Incepted in Abu Dhabi, our vision includes digitizing service delivery, and positioning ourselves as a “one-stop shop” across various industries like hospitality, healthcare, fintech, technology among other industries.' ),
						array( 'text' => 'We envision becoming more than a BPO; a tech company at its core, combining systems, automation, AI, and cloud-based infrastructure with human ingenuity and drive to deliver operational excellence at scale.' ),
						array( 'text' => 'We are home-grown in the UAE with delivery centres both onshore and offshore.' ),
					),
					'subfields' => array(
						array(
							'key'   => 'text',
							'type'  => 'textarea',
							'label' => __( 'Paragraph', 'synergi' ),
							'rows'  => 4,
						),
					),
				),
				array(
					'key'       => 'story_pillars',
					'type'      => 'repeater',
					'label'     => __( 'Mission and vision', 'synergi' ),
					'row_noun'  => __( 'Statement', 'synergi' ),
					'button'    => __( 'Add statement', 'synergi' ),
					'row_label' => 'title',
					'min_rows'  => 1,
					'max_rows'  => 4,
					'default'   => array(
						array(
							'title' => 'Our Mission',
							'body'  => 'At Synergi, our mission is to empower our clients to concentrate on their core activities, ensuring they realize cost savings, benefit from enhanced operational efficiency, and witness a marked improvement in their overall business performance. With us, you are not just thriving; you’re leading.',
						),
						array(
							'title' => 'Our Vision',
							'body'  => 'At Synergi, we’re dedicated to aiding our clients in their journey of growth and profitability. By aligning our expertise with their ambitions, we strive to make every partnership a success story. Your growth and profit are the benchmarks of our own success.',
						),
					),
					'subfields' => array(
						array(
							'key'        => 'title',
							'type'       => 'text',
							'label'      => __( 'Heading', 'synergi' ),
							'max_length' => 60,
						),
						array(
							'key'   => 'body',
							'type'  => 'textarea',
							'label' => __( 'Statement', 'synergi' ),
							'rows'  => 5,
						),
						array(
							'key'         => 'image',
							'type'        => 'image',
							'label'       => __( 'Photograph', 'synergi' ),
							'description' => __( 'Optional. Choose one with meaningful alt text already set on it.', 'synergi' ),
						),
					),
				),
			),
		)
	);

	/*
	 * 3. VALUES — the pillars. A repeater and not six fields for the ordinary
	 * reason: the business may have five of them next year.
	 */
	syn_register_field_group(
		array(
			'id'          => 'about_values',
			'title'       => __( 'About — our values', 'synergi' ),
			'description' => __( 'The pillars the company works to. The order here is the order on the page.', 'synergi' ),
			'templates'   => array( SYN_ABOUT_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'values_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Our Values', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'values_intro',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => __( 'At Synergi, our ethos is built upon pillars that guide every step we take.', 'synergi' ),
					'rows'       => 3,
					'max_length' => 320,
				),
				array(
					'key'         => 'values_image',
					'type'        => 'image',
					'label'       => __( 'Photograph', 'synergi' ),
					'description' => __( 'Optional. Sits beside the list.', 'synergi' ),
				),
				array(
					'key'       => 'values',
					'type'      => 'repeater',
					'label'     => __( 'Values', 'synergi' ),
					'row_noun'  => __( 'Value', 'synergi' ),
					'button'    => __( 'Add value', 'synergi' ),
					'row_label' => 'title',
					'min_rows'  => 1,
					'max_rows'  => 12,
					'default'   => array(
						array(
							'title'       => 'Agility',
							'description' => 'In an ever-changing world, our adaptability ensures we stay ahead, making swift decisions to benefit our clients.',
						),
						array(
							'title'       => 'Expertise',
							'description' => 'We pride ourselves on our deep knowledge and experience, ensuring that every task is executed precisely.',
						),
						array(
							'title'       => 'Customer Service',
							'description' => 'We put our clients at the heart of all we do, ensuring their satisfaction is our primary measure of success.',
						),
						array(
							'title'       => 'Efficiency',
							'description' => 'Our processes are streamlined, ensuring that every effort is maximized for the best possible outcomes.',
						),
						array(
							'title'       => 'Cost-effectiveness',
							'description' => 'We value every bit of your money and work diligently to ensure the best returns on your investment through our services.',
						),
						array(
							'title'       => 'Quality',
							'description' => 'Excellence isn’t just an aim; it’s a standard. We ensure every service provided is top-notch, meeting and exceeding expectations.',
						),
					),
					'subfields' => array(
						array(
							'key'        => 'title',
							'type'       => 'text',
							'label'      => __( 'Name', 'synergi' ),
							'max_length' => 60,
						),
						array(
							'key'   => 'description',
							'type'  => 'textarea',
							'label' => __( 'One sentence', 'synergi' ),
							'rows'  => 3,
						),
					),
				),
			),
		)
	);

	/*
	 * 4. JOURNEY — the timeline graphic. Its heading and caption are copy; the
	 * picture is a picture. There is no field for how it is laid out, because
	 * that is the whole point of the architecture (CLAUDE.md §7c).
	 */
	syn_register_field_group(
		array(
			'id'          => 'about_journey',
			'title'       => __( 'About — our journey', 'synergi' ),
			'description' => __( 'The company timeline. Leave the picture empty and the whole band is skipped.', 'synergi' ),
			'templates'   => array( SYN_ABOUT_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'journey_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Our Journey', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'journey_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => '',
					'rows'       => 3,
					'max_length' => 320,
				),
				array(
					'key'         => 'journey_image',
					'type'        => 'image',
					'label'       => __( 'The timeline', 'synergi' ),
					'description' => __( 'The graphic itself. It carries information rather than decorating, so it needs real alt text on its media library entry.', 'synergi' ),
				),
			),
		)
	);
}

add_action( 'syn_register_fields', 'syn_register_people_fields' );
/**
 * Registers the two field groups a people page carries.
 *
 * One template, two pages: /our-leadership/ holds the board and the advisors,
 * /engagement-team/ holds the delivery team. Same fields, different content —
 * the same claim templates/service.php makes about the six service lines.
 *
 * Side effects: registers two field groups on templates/people.php.
 *
 * @return void
 */
function syn_register_people_fields() {

	syn_register_field_group(
		array(
			'id'          => 'people_intro',
			'title'       => __( 'People — intro', 'synergi' ),
			'description' => __( 'The band at the top of the page. The page title is the heading.', 'synergi' ),
			'templates'   => array( SYN_PEOPLE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'people_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'About Synergi', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'people_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => __( 'For a boutique, the people are the product. These are the people you work with.', 'synergi' ),
					'rows'       => 3,
					'max_length' => 320,
				),
				array(
					'key'     => 'people_cta',
					'type'    => 'link',
					'label'   => __( 'Main button', 'synergi' ),
					'default' => array(
						'url'   => '',
						'label' => '',
					),
				),
			),
		)
	);

	/*
	 * ONE repeater, not one per group. Repeaters do not nest (CLAUDE.md §7c),
	 * and the alternative — a fixed "board" list and a fixed "advisors" list —
	 * would put the company's org chart in PHP. Instead every person carries the
	 * name of the group they belong to, and sections/people.php starts a new
	 * heading whenever that name changes. Adding a third group is typing its
	 * name on a row.
	 *
	 * Rows therefore have to be kept in group order. The row bar shows the
	 * person's name and the group is the first box in the row, so an editor can
	 * see the grouping without opening anything.
	 *
	 * There is no default list: the two pages hold different people, and a
	 * default would be one page's team appearing on the other.
	 */
	syn_register_field_group(
		array(
			'id'          => 'people_list',
			'title'       => __( 'People — the list', 'synergi' ),
			'description' => __( 'Everyone on this page, in the order they should appear. Rows sharing a “Group” sit together under one heading, so keep a group’s rows next to each other.', 'synergi' ),
			'templates'   => array( SYN_PEOPLE_TEMPLATE ),
			'fields'      => array(
				array(
					'key'       => 'people',
					'type'      => 'repeater',
					'label'     => __( 'People', 'synergi' ),
					'row_noun'  => __( 'Person', 'synergi' ),
					'button'    => __( 'Add person', 'synergi' ),
					'row_label' => 'name',
					'min_rows'  => 1,
					'max_rows'  => 60,
					/*
					 * The one repeater on the site that keeps a row an editor
					 * left entirely empty. On this grid an empty row is not a
					 * mistake, it is a gap: it holds a cell open so the cards
					 * after it start a new line — the two leaders alone on the
					 * first row, as the Elementor page had them. Everywhere
					 * else an empty row is still discarded on save.
					 */
					'keep_empty_rows' => true,
					'subfields' => array(
						array(
							'key'         => 'group',
							'type'        => 'text',
							'label'       => __( 'Group', 'synergi' ),
							'description' => __( 'The heading this person sits under, e.g. Board of Directors. Leave it empty on every row for one ungrouped list.', 'synergi' ),
							'max_length'  => 60,
						),
						array(
							'key'        => 'name',
							'type'       => 'text',
							'label'      => __( 'Name', 'synergi' ),
							'max_length' => 80,
						),
						array(
							'key'        => 'role',
							'type'       => 'text',
							'label'      => __( 'Role', 'synergi' ),
							'max_length' => 90,
						),
						array(
							'key'         => 'image',
							'type'        => 'image',
							'label'       => __( 'Photograph', 'synergi' ),
							'description' => __( 'Optional. A card without one still renders, with the person’s initials in its place.', 'synergi' ),
						),
						array(
							'key'         => 'linkedin',
							'type'        => 'url',
							'label'       => __( 'LinkedIn', 'synergi' ),
							'description' => __( 'Optional. The full address, starting with https://.', 'synergi' ),
							'placeholder' => 'https://www.linkedin.com/in/',
						),
						array(
							'key'         => 'bio',
							'type'        => 'textarea',
							'label'       => __( 'Biography', 'synergi' ),
							'description' => __( 'Optional. A few sentences. A card with one is wider than a card without.', 'synergi' ),
							'rows'        => 6,
						),
					),
				),
			),
		)
	);
}

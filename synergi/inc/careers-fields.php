<?php
/**
 * The field groups that feed templates/careers.php.
 *
 * Loaded by functions.php after inc/fields.php, whose engine this uses and does
 * not extend, and after inc/records.php, whose services record the department
 * list below is built from. A sibling of inc/podcast-fields.php and the rest.
 *
 * THE POSITIONS ARE POSTMETA, NOT A SITE RECORD. An open position appears on
 * exactly one page and nowhere else, which is CLAUDE.md §7a's test for page
 * fields. That changes the day a position needs its own URL — a shareable
 * listing with its own title tag, say — at which point it becomes a post type
 * with an archive, and this repeater is migrated into it. Until then a
 * repeater is honest about what the page is: a list of roles and a way to
 * apply.
 *
 * THE DEPARTMENT IS A CHOICE, NOT A TEXT BOX. A position sits under one of the
 * six service lines the business runs, or under the company itself. Read from
 * the services record so a renamed line renames every position under it, and
 * so sections/positions.php can key its accent off the same slug the rest of
 * the site uses (CLAUDE.md §7a, §13). "Human Resources", "HR" and "Human
 * resources " are three departments to a text box and one to a select.
 *
 * EACH ROLE CARRIES ITS OWN APPLICATION ADDRESS (14 Sep). There is no general
 * careers inbox and no page-wide "send your CV" button: the business wants
 * applications for a role to land with the person hiring for it, so the email
 * is a column of the repeater and the Apply button writes to it with the role
 * in the subject line.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The template every group below is scoped to. */
define( 'SYN_CAREERS_TEMPLATE', 'templates/careers.php' );

/**
 * The departments a position can belong to, as select choices.
 *
 * The six service lines come from the services record, keyed by the same
 * reference the case studies and the accents use, followed by two homes for
 * the roles that serve the company rather than a client. The two fixed keys
 * are kept OUT of the record on purpose: an editor should not be able to
 * delete "Operations" from a settings screen and orphan every role under it.
 *
 * Read at render time as well as at registration, so a stored key is always
 * printed through the same list that offered it (CLAUDE.md §13, one name
 * everywhere).
 *
 * @return array<string,string> slug => name.
 */
function syn_careers_department_choices() {
	$choices = array();

	$services = function_exists( 'syn_record' ) ? syn_record( 'services' ) : array();

	foreach ( (array) $services as $service ) {
		if ( ! is_array( $service ) ) {
			continue;
		}

		$slug = sanitize_key( $service['slug'] ?? '' );
		$name = trim( (string) ( $service['name'] ?? '' ) );

		if ( '' !== $slug && '' !== $name ) {
			$choices[ $slug ] = $name;
		}
	}

	$choices['operations'] = __( 'Operations', 'synergi' );
	$choices['corporate']  = __( 'Corporate', 'synergi' );

	return $choices;
}

/**
 * The states a listed position can be in, as select choices.
 *
 * The tag on every row (asked for 14 Sep). "available" is the first choice
 * and what a row with nothing stored is treated as, so a role added before
 * this column existed reads as open rather than as blank.
 *
 * "new" arrived with the portal feed (30 Sep): inc/careers-feed.php tags a
 * role new in its first week, and the key is listed here so the feed and the
 * repeater share one vocabulary and one set of labels.
 *
 * @return array<string,string> key => label.
 */
function syn_careers_status_choices() {
	return array(
		'available' => __( 'Still available', 'synergi' ),
		'new'       => __( 'New', 'synergi' ),
		'closing'   => __( 'Closing soon', 'synergi' ),
		'filled'    => __( 'Filled', 'synergi' ),
	);
}

/**
 * The employment types a position can have, as select choices.
 *
 * The keys are schema.org's employmentType values lowercased, so
 * sections/positions.php can put the stored key straight into its JobPosting
 * structured data without a second mapping to keep in step (CLAUDE.md §8).
 *
 * @return array<string,string> key => label.
 */
function syn_careers_type_choices() {
	return array(
		'full_time'  => __( 'Full-time', 'synergi' ),
		'part_time'  => __( 'Part-time', 'synergi' ),
		'contractor' => __( 'Contract', 'synergi' ),
		'intern'     => __( 'Internship', 'synergi' ),
		'temporary'  => __( 'Temporary', 'synergi' ),
	);
}

add_action( 'syn_register_fields', 'syn_register_careers_fields' );
/**
 * Registers the three field groups a careers page carries.
 *
 * Side effects: registers three field groups on templates/careers.php.
 *
 * @return void
 */
function syn_register_careers_fields() {

	/*
	 * 1. INTRO. No heading field: the page title is the <h1>, and the SEO
	 * title is Yoast's. The photograph is a field rather than the Featured
	 * Image so the fallback slug can name a picture already in the library and
	 * the band is never flat navy by accident (CLAUDE.md §7b). One button: it
	 * jumps to the roles, which is the page's only action.
	 */
	syn_register_field_group(
		array(
			'id'          => 'careers_intro',
			'title'       => __( 'Careers — intro', 'synergi' ),
			'description' => __( 'The band at the top. The page title is the heading.', 'synergi' ),
			'templates'   => array( SYN_CAREERS_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'careers_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Careers at Synergi', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'careers_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					// The business's own company description, as it publishes it
					// with every vacancy (supplied 14 Sep).
					'default'    => __( 'Synergi BPO is a boutique Business Process Outsourcing provider serving clients across the GCC region and internationally, helping organizations streamline and strengthen their support functions and shared services.', 'synergi' ),
					'rows'       => 3,
					'max_length' => 320,
				),
				array(
					'key'           => 'careers_image',
					'type'          => 'image',
					'label'         => __( 'Hero photograph', 'synergi' ),
					'description'   => __( 'Fills the band behind the page title. Without one the band stays on the flat navy.', 'synergi' ),
					'fallback_slug' => 'team-presentation-boardroom',
				),
				array(
					'key'         => 'careers_cta',
					'type'        => 'link',
					'label'       => __( 'Button', 'synergi' ),
					'description' => __( '#positions jumps to the open positions further down this page.', 'synergi' ),
					'default'     => array(
						'url'   => '#positions',
						'label' => __( 'See open positions', 'synergi' ),
					),
				),
			),
		)
	);

	/*
	 * 2. WHY JOIN — the perks band. One photograph beside the reasons, so the
	 * page shows the team before it lists the vacancies.
	 */
	syn_register_field_group(
		array(
			'id'          => 'careers_perks',
			'title'       => __( 'Careers — why join', 'synergi' ),
			'description' => __( 'The reasons to work here, beside a photograph of the team.', 'synergi' ),
			'templates'   => array( SYN_CAREERS_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'careers_perks_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Life at Synergi', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'careers_perks_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Why people build their careers here', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'careers_perks_intro',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					// The About Us page's own opening line.
					'default'    => __( 'Synergi is a Boutique Business Process Outsourcing (BPO) services provider with a bold ambition: to evolve into a tech-driven Shared Services as-a-service (SSaaS) provider.', 'synergi' ),
					'rows'       => 3,
					'max_length' => 400,
				),
				array(
					'key'           => 'careers_perks_image',
					'type'          => 'image',
					'label'         => __( 'Photograph', 'synergi' ),
					'description'   => __( 'Shown tall beside the reasons. A picture of people at work reads better than a skyline here.', 'synergi' ),
					'fallback_slug' => 'open-office-evening',
				),
				array(
					'key'       => 'careers_perks',
					'type'      => 'repeater',
					'label'     => __( 'Reasons', 'synergi' ),
					'row_noun'  => __( 'Reason', 'synergi' ),
					'button'    => __( 'Add reason', 'synergi' ),
					'row_label' => 'title',
					'min_rows'  => 1,
					'max_rows'  => 8,
					/*
					 * EVERY SENTENCE BELOW IS THE COMPANY'S OWN, taken from copy
					 * it already publishes: the About Us story paragraphs, the
					 * homepage's "why companies choose Synergi" cards and the
					 * About Us values. Nothing here was written for this page.
					 * A first draft invented benefits ("certifications paid
					 * for") and the business rejected it on 14 Sep; a careers
					 * page makes promises to strangers, so it may only repeat
					 * what the company has actually said.
					 */
					'default'   => array(
						array(
							'title'       => __( 'Home-grown in the GCC', 'synergi' ),
							'description' => __( 'We are home-grown in the GCC with delivery centres both onshore and offshore.', 'synergi' ),
						),
						array(
							'title'       => __( 'Experience across multiple markets', 'synergi' ),
							'description' => __( 'Regional context informs how teams, controls, and delivery models are designed.', 'synergi' ),
						),
						array(
							'title'       => __( 'A tech company at its core', 'synergi' ),
							'description' => __( 'We envision becoming more than a BPO; a tech company at its core, combining systems, automation, AI, and cloud-based infrastructure with human ingenuity and drive to deliver operational excellence at scale.', 'synergi' ),
						),
						array(
							'title'       => __( 'Expertise', 'synergi' ),
							'description' => __( 'We pride ourselves on our deep knowledge and experience, ensuring that every task is executed precisely.', 'synergi' ),
						),
					),
					'subfields' => array(
						array(
							'key'        => 'title',
							'type'       => 'text',
							'label'      => __( 'Title', 'synergi' ),
							'max_length' => 80,
						),
						array(
							'key'        => 'description',
							'type'       => 'textarea',
							'label'      => __( 'One or two sentences', 'synergi' ),
							'rows'       => 3,
							'max_length' => 320,
						),
					),
				),
			),
		)
	);

	/*
	 * 3. THE OPEN POSITIONS. One row per role, each with the address its
	 * applications go to. A row whose address is blank still has an Apply
	 * button — it goes to Contact Us — so a forgotten field never leaves a
	 * role with no way in.
	 */
	syn_register_field_group(
		array(
			'id'          => 'careers_positions',
			'title'       => __( 'Careers — open positions', 'synergi' ),
			'description' => __( 'One row per role. A row with no title is skipped. Remove a row when the role is filled; with no rows at all the band invites an open application instead.', 'synergi' ),
			'templates'   => array( SYN_CAREERS_TEMPLATE ),
			'fields'      => array(
				array(
					'key'        => 'careers_positions_eyebrow',
					'type'       => 'text',
					'label'      => __( 'Eyebrow', 'synergi' ),
					'default'    => __( 'Open positions', 'synergi' ),
					'max_length' => 40,
				),
				array(
					'key'        => 'careers_positions_heading',
					'type'       => 'text',
					'label'      => __( 'Section heading', 'synergi' ),
					'default'    => __( 'Roles we are hiring for now', 'synergi' ),
					'max_length' => 90,
				),
				array(
					'key'        => 'careers_positions_lede',
					'type'       => 'textarea',
					'label'      => __( 'Opening sentence', 'synergi' ),
					'default'    => __( 'Open a role to read what it involves and who it suits.', 'synergi' ),
					'rows'       => 2,
					'max_length' => 320,
				),
				array(
					'key'       => 'careers_positions_list',
					'type'      => 'repeater',
					'label'     => __( 'Positions', 'synergi' ),
					'row_noun'  => __( 'Position', 'synergi' ),
					'button'    => __( 'Add position', 'synergi' ),
					'row_label' => 'title',
					'min_rows'  => 0,
					'max_rows'  => 40,
					'default'   => array(),
					'subfields' => array(
						array(
							'key'        => 'title',
							'type'       => 'text',
							'label'      => __( 'Role title', 'synergi' ),
							'max_length' => 120,
						),
						array(
							'key'     => 'department',
							'type'    => 'select',
							'label'   => __( 'Department', 'synergi' ),
							'choices' => syn_careers_department_choices(),
						),
						array(
							'key'         => 'location',
							'type'        => 'text',
							'label'       => __( 'Location', 'synergi' ),
							'description' => __( 'City and country, or "Remote".', 'synergi' ),
							'placeholder' => 'Dubai, UAE',
							'max_length'  => 80,
						),
						array(
							'key'     => 'type',
							'type'    => 'select',
							'label'   => __( 'Employment type', 'synergi' ),
							'choices' => syn_careers_type_choices(),
						),
						array(
							'key'         => 'status',
							'type'        => 'select',
							'label'       => __( 'Status tag', 'synergi' ),
							'description' => __( 'The small tag on the role. Remove the row altogether once a role is filled and gone.', 'synergi' ),
							'choices'     => syn_careers_status_choices(),
						),
						array(
							'key'         => 'summary',
							'type'        => 'textarea',
							'label'       => __( 'One-line summary', 'synergi' ),
							'description' => __( 'Shown before the role is opened.', 'synergi' ),
							'rows'        => 2,
							'max_length'  => 240,
						),
						array(
							'key'         => 'description',
							'type'        => 'html',
							'label'       => __( 'The role', 'synergi' ),
							'description' => __( 'What the role involves and who it suits. Paragraphs and lists are fine.', 'synergi' ),
							'rows'        => 8,
						),
						array(
							'key'         => 'apply_email',
							'type'        => 'email',
							'label'       => __( 'Applications email', 'synergi' ),
							'description' => __( 'Printed on the role as "Mail us at …", with the role title in the subject line. Left blank, the role points at Contact Us instead.', 'synergi' ),
							'placeholder' => 'name@synergi.ae',
						),
						array(
							'key'         => 'apply_url',
							'type'        => 'url',
							'label'       => __( 'Apply link instead', 'synergi' ),
							'description' => __( 'Optional. A form or a job board page for this role. Filled in, it is used instead of the email.', 'synergi' ),
							'placeholder' => 'https://',
						),
						array(
							'key'         => 'posted',
							'type'        => 'date',
							'label'       => __( 'Posted on', 'synergi' ),
							'description' => __( 'Optional. Shown on the role, and needed for the listing to appear in Google Jobs.', 'synergi' ),
						),
						/*
						 * THE EXPIRY DATE, added 16 Sep with inc/careers-schema.php.
						 * Google keeps a job listing alive until the date in
						 * validThrough passes, so a posting with no closing date
						 * stays in Google Jobs after the role is gone, collecting
						 * applications nobody reads. That is the most common way a
						 * site earns a manual action for job markup, and the only
						 * cure is a date.
						 *
						 * Optional rather than required because the field engine
						 * has no required leaves and inventing a date would be
						 * worse than omitting the property — a guess that expires
						 * a live role is a real vacancy taken off Google early.
						 * The schema simply omits validThrough when this is blank,
						 * and the admin screen nags for it instead.
						 */
						array(
							'key'         => 'valid_through',
							'type'        => 'date',
							'label'       => __( 'Closes on', 'synergi' ),
							'description' => __( 'Optional but strongly recommended. The last day applications are open. Google uses it to drop the listing from Google Jobs automatically — without it a filled role can keep attracting applicants for months.', 'synergi' ),
						),
					),
				),
				array(
					'key'        => 'careers_positions_empty_heading',
					'type'       => 'text',
					'label'      => __( 'No openings — heading', 'synergi' ),
					'default'    => __( 'No open roles at the moment', 'synergi' ),
					'max_length' => 120,
				),
				array(
					'key'        => 'careers_positions_empty_text',
					'type'       => 'textarea',
					'label'      => __( 'No openings — text', 'synergi' ),
					'default'    => __( 'Get in touch if you would like to be considered for future roles.', 'synergi' ),
					'rows'       => 3,
					'max_length' => 320,
				),
				array(
					'key'         => 'careers_positions_empty_cta',
					'type'        => 'link',
					'label'       => __( 'No openings — button', 'synergi' ),
					'description' => __( 'Leave the address empty and the button goes to Contact Us.', 'synergi' ),
					'default'     => array(
						'url'   => '',
						'label' => __( 'Get in touch', 'synergi' ),
					),
				),
			),
		)
	);
}

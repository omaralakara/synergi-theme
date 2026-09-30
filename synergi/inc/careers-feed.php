<?php
/**
 * The bridge between the Synergi Careers plugin and sections/positions.php.
 *
 * Loaded by functions.php after inc/careers-fields.php, whose choice lists the
 * mapping below writes into. Read by templates/careers.php and by
 * synergi-careers/single-vacancy.php. Every function here is safe to call
 * when the plugin is absent: it answers "not active" and the repeater path
 * carries on exactly as before (CLAUDE.md §13, fail gracefully).
 *
 * THE PLUGIN OWNS THE DATA; THE THEME OWNS THE MARKUP. The portal team's
 * plugin (synergi-careers, 1.0.0) fetches, validates and caches the portal's
 * read-only vacancies feed, routes /careers/<title>-<id>/ to a virtual page,
 * 301s a renamed title, 404s a closed role, adds the roles to Yoast's sitemap
 * and emits the JobPosting markup. It also ships its own copy of the
 * syn-positions accordion, which the theme does NOT use: one class name, one
 * file (CLAUDE.md §13). So the theme reads the plugin's rows through its
 * public functions and hands them to the same section the repeater feeds.
 *
 * The plugin is never edited. Anything that can only be fixed inside it —
 * the second <main> in its own template, the is_home() it leaves WordPress
 * believing, the hiringOrganization it always names as Synergi — is on the
 * findings list in docs/careers-feed-vendor-findings.md, not patched here.
 *
 * WHAT A ROLE LOOKS LIKE COMING IN (feed schemaVersion 1, after the plugin's
 * normalise()): publicId, slug, title, hiringFor, department (ACCOUNTING …
 * PROJECT_MANAGEMENT or null), summary, descriptionHtml, location,
 * employmentType (FULL_TIME … INTERNSHIP), workMode ('' | HYBRID | REMOTE),
 * salary (null or min/max/currency), publishedAt, closingAt, application
 * (null or method/email).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the careers plugin is active and answering.
 *
 * Checked by function rather than by plugin file, so a renamed plugin folder
 * or a future 1.0.1 with the same API still counts.
 *
 * @return bool
 */
function syn_careers_feed_active() {
	return function_exists( '\SynergiCareers\vacancies' );
}

/**
 * The open roles from the portal, shaped for sections/positions.php.
 *
 * Three answers, and the difference matters to a visitor:
 *   - array of rows: the portal answered (possibly with nothing open);
 *   - empty array:   the portal says there are no roles;
 *   - null:          the portal could not be reached and no copy under a day
 *                    old exists. templates/careers.php shows the section's
 *                    empty panel with the plugin's fallback address.
 *
 * Side effects: asks LiteSpeed, through the plugin's own cache_hint(), to cache
 * the page briefly and tag it "synergi_careers", so the plugin's Fetch now
 * button purges this page too. Without that call only the plugin's own
 * shortcode output would be purged, and the theme does not use the shortcode.
 *
 * @return array[]|null Rows for the section's items argument, or null.
 */
function syn_careers_feed_items() {
	if ( ! syn_careers_feed_active() ) {
		return null;
	}

	$vacancies = \SynergiCareers\vacancies();

	if ( function_exists( '\SynergiCareers\cache_hint' ) ) {
		\SynergiCareers\cache_hint( null === $vacancies ? 60 : 300 );
	}

	if ( null === $vacancies ) {
		return null;
	}

	$items = array();

	foreach ( (array) $vacancies as $vacancy ) {
		if ( is_array( $vacancy ) ) {
			$items[] = syn_careers_feed_item( $vacancy );
		}
	}

	return $items;
}

/**
 * The role this request is for, when the plugin routed it to a role page.
 *
 * @return array|null The row, shaped for the section, or null when this is not
 *                    a role page or the role is not open.
 */
function syn_careers_feed_role() {
	if ( ! function_exists( '\SynergiCareers\current_role' ) ) {
		return null;
	}

	$role = \SynergiCareers\current_role();

	return is_array( $role ) && ! empty( $role ) ? syn_careers_feed_item( $role ) : null;
}

/**
 * Whether this request is one of the plugin's role pages.
 *
 * inc/assets.php asks this to load the title band's stylesheet. Deliberately
 * not derived from is_home(): WordPress calls the plugin's virtual page the
 * blog home because nothing else claims it, and the vendor may fix that.
 *
 * @return bool
 */
function syn_careers_is_role_page() {
	return function_exists( '\SynergiCareers\current_role' ) && (bool) \SynergiCareers\current_role();
}

/**
 * One feed row → one row for sections/positions.php.
 *
 * The department comes back as the theme's own slug (accounting,
 * human-resources …) through the plugin's department_accent(), which is the
 * one place the portal's ACCOUNTING-style keys are decided; the section then
 * prints its name from the services record, so the Arabic page shows the
 * Arabic service name. Everything else is a plain string the section escapes.
 *
 * The employment type, work mode and salary are worded here, in the theme's
 * text domain, rather than through the plugin's own labels: the plugin ships
 * no Arabic, and a label that cannot be translated is a label the Arabic page
 * cannot print (CLAUDE.md §12).
 *
 * @param array $vacancy One normalised vacancy from the plugin.
 * @return array The row.
 */
function syn_careers_feed_item( $vacancy ) {
	$text = static function ( $key ) use ( $vacancy ) {
		return isset( $vacancy[ $key ] ) && is_string( $vacancy[ $key ] ) ? trim( $vacancy[ $key ] ) : '';
	};

	$application = isset( $vacancy['application'] ) && is_array( $vacancy['application'] ) ? $vacancy['application'] : array();
	$department  = function_exists( '\SynergiCareers\department_accent' ) ? \SynergiCareers\department_accent( $vacancy['department'] ?? null ) : '';
	$permalink   = function_exists( '\SynergiCareers\role_url' ) && '' !== $text( 'slug' ) ? \SynergiCareers\role_url( $vacancy ) : '';

	/*
	 * The feed is written in English only. On a non-default language the
	 * copy is marked lang="en" so a screen reader switches voice and the
	 * browser picks the Latin face; the chrome around it — headings, chips,
	 * the mail line — stays in the page's language.
	 */
	$lang = function_exists( 'syn_language_is_default' ) && ! syn_language_is_default() ? 'en' : '';

	/*
	 * The portal writes "Synergi" in hiringFor for its own roles. "Hiring on
	 * behalf of Synergi" on a Synergi page is noise, so only another employer
	 * is worth a line — the same test the plugin's own template applies.
	 */
	$hiring_for = $text( 'hiringFor' );

	if ( 0 === strcasecmp( $hiring_for, 'Synergi' ) ) {
		$hiring_for = '';
	}

	return array(
		'title'       => $text( 'title' ),
		'department'  => $department,
		'location'    => $text( 'location' ),
		'type'        => syn_careers_feed_type( $text( 'employmentType' ) ),
		'status'      => syn_careers_feed_status( $vacancy ),
		'summary'     => $text( 'summary' ),
		'description' => $text( 'descriptionHtml' ),
		'posted'      => syn_careers_feed_date( $text( 'publishedAt' ) ),
		'closing'     => syn_careers_feed_closing( $text( 'closingAt' ) ),
		'apply_email' => isset( $application['email'] ) && is_email( $application['email'] ) ? $application['email'] : '',
		'apply_url'   => '',
		'permalink'   => $permalink,
		'hiring_for'  => $hiring_for,
		'mode'        => syn_careers_feed_mode( $text( 'workMode' ) ),
		'salary'      => syn_careers_feed_salary( $vacancy['salary'] ?? null ),
		'lang'        => $lang,
	);
}

/**
 * The portal's employment type → the theme's own key.
 *
 * The theme's keys are schema.org's values lowercased (inc/careers-fields.php),
 * the portal's are its own. Written out rather than lowercased so an unknown
 * value maps to "" and prints no chip, never a chip reading "OTHER".
 *
 * @param string $type As the feed sends it.
 * @return string A key from syn_careers_type_choices(), or "".
 */
function syn_careers_feed_type( $type ) {
	$map = array(
		'FULL_TIME'  => 'full_time',
		'PART_TIME'  => 'part_time',
		'CONTRACT'   => 'contractor',
		'TEMPORARY'  => 'temporary',
		'INTERNSHIP' => 'intern',
	);

	return $map[ $type ] ?? '';
}

/**
 * The status tag a portal role wears, by its dates.
 *
 * The same rule the plugin's status_tag() applies — closing within a week
 * beats new within a week beats still available — restated here because the
 * plugin returns a finished label and the section wants a key it can colour
 * and translate. If the vendor exposes the key, this becomes a call.
 *
 * @param array $vacancy One normalised vacancy.
 * @return string "closing", "new" or "available".
 */
function syn_careers_feed_status( $vacancy ) {
	$week    = 7 * DAY_IN_SECONDS;
	$now     = time();
	$closing = ! empty( $vacancy['closingAt'] ) ? strtotime( (string) $vacancy['closingAt'] ) : false;
	$posted  = ! empty( $vacancy['publishedAt'] ) ? strtotime( (string) $vacancy['publishedAt'] ) : false;

	if ( $closing && $closing - $now <= $week ) {
		return 'closing';
	}

	if ( $posted && $now - $posted <= $week ) {
		return 'new';
	}

	return 'available';
}

/**
 * A feed timestamp → the YYYY-MM-DD the section prints.
 *
 * @param string $raw Anything strtotime() reads, or "".
 * @return string The date in the site's timezone, or "".
 */
function syn_careers_feed_date( $raw ) {
	$stamp = '' !== $raw ? strtotime( $raw ) : false;

	return $stamp ? wp_date( 'Y-m-d', $stamp ) : '';
}

/**
 * The last day applications are open, from the feed's closing instant.
 *
 * The portal's closingAt is the moment the role closes, which is the start of
 * the day after the last day to apply; one second earlier is the day a
 * candidate needs to read. The same subtraction the plugin's own template
 * makes.
 *
 * @param string $raw The feed's closingAt, or "".
 * @return string YYYY-MM-DD, or "".
 */
function syn_careers_feed_closing( $raw ) {
	$stamp = '' !== $raw ? strtotime( $raw ) : false;

	return $stamp ? wp_date( 'Y-m-d', $stamp - 1 ) : '';
}

/**
 * The work-mode chip. On-site is the default and gets none.
 *
 * @param string $mode "", HYBRID or REMOTE.
 * @return string The label, or "".
 */
function syn_careers_feed_mode( $mode ) {
	$labels = array(
		'HYBRID' => __( 'Hybrid', 'synergi' ),
		'REMOTE' => __( 'Remote', 'synergi' ),
	);

	return $labels[ $mode ] ?? '';
}

/**
 * The salary chip, when the portal publishes a range.
 *
 * @param array|null $salary min, max and a three-letter currency, or null.
 * @return string "AED 8,000–12,000 a month", or "".
 */
function syn_careers_feed_salary( $salary ) {
	if ( ! is_array( $salary ) || ! isset( $salary['min'], $salary['max'], $salary['currency'] ) ) {
		return '';
	}

	if ( ! is_numeric( $salary['min'] ) || ! is_numeric( $salary['max'] ) || ! preg_match( '/^[A-Z]{3}$/', (string) $salary['currency'] ) ) {
		return '';
	}

	return sprintf(
		/* translators: 1: currency code, 2: minimum, 3: maximum monthly salary. */
		__( '%1$s %2$s–%3$s a month', 'synergi' ),
		$salary['currency'],
		number_format_i18n( (int) $salary['min'] ),
		number_format_i18n( (int) $salary['max'] )
	);
}

/**
 * The address visitors are given when the portal cannot be reached.
 *
 * The plugin's own fallback setting (Settings → Synergi Careers), so there is
 * one place to change it; careers@synergibpo.com is what the plugin defaults
 * to when nothing is set.
 *
 * @return string An email address, or "".
 */
function syn_careers_feed_fallback_email() {
	if ( ! function_exists( '\SynergiCareers\settings' ) ) {
		return '';
	}

	$settings = \SynergiCareers\settings();
	$email    = isset( $settings['fallback_email'] ) ? (string) $settings['fallback_email'] : '';

	return is_email( $email ) ? $email : '';
}

/**
 * The published careers page in the current language, by its template.
 *
 * By template rather than by a stored ID or the slug "careers", for the reason
 * inc/podcast-fields.php gives: the template is what makes a page THE careers
 * page. Polylang limits the query to the current language on the front end,
 * so the Arabic role page finds the Arabic careers page.
 *
 * @return int Page ID, or 0.
 */
function syn_careers_page_id() {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one exact match on an indexed meta key, over a few dozen pages.
			'meta_key'       => '_wp_page_template',
			'meta_value'     => SYN_CAREERS_TEMPLATE,
			'fields'         => 'ids',
		)
	);

	return $pages ? (int) $pages[0] : 0;
}

<?php
/**
 * Site records — the facts about the business, stored once and read everywhere.
 *
 * Loaded by functions.php, after inc/fields.php, whose repeater UI, leaf
 * sanitisers and row shaper this file reuses rather than reimplements.
 * Depended on by: templates/ and sections/ from Stage 6a step 3 onwards.
 *
 * WHY THIS IS NOT POSTMETA, and why it is a separate file from fields.php.
 * CLAUDE.md §7a splits editable data in two, and the test is one question: if
 * this changes, how many pages should change with it? The business asked for
 * "key figures — same numbers as the homepage, one set, used everywhere", and
 * the same is true of the offices and the service lines. Postmeta cannot express
 * that: it would put a separate copy on every page and they would disagree
 * within a month. So these live once, in a single option, edited on one screen.
 *
 * An option rather than a custom post type because a record needs no URL, no
 * template and no SEO of its own — a CPT would invent URLs nobody asked for, and
 * CLAUDE.md §2.8 is about not doing that carelessly.
 *
 * WHY THERE ARE THREE RECORDS AND NOT SIX. Decided 27 Aug (stage-6-scope.md
 * §8a): services, figures and locations are built because something in the
 * active scope reads them or is about to. partners, events and social are
 * deferred because the only pages that would read them do not exist yet. The
 * store is deliberately built so adding one is a data change — one call to
 * syn_register_record() in §5 — and not a code change.
 *
 * WHY RECORDS HAVE NO BUILT-IN DEFAULTS, unlike page fields. A page field's
 * default answers "what does this page show before anyone types anything"
 * (CLAUDE.md §7c). A record has no page, so it has no such answer: the section
 * that reads it owns the fallback, which is exactly what the numbers retrofit in
 * step 3 does with its existing $args defaults. syn_record() therefore returns
 * an empty array when nothing is stored, and the caller decides.
 *
 * Contents: 1 registry · 2 the settings screen · 3 saving · 4 the read API ·
 * 5 the three records themselves.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/** The single option every site record lives in. */
define( 'SYN_RECORDS_OPTION', 'syn_records' );

/** The Settings API group the option is registered against. */
define( 'SYN_RECORDS_GROUP', 'syn_records_group' );

/** The admin page slug, i.e. options-general.php?page=syn-records. */
define( 'SYN_RECORDS_PAGE', 'syn-records' );

/* ==========================================================================
   1. The registry

   A record is a repeater and one key of the option. It is normalised through
   fields.php's own syn_normalize_field(), so a record's rows and a page
   repeater's rows are the same shape, sanitised by the same code, and rendered
   by the same UI. Adding a record is adding one array in §5.
   ========================================================================== */

/**
 * Registers one site record, or reads every record registered so far.
 *
 * Side effects: writes to the static registry.
 *
 * @param array|null $record Record definition, or null to read the registry.
 * @return array[] Every registered record, keyed by id.
 */
function syn_record_registry( $record = null ) {
	static $records = array();

	if ( null === $record ) {
		return $records;
	}

	$record = syn_normalize_record( $record );

	if ( $record ) {
		$records[ $record['id'] ] = $record;
	}

	return $records;
}

/**
 * Registers a site record.
 *
 * Call this on the "syn_register_records" action. Accepted keys:
 *   id          string   Required. Also the option key the rows are stored under.
 *   title       string   Heading on the settings screen.
 *   description string   Sentence under the heading.
 *   read_by     string   Where the record surfaces, shown to the editor so the
 *                        consequence of a change is visible before they make it.
 *   row_noun    string   Singular noun for one row: "Service line".
 *   button      string   The add-button text.
 *   row_label   string   Which subfield names each row. Defaults to the first.
 *   min_rows    int      Blank rows shown when the record is empty.
 *   max_rows    int      Ceiling. 0 for none.
 *   fields      array[]  The columns of the record, as repeater subfields.
 *
 * @param array $record Record definition.
 * @return void
 */
function syn_register_record( $record ) {
	syn_record_registry( $record );
}

/**
 * Every registered record, keyed by id.
 *
 * Fires "syn_register_records" once, the first time it is called, then caches —
 * the same lazy pattern as syn_field_groups(), so the settings screen, the save
 * handler and a front-end template all see one registry whichever runs first.
 *
 * @return array[] Records keyed by id.
 */
function syn_record_definitions() {
	static $collected = false;

	if ( ! $collected ) {
		$collected = true;

		/**
		 * Fires once, when the site-record registry is first read.
		 *
		 * Register records here with syn_register_record().
		 */
		do_action( 'syn_register_records' );
	}

	return syn_record_registry();
}

/**
 * Fills a record definition out to its full shape and rejects a malformed one.
 *
 * The record's columns are normalised as a repeater field, which is what lets
 * everything downstream — the UI, the sanitiser, the shaper — be shared with
 * page fields rather than duplicated.
 *
 * @param array $record Raw record definition.
 * @return array|false Normalised record, or false if it cannot be used.
 */
function syn_normalize_record( $record ) {
	$record = (array) $record;
	$id     = isset( $record['id'] ) ? sanitize_key( $record['id'] ) : '';

	if ( '' === $id ) {
		syn_field_log( 'a site record was registered with no id and was ignored' );

		return false;
	}

	$record = array_merge(
		array(
			'title'       => $id,
			'description' => '',
			'read_by'     => '',
			'row_noun'    => __( 'Row', 'synergi' ),
			'button'      => __( 'Add row', 'synergi' ),
			'row_label'   => '',
			'min_rows'    => 1,
			'max_rows'    => 0,
			'single'      => false,
			'fields'      => array(),
		),
		$record
	);

	/*
	 * A "single" record is one set of fields rather than a list of rows: the
	 * heading and intro of a band that appears on seven pages, where there is
	 * exactly one of each and adding a second would be meaningless.
	 *
	 * It is stored as a one-row list all the same, so the sanitiser, the shaper
	 * and the repeater UI need no idea it exists — capping the row count is the
	 * whole implementation, and syn_repeater_ui() already drops the add button
	 * and the row bar at a cap of one. Only syn_record() unwraps it, handing the
	 * caller the group instead of a list of one.
	 */
	$record['single'] = (bool) $record['single'];

	if ( $record['single'] ) {
		$record['min_rows'] = 1;
		$record['max_rows'] = 1;
	}

	$field = syn_normalize_field(
		array(
			'key'       => $id,
			'type'      => 'repeater',
			'label'     => $record['title'],
			'row_noun'  => $record['row_noun'],
			'button'    => $record['button'],
			'row_label' => $record['row_label'],
			'min_rows'  => $record['min_rows'],
			'max_rows'  => $record['max_rows'],
			'subfields' => $record['fields'],
		),
		'records'
	);

	if ( ! $field ) {
		syn_field_log( sprintf( 'site record "%s" has no usable columns and was ignored', $id ) );

		return false;
	}

	return array(
		'id'          => $id,
		'title'       => $record['title'],
		'description' => $record['description'],
		'read_by'     => $record['read_by'],
		'single'      => $record['single'],
		'field'       => $field,
	);
}

/**
 * The input name one record's rows post under.
 *
 * Everything posts inside one option key, so the Settings API hands the whole
 * set to one sanitise callback and a partial save is impossible.
 *
 * @param string $id Record id.
 * @return string e.g. "syn_records[figures]".
 */
function syn_record_input_name( $id ) {
	return SYN_RECORDS_OPTION . '[' . $id . ']';
}

/* ==========================================================================
   2. The settings screen
   ========================================================================== */

add_action( 'admin_menu', 'syn_add_records_page' );
/**
 * Adds Settings → Site records.
 *
 * Side effects: registers one admin page under Settings.
 *
 * @return void
 */
function syn_add_records_page() {
	add_options_page(
		__( 'Synergi site records', 'synergi' ),
		__( 'Site records', 'synergi' ),
		'manage_options',
		SYN_RECORDS_PAGE,
		'syn_render_records_page'
	);
}

add_action( 'admin_init', 'syn_register_records_setting' );
/**
 * Registers the option with the Settings API.
 *
 * Going through the Settings API rather than a hand-rolled admin-post handler
 * is what buys the nonce, the referer check and the manage_options capability
 * check from core instead of from code that has to be got right here
 * (CLAUDE.md §5). options.php enforces the capability through the
 * option_page_capability_{group} filter, which defaults to manage_options.
 *
 * Side effects: registers the syn_records option and its sanitise callback.
 *
 * @return void
 */
function syn_register_records_setting() {
	register_setting(
		SYN_RECORDS_GROUP,
		SYN_RECORDS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'syn_sanitize_records',
			'default'           => array(),

			// These are business facts an editor curates, not an API surface.
			// Nothing outside wp-admin has any business writing them.
			'show_in_rest'      => false,
		)
	);
}

add_action( 'admin_enqueue_scripts', 'syn_enqueue_records_assets' );
/**
 * Loads the repeater and media picker on the records screen, and nowhere else.
 *
 * Side effects: enqueues the shared field UI assets.
 *
 * @param string $hook_suffix Current admin page.
 * @return void
 */
function syn_enqueue_records_assets( $hook_suffix ) {
	if ( 'settings_page_' . SYN_RECORDS_PAGE !== $hook_suffix ) {
		return;
	}

	syn_enqueue_field_ui_assets();
}

/**
 * Renders the site-records screen.
 *
 * Side effects: echoes the page, including the Settings API nonce.
 *
 * @return void
 */
function syn_render_records_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to edit site records.', 'synergi' ) );
	}

	$stored = syn_records_stored();

	echo '<div class="wrap syn-records">';

	printf( '<h1>%s</h1>', esc_html__( 'Synergi site records', 'synergi' ) );

	printf(
		'<p class="syn-records__intro">%s</p>',
		esc_html__( 'Facts about the business that appear on more than one page. Each one is stored here once and read wherever it is needed, so changing a figure or an office changes it everywhere at the same time. Page-specific wording is not edited here — that lives on the page itself.', 'synergi' )
	);

	/*
	 * Called explicitly. Verified against WordPress 7.1 on staging: admin-header.php
	 * contains no settings_errors() call, so a page added under Settings prints
	 * nothing on its own and the rejection messages would silently never appear.
	 */
	settings_errors( SYN_RECORDS_OPTION );

	echo '<form method="post" action="options.php">';

	settings_fields( SYN_RECORDS_GROUP );

	// The wrapper the admin stylesheet hangs its custom properties on — the same
	// one the meta boxes use, so both screens look like one thing.
	echo '<div class="syn-fields">';

	foreach ( syn_record_definitions() as $record ) {
		$rows = isset( $stored[ $record['id'] ] ) ? syn_shape_rows( $stored[ $record['id'] ], $record['field'] ) : array();

		printf(
			'<section class="syn-records__record" id="syn-record-%1$s"><h2>%2$s</h2>',
			esc_attr( $record['id'] ),
			esc_html( $record['title'] )
		);

		if ( '' !== $record['description'] ) {
			printf( '<p class="description">%s</p>', esc_html( $record['description'] ) );
		}

		if ( '' !== $record['read_by'] ) {
			printf(
				'<p class="description syn-records__read-by"><strong>%1$s</strong> %2$s</p>',
				esc_html__( 'Appears on:', 'synergi' ),
				esc_html( $record['read_by'] )
			);
		}

		syn_repeater_ui( $record['field'], syn_record_input_name( $record['id'] ), $rows );

		echo '</section>';
	}

	echo '</div>';

	submit_button( __( 'Save site records', 'synergi' ) );

	echo '</form></div>';
}

/* ==========================================================================
   3. Saving
   ========================================================================== */

/**
 * Sanitises the whole option before it is written.
 *
 * Runs as the Settings API sanitise callback, which means core has already
 * verified the nonce, the referer and the manage_options capability by the time
 * this is reached — and has already run wp_unslash() over the submitted array
 * (verified in wp-admin/options.php on WordPress 7.1). Unslashing again here
 * would eat a backslash out of every apostrophe an editor typed.
 *
 * Every record is rebuilt from the registry rather than from what arrived, so a
 * key nobody registered cannot be smuggled into the option, and a record whose
 * rows were all deleted becomes an empty array rather than disappearing.
 *
 * Side effects: registers settings errors describing anything that was changed
 * or discarded.
 *
 * @param mixed $input Submitted option value, already unslashed by core.
 * @return array[] Clean option value, keyed by record id.
 */
function syn_sanitize_records( $input ) {
	$input      = is_array( $input ) ? $input : array();
	$clean      = array();
	$rejections = array();

	foreach ( syn_record_definitions() as $id => $record ) {
		$submitted = isset( $input[ $id ] ) ? $input[ $id ] : array();

		$clean[ $id ] = syn_sanitize_rows( $submitted, $record['field'], $rejections );
	}

	foreach ( array_values( array_unique( $rejections ) ) as $index => $message ) {
		add_settings_error( SYN_RECORDS_OPTION, 'syn_record_rejected_' . $index, $message, 'warning' );
	}

	if ( ! $rejections ) {
		add_settings_error(
			SYN_RECORDS_OPTION,
			'syn_records_saved',
			__( 'Site records saved.', 'synergi' ),
			'success'
		);
	}

	return $clean;
}

/* ==========================================================================
   4. The read API — what templates and sections call

   Returns VALUES, never escaped markup. The partial that prints a value escapes
   it at the point of printing (CLAUDE.md §5).
   ========================================================================== */

/**
 * The raw option, exactly as stored.
 *
 * Unvalidated on purpose: this is the settings screen's own view of the data, so
 * an editor sees what is actually there. Front-end code wants syn_record().
 *
 * @return array Raw option value, or an empty array.
 */
function syn_records_stored() {
	$stored = get_option( SYN_RECORDS_OPTION, array() );

	return is_array( $stored ) ? $stored : array();
}

/**
 * One record's rows, shape-validated.
 *
 * Never trusts the option at render time (CLAUDE.md §5): the stored value may
 * predate a change to the record's columns, may have been edited by hand, or may
 * not be an array at all. Every row comes back with exactly the declared
 * columns, as strings, so a section can index one without isset() checks.
 *
 * Returns an empty array when the record is empty or unknown. That is the whole
 * contract — the SECTION owns the fallback, not this function. See the file
 * header for why records have no built-in defaults.
 *
 * Deliberately not cached in a static. The option is autoloaded, so get_option()
 * is already answered from the object cache, and shaping a handful of rows costs
 * nothing measurable — whereas a static would hand back stale rows to anything
 * reading the record in the same request that wrote it. Cheap and always right
 * beats fast and occasionally wrong (CLAUDE.md §13).
 *
 * @param string $id Record id, e.g. "figures".
 * @return array[] Rows, each keyed by column. Empty if nothing is stored.
 */
function syn_record( $id ) {
	$id      = sanitize_key( $id );
	$records = syn_record_definitions();

	if ( ! isset( $records[ $id ] ) ) {
		syn_field_log( sprintf( 'a template asked for the site record "%s", which is not registered', $id ) );

		return array();
	}

	$stored = syn_records_stored();
	$rows   = isset( $stored[ $id ] ) ? $stored[ $id ] : array();
	$shaped = syn_shape_rows( $rows, $records[ $id ]['field'] );

	/*
	 * A single record is stored as a one-row list and read as a group. Unwrapping
	 * it here rather than at every call site is what keeps a section's code the
	 * same shape whichever kind of record it reads: ask, get an array, decide
	 * whether it holds anything.
	 *
	 * A blank group comes back as array() rather than a row of empty strings, so
	 * the "is there anything here?" test a section makes before falling back to
	 * its own copy stays a plain truth test.
	 */
	if ( ! empty( $records[ $id ]['single'] ) ) {
		$group = isset( $shaped[0] ) ? $shaped[0] : array();

		return array_filter( $group, static function ( $value ) {
			return '' !== $value;
		} ) ? $group : array();
	}

	return $shaped;
}

/**
 * Whether a record holds anything at all.
 *
 * The question a section asks before deciding between the record and its own
 * fallback copy.
 *
 * @param string $id Record id.
 * @return bool
 */
function syn_has_record( $id ) {
	return (bool) syn_record( $id );
}

/**
 * The URL of the site-records screen.
 *
 * So a SYN_DEBUG note or an admin message can point at it without writing the
 * path out (CLAUDE.md §12, no hard-coded paths or domains).
 *
 * @param string $anchor Optional record id to jump to.
 * @return string
 */
function syn_records_admin_url( $anchor = '' ) {
	$url = admin_url( 'options-general.php?page=' . SYN_RECORDS_PAGE );

	return $anchor ? $url . '#syn-record-' . sanitize_key( $anchor ) : $url;
}

/* ==========================================================================
   5. The three records

   Adding partners, events or social later is one more block in here and nothing
   else — that is the promise made in stage-6-scope.md §8a.
   ========================================================================== */

add_action( 'syn_register_records', 'syn_register_default_records' );
/**
 * Registers the three records built in Stage 6a step 2.
 *
 * Side effects: registers three site records.
 *
 * @return void
 */
function syn_register_default_records() {

	/*
	 * SERVICES — the six service lines. Read by the homepage's services band,
	 * the Our Services listing and every service page, which is precisely why it
	 * is a record: the six exist once, and a seventh added here should appear in
	 * all three places without a developer.
	 */
	syn_register_record(
		array(
			'id'          => 'services',
			'title'       => __( 'Service lines', 'synergi' ),
			'description' => __( 'The service lines Synergi offers. The order here is the order they appear in.', 'synergi' ),
			'read_by'     => __( 'the homepage services band, the Our Services listing, and each service page.', 'synergi' ),
			'row_noun'    => __( 'Service line', 'synergi' ),
			'button'      => __( 'Add service line', 'synergi' ),
			'row_label'   => 'name',
			'min_rows'    => 1,
			'max_rows'    => 12,
			'fields'      => array(
				array(
					'key'         => 'name',
					'type'        => 'text',
					'label'       => __( 'Name', 'synergi' ),
					'description' => __( 'As it should read in the menu and on the card, e.g. Human Resources.', 'synergi' ),
					'max_length'  => 60,
				),
				array(
					'key'         => 'slug',
					'type'        => 'text',
					'label'       => __( 'Reference', 'synergi' ),
					'description' => __( 'A short lowercase name used to link case studies to this service later, e.g. human-resources. Lowercase letters and dashes only.', 'synergi' ),
					'max_length'  => 60,
				),
				array(
					'key'         => 'icon',
					'type'        => 'select',
					'label'       => __( 'Icon', 'synergi' ),
					'description' => __( 'Chosen from the icons the theme ships. Leave blank for no icon.', 'synergi' ),
					'choices'     => syn_service_icon_choices(),
				),
				array(
					'key'         => 'summary',
					'type'        => 'textarea',
					'label'       => __( 'One-line summary', 'synergi' ),
					'description' => __( 'One sentence, as it appears on the service card.', 'synergi' ),
					'max_length'  => 200,
					'rows'        => 2,
				),
				array(
					'key'         => 'url',
					'type'        => 'url',
					'label'       => __( 'Page address', 'synergi' ),
					'description' => __( 'Where the card links to, e.g. /our-services/human-resources/.', 'synergi' ),
					'placeholder' => '/our-services/',
				),
			),
		)
	);

	/*
	 * FIGURES — the key numbers. The record the business asked for by name:
	 * "same numbers as the homepage, one set, used everywhere". The as-at date
	 * is per figure rather than per set because the four were not all counted on
	 * the same day and a single site-wide date would be a claim nobody made.
	 */
	syn_register_record(
		array(
			'id'          => 'figures',
			'title'       => __( 'Key figures', 'synergi' ),
			'description' => __( 'The headline numbers. One set, used everywhere — changing a figure here changes it on every page that shows it.', 'synergi' ),
			'read_by'     => __( 'the homepage figures band and About Us.', 'synergi' ),
			'row_noun'    => __( 'Figure', 'synergi' ),
			'button'      => __( 'Add figure', 'synergi' ),
			'row_label'   => 'label',
			'min_rows'    => 1,
			'max_rows'    => 8,
			'fields'      => array(
				array(
					'key'         => 'value',
					'type'        => 'text',
					'label'       => __( 'The number', 'synergi' ),
					'description' => __( 'Exactly as it should read, including any plus sign or percentage, e.g. 50+ or 10–15%.', 'synergi' ),
					'max_length'  => 20,
					'placeholder' => '50+',
				),
				array(
					'key'         => 'label',
					'type'        => 'text',
					'label'       => __( 'What it counts', 'synergi' ),
					'description' => __( 'e.g. clients we have served.', 'synergi' ),
					'max_length'  => 80,
				),
				array(
					'key'         => 'as_at',
					'type'        => 'date',
					'label'       => __( 'Correct as at', 'synergi' ),
					'description' => __( 'The date this figure was last verified. Shown alongside the number so a reader knows how current it is.', 'synergi' ),
				),
			),
		)
	);

	/*
	 * LOCATIONS — the delivery offices. Kept in this first set because it is the
	 * record most likely to be needed next, though nothing in the active scope
	 * reads it yet (stage-6-scope.md §8a says so plainly).
	 *
	 * "Function delivered" is free text, not a list to choose from, on purpose:
	 * the deck's map legend has five functions but which office carries which is
	 * graphical only, and the business has not confirmed it (handoff §8, open
	 * question 2). Offering five options would be inventing an answer.
	 */
	syn_register_record(
		array(
			'id'          => 'locations',
			'title'       => __( 'Locations', 'synergi' ),
			'description' => __( 'The delivery locations. The order here is the order they appear in.', 'synergi' ),
			'read_by'     => __( 'the homepage locations band, Contact Us, and Global Locations.', 'synergi' ),
			'row_noun'    => __( 'Location', 'synergi' ),
			'button'      => __( 'Add location', 'synergi' ),
			'row_label'   => 'city',
			'min_rows'    => 1,
			'max_rows'    => 20,
			'fields'      => array(
				array(
					'key'        => 'city',
					'type'       => 'text',
					'label'      => __( 'City', 'synergi' ),
					'max_length' => 60,
				),
				array(
					'key'        => 'country',
					'type'       => 'text',
					'label'      => __( 'Country', 'synergi' ),
					'max_length' => 60,
				),
				array(
					'key'         => 'entity',
					'type'        => 'text',
					'label'       => __( 'Legal entity', 'synergi' ),
					'description' => __( 'The registered company name operating from this office.', 'synergi' ),
					'max_length'  => 120,
				),
				array(
					'key'         => 'function',
					'type'        => 'text',
					'label'       => __( 'Function delivered', 'synergi' ),
					'description' => __( 'What this office does, e.g. BPO, or Technology Delivery Centre.', 'synergi' ),
					'max_length'  => 120,
				),
				array(
					'key'         => 'email',
					'type'        => 'email',
					'label'       => __( 'Contact email', 'synergi' ),
					'description' => __( 'Only publish an address that has been cleared for the public site.', 'synergi' ),
				),
				array(
					'key'         => 'image',
					'type'        => 'image',
					'label'       => __( 'Photograph', 'synergi' ),
					'description' => __( 'Optional. Used where a location is shown with a picture.', 'synergi' ),
				),
			),
		)
	);

	/*
	 * WHY — the "Why Companies Choose Synergi" band. A record and not page
	 * fields because the band renders on the homepage and on all six service
	 * pages with identical wording, which is CLAUDE.md §7a's test answered:
	 * change it once, it changes on seven pages.
	 *
	 * Two records rather than one because the band is two different shapes. The
	 * heading exists exactly once, so it is a single group; the cards are a list
	 * that can be reordered and added to. One record cannot be both, and forcing
	 * it would mean a heading pretending to be row one.
	 */
	syn_register_record(
		array(
			'id'          => 'why',
			'title'       => __( 'Why Synergi band — heading', 'synergi' ),
			'description' => __( 'The wording above the four cards. Leave any box empty to keep the built-in text.', 'synergi' ),
			'read_by'     => __( 'the homepage and all six service pages.', 'synergi' ),
			'single'      => true,
			'fields'      => array(
				array(
					'key'         => 'eyebrow',
					'type'        => 'text',
					'label'       => __( 'Eyebrow', 'synergi' ),
					'description' => __( 'The small label above the heading.', 'synergi' ),
					'max_length'  => 60,
				),
				array(
					'key'        => 'title',
					'type'       => 'text',
					'label'      => __( 'Heading', 'synergi' ),
					'max_length' => 120,
				),
				array(
					'key'        => 'intro',
					'type'       => 'textarea',
					'label'      => __( 'Introduction', 'synergi' ),
					'rows'       => 3,
					'max_length' => 400,
				),
			),
		)
	);

	syn_register_record(
		array(
			'id'          => 'why_cards',
			'title'       => __( 'Why Synergi band — the cards', 'synergi' ),
			'description' => __( 'The reasons in the turning deck, in the order they appear. Leave the whole list empty to keep the four built-in cards.', 'synergi' ),
			'read_by'     => __( 'the homepage and all six service pages.', 'synergi' ),
			'row_noun'    => __( 'Card', 'synergi' ),
			'button'      => __( 'Add card', 'synergi' ),
			'row_label'   => 'title',
			'min_rows'    => 1,
			'max_rows'    => 8,
			'fields'      => array(
				array(
					'key'         => 'title',
					'type'        => 'text',
					'label'       => __( 'Heading', 'synergi' ),
					'description' => __( 'The sentence on the card, e.g. Experience across multiple Gulf markets.', 'synergi' ),
					'max_length'  => 120,
				),
				array(
					'key'         => 'short',
					'type'        => 'text',
					'label'       => __( 'Short name', 'synergi' ),
					'description' => __( 'Two or three words, used on the button that turns the deck to this card.', 'synergi' ),
					'max_length'  => 40,
				),
				array(
					'key'         => 'description',
					'type'        => 'textarea',
					'label'       => __( 'One sentence', 'synergi' ),
					'rows'        => 2,
					'max_length'  => 240,
				),
				array(
					'key'         => 'image',
					'type'        => 'image',
					'label'       => __( 'Photograph', 'synergi' ),
					'description' => __( 'Choose one with meaningful alt text already set on it in the media library.', 'synergi' ),
				),
			),
		)
	);
}

/**
 * The service icons an editor may choose from, as value => label.
 *
 * Built from the theme's own icon files rather than typed out, so an icon added
 * to assets/icons/ appears in the dropdown with no second list to update. The
 * "hub-" variants are excluded: they are the same six services drawn a second
 * time for the homepage bubble hub, not separate choices an editor should face.
 *
 * @return array<string,string> Icon slug => human label.
 */
function syn_service_icon_choices() {
	static $choices = null;

	if ( null !== $choices ) {
		return $choices;
	}

	$choices = array();

	foreach ( (array) glob( SYN_DIR . 'assets/icons/*.svg' ) as $file ) {
		$slug = basename( $file, '.svg' );

		if ( 0 === strpos( $slug, 'hub-' ) ) {
			continue;
		}

		// "human-resources" reads as "Human Resources" without a lookup table
		// that would need editing every time an icon is added.
		$choices[ $slug ] = ucwords( str_replace( '-', ' ', $slug ) );
	}

	return $choices;
}

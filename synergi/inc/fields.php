<?php
/**
 * Hand-built page fields — the registry, the admin UI, the save handler and the
 * read API.
 *
 * Loaded by functions.php. This file is the whole page-field engine and emits
 * nothing on the front end: it puts meta boxes on page edit screens, stores what
 * an editor types in postmeta, and hands it back to templates through the
 * syn_field_* readers in §9.
 *
 * Depends on: functions.php constants, and inc/sections.php for
 * syn_attachment_id_by_slug() when an image field declares a fallback slug.
 * Depended on by: templates/ and sections/ from Stage 6c onwards. Nothing reads
 * it yet — Stage 6a step 1 builds the engine and stops (stage-6-scope.md §8).
 *
 * Site records — the figures, locations and services that belong to the whole
 * site rather than to one page — are NOT here. They are a different thing with
 * a different store and they live in inc/records.php (step 2). CLAUDE.md §7a
 * has the test for which is which: if this changes, how many pages should
 * change with it?
 *
 * Two rules hold everywhere below:
 *   - Nothing is trusted on the way in. Every leaf is sanitised by its declared
 *     type, and anything that fails says so in an admin notice rather than
 *     vanishing (CLAUDE.md §5, §13).
 *   - Nothing is trusted on the way out either. The readers in §9 return values,
 *     they do not escape them; the partial that prints a value escapes it at the
 *     point of printing (CLAUDE.md §5).
 *
 * Contents: 1 registry · 2 scoping · 3 meta boxes · 4 admin assets ·
 * 5 field rendering · 6 save · 7 sanitising · 8 rejection notices ·
 * 9 the read API · 10 SYN_DEBUG proving ground.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prefix on every meta key this engine writes.
 *
 * The leading underscore is what keeps these out of the editor's default Custom
 * Fields panel (CLAUDE.md §4): a field an editor can also edit as raw text is a
 * field that can be broken as raw text.
 */
define( 'SYN_META_PREFIX', '_syn_' );

/* ==========================================================================
   1. The registry

   A field group is a plain array. Groups are collected once, on the first call
   to syn_field_groups(), by firing "syn_register_fields" — so a group can be
   registered from anywhere without caring which file loaded first.
   ========================================================================== */

/**
 * Registers one field group, or reads every group registered so far.
 *
 * Side effects: writes to the static registry.
 *
 * @param array|null $group Group definition, or null to read the registry.
 * @return array[] Every registered group, keyed by group id.
 */
function syn_field_registry( $group = null ) {
	static $groups = array();

	if ( null === $group ) {
		return $groups;
	}

	$group = syn_normalize_field_group( $group );

	if ( $group ) {
		$groups[ $group['id'] ] = $group;
	}

	return $groups;
}

/**
 * Registers a field group.
 *
 * Call this on the "syn_register_fields" action. Example:
 *
 *   add_action( 'syn_register_fields', 'my_groups' );
 *   function my_groups() {
 *       syn_register_field_group(
 *           array(
 *               'id'        => 'service_intro',
 *               'title'     => __( 'Service intro', 'synergi' ),
 *               'templates' => array( 'templates/service.php' ),
 *               'fields'    => array(
 *                   array(
 *                       'key'     => 'eyebrow',
 *                       'type'    => 'text',
 *                       'label'   => __( 'Eyebrow', 'synergi' ),
 *                       'default' => __( 'Our services', 'synergi' ),
 *                   ),
 *               ),
 *           )
 *       );
 *   }
 *
 * @param array $group Group definition. syn_normalize_field_group() lists every
 *                     accepted key and its default.
 * @return void
 */
function syn_register_field_group( $group ) {
	syn_field_registry( $group );
}

/**
 * Every registered field group, keyed by id.
 *
 * Fires "syn_register_fields" once, the first time it is called, then caches.
 * Lazy rather than hooked to init so a template, a meta box and a save handler
 * all see the same registry no matter which runs first.
 *
 * @return array[] Groups keyed by id.
 */
function syn_field_groups() {
	static $collected = false;

	if ( ! $collected ) {
		$collected = true;

		/**
		 * Fires once, when the field registry is first read.
		 *
		 * Register field groups here with syn_register_field_group().
		 */
		do_action( 'syn_register_fields' );
	}

	return syn_field_registry();
}

/**
 * One field group by id, or null.
 *
 * @param string $id Group id.
 * @return array|null Normalised group.
 */
function syn_field_group( $id ) {
	$groups = syn_field_groups();

	return isset( $groups[ $id ] ) ? $groups[ $id ] : null;
}

/**
 * Fills a group definition out to its full shape and rejects a malformed one.
 *
 * Accepted keys, with defaults:
 *   id          string   Required. Unique, sanitize_key()-safe.
 *   title       string   Meta box heading. Defaults to the id.
 *   description string   Sentence under the heading. Default "".
 *   post_types  string[] Post types the box may appear on. Default array('page').
 *   templates   string[] Page template slugs it is limited to, e.g.
 *                        "templates/service.php" or "default". Default: none,
 *                        meaning every post of the declared types.
 *   post_ids    int[]    Explicit page IDs it also appears on (CLAUDE.md §7c's
 *                        stated fallback when a template check is not enough).
 *   context     string   add_meta_box() context. Default "normal".
 *   priority    string   add_meta_box() priority. Default "default".
 *   fields      array[]  Field definitions. See syn_normalize_field().
 *
 * Fails loudly under SYN_DEBUG and quietly otherwise (CLAUDE.md §13): a group
 * with no id or no fields is a coding mistake, not editor input, so it must be
 * visible while developing and must not take a page down in production.
 *
 * @param array $group Raw group definition.
 * @return array|false Normalised group, or false if it cannot be used.
 */
function syn_normalize_field_group( $group ) {
	$group = (array) $group;
	$id    = isset( $group['id'] ) ? sanitize_key( $group['id'] ) : '';

	if ( '' === $id ) {
		syn_field_log( 'a field group was registered with no id and was ignored' );

		return false;
	}

	$group = array_merge(
		array(
			'title'       => $id,
			'description' => '',
			'post_types'  => array( 'page' ),
			'templates'   => array(),
			'post_ids'    => array(),
			'context'     => 'normal',
			'priority'    => 'default',
			'fields'      => array(),
		),
		$group
	);

	$group['id']         = $id;
	$group['post_types'] = array_values( array_filter( array_map( 'sanitize_key', (array) $group['post_types'] ) ) );
	$group['templates']  = array_values( array_filter( array_map( 'strval', (array) $group['templates'] ) ) );
	$group['post_ids']   = array_values( array_filter( array_map( 'absint', (array) $group['post_ids'] ) ) );

	$fields = array();
	$claims = array();

	foreach ( (array) $group['fields'] as $field ) {
		$field = syn_normalize_field( $field, $id );

		if ( ! $field ) {
			continue;
		}

		/*
		 * Two fields writing one meta key would make one of them silently
		 * unsaveable, and a link field claims two keys — "cta" owns both
		 * _syn_cta_url and _syn_cta_label — so a collision with a text field
		 * called "cta_url" is not visible by eye. Hence the explicit check
		 * rather than a naming convention nobody remembers.
		 */
		$collision = false;

		foreach ( syn_field_meta_keys( $field ) as $meta_key ) {
			if ( isset( $claims[ $meta_key ] ) ) {
				syn_field_log(
					sprintf(
						'group "%s": meta key %s is claimed by both "%s" and "%s". The second field was ignored.',
						$id,
						$meta_key,
						$claims[ $meta_key ],
						$field['key']
					)
				);
				$collision = true;
				break;
			}
		}

		if ( $collision ) {
			continue;
		}

		foreach ( syn_field_meta_keys( $field ) as $meta_key ) {
			$claims[ $meta_key ] = $field['key'];
		}

		$fields[ $field['key'] ] = $field;
	}

	if ( ! $fields ) {
		syn_field_log( sprintf( 'field group "%s" has no usable fields and was ignored', $id ) );

		return false;
	}

	$group['fields'] = $fields;

	return $group;
}

/**
 * Fills one field definition out to its full shape.
 *
 * Accepted keys, with defaults:
 *   key           string  Required. Meta key without the _syn_ prefix.
 *   type          string  text | textarea | repeater | image | link.
 *   label         string  Visible label. Defaults to the key.
 *   description   string  Help text under the control.
 *   placeholder   string  Input placeholder. Never a substitute for the label
 *                         (CLAUDE.md §9).
 *   default       mixed   What renders when the field is empty (CLAUDE.md §7c).
 *                         A string for text/textarea/image, array( url, label )
 *                         for link, array of rows for repeater.
 *   max_length    int     0 = no limit. Over-length input is trimmed and the
 *                         trim is reported.
 *   rows          int     textarea height.
 *   fallback_slug string  image only. Attachment slug used when the field is
 *                         empty, so Stage 5's slug lookups keep working on the
 *                         day the fields ship (CLAUDE.md §7b).
 *   subfields     array[] repeater only. See syn_normalize_subfield().
 *   row_label     string  repeater only. Which subfield names each row in the
 *                         admin list. Defaults to the first subfield.
 *   row_noun      string  repeater only. Singular noun for a row, used in the
 *                         screen-reader labels: "Move Capability 2 up".
 *   button        string  repeater only. The add-button text.
 *   min_rows      int     repeater only. Blank rows rendered when empty.
 *   max_rows      int     repeater only. 0 = unlimited.
 *
 * @param array  $field    Raw field definition.
 * @param string $group_id Owning group id, for error messages only.
 * @return array|false Normalised field, or false if it cannot be used.
 */
function syn_normalize_field( $field, $group_id ) {
	$field = (array) $field;
	$key   = isset( $field['key'] ) ? sanitize_key( $field['key'] ) : '';
	$type  = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';

	if ( '' === $key ) {
		syn_field_log( sprintf( 'group "%s": a field with no key was ignored', $group_id ) );

		return false;
	}

	if ( ! in_array( $type, array( 'text', 'textarea', 'repeater', 'image', 'link' ), true ) ) {
		syn_field_log( sprintf( 'group "%s": field "%s" has the unknown type "%s" and was ignored', $group_id, $key, $type ) );

		return false;
	}

	$field = array_merge(
		array(
			'label'         => $key,
			'description'   => '',
			'placeholder'   => '',
			'default'       => 'repeater' === $type ? array() : '',
			'max_length'    => 0,
			'rows'          => 3,
			'fallback_slug' => '',
			'subfields'     => array(),
			'row_label'     => '',
			'row_noun'      => __( 'Item', 'synergi' ),
			'button'        => __( 'Add item', 'synergi' ),
			'min_rows'      => 1,
			'max_rows'      => 0,
		),
		$field
	);

	$field['key']        = $key;
	$field['type']       = $type;
	$field['max_length'] = absint( $field['max_length'] );
	$field['rows']       = max( 2, absint( $field['rows'] ) );
	$field['min_rows']   = absint( $field['min_rows'] );
	$field['max_rows']   = absint( $field['max_rows'] );

	if ( 'repeater' === $type ) {
		$subfields = array();

		foreach ( (array) $field['subfields'] as $sub ) {
			$sub = syn_normalize_subfield( $sub, $group_id, $key );

			if ( $sub ) {
				$subfields[ $sub['key'] ] = $sub;
			}
		}

		if ( ! $subfields ) {
			syn_field_log( sprintf( 'group "%s": repeater "%s" has no usable subfields and was ignored', $group_id, $key ) );

			return false;
		}

		$field['subfields'] = $subfields;

		// The row bar shows one subfield's value, so a long list of rows stays
		// readable when the bodies are scrolled past. Defaults to the first
		// subfield, which is nearly always the title.
		if ( '' === $field['row_label'] || ! isset( $subfields[ $field['row_label'] ] ) ) {
			$field['row_label'] = (string) key( $subfields );
		}
	}

	if ( 'link' === $type ) {
		$default = (array) $field['default'];

		$field['default'] = array(
			'url'   => isset( $default['url'] ) ? (string) $default['url'] : '',
			'label' => isset( $default['label'] ) ? (string) $default['label'] : '',
		);
	}

	return $field;
}

/**
 * Fills one repeater subfield out to its full shape.
 *
 * Subfields are leaves: they map straight onto the sanitisers in §7 and cannot
 * nest. A repeater inside a repeater is not supported and is not wanted —
 * CLAUDE.md §7c's "keep it boring" is what keeps this UI debuggable.
 *
 * @param array  $sub       Raw subfield definition.
 * @param string $group_id  Owning group id, for error messages.
 * @param string $field_key Owning repeater key, for error messages.
 * @return array|false Normalised subfield, or false if it cannot be used.
 */
function syn_normalize_subfield( $sub, $group_id, $field_key ) {
	$sub  = (array) $sub;
	$key  = isset( $sub['key'] ) ? sanitize_key( $sub['key'] ) : '';
	$type = isset( $sub['type'] ) ? sanitize_key( $sub['type'] ) : 'text';

	if ( '' === $key ) {
		syn_field_log( sprintf( 'group "%s": repeater "%s" has a subfield with no key', $group_id, $field_key ) );

		return false;
	}

	if ( ! in_array( $type, syn_leaf_types(), true ) ) {
		syn_field_log( sprintf( 'group "%s": repeater "%s" subfield "%s" has the unknown type "%s"', $group_id, $field_key, $key, $type ) );

		return false;
	}

	$sub = array_merge(
		array(
			'label'         => $key,
			'description'   => '',
			'placeholder'   => '',
			'default'       => '',
			'max_length'    => 0,
			'rows'          => 3,
			'fallback_slug' => '',
			'choices'       => array(),
		),
		$sub
	);

	$sub['key']        = $key;
	$sub['type']       = $type;
	$sub['max_length'] = absint( $sub['max_length'] );
	$sub['rows']       = max( 2, absint( $sub['rows'] ) );
	$sub['choices']    = (array) $sub['choices'];

	if ( 'select' === $type && ! $sub['choices'] ) {
		syn_field_log( sprintf( 'group "%s": repeater "%s" subfield "%s" is a select with no choices', $group_id, $field_key, $key ) );

		return false;
	}

	return $sub;
}

/**
 * The leaf types a single value can be sanitised as.
 *
 * "html" is the one that accepts markup, and it exists for exactly one thing:
 * the FAQ answer, where a link inside the answer is legitimate content
 * (CLAUDE.md §7b). Everything else is plain text.
 *
 * select, date and email were added for the site records in step 2. Each exists
 * because the record it serves has a real data type and "text with a hopeful
 * placeholder" is how a set of six service icons, four as-at dates and five
 * office addresses drift into six different formats — which is the exact failure
 * the records store was built to prevent (CLAUDE.md §7a). A wrong icon slug, an
 * ambiguous date and a mistyped address each now fail on save, by name.
 *
 * @return string[]
 */
function syn_leaf_types() {
	return array( 'text', 'textarea', 'html', 'url', 'image', 'int', 'select', 'date', 'email' );
}

/**
 * Every postmeta key a field owns.
 *
 * All types own one key except "link", which owns two — a URL is useless
 * without the words that go on it, and two keys keep both readable in the
 * database and sanitisable by their own type (CLAUDE.md §7b).
 *
 * @param array $field Normalised field.
 * @return string[] Meta keys, prefix included.
 */
function syn_field_meta_keys( $field ) {
	if ( 'link' === $field['type'] ) {
		return array(
			SYN_META_PREFIX . $field['key'] . '_url',
			SYN_META_PREFIX . $field['key'] . '_label',
		);
	}

	return array( SYN_META_PREFIX . $field['key'] );
}

/**
 * Writes an engine-level mistake to the error log.
 *
 * These are developer errors — a malformed group, a duplicate meta key — never
 * editor input. Editor input that fails gets an admin notice instead (§8).
 *
 * Side effects: writes one line to the PHP error log when SYN_DEBUG is on.
 *
 * @param string $message What went wrong.
 * @return void
 */
function syn_field_log( $message ) {
	if ( SYN_DEBUG ) {
		error_log( '[synergi] fields: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- CLAUDE.md §13 requires the theme's own prefixed log line, gated on SYN_DEBUG.
	}
}

/* ==========================================================================
   2. Which groups apply to which post
   ========================================================================== */

/**
 * The field groups that belong on one post's edit screen.
 *
 * A group with neither "templates" nor "post_ids" applies to every post of its
 * declared types. A group with either applies only where one of them matches —
 * that is CLAUDE.md §7c's "field boxes appear only on pages using the matching
 * template", with the page-ID list as the stated fallback.
 *
 * Known limitation, and it is core's, not ours: the template read here is the
 * SAVED template. Switching a page's template in the editor sidebar does not
 * reveal or hide a meta box until the page has been saved and reloaded, because
 * meta boxes are registered server-side before the editor loads. Say so in the
 * box description rather than trying to out-clever it.
 *
 * @param WP_Post|int|null $post Post or post ID. Defaults to the current post.
 * @return array[] Matching groups, keyed by id.
 */
function syn_field_groups_for_post( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return array();
	}

	$template = syn_post_template_slug( $post );
	$matched  = array();

	foreach ( syn_field_groups() as $id => $group ) {
		if ( ! in_array( $post->post_type, $group['post_types'], true ) ) {
			continue;
		}

		$scoped = $group['templates'] || $group['post_ids'];

		if ( $scoped
			&& ! in_array( $template, $group['templates'], true )
			&& ! in_array( (int) $post->ID, $group['post_ids'], true ) ) {
			continue;
		}

		$matched[ $id ] = $group;
	}

	return $matched;
}

/**
 * The page template a post uses, with the default named rather than blank.
 *
 * get_page_template_slug() returns an empty string for the default template,
 * which cannot be written into a group's "templates" list without looking like
 * a mistake. "default" is core's own name for it in the editor dropdown.
 *
 * @param WP_Post|int|null $post Post or post ID.
 * @return string Template slug, e.g. "templates/service.php" or "default".
 */
function syn_post_template_slug( $post = null ) {
	$slug = (string) get_page_template_slug( $post );

	return '' === $slug ? 'default' : $slug;
}

/* ==========================================================================
   3. Meta boxes
   ========================================================================== */

add_action( 'add_meta_boxes', 'syn_add_field_meta_boxes', 10, 2 );
/**
 * Adds one meta box per field group that applies to the post being edited.
 *
 * Side effects: calls add_meta_box() once per matching group.
 *
 * @param string  $post_type Post type of the screen.
 * @param WP_Post $post      Post being edited.
 * @return void
 */
function syn_add_field_meta_boxes( $post_type, $post ) {
	foreach ( syn_field_groups_for_post( $post ) as $id => $group ) {
		add_meta_box(
			'syn-fields-' . $id,
			$group['title'],
			'syn_render_field_meta_box',
			$post_type,
			$group['context'],
			$group['priority'],
			array( 'group_id' => $id )
		);
	}
}

/**
 * Renders one field group's meta box.
 *
 * Side effects: echoes the box markup, including the group's nonce, and prints
 * and clears any pending rejection notice (§8) — which is how a rejection
 * reaches the screen in the block editor, where the meta-box area is re-rendered
 * on its own without a page load and admin_notices never runs.
 *
 * @param WP_Post $post Post being edited.
 * @param array   $box  Meta box arguments; $box['args']['group_id'] names the group.
 * @return void
 */
function syn_render_field_meta_box( $post, $box ) {
	$group_id = isset( $box['args']['group_id'] ) ? $box['args']['group_id'] : '';
	$group    = syn_field_group( $group_id );

	if ( ! $group ) {
		echo '<p>' . esc_html__( 'This field group is no longer registered.', 'synergi' ) . '</p>';

		return;
	}

	wp_nonce_field( syn_field_nonce_action( $group_id ), syn_field_nonce_name( $group_id ) );

	// One wrapper around everything the box prints, because assets/admin/fields.css
	// declares its handful of admin-grey custom properties here — the notice needs
	// them as much as the fields do.
	echo '<div class="syn-fields">';

	syn_print_field_notice( (int) $post->ID );

	if ( '' !== $group['description'] ) {
		echo '<p class="description syn-field-group__description">' . esc_html( $group['description'] ) . '</p>';
	}

	echo '<div class="syn-field-group">';

	foreach ( $group['fields'] as $field ) {
		syn_render_field( $field, (int) $post->ID );
	}

	echo '</div></div>';
}

/**
 * The nonce action for one group.
 *
 * @param string $group_id Group id.
 * @return string
 */
function syn_field_nonce_action( $group_id ) {
	return 'syn_fields_save_' . $group_id;
}

/**
 * The nonce input name for one group.
 *
 * @param string $group_id Group id.
 * @return string
 */
function syn_field_nonce_name( $group_id ) {
	return '_syn_fields_nonce_' . $group_id;
}

/* ==========================================================================
   4. Admin assets

   Enqueued only on a post edit screen that actually shows a field box
   (CLAUDE.md §7c). Nothing here loads on the front end, on the dashboard, or on
   a page whose template has no fields.
   ========================================================================== */

add_action( 'admin_enqueue_scripts', 'syn_enqueue_field_admin_assets' );
/**
 * Enqueues the repeater and media-picker assets on edit screens that need them.
 *
 * wp_enqueue_media() pulls in core's media modal, which has jQuery among its own
 * dependencies. That is core's dependency, not ours: no line of theme code calls
 * jQuery, which is what CLAUDE.md §2.4 forbids. The alternative — hand-rolling a
 * media browser — would be a far larger thing to maintain than the rule saves.
 *
 * Side effects: enqueues core media, one stylesheet and one script, and inlines
 * the script's translated strings.
 *
 * @param string $hook_suffix Current admin page.
 * @return void
 */
function syn_enqueue_field_admin_assets( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$post = get_post();

	if ( ! $post || ! syn_field_groups_for_post( $post ) ) {
		return;
	}

	syn_enqueue_field_ui_assets();
}

/**
 * Enqueues the field UI itself, for any screen that renders one.
 *
 * Separate from the hook above because the site-records screen (inc/records.php,
 * Stage 6a step 2) uses the same repeater and the same media picker, and there
 * is no sense in two copies of the same three enqueues drifting apart. Safe to
 * call more than once — wp_enqueue_* deduplicates by handle.
 *
 * Side effects: enqueues core media, one stylesheet and one script, and inlines
 * the script's translated strings.
 *
 * @return void
 */
function syn_enqueue_field_ui_assets() {
	wp_enqueue_media();

	wp_enqueue_style(
		'synergi-admin-fields',
		SYN_URI . 'assets/admin/fields.css',
		array(),
		syn_asset_version( 'assets/admin/fields.css' )
	);

	wp_enqueue_script(
		'synergi-admin-fields',
		SYN_URI . 'assets/admin/fields.js',
		array(),
		syn_asset_version( 'assets/admin/fields.js' ),
		true
	);

	/*
	 * Strings, not markup. The row template itself is rendered server-side into
	 * a <template> element per repeater, so the JS never builds markup out of
	 * strings and there is exactly one place a row's HTML is written.
	 */
	wp_add_inline_script(
		'synergi-admin-fields',
		'window.synFields = ' . wp_json_encode(
			array(
				'debug'        => (bool) SYN_DEBUG,
				'chooseImage'  => __( 'Choose image', 'synergi' ),
				'useImage'     => __( 'Use this image', 'synergi' ),
				/* translators: 1: row noun, e.g. "Capability". 2: row number. */
				'rowAdded'     => __( '%1$s %2$d added.', 'synergi' ),
				/* translators: 1: row noun, e.g. "Capability". 2: row number. */
				'rowRemoved'   => __( '%1$s %2$d removed.', 'synergi' ),
				/* translators: 1: row noun. 2: its new position. 3: total rows. */
				'rowMoved'     => __( '%1$s moved to position %2$d of %3$d.', 'synergi' ),
				/* translators: %d: maximum number of rows allowed. */
				'maxRows'      => __( 'This list is limited to %d items.', 'synergi' ),
				/* translators: 1: row noun. 2: row number. */
				'moveUp'       => __( 'Move %1$s %2$d up', 'synergi' ),
				/* translators: 1: row noun. 2: row number. */
				'moveDown'     => __( 'Move %1$s %2$d down', 'synergi' ),
				/* translators: 1: row noun. 2: row number. */
				'removeRow'    => __( 'Remove %1$s %2$d', 'synergi' ),
				/* translators: 1: row noun. 2: row number. */
				'rowLegend'    => __( '%1$s %2$d', 'synergi' ),
				'untitled'     => __( '(untitled)', 'synergi' ),
				'imageSet'     => __( 'Image selected.', 'synergi' ),
				'imageCleared' => __( 'Image removed.', 'synergi' ),
			)
		) . ';',
		'before'
	);
}

/* ==========================================================================
   5. Field rendering (admin)

   Every value printed below goes through esc_attr() or esc_textarea(). Stored
   meta is escaped here as well as on the front end, because CLAUDE.md §5 says
   never to trust stored meta at render time — and wp-admin is a render time.
   ========================================================================== */

/**
 * Renders one field's control.
 *
 * Side effects: echoes markup.
 *
 * @param array $field   Normalised field.
 * @param int   $post_id Post being edited.
 * @return void
 */
function syn_render_field( $field, $post_id ) {
	$name = SYN_META_PREFIX . $field['key'];
	$id   = 'syn-f-' . $field['key'];

	echo '<div class="syn-field syn-field--' . esc_attr( $field['type'] ) . '">';

	switch ( $field['type'] ) {
		case 'textarea':
			printf(
				'<label class="syn-field__label" for="%1$s">%2$s</label><textarea class="large-text" id="%1$s" name="%3$s" rows="%4$d" placeholder="%5$s">%6$s</textarea>',
				esc_attr( $id ),
				esc_html( $field['label'] ),
				esc_attr( $name ),
				(int) $field['rows'],
				esc_attr( $field['placeholder'] ),
				esc_textarea( syn_stored_value( $name, $post_id ) )
			);
			break;

		case 'image':
			printf( '<span class="syn-field__label">%s</span>', esc_html( $field['label'] ) );
			syn_render_image_control( $name, $id, (int) syn_stored_value( $name, $post_id ), $field );
			break;

		case 'link':
			syn_render_link_control( $field, $post_id );
			break;

		case 'repeater':
			printf( '<span class="syn-field__label">%s</span>', esc_html( $field['label'] ) );
			syn_render_repeater_control( $field, $post_id );
			break;

		case 'text':
		default:
			printf(
				'<label class="syn-field__label" for="%1$s">%2$s</label><input class="large-text" type="text" id="%1$s" name="%3$s" value="%4$s" placeholder="%5$s"%6$s>',
				esc_attr( $id ),
				esc_html( $field['label'] ),
				esc_attr( $name ),
				esc_attr( syn_stored_value( $name, $post_id ) ),
				esc_attr( $field['placeholder'] ),
				$field['max_length'] ? ' maxlength="' . (int) $field['max_length'] . '"' : ''
			);
			break;
	}

	syn_render_field_description( $field );

	echo '</div>';
}

/**
 * Prints a field's help text, and the sentence that says what shows when it is
 * left empty.
 *
 * Every field has a default (CLAUDE.md §7c), and an editor who cannot see the
 * default has no way to know that clearing a box does not clear the page.
 *
 * Side effects: echoes markup.
 *
 * @param array $field Normalised field.
 * @return void
 */
function syn_render_field_description( $field ) {
	$notes = array();

	if ( '' !== $field['description'] ) {
		$notes[] = $field['description'];
	}

	$default = syn_field_default_summary( $field );

	if ( '' !== $default ) {
		/* translators: %s: the text that renders when a field is left empty. */
		$notes[] = sprintf( __( 'Left empty, the page shows: %s', 'synergi' ), $default );
	}

	if ( $field['max_length'] ) {
		/* translators: %d: maximum number of characters. */
		$notes[] = sprintf( __( 'Maximum %d characters.', 'synergi' ), (int) $field['max_length'] );
	}

	if ( $notes ) {
		echo '<p class="description">' . esc_html( implode( ' ', $notes ) ) . '</p>';
	}
}

/**
 * A one-line, human-readable summary of a field's default.
 *
 * @param array $field Normalised field.
 * @return string Summary, or "" when there is nothing useful to say.
 */
function syn_field_default_summary( $field ) {
	if ( 'repeater' === $field['type'] ) {
		$count = count( (array) $field['default'] );

		if ( ! $count ) {
			return '';
		}

		/* translators: %d: number of rows in a repeater's default. */
		return sprintf( _n( '%d built-in item.', '%d built-in items.', $count, 'synergi' ), $count );
	}

	if ( 'link' === $field['type'] ) {
		$label = $field['default']['label'];

		return '' === $label ? '' : '“' . $label . '”';
	}

	if ( 'image' === $field['type'] ) {
		return '' === $field['fallback_slug'] ? '' : __( 'the built-in photograph.', 'synergi' );
	}

	$default = trim( (string) $field['default'] );

	if ( '' === $default ) {
		return '';
	}

	if ( function_exists( 'mb_strlen' ) && mb_strlen( $default ) > 90 ) {
		$default = mb_substr( $default, 0, 90 ) . '…';
	}

	return '“' . $default . '”';
}

/**
 * Renders an image picker: hidden attachment ID, preview, choose and remove.
 *
 * Used for both a top-level image field and an image subfield inside a repeater,
 * which is why it takes a name and an id rather than reading them from a field.
 *
 * Side effects: echoes markup.
 *
 * @param string $name          Input name.
 * @param string $id            Input id, used for the preview's aria wiring.
 * @param int    $attachment_id Currently stored attachment ID, or 0.
 * @param array  $field         Field or subfield definition, for the fallback note.
 * @return void
 */
function syn_render_image_control( $name, $id, $attachment_id, $field = array() ) {
	$image = $attachment_id ? wp_get_attachment_image( $attachment_id, 'thumbnail', false, array( 'class' => 'syn-image-field__thumb' ) ) : '';
	$has   = '' !== $image;

	$empty_text = __( 'No image chosen', 'synergi' );

	printf( '<div class="syn-image-field" data-syn-image><input type="hidden" id="%1$s" name="%2$s" value="%3$d" data-syn-image-input>', esc_attr( $id ), esc_attr( $name ), (int) $attachment_id );

	// The empty-state wording travels on the element so fields.js can restore it
	// after a Remove without holding a second copy of the string (CLAUDE.md §12,
	// translatable strings stay in PHP).
	printf( '<div class="syn-image-field__preview" data-syn-image-preview data-empty-text="%s">', esc_attr( $empty_text ) );

	if ( $has ) {
		echo wp_kses_post( $image );
	} else {
		echo '<span class="syn-image-field__empty">' . esc_html( $empty_text ) . '</span>';
	}

	echo '</div><p class="syn-image-field__actions">';

	printf(
		'<button type="button" class="button" data-syn-image-choose>%s</button> ',
		esc_html__( 'Choose image', 'synergi' )
	);

	printf(
		'<button type="button" class="button-link-delete" data-syn-image-remove%s>%s</button>',
		$has ? '' : ' hidden',
		esc_html__( 'Remove', 'synergi' )
	);

	echo '</p>';

	/*
	 * The attachment ID is stored, not a path — so the picture survives an
	 * upload folder move and comes with core's srcset, sizes and alt text
	 * (CLAUDE.md §6, §8). A stored ID that is no longer an attachment is
	 * rejected on save, not silently kept.
	 */
	if ( $attachment_id && ! wp_attachment_is_image( $attachment_id ) ) {
		echo '<p class="description syn-field__warning">' . esc_html__( 'The stored image is missing from the media library. Choose another.', 'synergi' ) . '</p>';
	}

	echo '</div>';
}

/**
 * Renders a link field: a URL and the words that go on it.
 *
 * Side effects: echoes markup.
 *
 * @param array $field   Normalised field of type "link".
 * @param int   $post_id Post being edited.
 * @return void
 */
function syn_render_link_control( $field, $post_id ) {
	$url_name   = SYN_META_PREFIX . $field['key'] . '_url';
	$label_name = SYN_META_PREFIX . $field['key'] . '_label';
	$url_id     = 'syn-f-' . $field['key'] . '-url';
	$label_id   = 'syn-f-' . $field['key'] . '-label';

	printf( '<span class="syn-field__label">%s</span><div class="syn-link-field">', esc_html( $field['label'] ) );

	printf(
		'<p class="syn-link-field__part"><label for="%1$s">%2$s</label><input class="regular-text code" type="url" id="%1$s" name="%3$s" value="%4$s" placeholder="https://"></p>',
		esc_attr( $url_id ),
		esc_html__( 'Address', 'synergi' ),
		esc_attr( $url_name ),
		esc_attr( syn_stored_value( $url_name, $post_id ) )
	);

	printf(
		'<p class="syn-link-field__part"><label for="%1$s">%2$s</label><input class="regular-text" type="text" id="%1$s" name="%3$s" value="%4$s"></p>',
		esc_attr( $label_id ),
		esc_html__( 'Button text', 'synergi' ),
		esc_attr( $label_name ),
		esc_attr( syn_stored_value( $label_name, $post_id ) )
	);

	echo '</div>';
}

/**
 * Renders a repeater: the stored rows, the add button, and the blank-row
 * template the JS clones.
 *
 * Rows post as indexed inputs — _syn_capabilities[3][title] — rather than as a
 * JSON string built by JavaScript. Three reasons, in order of importance:
 * with JavaScript off the existing rows still edit and still save; PHP never has
 * to json_decode() something a browser assembled; and PHP preserves the order
 * numeric keys arrive in, so moving a row in the DOM is enough to reorder it and
 * the indices never need renumbering. Storage is still one JSON array in one
 * meta key, as CLAUDE.md §7b requires — the JSON is written by the save handler
 * in §6, where every leaf has already been sanitised.
 *
 * Side effects: echoes markup.
 *
 * @param array $field   Normalised field of type "repeater".
 * @param int   $post_id Post being edited.
 * @return void
 */
function syn_render_repeater_control( $field, $post_id ) {
	$name = SYN_META_PREFIX . $field['key'];

	syn_repeater_ui( $field, $name, syn_decode_rows( syn_stored_value( $name, $post_id ), $field ) );
}

/**
 * Renders a repeater's UI from rows it is handed.
 *
 * The store-agnostic half of the repeater. syn_render_repeater_control() above
 * reads postmeta and calls this; the site-records screen (inc/records.php) reads
 * one key of the syn_records option and calls the same function with the same
 * $field shape. One UI, two stores, no second implementation to keep in step —
 * which is the whole reason step 2 could be "the same repeater UI" at all.
 *
 * Side effects: echoes markup.
 *
 * @param array  $field Normalised field of type "repeater".
 * @param string $name  Input name the rows post under, e.g. "_syn_capabilities"
 *                      or "syn_records[figures]".
 * @param array  $rows  Rows to render, each keyed by subfield.
 * @return void
 */
function syn_repeater_ui( $field, $name, $rows ) {
	$rows = array_values( (array) $rows );

	// A repeater with no saved rows still shows something to type into, so an
	// editor is never faced with an empty box and no obvious next move — and so
	// the field is usable with JavaScript off, where the add button cannot work.
	$blanks = max( 0, (int) $field['min_rows'] - count( $rows ) );

	for ( $i = 0; $i < $blanks; $i++ ) {
		$rows[] = array();
	}

	printf(
		'<div class="syn-repeater" data-syn-repeater data-next-index="%1$d" data-max-rows="%2$d" data-row-noun="%3$s">',
		count( $rows ) + 1,
		(int) $field['max_rows'],
		esc_attr( $field['row_noun'] )
	);

	echo '<div class="syn-repeater__rows" data-syn-repeater-rows>';

	foreach ( array_values( $rows ) as $index => $row ) {
		syn_render_repeater_row( $field, $name, $index, $index + 1, $row );
	}

	echo '</div>';

	/*
	 * A group capped at one row is not a list, it is a single set of fields —
	 * the "why" band's heading, say, which exists once for the whole site. An
	 * add button that can never add anything is a control that lies about what
	 * it does, so it is not drawn. syn_render_repeater_row() drops the row bar
	 * for the same reason, and between them a max_rows of 1 renders as a plain
	 * group of fields with no list chrome around it.
	 */
	if ( 1 !== (int) $field['max_rows'] ) {
		printf(
			'<p class="syn-repeater__add"><button type="button" class="button" data-syn-repeater-add>%s</button></p>',
			esc_html( $field['button'] )
		);
	}

	/*
	 * The blank row lives in a <template>: its inputs are inert, so they are
	 * never submitted, and with JavaScript off it renders nothing at all.
	 * __i__ is the index placeholder the script replaces — it can never collide
	 * with editor content because the template holds no editor content.
	 */
	echo '<template data-syn-repeater-template>';
	syn_render_repeater_row( $field, $name, '__i__', '__n__', array() );
	echo '</template>';

	// Where fields.js says what just happened. Without it, adding, removing and
	// reordering rows are all silent to a screen reader (CLAUDE.md §9).
	echo '<p class="screen-reader-text" role="status" aria-live="polite" data-syn-repeater-status></p></div>';
}

/**
 * Renders one repeater row.
 *
 * Side effects: echoes markup.
 *
 * @param array      $field  Normalised repeater field.
 * @param string     $name   The repeater's input name, prefix included.
 * @param int|string $index  Row index used in the input names, or "__i__" for
 *                           the blank template row.
 * @param int|string $number Human row number shown in the bar, or "__n__".
 * @param array      $row    Stored values for this row, keyed by subfield.
 * @return void
 */
function syn_render_repeater_row( $field, $name, $index, $number, $row ) {
	$noun  = $field['row_noun'];
	$title = isset( $row[ $field['row_label'] ] ) ? (string) $row[ $field['row_label'] ] : '';

	echo '<fieldset class="syn-repeater__row" data-syn-repeater-row>';

	printf(
		'<legend class="screen-reader-text" data-syn-repeater-legend>%1$s %2$s</legend>',
		esc_html( $noun ),
		esc_html( (string) $number )
	);

	/*
	 * The bar carries the row number and the move and remove controls. A group
	 * capped at one row has nothing to number, nothing to reorder against and
	 * nothing it is allowed to remove, so it gets no bar at all and reads as a
	 * plain set of fields. See the matching note in syn_repeater_ui().
	 */
	if ( 1 !== (int) $field['max_rows'] ) {
		echo '<div class="syn-repeater__bar">';

		printf(
			'<span class="syn-repeater__number" data-syn-repeater-number>%s</span>',
			esc_html( (string) $number )
		);

		printf(
			'<span class="syn-repeater__title" data-syn-repeater-title>%s</span>',
			esc_html( $title )
		);

		echo '<span class="syn-repeater__controls">';

		/* translators: 1: row noun, e.g. "Capability". 2: row number. */
		$up_label = sprintf( __( 'Move %1$s %2$s up', 'synergi' ), $noun, (string) $number );
		/* translators: 1: row noun, e.g. "Capability". 2: row number. */
		$down_label = sprintf( __( 'Move %1$s %2$s down', 'synergi' ), $noun, (string) $number );
		/* translators: 1: row noun, e.g. "Capability". 2: row number. */
		$remove_label = sprintf( __( 'Remove %1$s %2$s', 'synergi' ), $noun, (string) $number );

		printf(
			'<button type="button" class="button button-small" data-syn-repeater-up aria-label="%s"><span aria-hidden="true">&uarr;</span></button>',
			esc_attr( $up_label )
		);
		printf(
			'<button type="button" class="button button-small" data-syn-repeater-down aria-label="%s"><span aria-hidden="true">&darr;</span></button>',
			esc_attr( $down_label )
		);
		printf(
			'<button type="button" class="button button-small syn-repeater__remove" data-syn-repeater-remove aria-label="%s"><span aria-hidden="true">&times;</span></button>',
			esc_attr( $remove_label )
		);

		echo '</span></div>';
	}

	echo '<div class="syn-repeater__body">';

	foreach ( $field['subfields'] as $sub ) {
		$sub_name = sprintf( '%s[%s][%s]', $name, $index, $sub['key'] );
		$sub_id   = sprintf( 'syn-f-%s-%s-%s', $field['key'], $index, $sub['key'] );
		$value    = isset( $row[ $sub['key'] ] ) ? $row[ $sub['key'] ] : '';
		$is_title = $sub['key'] === $field['row_label'];

		echo '<div class="syn-field syn-field--' . esc_attr( $sub['type'] ) . '">';

		switch ( $sub['type'] ) {
			case 'image':
				printf( '<span class="syn-field__label">%s</span>', esc_html( $sub['label'] ) );
				syn_render_image_control( $sub_name, $sub_id, (int) $value, $sub );
				break;

			case 'textarea':
			case 'html':
				printf(
					'<label class="syn-field__label" for="%1$s">%2$s</label><textarea class="large-text" id="%1$s" name="%3$s" rows="%4$d" placeholder="%5$s"%6$s>%7$s</textarea>',
					esc_attr( $sub_id ),
					esc_html( $sub['label'] ),
					esc_attr( $sub_name ),
					(int) $sub['rows'],
					esc_attr( $sub['placeholder'] ),
					$is_title ? ' data-syn-repeater-title-source' : '',
					esc_textarea( (string) $value )
				);
				break;

			case 'select':
				printf(
					'<label class="syn-field__label" for="%1$s">%2$s</label><select id="%1$s" name="%3$s" class="syn-field__select"%4$s>',
					esc_attr( $sub_id ),
					esc_html( $sub['label'] ),
					esc_attr( $sub_name ),
					$is_title ? ' data-syn-repeater-title-source' : ''
				);

				/*
				 * An empty first option, always. Without it a brand-new blank
				 * row would post the first real choice, look like it holds data,
				 * and survive the empty-row cull in syn_sanitize_rows().
				 */
				printf( '<option value="">%s</option>', esc_html__( '— Select —', 'synergi' ) );

				foreach ( $sub['choices'] as $syn_choice => $syn_choice_label ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $syn_choice ),
						selected( (string) $value, (string) $syn_choice, false ),
						esc_html( $syn_choice_label )
					);
				}

				echo '</select>';
				break;

			default:
				$syn_input_types = array(
					'url'   => 'url',
					'int'   => 'number',
					'date'  => 'date',
					'email' => 'email',
				);

				printf(
					'<label class="syn-field__label" for="%1$s">%2$s</label><input class="large-text" type="%3$s" id="%1$s" name="%4$s" value="%5$s" placeholder="%6$s"%7$s%8$s>',
					esc_attr( $sub_id ),
					esc_html( $sub['label'] ),
					isset( $syn_input_types[ $sub['type'] ] ) ? $syn_input_types[ $sub['type'] ] : 'text',
					esc_attr( $sub_name ),
					esc_attr( (string) $value ),
					esc_attr( $sub['placeholder'] ),
					$sub['max_length'] ? ' maxlength="' . (int) $sub['max_length'] . '"' : '',
					$is_title ? ' data-syn-repeater-title-source' : ''
				);
				break;
		}

		if ( '' !== $sub['description'] ) {
			echo '<p class="description">' . esc_html( $sub['description'] ) . '</p>';
		}

		if ( 'html' === $sub['type'] ) {
			echo '<p class="description">' . esc_html__( 'Basic HTML is allowed here — a link, bold, a list. Anything else is stripped when you save.', 'synergi' ) . '</p>';
		}

		echo '</div>';
	}

	echo '</div></fieldset>';
}

/**
 * Reads one raw postmeta value.
 *
 * Raw: no default applied, no shape validation. The admin UI wants what is
 * actually stored, so that an editor sees their own words rather than a default
 * they never typed. The front-end readers in §9 are the ones that apply defaults.
 *
 * @param string $meta_key Full meta key, prefix included.
 * @param int    $post_id  Post ID.
 * @return string Stored value, or "".
 */
function syn_stored_value( $meta_key, $post_id ) {
	$value = get_post_meta( $post_id, $meta_key, true );

	if ( is_string( $value ) ) {
		return $value;
	}

	// An array or an object here means something other than this engine wrote
	// the key. Casting one to a string is a PHP warning, so it is treated as
	// absent instead and the default takes over (CLAUDE.md §13, fail gracefully).
	return is_scalar( $value ) ? (string) $value : '';
}

/* ==========================================================================
   6. Saving
   ========================================================================== */

add_action( 'save_post', 'syn_save_fields', 10, 2 );
/**
 * Saves every field group whose nonce arrived with the request.
 *
 * Groups are matched by the presence of their nonce, NOT by re-running the
 * template check. Two reasons, and both are bugs if you get them wrong:
 *
 *  - The block editor saves post content over the REST API and the meta boxes
 *    through a separate POST. save_post fires for both. The REST save carries no
 *    nonce of ours, so a handler that assumed "no value posted means the editor
 *    cleared it" would wipe every field on the first content save. The nonce
 *    check is what makes that impossible.
 *  - A save that changes the page template changes which groups the template
 *    check would match, so re-running it here could process a group whose box
 *    was never on screen. What was on screen is exactly what sent a nonce.
 *
 * Side effects: writes and deletes postmeta; stores a rejection notice in a
 * transient when input had to be changed or discarded.
 *
 * @param int     $post_id Post being saved.
 * @param WP_Post $post    Post object.
 * @return void
 */
function syn_save_fields( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$rejections = array();
	$touched    = false;

	foreach ( syn_field_groups() as $group_id => $group ) {
		$nonce_name = syn_field_nonce_name( $group_id );

		if ( ! isset( $_POST[ $nonce_name ] ) ) {
			continue;
		}

		if ( ! in_array( $post->post_type, $group['post_types'], true ) ) {
			continue;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ $nonce_name ] ) );

		if ( ! wp_verify_nonce( $nonce, syn_field_nonce_action( $group_id ) ) ) {
			$rejections[] = sprintf(
				/* translators: %s: field group name. */
				__( '“%s” was not saved: the page had been open long enough for its security token to expire. Reload the page and try again.', 'synergi' ),
				$group['title']
			);
			continue;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$touched = true;

		foreach ( $group['fields'] as $field ) {
			syn_save_field( $field, $post_id, $rejections );
		}
	}

	if ( ! $touched && ! $rejections ) {
		return;
	}

	/*
	 * PHP drops input variables past max_input_vars silently — the request looks
	 * complete and the tail of a long repeater is simply gone. There is no way to
	 * detect the truncation exactly, so this warns when the request came close
	 * enough to the ceiling to be worth checking by hand.
	 */
	$limit = (int) ini_get( 'max_input_vars' );

	if ( $limit > 0 ) {
		$received = count( $_POST, COUNT_RECURSIVE ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- counting only; every value read above is nonce-checked.

		if ( $received >= (int) ( $limit * 0.9 ) ) {
			$rejections[] = sprintf(
				/* translators: 1: number of form values sent. 2: the server's max_input_vars setting. */
				__( 'This page sent %1$d form values against a server limit of %2$d. Long lists can be cut off without warning at that limit — check that the last few items saved, and raise max_input_vars if they did not.', 'synergi' ),
				$received,
				$limit
			);
		}
	}

	if ( $rejections ) {
		syn_store_field_notice( $post_id, $rejections );
	}
}

/**
 * Saves one field.
 *
 * An empty value deletes the meta key rather than storing "". That is what makes
 * CLAUDE.md §7c's "every field has a default" true in practice: cleared means
 * absent, absent means the default renders, and the database never fills up with
 * empty strings that look like content.
 *
 * Side effects: writes or deletes postmeta; appends to $rejections.
 *
 * @param array $field      Normalised field.
 * @param int   $post_id    Post ID.
 * @param array $rejections Collected messages, by reference.
 * @return void
 */
function syn_save_field( $field, $post_id, &$rejections ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- the caller verified this group's nonce before reaching here.
	$key = SYN_META_PREFIX . $field['key'];

	switch ( $field['type'] ) {
		case 'link':
			$url_key   = $key . '_url';
			$label_key = $key . '_label';

			$url = isset( $_POST[ $url_key ] )
				? syn_sanitize_leaf( wp_unslash( $_POST[ $url_key ] ), 'url', $field['label'] . ' — ' . __( 'address', 'synergi' ), 0, $rejections )
				: '';

			$label = isset( $_POST[ $label_key ] )
				? syn_sanitize_leaf( wp_unslash( $_POST[ $label_key ] ), 'text', $field['label'] . ' — ' . __( 'button text', 'synergi' ), $field['max_length'], $rejections )
				: '';

			syn_write_meta( $post_id, $url_key, $url );
			syn_write_meta( $post_id, $label_key, $label );
			break;

		case 'repeater':
			$raw  = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : array();
			$rows = syn_sanitize_rows( $raw, $field, $rejections );

			syn_write_meta( $post_id, $key, $rows ? wp_json_encode( $rows ) : '' );
			break;

		case 'image':
			$value = isset( $_POST[ $key ] )
				? syn_sanitize_leaf( wp_unslash( $_POST[ $key ] ), 'image', $field['label'], 0, $rejections )
				: '';

			syn_write_meta( $post_id, $key, $value ? (string) $value : '' );
			break;

		case 'textarea':
			$value = isset( $_POST[ $key ] )
				? syn_sanitize_leaf( wp_unslash( $_POST[ $key ] ), 'textarea', $field['label'], $field['max_length'], $rejections )
				: '';

			syn_write_meta( $post_id, $key, $value );
			break;

		case 'text':
		default:
			$value = isset( $_POST[ $key ] )
				? syn_sanitize_leaf( wp_unslash( $_POST[ $key ] ), 'text', $field['label'], $field['max_length'], $rejections )
				: '';

			syn_write_meta( $post_id, $key, $value );
			break;
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing
}

/**
 * Writes one meta value, deleting the key when the value is empty.
 *
 * Side effects: writes or deletes postmeta.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Full meta key, prefix included.
 * @param string $value    Sanitised value.
 * @return void
 */
function syn_write_meta( $post_id, $meta_key, $value ) {
	if ( '' === $value || null === $value ) {
		delete_post_meta( $post_id, $meta_key );

		return;
	}

	update_post_meta( $post_id, $meta_key, $value );
}

/* ==========================================================================
   7. Sanitising

   One function per shape: a leaf, and a set of repeater rows made of leaves.
   Everything the engine stores passes through here, and anything that had to be
   changed or discarded names itself in $rejections so §8 can tell the editor.
   ========================================================================== */

/**
 * Sanitises one value by its declared leaf type.
 *
 * @param mixed  $raw        Unslashed submitted value.
 * @param string $type       One of syn_leaf_types().
 * @param string $label      Human name of the field, for the rejection message.
 * @param int    $max_length 0 for no limit.
 * @param array  $rejections Collected messages, by reference.
 * @param array  $choices    "select" only. Allowed values as value => label.
 * @return string|int Sanitised value. "" or 0 when there is nothing to store.
 */
function syn_sanitize_leaf( $raw, $type, $label, $max_length, &$rejections, $choices = array() ) {
	if ( is_array( $raw ) ) {
		$rejections[] = sprintf(
			/* translators: %s: field name. */
			__( '“%s” was sent in an unexpected shape and was not saved.', 'synergi' ),
			$label
		);

		return 'image' === $type || 'int' === $type ? 0 : '';
	}

	switch ( $type ) {
		case 'image':
			$id = absint( $raw );

			if ( ! $id ) {
				return 0;
			}

			if ( ! wp_attachment_is_image( $id ) ) {
				$rejections[] = sprintf(
					/* translators: %s: field name. */
					__( 'The image chosen for “%s” is not in the media library any more, so it was cleared.', 'synergi' ),
					$label
				);

				return 0;
			}

			return $id;

		case 'int':
			return absint( $raw );

		case 'select':
			$choice = (string) $raw;

			if ( '' === $choice ) {
				return '';
			}

			/*
			 * Whitelist, not sanitiser. The service icons are files on disk and
			 * inc/sections.php will only print one whose slug is on its own
			 * allowed list — so anything not offered here would render nothing
			 * at all, silently. Refusing it on save is where that becomes
			 * visible (CLAUDE.md §13).
			 */
			if ( ! array_key_exists( $choice, $choices ) ) {
				$rejections[] = sprintf(
					/* translators: 1: field name. 2: the value that was rejected. */
					__( '“%1$s” was set to something that is not one of the available options, so it was cleared. It read: %2$s', 'synergi' ),
					$label,
					$choice
				);

				return '';
			}

			return $choice;

		case 'date':
			$date = trim( (string) $raw );

			if ( '' === $date ) {
				return '';
			}

			/*
			 * Stored as Y-m-d, never as typed. "20 August 2026" and "08/20/2026"
			 * are the same day and two different strings, and a record read by
			 * three pages has to be one string. The template formats it for
			 * display with date_i18n(), which is also what makes the Arabic
			 * phase free (CLAUDE.md §12).
			 */
			$parsed = DateTime::createFromFormat( 'Y-m-d', $date );

			if ( ! $parsed || $parsed->format( 'Y-m-d' ) !== $date ) {
				$rejections[] = sprintf(
					/* translators: 1: field name. 2: the value that was rejected. */
					__( 'The date in “%1$s” was not a real date, so it was cleared. Use the date picker, or type it as 2026-08-20. It read: %2$s', 'synergi' ),
					$label,
					$date
				);

				return '';
			}

			return $date;

		case 'email':
			$typed = trim( (string) $raw );

			if ( '' === $typed ) {
				return '';
			}

			$email = sanitize_email( $typed );

			if ( '' === $email || ! is_email( $email ) ) {
				$rejections[] = sprintf(
					/* translators: 1: field name. 2: the value that was rejected. */
					__( 'The address in “%1$s” was not a usable email address, so it was cleared. It read: %2$s', 'synergi' ),
					$label,
					$typed
				);

				return '';
			}

			return $email;

		case 'url':
			$trimmed = trim( (string) $raw );

			if ( '' === $trimmed ) {
				return '';
			}

			$url = esc_url_raw( $trimmed );

			if ( '' === $url ) {
				$rejections[] = sprintf(
					/* translators: 1: field name. 2: the address that was rejected. */
					__( 'The address in “%1$s” was not a usable web address, so it was cleared. It read: %2$s', 'synergi' ),
					$label,
					$trimmed
				);

				return '';
			}

			return $url;

		case 'html':
			/*
			 * The one leaf that keeps markup. wp_kses_post() is the same filter
			 * core applies to post content, so an FAQ answer may carry a link, a
			 * list or emphasis and nothing else (CLAUDE.md §7b). A script tag or
			 * a style attribute is silently removed by kses, so the comparison
			 * below is what turns "silently" into a sentence on screen.
			 */
			$clean = wp_kses_post( (string) $raw );

			if ( $clean !== (string) $raw ) {
				$rejections[] = sprintf(
					/* translators: %s: field name. */
					__( 'Some markup in “%s” was not allowed and was removed. Links, lists, bold and italics are kept; scripts, styles and inline formatting are not.', 'synergi' ),
					$label
				);
			}

			$value = $clean;
			break;

		case 'textarea':
			$value = sanitize_textarea_field( (string) $raw );
			break;

		case 'text':
		default:
			$value = sanitize_text_field( (string) $raw );
			break;
	}

	if ( $max_length > 0 && syn_string_length( $value ) > $max_length ) {
		$value = syn_string_cut( $value, $max_length );

		$rejections[] = sprintf(
			/* translators: 1: field name. 2: maximum number of characters. */
			__( '“%1$s” was longer than %2$d characters and was shortened to fit.', 'synergi' ),
			$label,
			$max_length
		);
	}

	return $value;
}

/**
 * Sanitises a repeater's submitted rows.
 *
 * Row order is the order the values arrived in, which is the order they sat in
 * on screen — PHP keeps numeric array keys in insertion order, so moving a row
 * in the DOM is all the reordering the browser has to do. Rows where every leaf
 * is empty are dropped: a blank row is what an editor leaves behind after
 * clearing one, not something they meant to publish.
 *
 * @param mixed $raw        Unslashed submitted array of rows.
 * @param array $field      Normalised repeater field.
 * @param array $rejections Collected messages, by reference.
 * @return array[] Clean rows, re-indexed from zero.
 */
function syn_sanitize_rows( $raw, $field, &$rejections ) {
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$rows    = array();
	$dropped = 0;

	foreach ( $raw as $submitted ) {
		if ( ! is_array( $submitted ) ) {
			continue;
		}

		if ( $field['max_rows'] > 0 && count( $rows ) >= $field['max_rows'] ) {
			$dropped++;
			continue;
		}

		$row      = array();
		$has_data = false;

		foreach ( $field['subfields'] as $sub ) {
			$value = isset( $submitted[ $sub['key'] ] ) ? $submitted[ $sub['key'] ] : '';

			$value = syn_sanitize_leaf(
				$value,
				$sub['type'],
				$field['label'] . ' — ' . $sub['label'],
				$sub['max_length'],
				$rejections,
				$sub['choices']
			);

			$row[ $sub['key'] ] = $value;

			if ( '' !== $value && 0 !== $value ) {
				$has_data = true;
			}
		}

		if ( $has_data ) {
			$rows[] = $row;
		}
	}

	if ( $dropped ) {
		$rejections[] = sprintf(
			/* translators: 1: field name. 2: maximum rows allowed. 3: number of rows discarded. */
			__( '“%1$s” holds at most %2$d items, so %3$d extra were not saved.', 'synergi' ),
			$field['label'],
			$field['max_rows'],
			$dropped
		);
	}

	return $rows;
}

/**
 * Character length, counting an emoji as one character where possible.
 *
 * @param string $value String to measure.
 * @return int
 */
function syn_string_length( $value ) {
	return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
}

/**
 * Cuts a string to a character length without splitting a multi-byte character.
 *
 * strlen()-based truncation on a UTF-8 string can end mid-character and produce
 * bytes no browser can render — which is exactly what an editor pasting an emoji
 * into a length-limited field would hit.
 *
 * @param string $value  String to cut.
 * @param int    $length Maximum characters.
 * @return string
 */
function syn_string_cut( $value, $length ) {
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $value, 0, $length, 'UTF-8' );
	}

	return (string) preg_replace( '/(?:.{' . (int) $length . '})\K.*/su', '', $value );
}

/**
 * Decodes a stored repeater value into rows, validating the shape.
 *
 * Never trusts what is in the database (CLAUDE.md §5): the JSON may predate a
 * change to the subfields, may have been edited by hand, or may not be JSON at
 * all. Unknown keys are dropped, missing keys become "", and anything that is
 * not a list of arrays returns no rows rather than a warning-generating mess.
 *
 * @param string $stored Raw meta value.
 * @param array  $field  Normalised repeater field.
 * @return array[] Rows keyed by subfield, values raw and unescaped.
 */
function syn_decode_rows( $stored, $field ) {
	if ( ! is_string( $stored ) || '' === $stored ) {
		return array();
	}

	$decoded = json_decode( $stored, true );

	if ( ! is_array( $decoded ) ) {
		syn_field_log( sprintf( 'repeater "%s" holds a value that is not valid JSON; it was ignored', $field['key'] ) );

		return array();
	}

	return syn_shape_rows( $decoded, $field );
}

/**
 * Forces an array of stored rows into the exact shape a template can rely on.
 *
 * Every row comes back with exactly the declared subfields, as strings, in the
 * declared order. Unknown keys are dropped, missing keys become "", and anything
 * that is not a list of arrays contributes nothing. A template can therefore
 * index a row without isset() checks — though it still has to escape every leaf
 * when it prints it (CLAUDE.md §5).
 *
 * Shared with inc/records.php, which needs the same guarantee over data that
 * came out of an option rather than out of postmeta. Same promise, one
 * implementation.
 *
 * @param mixed $rows  Decoded rows from wherever they were stored.
 * @param array $field Normalised repeater field.
 * @return array[] Rows keyed by subfield, values raw and unescaped.
 */
function syn_shape_rows( $rows, $field ) {
	if ( ! is_array( $rows ) ) {
		return array();
	}

	$shaped = array();

	foreach ( $rows as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$row = array();

		foreach ( $field['subfields'] as $sub ) {
			$value = isset( $entry[ $sub['key'] ] ) ? $entry[ $sub['key'] ] : '';

			$row[ $sub['key'] ] = is_scalar( $value ) ? (string) $value : '';
		}

		$shaped[] = $row;
	}

	return $shaped;
}

/* ==========================================================================
   8. Rejection notices

   Nothing an editor types disappears without a sentence saying why
   (CLAUDE.md §13). The messages are stashed in a short-lived transient, keyed
   by user and post, and printed by whichever runs first: admin_notices on a
   classic full page load, or the meta box itself in the block editor, where the
   meta-box area re-renders on its own and admin_notices never fires. Whichever
   prints it also deletes it, so it can never appear twice.
   ========================================================================== */

/**
 * The transient key holding one editor's pending messages for one post.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function syn_field_notice_key( $post_id ) {
	return 'syn_field_notice_' . get_current_user_id() . '_' . (int) $post_id;
}

/**
 * Stores messages to show after the save completes.
 *
 * Side effects: writes a transient with a five-minute life.
 *
 * @param int      $post_id  Post ID.
 * @param string[] $messages Messages to show.
 * @return void
 */
function syn_store_field_notice( $post_id, $messages ) {
	set_transient( syn_field_notice_key( $post_id ), array_values( array_unique( $messages ) ), 5 * MINUTE_IN_SECONDS );
}

/**
 * Prints and clears the pending messages for one post, if there are any.
 *
 * Side effects: echoes a notice; deletes the transient.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function syn_print_field_notice( $post_id ) {
	$messages = get_transient( syn_field_notice_key( $post_id ) );

	if ( ! is_array( $messages ) || ! $messages ) {
		return;
	}

	delete_transient( syn_field_notice_key( $post_id ) );

	echo '<div class="notice notice-warning syn-field-notice"><p><strong>';
	echo esc_html__( 'Some of what you entered had to be changed:', 'synergi' );
	echo '</strong></p><ul>';

	foreach ( $messages as $message ) {
		echo '<li>' . esc_html( $message ) . '</li>';
	}

	echo '</ul></div>';
}

add_action( 'admin_notices', 'syn_field_admin_notice' );
/**
 * Prints pending field messages at the top of a classic post edit screen.
 *
 * Side effects: echoes a notice; deletes the transient.
 *
 * @return void
 */
function syn_field_admin_notice() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'post' !== $screen->base ) {
		return;
	}

	/*
	 * The block editor is left to the meta box itself. Gutenberg renders
	 * admin_notices into a region it mostly hides, so printing here would
	 * consume the message without anyone reading it — and the meta-box area is
	 * re-rendered after every save anyway, which is where the editor is looking.
	 */
	if ( method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
		return;
	}

	$post = get_post();

	if ( ! $post ) {
		return;
	}

	syn_print_field_notice( (int) $post->ID );
}

/* ==========================================================================
   9. The read API — what templates and sections call

   Every function here returns a VALUE, never escaped markup. The partial that
   prints it escapes it at the point of printing, because the escaping a value
   needs depends on where it lands (CLAUDE.md §5). Nothing in the theme calls
   these yet; Stage 6c is the first consumer.
   ========================================================================== */

/**
 * Finds a registered field by key, across every group.
 *
 * @param string $key Field key, without the _syn_ prefix.
 * @return array|null Normalised field.
 */
function syn_field_definition( $key ) {
	$key = sanitize_key( $key );

	foreach ( syn_field_groups() as $group ) {
		if ( isset( $group['fields'][ $key ] ) ) {
			return $group['fields'][ $key ];
		}
	}

	return null;
}

/**
 * A text or textarea field's value, falling back to its registered default.
 *
 * @param string   $key     Field key, without the _syn_ prefix.
 * @param int|null $post_id Post ID. Defaults to the current post.
 * @param string   $default Optional override for the registered default.
 * @return string Raw value — escape it where you print it.
 */
function syn_field( $key, $post_id = null, $default = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$field   = syn_field_definition( $key );
	$stored  = $post_id ? syn_stored_value( SYN_META_PREFIX . sanitize_key( $key ), $post_id ) : '';

	if ( '' !== trim( $stored ) ) {
		return $stored;
	}

	if ( null !== $default ) {
		return (string) $default;
	}

	return $field && is_string( $field['default'] ) ? $field['default'] : '';
}

/**
 * A repeater's rows, falling back to its registered default rows.
 *
 * Shape-validated on the way out: every row has exactly the declared subfields,
 * as strings. A template can therefore index a row without isset() checks — but
 * still has to escape every leaf when it prints it.
 *
 * @param string   $key     Repeater key, without the _syn_ prefix.
 * @param int|null $post_id Post ID. Defaults to the current post.
 * @return array[] Rows, each keyed by subfield.
 */
function syn_field_rows( $key, $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$field   = syn_field_definition( $key );

	if ( ! $field || 'repeater' !== $field['type'] ) {
		return array();
	}

	$rows = $post_id ? syn_decode_rows( syn_stored_value( SYN_META_PREFIX . $field['key'], $post_id ), $field ) : array();

	if ( $rows ) {
		return $rows;
	}

	$defaults = array();

	foreach ( (array) $field['default'] as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$row = array();

		foreach ( $field['subfields'] as $sub ) {
			$value = isset( $entry[ $sub['key'] ] ) ? $entry[ $sub['key'] ] : '';

			$row[ $sub['key'] ] = is_scalar( $value ) ? (string) $value : '';
		}

		$defaults[] = $row;
	}

	return $defaults;
}

/**
 * A link field's address and words.
 *
 * @param string   $key     Field key, without the _syn_ prefix.
 * @param int|null $post_id Post ID. Defaults to the current post.
 * @return array{url:string,label:string} Raw values — escape with esc_url() and
 *                                        esc_html() where you print them.
 */
function syn_field_link( $key, $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$field   = syn_field_definition( $key );
	$key     = sanitize_key( $key );

	$url   = $post_id ? syn_stored_value( SYN_META_PREFIX . $key . '_url', $post_id ) : '';
	$label = $post_id ? syn_stored_value( SYN_META_PREFIX . $key . '_label', $post_id ) : '';

	$defaults = $field && 'link' === $field['type'] ? $field['default'] : array(
		'url'   => '',
		'label' => '',
	);

	return array(
		'url'   => '' !== $url ? $url : $defaults['url'],
		'label' => '' !== $label ? $label : $defaults['label'],
	);
}

/**
 * An image field's attachment ID, with the Stage 5 slug lookup as the fallback.
 *
 * The fallback is the whole point of CLAUDE.md §7b: six sections currently find
 * their photographs with syn_attachment_id_by_slug(), and every one of those
 * call sites becomes a field. Keeping the slug lookup underneath means the day
 * the fields ship, nothing goes blank — the field is simply empty and the old
 * lookup still answers.
 *
 * @param string   $key     Field key, without the _syn_ prefix.
 * @param int|null $post_id Post ID. Defaults to the current post.
 * @return int Attachment ID, or 0.
 */
function syn_field_image_id( $key, $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$field   = syn_field_definition( $key );
	$stored  = $post_id ? (int) syn_stored_value( SYN_META_PREFIX . sanitize_key( $key ), $post_id ) : 0;

	if ( $stored && wp_attachment_is_image( $stored ) ) {
		return $stored;
	}

	if ( $field && '' !== $field['fallback_slug'] && function_exists( 'syn_attachment_id_by_slug' ) ) {
		return syn_attachment_id_by_slug( $field['fallback_slug'] );
	}

	return 0;
}

/**
 * Whether an editor has actually stored something in a field.
 *
 * Useful where a section should be skipped entirely rather than rendered with
 * its default — the defaults answer "what does an empty field look like", not
 * "should this exist at all".
 *
 * @param string   $key     Field key, without the _syn_ prefix.
 * @param int|null $post_id Post ID. Defaults to the current post.
 * @return bool
 */
function syn_field_has( $key, $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();

	if ( ! $post_id ) {
		return false;
	}

	$field = syn_field_definition( $key );
	$keys  = $field ? syn_field_meta_keys( $field ) : array( SYN_META_PREFIX . sanitize_key( $key ) );

	foreach ( $keys as $meta_key ) {
		if ( '' !== trim( syn_stored_value( $meta_key, $post_id ) ) ) {
			return true;
		}
	}

	return false;
}


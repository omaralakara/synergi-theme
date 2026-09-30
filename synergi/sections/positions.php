<?php
/**
 * Careers section — the open positions.
 *
 * Rendered through syn_section( 'positions', $args ). Styled by
 * assets/css/sections/positions.css. THERE IS NO positions.js AND THERE SHOULD
 * NOT BE: each role is a native <details>/<summary>, for the reasons
 * sections/faq.php gives — reachable with scripting off, keyboard-operable
 * with no code, and its open state exposed by the browser (CLAUDE.md §9, §10).
 *
 * Expected $args:
 *   eyebrow       string  Small label above the heading.
 *   heading       string  The section's <h2>.
 *   lede          string  Optional. One line under the heading.
 *   items         array[] One entry per role, in order, each:
 *                           title       string Required. A row without one is skipped.
 *                           department  string A key from syn_careers_department_choices().
 *                           location    string City and country, or "Remote".
 *                           type        string A key from syn_careers_type_choices().
 *                           status      string A key from syn_careers_status_choices();
 *                                              blank reads as "available".
 *                           summary     string One line, shown before the role opens.
 *                           description string The role. Editor HTML, sanitised on
 *                                              save with wp_kses_post() and again here.
 *                           posted      string Optional. YYYY-MM-DD.
 *                           closing     string Optional. YYYY-MM-DD, the last day
 *                                              to apply. The repeater's valid_through
 *                                              column is read under this name too.
 *                           apply_email string The address this role's CVs go to.
 *                           apply_url   string Optional. A form or job board page;
 *                                              used instead of the email when set.
 *                           permalink   string Optional. The role's own page. A
 *                                              portal role has one; a repeater
 *                                              row never does.
 *                           hiring_for  string Optional. Who the role is for when
 *                                              it is not Synergi ("a client in …").
 *                           mode        string Optional. A work-mode chip: Hybrid,
 *                                              Remote. On-site prints none.
 *                           salary      string Optional. A salary chip, worded.
 *                           lang        string Optional. "en" when the role's copy
 *                                              is not in the page's language.
 *   apply_url     string  Where Apply goes when a role has neither an email nor
 *                         a link. Defaults to the contact page.
 *   empty_heading string  Shown when there are no roles.
 *   empty_text    string  Shown under it.
 *   empty_cta     array   url and label for the empty state's button.
 *   single        bool    Optional. The role page: one item, drawn as a card
 *                         that is already open, with no head and no <details>.
 *                         The page's <h1> is parts/page-header.php's, so the
 *                         card does not repeat the title.
 *   back          array   Optional, single mode. url and label for the button
 *                         under the card that returns to the careers page.
 *
 * EVERY ROLE HAS ITS OWN ADDRESS (14 Sep). There is no page-wide inbox: the
 * business wants a role's applications to land with the person hiring for it,
 * so the email is a column of the row. It is printed as a line — "Mail us at
 * name@…" — rather than a button (also 14 Sep): the address itself is the
 * information a candidate wants to see and copy, and a button hides it. The
 * link is a mailto: with the role in the subject line. A link, when a role has
 * one, is printed as "Apply at" instead; a role with neither falls back to a
 * "Get in touch" line to Contact Us so no role is a dead end.
 *
 * Example:
 *   syn_section( 'positions', array( 'items' => syn_field_rows( 'careers_positions_list' ) ) );
 *
 * TWO SOURCES, ONE MARKUP (30 Sep). templates/careers.php hands this band
 * either the page's repeater rows or the portal's feed through
 * inc/careers-feed.php, in the same shape; the band cannot tell and must not
 * try. The permalink, the hiring-for line, the closing date, the mode and
 * salary chips and the "new" tag arrived with the feed, and the single mode
 * is what synergi-careers/single-vacancy.php draws a role's own page with.
 *
 * THE BAND NEVER SKIPS ITSELF. Every other list section returns when it has
 * nothing, but a careers page with no vacancies still has to answer "so what
 * do I do?" — so an empty list renders the open-application panel instead of
 * nothing (CLAUDE.md §7c, a page with an empty field renders the default).
 *
 * THE ACCENT IS THE DEPARTMENT'S. A role under a service line takes that line's
 * gradient as the stripe on its row, keyed by the same slug the homepage deck
 * and the listing pages use, so a reader who has seen the site knows the
 * colour before reading the label. Operations and Corporate take the brand
 * gradient. Written out one selector per accent in positions.css.
 *
 * THIS BAND EMITS NO STRUCTURED DATA, AND MUST NOT (16 Sep). It used to emit one
 * JobPosting per dated role. That is a documented Google policy violation, not
 * merely ineffective: "The JobPosting markup must only be used on pages that
 * contain a single job posting. We don't allow the use of JobPosting markup in
 * any other page" — and the troubleshooting page names the Search Console
 * message it earns, "A list page should not include structured data for
 * individual jobs", whose remedy is a reconsideration request. This page lists
 * three roles, so it was a list page from the day it shipped.
 *
 * Google Jobs needs one URL per role. Since 30 Sep the Synergi Careers plugin
 * gives every portal role one (/careers/<title>-<id>/) and emits the
 * JobPosting there, on the page whose only content is that job. The single
 * mode below draws that page; the markup stays the plugin's. Do not re-add a
 * JobPosting block to this file: see docs/job-posting-schema.md.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow       = trim( (string) ( $args['eyebrow'] ?? '' ) );
$syn_heading       = trim( (string) ( $args['heading'] ?? __( 'Open positions', 'synergi' ) ) );
$syn_lede          = trim( (string) ( $args['lede'] ?? '' ) );
$syn_apply_url     = trim( (string) ( $args['apply_url'] ?? '' ) );
$syn_empty_heading = trim( (string) ( $args['empty_heading'] ?? '' ) );
$syn_empty_text    = trim( (string) ( $args['empty_text'] ?? '' ) );
$syn_empty_cta     = (array) ( $args['empty_cta'] ?? array() );
$syn_single        = ! empty( $args['single'] );
$syn_back          = (array) ( $args['back'] ?? array() );

if ( '' === $syn_apply_url && function_exists( 'syn_contact_url' ) ) {
	$syn_apply_url = syn_contact_url();
}

$syn_empty_cta_url   = trim( (string) ( $syn_empty_cta['url'] ?? '' ) );
$syn_empty_cta_label = trim( (string) ( $syn_empty_cta['label'] ?? '' ) );

if ( '' === $syn_empty_cta_url ) {
	$syn_empty_cta_url = $syn_apply_url;
}

if ( '' === $syn_empty_cta_label ) {
	$syn_empty_cta_label = __( 'Get in touch', 'synergi' );
}

$syn_back_url   = trim( (string) ( $syn_back['url'] ?? '' ) );
$syn_back_label = trim( (string) ( $syn_back['label'] ?? '' ) );

if ( '' === $syn_back_label ) {
	$syn_back_label = __( 'See all open positions', 'synergi' );
}

$syn_departments = function_exists( 'syn_careers_department_choices' ) ? syn_careers_department_choices() : array();
$syn_types       = function_exists( 'syn_careers_type_choices' ) ? syn_careers_type_choices() : array();
$syn_statuses    = function_exists( 'syn_careers_status_choices' ) ? syn_careers_status_choices() : array();

/*
 * The six accents theme.json defines, by the slug the services record uses.
 * Any other department — Operations, Corporate, or a service line the theme
 * has no accent for — falls back to the brand gradient in positions.css.
 */
$syn_accented = array( 'accounting', 'human-resources', 'marketing', 'procurement', 'project-management', 'technology-ai' );

$syn_clean = array();

foreach ( (array) ( $args['items'] ?? array() ) as $syn_row ) {
	if ( ! is_array( $syn_row ) ) {
		continue;
	}

	$syn_title = trim( (string) ( $syn_row['title'] ?? '' ) );

	if ( '' === $syn_title ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-section positions: a role with no title was skipped -->\n";
		}

		continue;
	}

	$syn_department = sanitize_key( $syn_row['department'] ?? '' );
	$syn_type       = sanitize_key( $syn_row['type'] ?? '' );
	$syn_posted     = trim( (string) ( $syn_row['posted'] ?? '' ) );
	$syn_closing    = trim( (string) ( $syn_row['closing'] ?? ( $syn_row['valid_through'] ?? '' ) ) );
	$syn_role_url   = trim( (string) ( $syn_row['apply_url'] ?? '' ) );
	$syn_role_email = trim( (string) ( $syn_row['apply_email'] ?? '' ) );

	/*
	 * Link, then email, then the page's fallback. The email is checked with
	 * is_email() rather than trusted, so a mistyped address degrades to the
	 * contact page instead of a mailto: nobody can send (CLAUDE.md §13).
	 */
	if ( '' !== $syn_role_url ) {
		$syn_role_apply = $syn_role_url;
		$syn_role_kind  = 'link';
	} elseif ( is_email( $syn_role_email ) ) {
		$syn_role_apply = 'mailto:' . $syn_role_email;
		$syn_role_kind  = 'mail';
	} else {
		$syn_role_apply = $syn_apply_url;
		$syn_role_kind  = 'contact';
		$syn_role_email = '';
	}

	// Blank reads as the first choice, so a row from before the column
	// existed is tagged "Still available" rather than left untagged.
	$syn_status = sanitize_key( $syn_row['status'] ?? '' );

	if ( ! isset( $syn_statuses[ $syn_status ] ) ) {
		$syn_status = 'available';
	}

	$syn_clean[] = array(
		'title'           => $syn_title,
		'department'      => $syn_department,
		'department_name' => $syn_departments[ $syn_department ] ?? '',
		'accent'          => in_array( $syn_department, $syn_accented, true ) ? $syn_department : '',
		'location'        => trim( (string) ( $syn_row['location'] ?? '' ) ),
		'type'            => $syn_type,
		'type_name'       => $syn_types[ $syn_type ] ?? '',
		'summary'         => trim( (string) ( $syn_row['summary'] ?? '' ) ),
		'description'     => trim( (string) ( $syn_row['description'] ?? '' ) ),
		// A date the picker did not produce is not a date. Dropped rather
		// than printed, so nothing downstream ever carries a string that is
		// not a date.
		'posted'          => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $syn_posted ) ? $syn_posted : '',
		'closing'         => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $syn_closing ) ? $syn_closing : '',
		'apply_url'       => $syn_role_apply,
		'apply_kind'      => $syn_role_kind,
		'apply_email'     => $syn_role_email,
		'status'          => $syn_status,
		'status_name'     => $syn_statuses[ $syn_status ] ?? __( 'Still available', 'synergi' ),
		'permalink'       => trim( (string) ( $syn_row['permalink'] ?? '' ) ),
		'hiring_for'      => trim( (string) ( $syn_row['hiring_for'] ?? '' ) ),
		'mode'            => trim( (string) ( $syn_row['mode'] ?? '' ) ),
		'salary'          => trim( (string) ( $syn_row['salary'] ?? '' ) ),
		// A language code the theme itself chose (inc/careers-feed.php),
		// reduced to a key so nothing else can ever land in the attribute.
		'lang'            => sanitize_key( $syn_row['lang'] ?? '' ),
	);
}

$syn_count = count( $syn_clean );
$syn_uid   = wp_unique_id( 'syn-positions-' );
$syn_group = $syn_uid . '-group';

/*
 * The single mode draws exactly one role. A second item would be a caller's
 * mistake, so it is reported under SYN_DEBUG and only the first is drawn —
 * the page must never show two roles under one <h1>.
 */
if ( $syn_single && $syn_count > 1 ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section positions: single mode was given " . (int) $syn_count . " roles; drawing the first -->\n";
	}

	$syn_clean = array_slice( $syn_clean, 0, 1 );
	$syn_count = 1;
}

/**
 * The chip row under a role's title, in both modes.
 *
 * A closure at the partial's scope, never a named function: this partial can
 * render twice on one request and a named function would be a fatal
 * "cannot redeclare" the second time (see syn_youtube_id() in inc/sections.php).
 *
 * @param array $item One normalised row.
 * @return string Escaped markup.
 */
$syn_chips = static function ( $item ) {
	$out = '';

	if ( '' !== $item['department_name'] ) {
		$out .= '<span class="syn-positions__chip syn-positions__chip--department">' . esc_html( $item['department_name'] ) . '</span>';
	}

	if ( '' !== $item['location'] ) {
		$out .= '<span class="syn-positions__chip"' . ( '' !== $item['lang'] ? ' lang="' . esc_attr( $item['lang'] ) . '"' : '' ) . '>'
			. '<svg class="syn-positions__chip-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
			. '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z" /><circle cx="12" cy="9.5" r="2.5" /></svg>'
			. esc_html( $item['location'] ) . '</span>';
	}

	foreach ( array( $item['type_name'], $item['mode'], $item['salary'] ) as $chip ) {
		if ( '' !== $chip ) {
			$out .= '<span class="syn-positions__chip">' . esc_html( $chip ) . '</span>';
		}
	}

	return $out;
};
?>
<?php if ( $syn_single ) : ?>
<section class="syn-positions syn-positions--single syn-section">
<?php else : ?>
<section class="syn-positions syn-section" id="positions" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
<?php endif; ?>
	<div class="syn-container syn-container--narrow">

		<?php if ( ! $syn_single ) : ?>
		<div class="syn-positions__head syn-reveal">
			<?php if ( '' !== $syn_eyebrow ) : ?>
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<?php endif; ?>

			<h2 class="syn-positions__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>

			<?php if ( '' !== $syn_lede ) : ?>
				<p class="syn-positions__lede"><?php echo esc_html( $syn_lede ); ?></p>
			<?php endif; ?>

			<?php if ( $syn_count ) : ?>
				<p class="syn-positions__count">
					<?php
					printf(
						/* translators: %s: number of open roles. */
						esc_html( _n( '%s open role', '%s open roles', $syn_count, 'synergi' ) ),
						'<strong>' . esc_html( number_format_i18n( $syn_count ) ) . '</strong>'
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! $syn_count ) : ?>

			<div class="syn-positions__empty syn-reveal">
				<h3 class="syn-positions__empty-title"><?php echo esc_html( '' !== $syn_empty_heading ? $syn_empty_heading : __( 'No open roles right now', 'synergi' ) ); ?></h3>

				<?php if ( '' !== $syn_empty_text ) : ?>
					<p class="syn-positions__empty-text"><?php echo esc_html( $syn_empty_text ); ?></p>
				<?php endif; ?>

				<a class="syn-button syn-button--primary" href="<?php echo esc_url( syn_local_url( $syn_empty_cta_url ) ); ?>">
					<?php echo esc_html( $syn_empty_cta_label ); ?>
					<span aria-hidden="true"><?php echo syn_arrow(); // A fixed entity chosen by the theme, → or ←; nothing from the database. ?></span>
				</a>
			</div>

		<?php else : ?>

			<?php if ( ! $syn_single ) : ?>
			<div class="syn-positions__list syn-reveal">
			<?php endif; ?>
				<?php foreach ( $syn_clean as $syn_index => $syn_item ) : ?>
					<?php
					/*
					 * The accent attribute is only written when there is one, so
					 * a role under Operations has no data-accent at all and the
					 * stylesheet's fallback applies without a selector for "none".
					 */
					$syn_accent_attr = '' !== $syn_item['accent'] ? ' data-accent="' . esc_attr( $syn_item['accent'] ) . '"' : '';

					/*
					 * The feed's copy is English on every language. lang= lets a
					 * screen reader change voice and dir= keeps a Latin title
					 * reading left to right inside an Arabic page; the theme has
					 * one non-default language and it is right-to-left.
					 */
					$syn_lang_attr = '' !== $syn_item['lang'] ? ' lang="' . esc_attr( $syn_item['lang'] ) . '" dir="ltr"' : '';

					/*
					 * A mailto: carries the role in the subject line so an inbox
					 * of applications sorts itself. A role with its own link goes
					 * there untouched.
					 */
					$syn_apply_href = $syn_item['apply_url'];

					if ( 'mail' === $syn_item['apply_kind'] ) {
						$syn_apply_href .= '?subject=' . rawurlencode( sprintf(
							/* translators: %s: the role title. */
							__( 'Application: %s', 'synergi' ),
							$syn_item['title']
						) );
					}

					/*
					 * The four tag states are written out as full class names,
					 * never assembled from the key, so each can be found verbatim
					 * in positions.css (CLAUDE.md §13, the grep rule).
					 */
					if ( 'filled' === $syn_item['status'] ) {
						$syn_tag_class = 'syn-positions__tag syn-positions__tag--filled';
					} elseif ( 'closing' === $syn_item['status'] ) {
						$syn_tag_class = 'syn-positions__tag syn-positions__tag--closing';
					} elseif ( 'new' === $syn_item['status'] ) {
						$syn_tag_class = 'syn-positions__tag syn-positions__tag--new';
					} else {
						$syn_tag_class = 'syn-positions__tag';
					}

					/*
					 * The two halves of a role. In the list the first half is the
					 * <summary> and the second the body under it; on the role page
					 * the first half is a plain lead (the title is the page's <h1>)
					 * and the card is simply open.
					 */
					?>
					<?php if ( $syn_single ) : ?>
					<article class="syn-positions__item"<?php echo $syn_accent_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from esc_attr() only. ?>>
						<div class="syn-positions__lead">
							<span class="<?php echo esc_attr( $syn_tag_class ); ?>"><?php echo esc_html( $syn_item['status_name'] ); ?></span>

							<span class="syn-positions__meta">
								<?php echo $syn_chips( $syn_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every leaf escaped inside the closure. ?>
							</span>

							<?php if ( '' !== $syn_item['hiring_for'] ) : ?>
								<p class="syn-positions__hiring-for">
									<?php
									printf(
										/* translators: %s: who the role is for, as the portal words it ("a client in …"). */
										esc_html__( 'Hiring on behalf of %s.', 'synergi' ),
										'<span' . $syn_lang_attr . '>' . esc_html( $syn_item['hiring_for'] ) . '</span>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attribute built above from esc_attr() only.
									);
									?>
								</p>
							<?php endif; ?>
						</div>
					<?php else : ?>
					<details class="syn-positions__item" name="<?php echo esc_attr( $syn_group ); ?>"<?php echo $syn_accent_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from esc_attr() only. ?>>
						<summary class="syn-positions__summary">
							<span class="syn-positions__summary-copy">
								<span class="<?php echo esc_attr( $syn_tag_class ); ?>"><?php echo esc_html( $syn_item['status_name'] ); ?></span>

								<h3 class="syn-positions__role"<?php echo $syn_lang_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from esc_attr() only. ?>><?php echo esc_html( $syn_item['title'] ); ?></h3>

								<span class="syn-positions__meta">
									<?php echo $syn_chips( $syn_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every leaf escaped inside the closure. ?>
								</span>

								<?php if ( '' !== $syn_item['summary'] ) : ?>
									<span class="syn-positions__teaser"<?php echo $syn_lang_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from esc_attr() only. ?>><?php echo esc_html( $syn_item['summary'] ); ?></span>
								<?php endif; ?>
							</span>

							<span class="syn-positions__marker" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" focusable="false">
									<path d="M12 5v14" class="syn-positions__marker-bar" />
									<path d="M5 12h14" />
								</svg>
							</span>
						</summary>
					<?php endif; ?>

						<div class="syn-positions__body">
							<?php if ( '' !== $syn_item['description'] ) : ?>
								<div class="syn-positions__description"<?php echo $syn_lang_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from esc_attr() only. ?>>
									<?php echo wp_kses_post( $syn_item['description'] ); ?>
								</div>
							<?php endif; ?>

							<?php if ( $syn_single && '' !== $syn_item['closing'] ) : ?>
								<p class="syn-positions__closing">
									<?php
									printf(
										/* translators: %s: the last day applications are open. */
										esc_html__( 'Applications close on %s.', 'synergi' ),
										'<time datetime="' . esc_attr( $syn_item['closing'] ) . '">' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $syn_item['closing'] ) ) ) . '</time>'
									);
									?>
								</p>
							<?php endif; ?>

							<div class="syn-positions__foot">
								<?php if ( '' !== $syn_item['posted'] ) : ?>
									<p class="syn-positions__posted">
										<?php
										printf(
											/* translators: %s: the date the role was posted. */
											esc_html__( 'Posted %s', 'synergi' ),
											'<time datetime="' . esc_attr( $syn_item['posted'] ) . '">' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $syn_item['posted'] ) ) ) . '</time>'
										);
										?>
									</p>
								<?php endif; ?>

								<p class="syn-positions__mail">
									<?php if ( 'mail' === $syn_item['apply_kind'] ) : ?>
										<?php esc_html_e( 'Mail us at', 'synergi' ); ?>
										<a class="syn-positions__mail-link" href="<?php echo esc_url( $syn_apply_href ); ?>"><?php echo esc_html( $syn_item['apply_email'] ); ?></a>
									<?php elseif ( 'link' === $syn_item['apply_kind'] ) : ?>
										<?php esc_html_e( 'Apply at', 'synergi' ); ?>
										<a class="syn-positions__mail-link" href="<?php echo esc_url( $syn_apply_href ); ?>"><?php echo esc_html( wp_parse_url( $syn_apply_href, PHP_URL_HOST ) ?: $syn_apply_href ); ?></a>
									<?php else : ?>
										<a class="syn-positions__mail-link" href="<?php echo esc_url( $syn_apply_href ); ?>"><?php esc_html_e( 'Get in touch about this role', 'synergi' ); ?></a>
									<?php endif; ?>
								</p>

								<?php if ( ! $syn_single && '' !== $syn_item['permalink'] ) : ?>
									<p class="syn-positions__permalink">
										<a class="syn-positions__permalink-link" href="<?php echo esc_url( $syn_item['permalink'] ); ?>">
											<?php esc_html_e( 'Open this role on its own page', 'synergi' ); ?>
											<span aria-hidden="true"><?php echo syn_arrow(); // A fixed entity chosen by the theme, → or ←; nothing from the database. ?></span>
										</a>
									</p>
								<?php endif; ?>
							</div>
						</div>
					<?php if ( $syn_single ) : ?>
					</article>
					<?php else : ?>
					</details>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php if ( ! $syn_single ) : ?>
			</div>
			<?php endif; ?>

			<?php if ( $syn_single && '' !== $syn_back_url ) : ?>
				<p class="syn-positions__back">
					<a class="syn-button syn-button--outline" href="<?php echo esc_url( $syn_back_url ); ?>">
						<span aria-hidden="true"><?php echo is_rtl() ? '&rarr;' : '&larr;'; // The arrow that points back, the reverse of syn_arrow(); a fixed entity, nothing from the database. ?></span>
						<?php echo esc_html( $syn_back_label ); ?>
					</a>
				</p>
			<?php endif; ?>

		<?php endif; ?>

	</div>
</section>

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
 *                           summary     string One line, shown before the role opens.
 *                           description string The role. Editor HTML, sanitised on
 *                                              save with wp_kses_post() and again here.
 *                           posted      string Optional. YYYY-MM-DD.
 *                           apply_url   string Optional. Overrides apply_url below.
 *   apply_url     string  Where Apply goes when a role has no address of its own.
 *   apply_label   string  Optional. Words on the empty state's button.
 *   empty_heading string  Shown when there are no roles.
 *   empty_text    string  Shown under it.
 *
 * Example:
 *   syn_section( 'positions', array( 'items' => syn_field_rows( 'careers_positions_list' ) ) );
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
 * JobPosting STRUCTURED DATA is emitted for every role that carries a posted
 * date, because Google requires datePosted and a listing without one is
 * rejected rather than shown. Yoast has no job block, so there is nothing to
 * stand down for — unlike the FAQ band (CLAUDE.md §8).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow       = trim( (string) ( $args['eyebrow'] ?? '' ) );
$syn_heading       = trim( (string) ( $args['heading'] ?? __( 'Open positions', 'synergi' ) ) );
$syn_lede          = trim( (string) ( $args['lede'] ?? '' ) );
$syn_apply_url     = trim( (string) ( $args['apply_url'] ?? '' ) );
$syn_apply_label   = trim( (string) ( $args['apply_label'] ?? '' ) );
$syn_empty_heading = trim( (string) ( $args['empty_heading'] ?? '' ) );
$syn_empty_text    = trim( (string) ( $args['empty_text'] ?? '' ) );

if ( '' === $syn_apply_url && function_exists( 'syn_contact_url' ) ) {
	$syn_apply_url = syn_contact_url();
}

if ( '' === $syn_apply_label ) {
	$syn_apply_label = __( 'Send your CV', 'synergi' );
}

$syn_departments = function_exists( 'syn_careers_department_choices' ) ? syn_careers_department_choices() : array();
$syn_types       = function_exists( 'syn_careers_type_choices' ) ? syn_careers_type_choices() : array();

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
	$syn_role_url   = trim( (string) ( $syn_row['apply_url'] ?? '' ) );

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
		// than printed, so the schema never carries a string Google rejects.
		'posted'          => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $syn_posted ) ? $syn_posted : '',
		'apply_url'       => '' !== $syn_role_url ? $syn_role_url : $syn_apply_url,
	);
}

$syn_count = count( $syn_clean );
$syn_uid   = wp_unique_id( 'syn-positions-' );
$syn_group = $syn_uid . '-group';
?>
<section class="syn-positions syn-section" id="positions" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container syn-container--narrow">

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

		<?php if ( ! $syn_count ) : ?>

			<div class="syn-positions__empty syn-reveal">
				<h3 class="syn-positions__empty-title"><?php echo esc_html( '' !== $syn_empty_heading ? $syn_empty_heading : __( 'No open roles right now', 'synergi' ) ); ?></h3>

				<?php if ( '' !== $syn_empty_text ) : ?>
					<p class="syn-positions__empty-text"><?php echo esc_html( $syn_empty_text ); ?></p>
				<?php endif; ?>

				<a class="syn-button syn-button--primary" href="<?php echo esc_url( $syn_apply_url ); ?>">
					<?php echo esc_html( $syn_apply_label ); ?>
					<span aria-hidden="true">&rarr;</span>
				</a>
			</div>

		<?php else : ?>

			<div class="syn-positions__list syn-reveal">
				<?php foreach ( $syn_clean as $syn_index => $syn_item ) : ?>
					<?php
					/*
					 * The accent attribute is only written when there is one, so
					 * a role under Operations has no data-accent at all and the
					 * stylesheet's fallback applies without a selector for "none".
					 */
					$syn_accent_attr = '' !== $syn_item['accent'] ? ' data-accent="' . esc_attr( $syn_item['accent'] ) . '"' : '';

					/*
					 * A mailto: Apply carries the role in the subject line so an
					 * inbox of applications sorts itself. A role with its own
					 * address goes there untouched.
					 */
					$syn_apply_href = $syn_item['apply_url'];

					if ( 0 === strpos( $syn_apply_href, 'mailto:' ) ) {
						$syn_apply_href .= '?subject=' . rawurlencode( sprintf(
							/* translators: %s: the role title. */
							__( 'Application: %s', 'synergi' ),
							$syn_item['title']
						) );
					}
					?>
					<details class="syn-positions__item" name="<?php echo esc_attr( $syn_group ); ?>"<?php echo $syn_accent_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from esc_attr() only. ?>>
						<summary class="syn-positions__summary">
							<span class="syn-positions__summary-copy">
								<h3 class="syn-positions__role"><?php echo esc_html( $syn_item['title'] ); ?></h3>

								<span class="syn-positions__meta">
									<?php if ( '' !== $syn_item['department_name'] ) : ?>
										<span class="syn-positions__chip syn-positions__chip--department"><?php echo esc_html( $syn_item['department_name'] ); ?></span>
									<?php endif; ?>

									<?php if ( '' !== $syn_item['location'] ) : ?>
										<span class="syn-positions__chip">
											<svg class="syn-positions__chip-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
												<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z" />
												<circle cx="12" cy="9.5" r="2.5" />
											</svg>
											<?php echo esc_html( $syn_item['location'] ); ?>
										</span>
									<?php endif; ?>

									<?php if ( '' !== $syn_item['type_name'] ) : ?>
										<span class="syn-positions__chip"><?php echo esc_html( $syn_item['type_name'] ); ?></span>
									<?php endif; ?>
								</span>

								<?php if ( '' !== $syn_item['summary'] ) : ?>
									<span class="syn-positions__teaser"><?php echo esc_html( $syn_item['summary'] ); ?></span>
								<?php endif; ?>
							</span>

							<span class="syn-positions__marker" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" focusable="false">
									<path d="M12 5v14" class="syn-positions__marker-bar" />
									<path d="M5 12h14" />
								</svg>
							</span>
						</summary>

						<div class="syn-positions__body">
							<?php if ( '' !== $syn_item['description'] ) : ?>
								<div class="syn-positions__description">
									<?php echo wp_kses_post( $syn_item['description'] ); ?>
								</div>
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

								<a class="syn-button syn-button--primary syn-positions__apply" href="<?php echo esc_url( $syn_apply_href ); ?>">
									<?php
									printf(
										/* translators: %s: the role title, read by assistive technology only. */
										esc_html__( 'Apply %s', 'synergi' ),
										'<span class="syn-visually-hidden">' . esc_html( $syn_item['title'] ) . '</span>'
									);
									?>
									<span aria-hidden="true">&rarr;</span>
								</a>
							</div>
						</div>
					</details>
				<?php endforeach; ?>
			</div>

		<?php endif; ?>

	</div>

	<?php
	/*
	 * One JobPosting per dated role. hiringOrganization and the page URL come
	 * from WordPress rather than being typed, so nothing here assumes a domain
	 * (CLAUDE.md §12). JSON_HEX_TAG is what makes the block safe against a
	 * "</script>" inside a stored description — and esc_html() must NOT be used
	 * on it; see sections/faq.php for the day that lesson was learned.
	 */
	$syn_postings = array();

	foreach ( $syn_clean as $syn_item ) {
		if ( '' === $syn_item['posted'] || '' === $syn_item['description'] ) {
			continue;
		}

		$syn_posting = array(
			'@context'           => 'https://schema.org',
			'@type'              => 'JobPosting',
			'title'              => $syn_item['title'],
			'description'        => wp_kses_post( $syn_item['description'] ),
			'datePosted'         => $syn_item['posted'],
			'hiringOrganization' => array(
				'@type'  => 'Organization',
				'name'   => get_bloginfo( 'name' ),
				'sameAs' => home_url( '/' ),
			),
			'url'                => get_permalink() . '#positions',
		);

		if ( '' !== $syn_item['type'] ) {
			$syn_posting['employmentType'] = strtoupper( $syn_item['type'] );
		}

		if ( '' !== $syn_item['location'] ) {
			$syn_posting['jobLocation'] = array(
				'@type'   => 'Place',
				'address' => array(
					'@type'           => 'PostalAddress',
					'addressLocality' => $syn_item['location'],
				),
			);
		}

		if ( '' !== $syn_item['department_name'] ) {
			$syn_posting['occupationalCategory'] = $syn_item['department_name'];
		}

		$syn_postings[] = $syn_posting;
	}

	if ( $syn_postings ) {
		printf(
			'<script type="application/ld+json">%s</script>',
			wp_json_encode(
				1 === count( $syn_postings ) ? $syn_postings[0] : $syn_postings,
				JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			)
		);
	}
	?>
</section>

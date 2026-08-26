<?php
/**
 * Section 04 — industries we serve.
 *
 * Rendered through syn_section( 'industries', $args ). Styled by
 * assets/css/sections/industries.css, driven by assets/js/sections/industries.js.
 *
 * Six photographs in a rail. The first is wide and shows its heading and
 * description; the rest are narrowing strips queued behind it. Choosing one
 * brings it to the front and pushes the ones it skipped to the back.
 *
 * Expected $args (all optional — the defaults are the approved copy):
 *   eyebrow string  Small label above the heading.
 *   title   string  The section's <h2>.
 *   cards   array[] One entry per industry, in queue order, each:
 *                     slug        string Attachment slug for the photograph,
 *                                        i.e. the upload filename without its
 *                                        extension. Resolved through
 *                                        syn_attachment_id_by_slug().
 *                     image_id    int    Optional. Overrides slug when set —
 *                                        this is what Stage 6's field passes.
 *                     title       string The card's <h3>.
 *                     preview     string Short label burnt over the photograph.
 *                     description string One sentence under the heading.
 *
 * Example:
 *   syn_section( 'industries', array( 'title' => 'Sectors we know' ) );
 *
 * Alt text comes from the attachment, not from here — core reads it off the
 * media library entry (CLAUDE.md §8), which is where a person can fix it
 * without touching code.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'Sector expertise', 'synergi' );
$syn_title   = $args['title'] ?? __( 'Industries We Serve Across the Gulf', 'synergi' );

/*
 * The live region's sentence, handed to industries.js in a data attribute so
 * the script substitutes into the translated string rather than assembling an
 * English one of its own.
 */
/* translators: %s: industry name. */
$syn_status_template = __( 'Showing %s.', 'synergi' );

$syn_cards = $args['cards'] ?? array(
	array(
		'slug'        => 'industry-private-equity',
		'title'       => __( 'Private Equity, Family Offices & Holdings', 'synergi' ),
		'preview'     => __( 'Private Equity & Holdings', 'synergi' ),
		'description' => __( 'Shared services, portfolio reporting, governance, and specialist operating support for investment firms and diversified holdings.', 'synergi' ),
	),
	array(
		'slug'        => 'industry-public-sector',
		'title'       => __( 'Semi-government & Public Sector', 'synergi' ),
		'preview'     => __( 'Public Sector', 'synergi' ),
		'description' => __( 'Compliant HR, payroll, procurement, technology, and back-office delivery for government-linked and public-sector organizations.', 'synergi' ),
	),
	array(
		'slug'        => 'industry-financial-services',
		'title'       => __( 'Financial Services', 'synergi' ),
		'preview'     => __( 'Financial Services', 'synergi' ),
		'description' => __( 'Payroll outsourcing, compliance, secure data handling, finance operations, and reporting for financial services and banking organizations.', 'synergi' ),
	),
	array(
		'slug'        => 'industry-energy-manufacturing',
		'title'       => __( 'Energy, Agriculture & Manufacturing', 'synergi' ),
		'preview'     => __( 'Energy & Manufacturing', 'synergi' ),
		'description' => __( 'Procurement, inventory, finance, and operational support for energy, agriculture, automotive, and manufacturing businesses.', 'synergi' ),
	),
	array(
		'slug'        => 'industry-real-estate-construction',
		'title'       => __( 'Real Estate & Construction', 'synergi' ),
		'preview'     => __( 'Real Estate & Construction', 'synergi' ),
		'description' => __( 'Lead generation, document processing, project coordination, and back-office support across development and property portfolios.', 'synergi' ),
	),
	array(
		'slug'        => 'industry-retail-hospitality-healthcare',
		'title'       => __( 'Retail, Hospitality & Healthcare', 'synergi' ),
		'preview'     => __( 'Retail, Hospitality & Healthcare', 'synergi' ),
		'description' => __( 'Customer care, order management, returns, patient support, medical record management, and billing for service-led operators.', 'synergi' ),
	),
);

$syn_cards = array_values( array_filter( (array) $syn_cards, 'is_array' ) );

if ( ! $syn_cards ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section industries: no cards to render -->\n";
	}

	return;
}

/*
 * One id prefix per request. The card headings and descriptions are referenced
 * by aria-labelledby and aria-describedby from the buttons, so they need ids
 * that are unique on the page — and this section could in principle render
 * twice on one page once templates start composing freely.
 */
$syn_uid = wp_unique_id( 'syn-industries-' );
?>
<section class="syn-industries syn-section" id="industries" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-industries__heading syn-reveal">
			<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<h2 class="syn-industries__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_title ); ?></h2>
		</div>

		<div
			class="syn-industries__queue syn-reveal"
			data-syn-industries-queue
			role="region"
			aria-roledescription="<?php esc_attr_e( 'carousel', 'synergi' ); ?>"
			aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title"
		>
			<div class="syn-industries__rail" data-syn-industries-rail>
				<?php
				foreach ( $syn_cards as $syn_index => $syn_card ) :
					$syn_card_title = $syn_card['title'] ?? '';

					if ( ! $syn_card_title ) {
						if ( SYN_DEBUG ) {
							echo "\n<!-- syn-section industries: card " . (int) $syn_index . " has no title -->\n";
						}

						continue;
					}

					$syn_image_id = isset( $syn_card['image_id'] )
						? (int) $syn_card['image_id']
						: syn_attachment_id_by_slug( $syn_card['slug'] ?? '' );

					$syn_title_id = $syn_uid . '-card-title-' . (int) $syn_index;
					$syn_desc_id  = $syn_uid . '-card-desc-' . (int) $syn_index;
					$syn_active   = 0 === $syn_index;

					/*
					 * The first card is marked active server-side so the section
					 * arrives fully formed. industries.js is deferred; a state
					 * applied only in JavaScript would land after first paint and
					 * the whole rail would visibly reflow.
					 */
					$syn_card_class = $syn_active
						? 'syn-industries__card syn-is-active'
						: 'syn-industries__card';
					?>
					<article
						class="<?php echo esc_attr( $syn_card_class ); ?>"
						data-syn-industries-card
						data-syn-industries-position="<?php echo (int) $syn_index; ?>"
						role="group"
						aria-roledescription="<?php esc_attr_e( 'slide', 'synergi' ); ?>"
						aria-label="<?php echo esc_attr( $syn_card_title ); ?>"
					>
						<?php
						/*
						 * A button, not a link: choosing an industry moves the rail,
						 * it does not navigate. aria-pressed carries which one is
						 * showing, and the label and description are borrowed from
						 * the heading and paragraph below rather than duplicated.
						 */
						?>
						<button
							class="syn-industries__image"
							type="button"
							data-syn-industries-go="<?php echo (int) $syn_index; ?>"
							aria-pressed="<?php echo $syn_active ? 'true' : 'false'; ?>"
							aria-labelledby="<?php echo esc_attr( $syn_title_id ); ?>"
							aria-describedby="<?php echo esc_attr( $syn_desc_id ); ?>"
						>
							<?php
							if ( $syn_image_id ) {
								/*
								 * sizes says 100vw on a phone, where one card fills the
								 * screen, and 58rem above it — the widest the image is
								 * drawn, since industries.css oversizes it to 2.3x the
								 * card height so a narrowing strip crops rather than
								 * squashes.
								 */
								echo wp_get_attachment_image(
									$syn_image_id,
									'full',
									false,
									array(
										'class'    => 'syn-industries__photo',
										'loading'  => 'lazy',
										'decoding' => 'async',
										'sizes'    => '(max-width: 47.99rem) 100vw, 58rem',
									)
								);
							}
							?>
							<span class="syn-industries__scrim" aria-hidden="true"></span>
							<span class="syn-industries__preview" aria-hidden="true"><?php echo esc_html( $syn_card['preview'] ?? $syn_card_title ); ?></span>
						</button>

						<div class="syn-industries__copy">
							<h3 class="syn-industries__card-title" id="<?php echo esc_attr( $syn_title_id ); ?>"><?php echo esc_html( $syn_card_title ); ?></h3>
							<p class="syn-industries__card-text" id="<?php echo esc_attr( $syn_desc_id ); ?>"><?php echo esc_html( $syn_card['description'] ?? '' ); ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>

			<div class="syn-industries__toolbar">
				<div class="syn-industries__controls">
					<button class="syn-industries__control" type="button" data-syn-industries-prev aria-label="<?php esc_attr_e( 'Show previous industry', 'synergi' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m14.5 6-6 6 6 6" /></svg>
					</button>
					<button class="syn-industries__control" type="button" data-syn-industries-next aria-label="<?php esc_attr_e( 'Show next industry', 'synergi' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9.5 6 6 6-6 6" /></svg>
					</button>
				</div>
			</div>

			<p
				class="syn-visually-hidden"
				data-syn-industries-status="<?php echo esc_attr( $syn_status_template ); ?>"
				aria-live="polite"
				aria-atomic="true"
			>
				<?php printf( esc_html( $syn_status_template ), esc_html( $syn_cards[0]['title'] ?? '' ) ); ?>
			</p>

		</div>
	</div>
</section>

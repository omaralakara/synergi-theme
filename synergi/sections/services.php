<?php
/**
 * Section 02 — core BPO services.
 *
 * Rendered through syn_section( 'services', $args ). Styled by
 * assets/css/sections/services.css, driven by assets/js/sections/services.js.
 *
 * A deck of six service cards: one is face-on, the rest fan out behind it.
 * Previous/next buttons, arrow keys and a horizontal swipe move through them.
 *
 * Expected $args (all optional — the defaults are the approved copy):
 *   eyebrow string  Small label above the heading.
 *   title   string  The section's <h2>.
 *   lead    string  The paragraph under the heading.
 *   cards   array[] One entry per card, each:
 *                     slug         string   Matches assets/icons/SLUG.svg and
 *                                           the card's accent modifier class.
 *                     name         string   The card's <h3>.
 *                     label        string   Small uppercase line by the index.
 *                     summary      string   One sentence. Clamped to 3 lines.
 *                     url          string   Where "Explore X" goes.
 *                     capabilities string[] The bullet list.
 *
 * Example:
 *   syn_section( 'services', array( 'title' => 'What we run for you' ) );
 *
 * WHERE THE CARDS COME FROM, since Stage 6e. syn_service_cards() builds them
 * from the "services" record at Settings → Site records, and reads each card's
 * bullet list from that service's own page — so a capability renamed on the HR
 * page is renamed here, and a seventh service line added to the record appears
 * in the deck with no code change (CLAUDE.md §7a).
 *
 * The six arrays below are the fallback and nothing else. They render only when
 * the record is empty, which keeps the homepage from ever showing an empty deck
 * (CLAUDE.md §7c). Passing $args['cards'] still overrides both.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'One connected operating model', 'synergi' );
$syn_title   = $args['title'] ?? __( 'Our Core BPO Services', 'synergi' );
$syn_lead    = $args['lead'] ?? __( 'Six specialist functions, coordinated around your business.', 'synergi' );

/*
 * The live region's sentence, handed to services.js in a data attribute so the
 * script substitutes into the translated string instead of building an English
 * one of its own.
 */
/* translators: %s: service name, e.g. Accounting. */
$syn_status_template = __( 'Showing %s.', 'synergi' );

/*
 * Six cards, not five. CLAUDE.md §12a flags that the design source ships seven
 * icons against five service pages; the sixth card, Project Management, is in
 * the approved homepage markup and links to the services hub rather than to a
 * page of its own, because that page does not exist. It is reproduced here as
 * designed. If a Project Management page is ever published, only its url below
 * changes.
 */
$syn_cards = $args['cards'] ?? array();

if ( ! $syn_cards && function_exists( 'syn_service_cards' ) ) {
	$syn_cards = syn_service_cards();
}

/*
 * The fallback, and the reason it is this long: the homepage is the page the
 * business is judged on, and an empty "services" record must not be able to
 * empty it. These are the six cards as the approved design ships them.
 */
if ( ! $syn_cards ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section services: the services record is empty, so the built-in six are rendering. Fill it at Settings > Site records. -->\n";
	}

	$syn_cards = array(
		array(
			'slug'         => 'accounting',
			'name'         => __( 'Accounting', 'synergi' ),
			'label'        => __( 'Finance operations and reporting', 'synergi' ),
			'summary'      => __( 'Support for day-to-day finance processes, transaction cycles, analysis, and reporting.', 'synergi' ),
			'url'          => home_url( '/our-services/accounting/' ),
			'capabilities' => array(
				__( 'Bookkeeping, VAT & Tax', 'synergi' ),
				__( 'Record to Report', 'synergi' ),
				__( 'Order to Cash', 'synergi' ),
				__( 'Procure to Pay', 'synergi' ),
				__( 'Financial Planning & Analysis', 'synergi' ),
				__( 'Analytics and Reporting', 'synergi' ),
			),
		),
		array(
			'slug'         => 'human-resources',
			'name'         => __( 'Human Resources', 'synergi' ),
			'label'        => __( 'People operations and development', 'synergi' ),
			'summary'      => __( 'HR outsourcing and payroll support across the employee lifecycle — systems, development, and leadership capacity.', 'synergi' ),
			'url'          => home_url( '/our-services/human-resources/' ),
			'capabilities' => array(
				__( 'Hire to Retire', 'synergi' ),
				__( 'Payroll and Pension', 'synergi' ),
				__( 'Performance Management', 'synergi' ),
				__( 'Organizational Development', 'synergi' ),
				__( 'Learning & Development', 'synergi' ),
				__( 'HRMS Implementation', 'synergi' ),
				__( 'Manpower Augmentation', 'synergi' ),
				__( 'CXO as a Service', 'synergi' ),
			),
		),
		array(
			'slug'         => 'procurement',
			'name'         => __( 'Procurement', 'synergi' ),
			'label'        => __( 'Sourcing, contracts, and spend visibility', 'synergi' ),
			'summary'      => __( 'Procurement outsourcing from sourcing and governance through administration, negotiation, and spend analysis.', 'synergi' ),
			'url'          => home_url( '/our-services/procurement/' ),
			'capabilities' => array(
				__( 'Procurement Health Check', 'synergi' ),
				__( 'Procurement Function Build-Up', 'synergi' ),
				__( 'Governance, Compliance and Control', 'synergi' ),
				__( 'Procurement Planning and Inventory Management', 'synergi' ),
				__( 'Strategic Sourcing and Cost Optimization', 'synergi' ),
				__( 'Supplier Lifecycle Management', 'synergi' ),
				__( 'End-to-End Operational Support (BPO)', 'synergi' ),
			),
		),
		array(
			'slug'         => 'technology-ai',
			'name'         => __( 'Technology & AI', 'synergi' ),
			'label'        => __( 'Systems, support, and managed services', 'synergi' ),
			'summary'      => __( 'Technology operations that connect end-user support, infrastructure, compliance, and business systems.', 'synergi' ),
			'url'          => home_url( '/our-services/technology-ai/' ),
			'capabilities' => array(
				__( 'ERP Implementation', 'synergi' ),
				__( 'Custom AI Applications', 'synergi' ),
				__( 'AI-Enabled Business Process Automation', 'synergi' ),
				__( 'Service Desk and End-User Support', 'synergi' ),
				__( 'On-Premises and Cloud Managed Services', 'synergi' ),
				__( 'Regulatory Compliance', 'synergi' ),
				__( 'Procurement Support', 'synergi' ),
				__( 'IT Managed Services', 'synergi' ),
				__( 'Collaboration Tooling', 'synergi' ),
			),
		),
		array(
			'slug'         => 'marketing',
			'name'         => __( 'Marketing', 'synergi' ),
			'label'        => __( 'Brand, communications, and experience', 'synergi' ),
			'summary'      => __( 'Strategic and operational marketing support across brand, content, events, public relations, and customer experience.', 'synergi' ),
			'url'          => home_url( '/our-services/marketing/' ),
			'capabilities' => array(
				__( 'Marketing Audit and Strategy Development', 'synergi' ),
				__( 'Social Media Management and Paid Digital Ads', 'synergi' ),
				__( 'SEO, GEO and Website Optimization', 'synergi' ),
				__( 'Events Strategy and Management', 'synergi' ),
				__( 'Brand Development and Positioning', 'synergi' ),
				__( 'PR Representation and Media Relations', 'synergi' ),
				__( 'Reporting, Performance Analysis and Data Management', 'synergi' ),
				__( 'Fractional Chief Marketing Officer (CMO)', 'synergi' ),
			),
		),
		array(
			'slug'         => 'project-management',
			'name'         => __( 'Project Management', 'synergi' ),
			'label'        => __( 'Governance, delivery, and transformation offices', 'synergi' ),
			'summary'      => __( 'Structured project and program delivery with clear governance, resources, controls, and performance reporting.', 'synergi' ),
			'url'          => home_url( '/our-services/' ),
			'capabilities' => array(
				__( 'PMO Setup and Governance Frameworks', 'synergi' ),
				__( 'End-to-End Project Planning and Delivery', 'synergi' ),
				__( 'Program and Portfolio Management', 'synergi' ),
				__( 'Project Resource Planning and Augmentation', 'synergi' ),
				__( 'Risk, Budget and Schedule Control', 'synergi' ),
				__( 'Vendor and Stakeholder Coordination', 'synergi' ),
				__( 'Project Reporting and Performance Dashboards', 'synergi' ),
				__( 'Transformation Office Setup and Management', 'synergi' ),
			),
		),
	);
}

$syn_cards = array_values( array_filter( (array) $syn_cards, 'is_array' ) );

if ( ! $syn_cards ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section services: no cards to render -->\n";
	}

	return;
}
?>
<section class="syn-services syn-section" id="services" aria-labelledby="syn-services-title">
	<div class="syn-container">

		<?php
		/*
		 * aria-roledescription tells a screen reader this is a carousel before
		 * it reaches the cards, so the arrow-key hint on the viewport below
		 * makes sense when it arrives.
		 */
		?>
		<div class="syn-services__deck syn-reveal" data-syn-service-deck aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Synergi core BPO services', 'synergi' ); ?>">

			<div class="syn-services__copy">
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
				<h2 class="syn-services__title" id="syn-services-title"><?php echo esc_html( $syn_title ); ?></h2>
				<p class="syn-services__lead"><?php echo esc_html( $syn_lead ); ?></p>

				<?php
				/*
				 * Hidden until JavaScript runs (services.css puts these inside
				 * a scripting: enabled query): buttons that cannot move
				 * anything are worse than no buttons.
				 */
				?>
				<div class="syn-services__controls">
					<button class="syn-services__control" type="button" data-syn-service-prev aria-label="<?php esc_attr_e( 'Show previous service', 'synergi' ); ?>">
						<span aria-hidden="true">&#8592;</span>
					</button>
					<button class="syn-services__control" type="button" data-syn-service-next aria-label="<?php esc_attr_e( 'Show next service', 'synergi' ); ?>">
						<span aria-hidden="true">&#8594;</span>
					</button>
				</div>
			</div>

			<div class="syn-services__stage">
				<div class="syn-services__viewport" role="region" tabindex="0" aria-label="<?php esc_attr_e( 'Layered service cards. Use the left and right arrow keys to browse.', 'synergi' ); ?>">
					<div class="syn-services__track" data-syn-service-track>

						<?php
						foreach ( $syn_cards as $syn_index => $syn_card ) :
							$syn_slug = isset( $syn_card['slug'] ) ? sanitize_key( $syn_card['slug'] ) : '';
							$syn_name = $syn_card['name'] ?? '';

							if ( ! $syn_slug || ! $syn_name ) {
								if ( SYN_DEBUG ) {
									echo "\n<!-- syn-section services: card " . (int) $syn_index . " is missing slug or name -->\n";
								}

								continue;
							}

							/*
							 * Written out in full, not assembled from the slug,
							 * so every accent class in services.css is findable
							 * by searching for it (CLAUDE.md §13, the grep rule).
							 */
							$syn_accent_classes = array(
								'accounting'         => 'syn-services__card--accounting',
								'human-resources'    => 'syn-services__card--human-resources',
								'marketing'          => 'syn-services__card--marketing',
								'procurement'        => 'syn-services__card--procurement',
								'project-management' => 'syn-services__card--project-management',
								'technology-ai'      => 'syn-services__card--technology-ai',
							);

							$syn_card_class = 'syn-services__card';

							if ( isset( $syn_accent_classes[ $syn_slug ] ) ) {
								$syn_card_class .= ' ' . $syn_accent_classes[ $syn_slug ];
							}

							/*
							 * The deck position is rendered here rather than
							 * left to services.js. The script is deferred, so a
							 * position applied in JavaScript would land after
							 * first paint and the whole deck would visibly jump
							 * into place. aria-hidden and inert are NOT set
							 * here: with JavaScript off nothing would ever
							 * clear them, and five of the six services would be
							 * unreachable.
							 */
							?>
							<article
								class="<?php echo esc_attr( $syn_card_class ); ?>"
								data-syn-service-card
								data-syn-deck-position="<?php echo (int) $syn_index; ?>"
								role="group"
								aria-roledescription="<?php esc_attr_e( 'slide', 'synergi' ); ?>"
								aria-label="<?php echo esc_attr( $syn_name ); ?>"
							>
								<div class="syn-services__intro">

									<div class="syn-services__kicker">
										<span class="syn-services__index"><?php echo esc_html( sprintf( '%02d', $syn_index + 1 ) ); ?></span>
										<?php if ( ! empty( $syn_card['label'] ) ) : ?>
											<p class="syn-services__label"><?php echo esc_html( $syn_card['label'] ); ?></p>
										<?php endif; ?>
										<?php syn_inline_icon( $syn_slug, 'syn-services__icon' ); ?>
									</div>

									<h3 class="syn-services__name"><?php echo esc_html( $syn_name ); ?></h3>

									<?php if ( ! empty( $syn_card['summary'] ) ) : ?>
										<p class="syn-services__summary"><?php echo esc_html( $syn_card['summary'] ); ?></p>
									<?php endif; ?>

									<?php if ( ! empty( $syn_card['url'] ) ) : ?>
										<a class="syn-text-link syn-services__link" href="<?php echo esc_url( $syn_card['url'] ); ?>">
											<?php
											printf(
												/* translators: %s: service name, e.g. Accounting. */
												esc_html__( 'Explore %s', 'synergi' ),
												esc_html( $syn_name )
											);
											?>
											<span aria-hidden="true">&#8594;</span>
										</a>
									<?php endif; ?>

								</div>

								<?php if ( ! empty( $syn_card['capabilities'] ) ) : ?>
									<div class="syn-services__capabilities">
										<p class="syn-services__capabilities-title"><?php esc_html_e( 'Capabilities', 'synergi' ); ?></p>
										<ul class="syn-services__capability-list">
											<?php foreach ( (array) $syn_card['capabilities'] as $syn_capability ) : ?>
												<li><?php echo esc_html( $syn_capability ); ?></li>
											<?php endforeach; ?>
										</ul>
									</div>
								<?php endif; ?>

							</article>
						<?php endforeach; ?>

					</div>
				</div>
			</div>

			<?php
			/*
			 * The card change is silent to a screen reader without this: the
			 * deck moves visually, but focus never leaves the viewport.
			 * services.js writes the new service name here.
			 */
			?>
			<p class="syn-visually-hidden" data-syn-service-status="<?php echo esc_attr( $syn_status_template ); ?>" aria-live="polite">
				<?php printf( esc_html( $syn_status_template ), esc_html( $syn_cards[0]['name'] ?? '' ) ); ?>
			</p>

		</div>
	</div>
</section>

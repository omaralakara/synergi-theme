<?php
/**
 * Section 03 — shared services.
 *
 * Rendered through syn_section( 'shared-services', $args ). Styled by
 * assets/css/sections/shared-services.css. No JavaScript: the hub floats on a
 * CSS animation and nothing here responds to input.
 *
 * Copy on one side, a hub diagram on the other — six service bubbles ringed
 * around a core, joined by dashed connectors.
 *
 * Expected $args (all optional — the defaults are the approved copy):
 *   eyebrow   string   Small label above the heading.
 *   title     string   The section's <h2>.
 *   lead      string   Paragraph under the heading. May contain links.
 *   steps     array[]  { number, label } — the delivery model, in order.
 *   cta       array    { label, url } for the primary button.
 *   markets   string[] Short market names, shown dot-separated.
 *   chips     string[] Benefit pills under the copy.
 *   nodes     array[]  { slug, lines } — the six bubbles, in hub order
 *                      starting at the top and going clockwise. slug matches
 *                      assets/icons/hub-SLUG.svg and the position class.
 *   core      array    { label, lines } for the middle bubble.
 *   note      string   Closing paragraph under both columns. May contain links.
 *
 * Example:
 *   syn_section( 'shared-services', array( 'title' => 'One regional team' ) );
 *
 * The hub is one picture, so the stage carries role="img" and a single label
 * describing the whole diagram (CLAUDE.md §9). Everything inside it is
 * decoration as far as assistive technology is concerned — which is why the
 * bubble names are repeated in that label rather than left to be read one by
 * one out of context.
 *
 * Copy is hard-coded here for Stage 5, as in sections 01 and 02. Stage 6 moves
 * it to the hand-built fields; the $args shape above is already what those
 * fields will fill.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'Regional operations', 'synergi' );
$syn_title   = $args['title'] ?? __( 'Transform Your Business with Shared Services', 'synergi' );

/*
 * Built with sprintf rather than written as one HTML string, so the URL comes
 * from home_url() and never hard-codes a domain (CLAUDE.md §12) — the site
 * moves to synergibpo.com later and this must not need editing when it does.
 */
$syn_lead = $args['lead'] ?? sprintf(
	/* translators: %s: link to the procurement service page, reading "procurement in the UAE". */
	__( 'Centralize your back-office functions in one shared-services team covering the UAE, Dubai, and the wider GCC. From HR and finance to %s, we help you cut costs, strengthen compliance, and scale faster.', 'synergi' ),
	'<a href="' . esc_url( home_url( '/our-services/procurement/' ) ) . '">' . esc_html__( 'procurement in the UAE', 'synergi' ) . '</a>'
);

$syn_note = $args['note'] ?? sprintf(
	/* translators: %s: link to the Saudi Arabia BPO page, reading "BPO services in Saudi Arabia". */
	__( 'Looking to optimize operations beyond shared services? Our %s support businesses in Riyadh and across KSA — streamlining workflows, lifting performance, and sustaining growth.', 'synergi' ),
	'<a href="' . esc_url( home_url( '/bpo-services-in-saudi-arabia-ksa-riyadh/' ) ) . '">' . esc_html__( 'BPO services in Saudi Arabia', 'synergi' ) . '</a>'
);

$syn_steps = $args['steps'] ?? array(
	array(
		'number' => '01',
		'label'  => __( 'Understand', 'synergi' ),
	),
	array(
		'number' => '02',
		'label'  => __( 'Design', 'synergi' ),
	),
	array(
		'number' => '03',
		'label'  => __( 'Deliver', 'synergi' ),
	),
);

$syn_cta = $args['cta'] ?? array(
	'label' => __( 'Discover Shared Services UAE', 'synergi' ),
	'url'   => home_url( '/shared-services-uae/' ),
);

$syn_markets = $args['markets'] ?? array(
	__( 'UAE', 'synergi' ),
	__( 'GCC', 'synergi' ),
	__( 'KSA', 'synergi' ),
);

$syn_chips = $args['chips'] ?? array(
	__( 'Cost Savings', 'synergi' ),
	__( 'Regional Reach', 'synergi' ),
	__( 'Access to Expertise', 'synergi' ),
	__( 'Scalable Services', 'synergi' ),
	__( 'Focus on Core Activities', 'synergi' ),
);

$syn_core = $args['core'] ?? array(
	'label' => __( 'Shared Services', 'synergi' ),
	'lines' => array( __( 'One Regional', 'synergi' ), __( 'Framework', 'synergi' ) ),
);

/*
 * Hub order, starting at the top and going clockwise. The order matters: the
 * position classes below are assigned in sequence, and the connector lines in
 * the SVG are drawn to those six points.
 *
 * "lines" is an array rather than a string with a <br> in it, so the partial
 * never has to trust markup from a field. Stage 6 stores the same shape.
 */
$syn_nodes = $args['nodes'] ?? array(
	array(
		'slug'  => 'accounting',
		'lines' => array( __( 'Accounting', 'synergi' ) ),
	),
	array(
		'slug'  => 'human-resources',
		'lines' => array( __( 'Human', 'synergi' ), __( 'Resources', 'synergi' ) ),
	),
	array(
		'slug'  => 'procurement',
		'lines' => array( __( 'Procurement', 'synergi' ) ),
	),
	array(
		'slug'  => 'technology-ai',
		'lines' => array( __( 'Technology', 'synergi' ), __( '& AI', 'synergi' ) ),
	),
	array(
		'slug'  => 'marketing',
		'lines' => array( __( 'Marketing', 'synergi' ) ),
	),
	array(
		'slug'  => 'project-management',
		'lines' => array( __( 'Project', 'synergi' ), __( 'Management', 'synergi' ) ),
	),
);

/*
 * Written out in full rather than built from the loop index, so every position
 * class in shared-services.css can be found by searching for it (CLAUDE.md §13,
 * the grep rule). Six positions, so six entries; a seventh node would need a
 * seventh here and a seventh connector line, which is the point of it failing
 * loudly rather than silently stacking on the sixth.
 */
$syn_orb_positions = array(
	'syn-shared-services__orb--1',
	'syn-shared-services__orb--2',
	'syn-shared-services__orb--3',
	'syn-shared-services__orb--4',
	'syn-shared-services__orb--5',
	'syn-shared-services__orb--6',
);

$syn_nodes = array_values( array_filter( (array) $syn_nodes, 'is_array' ) );

if ( count( $syn_nodes ) > count( $syn_orb_positions ) ) {
	if ( SYN_DEBUG ) {
		printf(
			"\n<!-- syn-section shared-services: %d nodes given but the hub has only %d positions; the extras are not rendered -->\n",
			count( $syn_nodes ),
			count( $syn_orb_positions )
		);
	}

	$syn_nodes = array_slice( $syn_nodes, 0, count( $syn_orb_positions ) );
}

/*
 * One label for the whole diagram, assembled from the node names so it cannot
 * drift out of step with what is drawn.
 */
$syn_hub_names = array();

foreach ( $syn_nodes as $syn_node ) {
	$syn_hub_names[] = implode( ' ', (array) ( $syn_node['lines'] ?? array() ) );
}

$syn_hub_label = sprintf(
	/* translators: %s: comma-separated list of service names. */
	__( 'Shared services hub connecting %s under one regional framework.', 'synergi' ),
	implode( ', ', array_filter( $syn_hub_names ) )
);
?>
<section class="syn-shared-services syn-section" id="shared-services" aria-labelledby="syn-shared-services-title">
	<div class="syn-container">

		<div class="syn-shared-services__layout">

			<div class="syn-shared-services__copy syn-reveal">
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
				<h2 class="syn-shared-services__title" id="syn-shared-services-title"><?php echo esc_html( $syn_title ); ?></h2>
				<p class="syn-shared-services__lead"><?php echo wp_kses_post( $syn_lead ); ?></p>

				<?php if ( $syn_steps ) : ?>
					<ol class="syn-shared-services__steps" aria-label="<?php esc_attr_e( 'Synergi delivery model', 'synergi' ); ?>">
						<?php
						$syn_last_step = count( $syn_steps ) - 1;

						foreach ( array_values( $syn_steps ) as $syn_index => $syn_step ) :
							?>
							<li>
								<span class="syn-shared-services__step-number"><?php echo esc_html( $syn_step['number'] ?? '' ); ?></span>
								<?php echo esc_html( $syn_step['label'] ?? '' ); ?>
							</li>
							<?php
							/*
							 * The arrows are list items in the design's markup too. They
							 * stay elements rather than becoming a CSS ::after, because
							 * aria-hidden reliably keeps them out of the reading order and
							 * generated content does not — several screen readers announce
							 * ::after text, and "Understand right arrow Design" is not what
							 * this list should read as.
							 */
							if ( $syn_index < $syn_last_step ) :
								?>
								<li class="syn-shared-services__step-arrow" aria-hidden="true">&#8594;</li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>

				<div class="syn-shared-services__cta-row">
					<?php if ( ! empty( $syn_cta['label'] ) && ! empty( $syn_cta['url'] ) ) : ?>
						<a class="syn-button syn-button--primary" href="<?php echo esc_url( $syn_cta['url'] ); ?>">
							<?php echo esc_html( $syn_cta['label'] ); ?>
							<span aria-hidden="true">&#8594;</span>
						</a>
					<?php endif; ?>

					<?php if ( $syn_markets ) : ?>
						<p class="syn-shared-services__markets" aria-label="<?php esc_attr_e( 'Markets served', 'synergi' ); ?>">
							<?php
							$syn_last_market = count( $syn_markets ) - 1;

							foreach ( array_values( $syn_markets ) as $syn_index => $syn_market ) :
								?>
								<span><?php echo esc_html( $syn_market ); ?></span>
								<?php if ( $syn_index < $syn_last_market ) : ?>
									<i class="syn-shared-services__market-dot" aria-hidden="true"></i>
								<?php endif; ?>
							<?php endforeach; ?>
						</p>
					<?php endif; ?>
				</div>

				<?php if ( $syn_chips ) : ?>
					<ul class="syn-shared-services__chips" aria-label="<?php esc_attr_e( 'Benefits of outsourcing with Synergi', 'synergi' ); ?>">
						<?php foreach ( $syn_chips as $syn_chip ) : ?>
							<li><?php echo esc_html( $syn_chip ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

			<div class="syn-shared-services__stage syn-reveal" role="img" aria-label="<?php echo esc_attr( $syn_hub_label ); ?>">

				<?php
				/*
				 * The connectors. Drawn in a 0-100 viewBox so the six endpoints are
				 * the same percentages the position classes use — move a bubble in
				 * the stylesheet and its line has to move here by the same numbers.
				 */
				?>
				<svg class="syn-shared-services__links" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
					<line x1="50" y1="50" x2="50" y2="10" />
					<line x1="50" y1="50" x2="84.6" y2="30" />
					<line x1="50" y1="50" x2="84.6" y2="70" />
					<line x1="50" y1="50" x2="50" y2="88" />
					<line x1="50" y1="50" x2="15.4" y2="70" />
					<line x1="50" y1="50" x2="15.4" y2="30" />
				</svg>

				<div class="syn-shared-services__bubble syn-shared-services__core">
					<small class="syn-shared-services__core-label"><?php echo esc_html( $syn_core['label'] ?? '' ); ?></small>
					<b class="syn-shared-services__core-title">
						<?php
						foreach ( array_values( (array) ( $syn_core['lines'] ?? array() ) ) as $syn_index => $syn_line ) {
							echo $syn_index ? '<br />' : '';
							echo esc_html( $syn_line );
						}
						?>
					</b>
				</div>

				<?php
				foreach ( $syn_nodes as $syn_index => $syn_node ) :
					$syn_slug = isset( $syn_node['slug'] ) ? sanitize_key( $syn_node['slug'] ) : '';

					if ( ! $syn_slug ) {
						if ( SYN_DEBUG ) {
							echo "\n<!-- syn-section shared-services: node " . (int) $syn_index . " has no slug -->\n";
						}

						continue;
					}
					?>
					<div class="syn-shared-services__bubble syn-shared-services__orb <?php echo esc_attr( $syn_orb_positions[ $syn_index ] ); ?>">
						<?php syn_inline_icon( 'hub-' . $syn_slug, 'syn-shared-services__orb-icon' ); ?>
						<b class="syn-shared-services__orb-title">
							<?php
							foreach ( array_values( (array) ( $syn_node['lines'] ?? array() ) ) as $syn_line_index => $syn_line ) {
								echo $syn_line_index ? '<br />' : '';
								echo esc_html( $syn_line );
							}
							?>
						</b>
					</div>
				<?php endforeach; ?>

				<?php /* Four drifting specks. Pure decoration, and the only thing here that would be missed by nobody if it went. */ ?>
				<span class="syn-shared-services__dot syn-shared-services__dot--1" aria-hidden="true"></span>
				<span class="syn-shared-services__dot syn-shared-services__dot--2" aria-hidden="true"></span>
				<span class="syn-shared-services__dot syn-shared-services__dot--3" aria-hidden="true"></span>
				<span class="syn-shared-services__dot syn-shared-services__dot--4" aria-hidden="true"></span>
			</div>

		</div>

		<p class="syn-shared-services__note syn-reveal"><?php echo wp_kses_post( $syn_note ); ?></p>

	</div>
</section>

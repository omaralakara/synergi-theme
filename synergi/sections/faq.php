<?php
/**
 * Service section — frequently asked questions.
 *
 * Rendered through syn_section( 'faq', $args ). Styled by
 * assets/css/sections/faq.css. THERE IS NO faq.js AND THERE SHOULD NOT BE.
 *
 * The whole section is native <details>/<summary>. That choice does the work
 * three separate rules would otherwise need JavaScript for: every answer is
 * reachable with scripting off, the disclosure is keyboard-operable with no code
 * at all, and the open/closed state is exposed to assistive technology by the
 * browser rather than by hand-written ARIA (CLAUDE.md §9, §10). The `name`
 * attribute makes the group behave as an accordion where the browser supports
 * it, and where it does not, more than one answer simply opens at once — which
 * is a smaller failure than an accordion that needs a script to open.
 *
 * Expected $args:
 *   heading string  The section's <h2>.
 *   eyebrow string  Small label above the heading.
 *   items   array[] One entry per question, in reading order, each:
 *                     question string Required, plain text.
 *                     answer   string Required. The one field in the theme that
 *                                     keeps markup, sanitised on save with
 *                                     wp_kses_post() and escaped again here.
 *   schema  bool    Optional. Emit FAQPage JSON-LD. Default true, and suppressed
 *                   automatically where Yoast is already emitting it.
 *
 * Example:
 *   syn_section( 'faq', array( 'items' => syn_field_rows( 'faqs' ) ) );
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow = $args['eyebrow'] ?? __( 'Questions', 'synergi' );
$syn_heading = $args['heading'] ?? __( 'Frequently asked', 'synergi' );
$syn_items   = $args['items'] ?? array();

$syn_clean = array();

foreach ( (array) $syn_items as $syn_item ) {
	if ( ! is_array( $syn_item ) ) {
		continue;
	}

	$syn_question = trim( (string) ( $syn_item['question'] ?? '' ) );
	$syn_answer   = trim( (string) ( $syn_item['answer'] ?? '' ) );

	if ( '' === $syn_question || '' === $syn_answer ) {
		continue;
	}

	$syn_clean[] = array(
		'question' => $syn_question,
		'answer'   => $syn_answer,
	);
}

if ( ! $syn_clean ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section faq: no questions to render -->\n";
	}

	return;
}

$syn_uid   = wp_unique_id( 'syn-faq-' );
$syn_group = $syn_uid . '-group';

/*
 * Two FAQPage blocks on one URL is a Search Console error, not a bonus
 * (CLAUDE.md §8). Yoast ships an FAQ block that emits the same structured data,
 * so the theme checks for it in the post content and stands down rather than
 * competing. The filter is there for the case this check cannot see — a plugin
 * emitting it from somewhere else entirely.
 */
$syn_schema = $args['schema'] ?? true;

if ( $syn_schema && function_exists( 'has_block' ) && has_block( 'yoast/faq-block', get_queried_object_id() ) ) {
	$syn_schema = false;

	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section faq: Yoast's FAQ block is on this page, so the theme is not emitting FAQPage as well -->\n";
	}
}

/** Filters whether the FAQ section emits FAQPage structured data. */
$syn_schema = (bool) apply_filters( 'syn_faq_schema', $syn_schema, $syn_clean );
?>
<section class="syn-faq syn-section" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container syn-container--narrow">

		<div class="syn-faq__head syn-reveal">
			<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<h2 class="syn-faq__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_heading ); ?></h2>
		</div>

		<div class="syn-faq__list syn-reveal">
			<?php foreach ( $syn_clean as $syn_index => $syn_item ) : ?>
				<details class="syn-faq__item" name="<?php echo esc_attr( $syn_group ); ?>"<?php echo 0 === $syn_index ? ' open' : ''; ?>>
					<summary class="syn-faq__question">
						<span class="syn-faq__question-text"><?php echo esc_html( $syn_item['question'] ); ?></span>
						<span class="syn-faq__marker" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" focusable="false">
								<path d="M12 5v14" class="syn-faq__marker-bar" />
								<path d="M5 12h14" />
							</svg>
						</span>
					</summary>
					<div class="syn-faq__answer">
						<?php echo wp_kses_post( $syn_item['answer'] ); ?>
					</div>
				</details>
			<?php endforeach; ?>
		</div>

	</div>

	<?php
	if ( $syn_schema ) {
		$syn_entities = array();

		foreach ( $syn_clean as $syn_item ) {
			$syn_entities[] = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $syn_item['question'] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $syn_item['answer'] ),
				),
			);
		}

		/*
		 * wp_json_encode plus esc_html is deliberate belt and braces: the encode
		 * escapes the JSON, and escaping the result stops a "</script>" inside
		 * an answer from closing this element early. Stored meta is never
		 * trusted at render time (CLAUDE.md §5).
		 */
		printf(
			'<script type="application/ld+json">%s</script>',
			esc_html(
				wp_json_encode(
					array(
						'@context'   => 'https://schema.org',
						'@type'      => 'FAQPage',
						'mainEntity' => $syn_entities,
					),
					JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
				)
			)
		);
	}
	?>
</section>

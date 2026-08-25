<?php
/**
 * Section 01 — hero.
 *
 * Rendered through syn_section( 'hero', $args ). Styled by
 * assets/css/sections/hero.css, driven by assets/js/sections/hero.js.
 *
 * Expected $args (every one optional — the defaults are the approved copy):
 *   title_lead  string   Text before the highlighted phrase.
 *   title_focus string   The highlighted phrase, rendered in cyan on its own line.
 *   lead        string   The paragraph under the heading.
 *   motion_lead string   The static half of the animated line.
 *   motion_words string[] Words the typewriter cycles. The first is the one
 *                        rendered server-side, so it is what shows with JS off.
 *   image_id    int      Attachment ID for the background photograph.
 *   buttons     array[]  { label, url, style } — style is 'primary' or 'light'.
 *
 * Example:
 *   syn_section( 'hero', array( 'title_focus' => 'Power Your Business' ) );
 *
 * This section owns the homepage's one <h1> (CLAUDE.md §8). front-page.php must
 * not emit another, and does not include parts/page-header.php for that reason.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_title_lead   = $args['title_lead'] ?? __( 'BPO Services in UAE & the Gulf to', 'synergi' );
$syn_title_focus  = $args['title_focus'] ?? __( 'Power Your Business', 'synergi' );
$syn_lead         = $args['lead'] ?? __( 'Synergi runs and transforms non-core business functions through BPO, consulting, manpower augmentation, and technology-enabled shared services across the Gulf.', 'synergi' );
$syn_motion_lead  = $args['motion_lead'] ?? __( 'Helping your business remove', 'synergi' );
$syn_motion_words = $args['motion_words'] ?? array(
	__( 'manual work', 'synergi' ),
	__( 'silos', 'synergi' ),
	__( 'delays', 'synergi' ),
	__( 'unnecessary overhead', 'synergi' ),
);
$syn_image_id = $args['image_id'] ?? 0;
$syn_buttons  = $args['buttons'] ?? array(
	array(
		'label' => __( 'Explore Our Services', 'synergi' ),
		'url'   => '#services',
		'style' => 'primary',
	),
	array(
		'label' => __( 'Start a Conversation', 'synergi' ),
		'url'   => syn_contact_url(),
		'style' => 'light',
	),
);

$syn_motion_words = array_values( array_filter( array_map( 'trim', (array) $syn_motion_words ) ) );
?>
<section class="syn-hero" id="top" aria-labelledby="syn-hero-title">

	<?php
	if ( $syn_image_id ) {
		/*
		 * The hero photograph is the LCP element on the homepage, so it is
		 * declared high priority and never lazy-loaded (CLAUDE.md §6). sizes is
		 * 100vw because it always covers the full viewport width.
		 */
		echo wp_get_attachment_image(
			$syn_image_id,
			'full',
			false,
			array(
				'class'         => 'syn-hero__media',
				'fetchpriority' => 'high',
				'loading'       => 'eager',
				'decoding'      => 'async',
				'sizes'         => '100vw',
			)
		);
	}
	?>

	<span class="syn-hero__shade" aria-hidden="true"></span>

	<div class="syn-hero__layout syn-container">
		<div class="syn-hero__copy">

			<h1 class="syn-hero__title" id="syn-hero-title">
				<?php echo esc_html( $syn_title_lead ); ?>
				<span><?php echo esc_html( $syn_title_focus ); ?></span>
			</h1>

			<p class="syn-hero__lead"><?php echo esc_html( $syn_lead ); ?></p>

			<?php if ( $syn_motion_words ) : ?>
				<p class="syn-hero__motion">
					<?php
					/*
					 * The whole sentence is announced once, in full, from the
					 * visually hidden copy. The animated half is aria-hidden, or
					 * a screen reader would read a word changing every second.
					 */
					?>
					<span class="syn-visually-hidden">
						<?php
						printf(
							/* translators: 1: static lead-in, 2: comma-separated list of words. */
							esc_html__( '%1$s %2$s.', 'synergi' ),
							esc_html( $syn_motion_lead ),
							esc_html( implode( ', ', $syn_motion_words ) )
						);
						?>
					</span>

					<span class="syn-hero__motion-visual" aria-hidden="true">
						<span><?php echo esc_html( $syn_motion_lead ); ?></span>
						<span class="syn-hero__motion-slot">
							<?php
							/*
							 * The first word is printed server-side, so with
							 * JavaScript off the line still reads as a finished
							 * sentence rather than an empty gap.
							 */
							?>
							<span class="syn-hero__typeword" data-syn-typewords="<?php echo esc_attr( implode( ',', $syn_motion_words ) ); ?>"><?php echo esc_html( $syn_motion_words[0] ); ?></span>
							<span class="syn-hero__caret" data-syn-caret></span>
						</span>
					</span>
				</p>
			<?php endif; ?>

			<?php if ( $syn_buttons ) : ?>
				<div class="syn-button-row">
					<?php
					foreach ( $syn_buttons as $syn_button ) :
						if ( empty( $syn_button['label'] ) || empty( $syn_button['url'] ) ) {
							continue;
						}

						/*
						 * Both class names are written out in full rather than
						 * built by appending a modifier to a stem. CLAUDE.md §13:
						 * every class in a template must be findable verbatim, or
						 * searching the codebase for "syn-button--light" turns up
						 * the stylesheet and nothing that uses it.
						 */
						$syn_button_class = 'light' === ( $syn_button['style'] ?? '' )
							? 'syn-button syn-button--light'
							: 'syn-button syn-button--primary';
						?>
						<a class="<?php echo esc_attr( $syn_button_class ); ?>" href="<?php echo esc_url( $syn_button['url'] ); ?>"><?php echo esc_html( $syn_button['label'] ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>
	</div>

</section>

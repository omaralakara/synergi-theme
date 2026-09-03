<?php
/**
 * Keyword ribbon - the moving band that breaks the page between Industries
 * and Why Synergi.
 *
 * Rendered through syn_section( 'ribbon', $args ). Styled by
 * assets/css/sections/ribbon.css. Deliberately scriptless: the movement is
 * one CSS animation, so the band renders and moves identically with
 * JavaScript off, and base.css's global reduced-motion rule freezes it with
 * the words still visible (CLAUDE.md par.6).
 *
 * Two shallow diagonal bands crossing over ink. The front band is a cyan
 * strip carrying the keywords in ink - ink on cyan measures 6.6:1, where
 * white on the brand gradient's cyan end is 2.6:1 and fails par.9, which is
 * why the bright-band-with-light-words look of the reference could not be
 * copied literally. The back band is the brand gradient, dimmed, with no
 * text.
 *
 * Expected $args (all optional - the defaults are the approved list):
 *   words string[] Short phrases, in strip order.
 *
 * Example:
 *   syn_section( 'ribbon', array( 'words' => array( 'HR Outsourcing' ) ) );
 *
 * ONE COPY OF THE LIST IS REAL. A seamless loop needs the words printed
 * several times, so every printing after the first is aria-hidden:
 * assistive tech and search engines are told each keyword appears once,
 * which is what keeps this a short list of what the business does rather
 * than repeated text.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_words = array();

foreach ( (array) ( $args['words'] ?? array() ) as $syn_word ) {
	$syn_word = trim( (string) $syn_word );

	if ( '' !== $syn_word ) {
		$syn_words[] = $syn_word;
	}
}

if ( ! $syn_words ) {
	$syn_words = array(
		__( 'Business Process Outsourcing', 'synergi' ),
		__( 'HR Outsourcing', 'synergi' ),
		__( 'Accounting Services', 'synergi' ),
		__( 'Technology & AI', 'synergi' ),
		__( 'Procurement', 'synergi' ),
		__( 'Marketing', 'synergi' ),
		__( 'Project Management', 'synergi' ),
		__( 'Shared Services', 'synergi' ),
	);
}

/*
 * The animation slides the row by exactly one track width, so a track
 * narrower than the screen would open a gap before the loop rejoins. A
 * short list is therefore printed enough times per track to keep the track
 * wide; ten homepage-sized phrases comfortably clear the widest container.
 */
$syn_repeats = max( 1, (int) ceil( 10 / count( $syn_words ) ) );

/*
 * A closure rather than a function on purpose: a named function declared in
 * a partial is a fatal redeclare the second time the partial renders, which
 * took the podcast page down on 28 Aug (see syn_youtube_id() in
 * inc/sections.php).
 */
$syn_print_track = static function ( $syn_first_is_real ) use ( $syn_words, $syn_repeats ) {
	?>
	<ul class="syn-ribbon__track">
		<?php for ( $syn_pass = 0; $syn_pass < $syn_repeats; $syn_pass++ ) : ?>
			<?php foreach ( $syn_words as $syn_word ) : ?>
				<li class="syn-ribbon__word"<?php echo ( $syn_first_is_real && 0 === $syn_pass ) ? '' : ' aria-hidden="true"'; ?>><?php echo esc_html( $syn_word ); ?></li>
			<?php endforeach; ?>
		<?php endfor; ?>
	</ul>
	<?php
};
?>
<section class="syn-ribbon" aria-label="<?php esc_attr_e( 'What Synergi delivers', 'synergi' ); ?>">

	<div class="syn-ribbon__band syn-ribbon__band--back" aria-hidden="true"></div>

	<div class="syn-ribbon__band syn-ribbon__band--front">
		<?php
		/*
		 * tabindex="0" is what makes the drift pausable from the keyboard.
		 * Nothing inside the strip is focusable, so without a stop here a
		 * keyboard user would have no way to halt something that never stops
		 * moving (WCAG 2.2.2) - the same reasoning as the partners marquee,
		 * done here in CSS alone because this strip is not draggable.
		 */
		?>
		<div class="syn-ribbon__viewport" tabindex="0" aria-label="<?php esc_attr_e( 'Synergi service keywords, drifting sideways. The movement pauses while this strip is hovered or focused.', 'synergi' ); ?>">
			<div class="syn-ribbon__loop">
				<?php $syn_print_track( true ); ?>
				<?php $syn_print_track( false ); ?>
			</div>
		</div>
	</div>
</section>

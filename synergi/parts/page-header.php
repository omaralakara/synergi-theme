<?php
/**
 * The title band at the top of a singular view.
 *
 * Included by page.php through get_template_part(); Stage 4's single.php and
 * archive.php reuse it, and Stage 6c's templates/service.php uses its extended
 * form as the service hero. Styled by assets/css/parts/page-header.css.
 *
 * Expected $args:
 *   title   string Optional. Heading text. Defaults to the current post's title.
 *   lede    string Optional. One line under the heading. Nothing renders if empty.
 *   eyebrow string Optional. Short label above the heading — a category, say.
 *   meta    string Optional. Short line below the heading — a date, say.
 *
 * Added at Stage 6c, all optional, all inert unless passed — a post or an
 * ordinary page renders exactly what it rendered before:
 *   image   int    Optional. Attachment ID. Its presence switches the band to
 *                  the two-column hero and widens the container.
 *   cta     array  Optional. url and label for the primary button.
 *   cta_alt array  Optional. url and label for a second, outline button.
 *   proof   array[] Optional. Figures beside the copy, each: value, label.
 *   accent  string Optional. A service slug. Sets data-service, which
 *                  page-header.css uses to pick one of the six gradients from
 *                  theme.json. It names a service, never a colour (CLAUDE.md §7c).
 *
 * Every text value is plain text and is escaped here. Nothing accepts markup, so
 * a caller can never inject an element into the band (CLAUDE.md §5).
 *
 * This band exists for a structural reason, not a decorative one: the site
 * header is position:fixed and transparent until scrolled, so page content that
 * started at the top of the viewport would slide underneath it and read white
 * on white. The band reserves that space and puts a dark surface behind the
 * header's white text. The homepage does not use it — its hero is designed to
 * sit under the header and does the same job itself.
 *
 * It owns the page's single <h1> (CLAUDE.md §8). No template that includes this
 * part may emit another one.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_title   = $args['title'] ?? get_the_title();
$syn_lede    = $args['lede'] ?? '';
$syn_eyebrow = $args['eyebrow'] ?? '';
$syn_meta    = $args['meta'] ?? '';

$syn_image   = (int) ( $args['image'] ?? 0 );
$syn_cta     = (array) ( $args['cta'] ?? array() );
$syn_cta_alt = (array) ( $args['cta_alt'] ?? array() );
$syn_accent  = sanitize_key( $args['accent'] ?? '' );

$syn_proof = array();

foreach ( (array) ( $args['proof'] ?? array() ) as $syn_row ) {
	if ( ! is_array( $syn_row ) ) {
		continue;
	}

	$syn_value = trim( (string) ( $syn_row['value'] ?? '' ) );
	$syn_label = trim( (string) ( $syn_row['label'] ?? '' ) );

	if ( '' !== $syn_value && '' !== $syn_label ) {
		$syn_proof[] = array(
			'value' => $syn_value,
			'label' => $syn_label,
		);
	}
}

/*
 * The photograph is what decides the shape. With one, the band becomes the
 * two-column service hero on the wide container; without one it stays the
 * narrow title band every other template has always rendered.
 */
$syn_is_hero = $syn_image > 0;

$syn_band_class      = $syn_is_hero ? 'syn-page-header syn-page-header--hero' : 'syn-page-header';
$syn_container_class = $syn_is_hero ? 'syn-container' : 'syn-container syn-container--narrow';

/**
 * A short helper's worth of markup, inline: a button renders only when it has
 * both an address and words to put on it. Half a link is not a link.
 */
$syn_button = static function ( $link, $class ) {
	$url   = trim( (string) ( $link['url'] ?? '' ) );
	$label = trim( (string) ( $link['label'] ?? '' ) );

	if ( '' === $url || '' === $label ) {
		return '';
	}

	return sprintf(
		'<a class="syn-button %s" href="%s">%s</a>',
		esc_attr( $class ),
		esc_url( $url ),
		esc_html( $label )
	);
};

$syn_buttons = $syn_button( $syn_cta, 'syn-button--primary' ) . $syn_button( $syn_cta_alt, 'syn-button--light' );
?>
<!-- syn-part: page-header -->
<div class="<?php echo esc_attr( $syn_band_class ); ?>"<?php echo $syn_accent ? ' data-service="' . esc_attr( $syn_accent ) . '"' : ''; ?>>
	<div class="<?php echo esc_attr( $syn_container_class ); ?> syn-page-header__inner">

		<div class="syn-page-header__copy">
			<?php if ( $syn_eyebrow ) : ?>
				<p class="syn-page-header__eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
			<?php endif; ?>

			<h1 class="syn-page-header__title"><?php echo esc_html( $syn_title ); ?></h1>

			<?php if ( $syn_lede ) : ?>
				<p class="syn-page-header__lede"><?php echo esc_html( $syn_lede ); ?></p>
			<?php endif; ?>

			<?php if ( $syn_meta ) : ?>
				<p class="syn-page-header__meta"><?php echo esc_html( $syn_meta ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $syn_buttons ) : ?>
				<p class="syn-page-header__actions">
					<?php
					// Built above from esc_url() and esc_html() parts only.
					echo wp_kses_post( $syn_buttons );
					?>
				</p>
			<?php endif; ?>

			<?php if ( $syn_proof ) : ?>
				<ul class="syn-page-header__proof">
					<?php foreach ( $syn_proof as $syn_figure ) : ?>
						<li class="syn-page-header__figure">
							<span class="syn-page-header__figure-value"><?php echo esc_html( $syn_figure['value'] ); ?></span>
							<span class="syn-page-header__figure-label"><?php echo esc_html( $syn_figure['label'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<?php if ( $syn_is_hero ) : ?>
			<div class="syn-page-header__media">
				<?php
				/*
				 * The LCP element on a service page, so it is eager and carries
				 * fetchpriority (CLAUDE.md §6). Through core, so srcset, sizes
				 * and the attachment's own alt text come free.
				 */
				echo wp_get_attachment_image(
					$syn_image,
					'large',
					false,
					array(
						'class'         => 'syn-page-header__image',
						'fetchpriority' => 'high',
						'decoding'      => 'async',
						'sizes'         => '(max-width: 62rem) 100vw, 42vw',
					)
				);
				?>
			</div>
		<?php endif; ?>

	</div>
</div>
<!-- /syn-part: page-header -->

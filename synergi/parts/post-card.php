<?php
/**
 * One post in a listing.
 *
 * Included by archive.php and search.php through get_template_part(), inside
 * the loop — it reads the current post rather than taking one as an argument.
 * Styled by assets/css/parts/post.css.
 *
 * Expected $args:
 *   heading_level int Optional. 2 or 3. Defaults to 2.
 *
 * The heading level is a parameter because the listing's own <h1> is the
 * archive title, so a card is an <h2> there — but a card nested under a section
 * heading needs to be an <h3> to keep the order unbroken (CLAUDE.md §8). The
 * value is forced into range rather than trusted, because it is interpolated
 * into a tag name.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_level = isset( $args['heading_level'] ) ? (int) $args['heading_level'] : 2;
$syn_level = ( 3 === $syn_level ) ? 3 : 2;
$syn_tag   = 'h' . $syn_level;
?>
<article <?php post_class( 'syn-card' ); ?> id="post-<?php the_ID(); ?>">

	<?php if ( has_post_thumbnail() ) : ?>
		<a class="syn-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php
			/*
			 * The card image is decorative here: the title link immediately
			 * after it says the same thing, so exposing both would make a
			 * screen reader announce every post twice. Hidden and taken out of
			 * the tab order rather than given duplicate alt text.
			 */
			the_post_thumbnail(
				'syn-card',
				array(
					'class'    => 'syn-card__image',
					'alt'      => '',
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => '(max-width: 47.99rem) 100vw, 22rem',
				)
			);
			?>
		</a>
	<?php endif; ?>

	<div class="syn-card__body">
		<?php
		$syn_terms = get_the_category();

		if ( $syn_terms ) :
			?>
			<p class="syn-card__eyebrow"><?php echo esc_html( $syn_terms[0]->name ); ?></p>
		<?php endif; ?>

		<<?php echo esc_html( $syn_tag ); ?> class="syn-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</<?php echo esc_html( $syn_tag ); ?>>

		<p class="syn-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26 ) ); ?></p>

		<p class="syn-card__meta">
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</p>
	</div>

</article>

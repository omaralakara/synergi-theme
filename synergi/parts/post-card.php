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
 * BUILT TO MATCH sections/case-studies.php, asked for 31 Aug. The two are the
 * same object — a picture, a label, a headline, a line of context and a way in
 * — and before this they were two different cards on one site. They are still
 * two files rather than one shared partial, because a case study reads its
 * facts from fields on a page and a post reads a category and a date from core;
 * merging them would mean a partial that takes eight arguments to serve two
 * callers. What they share is the shape, and the shape lives in the CSS.
 *
 * ONE LINK PER CARD. The headline's <a> is stretched over the whole card by
 * post.css, so a mouse can hit anywhere while a keyboard gets a single stop
 * announced with the post's own title. The picture is therefore not a link, and
 * the "Read the article" button is a <span> — a second <a> to the same place
 * would have a screen reader announce the post twice, once with a worse name
 * (CLAUDE.md §9).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_level = isset( $args['heading_level'] ) ? (int) $args['heading_level'] : 2;
$syn_level = ( 3 === $syn_level ) ? 3 : 2;
$syn_tag   = 'h' . $syn_level;

$syn_card_id = wp_unique_id( 'syn-card-' );
$syn_terms   = get_the_category();
?>
<article <?php post_class( 'syn-card' ); ?> id="post-<?php the_ID(); ?>" aria-labelledby="<?php echo esc_attr( $syn_card_id ); ?>">

	<?php if ( has_post_thumbnail() ) : ?>
		<div class="syn-card__media">
			<?php
			/*
			 * alt="" deliberately: the headline immediately below is the link
			 * and says the same thing, so alt text here would make a screen
			 * reader announce every post twice (CLAUDE.md §8).
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
		</div>
	<?php endif; ?>

	<div class="syn-card__body">

		<?php if ( $syn_terms ) : ?>
			<p class="syn-card__eyebrow"><?php echo esc_html( $syn_terms[0]->name ); ?></p>
		<?php endif; ?>

		<<?php echo esc_html( $syn_tag ); ?> class="syn-card__title" id="<?php echo esc_attr( $syn_card_id ); ?>">
			<a class="syn-card__link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</<?php echo esc_html( $syn_tag ); ?>>

		<p class="syn-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26 ) ); ?></p>

		<p class="syn-card__meta">
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
		</p>

		<p class="syn-card__action">
			<span class="syn-card__button" aria-hidden="true">
				<?php esc_html_e( 'Read the article', 'synergi' ); ?>
				<span class="syn-card__arrow">&rarr;</span>
			</span>
		</p>

	</div>

</article>

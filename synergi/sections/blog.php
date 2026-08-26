<?php
/**
 * Section 09 — Read Our Blog.
 *
 * Rendered through syn_section( 'blog', $args ). Styled by
 * assets/css/sections/blog.css, driven by assets/js/sections/blog.js.
 *
 * The newest posts as cards, three across, paged by two arrows or by dragging.
 * This is the one section on the homepage whose content comes from the database
 * rather than from copy in a partial, so publishing an article changes the
 * homepage with no edit anywhere.
 *
 * Expected $args (all optional):
 *   eyebrow   string Small label above the heading.
 *   title     string The section's <h2>.
 *   link_url  string Where "View all articles" goes. Defaults to the Posts page
 *                    set in Settings → Reading, falling back to /blog/.
 *   link_text string Its label.
 *   count     int    How many posts to fetch. Five gives three on screen and
 *                    two in hand to page through.
 *   excerpt   int    Words to trim each excerpt to.
 *
 * Example:
 *   syn_section( 'blog', array( 'count' => 6 ) );
 *
 * Thumbnails are decorative here — the card's heading is the link and says the
 * same thing — so they carry alt="" rather than repeating the title to a screen
 * reader (CLAUDE.md §8).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow  = $args['eyebrow'] ?? __( 'Insights & analysis from our teams', 'synergi' );
$syn_title    = $args['title'] ?? __( 'Read Our Blog', 'synergi' );
$syn_count    = isset( $args['count'] ) ? absint( $args['count'] ) : 5;
$syn_words    = isset( $args['excerpt'] ) ? absint( $args['excerpt'] ) : 22;
$syn_link_text = $args['link_text'] ?? __( 'View all articles', 'synergi' );

/*
 * The Posts page if one is set, so the link follows the site's own settings
 * rather than assuming a slug. home_url() keeps it domain-free for the move to
 * synergibpo.com (CLAUDE.md §12).
 */
$syn_posts_page = (int) get_option( 'page_for_posts' );
$syn_link_url   = $args['link_url'] ?? ( $syn_posts_page ? get_permalink( $syn_posts_page ) : home_url( '/blog/' ) );

/*
 * A secondary query, so it must not disturb the main one. WP_Query with
 * no_found_rows skips the SQL_CALC_FOUND_ROWS count, which this section has no
 * use for — there is no pagination, only the newest few.
 */
$syn_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, $syn_count ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( ! $syn_query->have_posts() ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section blog: no published posts, section omitted -->\n";
	}

	wp_reset_postdata();

	return;
}

$syn_uid = wp_unique_id( 'syn-blog-' );

/* translators: %s: the heading of the first article now on screen. */
$syn_status_template = __( 'Showing articles starting with %s.', 'synergi' );
?>
<section class="syn-blog syn-section" id="blog" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-blog__heading syn-reveal">
			<div class="syn-blog__heading-copy">
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
				<h2 class="syn-blog__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_title ); ?></h2>
			</div>

			<?php if ( $syn_link_url && $syn_link_text ) : ?>
				<a class="syn-button syn-button--outline syn-blog__all" href="<?php echo esc_url( $syn_link_url ); ?>">
					<?php echo esc_html( $syn_link_text ); ?>
					<span aria-hidden="true">&rarr;</span>
				</a>
			<?php endif; ?>
		</div>

		<div
			class="syn-blog__carousel syn-reveal"
			data-syn-blog-carousel
			role="region"
			aria-roledescription="<?php esc_attr_e( 'carousel', 'synergi' ); ?>"
			aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title"
		>
			<button class="syn-blog__control syn-blog__control--prev" type="button" data-syn-blog-prev aria-label="<?php esc_attr_e( 'Show previous articles', 'synergi' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m14.5 6-6 6 6 6" /></svg>
			</button>

			<div class="syn-blog__viewport" data-syn-blog-viewport>
				<ul class="syn-blog__track" data-syn-blog-track>
					<?php
					while ( $syn_query->have_posts() ) :
						$syn_query->the_post();
						$syn_thumb_id = get_post_thumbnail_id();
						?>
						<li class="syn-blog__card">
							<div class="syn-blog__media">
								<?php
								if ( $syn_thumb_id ) {
									/*
									 * Three across inside an 82rem container is
									 * about 26rem a card, one across on a phone.
									 * Saying so stops the browser fetching for a
									 * full-width image it will never draw
									 * (CLAUDE.md §6).
									 */
									echo wp_get_attachment_image(
										$syn_thumb_id,
										'medium_large',
										false,
										array(
											'class'    => 'syn-blog__thumb',
											'alt'      => '',
											'loading'  => 'lazy',
											'decoding' => 'async',
											'sizes'    => '(max-width: 47.99rem) 92vw, (max-width: 61.25rem) 45vw, 26rem',
										)
									);
								}
								?>
							</div>

							<div class="syn-blog__body">
								<p class="syn-blog__meta">
									<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j M Y' ) ); ?></time>
								</p>

								<?php
								/*
								 * h3, because the section's h2 is above it and the
								 * page's one h1 belongs to the hero (CLAUDE.md §8).
								 * The link inside it is stretched over the whole
								 * card by blog.css, so the card is clickable
								 * without wrapping everything in an anchor.
								 */
								?>
								<h3 class="syn-blog__card-title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</h3>

								<?php
								$syn_excerpt = wp_trim_words( get_the_excerpt(), max( 1, $syn_words ), '&hellip;' );

								if ( $syn_excerpt ) :
									?>
									<p class="syn-blog__excerpt"><?php echo esc_html( $syn_excerpt ); ?></p>
								<?php endif; ?>

								<span class="syn-blog__more" aria-hidden="true"><?php esc_html_e( 'Read more', 'synergi' ); ?> &rarr;</span>
							</div>
						</li>
					<?php endwhile; ?>
				</ul>
			</div>

			<button class="syn-blog__control syn-blog__control--next" type="button" data-syn-blog-next aria-label="<?php esc_attr_e( 'Show next articles', 'synergi' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9.5 6 6 6-6 6" /></svg>
			</button>

			<p
				class="syn-visually-hidden"
				data-syn-blog-status="<?php echo esc_attr( $syn_status_template ); ?>"
				aria-live="polite"
				aria-atomic="true"
			></p>
		</div>

	</div>
</section>
<?php
// The loop above moved the global post; put it back for whatever renders next.
wp_reset_postdata();

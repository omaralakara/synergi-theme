<?php
/**
 * Section 10 — Recent From Instagram.
 *
 * Rendered through syn_section( 'instagram', $args ). Styled by
 * assets/css/sections/instagram.css. No script of its own.
 *
 * A heading, a follow button, and the newest posts as a carousel — three across,
 * paged by the same arrows and the same drag as the blog and case-study bands.
 *
 * WHERE THE POSTS COME FROM (3 Sep). inc/instagram.php reads the feed plugin's
 * own cache, so the theme draws the cards and the plugin keeps doing the part
 * that is genuinely its job — talking to Instagram and downloading each picture
 * locally as WebP. That file explains the trade at length. The band therefore
 * makes no external request, and neither the plugin's stylesheet nor its script
 * is needed on the page.
 *
 * The shortcode is still here as the fallback, and renders unchanged whenever
 * the cache read comes back empty — a plugin update that moves the table, or a
 * feed that has not been connected yet.
 *
 * Expected $args (all optional — the defaults are the approved copy):
 *   eyebrow   string Small label above the heading.
 *   title     string The section's <h2>.
 *   link_url  string The profile to follow.
 *   link_text string The button's label.
 *   count     int    How many posts to fetch. Nine gives three on screen and
 *                    two further pages behind them.
 *   shortcode string The feed plugin's shortcode, used only as the fallback.
 *
 * Example:
 *   syn_section( 'instagram', array( 'title' => 'Life at Synergi' ) );
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_eyebrow   = $args['eyebrow'] ?? __( 'Life at Synergi', 'synergi' );
$syn_title     = $args['title'] ?? __( 'Recent From Instagram', 'synergi' );
$syn_link_url  = $args['link_url'] ?? 'https://www.instagram.com/synergi.bpo';
$syn_link_text = $args['link_text'] ?? __( 'Follow Synergi', 'synergi' );
$syn_shortcode = $args['shortcode'] ?? '[instagram-feed feed=1]';
$syn_count     = isset( $args['count'] ) ? absint( $args['count'] ) : 9;

$syn_posts = function_exists( 'syn_instagram_posts' ) ? syn_instagram_posts( $syn_count ) : array();

/*
 * The fallback, and only when there is nothing to draw. The tag is pulled out
 * of the shortcode so its registration can be checked before anything is
 * rendered: a missing plugin then costs the reader an empty section, not a line
 * of raw shortcode text (CLAUDE.md §13: fail gracefully in production, loudly
 * in development).
 */
$syn_feed = '';

if ( ! $syn_posts && $syn_shortcode && preg_match( '/^\[\s*([a-z0-9_-]+)/i', $syn_shortcode, $syn_tag ) ) {
	if ( shortcode_exists( $syn_tag[1] ) ) {
		$syn_feed = do_shortcode( $syn_shortcode );

		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-section instagram: the plugin cache read came back empty, so the shortcode is rendering instead. See inc/instagram.php. -->\n";
		}
	} elseif ( SYN_DEBUG ) {
		echo "\n<!-- syn-section instagram: no shortcode registered for \"" . esc_html( $syn_tag[1] ) . "\", feed omitted -->\n";
	}
}

$syn_uid = wp_unique_id( 'syn-instagram-' );
?>
<section class="syn-instagram syn-section" id="instagram" aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title">
	<div class="syn-container">

		<div class="syn-instagram__heading syn-reveal">
			<div class="syn-instagram__heading-copy">
				<p class="syn-eyebrow"><?php echo esc_html( $syn_eyebrow ); ?></p>
				<h2 class="syn-instagram__title" id="<?php echo esc_attr( $syn_uid ); ?>-title"><?php echo esc_html( $syn_title ); ?></h2>
			</div>

			<?php if ( $syn_link_url && $syn_link_text ) : ?>
				<?php
				/*
				 * target="_blank" needs rel="noopener" or the opened tab can
				 * reach back through window.opener. The label says where it
				 * goes, so a screen reader is not surprised by the new tab.
				 */
				?>
				<a
					class="syn-button syn-button--outline syn-instagram__follow"
					href="<?php echo esc_url( $syn_link_url ); ?>"
					target="_blank"
					rel="noopener"
				>
					<?php echo esc_html( $syn_link_text ); ?>
					<span aria-hidden="true">&rarr;</span>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $syn_posts ) : ?>
			<div
				class="syn-instagram__carousel syn-reveal"
				data-syn-instagram-carousel
				role="region"
				aria-roledescription="<?php esc_attr_e( 'carousel', 'synergi' ); ?>"
				aria-labelledby="<?php echo esc_attr( $syn_uid ); ?>-title"
			>
				<button class="syn-instagram__control syn-instagram__control--prev" type="button" data-syn-instagram-prev aria-label="<?php esc_attr_e( 'Show previous posts', 'synergi' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m14.5 6-6 6 6 6" /></svg>
				</button>

				<div class="syn-instagram__viewport" data-syn-instagram-viewport>
					<ul class="syn-instagram__track" data-syn-instagram-track>
						<?php foreach ( $syn_posts as $syn_post ) : ?>
							<li class="syn-instagram__item">
								<?php
								/*
								 * One link per card, wrapping the picture, so
								 * there is a single tab stop and it announces
								 * itself with the caption rather than with a
								 * bare URL. The picture is the content here, so
								 * it carries real alt text rather than the empty
								 * alt the blog thumbnails take (CLAUDE.md §8).
								 */
								?>
								<a class="syn-instagram__link" href="<?php echo esc_url( $syn_post['permalink'] ); ?>" target="_blank" rel="noopener">
									<span class="syn-instagram__media">
										<img
											class="syn-instagram__image"
											src="<?php echo esc_url( $syn_post['image'] ); ?>"
											alt="<?php echo esc_attr( $syn_post['alt'] ); ?>"
											width="640"
											height="800"
											loading="lazy"
											decoding="async"
										>
									</span>

									<?php
									/*
									 * The caption under the picture, as the band
									 * showed it before this became our own
									 * carousel. aria-hidden because it repeats
									 * the image's alt text word for word, and a
									 * screen reader meeting both would read the
									 * post twice (CLAUDE.md §8).
									 */
									?>
									<span class="syn-instagram__body">
										<span class="syn-instagram__caption" aria-hidden="true"><?php echo esc_html( $syn_post['alt'] ); ?></span>
									</span>
									<span class="syn-visually-hidden"><?php esc_html_e( 'View this post on Instagram', 'synergi' ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<button class="syn-instagram__control syn-instagram__control--next" type="button" data-syn-instagram-next aria-label="<?php esc_attr_e( 'Show more posts', 'synergi' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9.5 6 6 6-6 6" /></svg>
				</button>

				<?php
				/*
				 * The paging is silent to a screen reader without this: the
				 * track moves visually, but focus never leaves the buttons.
				 * instagram.js writes the leading post's caption here.
				 */
				?>
				<p
					class="syn-visually-hidden"
					data-syn-instagram-status="<?php echo esc_attr__( 'Showing posts starting with %s.', 'synergi' ); ?>"
					aria-live="polite"
					aria-atomic="true"
				></p>
			</div>
		<?php elseif ( $syn_feed ) : ?>
			<div class="syn-instagram__feed syn-reveal">
				<?php
				// Already run through the shortcode API, which escapes its own
				// output; wp_kses_post() here would strip the feed's markup.
				echo $syn_feed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		<?php endif; ?>

	</div>
</section>

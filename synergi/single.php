<?php
/**
 * Single blog post.
 *
 * Loaded by: WordPress template hierarchy, for post_type "post".
 * Depends on: header.php, footer.php, parts/page-header.php.
 * Styled by: assets/css/parts/page-header.css and assets/css/parts/post.css.
 *
 * This template owns the page's one and only <h1>, and it is the post title
 * (CLAUDE.md §8). That is the fix for the whole archive: under the old theme a
 * post's title rendered as an <h3> and no <h1> existed, which is why an ASE
 * code snippet ("SEO: single post title H3 to H1", #10379) has been rewriting
 * the markup in an output buffer. That snippet checks for an existing <h1>
 * before touching anything, so it goes inert the moment this file renders —
 * but it should be retired rather than left running.
 *
 * Heading order below the <h1> is content, not template. Seven posts open at
 * <h4> or <h3> and therefore skip a level; they are listed in the Stage 4
 * migration checklist and fixed in the editor, not here. A template that
 * rewrote author headings would be lying about what the database holds.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$syn_terms    = get_the_category();
	$syn_category = $syn_terms ? $syn_terms[0]->name : '';
	?>

	<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">

		<?php
		get_template_part(
			'parts/page-header',
			null,
			array(
				'eyebrow' => $syn_category,
				// get_the_date() honours the site's date format setting rather
				// than hard-coding one, so the blog matches the rest of wp-admin.
				'meta'    => get_the_date(),
			)
		);
		?>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="syn-post-media">
				<div class="syn-container syn-container--narrow">
					<?php
					/*
					 * The featured image is the LCP element on a post, so it is
					 * told so explicitly and never lazy-loaded — the two
					 * together are what stop it queueing behind the fold
					 * (CLAUDE.md §6). srcset and sizes come free from core.
					 */
					the_post_thumbnail(
						'post-thumbnail',
						array(
							'class'         => 'syn-post-media__image',
							'fetchpriority' => 'high',
							'loading'       => 'eager',
							'decoding'      => 'async',
							'sizes'         => '(max-width: 68rem) 100vw, 68rem',
						)
					);
					?>
				</div>
			</figure>
		<?php endif; ?>

		<div class="syn-container syn-container--narrow">
			<div class="syn-entry-content">
				<?php the_content(); ?>

				<?php
				wp_link_pages(
					array(
						'before' => '<nav class="syn-page-links" aria-label="' . esc_attr__( 'Page', 'synergi' ) . '">',
						'after'  => '</nav>',
					)
				);
				?>
			</div>

			<?php
			/*
			 * Previous and next by date, not by category: the blog is a single
			 * stream and category membership is uneven enough that scoping the
			 * links would dead-end several posts.
			 */
			$syn_prev = get_previous_post();
			$syn_next = get_next_post();

			if ( $syn_prev || $syn_next ) :
				?>
				<nav class="syn-post-nav" aria-label="<?php esc_attr_e( 'More posts', 'synergi' ); ?>">
					<?php if ( $syn_prev ) : ?>
						<a class="syn-post-nav__link" href="<?php echo esc_url( get_permalink( $syn_prev ) ); ?>">
							<span class="syn-post-nav__label"><?php esc_html_e( 'Previous', 'synergi' ); ?></span>
							<span class="syn-post-nav__title"><?php echo esc_html( get_the_title( $syn_prev ) ); ?></span>
						</a>
					<?php endif; ?>

					<?php if ( $syn_next ) : ?>
						<a class="syn-post-nav__link" href="<?php echo esc_url( get_permalink( $syn_next ) ); ?>">
							<span class="syn-post-nav__label"><?php esc_html_e( 'Next', 'synergi' ); ?></span>
							<span class="syn-post-nav__title"><?php echo esc_html( get_the_title( $syn_next ) ); ?></span>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		</div>

	</article>

	<?php
endwhile;

get_footer();

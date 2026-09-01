<?php
/**
 * One case study.
 *
 * One engagement, written up: what the situation was, what we did, and what
 * changed. Every study is a card on the Case studies listing and on its own
 * service line's page — both ask syn_case_studies() rather than keeping a list.
 *
 * Loaded by: the template hierarchy, for a single syn_case_study post. It was
 * templates/case-study.php with a "Template Name" header until 1 Sep 2026, when
 * the studies became a post type (inc/case-study-post-type.php) and the choice
 * of template stopped being something an editor could get wrong.
 * Depends on: header.php, footer.php, inc/sections.php, inc/fields.php,
 * inc/case-study-fields.php, parts/page-header.php, sections/*.php.
 *
 * THE STORY IS THE BLOCK EDITOR'S, and that is deliberate rather than lazy. A
 * case study is prose — paragraphs, a subheading or two, maybe a list and a
 * pull quote — and it is different prose every time. CLAUDE.md §1 puts exactly
 * that in the database: "the block editor is used only for writing content".
 * Fields carry the things a card has to read back out again (the service line,
 * the kind of client, the country) and the figures the outcome band counts;
 * everything a person just writes is written where writing is easy. That is why
 * this template has one field group for facts and one for numbers, and no field
 * called "paragraph two".
 *
 * Composed from bands that already exist, so there is no CSS and no JavaScript
 * belonging to this template (CLAUDE.md §4). The body uses the same
 * .syn-entry-content wrapper page.php uses, so a case study's prose is styled by
 * base.css exactly as any other written page is.
 *
 * parts/page-header.php owns this page's one <h1> (CLAUDE.md §8) and nothing
 * below emits another — the block editor's own headings inside the body start at
 * <h2>, which is the level the editor offers first. The title, the meta
 * description and the canonical are Yoast's; the theme emits none of them.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_id = get_the_ID();

/*
 * The facts line under the headline: the service line, the kind of client and
 * the country, in that order, and only the ones that have been filled in. Joined
 * here rather than in the hero so the band stays the plain title band every
 * other template gets — it takes a string, and this is that string.
 */
$syn_facts = array_filter(
	array(
		syn_service_name( syn_field( 'case_service', $syn_id ) ),
		trim( (string) syn_field( 'case_client', $syn_id ) ),
		trim( (string) syn_field( 'case_country', $syn_id ) ),
	),
	static function ( $fact ) {
		return '' !== $fact;
	}
);

/*
 * The two optional bands are worked out before anything is declared, so a study
 * with no figures and no siblings downloads neither stylesheet — which is what
 * CLAUDE.md §6's conditional loading is for, and what inc/sections.php's
 * declared-but-never-rendered warning would otherwise report on every early
 * case study.
 *
 * Three siblings, not all of them: this is a footer on a page someone is already
 * reading, and the listing page is one click away for the rest.
 */
$syn_figures = syn_field_rows( 'case_outcome_figures', $syn_id );
$syn_more    = function_exists( 'syn_case_studies' )
	? syn_case_studies(
		array(
			'count'   => 3,
			'exclude' => $syn_id,
		)
	)
	: array();

$syn_sections = array();

if ( $syn_figures ) {
	$syn_sections[] = 'numbers';
}

if ( $syn_more ) {
	$syn_sections[] = 'case-studies';
}

$syn_sections[] = 'final-cta';

syn_use_sections( $syn_sections );

get_header();

/*
 * The loop, because this template renders the_content() and the block editor's
 * output wants the post properly set up around it. The other Stage 6 templates
 * read fields by ID and have no need of it.
 */
while ( have_posts() ) :
	the_post();
	?>

	<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">

		<?php
		/*
		 * The page title is the <h1> — parts/page-header.php defaults to it, so
		 * the headline can never disagree with the browser tab, the menu or the
		 * card on the listing page.
		 *
		 * The photograph falls back to the Featured Image, so a case study given
		 * one in the sidebar already has its hero and its card picture without a
		 * second choice being made about the same image.
		 */
		$syn_image = syn_field_image_id( 'case_image', $syn_id );

		if ( ! $syn_image ) {
			$syn_image = (int) get_post_thumbnail_id( $syn_id );
		}

		get_template_part(
			'parts/page-header',
			null,
			array(
				'eyebrow' => syn_field( 'case_eyebrow', $syn_id ),
				'lede'    => syn_field( 'case_lede', $syn_id ),
				'meta'    => implode( ' · ', $syn_facts ),
				'image'   => $syn_image,
			)
		);
		?>

		<?php
		/*
		 * No <main> here: header.php opens <main id="main-content"> and
		 * footer.php closes it (CLAUDE.md §8, one <main>).
		 */
		?>
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
		</div>

	</article>

	<?php
endwhile;

/*
 * The outcome, on the homepage's counting band. Its figures are this page's own,
 * not the "figures" site record: the record holds facts about the business that
 * must read identically everywhere, and what one engagement achieved is a fact
 * about one engagement (CLAUDE.md §7a — if it changed, exactly one page should
 * change with it).
 */
if ( $syn_figures ) {
	syn_section(
		'numbers',
		array(
			'eyebrow' => __( 'The result', 'synergi' ),
			'title'   => syn_field( 'case_outcome_heading', $syn_id ),
			'lead'    => syn_field( 'case_outcome_lede', $syn_id ),
			'stats'   => $syn_figures,
		)
	);
}

/*
 * The other case studies. The cards were already fetched above to decide whether
 * this band exists at all, so they are handed over rather than fetched twice.
 */
if ( $syn_more ) {
	syn_section(
		'case-studies',
		array(
			'eyebrow' => syn_field( 'case_more_eyebrow', $syn_id ),
			'heading' => syn_field( 'case_more_heading', $syn_id ),
			'items'   => $syn_more,
		)
	);
}

syn_section( 'final-cta' );

get_footer();

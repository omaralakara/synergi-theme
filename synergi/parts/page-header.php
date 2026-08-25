<?php
/**
 * The title band at the top of a singular view.
 *
 * Included by page.php through get_template_part(); Stage 4's single.php and
 * archive.php reuse it. Styled by assets/css/parts/page-header.css.
 *
 * Expected $args:
 *   title string Optional. Heading text. Defaults to the current post's title.
 *   lede  string Optional. One line under the heading. Nothing renders if empty.
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

$syn_title = $args['title'] ?? get_the_title();
$syn_lede  = $args['lede'] ?? '';
?>
<!-- syn-part: page-header -->
<div class="syn-page-header">
	<div class="syn-container syn-container--narrow">
		<h1 class="syn-page-header__title"><?php echo esc_html( $syn_title ); ?></h1>

		<?php if ( $syn_lede ) : ?>
			<p class="syn-page-header__lede"><?php echo esc_html( $syn_lede ); ?></p>
		<?php endif; ?>
	</div>
</div>
<!-- /syn-part: page-header -->

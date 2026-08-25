<?php
/**
 * The Synergi wordmark, linked home.
 *
 * Included by header.php and footer.php through get_template_part(). Styled by
 * assets/css/parts/header.css (.syn-brand) — footer.css only adjusts its size.
 *
 * Expects no $args. Both places it appears sit on a dark background, so the one
 * white logo set in Appearance → Customize serves both and there is no light
 * variant to choose between.
 *
 * Falls back to the site name as text when no logo is set, which keeps the
 * header usable on a fresh install and keeps the site's name in the markup for
 * screen readers either way.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_logo_id = get_theme_mod( 'custom_logo' );
?>
<a class="syn-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
	<?php
	$syn_logo = $syn_logo_id ? wp_get_attachment_image_src( $syn_logo_id, 'medium' ) : false;

	if ( $syn_logo ) :
		?>
		<?php
		/*
		 * Written out rather than passed through the_custom_logo() or
		 * wp_get_attachment_image(), for two reasons.
		 *
		 * the_custom_logo() wraps its own <a> around the image, which would nest
		 * a link inside this one.
		 *
		 * wp_get_attachment_image() would attach a srcset spanning every
		 * registered size up to the 1536px original. The wordmark is chrome, not
		 * content: header.css caps it at 9rem and footer.css at 10rem, so the
		 * largest it is ever painted is 160px. Offering the browser a 1536px
		 * candidate for a 160px box invites it to download 21 KB for something
		 * that needs 4 — measured on staging, 25 Aug. The "medium" derivative is
		 * 300px wide, which is still just under 2x for the largest use.
		 *
		 * loading="eager" because the header logo is above the fold on every
		 * page and lazy-loading it costs a visible pop-in.
		 */
		?>
		<img
			class="syn-brand__logo"
			src="<?php echo esc_url( $syn_logo[0] ); ?>"
			width="<?php echo esc_attr( $syn_logo[1] ); ?>"
			height="<?php echo esc_attr( $syn_logo[2] ); ?>"
			alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
			loading="eager"
			decoding="async"
		>
	<?php else : ?>
		<span class="syn-brand__name"><?php bloginfo( 'name' ); ?></span>
	<?php endif; ?>
</a>

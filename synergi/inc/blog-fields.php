<?php
/**
 * The blog listing's own copy.
 *
 * Loaded by functions.php, after inc/fields.php. Read by archive.php.
 *
 * WHY THIS GROUP IS SCOPED BY PAGE ID AND NOT BY TEMPLATE. Every other field
 * group in the theme attaches to a template an editor chooses in the sidebar.
 * The blog cannot work that way: /blog/ is the Posts page, set in
 * Settings > Reading, and WordPress renders it through archive.php no matter
 * what the Template dropdown says. There is no template slug to key on, so the
 * group attaches to whichever page is currently assigned to Posts — which is
 * the page-ID fallback CLAUDE.md §7c allows for exactly this case.
 *
 * The consequence worth knowing: change the Posts page in Settings > Reading
 * and these fields follow it to the new page, leaving the old one's values in
 * the database untouched. That is the right behaviour — nothing is lost if the
 * setting is changed back — but it does mean the box moves.
 *
 * Before this the blog was the one landing page whose words a developer owned:
 * its heading came from the page title, its sentence from the excerpt, and its
 * eyebrow did not exist. Asked for 31 Aug: the blog gets the same editable hero
 * every other page has.
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

/**
 * The page currently assigned to Posts in Settings → Reading.
 *
 * @return int Page ID, or 0 when the site has no Posts page.
 */
function syn_blog_page_id() {
	return (int) get_option( 'page_for_posts' );
}

add_action( 'syn_register_fields', 'syn_register_blog_fields' );
/**
 * Registers the one field group the blog listing carries.
 *
 * Side effects: registers one field group, on the Posts page only.
 *
 * @return void
 */
function syn_register_blog_fields() {

	$blog_id = syn_blog_page_id();

	/*
	 * Nothing is registered when no Posts page is set, and that guard is not
	 * defensive padding. A group with an empty "post_ids" and no "templates"
	 * counts as unscoped in syn_field_groups_for_post(), so it would appear on
	 * every page on the site — the opposite of what this file is for.
	 */
	if ( ! $blog_id ) {
		if ( SYN_DEBUG ) {
			syn_field_log( 'blog fields: no Posts page is set in Settings > Reading, so the blog hero group was not registered' );
		}

		return;
	}

	syn_register_field_group(
		array(
			'id'          => 'blog_hero',
			'title'       => __( 'Blog — the band at the top', 'synergi' ),
			'description' => __( 'The hero on the blog listing. The page title is the heading, so it is not repeated here. These fields also cover the category and tag archives, which have no page of their own to be edited on.', 'synergi' ),
			'post_ids'    => array( $blog_id ),
			'fields'      => array(
				array(
					'key'         => 'blog_eyebrow',
					'type'        => 'text',
					'label'       => __( 'Eyebrow', 'synergi' ),
					'description' => __( 'The small label above the heading.', 'synergi' ),
					'default'     => __( 'Insights', 'synergi' ),
					'max_length'  => 40,
				),
				array(
					'key'         => 'blog_lede',
					'type'        => 'textarea',
					'label'       => __( 'Opening sentence', 'synergi' ),
					'description' => __( 'One or two sentences under the heading. Leave it empty and the page falls back to this page’s excerpt, which is where the sentence used to come from.', 'synergi' ),
					'default'     => __( 'Analysis and practical guidance from the teams who run these functions every day.', 'synergi' ),
					'rows'        => 3,
					'max_length'  => 320,
				),
				array(
					'key'         => 'blog_image',
					'type'        => 'image',
					'label'       => __( 'Hero photograph', 'synergi' ),
					'description' => __( 'Optional. Fills the band behind the heading. Without one the page falls back to this page’s Featured Image, and without that the band stays on the flat navy. Choose one with meaningful alt text already set on it.', 'synergi' ),
				),
			),
		)
	);
}

/**
 * The blog hero's copy, with every fallback already applied.
 *
 * archive.php serves four different views — the posts page, and the category,
 * tag and date archives — and only the first has a page to carry fields. The
 * rest borrow the Posts page's eyebrow and picture, because an archive with a
 * bare navy band beside a photographic blog looks like a page that was missed
 * rather than a deliberate difference. Their heading and description stay their
 * own: those are what the reader filtered by.
 *
 * @param bool $is_posts_page Whether this is the posts page itself, as opposed
 *                            to a category, tag or date archive.
 * @return array eyebrow, lede and image, ready to hand to parts/page-header.php.
 */
function syn_blog_hero( $is_posts_page ) {
	$blog_id = syn_blog_page_id();

	if ( ! $blog_id || ! function_exists( 'syn_field' ) ) {
		return array(
			'eyebrow' => '',
			'lede'    => '',
			'image'   => 0,
		);
	}

	$image = syn_field_image_id( 'blog_image', $blog_id );

	// The Featured Image is where the picture came from before there was a
	// field, so it stays the fallback and nothing changes for a site that has
	// already set one (28 Aug).
	if ( ! $image ) {
		$image = (int) get_post_thumbnail_id( $blog_id );
	}

	$lede = '';

	if ( $is_posts_page ) {
		$lede = trim( (string) syn_field( 'blog_lede', $blog_id ) );

		if ( '' === $lede && has_excerpt( $blog_id ) ) {
			$lede = wp_strip_all_tags( get_the_excerpt( $blog_id ) );
		}
	}

	return array(
		'eyebrow' => syn_field( 'blog_eyebrow', $blog_id ),
		'lede'    => $lede,
		'image'   => $image,
	);
}

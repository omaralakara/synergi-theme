<?php
/**
 * People section — the board, the advisors, the team.
 *
 * Rendered through syn_section( 'people', $args ). Styled by
 * assets/css/sections/people.css. No script.
 *
 * Expected $args:
 *   people   array[] Everyone on the page, in order, each:
 *                      group    string Optional heading this person sits under.
 *                      name     string The person's name. A row without one is
 *                                       skipped.
 *                      role     string Their title.
 *                      image    int    Optional attachment ID.
 *                      linkedin string Optional profile address.
 *                      bio      string Optional. A card with one is wider.
 *   fallback string  Heading for people whose group is empty. It is rendered
 *                    for assistive technology only, so an ungrouped list looks
 *                    like a plain grid but still has an unbroken outline.
 *
 * Example:
 *   syn_section( 'people', array(
 *       'people' => array(
 *           array( 'group' => 'Board of Directors', 'name' => 'A. Person', 'role' => 'Chair' ),
 *       ),
 *   ) );
 *
 * HOW GROUPING WORKS. There is one flat list of people and each carries the
 * name of the group it belongs to, because repeaters do not nest (CLAUDE.md
 * §7c). Rows are collected under the first appearance of their group name, so
 * a row typed out of order still lands in the right place rather than starting
 * a second heading with the same words.
 *
 * HEADING LEVELS. parts/page-header.php owns the page's <h1>. Every group is an
 * <h2> — a real one when it has a name, a visually hidden one when it does not —
 * and every person is an <h3> underneath it. There is no case where a level is
 * skipped (CLAUDE.md §8).
 *
 * @package Synergi
 */

defined( 'ABSPATH' ) || exit;

$syn_fallback = trim( (string) ( $args['fallback'] ?? '' ) );

if ( '' === $syn_fallback ) {
	$syn_fallback = __( 'The team', 'synergi' );
}

/**
 * A person's initials, for the card that has no photograph.
 *
 * Two letters at most, from the first and last word of the name, so "Mohamad
 * Saker" reads MS and a single-word name reads as its own first letter.
 * mb_substr where the extension is present, substr where it is not — the same
 * guard syn_string_length() in inc/fields.php uses, and for the same reason: a
 * name is as likely to start with an Arabic letter as a Latin one.
 *
 * @param string $name Full name.
 * @return string One or two uppercase letters, or "" for an empty name.
 */
$syn_initials = static function ( $name ) {
	$words = preg_split( '/\s+/u', trim( $name ), -1, PREG_SPLIT_NO_EMPTY );

	if ( ! $words ) {
		return '';
	}

	$first = $words[0];
	$last  = count( $words ) > 1 ? $words[ count( $words ) - 1 ] : '';

	$cut = static function ( $word ) {
		if ( '' === $word ) {
			return '';
		}

		return function_exists( 'mb_substr' ) ? mb_substr( $word, 0, 1 ) : substr( $word, 0, 1 );
	};

	$letters = $cut( $first ) . $cut( $last );

	return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $letters ) : strtoupper( $letters );
};

/*
 * Collected before anything is echoed: a page whose every row is blank must
 * render no section at all, and there is no way back once the <section> is
 * open.
 */
$syn_groups = array();

foreach ( (array) ( $args['people'] ?? array() ) as $syn_index => $syn_row ) {
	if ( ! is_array( $syn_row ) ) {
		continue;
	}

	$syn_name = trim( (string) ( $syn_row['name'] ?? '' ) );

	if ( '' === $syn_name ) {
		if ( SYN_DEBUG ) {
			echo "\n<!-- syn-section people: row " . (int) $syn_index . " has no name, so it was skipped -->\n";
		}

		continue;
	}

	$syn_group = trim( (string) ( $syn_row['group'] ?? '' ) );

	if ( ! isset( $syn_groups[ $syn_group ] ) ) {
		$syn_groups[ $syn_group ] = array();
	}

	$syn_groups[ $syn_group ][] = array(
		'name'     => $syn_name,
		'role'     => trim( (string) ( $syn_row['role'] ?? '' ) ),
		'image'    => (int) ( $syn_row['image'] ?? 0 ),
		'linkedin' => trim( (string) ( $syn_row['linkedin'] ?? '' ) ),
		'bio'      => trim( (string) ( $syn_row['bio'] ?? '' ) ),
	);
}

if ( ! $syn_groups ) {
	if ( SYN_DEBUG ) {
		echo "\n<!-- syn-section people: nobody to render -->\n";
	}

	return;
}

$syn_uid = wp_unique_id( 'syn-people-' );
?>
<section class="syn-people syn-section" id="people">
	<div class="syn-container">
		<?php
		$syn_group_index = 0;

		foreach ( $syn_groups as $syn_group_title => $syn_members ) :
			$syn_group_id = $syn_uid . '-group-' . $syn_group_index;
			$syn_named    = '' !== $syn_group_title;

			/*
			 * The heading is always in the document; only its visibility
			 * changes. Both class strings are written out in full so either one
			 * can be found by searching for it (CLAUDE.md §13, the grep rule).
			 */
			$syn_title_class = $syn_named
				? 'syn-people__group-title'
				: 'syn-people__group-title syn-visually-hidden';
			?>
			<section class="syn-people__group" aria-labelledby="<?php echo esc_attr( $syn_group_id ); ?>">
				<h2 class="<?php echo esc_attr( $syn_title_class ); ?>" id="<?php echo esc_attr( $syn_group_id ); ?>">
					<?php echo esc_html( $syn_named ? $syn_group_title : $syn_fallback ); ?>
				</h2>

				<ul class="syn-people__grid">
					<?php
					foreach ( $syn_members as $syn_member_index => $syn_person ) :
						$syn_person_id = $syn_group_id . '-person-' . (int) $syn_member_index;

						/*
						 * A biography is several sentences, and several
						 * sentences in a portrait-sized card is a column two
						 * words wide. A card that has one therefore takes the
						 * full row and lays its photograph beside the words.
						 */
						$syn_card_class = '' !== $syn_person['bio']
							? 'syn-people__person syn-people__person--profile syn-reveal'
							: 'syn-people__person syn-reveal';
						?>
						<li class="<?php echo esc_attr( $syn_card_class ); ?>">
							<div class="syn-people__portrait">
								<?php
								if ( $syn_person['image'] ) {
									/*
									 * "medium_large" is the smallest core crop
									 * wider than the card is ever drawn, so the
									 * page never downloads a 2400px portrait to
									 * show it at 320 (CLAUDE.md §6).
									 */
									echo wp_get_attachment_image(
										$syn_person['image'],
										'medium_large',
										false,
										array(
											'class'    => 'syn-people__photo',
											'loading'  => 'lazy',
											'decoding' => 'async',
											'sizes'    => '(max-width: 47.99rem) 60vw, 22rem',
										)
									);
								} else {
									/*
									 * Decoration standing in for a photograph
									 * that does not exist. The name is already
									 * right underneath it, so reading the
									 * initials aloud would say it twice.
									 */
									?>
									<span class="syn-people__initials" aria-hidden="true"><?php echo esc_html( $syn_initials( $syn_person['name'] ) ); ?></span>
									<?php
								}
								?>
							</div>

							<div class="syn-people__copy">
								<h3 class="syn-people__name" id="<?php echo esc_attr( $syn_person_id ); ?>"><?php echo esc_html( $syn_person['name'] ); ?></h3>

								<?php if ( '' !== $syn_person['role'] ) : ?>
									<p class="syn-people__role"><?php echo esc_html( $syn_person['role'] ); ?></p>
								<?php endif; ?>

								<?php if ( '' !== $syn_person['bio'] ) : ?>
									<p class="syn-people__bio"><?php echo esc_html( $syn_person['bio'] ); ?></p>
								<?php endif; ?>

								<?php if ( '' !== $syn_person['linkedin'] ) : ?>
									<?php
									/*
									 * The link's accessible name carries the
									 * person, because "LinkedIn" repeated
									 * seventeen times is a useless list of links
									 * to anyone tabbing through the page.
									 */
									?>
									<a
										class="syn-people__link"
										href="<?php echo esc_url( $syn_person['linkedin'] ); ?>"
										rel="noopener"
										target="_blank"
									>
										<svg class="syn-people__link-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
											<path d="M6.94 5a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM7 8.48H3V21h4V8.48Zm6.32 0H9.34V21h3.94v-6.57c0-3.66 4.77-4 4.77 0V21H22v-7.93c0-6.17-7.06-5.94-8.72-2.91V8.48Z" />
										</svg>
										<span class="syn-visually-hidden">
											<?php
											printf(
												/* translators: %s: person's name. */
												esc_html__( 'LinkedIn profile — %s', 'synergi' ),
												esc_html( $syn_person['name'] )
											);
											?>
										</span>
										<span class="syn-people__link-text" aria-hidden="true"><?php esc_html_e( 'LinkedIn', 'synergi' ); ?></span>
									</a>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
			<?php
			++$syn_group_index;
		endforeach;
		?>
	</div>
</section>

/*
 * fields.js — behaviour for the hand-built page fields in wp-admin.
 * Contents: repeater (add · remove · move · renumber · announce) · image picker.
 *
 * Enqueued by inc/fields.php, only on a post edit screen that shows at least one
 * field box. Nothing here runs on the front end.
 *
 * Vanilla, no jQuery (CLAUDE.md §2.4). Every listener is delegated from the
 * document, because the block editor re-renders the whole meta-box area after a
 * save and anything bound to an element would be bound to a discarded one.
 *
 * What this file deliberately does NOT do: build markup out of strings. A blank
 * repeater row is rendered once by PHP into a <template> element, and cloning it
 * is all that happens here — so there is exactly one place a row's HTML is
 * written, which is what makes the grep rule in CLAUDE.md §13 hold.
 */
( function () {
	'use strict';

	var L = window.synFields || {};
	var frames = ( typeof WeakMap === 'function' ) ? new WeakMap() : null;

	/**
	 * Fills %1$s / %2$d style placeholders, the same order PHP wrote them in.
	 *
	 * @param {string} template String from window.synFields.
	 * @param {Array}  values   Replacements, in order.
	 * @return {string}
	 */
	function format( template, values ) {
		if ( ! template ) {
			return '';
		}

		return template.replace( /%(\d+)\$[sd]/g, function ( match, position ) {
			var value = values[ parseInt( position, 10 ) - 1 ];

			return ( typeof value === 'undefined' ) ? match : String( value );
		} ).replace( /%[sd]/g, function () {
			return String( values.shift() );
		} );
	}

	/**
	 * Says something once, into the repeater's own live region.
	 *
	 * Every add, remove and move is invisible to a screen reader otherwise: the
	 * list changes shape with no announcement and focus lands somewhere new with
	 * no explanation (CLAUDE.md §9).
	 *
	 * @param {Element} repeater The repeater container.
	 * @param {string}  message  What happened.
	 */
	function announce( repeater, message ) {
		var status = repeater.querySelector( '[data-syn-repeater-status]' );

		if ( status ) {
			status.textContent = message;
		}
	}

	/**
	 * The rows of one repeater, in document order.
	 *
	 * @param {Element} repeater The repeater container.
	 * @return {Element[]}
	 */
	function rowsOf( repeater ) {
		var list = repeater.querySelector( '[data-syn-repeater-rows]' );

		return list ? Array.prototype.slice.call( list.children ) : [];
	}

	/**
	 * Re-labels every row after the list changes shape.
	 *
	 * Only the human-facing numbers move. The input names keep whatever index
	 * they were born with, because PHP builds the posted array in the order the
	 * values arrive and that order is the DOM's — so moving a row is a DOM move
	 * and nothing else. Renaming inputs here would be work that buys nothing and
	 * a chance to collide two rows onto one index.
	 *
	 * @param {Element} repeater The repeater container.
	 */
	function refresh( repeater ) {
		var rows = rowsOf( repeater );
		var noun = repeater.getAttribute( 'data-row-noun' ) || L.untitled || '';
		var total = rows.length;

		rows.forEach( function ( row, index ) {
			var number = index + 1;
			var legend = row.querySelector( '[data-syn-repeater-legend]' );
			var badge = row.querySelector( '[data-syn-repeater-number]' );
			var up = row.querySelector( '[data-syn-repeater-up]' );
			var down = row.querySelector( '[data-syn-repeater-down]' );
			var remove = row.querySelector( '[data-syn-repeater-remove]' );

			if ( legend ) {
				legend.textContent = format( L.rowLegend, [ noun, number ] );
			}

			if ( badge ) {
				badge.textContent = String( number );
			}

			if ( up ) {
				up.setAttribute( 'aria-label', format( L.moveUp, [ noun, number ] ) );
				setDisabled( up, index === 0 );
			}

			if ( down ) {
				down.setAttribute( 'aria-label', format( L.moveDown, [ noun, number ] ) );
				setDisabled( down, index === total - 1 );
			}

			if ( remove ) {
				remove.setAttribute( 'aria-label', format( L.removeRow, [ noun, number ] ) );
			}

			syncTitle( row );
		} );

		var add = repeater.querySelector( '[data-syn-repeater-add]' );
		var max = parseInt( repeater.getAttribute( 'data-max-rows' ) || '0', 10 );

		if ( add ) {
			setDisabled( add, max > 0 && total >= max );
		}
	}

	/**
	 * Marks a button unavailable without taking it out of the tab order.
	 *
	 * A real disabled attribute would drop keyboard focus the moment a row
	 * reaches the top of the list, stranding the person who just moved it there.
	 * aria-disabled keeps the button focusable and says why nothing happened
	 * (CLAUDE.md §9).
	 *
	 * @param {Element} button The button.
	 * @param {boolean} off    Whether it should be unavailable.
	 */
	function setDisabled( button, off ) {
		button.setAttribute( 'aria-disabled', off ? 'true' : 'false' );
		button.classList.toggle( 'is-unavailable', !! off );
	}

	/**
	 * Copies a row's title field into its collapsed bar.
	 *
	 * @param {Element} row The row.
	 */
	function syncTitle( row ) {
		var target = row.querySelector( '[data-syn-repeater-title]' );
		var source = row.querySelector( '[data-syn-repeater-title-source]' );

		if ( ! target ) {
			return;
		}

		var value = source ? String( source.value || '' ).trim() : '';

		target.textContent = value;
		target.classList.toggle( 'is-empty', value === '' );
	}

	/**
	 * Adds one blank row, cloned from the repeater's <template>.
	 *
	 * @param {Element} repeater The repeater container.
	 */
	function addRow( repeater ) {
		var template = repeater.querySelector( '[data-syn-repeater-template]' );
		var list = repeater.querySelector( '[data-syn-repeater-rows]' );
		var max = parseInt( repeater.getAttribute( 'data-max-rows' ) || '0', 10 );
		var noun = repeater.getAttribute( 'data-row-noun' ) || '';

		if ( ! template || ! list ) {
			return;
		}

		if ( max > 0 && rowsOf( repeater ).length >= max ) {
			announce( repeater, format( L.maxRows, [ max ] ) );
			return;
		}

		var index = parseInt( repeater.getAttribute( 'data-next-index' ) || '0', 10 );

		repeater.setAttribute( 'data-next-index', String( index + 1 ) );

		// Index placeholders only ever appear in name and id attributes of a
		// blank row, so a split/join is safe here in a way it never is on
		// anything an editor typed.
		var markup = template.innerHTML.split( '__i__' ).join( String( index ) ).split( '__n__' ).join( String( rowsOf( repeater ).length + 1 ) );
		var holder = document.createElement( 'div' );

		holder.innerHTML = markup;

		var row = holder.firstElementChild;

		if ( ! row ) {
			return;
		}

		list.appendChild( row );
		refresh( repeater );
		announce( repeater, format( L.rowAdded, [ noun, rowsOf( repeater ).length ] ) );

		var first = row.querySelector( 'input:not([type="hidden"]), textarea, button' );

		if ( first ) {
			first.focus();
		}
	}

	/**
	 * Removes one row and puts focus somewhere sensible.
	 *
	 * @param {Element} repeater The repeater container.
	 * @param {Element} row      The row to remove.
	 */
	function removeRow( repeater, row ) {
		var rows = rowsOf( repeater );
		var position = rows.indexOf( row );
		var noun = repeater.getAttribute( 'data-row-noun' ) || '';

		row.parentNode.removeChild( row );

		var remaining = rowsOf( repeater );
		var next = remaining[ position ] || remaining[ position - 1 ] || null;
		var focus = next ? next.querySelector( '[data-syn-repeater-remove]' ) : repeater.querySelector( '[data-syn-repeater-add]' );

		refresh( repeater );
		announce( repeater, format( L.rowRemoved, [ noun, position + 1 ] ) );

		if ( focus ) {
			focus.focus();
		}
	}

	/**
	 * Moves one row up or down.
	 *
	 * @param {Element} repeater  The repeater container.
	 * @param {Element} row       The row to move.
	 * @param {number}  direction -1 for up, 1 for down.
	 * @param {Element} button    The button that was pressed, to keep focus on.
	 */
	function moveRow( repeater, row, direction, button ) {
		var rows = rowsOf( repeater );
		var from = rows.indexOf( row );
		var to = from + direction;
		var noun = repeater.getAttribute( 'data-row-noun' ) || '';

		if ( to < 0 || to >= rows.length ) {
			return;
		}

		if ( direction < 0 ) {
			rows[ to ].parentNode.insertBefore( row, rows[ to ] );
		} else {
			rows[ to ].parentNode.insertBefore( row, rows[ to ].nextSibling );
		}

		refresh( repeater );
		announce( repeater, format( L.rowMoved, [ noun, to + 1, rows.length ] ) );

		// Keep the person where they were. If the button they pressed has just
		// become unavailable — the row reached the top or the bottom — move to
		// the one that still does something rather than leaving focus on a dead
		// control.
		if ( button.getAttribute( 'aria-disabled' ) === 'true' ) {
			var sibling = row.querySelector( direction < 0 ? '[data-syn-repeater-down]' : '[data-syn-repeater-up]' );

			if ( sibling ) {
				sibling.focus();
				return;
			}
		}

		button.focus();
	}

	/* ----------------------------------------------------------------------
	   Image picker
	   ---------------------------------------------------------------------- */

	/**
	 * Opens the core media modal for one image field and stores what is chosen.
	 *
	 * @param {Element} field The [data-syn-image] container.
	 */
	function chooseImage( field ) {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}

		var frame = frames ? frames.get( field ) : field._synFrame;

		if ( ! frame ) {
			frame = window.wp.media( {
				title: L.chooseImage,
				button: { text: L.useImage },
				library: { type: 'image' },
				multiple: false
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();

				setImage( field, attachment );
			} );

			if ( frames ) {
				frames.set( field, frame );
			} else {
				field._synFrame = frame;
			}
		}

		frame.open();
	}

	/**
	 * Writes a chosen attachment into the field and paints its thumbnail.
	 *
	 * The preview is built with createElement rather than innerHTML: an
	 * attachment's alt text is editor input, and it is never allowed near an
	 * HTML parser (CLAUDE.md §5).
	 *
	 * @param {Element} field      The [data-syn-image] container.
	 * @param {Object}  attachment Attachment as JSON from the media modal.
	 */
	function setImage( field, attachment ) {
		var input = field.querySelector( '[data-syn-image-input]' );
		var preview = field.querySelector( '[data-syn-image-preview]' );
		var remove = field.querySelector( '[data-syn-image-remove]' );
		var thumb = ( attachment.sizes && attachment.sizes.thumbnail ) ? attachment.sizes.thumbnail : null;

		if ( input ) {
			input.value = String( attachment.id );
		}

		if ( preview ) {
			var img = document.createElement( 'img' );

			img.className = 'syn-image-field__thumb';
			img.src = thumb ? thumb.url : attachment.url;
			img.alt = attachment.alt || '';

			if ( thumb ) {
				img.width = thumb.width;
				img.height = thumb.height;
			}

			preview.innerHTML = '';
			preview.appendChild( img );
		}

		if ( remove ) {
			remove.hidden = false;
		}

		liveSay( field, L.imageSet );
	}

	/**
	 * Clears an image field back to empty.
	 *
	 * @param {Element} field The [data-syn-image] container.
	 */
	function clearImage( field ) {
		var input = field.querySelector( '[data-syn-image-input]' );
		var preview = field.querySelector( '[data-syn-image-preview]' );
		var remove = field.querySelector( '[data-syn-image-remove]' );
		var choose = field.querySelector( '[data-syn-image-choose]' );

		if ( input ) {
			input.value = '0';
		}

		if ( preview ) {
			var empty = document.createElement( 'span' );

			empty.className = 'syn-image-field__empty';
			empty.textContent = preview.getAttribute( 'data-empty-text' ) || '';
			preview.innerHTML = '';
			preview.appendChild( empty );
		}

		if ( remove ) {
			remove.hidden = true;
		}

		liveSay( field, L.imageCleared );

		if ( choose ) {
			choose.focus();
		}
	}

	/**
	 * Announces an image change through the nearest repeater's live region, or
	 * silently does nothing outside one.
	 *
	 * @param {Element} field   The [data-syn-image] container.
	 * @param {string}  message What happened.
	 */
	function liveSay( field, message ) {
		var repeater = field.closest( '[data-syn-repeater]' );

		if ( repeater ) {
			announce( repeater, message );
		}
	}

	/* ----------------------------------------------------------------------
	   Wiring
	   ---------------------------------------------------------------------- */

	document.addEventListener( 'click', function ( event ) {
		var target = event.target;

		if ( ! target || ! target.closest ) {
			return;
		}

		var button = target.closest( 'button' );

		if ( ! button ) {
			return;
		}

		var image = button.closest( '[data-syn-image]' );

		if ( image && button.hasAttribute( 'data-syn-image-choose' ) ) {
			event.preventDefault();
			chooseImage( image );
			return;
		}

		if ( image && button.hasAttribute( 'data-syn-image-remove' ) ) {
			event.preventDefault();
			clearImage( image );
			return;
		}

		var repeater = button.closest( '[data-syn-repeater]' );

		if ( ! repeater ) {
			return;
		}

		var row = button.closest( '[data-syn-repeater-row]' );

		if ( button.hasAttribute( 'data-syn-repeater-add' ) ) {
			event.preventDefault();

			if ( button.getAttribute( 'aria-disabled' ) !== 'true' ) {
				addRow( repeater );
			} else {
				announce( repeater, format( L.maxRows, [ repeater.getAttribute( 'data-max-rows' ) ] ) );
			}

			return;
		}

		if ( ! row ) {
			return;
		}

		if ( button.hasAttribute( 'data-syn-repeater-remove' ) ) {
			event.preventDefault();
			removeRow( repeater, row );
			return;
		}

		if ( button.hasAttribute( 'data-syn-repeater-up' ) ) {
			event.preventDefault();

			if ( button.getAttribute( 'aria-disabled' ) !== 'true' ) {
				moveRow( repeater, row, -1, button );
			}

			return;
		}

		if ( button.hasAttribute( 'data-syn-repeater-down' ) ) {
			event.preventDefault();

			if ( button.getAttribute( 'aria-disabled' ) !== 'true' ) {
				moveRow( repeater, row, 1, button );
			}
		}
	} );

	document.addEventListener( 'input', function ( event ) {
		var target = event.target;

		if ( target && target.hasAttribute && target.hasAttribute( 'data-syn-repeater-title-source' ) ) {
			var row = target.closest( '[data-syn-repeater-row]' );

			if ( row ) {
				syncTitle( row );
			}
		}
	} );

	/**
	 * Labels every repeater on the page.
	 */
	function refreshAll() {
		var repeaters = document.querySelectorAll( '[data-syn-repeater]' );

		Array.prototype.forEach.call( repeaters, refresh );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', refreshAll );
	} else {
		refreshAll();
	}

	/*
	 * The block editor posts the meta boxes separately from the post content and
	 * then replaces the whole meta-box area with the server's response. Every
	 * listener above is delegated so it survives that, but the row numbering and
	 * the unavailable-button state are painted by this script and come back
	 * unpainted. Watching for the replacement is what keeps them correct without
	 * anyone having to reload the screen.
	 */
	if ( typeof MutationObserver === 'function' ) {
		var pending = null;

		new MutationObserver( function ( records ) {
			for ( var i = 0; i < records.length; i++ ) {
				if ( records[ i ].addedNodes.length ) {
					window.clearTimeout( pending );
					pending = window.setTimeout( refreshAll, 50 );
					return;
				}
			}
		} ).observe( document.body, { childList: true, subtree: true } );
	}

	if ( L.debug && window.console ) {
		window.console.log( '[synergi] fields.js ready:', document.querySelectorAll( '[data-syn-repeater]' ).length, 'repeater(s)' );
	}
}() );

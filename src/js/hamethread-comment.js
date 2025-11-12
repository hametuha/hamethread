/*!
 * Comment helper
 *
 * @deps hamethread, wp-i18n
 */

/*global HameThread: true*/
/*global HameThreadComment: false*/

const $ = jQuery;

const updateCommentCount = function () {
	$( '.hamethread-comments' ).each( function ( index, comments ) {
		$( comments ).attr(
			'data-comment-count',
			$( comments ).find( '.hamethread-comment-item-wrapper' ).length
		);
	} );
};

// Initialize comment count.
$( document ).ready( function () {
	updateCommentCount();
} );

// Get comment form.
$( document ).on( 'click', 'button[data-hamethread="comment"]', function ( e ) {
	e.preventDefault();
	const $button = $( this );
	$button.addClass( 'disabled' ).attr( 'disabled', true );
	HameThread.request( 'GET', $button.attr( 'data-end-point' ) )
		.done( function ( response ) {
			$( 'body' ).append( response.html );
		} )
		.always( function () {
			$button.removeClass( 'disabled' ).attr( 'disabled', null );
		} );
} );

// Get reply form.
$( document ).on( 'click', '.hamethread-reply', function ( e ) {
	e.preventDefault();
	const $comment = $( this ).closest( '.hamethread-comment-item-wrapper' );
	$comment.addClass( 'loading' );
	HameThread.request( 'GET', $( this ).attr( 'data-path' ), {
		reply_to: $( this ).attr( 'data-reply-to' ),
	} )
		.done( function ( response ) {
			$( 'body' ).append( response.html );
		} )
		.always( function () {
			$comment.removeClass( 'loading' );
		} );
} );

// Get edit form.
$( document ).on( 'click', 'a[data-hamethread="comment-edit"]', function ( e ) {
	e.preventDefault();
	const $comment = $( this ).closest( '.hamethread-comment-item-wrapper' );
	$comment.addClass( 'loading' );
	HameThread.request( 'GET', $( this ).attr( 'href' ) )
		.done( function ( response ) {
			$( 'body' ).append( response.html );
		} )
		.always( function () {
			$comment.removeClass( 'loading' );
		} );
} );

// Voting
$( document ).on( 'click', '.hamethread-upvote', function ( e ) {
	e.preventDefault();
	const $button = $( this );
	if ( $button.hasClass( 'disabled' ) ) {
		return false;
	}
	const isActive = $( this ).hasClass( 'active' );
	const path = $( this ).attr( 'data-path' );
	const method = isActive ? 'DELETE' : 'POST';
	$button.attr( 'disabled', true ).addClass( 'disabled' );
	HameThread.request( method, path )
		.done( function () {
			// Do something.
			if ( isActive ) {
				$button.removeClass( 'active' );
			} else {
				$button.addClass( 'active' );
			}
		} )
		.always( function () {
			$button.attr( 'disabled', null ).removeClass( 'disabled' );
		} );
} );

// Post comment.
$( document ).on( 'submit', '#hamethread-comment', function ( e ) {
	e.preventDefault();
	const $form = $( this );
	const data = {};
	$form
		.find( 'input[name], select[name], textarea[name]' )
		.each( function ( index, input ) {
			if ( 'checkbox' === $( input ).attr( 'type' ) && ! input.checked ) {
				return true;
			}
			data[ $( input ).attr( 'name' ) ] = $( input ).val();
		} );
	$form.addClass( 'loading' );
	HameThread.request( 'POST', $form.attr( 'action' ), data )
		.done( function ( response ) {
			$form.trigger( 'commented', [ response ] );
			const $comment = $( response.html );
			// If comment exists, get it.
			const $oldComment = $( '#comment-' + response.id );
			if ( $oldComment.length ) {
				// This is existing comment, so replace it.
				const $children = $oldComment.children( 'ul.children' );
				if ( $children.length ) {
					// Mover children to new comment.
					$children.appendTo( $comment );
				}
				$oldComment.replaceWith( $comment );
			} else {
				// This is new comment.
				// If comment has children, set it as target.
				let $target = $( '.hamethread-comments' );
				if ( response.parent ) {
					const $parent = $( '#comment-' + response.parent );
					if ( $parent.length ) {
						// Parent found. check if
						let $children = $parent.find( 'ul.children' );
						if ( ! $children.length ) {
							$children = $( '<ul></ul>' ).addClass( 'children' );
							$parent.append( $children );
						}
						$target = $children;
					}
				}
				$target.append( $comment );
			}
			$comment.effect( 'highlight', {}, 3000 );
			$form.remove();
			updateCommentCount();
		} )
		.always( function () {
			$form.removeClass( 'loading' );
		} );
} );

// Best answer.
$( document ).on( 'click', '.hamethread-ba-toggle', function ( e ) {
	e.preventDefault();
	const $button = $( this );
	const method = $button.attr( 'data-method' );
	const message =
		'POST' === method
			? HameThreadComment.chooseBa
			: HameThreadComment.cancelBa;
	if ( ! window.confirm( message ) ) {
		return;
	}
	const $comment = $button.closest( '.hamethread-comment-item-wrapper' );
	const path = $button.attr( 'data-path' );
	$comment.addClass( 'loading' );
	HameThread.request( method, path )
		.done( function ( response ) {
			alert( response.message );
			window.location.href = response.url;
		} )
		.always( function () {
			$comment.removeClass( 'loading' );
		} );
} );

// Remove comment.
$( document ).on(
	'click',
	'a[data-hamethread="comment-delete"]',
	function ( e ) {
		e.preventDefault();
		if ( ! confirm( HameThreadComment.confirm ) ) {
			return false;
		}
		const $comment = $( this ).closest(
			'.hamethread-comment-item-wrapper'
		);
		$comment.addClass( 'loading' );
		HameThread.request( 'DELETE', $( this ).attr( 'href' ) )
			.done( function ( response ) {
				const $children = $comment.children( 'ul.children' );
				if ( $children.length ) {
					// Move children to after comments.
					$comment.after( $children.children( 'li' ) );
				}
				alert( response.message );
				HameThread.removeElement( $comment, function () {
					updateCommentCount();
				} );
			} )
			.always( function () {
				$comment.removeClass( 'loading' );
			} );
	}
);

// Update following status.
$( document ).ready( function () {
	const $button = $( '#hamthread-watchers-toggle' );
	if ( ! $button.length ) {
		// Do nothing.
		return true;
	}
	const setFollowStatus = function ( following ) {
		let html = '';
		if ( following ) {
			html =
				'<button class="btn btn-success btn-following"><span class="on"><i class="fa fa-check-circle"></i> ' +
				HameThreadComment.following +
				'</span><span class="hover">' +
				HameThreadComment.unfollow +
				'</span></button>';
		} else {
			html =
				'<button class="btn btn-default">' +
				HameThreadComment.follow +
				'</button>';
		}
		if ( HameThreadComment.buttonCallback ) {
			html = HameThreadComment.buttonCallback( html, following );
		}
		$button.html( html );
	};

	// Check current status.
	HameThread.request( 'GET', 'follower/in/' + $button.attr( 'data-id' ), {
		user_id: 'me',
	} ).done( function ( response ) {
		setFollowStatus( response.subscribing );
	} );

	// Toggle information.
	$button.on( 'click', 'button', function ( e ) {
		e.preventDefault();
		let method;
		if ( $( this ).hasClass( 'btn-following' ) ) {
			method = 'DELETE';
		} else {
			method = 'POST';
		}
		HameThread.request(
			method,
			'follower/in/' + $button.attr( 'data-id' ),
			{
				user_id: 'me',
			}
		).done( function ( response ) {
			setFollowStatus( response.subscribing );
		} );
	} );
} );

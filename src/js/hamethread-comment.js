/**
 * Description
 */

/*global HameThread: true*/
/*global HameThreadComment: false*/

(function ($) {

	'use strict';

	var updateCommentCount = function() {
		$( '.hamethread-comments' ).each( function( index, comments ) {
			$( comments ).attr( 'data-comment-count', $( comments ).find( '.hamethread-comment-item-wrapper' ).length );
		} );
	};

	// Get comment form.
	$(document).on('click', 'button[data-hamethread="comment"]', function (e) {
		e.preventDefault();
		var $button = $(this);
		$button.addClass('disabled').attr('disabled', true);
		HameThread.request('GET', $button.attr('data-end-point'))
			.done(function (response) {
				$('body').append(response.html);
			}).always(function () {
				$button.removeClass('disabled').attr('disabled', null);
			});
	});

	// Get reply form.
	$(document).on('click', '.hamethread-reply', function(e){
		e.preventDefault();
		var $comment = $(this).closest('.hamethread-comment-item-wrapper');
		$comment.addClass('loading');
		HameThread.request('GET', $(this).attr('data-path'), {
			reply_to: $(this).attr('data-reply-to')
		})
			.done(function (response) {
				$('body').append(response.html);
			}).always(function () {
			$comment.removeClass('loading');
		});
	});

	// Get edit form.
	$(document).on('click', 'a[data-hamethread="comment-edit"]', function(e){
		e.preventDefault();
		var $comment = $(this).closest('.hamethread-comment-item-wrapper');
		$comment.addClass('loading');
		HameThread.request('GET', $(this).attr('href'))
			.done(function(response){
				$('body').append( response.html );
			})
			.always(function(){
				$comment.removeClass('loading');
			});
	});

	// Voting
	$(document).on('click','.hamethread-upvote', function(e){
		e.preventDefault();
		var $button  = $(this);
		var isActive = $(this).hasClass('active');
		var path     = $(this).attr('data-path');
		var method   = isActive ? 'DELETE' : 'POST';
		if($button.hasClass('disabled')){
			return false;
		}
		$button.attr('disabled', true).addClass('disabled');
		HameThread.request(method, path).done(function(response){
			// Do something.
			if(isActive){
				$button.removeClass('active');
			}else{
        		$button.addClass('active');
			}
		}).always(function(){
			$button.attr('disabled', null).removeClass('disabled');
		});
	});

	// Post comment.
	$(document).on('submit', '#hamethread-comment', function (e) {
		e.preventDefault();
		var $form = $(this);
		var data = {};
		$form.find('input[name], select[name], textarea[name]').each(function (index, input) {
			data[$(input).attr('name')] = $(input).val();
		});
		$form.addClass('loading');
		HameThread.request('POST', $form.attr('action'), data)
			.done(function (response) {
				$form.trigger('commented', [response]);
				var $comment = $( response.html );
				// If comment exists, get it.
				var $oldComment = $( '#comment-' + response.id );
				if ( $oldComment.length ) {
					// This is existing comment, so replace it.
					var $children = $oldComment.children( 'ul.children' );
					if ( $children.length ) {
						// Mover children to new comment.
						$children.appendTo( $comment );
					}
					$oldComment.replaceWith( $comment );
				} else {
					// This is new comment.
					// If comment has children, set it as target.
					var $target = $( '.hamethread-comments' );
					if ( response.parent ) {
						var $parent = $( '#comment-' + response.parent );
						if ( $parent.length ) {
							// Parent found. check if
							var $children = $parent.find( 'ul.children' );
							if ( ! $children.length ) {
								$children = $('<ul></ul>').addClass('children');
								$parent.append($children);
							}
							$target = $children;
						}
					}
					$target.append( $comment );
				}
				$comment.effect('highlight', {}, 3000);
				$form.remove();
				updateCommentCount();
			}).always(function () {
				$form.removeClass('loading');
			});
	});


	// Remove comment.
	$(document).on('click', 'a[data-hamethread="comment-delete"]', function (e) {
		e.preventDefault();
		if (!confirm(HameThreadComment.confirm)) {
			return false;
		}
		var $comment = $(this).closest('.hamethread-comment-item-wrapper');
		$comment.addClass('loading');
		HameThread.request('DELETE', $(this).attr('href'))
			.done(function (response) {
				var $children = $comment.children('ul.children');
				if ( $children.length ) {
					// Move children to after comments.
					$comment.after( $children.children( 'li' ) );
				}
				alert(response.message);
				HameThread.removeElement( $comment, function() {
					updateCommentCount();
				} );
			}).always(function () {
				$comment.removeClass('loading');
			});
	});

})(jQuery);

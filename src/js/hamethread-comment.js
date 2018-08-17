/**
 * Description
 */

/*global HameThread: true*/
/*global HameThreadComment: false*/

(function ($) {

	'use strict';

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
			console.log(response);
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
				var $comment = $(response.html);
				var $target = $('.hamethread-comments');
				if(response.parent){
					var $parent = $('#comment-' + response.parent);
					if($parent.length){
						// Parent found. check fi
						var $children = $parent.find('ul.children');
						if(! $children.length){
							$children = $('<ul></ul>').addClass('children');
							$parent.append($children);
						}
						$target = $children;
					}
				}
				$target.append(response.html).effect('highlight', {}, 3000);
				$form.remove();
			}).always(function () {
				$form.removeClass('loading');
			});
	});

	// Edit comment.
	$(document).on('click', 'a[data-hamethread="comment-edit"]', function(e){
		e.preventDefault();
		var $comment = $(this).closest('.hamethread-comment-item-wrapper');
		$comment.addClass('loading');
		HameThread.request('GET', $(this).attr('href'))
			.done(function(response){
				$comment.replaceWith(response.html).effect('highlight', {}, 300);
			})
			.always(function(){
				$comment.removeClass('loading');
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
				alert(response.message);
				HameThread.removeElement($comment);
			}).always(function () {
				$comment.removeClass('loading');
			});
	});

})(jQuery);

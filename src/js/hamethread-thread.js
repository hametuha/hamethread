/**
 * Description
 */

/*global HameThread: true*/

(function ($) {

  'use strict';

	//
	// Call form button
	//
	$(document).on('click', 'button[data-hamethread="create"], a[data-hamethread="create"]', function(e) {
		e.preventDefault();
		var $button = $(this);
		if($button.attr('disabled')){
			return;
		}
		var query = {
			_wpnonce: HameThread.nonce
		};
		var post_id = $button.attr('data-post-id');
		if(post_id){
			query.post_id = post_id;
		}
		$button.addClass('disabled').attr('disabled', true);
		$.get(HameThread.endpoint + '/new', query).done(function(response){
			$('body').append(response.html);
		}).fail(HameThread.errorHandler).always(function(){
			$button.removeClass('disabled').attr('disabled', false);
		});
	});

	//
	// Click Post Action.
	//
	$(document).on('submit', '#hamethread-add', function(e){
		e.preventDefault();
		var $form = $(this);
		var data = {};
		$form.find('input[name], select[name], textarea[name]').each(function(index, input){
			data[$(input).attr('name')] = $(input).val();
		});
		data._wpnonce = HameThread.nonce;
		$form.addClass('loading');
		$.post($form.attr('action'), data).done(function(response){
			window.location.href = response.link;
		}).fail(HameThread.errorHandler).always(function(){
			$form.removeClass('loading');
		});
	});

	// Form cancel
	$(document).on('click', '.hamethread-form-cancel', function(e){
		e.preventDefault();
		$(this).parents('.hamethread-form').remove();
	});

	// Archive thread.
	$(document).on('click', 'a[data-hamethread="archive"]', function(e){
		e.preventDefault();
		if(!window.confirm(HameThread.archive)){
			return false;
		}
		var $button = $(this);
		if($button.attr('disabled')){
			return;
		}
		var query = {
			_wpnonce: HameThread.nonce
		};
		$button.addClass('disabled').attr('disabled', true);
		$.ajax({
			url: $button.attr('href') + '?' + $.param({
				_wpnonce: HameThread.nonce
			}),
			method: 'DELETE'
		}).done(function (response) {
			alert(response.message);
			window.location.href = response.url;
		}).fail(HameThread.errorHandler).always(function () {
			$button.removeClass('disabled').attr('disabled', false);
		});
	});


})(jQuery);

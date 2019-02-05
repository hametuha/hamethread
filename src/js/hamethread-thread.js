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
		var parent = $button.attr('data-parent');
		if(parent){
			query.parent = parent;
		}
		var allowPrivate = $button.attr( 'data-private' );
		if ( parseInt( allowPrivate, 10 ) ) {
			query.private = 1;
		}
		$button.addClass('disabled').attr('disabled', true);
		$.get(HameThread.endpoint + '/thread/new', query).done(function(response){
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
			if ( 'checkbox' === $( input ).attr( 'type' ) && ! input.checked ) {
				return true;
			}
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

	$(document).on('click', 'a[data-hamethread]', function(e){
		e.preventDefault();
		var $button = $(this);
		var action = $button.attr('data-hamethread');
		var query = {
			_wpnonce: HameThread.nonce
		};
		switch( action ) {
			case 'resolve':
				if ( $button.attr( 'disabled' ) ) {
					return;
				}
				$button.addClass( 'disabled' ).attr( 'disabled', true );
				$.ajax( {
					url: HameThread.endpoint + '/thread/' + $button.attr( 'data-post-id' ) + '?' + $.param( query ),
					method: 'put'
				} ).done( function( response ) {
					alert( response.message );
					window.location.href = response.url;
				} ).fail( HameThread.errorhandler ).always( function () {
					$button.removeclass( 'disabled' ).attr( 'disabled', false );
				} );

				break;
			case 'publish':
			case 'archive':
				if ( ! window.confirm( HameThread[ action ] ) ) {
					return false;
				}
				if ( $button.attr( 'disabled' ) ) {
					return;
				}
				$button.addClass( 'disabled' ).attr( 'disabled', true );
				$.ajax( {
					url: $button.attr( 'href' ) + '?' + $.param( query ),
					method: 'delete'
				} ).done( function( response ) {
					alert( response.message );
					window.location.href = response.url;
				} ).fail( hamethread.errorhandler ).always( function () {
					$button.removeclass( 'disabled' ).attr( 'disabled', false );
				} );
				break;
		}
	});
})(jQuery);

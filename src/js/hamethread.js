/*!
 * Hamethread main script
 *
 * @deps jquery-effects-highlight, wp-i18n
 */

/*global HameThread: true*/

const $ = jQuery;
const { __ } = wp.i18n;


/**
 * Assign extra functions.
 */
$.extend( HameThread, {
	errorHandler: function ( response ) {
		let message = __( 'Sorry but request failed.', 'hamethread' );
		if ( response.responseJSON && response.responseJSON.message ) {
			message = response.responseJSON.message;
		}
		alert( message );
	},
	removeElement: function ( elem, callback ) {
		return $( elem )
			.effect( 'highlight', {}, 300 )
			.fadeOut( 300, function () {
				$( elem ).remove();
				if ( callback ) {
					callback();
				}
			} );
	},
	/**
	 * Send request.
	 *
	 * @param {String} method
	 * @param {String } path
	 * @param {Object} params
	 * @return {jQuery.ajax}
	 */
	request: function ( method, path, params ) {

		var url = ( HameThread.endpoint + '/' + path.replace( /\/$/, '' ).replace( /^\//, '' ) );
		var data = null;
		method = method.toUpperCase();
		// TODO: to be ready for cached page, nonce should be retrieved from wpApi
		if ( params ) {
			params._wpnonce = HameThread.nonce;
		} else {
			params = {
				_wpnonce: HameThread.nonce
			};
		}
		switch ( method ) {
			case 'GET':
			case 'DELETE':
				url += '?' + $.param( params );
				break;
			default:
				data = params;
				break;
		}
		var request = {
			url: url,
			method: method
		};
		if ( data ) {
			request.data = data;
		}
		return $.ajax( request ).fail( HameThread.errorHandler );
	}
} );


// Form cancel
$( document ).on( 'click', '.hamethread-form-cancel', function ( e ) {
	e.preventDefault();
	$( this ).parents( '.hamethread-form' ).remove();
} );


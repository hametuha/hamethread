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
	errorHandler( response ) {
		let message = __( 'Sorry but request failed.', 'hamethread' );
		if ( response.responseJSON && response.responseJSON.message ) {
			message = response.responseJSON.message;
		}
		alert( message );
	},
	removeElement( elem, callback ) {
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
	 * @param {string} method
	 * @param {string} path
	 * @param {Object} params
	 * @return {Object}
	 */
	request( method, path, params ) {
		let url =
			HameThread.endpoint +
			'/' +
			path.replace( /\/$/, '' ).replace( /^\//, '' );
		let data = null;
		method = method.toUpperCase();
		// TODO: to be ready for cached page, nonce should be retrieved from wpApi
		if ( params ) {
			params._wpnonce = HameThread.nonce;
		} else {
			params = {
				_wpnonce: HameThread.nonce,
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
		const request = {
			url,
			method,
		};
		if ( data ) {
			request.data = data;
		}
		return $.ajax( request ).fail( HameThread.errorHandler );
	},
} );

// Form cancel
$( document ).on( 'click', '.hamethread-form-cancel', function ( e ) {
	e.preventDefault();
	$( this ).parents( '.hamethread-form' ).remove();
} );

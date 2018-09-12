/**
 * Description
 */

/*global HameThread: true*/

(function ($) {

	'use strict';

	/**
	 * Assign extra functions.
	 */
	$.extend(HameThread, {
		errorHandler: function (response) {
			var message = HameThread.error;
			if (response.responseJSON && response.responseJSON.message) {
				message = response.responseJSON.message;
			}
			alert(message);
		},
		removeElement: function (elem) {
			return $(elem)
				.effect('highlight', {}, 300)
				.fadeOut(300, function () {
					$(elem).remove();
				});
		},
		/**
		 * Send request.
		 *
		 * @param {String} method
		 * @param {String } path
		 * @param {Object} params
		 * @return {jQuery.ajax}
		 */
		request: function(method, path, params){

			var url = (HameThread.endpoint + '/' + path.replace(/\/$/, '').replace(/^\//, ''));
			var data = null;
			method = method.toUpperCase();
			if(params){
				params._wpnonce = HameThread.nonce;
			}else{
				params = {
					_wpnonce: HameThread.nonce
				};
			}
			switch(method){
				case 'GET':
				case 'DELETE':
					url += '?' + $.param(params);
					break;
				default:
					data = params;
					break;
			}
			var request = {
				url: url,
				method: method
			};
			if(data){
				request.data = data;
			}
			return $.ajax(request).error(HameThread.errorHandler);
		}
	});

})(jQuery);

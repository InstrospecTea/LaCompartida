"use strict";

(function ($) {
	window.LoadingModal = function () {

		this.fileDownload = function (id_button, id_data, url) {
			var onclick = $(id_button).attr('onclick');
			$(id_button).addClass('ui-state-disabled');
			$(id_button).removeAttr('onclick');

			$.fileDownload(url, {
				successCallback: function (url) {
					$(id_button).removeClass('ui-state-disabled');
					$(id_button).attr('onclick', onclick);
				},
				preparingMessageHtml: "Generando documento, espere...",
				failMessageHtml: "Se produjo un error generando el documento, por favor intente nuevamente.",
				httpMethod: 'post',
				data: $(id_data).serialize()
			});
		};
		
	};
})(jQuery);
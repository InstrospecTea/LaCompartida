"use strict";

(function ($) {
	window.LoadingModal = function () {

		this.fileDownload = function (id_data, url) {
			$.fileDownload(url, {
				preparingMessageHtml: "<div class='bounce1'></div><div class='bounce2'></div><div class='bounce3'></div>",
				failMessageHtml: "Se produjo un error generando el documento, por favor intente nuevamente.",
				httpMethod: 'post',
				dialogOptions: {
					modal: true,
					closeOnEscape: false,
					resizable: false,
					dialogClass: 'loadingModal',
				},
				data: $(id_data).serialize()
			});
		};
		
	};
})(jQuery);
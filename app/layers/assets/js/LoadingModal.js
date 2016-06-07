"use strict";

(function ($) {
	window.LoadingModal = function () {
		var $divs = $('<div/>').addClass('bounces');
		$divs.append($('<div/>').addClass('bounce1'));
		$divs.append($('<div/>').addClass('bounce2'));
		$divs.append($('<div/>').addClass('bounce3'));
		this.fileDownload = function (form, url, data) {
			$.fileDownload(url, {
				preparingMessageHtml: $divs,
				failMessageHtml: "Se produjo un error generando el documento, por favor intente nuevamente.",
				httpMethod: 'post',
				dialogOptions: {
					modal: true,
					closeOnEscape: false,
					resizable: false,
					dialogClass: 'loadingModal',
					close: function () {
						$(this).dialog('destroy').remove();
					}
				},
				data: form ? $(form).serialize() : data
			});
		};

	};
})(jQuery);
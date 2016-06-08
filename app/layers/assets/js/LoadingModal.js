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
				data: form ? $(form).serialize() : data,
				failCallback: function (html, url) {
					var $el = $('.loadingModal .ui-dialog-content');
					$el.css({'padding': '.5em', 'text-align': 'center', 'vertical-align': 'middle'});
					$el.dialog({height:'auto'});
					$('.ui-dialog-titlebar').text('Error');
					var $button = $('<button/>').text('Cerrar');
					var $button_pane = $('<div/>').addClass('ui-dialog-buttonpane ui-widget-content ui-helper-clearfix');
					$button_pane.append($button);
					$('.loadingModal').append($button_pane);
					$('.loadingModal').removeClass('loadingModal');
					$button.on('click', function() {
						$el.dialog('close');
					});
					return false;
				}
			});
		};

	};
})(jQuery);
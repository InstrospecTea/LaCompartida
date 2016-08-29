var graphic;
(function($) {
	graphic = {
		$form: null,
		render: function (container, charts_data) {
			var url = 'render_grafico.php';
			if (!this.hasIframe(container)) {
				this.addIframe(container, url);
			}
			this.$form.find('#charts_data').val(JSON.stringify(charts_data));
			this.$form.submit();
		},
		hasIframe: function (container) {
			return $(container).find('iframe').length > 0;
		},
		addIframe: function (container, url) {
			var iframe = $('<iframe/>')
				.css('border', 'none')
				.attr('name', 'charts_iframe')
				.attr('id', 'charts_iframe')
				.attr('scrolling', 'no')
				.attr('width', '700')
				.attr('height', '500');

			$(container).html(iframe);
			var $input = $('<input/>')
				.attr('type', 'hidden')
				.attr('name', 'charts_data')
				.attr('id', 'charts_data');

			this.$form = $('<form/>')
				.attr('method', 'post')
				.attr('action', url)
				.attr('target', 'charts_iframe')
				.append($input);
			$(container).append(this.$form);
		}
	};
})(jQuery);

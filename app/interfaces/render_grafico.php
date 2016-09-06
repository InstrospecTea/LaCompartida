<?php
	require_once dirname(__FILE__).'/../conf.php';
	$Html = new \TTB\Html();

	echo $Html->script(
		'https://code.jquery.com/jquery-1.9.1.min.js',
		array(
			'integrity' => 'sha256-wS9gmOZBqsqWxgIVgA8Y9WcQOa7PgSIX+rPA0VL2rbQ=',
			'crossorigin' => 'anonymous'
		)
	);
	echo $Html->script(
		Conf::RootDir() . '/public/js/vendors.js?' . UtilesApp::obtenerVersion()
	);
	echo $Html->css('//static.thetimebilling.com/css/main.css');
?>

<style type="text/css">
	.contenedorCanvas {
		margin-top: 20px;
	}
</style>

<script type="text/javascript">
	jQuery(document).ready(function() {
		var charts_data = <?= json_decode(utf8_encode($_POST['charts_data'])); ?>;
		var responses = [];

		var promises = jQuery.map(charts_data, function(chart_data) {
			return jQuery.ajax({
				url: chart_data.url,
				data: chart_data.data,
				dataType: 'json',
				type: 'POST',
				success: function(response) {
					if (typeof response.options != 'undefined' &&
							typeof response.options.tooltips != 'undefined') {
						for (var key in response.options.tooltips.callbacks) {
							(function(text) {
								response.options.tooltips.callbacks[key] = function(tooltipItem, data){
									return Array.isArray(tooltipItem) ? text[tooltipItem[0].index] : text[tooltipItem.index];
								}
							})(response.options.tooltips.callbacks[key]);
						}
					}

					responses.push(response);
				},
				error: function(e) {
					alert('Se ha producido un error en la carga de los gráficos, favor volver a cargar la pagina. Si el problema persiste favor comunicarse con nuestra área de Soporte.');
				}
			});
		});

		jQuery.when.apply(jQuery, promises).done(function( x ) {
			if (responses[0].error != null) {
				var h3 = document.createElement('h3');
				h3.style = 'text-align: center; font-family: Tahoma, Arial, Geneva, sans-serif;';
				h3.innerText = 'No exiten datos para generar el gráfico';

				jQuery('#contenedor_graficos').append(h3);
				return;
			}

			for (var i in responses) {
				var response = responses[i];
				var canvas_id = new Date().getTime();

				var $div = jQuery('<div/>')
					.attr('id', 'contenedor_' + canvas_id)
					.addClass('contenedorCanvas');

				var $canvas = jQuery('<canvas/>')
					.attr('width', 600)
					.attr('height', 400)
					.attr('id', 'grafico_' + canvas_id);

				agregarBotones($div, 'grafico_' + canvas_id, response.name_chart);

				$div.append($canvas);
				jQuery("#contenedor_graficos").append($div);

				var context = document.getElementById('grafico_' + canvas_id).getContext('2d');

				var chart = new Chart(context, response);
			}

			reportIFrameHeight();
		});

		function agregarBotones($contenedor, id_canvas, report_name) {
			var $button = jQuery('<button/>')
				.text('PDF')
				.attr('id', 'btn_pdf_' + id_canvas)
				.addClass('btn_pdf')
				.css({float: 'right'})
				.data('id_canvas', id_canvas)
				.on('click', function(event){
					var id_canvas = '#' + jQuery(this).data('id_canvas');
					var $canvas = jQuery(id_canvas);

					var pdf = new jsPDF('portrait', 'mm', 'a4');
					pdf.setProperties({
						title: report_name,
						author: 'Lemontech',
						creator: '© Lemontech'
					});

					pdf.addImage($canvas[0].toDataURL('image/png'), 'png', 13, 20);
					pdf.save(report_name + '.pdf');
				});

			$contenedor.append($button);
		}
	});
</script>

<div id="contenedor_graficos"></div>
<script type="text/javascript">
	var RESIZE_MESSAGE = 'RESIZE_MESSAGE';
	function reportIFrameHeight() {
		if (!windowIsIFrame()) {
			return;
		}
		var message = {
			action: RESIZE_MESSAGE,
			iframeID: window.frameElement.id,
			height: jQuery('#contenedor_graficos').height() + 20
		};
		var targetOrigin = window.location.href;
		parent.postMessage(JSON.stringify(message), targetOrigin);
	}
	function windowIsIFrame() {
		return window.frameElement != null;
	}
</script>

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

<script type="text/javascript">
	jQuery(document).ready(function() {
		var charts_data = <?= json_encode($_POST['charts_data']); ?>;
		var responses = [];

		var promises = jQuery.map(charts_data, function(chart_data) {
			return jQuery.ajax({
				url: chart_data.url,
				data: chart_data.data,
				dataType: 'json',
				type: 'POST',
				success: function(response) {
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
				h3.innerText = 'No exiten datos para generar el gráfico'

				jQuery('#contenedor_graficos').append(h3);
				return;
			}

			for (var i in responses) {
				var response = responses[i];
				var canvas_id = response['name_chart'].toLowerCase().replace(/ /g, '_');

				agregarCanvas(canvas_id, jQuery("#contenedor_graficos"));

				jQuery('#h3').html(response['name_chart']);
				jQuery('#contenedor_' + canvas_id + ' h2').append(response['name_chart']);

				var context = document.getElementById('grafico_' + canvas_id).getContext('2d');

				var chart = new Chart(context, response);
			}
		});

		function agregarCanvas(id, contenedor) {
			var div = document.createElement('div');
			var canvas = document.createElement('canvas');
			var h2 = document.createElement('h2');
			canvas.width = 600;
			canvas.height = 400;
			canvas.id = 'grafico_' + id;
			div.id = 'contenedor_' + id;
			div.className = 'contenedorCanvas';
			h2.style = 'text-align: center; font-family: Tahoma, Arial, Geneva, sans-serif;';

			div.appendChild(h2);
			div.appendChild(canvas);
			contenedor.append(div);
		}
	});
</script>

<div id="contenedor_graficos"></div>

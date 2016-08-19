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
		jQuery.ajax({
			url: '<?= $_POST['url']; ?>',
			data: <?= json_encode($_POST); ?>,
			dataType: 'json',
			type: 'POST',
			success: function(respuesta) {
				// TODO: MULTIPLES GRÁFICOS
				if (respuesta != null) {
					var context = document.getElementById("canvas").getContext("2d");

					// var canvas = document.createElement('canvas');
					// canvas.id = 'canvas';
					// var h3 = document.createElement('h3');
					// h3.innerHTML = respuesta['name_chart'];

					jQuery('#h3').html(respuesta['name_chart']);
					// jQuery('#contenedor_grafico').append(canvas);
					// agregarCanvas('hito', jQuery('#contenedor_grafico_hito'), respuesta['name_chart']);

					// var barChartData = {
     //        labels: ["January", "February", "March", "April", "May", "June", "July"],
     //        datasets: [{
     //          label: "Sales",
     //          type:'line',
     //          data: [51, 65, 40, 49, 60, 37, 40],
     //          fill: false,
     //          borderColor: '#EC932F',
     //          backgroundColor: '#EC932F',
     //          pointBorderColor: '#EC932F',
     //          pointBackgroundColor: '#EC932F',
     //          pointHoverBackgroundColor: '#EC932F',
     //          pointHoverBorderColor: '#EC932F',
     //          yAxisID: 'y-axis-2'
     //        }, {
     //          type: 'bar',
     //          label: "Visitor",
     //          data: [200, 185, 590, 621, 250, 400, 95],
     //          fill: false,
     //          backgroundColor: '#71B37C',
     //          borderColor: '#71B37C',
     //          hoverBackgroundColor: '#71B37C',
     //          hoverBorderColor: '#71B37C',
     //          yAxisID: 'y-axis-1'
     //        }]
     //    	};

			graficoBarraHito = new Chart(context, {
  				type: 'bar',
					data: respuesta,
					options: {
						responsive: true,
						tooltips: {
							mode: 'label'
						},
						elements: {
							line: {
								fill: false
							}
						},
						scales: {
							xAxes: [{
								display: true,
								gridLines: {
									display: false
								},
								labels: {
									show: true,
								}
							}],
							yAxes: [{
								type: "linear",
								display: true,
								position: "left",
								id: "y-axis-1",
								gridLines:{
									display: false
								},
								labels: {
									show:true,
								}
							}, {
								type: "linear",
								display: true,
								position: "right",
								id: "y-axis-2",
								gridLines:{
									display: false
								},
								labels: {
									show:true,
								}
							}]
						}
					}
					});
				} else {
					jQuery('#contenedor_grafico').empty();
					jQuery('#contenedor_grafico').append('<h3>No exiten datos para generar el gráfico</h3>');
				}
			},
			error: function(e) {
				alert('Se ha producido un error en la carga de los gráficos, favor volver a cargar la pagina. Si el problema persiste favor comunicarse con nuestra área de Soporte.');
			}
		});
	});
</script>

<div id="contenedor_grafico">
	<h3 id="h3" style="text-align: center;"></h3>
	<canvas id="canvas" width="600" height="400"></canvas>
</div>

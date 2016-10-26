<?php
	require_once dirname(__FILE__).'/../../conf.php';

	$sesion = new Sesion();
	$Criteria = new Criteria($sesion);

 	if (is_array($usuarios)) {
 		$Criteria->add_restriction(
 			CriteriaRestriction::in('trabajo.id_usuario', $usuarios)
 		);
 	}

 	if ($solo_activos == 1) {
 		$Criteria->add_restriction(
 			CriteriaRestriction::equals('usuario.activo', 1)
 			);
	}

	if (is_array($clientes)) {
		$Criteria->add_restriction(
			CriteriaRestriction::in('cliente.codigo_cliente', $clientes)
		);
	}

	$total_tiempo = 0;

	$Criteria->add_select("CONCAT_WS(', ', apellido1, nombre)", 'usuario')
		->add_select('username')
		->add_select('SUM(TIME_TO_SEC(duracion)) / 3600', 'tiempo')
		->add_from('trabajo')
		->add_custom_join_with(
			'usuario',
			CriteriaRestriction::equals('usuario.id_usuario', 'trabajo.id_usuario'), ''
		)
		->add_custom_join_with(
			'asunto',
			CriteriaRestriction::equals('trabajo.codigo_asunto', 'asunto.codigo_asunto'), ''
		)
		->add_custom_join_with(
			'cliente',
			CriteriaRestriction::equals('asunto.codigo_cliente', 'cliente.codigo_cliente'), ''
		)
		->add_restriction(
			CriteriaRestriction::between('trabajo.fecha', "'" . Utiles::fecha2sql($fecha_ini) . "'", "'" . Utiles::fecha2sql($fecha_fin) . "'")
		)
		->add_grouping('usuario.id_usuario')
		->add_ordering('tiempo', 'DESC')
		->add_limit(14, 0);

	try {
		$respuesta = $Criteria->run();
	} catch(Exception $e) {
		error_log('Error al ejecutar la SQL');
	}

	foreach ($respuesta as $i => $value) {
		$empleado[] = $value['username'];
		$nombre_empleado[] = [Encode::utf8($value['usuario'])];
		$tiempo[] = $value['tiempo'];
		$tiempo_formateado = Format::number(floatval($value['tiempo']));
		$tiempo_tooltip[] = ["{$tiempo_formateado} Hrs."];
		$total_tiempo += $value['tiempo'];
	}

	$grafico = new TTB\Graficos\Grafico();
	if (is_null($tiempo)) {
		echo $grafico->getJsonError(3, 'No exiten datos para generar el gráfico');
		return;
	}

	$LanguageManager = new LanguageManager($sesion);
	$language = $LanguageManager->getById(1);
	$separators = [
		'decimales' => $language->fields['separador_decimales'],
		'miles' => $language->fields['separador_miles']
	];

	$dataset = new TTB\Graficos\Dataset();

	$options = [
		'responsive' => true,
		'tooltips' => [
			'mode' => 'label',
			'callbacks' => [
				'afterTitle' => $nombre_empleado,
				'label' => $tiempo_tooltip
			]
		],
		'title' => [
			'display' => true,
			'fontSize' => 14,
			'text' => __('Horas trabajadas por usuario')
		],
		'scales' => [
			'xAxes' => [[
				'display' => true,
				'gridLines' => [
					'display' => false
				],
				'labels' => [
					'show' => true,
				]
			]],
			'yAxes' => [
				[
					'type' => 'linear',
					'display' => true,
					'position' => 'left',
					'id' => 'y-axis-1',
					'gridLines' => [
						'display' => false
					],
					'labels' => [
						'show' => true
					],
					'ticks' => [
						'beginAtZero' => true,
						'callback' => $separators
					]
				]
			]
		]
	];

	$dataset->setType('bar')
		->setFill(false)
		->setYAxisID('y-axis-1')
		->setLabel(__('Horas trabajadas por usuario'))
		->setData($tiempo);

	$grafico->setNameChart(__('Horas trabajadas por usuario'))
		->addDataset($dataset)
		->setOptions($options)
		->addLabels($empleado);

	echo $grafico->getJson();

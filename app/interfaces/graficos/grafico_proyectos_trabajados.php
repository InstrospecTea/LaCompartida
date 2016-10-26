<?php
	require_once dirname(__FILE__).'/../../conf.php';

	$sesion = new Sesion();

	$fecha_inicio = Date::parse($fecha1)->toDate();
	$fecha_fin = Date::parse($fecha2)->toDate();
	$Criteria = new Criteria($sesion);

	if (Conf::read('CodigoSecundario')) {
		$codigo_asunto = 'codigo_asunto_secundario';
	} else {
		$codigo_asunto = 'codigo_asunto';
	}

	$Criteria
		->add_select("asunto." . $codigo_asunto, 'codigo_asunto')
		->add_select("asunto.codigo_cliente", 'codigo_cliente')
		->add_select("asunto.glosa_asunto", 'glosa_asunto')
		->add_select('SUM(TIME_TO_SEC(duracion))/3600', 'tiempo')
		->add_from('asunto')
		->add_left_join_with('trabajo',
			CriteriaRestriction::equals('trabajo.codigo_asunto', 'asunto.codigo_asunto')
		)
		->add_restriction(
			CriteriaRestriction::equals('trabajo.id_usuario', "'$id_usuario'")
		)
		->add_restriction(
			CriteriaRestriction::between('fecha', "'$fecha_inicio'", "'$fecha_fin'")
		)
		->add_grouping('asunto.codigo_asunto')
		->add_ordering('tiempo', 'DESC')
		->add_limit(14, 0);

	try {
		$respuesta = $Criteria->run();
	} catch(Exception $e) {
		error_log('Error al ejecutar la SQL');
	}

	foreach ($respuesta as $i => $fila) {
		$tiempo[] = $fila['tiempo'];
		$tiempo_formateado = Format::number(floatval($fila['tiempo']));
		$tiempo_tooltip[] = ["{$tiempo_formateado} Hrs."];
		$labels[] = $fila['codigo_asunto'];
		$fila['glosa_asunto'] = Encode::utf8($fila['glosa_asunto']);
		$glosa_asunto[] = [
			__('Cliente') . ': ' . $fila['codigo_cliente'],
			__('Asunto')  . ': ' . $fila['glosa_asunto']
		];
	}

	$titulo = __('Horas trabajadas') . (!empty($nombre_usuario) ? ' - ' . $nombre_usuario : '');

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
				'afterTitle' => $glosa_asunto,
				'label' => $tiempo_tooltip
			]
		],
		'title' => [
			'display' => true,
			'fontSize' => 14,
			'text' => Encode::utf8($titulo)
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

	$dataset->setYAxisID('y-axis-1')
		->setLabel(__('Horas trabajadas'))
		->setData($tiempo);

	$grafico->setNameChart($titulo)
		->addDataset($dataset)
		->setOptions($options)
		->addLabels($labels);

	echo $grafico->getJson();

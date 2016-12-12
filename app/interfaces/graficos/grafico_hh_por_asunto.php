<?php
	require_once dirname(__FILE__).'/../../conf.php';

	$sesion = new Sesion();
	$Criteria = new Criteria($sesion);

	if (is_array($usuarios)) {
		$Criteria->add_restriction(
			CriteriaRestriction::in('trabajo.id_usuario', $usuarios)
		);
	}

	if($solo_activos) {
		$Criteria->add_restriction(
			CriteriaRestriction::equals('usuario.activo ', 1)
		);
	}

	if(is_array($clientes)) {
		$Criteria->add_restriction(
			CriteriaRestriction::in('cliente.codigo_cliente', $clientes)
		);
	}

	$total_tiempo = 0;

	$Criteria
		->add_select('asunto.codigo_asunto')
		->add_select('asunto.glosa_asunto')
		->add_select('SUM(TIME_TO_SEC(duracion))/3600', 'tiempo')
		->add_from('trabajo')
		->add_inner_join_with(
			'usuario',
			CriteriaRestriction::equals('usuario.id_usuario', 'trabajo.id_usuario')
		)
		->add_inner_join_with(
			'asunto',
			CriteriaRestriction::equals('asunto.codigo_asunto', 'trabajo.codigo_asunto')
		)
		->add_inner_join_with(
			'cliente',
			CriteriaRestriction::equals('cliente.codigo_cliente', 'asunto.codigo_cliente')
		)
		->add_restriction(
			CriteriaRestriction::between('trabajo.fecha', "'" . Utiles::fecha2sql($fecha_ini) . "'", "'" . Utiles::fecha2sql($fecha_fin) . "'")
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
		$asunto[] = $fila['codigo_asunto'];
		$glosa_asunto[] = [Convert::utf8($fila['glosa_asunto'])];
		$tiempo[] = $fila['tiempo'];
		$tiempo_formateado = Format::number($fila['tiempo']);
		$tiempo_tooltip[] = ["{$tiempo_formateado} Hrs."];
		$total_tiempo += $fila['tiempo'];
	}

	$grafico = new TTB\Graficos\Grafico();
	if (is_null($tiempo)) {
		echo $grafico->getJsonError(__('No existen datos para generar el gráfico'));
		return;
	}

	$LanguageManager = new LanguageManager($sesion);
	$language_code = strtolower(Conf::read('Idioma'));
	$language = $LanguageManager->getByCode($language_code);
	$separators = [
		'decimales' => $language->get('separador_decimales'),
		'miles' => $language->get('separador_miles')
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
			'text' => __('Horas trabajadas por asunto')
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
			'yAxes' => [[
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
			]]
		]
	];

	$dataset->setType('bar')
		->setYAxisID('y-axis-1')
		->setLabel(__('Horas trabajadas por asunto'))
		->setData($tiempo);

	$grafico->setNameChart(__('Horas trabajadas por asunto'))
		->addDataset($dataset)
		->setOptions($options)
		->addLabels($asunto);

	echo $grafico->getJson();

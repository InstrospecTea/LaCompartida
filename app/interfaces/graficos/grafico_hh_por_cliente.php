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

	if (Conf::read('CodigoSecundario')) {
 		$codigo_cliente = 'codigo_cliente_secundario';
 	} else {
 		$codigo_cliente = 'codigo_cliente';
 	}


	$total_tiempo = 0;

	$Criteria
		->add_select("CONCAT_WS(' - ', cliente." . $codigo_cliente . ", SUBSTRING(cliente.glosa_cliente, 1, 12))", 'glosa_cliente')
		->add_select('SUM(TIME_TO_SEC(duracion))/3600', 'tiempo')
		->add_from('cliente')
		->add_left_join_with(
			'asunto',
			CriteriaRestriction::equals('asunto.codigo_cliente', 'cliente.codigo_cliente')
		)
		->add_left_join_with(
			'trabajo',
			CriteriaRestriction::equals('trabajo.codigo_asunto', 'asunto.codigo_asunto')
		)
		->add_inner_join_with(
			'usuario',
			CriteriaRestriction::equals('usuario.id_usuario', 'trabajo.id_usuario')
		)
		->add_restriction(
			CriteriaRestriction::between('trabajo.fecha', "'" . Utiles::fecha2sql($fecha_ini) . "'", "'" . Utiles::fecha2sql($fecha_fin) . "'")
		)
		->add_grouping('cliente.codigo_cliente')
		->add_ordering('tiempo', 'DESC')
		->add_limit(14, 0);

	try {
		$respuesta = $Criteria->run();
	} catch(Exception $e) {
		error_log('Error al ejecutar la SQL');
	}

	foreach ($respuesta as $i => $fila) {
		$cliente[] = $fila['glosa_cliente'];
		$tiempo[] = $fila['tiempo'];
		$total_tiempo += $fila['tiempo'];
	}

	$grafico = new TTB\Graficos\Grafico();
	$dataset = new TTB\Graficos\Dataset();

	$options = [
		'responsive' => true,
		'tooltips' => [
			'mode' => 'label'
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
					'beginAtZero' => true
				]
			]]
		]
	];

	$dataset->setType('bar')
		->setFill(false)
		->setYAxisID('y-axis-1')
		->setLabel('Horas trabajadas por cliente')
		->setData($tiempo);

	$grafico->setNameChart('Horas trabajadas por cliente')
		->addDataset($dataset)
		->addOptions($options)
		->addLabels($cliente);

	echo $grafico->getJson();

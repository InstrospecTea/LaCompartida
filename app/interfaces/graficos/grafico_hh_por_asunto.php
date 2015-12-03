<?php
	require_once dirname(__FILE__).'/../../conf.php';

	$sesion = new Sesion();
	$Criteria = new Criteria($sesion);

	if (!empty($usuarios)) {
		$Criteria->add_restriction(
			CriteriaRestriction::in('trabajo.id_usuario', $usuarios)
		);
	}

	if($solo_activos) {
		$Criteria->add_restriction(
			CriteriaRestriction::equals('usuario.activo ', 1)
		);
	}

	if(!empty($clientes)) {
		$Criteria->add_restriction(
			CriteriaRestriction::in('cliente.codigo_cliente', $clientes)
		);
	}

	$total_tiempo = 0;

	$Criteria
		->add_select('asunto.codigo_asunto')
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

	try{
		$respuesta = $Criteria->run();
	} catch(Exception $e) {
		error_log('Error al ejecutar la SQL');
	}

	foreach ($respuesta as $i => $fila) {
		$asunto[] = $fila['codigo_asunto'];
		$tiempo[] = $fila['tiempo'];
		$total_tiempo += $fila['tiempo'];
	}

	$grafico = new TTB\Graficos\GraficoBarra();
	$dataset = new TTB\Graficos\GraficoDataset();

	$dataset->addLabel('Horas trabajadas por asunto')
		->addData($tiempo);

	$grafico->addDataSets($dataset)
		->addLabels($asunto);

	echo $grafico->getJson();
?>

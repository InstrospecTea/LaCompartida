<?php
	require_once dirname(__FILE__).'/../../conf.php';

	$sesion = new Sesion();
	$Criteria = new Criteria($sesion);

	$Criteria
		->add_select("CONCAT_WS(' - ', asunto.codigo_cliente, SUBSTRING(asunto.glosa_asunto, 1, 12))", 'glosa_asunto')
		->add_select('SUM(TIME_TO_SEC(duracion))/3600', 'tiempo')
		->add_from('asunto')
		->add_left_join_with('trabajo',
				CriteriaRestriction::equals('trabajo.codigo_asunto', 'asunto.codigo_asunto')
			)
		->add_restriction(
				CriteriaRestriction::equals('trabajo.id_usuario', "'$id_usuario'")
			)
		->add_restriction(
				CriteriaRestriction::between('fecha', "'" . Utiles::fecha2sql($fecha1) . "'", "'" . Utiles::fecha2sql($fecha2) . "'")
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
		$asunto[] = $fila['glosa_asunto'];
		$tiempo[] = $fila['tiempo'];
	}

	$grafico = new TTB\Graficos\GraficoBarra();
	$dataset = new TTB\Graficos\GraficoDataset();

	$dataset->addLabel(__('Horas trabajadas'))
		->addData($tiempo);

	$grafico->addDataSets($dataset)
		->addNameChart('Horas trabajadas' . (!empty($nombre_usuario) ? ' - ' . $nombre_usuario : ''))
		->addLabels($asunto);

	echo $grafico->getJson();

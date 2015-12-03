<?php
	require_once dirname(__FILE__).'/../../conf.php';

	$sesion = new Sesion();
	$Criteria = new Criteria($sesion);

 	if (method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema')) {
 		$letra_profesional = 'username';
 	} else {
 		$letra_profesional = 'usuario';
 	}

 	if ($usuarios) {
 		$Criteria->add_restriction(
 			CriteriaRestriction::in('trabajo.id_usuario', $usuarios)
 		);
 	}

 	if ($solo_activos == 1) {
 		$Criteria->add_restriction(
 			CriteriaRestriction::equals('usuario.activo', 1)
 			);
	}

	if ($clientes) {
		$Criteria->add_restriction(
			CriteriaRestriction::in('cliente.codigo_cliente', $clientes)
		);
	}

	$total_tiempo = 0;

	$Criteria->add_select("CONCAT_WS(', ', apellido1, nombre)", 'usuario')
		->add_select('username')
		->add_select('SUM(TIME_TO_SEC(duracion))/3600', 'tiempo')
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

	try{
		$respuesta = $Criteria->run();
	} catch(Exception $e) {
		error_log('Error al ejecutar la SQL');
	}

	foreach ($respuesta as $i => $value) {
		$empleado[] = $value[$letra_profesional];
		$tiempo[] = $value['tiempo'];
		$total_tiempo += $value['tiempo'];
	}

	$grafico = new TTB\Graficos\GraficoBarra();
	$dataset = new TTB\Graficos\GraficoDataset();

	$dataset->addLabel('Horas trabajadas por empleado')
		->addData($tiempo);

	$grafico->addDataSets($dataset)
		->addLabels($empleado);

	echo $grafico->getJson();

?>

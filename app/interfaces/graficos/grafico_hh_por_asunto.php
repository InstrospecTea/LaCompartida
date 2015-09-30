<?php

require_once "GraficoBarras.php";
require_once "../../../fw/classes/Sesion.php";
require_once "../../../fw/classes/Utiles.php";

$sesion = new Sesion();

$Criteria = new Criteria($sesion);

if (!empty($usuarios)) {
	$Criteria->add_restriction(
		CriteriaRestriction::in('trabajo.id_usuario', explode(',', $usuarios))
	);
}

if($solo_activos) {
	$Criteria->add_restriction(
		CriteriaRestriction::equals('usuario.activo ', 1)
	);
}

if(!empty($clientes)) {
	$Criteria->add_restriction(
		CriteriaRestriction::in('cliente.codigo_cliente', explode(',', $clientes))
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
			CriteriaRestriction::between('trabajo.fecha', "'{$fecha_ini}'", "'{$fecha_fin}'")
		)
	->add_grouping('asunto.codigo_asunto')
	->add_ordering('tiempo', 'DESC')
	->add_limit(14, 0);

$resp = $Criteria->run();

foreach ($resp as $i => $fila) {
	$asunto[$i] = $fila[codigo_asunto];
	$tiempo[$i] = $fila[tiempo];
	$total_tiempo += $fila[tiempo];
}

#Create a XYChart object of size 300 x 240 pixels
$c = new GraficoBarras();

#Add a title to the chart using 10 pt Arial font
$c->Titulo(sprintf("Horas trabajadas por %s / %s - %s (Sólo 14 más relevantes)", __('asunto'), Utiles::sql2date($fecha_ini), Utiles::sql2date($fecha_fin) ));

#Add a title to the y-axis
$c->Ejes(__('Asunto'),"Horas");

#Set the x axis labels
$c->Labels($asunto);

$c->layer->addDataSet($tiempo, 0xff8080,__('Horas trabajadas').': '.$total_tiempo);
#$layer->addDataSet($terminados, 0x80ff80, "Terminadas");

#output the chart
$c->Imprimir();
?>

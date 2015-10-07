<?php

require_once "GraficoBarras.php";
require_once "../../../fw/classes/Sesion.php";

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
	->add_select('cliente.glosa_cliente')
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
			CriteriaRestriction::between('trabajo.fecha', "'{$fecha_ini}'", "'{$fecha_fin}'")
		)
	->add_grouping('cliente.codigo_cliente')
	->add_ordering('tiempo', 'DESC')
	->add_limit(14, 0);

$resp = $Criteria->run();

foreach ($resp as $i => $fila) {
	$cliente[$i] = $fila[glosa_cliente];
	$tiempo[$i] = $fila[tiempo];
	$total_tiempo += $fila[tiempo];
}

#Create a XYChart object of size 300 x 240 pixels
$c = new GraficoBarras();

#Add a title to the chart using 10 pt Arial font
$title = __('Horas trabajadas').' / '.Utiles::sql2date($fecha_ini).' - '.Utiles::sql2date($fecha_fin).'  '.__('Sólo 14 más relevantes');
$c->Titulo($title);

#Add a title to the y-axis
$c->Ejes(__("Cliente"),__("Horas"));

#Set the x axis labels
$c->Labels($cliente);

#Add a multi-bar layer with 2 data sets
$c->layer->addDataSet($tiempo, 0xff8080, __("Horas trabajadas").': '.$total_tiempo);
#$layer->addDataSet($terminados, 0x80ff80, "Terminadas");

#output the chart
$c->Imprimir();
?>

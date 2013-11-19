<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once APPPATH . '/app/classes/Reportes/SimpleReport.php';

$sesion = new Sesion(array('OFI', 'COB'));

function escapar(&$item) {
	$item = mysql_escape_string(trim($item));
}

$data = $_GET;
array_walk_recursive($data, 'escapar');
extract($data);

$where = array(1);
if ($codigo_actividad != '') {
	$where[] = "codigo_actividad = {$codigo_actividad}";
}

if ($codigo_cliente_secundario != '' && $codigo_cliente == '') {
	$cliente = new Cliente($sesion);
	$codigo_cliente = $cliente->CodigoSecundarioACodigo($codigo_cliente_secundario);
}

if ($codigo_cliente) {
	$where[] = "cliente.codigo_cliente = '{$codigo_cliente}'";
}

if ($codigo_asunto) {
	$where[] = "actividad.codigo_asunto = '{$codigo_asunto}'";
}

$where = implode(' AND ', $where);
$query_excel = "SELECT SQL_CALC_FOUND_ROWS
					actividad.glosa_actividad,
					asunto.glosa_asunto,
					cliente.glosa_cliente,
					actividad.codigo_actividad

				FROM actividad
				LEFT JOIN asunto ON actividad.codigo_asunto = asunto.codigo_asunto
				LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente

				WHERE $where";

$statement = $sesion->pdodbh->prepare($query_excel);
$statement->execute();
$results = $statement->fetchAll(PDO::FETCH_ASSOC);

$config = array(
	array(
		'field' => 'glosa_actividad',
		'title' => __('Nombre Actividad'),
		'extras' => array('width' => 60)
	),
	array(
		'field' => 'glosa_asunto',
		'title' => __('Nombre Asunto'),
		'extras' => array('width' => 40)
	),
	array(
		'field' => 'glosa_cliente',
		'title' => __('Nombre Cliente'),
		'extras' => array('width' => 20)
	),
	array(
		'field' => 'codigo_actividad',
		'title' => __('Codigo Actividad'),
		'extras' => array('width' => 20)
	)
);


$SimpleReport = new SimpleReport($sesion);
$SimpleReport->SetRegionalFormat($regional_format);
$SimpleReport->SetBaseConfig($config);
$SimpleReport->LoadConfiguration($config);
$SimpleReport->Config->title = __('Listado de Actividades');
$SimpleReport->LoadResults($results);

$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Spreadsheet');
$writer->save(__('Listado_actividades'));

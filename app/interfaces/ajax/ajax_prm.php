<?php
require_once dirname(__FILE__) . '/../../conf.php';

$Sesion = new Sesion();

$single_class = filter_input(INPUT_GET, 'single_class', FILTER_SANITIZE_STRING);
$fetch_by_id = filter_input(INPUT_GET, 'fetch_by_id', FILTER_SANITIZE_STRING);
$id_field = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
$fields = filter_input(INPUT_GET, 'fields', FILTER_SANITIZE_STRING);
$prm = filter_input(INPUT_GET, 'prm', FILTER_SANITIZE_STRING);
$order_by = filter_input(INPUT_GET, 'order_by', FILTER_SANITIZE_STRING);
$order_by_type = filter_input(INPUT_GET, 'order_by_type', FILTER_SANITIZE_STRING);

$prmClass = ($single_class && $single_class == '1') ? $prm : 'Prm' . $prm;
$PrmPrm = new $prmClass($Sesion);

if (!is_null($id_field)) {
	$PrmPrm->campo_id = $id_field;
}

$queryExtra = '';
if ($order_by) {
	$limpia = function($elem){
		$pattern = '/[^0-9A-Za-z_]/';
		return preg_replace($pattern, '', $elem);
	};

	$order_by = implode(', ', array_map($limpia, explode(',', $order_by)));

	if (strtolower($order_by_type) <> 'desc'){
		$order_by_type = 'ASC';
	}
	$queryExtra .= sprintf('ORDER BY %s %s', $order_by, $order_by_type);
}
try {
	$list = $PrmPrm->Listar($queryExtra, $fields, (boolean)$fetch_by_id);
	echo empty($list) ? '{}' : json_encode(UtilesApp::utf8izar($list));
} catch(Exception $e) {
	echo json_encode(UtilesApp::utf8izar(array('error' => $e->getMessage())));
}
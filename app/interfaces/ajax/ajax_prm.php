<?php
require_once dirname(__FILE__) . '/../../conf.php';

$Sesion = new Sesion();

$single_class = filter_input(INPUT_GET, 'single_class', FILTER_SANITIZE_STRING);
$prm = filter_input(INPUT_GET, 'prm', FILTER_SANITIZE_STRING);
$order_by = filter_input(INPUT_GET, 'order_by', FILTER_SANITIZE_STRING);
$order_by_type = filter_input(INPUT_GET, 'order_by_type', FILTER_SANITIZE_STRING);
$query = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING);
$id_field = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
$fields = filter_input(INPUT_GET, 'fields', FILTER_SANITIZE_STRING);
$hasFields = !empty($fields);
$prmClass = ($single_class === '1') ? $prm : 'Prm' . $prm;

if (!class_exists($prmClass)) {
	echo '{}';
} else {
	$PrmPrm = new $prmClass($Sesion);

	$queryExtra = '';

	if (!is_null($query)) {
		$query_list = explode(',', $query);
		$queryExtra = 'WHERE ';
		$queryFields = array();
		foreach ($query_list as $field) {
			$query_object = explode(':', $field);
			$queryFields[] = $query_object[0] . ' = "' . $query_object[1] . '"';
		}
		$queryExtra .= implode(' AND ', $queryFields);
	}

	if (!is_null($id_field)) {
		$PrmPrm->campo_id = $id_field;
	}

	$order_by = preg_replace('/[^a-zA-Z0-9, ]/', '', $order_by);
	if ($order_by) {
		if (strtolower($order_by_type) != 'asc') {
			$order_by_type = ' DESC';
		} else {
			$order_by_type = ' ASC';
		}

		$queryExtra .= ' ORDER BY ' . $order_by . $order_by_type;
	}

	try {
		if ($hasFields && (method_exists($prmClass, 'ListarExt'))) {
			$list = $PrmPrm->ListarExt($queryExtra, $fields);
		} else {
			$list = $PrmPrm->Listar($queryExtra);
		}

		echo empty($list) ? '{}' : json_encode(UtilesApp::utf8izar($list));
	} catch (Exception $e) {
		echo json_encode(UtilesApp::utf8izar(array('error' => $e->getMessage())));
	}
}

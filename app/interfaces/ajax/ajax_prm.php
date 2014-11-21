<?php
require_once dirname(__FILE__).'/../../conf.php';

$Sesion = new Sesion();

$single_class = $_GET['single_class'];
$prmClass = ($single_class && $single_class == '1') ? $_GET['prm'] : 'Prm' . $_GET['prm'];

$query = $_GET['q'] ? $_GET['q'] : null;
$fields = $_GET['fields'] ? $_GET['fields'] : '';
$hasFields = !empty($fields);

$PrmPrm = new $prmClass($Sesion);

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
if ($hasFields && (method_exists($prmClass, 'ListarExt'))) {
  $list = $PrmPrm->ListarExt($queryExtra, $fields);
} else {
  $list = $PrmPrm->Listar($queryExtra);
}

echo empty($list) ? '{}' : json_encode(UtilesApp::utf8izar($list));

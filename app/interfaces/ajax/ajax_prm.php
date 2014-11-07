<?php
require_once dirname(__FILE__).'/../../conf.php';

$Sesion = new Sesion();

$prmClass = 'Prm' . $_GET['prm'];

$customQuery = $_POST['query'] ? $_POST['query'] : '';
$fields = $_GET['fields'] ? $_GET['fields'] : '';
$hasFields = !empty($fields);

$PrmPrm = new $prmClass($Sesion);

if ($hasFields && (method_exists($prmClass, 'ListarExt'))) {
  $list = $PrmPrm->ListarExt($customQuery, $fields);
} else {
  $list = $PrmPrm->Listar($customQuery);
}

echo json_encode(UtilesApp::utf8izar($list));
<?php

require_once dirname(__FILE__) . '/../app/conf.php';

$sesion = new Sesion(array('ADM'));

$CargaMasiva = new CargaMasiva($sesion);

if (isset($data) && isset($campos)) {
	$errores = $CargaMasiva->CargarData($data, $clase, $campos);
	echo json_encode(UtilesApp::utf8izar($errores));
} else if (isset($obtener_listados)) {
	$listados = $CargaMasiva->ObtenerListados($clase);
	echo json_encode(UtilesApp::utf8izar($listados));
}

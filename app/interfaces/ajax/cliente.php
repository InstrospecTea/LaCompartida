<?php
require_once('../../conf.php');

$Sesion = new Sesion();
$Cliente = new Cliente($Sesion);

$respuesta = array('error' => null);

switch ($_GET['opt']) {
	case 'ultimo_codigo':
		$respuesta['codigo'] = $Cliente->CodigoSecundarioSiguienteCorrelativo();
		break;

	case 'validar_codigo':
		$valido = $Cliente->CodigoSecundarioValidarCorrelativo($_GET['codigo']);
		if ($valido === true) {
			$respuesta['valido'] = true;
		} else {
			$respuesta['error'] = $valido;
		}
		break;

	default:
		$respuesta['error'] = 'Consulta no válida';
}

echo json_encode(UtilesApp::utf8izar($respuesta));

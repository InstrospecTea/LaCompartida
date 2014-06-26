<?php

require_once('../../conf.php');

$Sesion = new Sesion();
$Asunto = new Asunto($Sesion);

$respuesta = array('error' => null);

switch ($_GET['opt']) {
	case 'ultimo_codigo':
		$respuesta['codigo'] = $Asunto->CodigoSecundarioSiguienteCorrelativo();
		break;

	case 'validar_codigo':
		$valido = $Asunto->CodigoSecundarioValidarCorrelativo($_GET['codigo']);
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
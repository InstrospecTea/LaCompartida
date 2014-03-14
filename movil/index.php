<?php

require_once(dirname(__FILE__) . '/../app/conf.php');

$Slim=Slim::getInstance('default')? Slim::getInstance('default') : new Slim();
$sesion = new Sesion();




$wsdl = Conf::Server() . Conf::RootDir() . '/web_services/webservices.php';

$wsClient = new SoapClient($wsdl . '?wsdl');
$wsClient->__setLocation($wsdl);

$Slim->get('/', 'root');

function root() {
	$redirect = str_replace('//movil', '/movil', Conf::Host() . '/movil/public/index.php');
	$Slim = Slim::getInstance();
	$Slim->redirect($redirect);
}


$Slim->map('login', 'Authenticate')->via('GET', 'POST');
$Slim->map('/login', 'Authenticate')->via('GET', 'POST');


function Authenticate()  {
	global $sesion;
	$Slim=Slim::getInstance('default');
		$usuario= $Slim->request()->params('rut');
		$password= $Slim->request()->params('password');
		if (empty($usuario) || empty($password) || $usuario == "" || $password == "") {
					$Slim->response()->status(401);
					$Slim->halt(401,'["Debe entregar el usuario y el password."]');
				} else if (!$sesion->VerificarPassword($usuario, $password)) {
					$Slim->response()->status(401);
					$Slim->halt(401,'["Usuario o Password incorrectos"]');
				}
		header('Content-Type: text/javascript; charset=utf8');
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Max-Age: 3628800');
		header('Access-Control-Allow-Methods: GET, POST');

}


$Slim->post('/clientes', 'obtener_clientes');
$Slim->post('clientes', 'obtener_clientes');

//$Slim->get('/clientes', 'obtener_clientes');
function obtener_clientes() {
	global $sesion, $wsClient;
	$Slim = Slim::getInstance();
	try {
		$retorno = $wsClient->EntregarListaClientes($_POST['rut'], $_POST['password']);
	} catch (SoapFault $e) {
		$Slim->halt(401, '["Debe entregar el usuario y el password."]');
	}


	echo json_encode($retorno);
}

$Slim->post('/asuntos', 'obtener_asuntos');
$Slim->post('asuntos', 'obtener_asuntos');

//$Slim->get('/asuntos', 'obtener_asuntos');
function obtener_asuntos() {
	global $sesion, $wsClient;
	$Slim = Slim::getInstance();
	try {
		$retorno = $wsClient->EntregarListaAsuntos($_POST['rut'], $_POST['password']);
	} catch (SoapFault $e) {
		$Slim->halt(401, '["Debe entregar el usuario y el password."]');
	}


	echo json_encode($retorno);
}

$Slim->get('/intervalo', 'obtener_intervalo');
$Slim->get('intervalo', 'obtener_intervalo');

function obtener_intervalo() {
	global $sesion, $wsClient;
	//status(401);
	//return "holiwi";
	echo json_encode($wsClient->Intervalo());
}

$Slim->get('/nombre_empresa', 'obtener_nombre_empresa');

function obtener_nombre_empresa() {
	return Conf::PdfLinea1();
}

$Slim->post('/trabajos', 'cargar_trabajo');
$Slim->post('trabajos', 'cargar_trabajo');

function cargar_trabajo() {
	global $sesion, $wsClient;
	$Slim = Slim::getInstance();
	try {
		$wsClient->CargarTrabajo($_POST['rut'], $_POST['password'], "", $_POST['codigo_asunto'], "", $_POST['descripcion'], date('Y-m-d', strtotime($_POST['fecha']) + 86400), (int) $_POST['duracion'] * 60);
	} catch (SoapFault $e) {
		if ($e->faultstring == "Debe entregar el usuario y el password.") {
			$Slim->halt(401, '["Debe entregar el usuario y el password."]');
		} else {
			$Slim->halt(401, $e->faultstring);
		}
	}

	echo "Trabajo cargado OK";
}

$Slim->post('/trabajos2', 'cargar_trabajo2');
$Slim->post('trabajos2', 'cargar_trabajo2');

function cargar_trabajo2() {
	global $sesion, $wsClient;
	$Slim = Slim::getInstance();
	try {
		$wsClient->CargarTrabajo2($_POST['rut'], $_POST['password'], "", $_POST['codigo_asunto'], "", $_POST['descripcion'], $_POST['ordenado_por'], date('Y-m-d', strtotime($_POST['fecha']) + 86400), (int) $_POST['duracion'] * 60, "");
	} catch (SoapFault $e) {
		if ($e->faultstring == "Debe entregar el usuario y el password.") {
			$Slim->halt(401, '["Debe entregar el usuario y el password."]');
		} else {
			$Slim->halt(500, $e->faultstring);
		}
	}

	echo "Trabajo cargado OK";
}

$Slim->post('/trabajos_app', 'cargar_trabajo_app');
$Slim->post('trabajos_app', 'cargar_trabajo_app');

function getAppIdByAppKey($app_key) {
	global $sesion;
	$UserToken = new UserToken($sesion);
	return $UserToken->getAppIdByAppKey($app_key);
}

function cargar_trabajo_app() {
	global $sesion, $wsClient;
	$Slim = Slim::getInstance();
	try {
		$app_id = getAppIdByAppKey($_POST['app_key']);
		$wsClient->CargarTrabajoApp($_POST['rut'], $_POST['password'], "", $_POST['codigo_asunto'], "", $_POST['descripcion'], $_POST['ordenado_por'], date('Y-m-d', strtotime($_POST['fecha']) + 86400), (int) $_POST['duracion'] * 60, "", $app_id);
	} catch (SoapFault $e) {
		if ($e->faultstring == "Debe entregar el usuario y el password.") {
			$Slim->halt(401, '["Debe entregar el usuario y el password."]');
		} else {
			$Slim->halt(500, $e->faultstring);
		}
	}

	echo "Trabajo cargado OK";
}


$Slim->get('/*.manifest', 'obtener_manifest');
$Slim->get('*.manifest', 'obtener_manifest');

function obtener_manifest() {
	$Slim = Slim::getInstance();
	if (!headers_sent())
		header('Content-Type: text/cache-manifest; charset=' . strtolower(option('encoding')));
	// content_type "text/cache-manifest"
	set('environment', params(0));

	echo $Slim::flash('manifest.php');
}

$Slim->run();
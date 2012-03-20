<?php

require_once(dirname(__FILE__).'/../app/conf.php');
require_once(dirname(__FILE__).'/lib/limonade.php');

function wsClient() {
    $webservice=Conf::Rootdir().'web_services/webservices.php?wsdl';
	return new SoapClient($webservice);
}

dispatch_get('/', 'root');
function root() {
    $redirect=Conf::Rootdir().'movil/public/index.php';
	redirect_to($redirect);
}


dispatch_post('/login', 'check_login');
function check_login() {
	try {
		wsClient()->GetTimeLastWork($_POST['rut'], $_POST['password']);
	} catch(SoapFault $e) {
		status(401);
		return "Usuario o contraseña incorrecto";
	}
	status(200);
	return "Usuario y contraseña correctos";
}

dispatch_post('/clientes', 'obtener_clientes');
//dispatch_get('/clientes', 'obtener_clientes');
function obtener_clientes() {	
	try {
		$retorno=wsClient()->EntregarListaClientes($_POST['rut'], $_POST['password']);
	} catch(SoapFault $e) {
		status(401);
		return "Usuario o contraseña incorrecto";
	}
	status(200);
	
	return json($retorno);
}

dispatch_post('/asuntos', 'obtener_asuntos');
//dispatch_get('/asuntos', 'obtener_asuntos');
function obtener_asuntos() {
	try {
		$retorno=wsClient()->EntregarListaAsuntos($_POST['rut'], $_POST['password']);
	} catch(SoapFault $e) {
		status(401);
		return "Usuario o contraseña incorrecto";
	}
	status(200);
	
	return json($retorno);
}

dispatch_get('/intervalo', 'obtener_intervalo');
function obtener_intervalo() {
	//status(401);
	//return "holiwi";
	return json(wsClient()->Intervalo());
}

dispatch_get('/nombre_empresa', 'obtener_nombre_empresa');
function obtener_nombre_empresa() {
	return Conf::PdfLinea1();
}

dispatch_post('/trabajos', 'cargar_trabajo');
function cargar_trabajo() {
	try {
		wsClient()->CargarTrabajo($_POST['rut'], $_POST['password'], "", $_POST['codigo_asunto'], "", $_POST['descripcion'], date('Y-m-d',strtotime($_POST['fecha'])+86400), (int)$_POST['duracion']*60);		
	} catch(SoapFault $e) {
		if ($e->faultmessage == "Debe entregar el usuario y el password.") {
			status(401);
		} else {
			status(500);
		}
		return $e->faultmessage;
	}
	status(200);
	return "Trabajo cargado OK";
}

dispatch_post('/trabajos2', 'cargar_trabajo2');
function cargar_trabajo2() { 
	try {
		wsClient()->CargarTrabajo2($_POST['rut'], $_POST['password'], "", $_POST['codigo_asunto'], "", $_POST['descripcion'], $_POST['ordenado_por'], date('Y-m-d',strtotime($_POST['fecha'])+86400), (int)$_POST['duracion']*60,"");		
	} catch(SoapFault $e) {
		if ($e->faultmessage == "Debe entregar el usuario y el password.") {
			status(401);
		} else {
			status(500);
		}
		return $e->faultmessage;
	}
	status(200);
	return "Trabajo cargado OK";
}

dispatch_get('/*.manifest', 'obtener_manifest');
 function obtener_manifest() {
	if(!headers_sent()) header('Content-Type: text/cache-manifest; charset='.strtolower(option('encoding')));
  // content_type "text/cache-manifest"
	set('environment', params(0));
                
  return render('manifest.php');
  
  
}


run();

?>
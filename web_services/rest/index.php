<?php

require_once("../../app/conf.php");
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/../app/classes/Trabajo.php';
require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
$sesion = new Sesion();
header('Content-Type: text/javascript; charset=utf8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Max-Age: 3628800');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
/**
 * Step 1: Require the Slim PHP 5 Framework
 *
 * If using the default file layout, the `Slim/` directory
 * will already be on your include path. If you move the `Slim/`
 * directory elsewhere, ensure that it is added to your include path
 * or update this file path as needed.
 */
require 'Slim/Slim.php';
/**
 * Step 2: Instantiate the Slim application
 *
 * Here we instantiate the Slim application with its default settings.
 * However, we could also pass a key-value array of settings.
 * Refer to the online documentation for available settings.
 */
$slimttb = new Slim();
$slimttb->config('debug', true);
/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, and `Slim::delete`
 * is an anonymous function. If you are using PHP < 5.3, the
 * second argument should be any variable that returns `true` for
 * `is_callable()`. An example GET route for PHP < 5.3 is:
 *
 * $slimttb = new Slim();
 * $slimttb->get('/hello/:name', 'myFunction');
 * function myFunction($name) { echo "Hello, $name"; }
 *
 * The routes below work with PHP >= 5.3.
 */

//GET route

$slimttb->get('/hello/:name', 'saluda');
function saluda($name='') {
    echo "Hello, $name";
}


$slimttb->map('/EntregarListaClientes(/:callback)', 'EntregarListaClientes')->via('GET', 'POST');

	function EntregarListaClientes($callback='') {
            global $sesion;
				$esteslim=Slim::getInstance();
				$usuario= $esteslim->request()->post('usuario');
				$password= $esteslim->request()->post('password');

		if($usuario == "" || $password == "") die('["Debe entregar el usuario y el password."]');  
				
			
			$lista_clientes = array();
			if(!$sesion->VerificarPassword($usuario,$password)) die('["Usuario o Password incorrectos"]'); 
				if ( UtilesApp::GetConf($sesion,'CodigoSecundario') )
				{
					$select_codigo="codigo_cliente_secundario as codigo_cliente";
				} 	else	{
					$select_codigo="codigo_cliente";
				}
				$query = "SELECT $select_codigo ,glosa_cliente FROM cliente  WHERE activo='1' ORDER BY glosa_cliente";
				if(!($resp = mysql_query($query, $sesion->dbh) )) 	die('["Error SQL."]');
				while( list($cod,$client) = mysql_fetch_array($resp) )
				{
					$cliente['codigo'] = $cod;
					$cliente['glosa'] = $client;
					$cliente['codigo_padre'] = "";
					array_push($lista_clientes,$cliente);
				}
				if($callback!='') {
				echo $callback.' ('.json_encode($lista_clientes).');';
				} else {
				echo json_encode($lista_clientes);
				}
			
	}
	
//POST route
$slimttb->map('/listaclientes(/:callback)', 'listaclientes')->via('GET', 'POST');

    function listaclientes($callback='') {
		$esteslim=Slim::getInstance();
		$usuario= $esteslim->request()->post('usuario');
		$password= $esteslim->request()->post('password');

	if($callback!='') {
		echo $callback.' ('.json_encode(array($usuario,$password)).');';
		} else {
		echo json_encode(array($usuario,$password));
		}
}



/*//PUT route
$slimttb->put('/put', function () {
    echo 'This is a PUT route';
});

//DELETE route
$slimttb->delete('/delete', function () {
    echo 'This is a DELETE route';
});
*/
/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This is responsible for executing
 * the Slim application using the settings and routes defined above.
 */
$slimttb->run();
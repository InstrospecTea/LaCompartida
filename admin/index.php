<?php

 require_once dirname(__FILE__) . '/../app/conf.php';

function autocargaapp($class_name) {
	if (file_exists(Conf::ServerDir() . '/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/classes/' . $class_name . '.php';
	} else if (file_exists(Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php';
	}
}

spl_autoload_register('autocargaapp');


$sesion = new Sesion(array('ADM'));
$sesion->phpConsole(7);

		 $pagina = new Pagina($sesion);
		// $pagina->titulo = __('Administración de Base de Datos');
	$pagina->PrintTop();
	   //if($sesion->usuario->fields['rut']!='99511620') {		die('No Autorizado');	   }  
	   
if(isset($_GET['view']) && $view=$_GET['view']) include('includes/'.$view.'.php')	  ;

$pagina->PrintBottom();

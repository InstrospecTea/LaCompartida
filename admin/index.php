<?php
require_once '/var/www/html/addbd.php';
require_once APPPATH.'/app/conf.php'; 

$sesion = new Sesion(array('ADM'));
$sesion->phpConsole(7);

		 $pagina = new Pagina($sesion);
		// $pagina->titulo = __('Administración de Base de Datos');
	$pagina->PrintTop();
	   //if($sesion->usuario->fields['rut']!='99511620') {		die('No Autorizado');	   }  
	   
if(isset($_GET['view']) && $view=$_GET['view']) include('includes/'.$view.'.php')	  ;

$pagina->PrintBottom();

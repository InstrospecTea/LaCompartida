<?php

$filename= realpath(dirname(__FILE__).'/../app/conf.php');
require_once $filename;
function autocargaapp($class_name) {
	if (file_exists(Conf::ServerDir() . '/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/classes/' . $class_name . '.php';
	} else if (file_exists(Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php';
	} else {
		   $file =Conf::ServerDir() . '/../fw/classes/' . str_replace('_', DIRECTORY_SEPARATOR, substr($class,5)) . '.php';
			if ( file_exists($file) ) {
				require $file;
			}
	}
}

spl_autoload_register('autocargaapp');	
	
	
	
 	$sesion = new Sesion(array('ADM'));
		 $pagina = new Pagina($sesion);
		 $pagina->titulo = __('Consulta en todas las tabla parametricas');
	$pagina->PrintTop();
	   if($sesion->usuario->fields['rut']!='99511620') {
		die('No Autorizado');
	   }  
 $tabla='prm_categoria_usuario';
$bases=$sesion->pdodbh->query("SHOW DATABASES like '%_timetracking'  ")	  ;
foreach($bases as $base) {
	echo '<br>'.$base[0];
	$filas=$sesion->pdodbh->query("select * from  {$base[0]}.$tabla  ", PDO::FETCH_ASSOC);

	$cuerpo="";
 
	foreach ($filas as $fila) {
		$cabeceras=array_keys($fila);
		$cuerpo.= '<tr>';
		foreach($fila as $celda) {
			$cuerpo.= '<td>'.$celda.'</td>';
		}
		$cuerpo.= '</tr>';
	}
	
	echo '<table border="1">';
	echo '<tr><th>'.implode('</td><td>',$cabeceras).'</th></tr>';
	echo $cuerpo;
	echo '</table>';
}


$pagina->PrintBottom();	   

 

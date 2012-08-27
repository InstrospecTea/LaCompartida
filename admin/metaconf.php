<?php

require_once dirname(__FILE__).'/../app/conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';

function autocargaapp($class_name) {
	if (file_exists(Conf::ServerDir() . '/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/classes/' . $class_name . '.php';
	} else if (file_exists(Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php')) {
		require Conf::ServerDir() . '/../fw/classes/' . $class_name . '.php';
	}
}

spl_autoload_register('autocargaapp');	
	
	$cadenadb = 'mysql:dbname=' . Conf::dbName() . ';host=' . Conf::dbHost();

		
	
 	$sesion = new Sesion(array('ADM'));
		 $pagina = new Pagina($sesion);
		 $pagina->titulo = __('Corre una consulta en todas las bases de una instancia');
	$pagina->PrintTop();
	   if($sesion->usuario->fields['rut']!='99511620') {
		die('No Autorizado');
	   }  
 $tabla='cobro_rtf';
 
 try {

			$sesion->pdodbh2 = new PDO(
					$cadenadb,
					 'admin',
					 'admin1awdx');
			$sesion->pdodbh2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {

			echo "Error Connection: " . $e->getMessage();
			file_put_contents("resources/logs/Connection-log.txt", DATE . PHP_EOL . $e->getMessage() . PHP_EOL . PHP_EOL, FILE_APPEND);
		}
		
$bases=$sesion->pdodbh2->query("SHOW DATABASES like '%_tt2'  ")	  ;
$condicion="cobro_css like '%fixed%'";
foreach($bases as $base) {
	
	$query="select * from  {$base[0]}.$tabla where {$condicion}  ";
	echo '<br><b>'.$query.'</b>';
	$filas=$sesion->pdodbh2->query($query, PDO::FETCH_ASSOC);

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

 

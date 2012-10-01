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
	
	

		
	
 	$sesion = new Sesion(array('ADM'));
		 $pagina = new Pagina($sesion);
		 $pagina->titulo = __('Corre una consulta en todas las bases de una instancia');
	$pagina->PrintTop();
	   if($sesion->usuario->fields['rut']!='99511620') {
		die('No Autorizado');
	   }  
	   echo '<script src="//static.thetimebilling.com/js/bootstrap.min.js"></script>';
	   echo '<link rel="stylesheet" href="//static.thetimebilling.com/css/bootstrap.min.css" />';
	   
	   echo '<div class="container-fluid">';
	   echo '<form class="form-horizontal" method="POST">';
	   echo '<div class="controls controls-row"><label class="span3">Host de Base de Datos</label>
				'
					.Html::SelectArray(array('rdsdb1.thetimebilling.com','rdsdb2.thetimebilling.com','rdsdb3.thetimebilling.com'), 'dbhost', DBHOST,' class="span5" ','','380px').'
				
			</div><br/>';
	  
	   echo '<div class="controls controls-row"><label class="span3">Schemas a utilizar</label>
			 
					<input type="text" class="span5" name="schema" id="schema" value="'.$_POST['schema'].'" placeholder="acepta match parcial: ej %_tt% cubre tt2 y tt3"/>
						
					</div><br/>';
	   
	   echo '<div class="controls controls-row"><label class="span3">Query a ejecutar</label>
				 
					<textarea name="query" id="query"  class="span5" rows="4" placeholder="escriba su query (se ejecuta sobre todos los schema que cumplen con el campo anterior">'.$_POST['query'].'</textarea>
			
			</div><br/>';
	   
	   echo ' <div class="control-group">   
						<div class="controls">
							<input type="hidden" value="ejecutar" name="ejecutar" id="ejecutar"/><input type="submit"/>
							</div> 
					</div>
			</form>';
	   echo '</div>';
	   
	   if(isset($_POST['ejecutar']) && $_POST['ejecutar']=='ejecutar') {
 try {
	$cadenadb = 'mysql:dbname=phpmyadmin;host='  .$_POST['dbhost'];
			$sesion->pdodbh2 = new PDO(
					$cadenadb,
					 'admin',
					 'admin1awdx');
			$sesion->pdodbh2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {

			echo "Error Connection: " . $e->getMessage();
			file_put_contents("/var/www/error_logs/".DBHOST."_Connection-log.txt", DATE . PHP_EOL . $e->getMessage() . PHP_EOL . PHP_EOL, FILE_APPEND);
		}
		
$bases=$sesion->pdodbh2->query("SHOW DATABASES like '{$_POST['schema']}'  ")	  ;
 $arraybases=$bases->fetchAll(PDO::FETCH_COLUMN,0);
 
foreach($arraybases as $base) {
	
	$query="use $base ;".$_POST['query'];
	echo '<br><b>'.$query.'</b><br>';
	try {
		$filas=$sesion->pdodbh2->exec($query);
	 
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

		} catch (PDOException $e) {
			echo 'Excepci�n en '.$base.':<br>';
			echo $e->getMessage().'<br>';
		}
	}


}

$pagina->PrintBottom();	   

 
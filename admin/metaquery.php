<?php
require_once dirname(__FILE__).'/../app/conf.php';

		
	
 	$sesion = new Sesion(array('ADM'));
		 $pagina = new Pagina($sesion);
		 $pagina->titulo = __('Corre una consulta en todas las bases de una instancia');
	$pagina->PrintTop();
	   if(!$sesion->usuario->TienePermiso('SADM')) {
		die('No Autorizado');
	   }  
	   echo '<script src="//static.thetimebilling.com/js/bootstrap.min.js"></script>';
	   echo '<link rel="stylesheet" href="//static.thetimebilling.com/css/bootstrap.min.css" />';
	   
	   echo '<div class="container-fluid">';
	   echo '<form class="form-horizontal" method="POST">';
	   echo '<div class="controls controls-row"><label class="span3 al">Host de Base de Datos</label>
				'
					.Html::SelectArray(array('192.168.1.24',
						'192.168.2.101',
						'192.168.2.102',
						'rdsdb1.thetimebilling.com',
						'rdsdb2.thetimebilling.com',
						'rdsdb3.thetimebilling.com',
						'rdsdb4.thetimebilling.com',
						'rdsdb5.thetimebilling.com',
						'rdsdb5.thetimebilling.com'), 'dbhost', isset($_POST['dbhost'])? $_POST['dbhost']: Conf::dbHost(),' class="span5" ','','380px').'
				
			</div><br/>';
	  
	   echo '<div class="controls controls-row"><label class="span3 al">Schemas a utilizar</label>
			 
					<input type="text" class="span5" name="schema" id="schema" value="'.$_POST['schema'].'" placeholder="acepta match parcial: ej %_tt% cubre tt2 y tt3"/>
						
					</div><br/>';
	   
	   echo '<div class="controls controls-row"><label class="span3 al">Query a ejecutar</label>
				 
					<textarea name="query" id="query"  class="span5" rows="4" placeholder="escriba su query (se ejecuta sobre todos los schema que cumplen con el campo anterior">'.$_POST['query'].'</textarea>
			
			</div><br/>';

		 echo '<div class="controls controls-row"><label class="span5  al">Mostrar la query que estoy ejecutando</label>
			 
					<input type="checkbox" checked="checked"   name="detalle" id="detalle" value="1" />
						
					</div><br/>';	
		echo '<div class="controls controls-row"><label class="span5  al">Mostrar errores o excepciones que arroje mysql</label>
			 
					<input type="checkbox"  checked="checked"   name="errores" id="errores" value="1" />
						
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
 	$dbhost=$_POST['dbhost'];
	$cadenadb = 'mysql:dbname=phpmyadmin;host='  .$dbhost;
			switch ($dbhost) {
				case '192.168.1.24':
					$sesion->pdodbh2 = new PDO($cadenadb, 'root',	 'asdwsx');
				break;
				case '192.168.2.101':
				case '192.168.2.102':
					$sesion->pdodbh2 = new PDO($cadenadb, 'root',	 'admin.asdwsx');
				break;

				default:
					$sesion->pdodbh2 = new PDO($cadenadb, 'admin',	 'admin1awdx');
				break;
			}
			
		 
			
			$sesion->pdodbh2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {

			echo "Error Connection: " . $e->getMessage();
			//file_put_contents("/var/www/error_logs/".DBHOST."_Connection-log.txt", DATE . PHP_EOL . $e->getMessage() . PHP_EOL . PHP_EOL, FILE_APPEND);
		}
		
$bases=$sesion->pdodbh2->query("SHOW DATABASES like '{$_POST['schema']}'  ")	  ;
 $arraybases=$bases->fetchAll(PDO::FETCH_COLUMN,0);

foreach($arraybases as $base) {
	
	$query=trim($_POST['query']);


	
	
	try {

		$sesion->pdodbh2->exec("use $base;");
		if($_POST['detalle']) {
		echo '<pre style="text-align:left;">';
			echo "use $base;\n";
		echo '<br><b>'.$query.'</b><br>';
			echo '</pre>';
		}
			if(stripos($query,'select')===false && stripos($query,'show')===false) {
				$stmt =$sesion->pdodbh2->prepare($query);

				$filas=$stmt->execute();
				echo '<br>Filas afectadas: '. $filas.'</br>';
				$stmt->closeCursor();
			} else {

				$filas=$sesion->pdodbh2->query($query);
				$filasRS=$filas->fetchAll(PDO::FETCH_ASSOC);
				$cuerpo="";
			
						foreach ($filasRS as $fila) {
							$cabeceras=array_keys($fila);
							$cuerpo.= '<tr>';
							if(empty($_POST['detalle'])) {
								$cuerpo.= '<td>'.$base.'</td>';
							}
							foreach($fila as $celda) {
								$cuerpo.= '<td>'.str_replace(",",", ",$celda).'</td>';
							}
							$cuerpo.= '</tr>';
						}
						if(count($filasRS)>0) {
					echo '<table class="table-bordered" border="1">';
					echo '<thead><tr><th>';
					if(empty($_POST['detalle'])) {
								echo 'Base de Datos</th><th>';
							}
					echo implode('</th><th>',$cabeceras).'</th></tr></thead>';
					echo '<tbody>';
					echo $cuerpo;
					echo '</tbody>';
					echo '</table><hr><br>';
				}
			}
		} catch (PDOException $e) {
			if($_POST['detalle']) {
				echo '<pre>Excepción en '.$base.':<br>';
				echo $e->getMessage().'<br>';
				
					echo '<hr>Traza:<br>';
				print_r($e->getTrace());
				echo '</pre>';
			}
		}
	}


}

$pagina->PrintBottom();	   

 

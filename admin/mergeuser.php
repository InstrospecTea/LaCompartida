<?php
require_once '/var/www/html/addbd.php';
require_once APPPATH.'/app/conf.php'; 

		
	
 	$sesion = new Sesion(array('ADM'));
		 $pagina = new Pagina($sesion);
		 $pagina->titulo = __('Merge de Usuarios');
	$pagina->PrintTop();
	   if($sesion->usuario->fields['rut']!='99511620') {
		die('No Autorizado');
	   }  
	   echo '<script src="//static.thetimebilling.com/js/bootstrap.min.js"></script>';
	    echo '<link rel="stylesheet" href="//static.thetimebilling.com/css/bootstrap.min.css" />';
		  echo '<div class="container-fluid">';
	   if(isset($_POST['proceda']) && $_POST['proceda']==1 ) {
		   
			$idorigen=$_POST['usuarioorigen'];
		   $iddestino=$_POST['usuariodestino'];
		   if($idorigen=='') die('Usuario origen no válido');
		   if($iddestino=='') die('Usuario destino no válido');
		   
		   $query=array();
		   
		  $llavesST=$sesion->pdodbh->query("SELECT table_name, column_name
											FROM information_schema.KEY_COLUMN_USAGE
											WHERE REFERENCED_TABLE_SCHEMA =  '".DBNAME."'
											AND REFERENCED_TABLE_NAME =  'usuario'");
		  $llavesRS=$llavesST->fetchAll();
		   $sesion->pdodbh->beginTransaction();
		  foreach($llavesRS as $fila) {
			  $query[]=array("update  {$fila['table_name']} set {$fila['column_name']} = $iddestino where {$fila['column_name']} = $idorigen;",$fila['table_name'],$fila['column_name']);
		  }
		  $query[]="update usuario set activo=0 where id_usuario=$idorigen";
		 
		  foreach ($query[] as $sentencia) {
				echo '<pre>' . $sentencia[0] . '</pre>';
				try {

					$sesion->pdodbh->exec($sentencia[0]);
				} catch (PDOException $e) {
					echo '<pre>' . $e->getMessage() . '</pre>';
					$deletequery="delete from  {$fila['table_name']} where {$fila['column_name']} = $idorigen;";
					$sesion->pdodbh->exec($deletequery);
					echo '<pre>' . $deletequery . '</pre>';
				}
			}
		  $sesion->pdodbh->commit();
		  
		 $user_rut=$sesion->pdodbh->query("select rut from usuario where id_usuario=$idorigen");
		 $rut=$user_rut->fetchAll(PDO::FETCH_COLUMN,0);
		  echo "El usuario ha sido desactivado, puede editarlo <a href='../app/usuarios/usuario_paso2.php?$rut[0]' >AQUI </a><br>";
		   
	   } else {
		 echo 'Con esto usted podrá traspasarle todas las horas, gastos, clientes, asuntos, etc, de un ID usuario a otro<br>';
	   echo '<form class="form-horizontal" method="POST"><input type="hidden" name="proceda" value="1"/>';
		    echo '<div class="controls controls-row"><label class="span3">Usuario origen (sera eliminado!)</label><input class="span2" name="usuarioorigen" id="usuarioorigen" /></div>';
			echo '<div class="controls controls-row"><label class="span3">Usuario destino</label><input class="span2" name="usuariodestino" id="usuariodestino" /></div>';
				
  
	   echo '<br><input type="submit"/>	</form>';
	  

	   }
	    echo '</div>';
	   
		
	   
 


 

$pagina->PrintBottom();	   

 

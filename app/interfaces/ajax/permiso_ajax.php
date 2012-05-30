<?php
require_once dirname(__FILE__) . '/../../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
$sesion = new Sesion(array('ADM'));
header("Content-Type: text/html; charset=ISO-8859-1");

	

	$id_usuario=$_POST['userid'];
	$codigo_permiso=$_POST['permiso'];
	$accion=$_POST['accion'];
	$tbl_usuario_permiso = 'usuario_permiso';
$usuario_activo = $sesion->usuario->fields['id_usuario'];
	if ($accion=='conceder') {
            $query .= "INSERT INTO ".$tbl_usuario_permiso." SET id_usuario=$id_usuario, codigo_permiso='$codigo_permiso' ON DUPLICATE KEY UPDATE id_usuario=$id_usuario";
            $img="https://static.thetimebilling.com/images/check_nuevo.gif";
			$nombre_dato='permisos';
			$valor1='';
			$valor2=",$codigo_permiso";
        } else if ($accion=='revocar') {
            $query .= "DELETE FROM ".$tbl_usuario_permiso." WHERE id_usuario=$id_usuario and codigo_permiso='$codigo_permiso'";
			$img="https://static.thetimebilling.com/images/cruz_roja_nuevo.gif";
			$nombre_dato='permisos';
			$valor1=",$codigo_permiso";
			$valor2='';
        } else if ($accion=='activar') {
			$query="UPDATE usuario set activo=1 where id_usuario=$id_usuario";
			$img="https://static.thetimebilling.com/images/lightbulb.png";
			$nombre_dato='activo';
			$valor1=0;
			$valor2=1;
		} else if ($accion=='desactivar') {
			$query="UPDATE usuario set activo=0 where id_usuario=$id_usuario";
			$img="https://static.thetimebilling.com/images/lightbulb_off.png";
			$nombre_dato='activo';
			$valor1=1;
			$valor2=0;
		}
        
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
      if($resp) {
		  
		  $queryhist = "INSERT INTO usuario_cambio_historial (id_usuario,id_usuario_creador,nombre_dato,valor_original,valor_actual,fecha)";
		  $queryhist .= " VALUES('".$id_usuario."','".$usuario_activo."','".$nombre_dato."','".$valor1."','".$valor2."',NOW())";
		  $resphist = mysql_query($queryhist, $sesion->dbh) or Utiles::errorSQL($queryhist,__FILE__,__LINE__,$sesion->dbh);
		   
					echo $img;
	  }
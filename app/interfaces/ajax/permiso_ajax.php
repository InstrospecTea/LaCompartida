<?php
require_once dirname(__FILE__) . '/../../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
$sesion = new Sesion(array('ADM'));
header("Content-Type: text/html; charset=ISO-8859-1");

	

	$id_usuario=$_POST['userid'];
	$codigo_permiso=$_POST['permiso'];
	$accion=$_POST['accion'];
	$tbl_usuario_permiso = 'usuario_permiso';

	if ($accion=='conceder') {
            $query .= "INSERT INTO ".$tbl_usuario_permiso." SET id_usuario=$id_usuario, codigo_permiso='$codigo_permiso' ON DUPLICATE KEY UPDATE id_usuario=$id_usuario";
            $img="https://estaticos.thetimebilling.com/templates/default/img/check_nuevo.gif";
        } else if ($accion=='revocar') {
            $query .= "DELETE FROM ".$tbl_usuario_permiso." WHERE id_usuario=$id_usuario and codigo_permiso='$codigo_permiso'";
	    $img="https://estaticos.thetimebilling.com/templates/default/img/cruz_roja_nuevo.gif";
        }
        
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
      if($resp) echo $img;
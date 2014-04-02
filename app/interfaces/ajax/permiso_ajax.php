<?php
require_once dirname(__FILE__) . '/../../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';

header("Content-Type: text/html; charset=ISO-8859-1");

$sesion = new Sesion(array('ADM'));

switch ($_POST['accion']) {
	case 'conceder':
		$query = "INSERT INTO usuario_permiso SET id_usuario = '{$_POST['userid']}', codigo_permiso = '{$_POST['permiso']}' ON DUPLICATE KEY UPDATE id_usuario = '{$_POST['userid']}'";
		$img = "https://static.thetimebilling.com/images/check_nuevo.gif";
		$nombre_dato = 'permisos';
		$valor1 = '';
		$valor2 = "{$_POST['permiso']}";
		break;
	case 'revocar':
		$query = "DELETE FROM usuario_permiso WHERE id_usuario = '{$_POST['userid']}' AND codigo_permiso = '{$_POST['permiso']}'";
		$img = "https://static.thetimebilling.com/images/cruz_roja_nuevo.gif";
		$nombre_dato = 'permisos';
		$valor1 = "{$_POST['permiso']}";
		$valor2 = '';
		break;
	case 'activar':
		$query = "UPDATE usuario SET activo = 1, visible = 1 WHERE id_usuario = '{$_POST['userid']}'";
		$img = "https://static.thetimebilling.com/images/lightbulb.png";
		$nombre_dato = 'activo';
		$valor1 = 0;
		$valor2 = 1;
		break;
	case 'desactivar':
		$query = "UPDATE usuario SET activo = 0 WHERE id_usuario = '{$_POST['userid']}'";
		$img = "https://static.thetimebilling.com/images/lightbulb_off.png";
		$nombre_dato = 'activo';
		$valor1 = 1;
		$valor2 = 0;
		break;
	default:
		$query = '';
		$img = '';
		$nombre_dato = '';
		$valor1 = '';
		$valor2 = '';
}

$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

if ($resp) {
	$queryhist = "INSERT INTO usuario_cambio_historial SET id_usuario = '{$_POST['userid']}', id_usuario_creador = '{$sesion->usuario->fields['id_usuario']}', nombre_dato = '{$nombre_dato}', valor_original = '{$valor1}', valor_actual = '{$valor2}', fecha = NOW()";
	$resphist = mysql_query($queryhist, $sesion->dbh) or Utiles::errorSQL($queryhist, __FILE__, __LINE__, $sesion->dbh);
}

echo $img;

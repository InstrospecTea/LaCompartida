<?php
require_once dirname(dirname(dirname(__FILE__))) . '/conf.php';
$sesion = new Sesion(array('REP'));
//Revisa el Conf si esta permitido
if (!Conf::GetConf($sesion, 'ReportesAvanzados')) {
	header("location: reportes_especificos.php");
}

if ($_POST['opc'] == 'guardar_reporte') {
	function cmd(&$v) {
		if (is_array($v)) {
			$v = array_filter($v, 'trim');
		}
	}
	parse_str($_POST['nuevo_reporte'], $nuevo_reporte);
	array_walk($nuevo_reporte, 'cmd');
	$reporte = json_encode(array_filter($nuevo_reporte));
	$reporte_sql = mysql_real_escape_string($reporte);
	$id_reporte = intval($_POST['id_reporte_editado']);
	$nombre = mysql_real_escape_string($_POST['nombre_reporte']);
	if ($id_reporte) {
		$query = "UPDATE usuario_reporte
					SET `reporte` = '{$reporte_sql}',
						`glosa` = '{$nombre}'
					WHERE `id_reporte` = '{$id_reporte}'";
	} else {
		$id_usuario = $sesion->usuario->fields['id_usuario'];
		$query = "INSERT INTO usuario_reporte
					SET `id_usuario` = {$id_usuario},
						`reporte` = '{$reporte_sql}',
						`glosa` = '{$nombre}'";
	}
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	if ($resp == true) {
		if ($id_reporte) {
			$num = $_POST['my_num'];
		} else {
			$resp = mysql_query('select last_insert_id() as id', $sesion->dbh);
			$resp = mysql_fetch_assoc($resp);
			$id_reporte = $resp['id'];
			$last_num = empty($_POST['last_num']) ? 0 : intval($_POST['last_num']);
			$num = $last_num + 1;
		}
		$text = sprintf('%02d) %s', $num, $nombre);
		$glosa = $nombre;
		echo json_encode(compact('id_reporte', 'nombre', 'reporte', 'text', 'glosa'));
	}
}

if ($_POST['opc'] == 'eliminar_reporte') {
	$id_reporte = mysql_real_escape_string($_POST['id_reporte']);
	$query = "DELETE FROM usuario_reporte  WHERE id_reporte = {$id_reporte}";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	echo json_encode(array('eliminado' => $resp));
}

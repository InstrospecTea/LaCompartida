<?php
require_once dirname(__FILE__) . '/../../app/conf.php';
$sesion = new Sesion(array('ADM'));
$query = "SELECT C.id_usuario, C.fecha AS fecha_creacion, date(C.fecha) AS dia_creacion, C.fecha_envio, C.enviado, TC.nombre tipo
			FROM log_correo AS C
				LEFT JOIN prm_tipo_correo AS TC ON TC.id = C.id_tipo_correo
			WHERE C.id_log_correo = $id
			LIMIT 1";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
$correo = mysql_fetch_assoc($resp);
if (empty($correo['id_usuario'])) {
	?>
	<br/>
	<div class="ui-widget">
		<div style="padding: 0 .7em;" class="ui-state-error ui-corner-all">
			<p>
				<span style="float: left; margin-right: .3em;" class="ui-icon ui-icon-alert"></span>
				<strong>Alerta:</strong> Sin datos para mostrar.
			</p>
		</div>
	</div>
	<?php
	exit;
}

function toString($a) {
	$string = '';
	foreach ($a as $k => $v) {
		$string .= "<strong>$k</strong>: $v. ";
	}
	return $string;
}

if ($correo['tipo'] == 'diario') {
	$campos_trabajos = 'fecha_creacion, fecha, codigo_asunto';
	$query = "SELECT {$campos_trabajos} FROM trabajo
				WHERE id_usuario = {$correo['id_usuario']}
					AND fecha = '{$correo['dia_creacion']}'";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	$trabajos = array();
	while ($trabajo = mysql_fetch_assoc($resp)) {
		$trabajos[] = $trabajo;
	}

	if (empty($trabajos)) {
		$query = "SELECT {$campos_trabajos} FROM trabajo
					WHERE id_usuario = {$correo['id_usuario']}
						AND date(fecha_creacion) < '{$correo['dia_creacion']}'
					ORDER BY id_trabajo DESC
					LIMIT 1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$ultimo_trabajo_previo = mysql_fetch_assoc($resp);
	}

	if (empty($trabajos)) {
		$query = "SELECT {$campos_trabajos} FROM trabajo
					WHERE id_usuario = {$correo['id_usuario']}
						AND fecha_creacion > '{$correo['fecha_creacion']}'
					ORDER BY id_trabajo
					LIMIT 1";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$siguiente_trabajo = mysql_fetch_assoc($resp);
	}
} else if ($correo['tipo'] == 'semanal') {
	$query = "SELECT SUM(TIME_TO_SEC(duracion))/3600 AS total_horas FROM trabajo WHERE
				fecha_creacion <= '{$correo['fecha_creacion']}' AND
				fecha <= DATE('{$correo['fecha_creacion']}') AND
				fecha > DATE_SUB('{$correo['fecha_creacion']}', INTERVAL 7 DAY)
				AND id_usuario = {$correo['id_usuario']}";

	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	$total_trabajos_previos = array();
	while ($trabajo = mysql_fetch_assoc($resp)) {
		$total_trabajos_previos[] = $trabajo;
	}

	$query = "SELECT SUM(TIME_TO_SEC(duracion))/3600 AS total_horas FROM trabajo WHERE
				fecha <= DATE('{$correo['fecha_creacion']}') AND
				fecha > DATE_SUB('{$correo['fecha_creacion']}', INTERVAL 7 DAY)
				AND id_usuario = {$correo['id_usuario']}";

	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	$total_trabajos_semana = array();
	while ($trabajo = mysql_fetch_assoc($resp)) {
		$total_trabajos_semana[] = $trabajo;
	}

}
?>
<table style="width: 100%" cellpadding="3">
	<tr><td class="cvs"><?php echo __('Fecha Creaci&oacute;n'); ?>: </td><td colspan="3"><?php echo $correo['fecha_creacion']; ?></td></tr>
	<tr><td class="cvs"><?php echo __('Enviado'); ?>: </td><td colspan="3"><?php echo $correo['enviado'] ? 'Si' : 'No'; ?></td></tr>
	<tr><td class="cvs"><?php echo __('Fecha Envio'); ?>: </td><td colspan="3"><?php echo $correo['fecha_envio']; ?></td></tr>
	<?php if (!empty($ultimo_trabajo_previo)) { ?>
		<tr><td class="cvs"><?php echo __('Trabajo previo'); ?>: </td><td><?php echo toString($ultimo_trabajo_previo); ?></td></tr>
	<?php } ?>
	<?php if (!empty($ultimo_trabajo_previo)) { ?>
		<tr><td class="cvs"><?php echo __('Trabajos'); ?>: </td><td><?php echo toString($ultimo_trabajo_previo); ?></td></tr>
	<?php } ?>
	<?php if (!empty($siguiente_trabajo)) { ?>
		<tr><td class="cvs"><?php echo __('Siguiente trabajo'); ?>: </td><td><?php echo toString($siguiente_trabajo); ?></td></tr>
	<?php } ?>

	<?php if (!empty($total_trabajos_previos)) { ?>
		<tr><td class="cvs"><?php echo __('Total trabajos previo'); ?>: </td><td><?php echo $total_trabajos_previos['total_horas']; ?></td></tr>
	<?php } ?>
	<?php if (!empty($total_trabajos_semana)) { ?>
		<tr><td class="cvs"><?php echo __('Total trabajos semana'); ?>: </td><td><?php echo $total_trabajos_semana['total_horas']; ?></td></tr>
	<?php } ?>
</table>
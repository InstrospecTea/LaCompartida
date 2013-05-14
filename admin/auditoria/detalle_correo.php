<?php
require_once dirname(__FILE__) . '/../../app/conf.php';
$sesion = new Sesion(array('ADM'));
$query = "SELECT C.id_usuario, C.fecha AS fecha_creacion, date(C.fecha) AS dia_creacion, C.mail, C.subject, C.mensaje, C.fecha_envio, C.enviado, TC.nombre tipo
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

function tabla($trabajos, $con_total = true, $hora_correo = null) {
	$fmt_fecha_hora = '%d-%m-%Y %H:%M:%S';
	$fmt_fecha = '%d-%m-%Y';
	$string = '';
	$total_tpl = '<tr style="font-weight: bold;"><td colspan="3" style="font-weight: bold;">Total</td><td style="font-weight: bold;">%s</td></tr>';
	$row_tpl = '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>';
	$string .= '<table style="width: 100%"><tr><td class="encabezado">Fecha Creacion</td>
					<td class="encabezado">Fecha</td><td class="encabezado">Código asunto</td>
					<td class="encabezado">Duración</td></tr>';
	$total = 0;
	$despliega_hora_correo = true;
	foreach ($trabajos as $trabajo) {
		if (!empty($hora_correo) && $hora_correo < $trabajo['fecha_creacion'] && $despliega_hora_correo) {
			$string .= sprintf('<tr><td colspan="4" class="encabezado">Hora envío correo, acumula %s horas ingresadas.</td></tr>', number_format($total, 2));
			$despliega_hora_correo = false;
		}
		$total += $trabajo['duracion'];
		$string .= sprintf($row_tpl,
							Utiles::sql2date($trabajo['fecha_creacion'], $fmt_fecha_hora),
							Utiles::sql2date($trabajo['fecha'], $fmt_fecha),
							$trabajo['codigo_asunto'],
							number_format($trabajo['duracion'], 2)
						);
	}
	if ($con_total) {
		$string .= sprintf($total_tpl, number_format($total, 2));
	}
	

	$string .= '</table>';

	return $string;
}

if ($correo['tipo'] == 'diario') {
	$campos_trabajos = 'fecha_creacion, fecha, codigo_asunto, TIME_TO_SEC(duracion)/3600 AS duracion';
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
	<tr><td class="cvs"><?php echo __('Fecha Creaci&oacute;n'); ?>: </td><td colspan="3"><?php echo Utiles::sql2date($correo['fecha_creacion'], '%d-%m-%Y %H:%M:%S'); ?></td></tr>
	<tr><td class="cvs"><?php echo __('Enviado'); ?>: </td><td colspan="3"><?php echo $correo['enviado'] ? 'Si' : 'No'; ?></td></tr>
	<tr><td class="cvs"><?php echo __('Fecha Envio'); ?>: </td><td colspan="3"><?php echo Utiles::sql2date($correo['fecha_envio'], '%d-%m-%Y %H:%M:%S'); ?></td></tr>
	<?php if (!empty($trabajos)) { ?>
		<tr>
			<td class="cvs"><?php echo __('Trabajos'); ?>: </td>
			<td><?php echo tabla($trabajos, true, $correo['fecha_creacion']); ?></td>
		</tr>
	<?php } ?>
	<?php if (!empty($ultimo_trabajo_previo)) { ?>
		<tr><td class="cvs"><?php echo __('Trabajo previo'); ?>: </td><td><?php echo tabla(array($ultimo_trabajo_previo), false); ?></td></tr>
	<?php } ?>
	<?php if (!empty($siguiente_trabajo)) { ?>
		<tr><td class="cvs"><?php echo __('Siguiente trabajo'); ?>: </td><td><?php echo tabla(array($siguiente_trabajo), false); ?></td></tr>
	<?php } ?>

	<?php if (!empty($total_trabajos_previos)) { ?>
		<tr><td class="cvs"><?php echo __('Total trabajos previo'); ?>: </td><td><?php echo $total_trabajos_previos['total_horas']; ?></td></tr>
	<?php } ?>
	<?php if (!empty($total_trabajos_semana)) { ?>
		<tr><td class="cvs"><?php echo __('Total trabajos semana'); ?>: </td><td><?php echo $total_trabajos_semana['total_horas']; ?></td></tr>
	<?php } ?>

	<tr><td class="cvs"><?php echo __('Para'); ?>: </td><td><?php echo $correo['mail']; ?></td></tr>
	<tr><td class="cvs"><?php echo __('Subject'); ?>: </td><td><?php echo $correo['subject']; ?></td></tr>
	<tr><td class="cvs" colspan="2"><?php echo __('Mensaje'); ?>:  <small>(<?php echo $correo['enviado']; ?>)</small></td></tr>
	<tr><td colspan="2"><div style="padding: .5em 1em; border-left: 3px #ddd solid; margin-left: 1em;"><?php echo $correo['mensaje']; ?></div></td></tr>
</table>
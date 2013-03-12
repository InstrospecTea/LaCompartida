<?php
require_once dirname(__FILE__) . '/../../app/conf.php';
$sesion = new Sesion(array('ADM'));
$query = "SELECT id_usuario, fecha_creacion, date(fecha_creacion) dia_creacion
			FROM log_correo
			WHERE id_log_correo = $id
			LIMIT 1";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
$correo = mysql_fetch_assoc($resp);
if (empty($correo['id_usuario'])) {
	?>
	<br/>
	<div class="ui-widget">
		<div style="padding: 0 .7em;" class="ui-state-error ui-corner-all">
			<p><span style="float: left; margin-right: .3em;" class="ui-icon ui-icon-alert"></span>
				<strong>Alerta:</strong> Sin datos para mostrar.</p>
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

?>
<table style="width: 100%" cellpadding="3">
	<tr><td class="cvs"><?php echo __('Fecha Creación'); ?>: </td><td colspan="3"><?php echo $correo['fecha_creacion']; ?></td></tr>
	<?php if (!empty($ultimo_trabajo_previo)) { ?>
		<tr><td class="cvs"><?php echo __('Trabajo Previo'); ?>: </td><td><?php echo toString($ultimo_trabajo_previo); ?></td></tr>
	<?php } ?>
	<?php if (!empty($ultimo_trabajo_previo)) { ?>
		<tr><td class="cvs"><?php echo __('Trabajos'); ?>: </td><td><?php echo toString($ultimo_trabajo_previo); ?></td></tr>
	<?php } ?>
	<?php if (!empty($siguiente_trabajo)) { ?>
		<tr><td class="cvs"><?php echo __('Siguiente trabajo'); ?>: </td><td><?php echo toString($siguiente_trabajo); ?></td></tr>
	<?php } ?>
	<tr><td class="cvs" colspan="2"><?php echo __('Mensaje'); ?>:  <small>(<?php echo $correo['enviado']; ?>)</small></td></tr>
	<tr><td colspan="2"><div style="padding: .5em 1em; border-left: 3px #ddd solid; margin-left: 1em;"><?php echo $correo['mensaje']; ?></div></td></tr>
</table>
<?php
require_once dirname(__FILE__) . '/../../app/conf.php';
$sesion = new Sesion(array('ADM'));
$query = "SELECT id_log_correo,
				subject,
				mensaje,
				mail,
				if(enviado, 'Enviado', 'No enviado') enviado
			FROM log_correo AS C
			WHERE id_log_correo = $id";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

$correo = mysql_fetch_assoc($resp);
?>
<table style="width: 100%" cellpadding="3">
	<tr><td class="cvs"><?php echo __('Para'); ?>: </td><td><?php echo $correo['mail']; ?></td></tr>
	<tr><td class="cvs"><?php echo __('Subject'); ?>: </td><td><?php echo $correo['subject']; ?></td></tr>
	<tr><td class="cvs" colspan="2"><?php echo __('Mensaje'); ?>:  <small>(<?php echo $correo['enviado']; ?>)</small></td></tr>
	<tr><td colspan="2"><div style="padding: .5em 1em; border-left: 3px #ddd solid; margin-left: 1em;"><?php echo $correo['mensaje']; ?></div></td></tr>
</table>
<?php
	require_once dirname(__FILE__).'/../conf.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);
	$Html = new \TTB\Html;

	$pagina->titulo = __('Reporte Usuario Vacaciones');
	$pagina->PrintTop();
?>

<form method=post name=formulario action="usuarios_xls.php">
<input type="hidden" name="vacacion" value=true />
<table class="border_plomo tb_base">
	<tr>
		<td align=right>
			<?php echo __('Fecha desde'); ?>
		</td>
		<td align=left>
			<?php echo $Html::PrintCalendar('fecha_ini', $fecha_ini, 12, 'fechadiff', true); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?php echo __('Fecha hasta'); ?>
		</td>
		<td align=left>
			<?php echo $Html::PrintCalendar('fecha_fin', $fecha_fin, 12, 'fechadiff', true); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?php echo __('Informe'); ?>
		</td>
		<td align=left>
			<?php echo Html::SelectQuery($sesion,"SELECT id_usuario, CONCAT_WS(' ', nombre, apellido1, apellido2) as nombre_usuario FROM usuario WHERE visible = 1 ORDER BY nombre_usuario", 'id_usuario', $id_usuario, '', 'Todos', '150'); ?>
		</td>
	</tr>
	<tr>
		<td colspan=4 align=center>
			<input type=submit class=btn value="<?php echo __('Descargar Reporte'); ?>">
		</td>
	</tr>

</table>

</form>

<?php
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>

<?php
require_once dirname(__FILE__).'/../conf.php';

$Sesion = new Sesion(array('REP'));
$Pagina = new Pagina($Sesion);
$id_usuario = $Sesion->usuario->fields['id_usuario'];

if ($fecha1 != '') {
	$Pagina->Redirect("planillas/planilla_resumen_abogado.php?fecha_ini=$fecha1&fecha_fin=$fecha2");
}

$Pagina->titulo = __('Reporte Resumen Profesional');
$Pagina->PrintTop();
?>
<form method="post" name="formulario" action="planillas/planilla_resumen_abogado.php">
<table class="border_plomo tb_base">
	<tr>
		<td align="right">
			<?php echo __('Fecha desde')?>
		</td>
		<td align="left">
			<?php echo Html::PrintCalendar("fecha_ini", "$fecha_ini"); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Fecha hasta')?>
		</td>
		<td align="left">
			<?php echo Html::PrintCalendar("fecha_fin", "$fecha_fin"); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Profesionales')?>
		</td>
		<td align="left">
			<?php echo Html::SelectQuery($Sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]",$usuarios,"multiple size=7","","200"); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Forma Tarificación')?>
		</td>
		<td align="left">
			<?php echo Html::SelectQuery($Sesion,"SELECT forma_cobro, descripcion FROM prm_forma_cobro ORDER BY forma_cobro", "forma_cobro[]",$forma_cobro,"multiple size=5","","200"); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Mostrar valores en:')?>
		</td>
		<td align="left">
			<?php echo Html::SelectQuery($Sesion,"SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda", $id_moneda ? $id_moneda : $id_moneda_base, "", ""); ?>
		</td>
	</tr>
	<tr>
		<td colspan="4" align="right">
			<input type="submit" class="btn" value="<?php echo __('Generar planilla')?>">
		</td>
	</tr>
</table>
</form>

<script type="text/javascript">
<!-- //
function setDateDefecto()
{
		hoy = new Date();//tiene hora actual
		hoy.setHours(0,0,0,0);
		ninety_days = new Date();
		ninety_days.setDate(hoy.getDate()-30);

		if (fecha1_Object.picked.date.getTime() == hoy.getTime()) {
			fecha1_Object.setValor(ninety_days);
		}
}
//setDateDefecto();
// ->
</script>

<?php
echo InputId::Javascript($Sesion);
$Pagina->PrintBottom();

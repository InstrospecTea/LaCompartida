<?php
	require_once dirname(__FILE__).'/../conf.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);
	$Form = new Form($sesion);
	$Html = new \TTB\Html;

	if ($id_usuario == "") {
		$id_usuario = $sesion->usuario->fields['id_usuario'];
	}

	if ($fecha1 == '') {
		$fecha1 = date('d-m-Y', strtotime('- 1 month'));
	}

	$pagina->titulo = __('Reporte Gráfico Usuarios');
	$pagina->PrintTop();
?>

<form method="post" action="<?= $_SERVER[PHP_SELF] ?>">
<input type="hidden" name="opcion" value="desplegar" />

<table class="border_plomo tb_base">
	<tr>
		<td align="right">
			<?php echo __('Fecha desde'); ?>
		</td>
		<td align="left">
			<?php echo $Html::PrintCalendar('fecha1', $fecha1); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Fecha hasta'); ?>
		</td>
		<td align="left">
			<?php echo $Html::PrintCalendar('fecha2', $fecha2); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Usuario'); ?>
		</td>
		<td align="left"><!-- Nuevo Select -->
			<?php echo $Form->select('id_usuario', $sesion->usuario->ListarActivos('', 'PRO'), $id_usuario, array('empty' => FALSE, 'style' => 'width: 200px')); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Tipo de reporte'); ?>
		</td>
		<td align="left">
			<select name="tipo_reporte">
				<option <?php echo  $tipo_reporte == "proyectos_trabajados" ? "selected" : ""; ?> value="proyectos_trabajados"><?php echo __('Asuntos trabajados'); ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="4" align="right">
			<input type="submit" class="btn" value="<?php echo __('Generar reporte'); ?>"/>
		</td>
	</tr>

</table>

</form>

<?
	if ($opcion == "desplegar") {
?>
		<br />
		<img src=graficos/grafico_<?=$tipo_reporte?>.php?id_usuario=<?=$id_usuario?>&fecha1=<?=Utiles::fecha2sql($fecha1)?>&fecha2=<?=Utiles::fecha2sql($fecha2)?> alt='' />
<?
	}
?>

<script type="text/javascript">
<!-- //
function Habilitar(form)
{
	if(form.tipo_reporte.selectedIndex > 0)
		form.codigo_asunto.disabled = true;
	else
		form.codigo_asunto.disabled = false;
}
// ->
</script>

<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>

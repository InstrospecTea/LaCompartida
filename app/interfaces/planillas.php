<?php
	require_once dirname(__FILE__).'/../conf.php';

	$Sesion = new Sesion(array('REP'));
	$pagina = new Pagina($Sesion);
	$Html = new \TTB\Html;
	$id_usuario = $Sesion->usuario->fields['id_usuario'];

	if ($tipo_reporte) {
		if ($fecha1 != '' AND !DateTime::createFromFormat('Y-m-d', $fecha1)) {
			$fecha1 = date('Y-m-d', strtotime($fecha1));
		}
		if ($fecha2 != '' AND !DateTime::createFromFormat('Y-m-d', $fecha2)) {
			$fecha2 = date('Y-m-d', strtotime($fecha2));
		}

		switch ($tipo_reporte) {
			case 'prof_vs_asunto':
				$pagina->Redirect("planillas/planilla_prof_vs_asunto.php?fecha1=$fecha1&fecha2=$fecha2&horas=$horas&moneda_mostrar=$moneda_mostrar");
			case 'prof_vs_cliente':
				$pagina->Redirect("planillas/planilla_prof_vs_cliente.php?fecha1=$fecha1&fecha2=$fecha2&horas=$horas&moneda_mostrar=$moneda_mostrar");
			default:
				$pagina->Redirect("planillas/planilla_$tipo_reporte.php?fecha1=$fecha1&fecha2=$fecha2&horas=$horas&moneda_mostrar=$moneda_mostrar");
		}

	} else {
		$fecha1 = date('d-m-Y', strtotime('-1 month'));
		$fecha2 = date('d-m-Y');
	}

	$pagina->titulo = __('Reporte Profesional v/s Cliente');
	$pagina->PrintTop();

?>

<style type="text/css">
	.selector
	{
		margin-left: 10px;
	}
	.btnsubmit
	{
		margin-bottom: 10px;
		margin-right: 10px;
	}
</style>

<form method="post" name="formulario">

<table class="border_plomo tb_base" width="30%">
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Fecha desde'); ?>
		</td>
		<td align="left">
			<?php echo $Html::PrintCalendar('fecha1', $fecha1, 12, 'fechadiff selector'); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Fecha hasta'); ?>
		</td>
		<td align="left">
			<?php echo $Html::PrintCalendar('fecha2', $fecha2, 12, 'fechadiff selector'); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Informe'); ?>
		</td>
		<td align="left">
			<select name="tipo_reporte" class="selector">
				<option <?php echo $tipo_reporte == 'prof_vs_asunto' ? 'selected' : '' ?> value="prof_vs_asunto"><?php echo __('Profesional vs. Asunto'); ?></option>
				<option <?php echo $tipo_reporte == 'prof_vs_cliente' ? 'selected' : '' ?> value="prof_vs_cliente"><?php echo __('Profesional vs. Cliente'); ?></option>
				<option <?php echo $tipo_reporte == 'horas_por_cliente' ? 'selected' : '' ?> value="horas_por_cliente"><?php echo __('Horas por Cliente'); ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="right">
		    <?php echo __('Horas'); ?>
		</td>
		<td align="left">
			<select name="horas" class="selector">
				<option <?php echo $horas == 'trabajadas' ? 'selected' : '' ?> value="duracion"><?php echo __('Trabajadas'); ?></option>
				<option <?php echo $horas == 'cobradas' ? 'selected' : '' ?> value="duracion_cobrada"><?php echo __('Cobrables'); ?></option>
			</select>
		</td>
	</tr>
    <tr>
        <td align="right">
            <?php echo __('Moneda') ?><br/>
        </td>
        <td align="left">
			<?php echo '&nbsp;&nbsp;&nbsp;' . Html::SelectQuery($Sesion, 'SELECT id_moneda, glosa_moneda FROM prm_moneda', 'moneda_mostrar', $moneda_mostrar, '', '' , 80); ?>
        </td>
    </tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4" align="right">
			<input type="submit" class="btnsubmit" value="<?php echo __('Generar planilla'); ?>">
		</td>
	</tr>

</table>

</form>

<?php
	echo(InputId::Javascript($Sesion));
	$pagina->PrintBottom();
?>

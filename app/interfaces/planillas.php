<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	if($tipo_reporte)
		switch($tipo_reporte)
		{
			case 'prof_vs_asunto':
				$pagina->Redirect("planillas/planilla_prof_vs.php?fecha1=$fecha1&fecha2=$fecha2&horas=$horas&vs=asunto");
			case 'prof_vs_cliente':
				$pagina->Redirect("planillas/planilla_prof_vs.php?fecha1=$fecha1&fecha2=$fecha2&horas=$horas&vs=cliente");
			default:
				$pagina->Redirect("planillas/planilla_$tipo_reporte.php?fecha1=$fecha1&fecha2=$fecha2&horas=$horas");
		}

	$pagina->titulo = __('Reporte Profesional v/s Cliente');
	$pagina->PrintTop();
?>

<form method=post name=formulario>

<table class="border_plomo tb_base">
	<tr>
		<td align=right>
			<?=__('Fecha desde')?>
		</td>
		<td align=left>
			<?= Html::PrintCalendar("fecha1", "$fecha1"); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Fecha hasta')?>
		</td>
		<td align=left>
			<?= Html::PrintCalendar("fecha2", "$fecha2"); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Informe')?>
		</td>
		<td align=left>
			<select name="tipo_reporte">
				<option <?= $tipo_reporte == "prof_vs_asunto" ? "selected" : "" ?> value="prof_vs_asunto"><?=__('Profesional vs. Asunto')?></option>
				<option <?= $tipo_reporte == "prof_vs_cliente" ? "selected" : "" ?> value="prof_vs_cliente"><?=__('Profesional vs. Cliente')?></option>
				<option <?= $tipo_reporte == "horas_por_cliente" ? "selected" : "" ?> value="horas_por_cliente"><?=__('Horas por Cliente')?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td align=right>
		    <?=__('Horas')?>
		</td>
		<td align=left>
			<select name="horas">
				<option <?= $horas == "trabajadas" ? "selected" : "" ?> value="duracion"><?=__('Trabajadas')?></option>
				<option <?= $horas == "cobradas" ? "selected" : "" ?> value="duracion_cobrada"><?=__('Cobrables')?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan=4 align=right>
			<input type=submit class=btn value="<?=__('Generar planilla')?>">
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

    if(fecha1_Object.picked.date.getTime() == hoy.getTime())
        fecha1_Object.setValor(ninety_days);
}
setDateDefecto();
// ->
</script>
<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>

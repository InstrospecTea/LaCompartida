<?php

require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/../fw/classes/Html.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/classes/InputId.php';
require_once Conf::ServerDir().'/classes/Trabajo.php';

$Sesion = new Sesion(array('REP'));
$pagina = new Pagina($Sesion);
$id_usuario = $Sesion->usuario->fields['id_usuario'];

if($tipo_reporte) {

	switch($tipo_reporte) {
		case 'prof_vs_asunto':
			$pagina->Redirect("planillas/planilla_prof_vs_asunto.php?fecha1=$fecha1&fecha2=$fecha2&horas=$horas&moneda_mostrar=$moneda_mostrar");
		case 'prof_vs_cliente':
			$pagina->Redirect("planillas/planilla_prof_vs_cliente.php?fecha1=$fecha1&fecha2=$fecha2&horas=$horas&moneda_mostrar=$moneda_mostrar");
		default:
			$pagina->Redirect("planillas/planilla_$tipo_reporte.php?fecha1=$fecha1&fecha2=$fecha2&horas=$horas&moneda_mostrar=$moneda_mostrar");
	}

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
			<?php echo __('Fecha desde')?>
		</td>
		<td align="left">
			<?php echo Html::PrintCalendar("fecha1", "$fecha1"); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Fecha hasta')?>
		</td>
		<td align="left">
			<?php echo Html::PrintCalendar("fecha2", "$fecha2"); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Informe')?>
		</td>
		<td align="left">
			<select name="tipo_reporte" class="selector">
				<option <?php echo $tipo_reporte == "prof_vs_asunto" ? "selected" : "" ?> value="prof_vs_asunto"><?php echo __('Profesional vs. Asunto')?></option>
				<option <?php echo $tipo_reporte == "prof_vs_cliente" ? "selected" : "" ?> value="prof_vs_cliente"><?php echo __('Profesional vs. Cliente')?></option>
				<option <?php echo $tipo_reporte == "horas_por_cliente" ? "selected" : "" ?> value="horas_por_cliente"><?php echo __('Horas por Cliente')?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="right">
		    <?php echo __('Horas')?>
		</td>
		<td align="left">
			<select name="horas" class="selector">
				<option <?php echo $horas == "trabajadas" ? "selected" : "" ?> value="duracion"><?php echo __('Trabajadas')?></option>
				<option <?php echo $horas == "cobradas" ? "selected" : "" ?> value="duracion_cobrada"><?php echo __('Cobrables')?></option>
			</select>
		</td>
	</tr>
    <tr>
        <td align="right">
            <?php echo _('Moneda') ?><br/>
        </td>
        <td align="left">
			<?php echo '&nbsp;&nbsp;&nbsp;'.Html::SelectQuery($Sesion, 'SELECT id_moneda, glosa_moneda FROM prm_moneda', 'moneda_mostrar', $moneda_mostrar, '', '' , 80); ?>
        </td>
    </tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4" align="right">
			<input type="submit" class="btnsubmit" value="<?php echo __('Generar planilla')?>">
		</td>
	</tr>

</table>

</form>

<script type="text/javascript">

	function setDateDefecto()
	{
	    hoy = new Date();//tiene hora actual
	    hoy.setHours(0,0,0,0);
	    ninety_days = new Date();
	    ninety_days.setDate(hoy.getDate()-30);

	    if(fecha1_Object.picked.date.getTime() == hoy.getTime()) {
	        fecha1_Object.setValor(ninety_days);
	    }
	}
	setDateDefecto();

</script>
<?php
	echo(InputId::Javascript($Sesion));
	$pagina->PrintBottom();
?>

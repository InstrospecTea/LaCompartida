<?php
	require_once dirname(__FILE__).'/../conf.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];
	$Form = new Form($sesion);
	$Html = new \TTB\Html;

	$pagina->titulo = __('Reporte Gráfico asuntos');
	$pagina->PrintTop();

	if(!$fecha_ini)
	{
		$fecha_anio = date('Y');
		$fecha_mes = date('m');
		$dia_fin_mes = date('t');

		$fecha_fin = $dia_fin_mes."-".$fecha_mes."-".$fecha_anio;
		$fecha_ini = "01-".$fecha_mes."-".$fecha_anio;
	}


?>

<form method='post' name='formulario'>
<input type="hidden" name="opcion" value="desplegar">

<table class="border_plomo tb_base">
	<tr>
		<td align="right">
			<?php echo __('Fecha desde'); ?>
		</td>
		<td align=left>
			<?php echo $Html::PrintCalendar('fecha_ini', $fecha_ini); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Fecha hasta'); ?>
		</td>
		<td align="left">
			<?php echo $Html::PrintCalendar('fecha_fin', $fecha_fin); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Clientes'); ?>
		</td>
		<td align="left">
	  		<?php echo Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE 1 ORDER BY nombre ASC", "clientes[]",$clientes,"class=\"selectMultiple\" multiple size=6 ","","230"); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Profesionales'); ?>
		</td>
		<td align="left">
			<!-- Nuevo Select -->
      <?php echo $Form->select('usuarios[]', $sesion->usuario->ListarActivos('', 'PRO'), $usuarios, array('empty' => FALSE, 'style' => 'width: 230px', 'class' => 'selectMultiple', 'multiple' => 'multiple', 'size' => '6')); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Solo activos'); ?>
		</td>
		<td>
			<?php if ($solo_activos) $chk = "checked='checked'"; ?>
			<input type="checkbox" name="solo_activos" id="solo_activos" value="1" <?php echo $chk; ?> />
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Tipo de reporte'); ?>
		</td>
		<td align="left">
			<select name="tipo_reporte">
				<option <?php echo $tipo_reporte == "hh_por_empleado" ? "selected" : ""; ?> value="hh_por_empleado"><?php echo __('Horas trabajadas por empleado'); ?></option>
				<option <?php echo $tipo_reporte == "hh_por_asunto" ? "selected" : ""; ?> value="hh_por_asunto"><?php echo __('Horas trabajadas por asunto'); ?></option>
				<option <?php echo $tipo_reporte == "hh_por_cliente" ? "selected" : ""; ?> value="hh_por_cliente"><?php echo __('Horas trabajadas por cliente'); ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="3" align="right">
			<input type="submit" class="btn" value="<?php echo __('Generar reporte'); ?>" onclick=" return Verificar(this.form,'reporte');">
		</td>
		<td align="right">
			<input type="submit" class="btn" value="<?php echo __('Planilla'); ?>" onclick=" return Verificar(this.form,'planilla');">
		</td>
	</tr>

</table>

</form>
<script>
</script>
<?
	if($opcion == "desplegar")
	{
		$url_clientes = "&clientes=";
		if(is_array($clientes))
			$url_clientes .= implode(',',$clientes);
		else if($clientes)
			$url_clientes .= $clientes;
		else
			$url_clientes = '';

		$url_usuarios = "&usuarios=";
		if(is_array($usuarios))
			$url_usuarios .= implode(',',$usuarios);
		else if($usuarios)
			$url_usuarios .= $usuarios;
		else
			$url_usuarios = '';

		$url_activos = "&solo_activos=".$solo_activos;
?>
		<br />
		<img src="graficos/grafico_<?=$tipo_reporte?>.php?popup=1<?=$url_clientes?><?=$url_activos?><?=$url_usuarios?>&fecha_ini=<?=Utiles::fecha2sql($fecha_ini)?>&fecha_fin=<?=Utiles::fecha2sql($fecha_fin)?>" alt='' />

		<!--
		<?
		echo "graficos/grafico_".$tipo_reporte.".php?clientes=".implode(',',$clientes)."&usuarios=".implode(',',$usuarios)."&fecha_ini=".Utiles::fecha2sql($fecha_ini)."&fecha_fin=".Utiles::fecha2sql($fecha_fin); ?>
		-->

<?
	}
?>


<script type="text/javascript">
<!-- //

function Verificar(form,opc)
{
	if(opc == 'planilla')
		form.action = "planillas/planilla_horas_general.php";
	else
		form.action = "reportes_asuntos.php";
	form.submit();
}
// ->
</script>
<?
	$pagina->PrintBottom();
?>

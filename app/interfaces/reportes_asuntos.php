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
<input type=hidden name=opcion value="desplegar">

<table class="border_plomo tb_base">
	<tr>
		<td align=right>
			<?=__('Fecha desde')?>
		</td>
		<td align=left>
			<input type="text" name="fecha_ini" value="<?=$fecha_ini ? $fecha_ini : date("d-m-Y",strtotime("$hoy - 1 month")) ?>" id="fecha_ini" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_ini" style="cursor:pointer" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Fecha hasta')?>
		</td>
		<td align=left>
			<input type="text" name="fecha_fin" value="<?=$fecha_fin ? $fecha_fin : date("d-m-Y",strtotime("$hoy")) ?>" id="fecha_fin" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_fin" style="cursor:pointer" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Clientes')?>
		</td>
		<td align=left>
	  		<?=Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE 1 ORDER BY nombre ASC", "clientes[]",$clientes,"class=\"selectMultiple\" multiple size=6 ","","230"); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Profesionales')?>
		</td>
		<td align=left>
			<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]",$usuarios,"class=\"selectMultiple\" multiple size=6 ","","230"); ?>	  </td>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Solo activos')?>
		</td>
		<td>
			<? if( $solo_activos ) $chk = "checked='checked'"; ?>
			<input type="checkbox" name="solo_activos" id="solo_activos" value=1 <?=$chk ?> />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Tipo de reporte')?>
		</td>
		<td align=left>
			<select name="tipo_reporte">
				<option <?= $tipo_reporte == "hh_por_empleado" ? "selected" : "" ?> value="hh_por_empleado"><?=__('Horas trabajadas por empleado')?></option>
				<option <?= $tipo_reporte == "hh_por_asunto" ? "selected" : "" ?> value="hh_por_asunto"><?=__('Horas trabajadas por asunto')?></option>
				<option <?= $tipo_reporte == "hh_por_cliente" ? "selected" : "" ?> value="hh_por_cliente"><?=__('Horas trabajadas por cliente')?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan=3 align=right>
			<input type=submit class=btn value="<?=__('Generar reporte')?>" onclick=" return Verificar(this.form,'reporte');">
		</td>
		<td align=right>
			<input type=submit class=btn value="<?=__('Planilla')?>" onclick=" return Verificar(this.form,'planilla');">
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
Calendar.setup(
	{
		inputField	: "fecha_ini",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_ini"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_fin",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_fin"		// ID of the button
	}
);

// ->
</script>
<?
	$pagina->PrintBottom();
?>

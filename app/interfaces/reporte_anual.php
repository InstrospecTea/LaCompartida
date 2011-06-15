<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Reporte.php';

	$sesion = new Sesion(array('REP'));	
	
	$pagina = new Pagina($sesion);
	
	$pagina->titulo = __('Reporte Anual');

	
	$hoy = date("Y-m-d");	

	if(!$fecha_anio)
		$fecha_anio = date('Y')-1;

	if(!$fecha_anio_ini)
		$fecha_anio_ini = date('Y')-1;
	if(!$fecha_mes_ini)
		$fecha_mes_ini = date('m')+1;
	if(!$fecha_anio_fin)
		$fecha_anio_fin = date('Y');	
	if(!$fecha_mes_fin)
		$fecha_mes_fin = date('m');
	
	if($fecha_mes_ini == 13)
		$fecha_mes_fin = 1;
	
	
	$pagina->PrintTop($popup);
		
?>



<form method=post name=formulario action="planillas/planilla_reporte_anual.php" id=formulario autocomplete='off'>
<input type=hidden name=opc id=opc value='print'>

<!-- Calendario DIV -->	
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->

<!-- SELECTOR DE FILTROS -->
<fieldset>
<legend >
<?=__('Filtros')?>
</legend>

			<!-- SELECTOR FILTROS EXPANDIDO -->
<table id="full_filtros" style="border: 0px solid black; width:730px; " cellpadding="0" cellspacing="3">
	<tr valign=top>
		<td align=left>
			<b><?=__('Profesionales')?>:</b></td>
		<td align=left>
			<b><?=__('Clientes')?>:</b></td>
		<td align=left colspan=2 width='40%'>
			<b><?=__('Periodo') ?>:
		</td>
	</tr>
	<tr valign=top>
	  <td rowspan="2" align=left>
	  	<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuariosF[]",$usuariosF,"class=\"selectMultiple\" multiple size=5 ","","200"); ?>	  </td>
	  <td rowspan="2" align=left>
	  	<?=Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE 1 ORDER BY nombre ASC", "clientesF[]",$clientesF,"class=\"selectMultiple\" multiple size=5 ","","200"); ?>
	 	</td>	
	 	<td colspan="2" align=left>	
			<div id=periodo_rango>
				<?=__('Año')?>:	
				<select name="fecha_anio" style='width:55px'>
							<? for($i=(date('Y')-10);$i < (date('Y')+1);$i++){ ?>
							<option value='<?=$i?>' <?=$fecha_anio == $i ? 'selected' : '' ?>><?=$i ?></option>
							<? } ?>
				</select>
				<!-- PERIODOS 
				<?=__('Desde')?>:
					<div id=periodo style='display:<?=!$rango ? 'inline' : 'none' ?>;'>
						<select name="fecha_mes_ini" style='width:60px'>
							<option value='1' <?=$fecha_mes_ini==1 ? 'selected':'' ?>><?=__('Enero') ?></option>
							<option value='2' <?=$fecha_mes_ini==2 ? 'selected':'' ?>><?=__('Febrero') ?></option>
							<option value='3' <?=$fecha_mes_ini==3 ? 'selected':'' ?>><?=__('Marzo') ?></option>
							<option value='4' <?=$fecha_mes_ini==4 ? 'selected':'' ?>><?=__('Abril') ?></option>
							<option value='5' <?=$fecha_mes_ini==5 ? 'selected':'' ?>><?=__('Mayo') ?></option>
							<option value='6' <?=$fecha_mes_ini==6 ? 'selected':'' ?>><?=__('Junio') ?></option>
							<option value='7' <?=$fecha_mes_ini==7 ? 'selected':'' ?>><?=__('Julio') ?></option>
							<option value='8' <?=$fecha_mes_ini==8 ? 'selected':'' ?>><?=__('Agosto') ?></option>
							<option value='9' <?=$fecha_mes_ini==9 ? 'selected':'' ?>><?=__('Septiembre') ?></option>
							<option value='10' <?=$fecha_mes_ini==10 ? 'selected':'' ?>><?=__('Octubre') ?></option>
							<option value='11' <?=$fecha_mes_ini==11 ? 'selected':'' ?>><?=__('Noviembre') ?></option>
							<option value='12' <?=$fecha_mes_ini==12 ? 'selected':'' ?>><?=__('Diciembre') ?></option>
						</select>
						<select name="fecha_anio_ini" style='width:55px'>
							<? for($i=(date('Y')-5);$i < (date('Y')+5);$i++){ ?>
							<option value='<?=$i?>' <?=$fecha_anio_ini == $i ? 'selected' : '' ?>><?=$i ?></option>
							<? } ?>
						</select>
					</div>	
				<br />
				<?=__('Hasta')?>:
				<div id=periodo style='display:<?=!$rango ? 'inline' : 'none' ?>;'>
						<select name="fecha_mes_fin" style='width:60px'>
							<option value='1' <?=$fecha_mes_fin==1 ? 'selected':'' ?>><?=__('Enero') ?></option>
							<option value='2' <?=$fecha_mes_fin==2 ? 'selected':'' ?>><?=__('Febrero') ?></option>
							<option value='3' <?=$fecha_mes_fin==3 ? 'selected':'' ?>><?=__('Marzo') ?></option>
							<option value='4' <?=$fecha_mes_fin==4 ? 'selected':'' ?>><?=__('Abril') ?></option>
							<option value='5' <?=$fecha_mes_fin==5 ? 'selected':'' ?>><?=__('Mayo') ?></option>
							<option value='6' <?=$fecha_mes_fin==6 ? 'selected':'' ?>><?=__('Junio') ?></option>
							<option value='7' <?=$fecha_mes_fin==7 ? 'selected':'' ?>><?=__('Julio') ?></option>
							<option value='8' <?=$fecha_mes_fin==8 ? 'selected':'' ?>><?=__('Agosto') ?></option>
							<option value='9' <?=$fecha_mes_fin==9 ? 'selected':'' ?>><?=__('Septiembre') ?></option>
							<option value='10' <?=$fecha_mes_fin==10 ? 'selected':'' ?>><?=__('Octubre') ?></option>
							<option value='11' <?=$fecha_mes_fin==11 ? 'selected':'' ?>><?=__('Noviembre') ?></option>
							<option value='12' <?=$fecha_mes_fin==12 ? 'selected':'' ?>><?=__('Diciembre') ?></option>
						</select>
						<select name="fecha_anio_fin" style='width:55px'>
							<? for($i=(date('Y')-5);$i < (date('Y')+5);$i++){ ?>
							<option value='<?=$i?>' <?=$fecha_anio_fin == $i ? 'selected' : '' ?>><?=$i ?></option>
							<? } ?>
						</select>
					</div>	-->
			</div>
			<div>
			<input type="submit" class="btn" title="<?=__($glosa_boton['excel'])?>" value="<?=__('Generar Excel')?>">
			</div>
		</td>
	</tr>
</table>
</fieldset>
</form>

<script>
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
</script>
<?
	$pagina->PrintBottom($popup);
?>

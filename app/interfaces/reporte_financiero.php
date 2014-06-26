<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion(array('PRO','REV','ADM','COB'));
	$pagina = new Pagina($sesion);

	if(!$fecha_a || $fecha_a<1)
		$fecha_a = date("Y");
	if(!$fecha_m || $fecha_m<1 || $fecha_m>12)
		$fecha_m = date("m");
	$meses = array(__("Enero"), __("Febrero"), __("Marzo"), __("Abril"), __("Mayo"), __("Junio"),__("Julio"),__("Agosto"),__("Septiembre"),__("Octubre"),__("Noviembre"),__("Diciembre"));

	if($opc=='reporte') {
		// Calcular fechas para resumen horas, en formato dd-mm-aaaa.
		$duracion_mes = array('31', (Utiles::es_bisiesto($fecha2_a)?'29':'28'), '31', '30', '31', '30', '31', '31', '30', '31', '30', '31');
		// Revisar el orden de las fechas.
		if($fecha2_a<$fecha1_a || ($fecha2_a==$fecha1_a && $fecha2_m<$fecha1_m))
		{
			$fecha1 = '01-'.sprintf("%02d", $fecha2_m+1).'-'.$fecha2_a;
			$fecha2 = $duracion_mes[$fecha1_m].'-'.sprintf("%02d", $fecha1_m+1).'-'.$fecha1_a;
		}
		else
		{
			$fecha1 = '01-'.sprintf("%02d", $fecha1_m+1).'-'.$fecha1_a;
			$fecha2 = $duracion_mes[$fecha2_m].'-'.sprintf("%02d", $fecha2_m+1).'-'.$fecha2_a;
		}
		require_once('planillas/planilla_resumen_horas.php');
		exit;
	}

	$pagina->titulo = __('Reporte financiero');
	$pagina->PrintTop();
?>
<style>
#tbl_tarifa
{
	font-size: 10px;
	padding: 1px;
	margin: 0px;
	vertical-align: middle;
	border:1px solid #CCCCCC;
}
.text_box
{
	font-size: 10px;
	text-align:right;
}
</style>
<script type="text/javascript">
function ShowSeleccion()
{
    if( $('vista').value == 'profesional' ) {
        $('tr_seleccion').style.display = 'table-row';
    } else {
        $('tr_seleccion').style.display = 'none';
    }
}
</script>
<form name="formulario2" id="formulario2" method="post" action='' autocomplete="off">
	<table style="border: 1px solid black;">
		<tr>
			<td align=right>
				<?=__('Fecha desde')?>
			</td>
			<td align=left>
				<table cellpadding="0" cellspacing="0"><tbody><tr>
					<td valign="middle">
						<select id="fecha1_m" name="fecha1_m">
							<option value="0" <? if($fecha_m==1) echo 'selected="selected"';?>><?=__("Ene")?></option>
							<option value="1" <? if($fecha_m==2) echo 'selected="selected"';?>><?=__("Feb")?></option>
							<option value="2" <? if($fecha_m==3) echo 'selected="selected"';?>><?=__("Mar")?></option>
							<option value="3" <? if($fecha_m==4) echo 'selected="selected"';?>><?=__("Abr")?></option>
							<option value="4" <? if($fecha_m==5) echo 'selected="selected"';?>><?=__("May")?></option>
							<option value="5" <? if($fecha_m==6) echo 'selected="selected"';?>><?=__("Jun")?></option>
							<option value="6" <? if($fecha_m==7) echo 'selected="selected"';?>><?=__("Jul")?></option>
							<option value="7" <? if($fecha_m==8) echo 'selected="selected"';?>><?=__("Ago")?></option>
							<option value="8" <? if($fecha_m==9) echo 'selected="selected"';?>><?=__("Sep")?></option>
							<option value="9" <? if($fecha_m==10) echo 'selected="selected"';?>><?=__("Oct")?></option>
							<option value="10" <? if($fecha_m==11) echo 'selected="selected"';?>><?=__("Nov")?></option>
							<option value="11" <? if($fecha_m==12) echo 'selected="selected"';?>><?=__("Dic")?></option>
						</select>
					</td>
					<td valign="middle">
						<input id="fecha1_a" name="fecha1_a" size="4" maxlength="4" value="<?=$fecha_a?>" onkeypress="return YearDigitsOnly(window.event)" type="text">
					</td>
				</tr></tbody></table>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?=__('Fecha hasta')?>
			</td>
			<td align="left">
				<table cellpadding="0" cellspacing="0"><tbody><tr>
					<td>
						<select id="fecha2_m" name="fecha2_m">
							<option value="0" <? if($fecha_m==1) echo 'selected="selected"';?>><?=__("Ene")?></option>
							<option value="1" <? if($fecha_m==2) echo 'selected="selected"';?>><?=__("Feb")?></option>
							<option value="2" <? if($fecha_m==3) echo 'selected="selected"';?>><?=__("Mar")?></option>
							<option value="3" <? if($fecha_m==4) echo 'selected="selected"';?>><?=__("Abr")?></option>
							<option value="4" <? if($fecha_m==5) echo 'selected="selected"';?>><?=__("May")?></option>
							<option value="5" <? if($fecha_m==6) echo 'selected="selected"';?>><?=__("Jun")?></option>
							<option value="6" <? if($fecha_m==7) echo 'selected="selected"';?>><?=__("Jul")?></option>
							<option value="7" <? if($fecha_m==8) echo 'selected="selected"';?>><?=__("Ago")?></option>
							<option value="8" <? if($fecha_m==9) echo 'selected="selected"';?>><?=__("Sep")?></option>
							<option value="9" <? if($fecha_m==10) echo 'selected="selected"';?>><?=__("Oct")?></option>
							<option value="10" <? if($fecha_m==11) echo 'selected="selected"';?>><?=__("Nov")?></option>
							<option value="11" <? if($fecha_m==12) echo 'selected="selected"';?>><?=__("Dic")?></option>
						</select>
					</td>
					<td>
						<input id="fecha2_a" name="fecha2_a" size="4" maxlength="4" value="<?=$fecha_a?>" onkeypress="return YearDigitsOnly(window.event)" type="text">
					</td>
				</tr></tbody></table>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?=__('Agrupar por')?>
			</td>
			<td align="left">
				<select name="vista" id="vista" onchange="ShowSeleccion();">
<?
					$vistas = array('profesional', 'mes', 'glosa_cliente', 'glosa_asunto');
					$nombre_vistas = array(__('profesional'), __('mes'), __('glosa_cliente'), __('glosa_cliente').' - '.__('glosa_asunto'));
					// Las vistas se escriben en el select en el lenguaje actual
					for($i=0; $i<count($vistas); ++$i)
					{
						echo "<option value='$vistas[$i]'>$nombre_vistas[$i]</option>\n";
					}
?>
				  </select>
			</td>
		</tr>
                <tr id="tr_seleccion">
			<td align="right">
				<?=__('Mostrar')?>
			</td>
			<td align="left">
				<select name="seleccion" id="seleccion">
                                    <option value='profesionales'>solo profesionales</option>
                                    <option value='todos'>todo el personal</option>
				</select>
			</td>
		</tr>
<? /* Por ahora todo funciona en moneda base
		<tr>
			<td align=right>
				<?=__('Moneda')?>
			</td>
			<td>
<?=Html::SelectQuery($sesion, "SELECT id_moneda,glosa_moneda FROM prm_moneda ORDER BY id_moneda","id_moneda",$id_moneda? $id_moneda:'3', '','',"60")?>
			</td>
		</tr>
*/ ?>
		<tr>
			<td align=right colspan=2>
				<input type=hidden name='opc' value='reporte'>
				<input type="submit" class=btn value="<?=__('Generar reporte')?>" name="btn_reporte">
			</td>
		</tr>
	</table>
</form>
<br />
<?
	$pagina->PrintBottom($popup);
?>
<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion(array('PRO','REV','ADM','COB'));
	if( method_exists('Conf','GetConf') )
	{
		if( !Conf::GetConf($sesion,'ReportesAvanzados') )
		{
			header("location: reportes_especificos.php");
		}
	}
	else if( method_exists('Conf','ReportesAvanzados') )
	{
		if( !Conf::ReportesAvanzados() )
		{
			header("location: reportes_especificos.php");
		}
	}
	else
		header("location: reportes_especificos.php");
	$pagina = new Pagina($sesion);

	if(!$fecha_a || $fecha_a<1)
		$fecha_a = date("Y");
	if(!$fecha_m || $fecha_m<1 || $fecha_m>12)
		$fecha_m = date("m");
	$meses = array(__("Enero"), __("Febrero"), __("Marzo"), __("Abril"), __("Mayo"), __("Junio"),__("Julio"),__("Agosto"),__("Septiembre"),__("Octubre"),__("Noviembre"),__("Diciembre"));

	if($opc == 'excel_anual' || $opc=='reporte')
	{
		require_once('planillas/planilla_resumen_costos.php');
		exit;
	}

	$pagina->titulo = __('Reporte costo por profesional');
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
			<td align=right>
				<?=__('Fecha hasta')?>
			</td>
			<td align=left>
				<table cellpadding="0" cellspacing="0"><tbody><tr>
					<td valign="middle">
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
					<td valign="middle">
						<input id="fecha2_a" name="fecha2_a" size="4" maxlength="4" value="<?=$fecha_a?>" onkeypress="return YearDigitsOnly(window.event)" type="text">
					</td>
				</tr></tbody></table>
			</td>
		</tr>
		<tr>
			<td align=right colspan=2>
				<input type="checkbox" name="solo_pro" value="1" checked /><?=__('Solo profesionales')?>
			</td>
		</tr>
		<tr>
			<td align=right colspan=2>
				<input type=hidden name='opc' value='reporte'>
				<input type="submit" class=btn value="<?=__('Generar reporte')?>" name="btn_reporte">
			</td>
		</tr>
	</table>
</form>
<br />
<form name=formulario id=formulario method=post action='' autocomplete="off">
	<input type=hidden name='opc' value='excel_anual'>
	<input type=hidden name='popup' id='popup' value='<?=$popup ?>'>
	<input type=submit value='<?=__('Descargar excel anual') ?>' class=btn >
</form>
<br />
<?
	$pagina->PrintBottom($popup);
?>
<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/classes/InputId.php';
require_once Conf::ServerDir() . '/classes/Cliente.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/Trabajo.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/Retribuciones.php';

$sesion = new Sesion(array('RET'));
$moneda_base = Utiles::MonedaBase($sesion);

if (!empty($_POST)) {
	$filtros = compact('fecha1', 'fecha2', 'estado', 'usuarios', 'tipo_calculo', 'moneda_filtro');

	$retribuciones = new Retribuciones($sesion);
	$datos_reporte = $retribuciones->FetchReporte($filtros, PDO::FETCH_ASSOC);
	$filtros['cobros'] = $retribuciones->GetListaCobros();
	$datos_subreporte = $retribuciones->FetchSubReporte($filtros, PDO::FETCH_ASSOC | PDO::FETCH_GROUP);

	if ($opc == 'xls') {
		$retribuciones->DownloadExcel('RETRIBUCIONES_DETALLE', $datos_reporte, $datos_subreporte, $filtros);
	}

}

$pagina = new Pagina($sesion);
$pagina->titulo = 'Detalle de ' . __('Retribuciones') . ' por ' . __('Cobro');
$pagina->PrintTop();

	?>

<style type="text/css">
	.detail-table {
		width: 80%;
		text-align:left;
	}
	.header-labels th{
		vertical-align: middle;
		font-size: -0.4em;
		color: #060;
		text-align: left;
	}
	.group {
		color: #040;
		text-align:left;
		font-weight: bold;
	}
	.group-total {
		color: #040;
		text-align:left;
		font-weight: bold;
	}
	.subgroup {
		color: #040;
		text-align:left;
		font-weight: bold;
	}
	.subgroup.title {
		padding-left: 20px;
	}
	.detail {
		text-align:left;
	}
	.detail.title {
		padding-left: 30px;
	}
	.number-cell  {
		text-align:right !important;
	}
	.total {
		color: #040;
		text-align:left;
		font-weight: bold;
	}
	.total td, .group-total td {
		font-weight: bold;
	}
	.item-end hr{
		border-color: #080;
		border-width: 2px;
	}
	.total-separator  {
		border-top: solid 1px #080;
	}
	.group-separator {
		padding-bottom: 10px;
		border-top: solid 1px #BBB;
	}
	.item-group, h1{
		background-color: #080;
		color: white;
	}
	/* Estilo nuevos reportes*/
	h1{
		background-color: #080;
		color: white;
		text-align: center !important;
		font-size: 1.2em !important;
	}

	.subreport h1, .subreport h2{
		background-color: transparent;
		color:  #040 !important;
		text-align: left !important;
		font-size: 1.0em !important;
	}
	.subreport h2 {
		padding-left: 20px;
		font-size: 0.9em !important;
	}

	tr.encabezado {
		border: 0 !important;
	}

	td.encabezado {
		font-weight: bold !important;
		color: #040 !important;
		background-color: #ddd;
		height: 20px;
		font-size: 11px;
		vertical-align: middle;
		text-align: center;
		border-bottom: solid 1px #BBB;
	}
	.subreport td.encabezado {
		background-color: #eee;
	}

	td.buscador {
		border-bottom: solid 1px #BBB;
	}

	.subtotal {
		text-align: left;
		padding-left: 0px;
	}

	.subtotal td {
		font-weight: bold !important;
		border-bottom: none !important;
		padding-bottom: 15px;
	}

	td > h1 {
		margin: 0;
		padding-left: 0px;
	}
	td > h2 {
		margin: 0;
		padding-left: 20px;
	}
	.level3 {
		padding-left: 20px !important;
		background-color: transparent;
		color:  #040 !important;
		text-align: left !important;
		font-size: 0.9em !important;
		font-weight: normal;
	}
	.level2 {
		padding-left: 0px !important;
	}

</style>


<form method=post name=formulario action="reporte_retribuciones_b.php">
	<input type="hidden" name="opc" id="opc" value='print'>
	<table width="90%"><tr><td>
				<fieldset class="border_plomo tb_base">
					<legend>
						<?php echo __('Filtros') ?>
					</legend>
					<table style=" width: 90%;" cellpadding="4">
						<tr>
							<td align=right >
								<?php echo __('Considerar cobros en estado') ?>:
							</td>
							<td align=left>
								<?php
								echo Html::SelectArrayDecente(array(
									'EMITIDO,ENVIADO AL CLIENTE' => 'Emitido',
									'FACTURADO,PAGO PARCIAL' => 'Fecturado',
									'PAGADO' => 'Pagado',
									), 'estado', isset($estado) ? $estado : 'PAGADO', '', 'Todos');
								?>
							</td>
						</tr>
						<tr>
							<td align=right >
								<?php echo __('Fecha desde') ?>:
							</td>
							<td align=left>
								<?php
								if (!$fecha1) {
									$fecha1 = date("d-m-Y", strtotime("- 1 month"));
									$fecha2 = date("d-m-Y");
								}
								?>
								<input type="text" name="fecha1" value="<?php echo $fecha1; ?>" id="fecha1" size="11" maxlength="10" />
								<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_ini" style="cursor:pointer" />
							</td>
						</tr>
						<tr>
							<td align=right >
								<?php echo __('Fecha hasta') ?>:
							</td>
							<td align=left>
								<input type="text" name="fecha2" value="<?php echo $fecha2; ?>" id="fecha2" size="11" maxlength="10" />
								<img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_fin" style="cursor:pointer" />
							</td>
						</tr>


						<tr>
							<td align=right>
								<?php echo __("Encargado") ?>:
							</td>
							<td align=left>
								<?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]", $usuarios, "class=\"selectMultiple\" multiple size=6 ", "", "200"); ?>
							</td>
						</tr>

						<tr>
							<td align="right">
								<?php echo __('Calcular según') ?>:     </td>
							<td align="left">
								<?php
								echo Html::SelectArrayDecente(array('duracion_cobrada' => 'Horas Cobradas', 'monto_cobrado' => 'Valor Cobrado'), 'tipo_calculo', $tipo_calculo);
								?>
							</td>
						</tr>
						<tr>
							<td align=right >
								<?php echo __('Visualizar en Moneda') ?>:
							</td>
							<td align=left>
								<?php
								$moneda_seleccionada = $moneda_filtro ? $moneda_filtro : $moneda_base['id_moneda'];
								echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda", "moneda_filtro", $moneda_seleccionada, "", '','');
								?>
							</td>
						</tr>

						<tr>
							<td align=center colspan=2>
								<input name="print" type="submit" class="btn submit-report" value="<?php echo __('Generar reporte') ?>" name="btn_reporte">
								&nbsp;
								<input name="xls" type="submit" class="btn submit-report" value="<?php echo __('Descargar Excel') ?>" name="btn_reporte">
							</td>
						</tr>
					</table>
				</fieldset>
			</td></tr></table>
</form>
<?php
if (!empty($_POST) && $opc == 'print') {
	$html_reporte = $retribuciones->PrintHtml('RETRIBUCIONES_DETALLE', $datos_reporte, $datos_subreporte, $filtros);
	echo $html_reporte;
}
?>
<script>
	jQuery(function() {
		jQuery('.submit-report').click(function(){
			jQuery('#opc').val(jQuery(this).attr('name'));
		});
	});

	Calendar.setup(
	{
		inputField  : "fecha1",       // ID of the input field
		ifFormat    : "%d-%m-%Y",     // the date format
		button      : "img_fecha_ini"   // ID of the button
	}
);
	Calendar.setup(
	{
		inputField  : "fecha2",       // ID of the input field
		ifFormat    : "%d-%m-%Y",     // the date format
		button      : "img_fecha_fin"   // ID of the button
	}
);
</script>
<?php
$pagina->PrintBottom();
?>
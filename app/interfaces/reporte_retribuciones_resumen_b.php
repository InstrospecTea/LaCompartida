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
require_once Conf::ServerDir() . '/classes/RetribucionesResumen.php';


$sesion = new Sesion(array('RET'));
$porcentaje_retribucion_socios = Conf::GetConf($sesion, 'RetribucionCentroCosto');
$moneda_base = Utiles::MonedaBase($sesion);
$pagina = new Pagina($sesion);

if (!empty($_POST)) {
	$filtros = array();
	$filtros['fecha1'] = $fecha1;
	$filtros['fecha2'] = $fecha2;
	$filtros['estado'] = $estado;
	$filtros['usuarios'] = $usuarios;
	$filtros['tipo_calculo'] = $tipo_calculo;
	$filtros['moneda_filtro'] = $moneda_filtro;
	$filtros['id_area_usuario'] = $id_area_usuario;
	$filtros['retribuidos'] = $retribuidos;
	$filtros['incluir_detalle'] = $incluir_detalle;

	$retribuciones = new RetribucionesResumen($sesion);

	if ($opc == 'xls') {
		$retribuciones->DownloadExcel('RETRIBUCIONES_RESUMEN', $filtros);
	}

	if($opc == 'marcar_retribuido'){
		if ($retribuciones->MarcarRetribuidos($filtros)) {
			$pagina->AddInfo(__('Se han marcado como retribuidos los siguientes montos'));
		} else {
			$pagina->AddError(__('No se han podido marcar los montos como retribuidos'));
		}
	}
}

$pagina->titulo = 'Resumen de ' . __('Retribuciones') . ' por ' . __('Profesional');
$pagina->PrintTop();
if (isset($opc) && $opc == 'marcar_retribuido') {
	if (strlen($pagina->GetInfos()) > 0) {
		$retribuidos = 'SI';
		$filtros['retribuidos'] = $retribuidos;
	}
	$opc = 'print';
}

?>
<style>
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
	.detail.title a {
		padding-left: -30px;
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
	.detail-separator  {
		padding-bottom: 10px;
	}
	.item-group{
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
	.subreport {
		padding-left: 30px !important;
	}
	.subreport h1, .subreport h2{
		background-color: transparent;
		color:  #040 !important;
		text-align: left !important;
		font-size: 1.0em !important;
	}
	.subreport h2 {
		padding-left: 20px;
	}

	.subreport table {
		width: 100% !important;
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
		background-color: #fff;
	}

	td.buscador {
		border-bottom: solid 1px #BBB;
	}
	.subtotal td {
		font-weight: bold !important;
		border-bottom: none !important;
		padding-bottom: 15px;
	}

</style>


<form method=post name=formulario action="reporte_retribuciones_resumen_b.php">
	<input type="hidden" name="opc" id="opc" value='print'>
	<table width="90%">
		<tr>
			<td>
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
									'FACTURADO,PAGO PARCIAL' => 'Facturado',
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
								<?php echo __('Profesionales') ?>:
							</td>
							<td align=left>
								<?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario)  WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]", $usuarios, "class=\"selectMultiple\" multiple size=6 ", "", "200"); ?>
							</td>
						</tr>

						<tr>
							<td align=right>
								<?php echo __('?rea Usuario') ?>:
							</td>
							<td align=left>
								<?php
								$query_areas = '
													SELECT area.id, CONCAT(REPEAT("&nbsp;", IF(ISNULL(padre.id), 0, 5)), area.glosa)
														FROM prm_area_usuario AS area
															LEFT JOIN prm_area_usuario AS padre ON area.id_padre = padre.id
														ORDER BY  IFNULL(padre.glosa, area.glosa), padre.glosa, area.glosa ASC ';

								echo Html::SelectQuery($sesion, $query_areas, 'id_area_usuario', $id_area_usuario, '', 'Todas');
								?>
							</td>
						</tr>

						<tr>
							<td align="right">
								<?php echo __('Calcular seg?n') ?>:     </td>
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
								echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda", "moneda_filtro", $moneda_seleccionada, "", '', '');
								?>
							</td>
						</tr>

						<tr>
							<td align="right">
								<?php echo __('Incluir Montos Retribuidos'); ?>:
							</td>
							<td align="left">
								<?php
								echo Html::SelectArrayDecente(array('SI' => __('S?lo Retribuidos'), 'NO' => __('S?lo Sin Retribuir')), 'retribuidos', isset($retribuidos) ? $retribuidos : 'NO', '', __('Todos'));
								?>
							</td>
						</tr>
						<tr>
							<td colspan=2>
								<label><input type="checkbox" value=1 name="incluir_detalle" <?php echo $incluir_detalle || empty($_POST)  ? 'checked' : '' ?> /><?php echo __('Incluir Detalle de') . ' ' . __('Cobros') ?></label><br/>
							</td>
						</tr>
						<tr>
							<td align=center colspan=2>
								<input type="submit" class="btn submit-report" value="<?php echo __('Generar reporte') ?>" name="print">
								<input type="submit" class="btn submit-report" value="<?php echo __('Descargar Excel') ?>" name="xls">
							<?php
								if($retribuidos == 'NO'){ ?>
									<button id="marcar_retribuido" class="btn"><?php echo __('Marcar montos como retribuidos'); ?></button>
							<?php } ?>

							</td>
						</tr>
					</table>
				</fieldset>
			</td>
		</tr>
	</table>
</form>

<?php
if (!empty($_POST)) {
	if($opc == 'print') {
		$html_reporte = $retribuciones->PrintHtml('RETRIBUCIONES_RESUMEN', $filtros);
		echo $html_reporte;
	}
}

?>

<script>
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
	jQuery(function() {
		jQuery('.submit-report').click(function(){
			jQuery('#opc').val(jQuery(this).attr('name'));
		});
		jQuery('#marcar_retribuido').click(function(){
			if(confirm('<?php echo __('Se van a marcar todos los montos del reporte actual como retribuidos, y no volver?n a aparecer al filtrar s?lo montos sin retribuir.\n?Est? seguro que desea continuar?'); ?>')){
				jQuery('#opc').val('marcar_retribuido');
				jQuery('form').submit();
			}
			return false;
		});
	});
</script>
<?php
$pagina->PrintBottom();
?>
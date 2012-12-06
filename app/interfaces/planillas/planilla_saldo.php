<?php
require_once dirname(__FILE__) . '/../../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/Contrato.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

$Sesion = new Sesion(array('REP'));

$tipos_liquidacion = array(
    
	1 => __('Sólo Honorarios'),
	2 => __('Sólo Gastos'),
	3 => __('Sólo Mixtas (Honorarios y Gastos)'));

if (in_array($_REQUEST['opcion'], array('buscar', 'xls', 'json')) && !empty($_REQUEST['codigo_cliente'])) {
	$codigo_cliente = $_REQUEST['codigo_cliente'];
	$id_contrato = $_REQUEST['id_contrato'];
	$tipo_liquidacion = $_REQUEST['tipo_liquidacion'];
	$fecha1 = $_REQUEST['fecha1'];
	$fecha2 = $_REQUEST['fecha2'];
	$moneda_mostrar = $_REQUEST['moneda_mostrar'];
	
	if (empty($moneda_mostrar)) {
		$moneda_base = Utiles::MonedaBase($Sesion);
		$moneda_mostrar = $moneda_base['id_moneda'];
	}

	/* Test Query */
	$query_glosa_cliente = "SELECT glosa_cliente AS cliente FROM cliente WHERE codigo_cliente = '" . $codigo_cliente . "'";
	$resp = mysql_query($query_glosa_cliente) or Utiles::errorSQL($query_glosa_cliente, __FILE__, __LINE__);
	list($glosa_clientes) = mysql_fetch_array($resp);

	$query_glosa_asuntos = "SELECT glosa_asunto AS asunto FROM asunto WHERE id_contrato = '" . $id_contrato . "'";
	$resp2 = mysql_query($query_glosa_asuntos) or Utiles::errorSQL($query_glosa_asuntos, __FILE__, __LINE__);
	list($glosa_asuntos) = mysql_fetch_array($resp2);

	if ($glosa_asuntos == NULL) {
		$glosa_asuntos = 'Todos';
	}

	$filters = array(
		__('Cliente') => "$glosa_clientes",
		__('Asuntos') => "$id_contrato $glosa_asuntos",
		__('Tipo liquidación') => $tipos_liquidacion[$tipo_liquidacion],
		'Desde' => $fecha1, 'Hasta' => $fecha2);

	$where_fecha = '';
	if ($fecha1) {
		$where_fecha .= " AND fecha >= '" . Utiles::fecha2sql($fecha1) . "' ";
	}
	if ($fecha2) {
		$where_fecha .= " AND fecha <= '" . Utiles::fecha2sql($fecha2) . "' ";
	}

	$where_liquidaciones =
		$where_adelantos =
		$where_gastos = $where_fecha;
	$join_liquidaciones =
		$join_adelantos =
		$join_gastos = '';

	$tipo_liq_gastos = 'G';
	$tipo_liq_honorarios = 'H';
	$tipo_liq_mixtas = 'M';

	if (!empty($id_contrato)) {
		$where_adelantos .= " AND d.id_contrato = '$id_contrato' ";
		$where_liquidaciones .= " AND cobro.id_contrato = '$id_contrato' ";

		$join_gastos .= ' JOIN asunto a ON a.codigo_asunto = cc.codigo_asunto ';
		$where_gastos .= " AND a.id_contrato = '$id_contrato' ";
	}

	if (!empty($tipo_liquidacion)) { //1-2 = honorarios-gastos, 3 = mixtas
		$honorarios = $tipo_liquidacion & 1;
		$gastos = $tipo_liquidacion & 2 ? 1 : 0;

		$where_liquidaciones .= "
			AND contrato.separar_liquidaciones = '" . ($tipo_liquidacion == '3' ? 0 : 1) . "'
			AND cobro.incluye_honorarios = '$honorarios'
			AND cobro.incluye_gastos = '$gastos' ";

		$where_adelantos .= "
			AND d.pago_honorarios = '$honorarios'
			AND d.pago_gastos = '$gastos' ";

		if ($honorarios) {
			$where_gastos .= ' AND 1=0 ';
		}
	}

	if ($mostrar_sin_saldo) {
		$where_liquidaciones .= " OR (
			d.tipo_doc = 'N' AND
			(d.saldo_honorarios + d.saldo_gastos) = 0 AND
			cobro.estado NOT IN ('CREADO', 'EN REVISION', 'INCOBRABLE') AND
			d.codigo_cliente = '$codigo_cliente'
			$where_liquidaciones
		)";
		$where_adelantos .= " OR (
			d.es_adelanto = 1 AND
			d.saldo_pago = 0 AND
			d.codigo_cliente = '$codigo_cliente'
			$where_adelantos
		)";
//		$where_gastos .= "";
	}

	$query_liquidaciones = "SELECT
				DATE(cobro.fecha_emision) AS fecha,
				d.id_cobro AS identificador,
				'Liquidaciones con saldo por pagar' AS tipo,
				UNHEX(HEX(CONCAT(d.glosa_documento, IF(cobro.se_esta_cobrando IS NOT NULL, CONCAT(' - ', cobro.se_esta_cobrando), '')))) AS descripcion,
				d.codigo_cliente,
				moneda_documento.simbolo AS moneda_documento,
				moneda_base.simbolo AS moneda_base,
				d.monto AS monto_original,
				-1 * (d.saldo_honorarios + d.saldo_gastos) AS saldo_original,
				d.monto * (tipo_cambio_documento.tipo_cambio / tipo_cambio_base.tipo_cambio) AS monto_base,
				-1 * (d.saldo_honorarios + d.saldo_gastos) * (tipo_cambio_documento.tipo_cambio / tipo_cambio_base.tipo_cambio) AS saldo_base,
				IF(contrato.separar_liquidaciones = 0,
					'$tipo_liq_mixtas', IF(cobro.incluye_honorarios = 1,
						'$tipo_liq_honorarios', '$tipo_liq_gastos')) AS tipo_liq
			FROM
				documento d
			JOIN cobro ON cobro.id_cobro = d.id_cobro
			INNER JOIN prm_moneda moneda_documento ON d.id_moneda = moneda_documento.id_moneda
			JOIN cobro_moneda tipo_cambio_documento ON tipo_cambio_documento.id_moneda = moneda_documento.id_moneda AND tipo_cambio_documento.id_cobro = cobro.id_cobro
			INNER JOIN prm_moneda moneda_base ON moneda_base.id_moneda = $moneda_mostrar
			JOIN cobro_moneda tipo_cambio_base ON tipo_cambio_base.id_moneda = moneda_base.id_moneda AND tipo_cambio_base.id_cobro = cobro.id_cobro
			JOIN contrato ON contrato.id_contrato = cobro.id_contrato
			$join_liquidaciones
			WHERE
				d.tipo_doc = 'N' AND
				(d.saldo_honorarios + d.saldo_gastos) > 0 AND
				cobro.estado NOT IN ('CREADO', 'EN REVISION', 'INCOBRABLE') AND
				d.codigo_cliente = '$codigo_cliente'
				$where_liquidaciones
			ORDER BY fecha";

	$query_adelantos = "SELECT
				d.fecha,
				d.id_documento AS identificador,
				'Adelantos no utilizados' AS tipo,
				UNHEX(HEX(d.glosa_documento)) AS descripcion,
				d.codigo_cliente,
				moneda_documento.simbolo AS moneda_documento,
				moneda_base.simbolo AS moneda_base,
				-1 * d.monto AS monto_original,
				-1 * d.saldo_pago AS saldo_original,
				-1 * d.monto * (moneda_documento.tipo_cambio / moneda_base.tipo_cambio) AS monto_base,
				-1 * d.saldo_pago * (moneda_documento.tipo_cambio / moneda_base.tipo_cambio) AS saldo_base,
				IF(d.pago_honorarios = 1 AND d.pago_gastos = 1,
					'$tipo_liq_mixtas', IF(d.pago_honorarios = 1,
						'$tipo_liq_honorarios', '$tipo_liq_gastos')) AS tipo_liq
			FROM
				documento d
			INNER JOIN prm_moneda moneda_documento ON d.id_moneda = moneda_documento.id_moneda
			INNER JOIN prm_moneda moneda_base ON moneda_base.id_moneda = $moneda_mostrar
			$join_adelantos
			WHERE
				d.es_adelanto = 1 AND
				d.saldo_pago < 0 AND
				d.codigo_cliente = '$codigo_cliente'
				$where_adelantos
			ORDER BY fecha";

	$query_gastos = "SELECT
				DATE(cc.fecha) AS fecha,
				cc.id_movimiento AS identificador,
				IF ( cc.ingreso IS NULL, 'Gastos por liquidar', 'Provisiones por liquidar' ) AS tipo,
				UNHEX(HEX(cc.descripcion)),
				cc.codigo_cliente,
				moneda_gasto.simbolo AS moneda_documento,
				moneda_base.simbolo AS moneda_base,
				IF (cc.ingreso IS NULL, cc.egreso, cc.ingreso) AS monto_original,
				IF (cc.ingreso IS NULL, -1 * cc.egreso, cc.ingreso) AS saldo_original,
				IF (
					cc.ingreso IS NULL,
					cc.egreso * (moneda_gasto.tipo_cambio / moneda_base.tipo_cambio),
					cc.ingreso * (moneda_gasto.tipo_cambio / moneda_base.tipo_cambio)
				) AS monto_base,
				IF (
					cc.ingreso IS NULL,
					-1 * cc.egreso * (moneda_gasto.tipo_cambio / moneda_base.tipo_cambio),
					cc.ingreso * (moneda_gasto.tipo_cambio / moneda_base.tipo_cambio)
				) AS saldo_base,
				'$tipo_liq_gastos' AS tipo_liq
			FROM
				cta_corriente cc
			INNER JOIN prm_moneda moneda_gasto ON cc.id_moneda=moneda_gasto.id_moneda
			INNER JOIN prm_moneda moneda_base ON moneda_base.id_moneda = $moneda_mostrar
			LEFT JOIN cobro ON cc.id_cobro = cobro.id_cobro
			$join_gastos
			WHERE
				cc.cobrable = 1 AND
				(cc.id_cobro IS NULL OR cobro.estado IN ('CREADO', 'EN REVISION')) AND
				cc.neteo_pago IS NULL AND
				cc.documento_pago IS NULL AND
				cc.codigo_cliente = '$codigo_cliente'
				$where_gastos
			ORDER BY fecha";

	$SimpleReport = new SimpleReport($Sesion);
	$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($Sesion));

	$SimpleReport->LoadConfiguration('REPORTE_SALDO_CLIENTES');

	$query_simbolo_base = "SELECT simbolo FROM prm_moneda WHERE id_moneda = $moneda_mostrar";
	$resp = mysql_query($query_simbolo_base) or Utiles::errorSQL($query_simbolo_base, __FILE__, __LINE__);
	list($simbolo_base) = mysql_fetch_array($resp);

	$SimpleReport->Config->columns['monto_base']->Title(__('Monto') . " ($simbolo_base)");
	$SimpleReport->Config->columns['saldo_base']->Title(__('Saldo') . " ($simbolo_base)");

	$SimpleReport->SetFilters($filters);

	$saldo_total = 0;

	$query = "($query_liquidaciones) UNION ($query_gastos) UNION ($query_adelantos)";

	$statement = $Sesion->pdodbh->prepare($query);
	$statement->execute();
	$results = $statement->fetchAll(PDO::FETCH_ASSOC);
	$SimpleReport->LoadResults($results);

	if ($_REQUEST['opcion'] == 'xls') {
		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Spreadsheet');
		$writer->save('Reporte_saldo');
	}
	if ($_REQUEST['opcion'] == 'json') {
		foreach ($results as $fila) {
			$saldo_total += $fila['saldo_base'];
		}
		$moneda_base = $fila['moneda_base'];
		$data = array(
			"resultado" => $moneda_base . ' ' . number_format($saldo_total, 2, ',', '.')
		);
		echo json_encode($data);
		exit;
	}
}

$Pagina = new Pagina($Sesion);

$Pagina->titulo = __('Reporte Saldo');
$Pagina->PrintTop($popup);
?>
<table width="90%">
    <tr>
		<td>
			<form method="POST" name="form_reporte_saldo" action="planilla_saldo.php" id="form_reporte_saldo">
				<input  id="xdesde"  name="xdesde" type="hidden" value="">
				<input type="hidden" name="opcion" id="opcion" value="buscar">
				<!-- Calendario DIV -->
				<div id="calendar-container" style="width:221px; position:absolute; display:none;">
					<div class="floating" id="calendar"></div>
				</div>
				<!-- Fin calendario DIV -->
				<fieldset class="tb_base" style="width: 100%;border: 1px solid #BDBDBD;">
					<legend><?php echo __('Filtros') ?></legend>
					<table style="border: 0px solid black" width='720px'>
						<tr>
							<td align="right" width="30%">
								<label for="codigo_cliente"><?php echo __('Cliente'); ?></label>
							</td>
							<td colspan="3" align="left">
								<?php echo UtilesApp::CampoCliente($Sesion, $_REQUEST['codigo_cliente']); ?>
							</td>
						</tr>
						<tr>
							<td align="right" width="30%">
								<label for="id_contrato"><?php echo __('Asuntos'); ?></label>
							</td>
							<td colspan="3" align="left" id="td_selector_contrato">
								<?php
								$Contrato = new Contrato($Sesion);
								echo $Contrato->ListaSelector($_REQUEST['codigo_cliente'], '', $_REQUEST['id_contrato']);
								?>
							</td>
						</tr>
						<tr>
							<td align=right>
								<label for="tipo_liquidacion"><?php echo __('Tipo de Liquidación') ?></label>
							</td>
							<td colspan=2 align=left>
								<?php
								echo Html::SelectArrayDecente($tipos_liquidacion, 'tipo_liquidacion', $_REQUEST['tipo_liquidacion'], '', __('Todas'))
								?>
							</td>
						</tr>
						<tr>
							<td align=right>
								<label for="moneda_mostrar"><?php echo __('Mostrar Montos en') ?></label>
							</td>
							<td colspan=2 align=left>
								<?php
								echo Html::SelectQuery($Sesion, 'SELECT id_moneda, glosa_moneda FROM prm_moneda', 'moneda_mostrar', $_REQUEST['moneda_mostrar']);
								?>
							</td>
						</tr>
                        <tr>
                            <td align=right><?php echo __('Fecha Desde') ?></td>
                            <td nowrap align=left>
                                <input class="fechadiff" type="text" name="fecha1" value="<?php echo $fecha1 ?>" id="fecha1" size="11" maxlength="10" />
                            </td>
                            <td nowrap align=left colspan=2>
								&nbsp;&nbsp; <?php echo __('Fecha Hasta') ?>
                                <input  class="fechadiff" type="text" name="fecha2" value="<?php echo $fecha2 ?>" id="fecha2" size="11" maxlength="10" />
                            </td>
                        </tr>
						<tr>
							<td>&nbsp;</td>
							<td colspan="3" align="left">
								<label>
									<input type="hidden" name="mostrar_sin_saldo" value="0" />
									<input type="checkbox" name="mostrar_sin_saldo" id="mostrar_sin_saldo" value="1" <?php echo $mostrar_sin_saldo ? 'checked="checked"' : '' ?> />
									<?php echo __('Mostrar liquidaciones y adelantos sin saldo'); ?>
								</label>
							</td>
						</tr>
						<tr>
							<td></td>
							<td colspan=2 align=left>
								<input name="boton_buscar" id="boton_buscar" type="submit" value="<?php echo __('Buscar') ?>" class="btn" />
							</td>
							<td width='40%' align="right">
								<input name="boton_xls" id="boton_xls" type="submit" value="<?php echo __('Descargar Excel') ?>" class="btn" />
							</td>
						</tr>
					</table>
				</fieldset>
			</form>
		</td>
    </tr>
</table>
<link rel="stylesheet" type="text/css" media="print" href="https://static.thetimebilling.com/css/imprimir.css" />
<script type="text/javascript">
    var valor_anterior_codigo;
    var campo_cliente;

    function ActualizarContratos(val) {
		var url = root_dir + '/app/ajax.php?accion=cargar_contratos&codigo_cliente=' + val;
		jQuery.ajax({
			url: url,
			success: function (data) {
				jQuery('#td_selector_contrato').html(data);
			}
		});
    }

    function ComprobarCodigos() {
		var valor_nuevo = campo_cliente.val()
		if (valor_anterior_codigo != valor_nuevo) {
			ActualizarContratos(valor_nuevo);
			valor_anterior_codigo = valor_nuevo;
		}
    }

    jQuery(document).ready(function () {
		// Cargar contratos on select
		campo_cliente = jQuery('input[name^="codigo_cliente"], select[name^="codigo_cliente"]');
		valor_anterior_codigo = campo_cliente.val();
		window.setInterval(ComprobarCodigos, 500);

		jQuery('#boton_xls').click(function(){
			jQuery('#opcion').val('xls');
		});
		jQuery('#boton_buscar').click(function(){
			jQuery('#opcion').val('buscar');
		});

		jQuery('.saldo:contains(-)').css('color', '#f00');
		jQuery('.saldo:not(:contains(-))').css('color', '#00f');
		jQuery('.subtotal td').css('font-weight', 'bold');
    });
</script>
<?php
if ($_REQUEST['opcion'] == 'buscar' && !empty($_REQUEST['codigo_cliente'])) {

	$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Html');
	echo $writer->save();

	foreach ($results as $fila) {
		$saldo_total += $fila['saldo_base'];
	}
	$moneda_base = $fila['moneda_base'];

	$color = $saldo_total < 0 ? 'red' : 'blue';
	$resultado = '<span style="color: ' . $color . '">' . $moneda_base . ' ' . number_format($saldo_total, 2, ',', '.') . '</span>';

	echo '<div style="text-align: right; font-size: 2em;">Saldo total: ' . $resultado . '</h1>';
}

$Pagina->PrintBottom();
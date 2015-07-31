<?php
require_once dirname(__FILE__) . '/../../conf.php';
require_once APPPATH . '/app/classes/Reportes/SimpleReport.php';

$Sesion = new Sesion(array('REP'));
$Form = new Form($Sesion);

$tipos_liquidacion = array(
	1 => __('Honorarios'),
	2 => __('Gastos')
);

if (in_array($_REQUEST['opcion'], array('buscar', 'xls', 'json'))) {

	$texto_liquidaciones_por_pagar = __('Liquidaciones por pagar');
	$texto_gastos_por_liquidar = __('Gastos por liquidar');
	$texto_provisiones_por_liquidar = __('Provisiones por liquidar');
	$texto_adelantos_no_utilizados = __('Adelantos no utilizados');

	$codigo_cliente = '';

	if (!empty($_REQUEST['codigo_cliente'])) {
		$codigo_cliente = $_REQUEST['codigo_cliente'];
	}

	$mostrar_detalle = $_REQUEST['mostrar_detalle'];

	$id_contrato = $_REQUEST['id_contrato'];
	$tipo_liquidacion = $_REQUEST['tipo_liquidacion'];
	$fecha1 = $_REQUEST['fecha1'];
	$fecha2 = $_REQUEST['fecha2'];
	$moneda_mostrar = $_REQUEST['moneda_mostrar'];
	$encargado_comercial = $_REQUEST['encargado_comercial'];

	if (empty($moneda_mostrar)) {
		$moneda_base = Utiles::MonedaBase($Sesion);
		$moneda_mostrar = $moneda_base['id_moneda'];
	}

	if (!empty($codigo_cliente)) {
		$query_glosa_cliente = "SELECT glosa_cliente AS cliente FROM cliente WHERE codigo_cliente = '$codigo_cliente'";
		$resp = mysql_query($query_glosa_cliente) or Utiles::errorSQL($query_glosa_cliente, __FILE__, __LINE__);
		list($glosa_clientes) = mysql_fetch_array($resp);
	}

	if (!empty($id_contrato)) {
		$query_glosa_asuntos = "SELECT glosa_asunto AS asunto FROM asunto WHERE id_contrato = '$id_contrato'";
		$resp2 = mysql_query($query_glosa_asuntos) or Utiles::errorSQL($query_glosa_asuntos, __FILE__, __LINE__);
		list($glosa_asuntos) = mysql_fetch_array($resp2);
	}

	if ($glosa_asuntos == NULL) {
		$glosa_asuntos = 'Todos';
	}

	if (!empty($moneda_mostrar)) {
		$query_glosa_moneda = "SELECT glosa_moneda AS moneda FROM prm_moneda WHERE id_moneda = '$moneda_mostrar'";
		$resp2 = mysql_query($query_glosa_moneda) or Utiles::errorSQL($query_glosa_moneda, __FILE__, __LINE__);
		list($glosa_moneda) = mysql_fetch_array($resp2);
	}

	if (!empty($encargado_comercial)) {
		$query_glosa_encargado = "SELECT CONCAT_WS(' ', apellido1, apellido2, ',' , nombre) AS encargado FROM usuario WHERE id_usuario = '$encargado_comercial'";
		$resp2 = mysql_query($query_glosa_encargado) or Utiles::errorSQL($query_glosa_encargado, __FILE__, __LINE__);
		list($glosa_encargado_comercial) = mysql_fetch_array($resp2);
	}

	$filters = array(
		__('Cliente') => $glosa_cliente,
		__('Asuntos') => "$id_contrato $glosa_asuntos",
		__('Mostrar Saldo') => empty($tipo_liquidacion) ? 'Total' : $tipos_liquidacion[$tipo_liquidacion],
		__('Mostrar montos en') => "$glosa_moneda",
		'Desde' => $fecha1, 'Hasta' => $fecha2,
		'Mostrar liquidaciones y adelantos sin saldo' => $mostrar_sin_saldo ? 'SI' : '',
		__('Encargado Comercial') => $glosa_encargado_comercial
	);

	$where_fecha = '';
	if ($fecha1) {
		$where_fecha .= " AND fecha >= '" . Utiles::fecha2sql($fecha1) . "' ";
	}
	if ($fecha2) {
		$where_fecha .= " AND fecha <= '" . Utiles::fecha2sql($fecha2) . "' ";
	}

	$where_liquidaciones = $where_adelantos = $where_gastos = $where_fecha;
	$join_liquidaciones = $join_adelantos = $join_gastos = '';
	$select_liquidaciones = $select_adelantos = $select_gastos = $select_resumen = "";

	$tipo_liq_gastos = 'G';
	$tipo_liq_honorarios = 'H';
	$tipo_liq_mixtas = 'M';

	// Viene asunto o contrato
	$glosa_asunto = trim($_REQUEST['glosa_asunto']);
	if (!empty($id_contrato) || !empty($codigo_asunto) || !empty($codigo_asunto_secundario) || !empty($glosa_asunto)) {
		if (empty($codigo_asunto) && empty($codigo_asunto_secundario) && !empty($glosa_asunto)) {
			$id_contrato = array();
			$query_asuntos = "SELECT DISTINCT id_contrato FROM asunto WHERE glosa_asunto LIKE '%$glosa_asunto%'";
			$resp_query_asuntos = mysql_query($query_asuntos);
			if ($resp_query_asuntos !== false) {
				while ($fila = mysql_fetch_assoc($resp_query_asuntos)) {
					$id_contrato[] = $fila['id_contrato'];
				}
			}
		}
		$wic = is_array($id_contrato) ? "IN ('" . implode("','", $id_contrato) . "')" : "= '$id_contrato'";
		$where_adelantos .= " AND d.id_contrato $wic ";
		$where_liquidaciones .= " AND cobro.id_contrato $wic ";

		$join_gastos .= ' JOIN asunto a ON a.codigo_asunto = cc.codigo_asunto ';
		$where_gastos .= " AND a.id_contrato $wic ";
	}

	if (!empty($encargado_comercial)) {
		$where_gastos .= " AND contrato.id_usuario_responsable = '$encargado_comercial'";
		$where_adelantos .= " AND contrato.id_usuario_responsable = '$encargado_comercial'";
		$where_liquidaciones .= " AND contrato.id_usuario_responsable = '$encargado_comercial'";
	}

	if (!empty($id_grupo_cliente)) {
		$where_gastos .= " AND cliente.id_grupo_cliente = '{$id_grupo_cliente}'";
		$where_adelantos .= " AND cliente.id_grupo_cliente = '{$id_grupo_cliente}'";
		$where_liquidaciones .= " AND cliente.id_grupo_cliente = '{$id_grupo_cliente}'";
	}

	if (!empty($tipo_liquidacion)) { //1-2 = honorarios-gastos, 3 = mixtas
		$honorarios = $tipo_liquidacion == 1;
		$gastos = $tipo_liquidacion == 2;

		if ($honorarios) {
			$where_liquidaciones .= " AND cobro.incluye_honorarios = '1' AND cobro.incluye_gastos = '0' ";
			if (!$mostrar_sin_saldo) {
				$where_liquidaciones .= " AND d.saldo_honorarios > 0 ";
			}
			$where_adelantos .= " AND d.pago_honorarios = '$honorarios' ";
			$where_gastos .= ' AND 1=0 ';
		}

		if ($gastos) {
			$where_liquidaciones .= " AND cobro.incluye_gastos = '1' AND cobro.incluye_honorarios = '0' ";
			if (!$mostrar_sin_saldo) {
				$where_liquidaciones .= " AND d.saldo_gastos > 0 ";
			}
			$where_adelantos .= " AND d.pago_gastos = '$gastos' ";
		}
	} else {
		$where_liquidaciones .= " AND (d.saldo_honorarios + d.saldo_gastos) > 0 ";
	}

	if (!empty($codigo_cliente)) {
		$where_liquidaciones .= " AND d.codigo_cliente = '$codigo_cliente' ";
		$where_adelantos .= " AND d.codigo_cliente = '$codigo_cliente' ";
		$where_gastos .= " AND cc.codigo_cliente = '$codigo_cliente' ";
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
	}

	$concat_asunto = '';
	$join_asunto = '';
	$group_by = '';

	if (Conf::GetConf($Sesion, 'MostrarAsuntoPlanillaSaldo')) {
		$concat_asunto = ", GROUP_CONCAT(asunto.codigo_asunto, '|', asunto.glosa_asunto) AS asunto";
		$join_asunto = "LEFT JOIN cobro_asunto ON cobro_asunto.id_cobro = cobro.id_cobro";
		$join_asunto .= " LEFT JOIN asunto ON cobro_asunto.codigo_asunto = asunto.codigo_asunto";
		$group_by = "GROUP BY cobro.id_cobro";
	}

	$query_liquidaciones = "SELECT
				$select_liquidaciones
				encargado_comercial.username AS encargado_comercial,
				DATE(cobro.fecha_emision) AS fecha,
				d.id_cobro AS identificador,
				'$texto_liquidaciones_por_pagar' AS tipo,
				UNHEX(HEX(CONCAT(d.glosa_documento, IF(cobro.se_esta_cobrando IS NOT NULL, CONCAT(' - ', cobro.se_esta_cobrando), '')))) AS descripcion,
				d.codigo_cliente,
				cliente.glosa_cliente,
				moneda_documento.simbolo AS moneda_documento,
				moneda_base.simbolo AS moneda_base,
				d.monto AS monto_original,
				-1 * (d.saldo_honorarios + d.saldo_gastos) AS saldo_original,
				d.monto * (tipo_cambio_documento.tipo_cambio / tipo_cambio_base.tipo_cambio) AS monto_base,
				-1 * (d.saldo_honorarios + d.saldo_gastos) * (tipo_cambio_documento.tipo_cambio / tipo_cambio_base.tipo_cambio) AS saldo_liquidaciones,
				0 AS saldo_gastos,
				0 AS saldo_adelantos,
				IF(contrato.separar_liquidaciones = 0,
					'$tipo_liq_mixtas', IF(cobro.incluye_honorarios = 1,
						'$tipo_liq_honorarios', '$tipo_liq_gastos')) AS tipo_liq
				$concat_asunto
			FROM
				documento d
			INNER JOIN cobro ON cobro.id_cobro = d.id_cobro
			INNER JOIN prm_moneda moneda_documento ON d.id_moneda = moneda_documento.id_moneda
			INNER JOIN cobro_moneda tipo_cambio_documento ON tipo_cambio_documento.id_moneda = moneda_documento.id_moneda AND tipo_cambio_documento.id_cobro = cobro.id_cobro
			INNER JOIN prm_moneda moneda_base ON moneda_base.id_moneda = $moneda_mostrar
			INNER JOIN cobro_moneda tipo_cambio_base ON tipo_cambio_base.id_moneda = moneda_base.id_moneda AND tipo_cambio_base.id_cobro = cobro.id_cobro
			INNER JOIN contrato ON contrato.id_contrato = cobro.id_contrato
			INNER JOIN cliente ON cliente.codigo_cliente = d.codigo_cliente
			LEFT JOIN usuario encargado_comercial ON encargado_comercial.id_usuario = contrato.id_usuario_responsable
			$join_asunto
			$join_liquidaciones
			WHERE
				cliente.activo = 1
				AND contrato.activo = 'SI'
				AND d.tipo_doc = 'N'
				AND cobro.estado NOT IN ('CREADO', 'EN REVISION', 'INCOBRABLE')
				$where_liquidaciones
			$group_by
			ORDER BY fecha";


	if (Conf::GetConf($Sesion, 'MostrarAsuntoPlanillaSaldo')) {
		$concat_asunto = ", CONCAT(asunto.codigo_asunto, '|', asunto.glosa_asunto) AS asunto";
		$join_asunto = "LEFT JOIN asunto ON asunto.codigo_asunto = d.codigo_asunto";
	}

	$query_adelantos = "SELECT
				$select_adelantos
				encargado_comercial.username AS encargado_comercial,
				d.fecha,
				d.id_documento AS identificador,
				'$texto_adelantos_no_utilizados' AS tipo,
				UNHEX(HEX(d.glosa_documento)) AS descripcion,
				d.codigo_cliente,
				cliente.glosa_cliente,
				moneda_documento.simbolo AS moneda_documento,
				moneda_base.simbolo AS moneda_base,
				-1 * d.monto AS monto_original,
				-1 * d.saldo_pago AS saldo_original,
				-1 * d.monto * (moneda_documento.tipo_cambio / moneda_base.tipo_cambio) AS monto_base,
				0 AS saldo_liquidaciones,
				0 AS saldo_gastos,
				-1 * d.saldo_pago * (moneda_documento.tipo_cambio / moneda_base.tipo_cambio) AS saldo_adelantos,
				IF(d.pago_honorarios = 1 AND d.pago_gastos = 1,
					'$tipo_liq_mixtas', IF(d.pago_honorarios = 1,
						'$tipo_liq_honorarios', '$tipo_liq_gastos')) AS tipo_liq
				$concat_asunto
			FROM
				documento d
			INNER JOIN prm_moneda moneda_documento ON d.id_moneda = moneda_documento.id_moneda
			INNER JOIN prm_moneda moneda_base ON moneda_base.id_moneda = $moneda_mostrar
			INNER JOIN cliente ON cliente.codigo_cliente = d.codigo_cliente
			LEFT JOIN contrato ON d.id_contrato = contrato.id_contrato
			LEFT JOIN usuario encargado_comercial ON encargado_comercial.id_usuario = contrato.id_usuario_responsable
			$join_asunto
			$join_adelantos
			WHERE
				cliente.activo = 1
				AND (d.id_contrato IS NULL OR contrato.activo = 'SI')
				AND d.es_adelanto = 1
				AND d.saldo_pago < 0
				$where_adelantos
			ORDER BY fecha";

	if (Conf::GetConf($Sesion, 'MostrarAsuntoPlanillaSaldo')) {
		$concat_asunto = ", CONCAT(asunto.codigo_asunto, '|', asunto.glosa_asunto) AS asunto";
	}

	$query_gastos = "SELECT
				$select_gastos
				encargado_comercial.username AS encargado_comercial,
				DATE(cc.fecha) AS fecha,
				cc.id_movimiento AS identificador,
				IF ( cc.ingreso IS NULL, '$texto_gastos_por_liquidar', '$texto_provisiones_por_liquidar' ) AS tipo,
				UNHEX(HEX(cc.descripcion)),
				cc.codigo_cliente,
				cliente.glosa_cliente,
				moneda_gasto.simbolo AS moneda_documento,
				moneda_base.simbolo AS moneda_base,
				IF (cc.ingreso IS NULL, cc.egreso, cc.ingreso) AS monto_original,
				IF (cc.ingreso IS NULL, -1 * cc.egreso, cc.ingreso) AS saldo_original,
				IF (
					cc.ingreso IS NULL,
					cc.egreso * (moneda_gasto.tipo_cambio / moneda_base.tipo_cambio),
					cc.ingreso * (moneda_gasto.tipo_cambio / moneda_base.tipo_cambio)
				) AS monto_base,
				0 AS saldo_liquidaciones,
				IF (
					cc.ingreso IS NULL,
					-1 * cc.egreso * (moneda_gasto.tipo_cambio / moneda_base.tipo_cambio),
					cc.ingreso * (moneda_gasto.tipo_cambio / moneda_base.tipo_cambio)
				) AS saldo_gastos,
				0 AS saldo_adelantos,
				'$tipo_liq_gastos' AS tipo_liq
				$concat_asunto
			FROM
				cta_corriente cc
			INNER JOIN prm_moneda moneda_gasto ON cc.id_moneda = moneda_gasto.id_moneda
			INNER JOIN prm_moneda moneda_base ON moneda_base.id_moneda = $moneda_mostrar
			INNER JOIN cliente ON cliente.codigo_cliente = cc.codigo_cliente
			INNER JOIN asunto ON asunto.codigo_asunto = cc.codigo_asunto
			INNER JOIN contrato ON asunto.id_contrato = contrato.id_contrato
			LEFT JOIN cobro ON cc.id_cobro = cobro.id_cobro
			LEFT JOIN usuario encargado_comercial ON encargado_comercial.id_usuario = contrato.id_usuario_responsable
			$join_gastos
			WHERE
				cliente.activo = 1
				AND contrato.activo = 'SI'
				AND cc.cobrable = 1
				AND (cc.id_cobro IS NULL OR cobro.estado IN ('CREADO', 'EN REVISION'))
				AND cc.id_neteo_documento IS NULL
				AND cc.documento_pago IS NULL
				$where_gastos
			ORDER BY fecha";

	$SimpleReport = new SimpleReport($Sesion);
	$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($Sesion));

	$SimpleReport->LoadConfiguration('REPORTE_SALDO_CLIENTES_RESUMEN');

	$SimpleReport->Config->columns['encargado_comercial']->Title(__('Encargado Comercial'));
	$SimpleReport->Config->columns['saldo_liquidaciones']->Title($texto_liquidaciones_por_pagar);
	$SimpleReport->Config->columns['saldo_gastos']->Title($texto_gastos_por_liquidar);
	$SimpleReport->Config->columns['saldo_adelantos']->Title($texto_adelantos_no_utilizados);

	$query_simbolo_base = "SELECT simbolo FROM prm_moneda WHERE id_moneda = $moneda_mostrar";
	$resp = mysql_query($query_simbolo_base) or Utiles::errorSQL($query_simbolo_base, __FILE__, __LINE__);
	list($simbolo_base) = mysql_fetch_array($resp);

	$SimpleReport->SetFilters($filters);

	$saldo_total = 0;

	if ($ocultar_clientes_sin_saldo) {
		$where_saldo = "WHERE r.saldo_liquidaciones + r.saldo_gastos + r.saldo_adelantos > 0";
	}

	$query = "SELECT
			r.encargado_comercial,
			r.codigo_cliente,
			r.glosa_cliente,
			SUM(IF(r.tipo = '$texto_liquidaciones_por_pagar', r.monto_base, 0)) AS total_liquidaciones,
			SUM(r.saldo_liquidaciones) AS saldo_liquidaciones,
			SUM(IF(r.tipo = '$texto_gastos_por_liquidar' OR r.tipo = '$texto_provisiones_por_liquidar', r.monto_base, 0)) AS total_gastos,
			SUM(r.saldo_gastos) AS saldo_gastos,
			SUM(IF(r.tipo = '$texto_adelantos_no_utilizados', r.monto_base, 0)) AS total_adelantos,
			SUM(r.saldo_adelantos) AS saldo_adelantos,
			0 AS total_total, -- total_liquidaciones + total_gastos + total_adelantos AS total_total,
			0 AS saldo_total, -- saldo_liquidaciones + saldo_gastos + saldo_adelantos AS saldo_total
			moneda_base AS simbolo_moneda
		FROM ( ($query_liquidaciones) UNION ($query_gastos) UNION ($query_adelantos) ) AS r
		$where_saldo
		GROUP BY glosa_cliente";

	//echo $query;
	//echo $query_adelantos;
	//echo $query_gastos;
	//echo $query_liquidaciones;
	//exit;

	$statement = $Sesion->pdodbh->prepare($query);
	$statement->execute();
	$results = $statement->fetchAll(PDO::FETCH_ASSOC);
	$SimpleReport->LoadResults($results);

	if ($mostrar_detalle) {
		$details_query = "($query_liquidaciones) UNION ($query_gastos) UNION ($query_adelantos) ORDER BY fecha";
		//echo $details_query; exit;
		$SimpleReportDetails = new SimpleReport($Sesion);
		$SimpleReportDetails->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($Sesion));
		$SimpleReportDetails->LoadConfiguration('REPORTE_SALDO_CLIENTES');


		$SimpleReportDetails->Config->columns['monto_base']->Title(__('Monto') . " ($simbolo_base)");
		// $SimpleReportDetails->Config->columns['saldo_base']->Title(__('Saldo') . " ($simbolo_base)");

		if (!Conf::GetConf($Sesion, 'MostrarAsuntoPlanillaSaldo')) {
			unset($SimpleReportDetails->Config->columns['asunto']);
		}

		$statement = $Sesion->pdodbh->prepare($details_query);
		$statement->execute();
		$details_all = $statement->fetchAll(PDO::FETCH_ASSOC);

		foreach ($details_all as $detail) {
			$details_results[$detail['codigo_cliente']][] = $detail;
		}

		$SimpleReportDetails->LoadResults($details_results);

		$SimpleReport->AddSubReport(array(
			'SimpleReport' => $SimpleReportDetails,
			'Keys' => array('codigo_cliente'),
			'Level' => 1
		));

		$SimpleReport->SetCustomFormat(array(
			'collapsible' => true
		));

		//$SimpleReport = null;
		//$SimpleReport = $SimpleReportDetails;
	}

	if ($_REQUEST['opcion'] == 'xls') {
		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Spreadsheet');
		$writer->save('Reporte_saldo');
	}

	if ($_REQUEST['opcion'] == 'json') {
		foreach ($results as $fila) {
			$saldo_total += $fila['saldo_liquidaciones'] + $fila['saldo_gastos'] + $fila['saldo_adelantos'];
		}
		$moneda_base = $fila['simbolo_moneda'];
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
<style type="text/css">
	.subreport {
		padding-bottom: 40px;
	}
	.subreport h1 {
		font-size: 12px;
		margin-left: 5%;
		color: #777;
		font-weight: normal;
	}
	.subreport td.encabezado {
		background-color: #ddd;
		color: #040;
	}

	.subreport .buscador {
		border-bottom: 1px solid #BDBDBD;
	}
	.subreport .buscador > tbody > tr {
		border-left: 1px solid #BDBDBD;
		border-right: 1px solid #BDBDBD;
	}
	.subreport .buscador > tbody > tr.subtotal {
		border-left: none;
		border-right: none;
	}
	.subreport .buscador > tbody > tr.subtotal td.level2 {
		text-align: left;
		color: #777;
		font-weight: normal !important;
	}
</style>
<table width="100%">
	<tr>
		<td>
			<form method="POST" name="form_reporte_saldo" action="#" id="form_reporte_saldo">
				<input id="xdesde" name="xdesde" type="hidden" value="">
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
						<?php UtilesApp::FiltroAsuntoContrato($Sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, $id_contrato); ?>
						<tr>
							<td align="right">
								<label for="tipo_liquidacion"><?php echo __('Mostrar Saldo') ?></label>
							</td>
							<td colspan="2" align="left">
								<?php
								echo Html::SelectArrayDecente($tipos_liquidacion, 'tipo_liquidacion', $_REQUEST['tipo_liquidacion'], '', __('Total'))
								?>
							</td>
						</tr>
						<tr>
							<td align="right">
								<?php echo __('Grupo Cliente'); ?>
							</td>
							<td colspan="2" align="left">
								<?php echo $Form->select('id_grupo_cliente', GrupoCliente::obtenerGruposSelect($Sesion), $id_grupo_cliente); ?>
							</td>
						</tr>
						<tr>
							<td align="right">
								<label for="moneda_mostrar"><?php echo __('Mostrar Montos en') ?></label>
							</td>
							<td colspan="2" align="left">
								<?php
								echo Html::SelectQuery($Sesion, 'SELECT id_moneda, glosa_moneda FROM prm_moneda', 'moneda_mostrar', $_REQUEST['moneda_mostrar']);
								?>
							</td>
						</tr>
						<tr>
							<td align="right">
								<label for="encargado_comercial"><?php echo __('Encargado Comercial') ?></label>
							</td>
							<td colspan="2" align="left"><!-- Nuevo Select -->
						        <?php echo $Form->select('encargado_comercial', UsuarioExt::QueryComerciales($Sesion), $_REQUEST['encargado_comercial'], array('empty' => __('Cualquiera'))); ?>
							</td>
						</tr>
						<tr>
							<td align="right"><?php echo __('Fecha Desde') ?></td>
							<td nowrap align="left">
								<input class="fechadiff" type="text" name="fecha1" value="<?php echo $fecha1 ?>" id="fecha1" size="11" maxlength="10" />
							</td>
							<td nowrap align="left" colspan="2">
								&nbsp;&nbsp; <?php echo __('Fecha Hasta') ?>
								<input class="fechadiff" type="text" name="fecha2" value="<?php echo $fecha2 ?>" id="fecha2" size="11" maxlength="10" />
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
							<td>&nbsp;</td>
							<td colspan="3" align="left">
								<label>
									<input type="hidden" name="mostrar_detalle" value="0" />
									<input type="checkbox" name="mostrar_detalle" id="mostrar_detalle" value="1" <?php echo $mostrar_detalle ? 'checked="checked"' : '' ?> />
									<?php echo __('Mostrar detalle del saldo'); ?>
								</label>
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td colspan="3" align="left">
								<label>
									<input type="hidden" name="ocultar_clientes_sin_saldo" value="0" />
									<input type="checkbox" name="ocultar_clientes_sin_saldo" id="ocultar_clientes_sin_saldo" value="1" <?php echo $ocultar_clientes_sin_saldo ? 'checked="checked"' : '' ?> />
									<?php echo __('Ocultar clientes sin saldo'); ?>
								</label>
							</td>
						</tr>
						<tr>
							<td></td>
							<td colspan="2" align="left">
								<input name="boton_buscar" id="boton_buscar" type="submit" value="<?php echo __('Buscar') ?>" class="btn" />
							</td>
							<td width="40%" align="right">
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
	jQuery(document).ready(function() {
		jQuery('#boton_xls').click(function() {
			jQuery('#opcion').val('xls');
		});
		jQuery('#boton_buscar').click(function() {
			jQuery('#opcion').val('buscar');
		});

		jQuery('.saldo:contains(-)').css('color', '#f00');
		jQuery('.saldo:not(:contains(-))').css('color', '#00f');
		jQuery('.subtotal td').css('font-weight', 'bold');

		jQuery('td.asunto').each(function() {
			var td = jQuery(this);
			var contenido = td.html();
			td.html('');
			try {
				if (contenido.length > 0 && contenido != 'asunto') {
					var _contenido = contenido.split(',');
					if (_contenido.length > 5) {
						td.append(_contenido.length + ' asuntos');
					} else {
						jQuery.each(_contenido, function(k,v) {
						var _v = v.split('|');
							td.append(jQuery('<a/>', {
								text: _v[0],
								style: 'white-space:nowrap;',
								href: 'javascript:void(0)',
								onMouseover: 'ddrivetip("' + _v[1] + '")',
								onMouseout: 'hideddrivetip()'
							})).append(' ');
						});
					}
				}
			} catch (err) {
			}
		});
	});
</script>
<?php
if ($_REQUEST['opcion'] == 'buscar') {

	$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Html');
	echo $writer->save();

	foreach ($results as $fila) {
		$saldo_total += $fila['saldo_base'];
	}
	$moneda_base = $fila['moneda_base'];

	$color = $saldo_total < 0 ? 'red' : 'blue';
	$resultado = '<span style="color: ' . $color . '">' . $moneda_base . ' ' . number_format($saldo_total, 2, ',', '.') . '</span>';
}

$Pagina->PrintBottom();

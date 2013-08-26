<?php
require_once dirname(__FILE__) . '/../../conf.php';

require_once APPPATH . '/app/classes/Reportes/SimpleReport.php';

$Sesion = new Sesion(array('REP'));


if (in_array($_REQUEST['opcion'], array('buscar', 'xls', 'json'))) {

	$id_tarifa_comparativa = $_REQUEST['id_tarifa_comparativa'];
	$query_tarifa = "SELECT glosa_tarifa FROM tarifa WHERE id_tarifa = '$id_tarifa_comparativa'";
	list($glosa_tarifa) = $Sesion->pdodbh->query($query_tarifa)->fetchAll(PDO::FETCH_COLUMN,0);
	$codigo_cliente = '';
	$where = array();

	if (!empty($_REQUEST['codigo_cliente'])) {
		$codigo_cliente = $_REQUEST['codigo_cliente'];
		$query_cliente = "SELECT glosa_cliente FROM cliente WHERE codigo_cliente = '$codigo_cliente'";
		list($glosa_cliente) = $Sesion->pdodbh->query($query_cliente)->fetchAll(PDO::FETCH_COLUMN,0);
		$where[] = "cobro.codigo_cliente = '$codigo_cliente'";
	}

	if (!empty($_REQUEST['tipo_liquidacion'])) {
		$tipo_liquidacion = $_REQUEST['tipo_liquidacion'];
		$where[] = "cobro.forma_cobro = '$tipo_liquidacion'";
	}

	$fecha1 = $_REQUEST['fecha1'];
	$fecha2 = $_REQUEST['fecha2'];

	$moneda_mostrar = $_REQUEST['moneda_mostrar'];
	if (empty($moneda_mostrar)) {
		$moneda_base = Utiles::MonedaBase($Sesion);
		$moneda_mostrar = $moneda_base['id_moneda'];
	}
	list($moneda) = Moneda::GetMonedas($Sesion, $moneda_mostrar);

	if ($fecha1) {
		$where[] = "cobro.fecha_emision >= '" . Utiles::fecha2sql($fecha1) . "'";
	}
	if ($fecha2) {
		$where[] = "cobro.fecha_emision <= '" . Utiles::fecha2sql($fecha2) . "'";
	}

	$SimpleReport = new SimpleReport($Sesion);
	$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($Sesion));

	$filters = array(
		__('Cliente') => $glosa_cliente,
		__('Forma de Tarificación') => $tipo_liquidacion,
		__('Fecha Desde') => $fecha1,
		__('Fecha Hasta') => $fecha2,
		__('Tarifa Comparativa') => $glosa_tarifa,
		__('Moneda') => $moneda['glosa_moneda']
	);
	$SimpleReport->SetFilters($filters);

	$Tarifa = new Tarifa($Sesion);
	$CategoriaTarifa = new CategoriaTarifa($Sesion);

	$variables = array('data' => array('moneda' => $moneda['simbolo']));
	$tarifas = $CategoriaTarifa->TarifasCategorias($id_tarifa_comparativa, $moneda_mostrar);
	$promedio = 'AVERAGE($Tarifa Comparativa ' . implode('$,$Tarifa Comparativa ', array_keys($tarifas)) . '$)';
	foreach ($tarifas as $nombre => $valor) {
		$variables[] = array(
			'row' => $nombre,
			'col' => 'Tarifa Comparativa',
			'name' => "Tarifa Comparativa $nombre",
			'value' => $valor,
			'extras' => array(
				'attrs' => 'style="text-align:right;"',
				'symbol' => 'moneda'
			)
		);
	}
	foreach ($tarifas as $nombre => $valor) {
		$variables[] = array(
			'row' => $nombre,
			'col' => 'Factor Comparativa',
			'name' => "Factor Tarifa Comparativa $nombre",
			'value' => "=\$Tarifa Comparativa $nombre\$/$promedio"
		);
	}

	$tarifas = $CategoriaTarifa->TarifasCategorias($Tarifa->SetTarifaDefecto(), $moneda_mostrar);
	$promedio = 'AVERAGE($Tarifa Standard ' . implode('$,$Tarifa Standard ', array_keys($tarifas)) . '$)';
	foreach ($tarifas as $nombre => $valor) {
		$variables[] = array(
			'row' => $nombre,
			'col' => 'Tarifa Standard',
			'name' => "Tarifa Standard $nombre",
			'value' => $valor,
			'extras' => array(
				'attrs' => 'style="text-align:right;"',
				'symbol' => 'moneda'
			)
		);
	}
	foreach ($tarifas as $nombre => $valor) {
		$variables[] = array(
			'row' => $nombre,
			'col' => 'Factor Standard',
			'name' => "Factor Tarifa Standard $nombre",
			'value' => "=\$Tarifa Standard $nombre\$/$promedio"
		);
	}
	$SimpleReport->SetVariables($variables);

	$config_reporte = array(
		array(
			'field' => 'id_cobro',
			'title' => __('Nº Liquidación'),
			'extras' => array(
				'attrs' => 'width="28%" style="text-align:left;"',
				'groupinline' => true
			)
		),
		array(
			'field' => 'fecha',
			'title' => 'Fecha',
			'format' => 'date',
			'extras' => array(
				'inlinegroup_field' => 'id_cobro'
			)
		),
		array(
			'field' => 'username',
			'title' => 'Encargado comercial',
			'extras' => array(
				'inlinegroup_field' => 'id_cobro'
			)
		),
		array(
			'field' => 'glosa_cliente',
			'title' => 'Cliente',
			'extras' => array(
				'inlinegroup_field' => 'id_cobro'
			)
		),
		array(
			'field' => 'forma_cobro',
			'title' => 'Tipo',
			'extras' => array(
				'inlinegroup_field' => 'id_cobro'
			)
		),
		array(
			'field' => 'glosa_tarifa',
			'title' => 'Tarifa',
			'extras' => array(
				'inlinegroup_field' => 'id_cobro'
			)
		),
		array(
			'field' => 'moneda_cobro_codigo',
			'title' => 'Moneda original',
			'extras' => array(
				'inlinegroup_field' => 'id_cobro'
			)
		),
		array(
			'field' => 'monto_honorarios',
			'title' => 'Honorarios',
			'format' => 'number',
			'extras' => array(
				'attrs' => 'style="text-align:right;"',
				'symbol' => 'moneda_base_simbolo',
				'inlinegroup_field' => 'id_cobro',
				'subtotal' => false
			)
		),
		array(
			'field' => 'monto_gastos',
			'title' => 'Gastos',
			'format' => 'number',
			'extras' => array(
				'attrs' => 'style="text-align:right;"',
				'symbol' => 'moneda_base_simbolo',
				'inlinegroup_field' => 'id_cobro',
				'subtotal' => false
			)
		),
		array(
			'field' => 'total_minutos',
			'title' => 'Duración',
			'format' => 'time',
			'extras' => array(
				'inlinegroup_field' => 'id_cobro',
				'subtotal' => false
			)
		),
		array(
			'field' => 'categoria_usuario',
			'title' => 'Categoría'
		),
		array(
			'field' => 'usuario_categoria',
			'title' => 'Usuario'
		),
		array(
			'field' => 'duracion_usuario',
			'title' => 'Duración cobrada',
			'format' => 'time'
		),
		array(
			'field' => 'duracion_usuario_numero',
			'title' => 'Duración numérica',
			'format' => 'number',
			'extras' => array(
				'attrs' => 'style="display:none"',
				'subtotal' => false
			)
		),
		array(
			'field' => 'tarifa_usuario',
			'title' => 'Tarifa Usuario',
			'format' => 'number',
			'extras' => array(
				'attrs' => 'style="text-align:right;"',
				'symbol' => 'moneda_base_simbolo',
				'subtotal' => false
			)
		),
		array(
			'field' => 'monto_usuario',
			'title' => 'Monto cobrado',
			'format' => 'number',
			'extras' => array(
				'attrs' => 'style="text-align:right;"',
				'symbol' => 'moneda_base_simbolo'
			)
		),
		array(
			'field' => '=$Tarifa Comparativa %categoria_usuario%$',
			'title' => 'Tarifa Comparativa',
			'format' => 'number',
			'extras' => array(
				'attrs' => 'style="text-align:right;"',
				'symbol' => 'moneda_base_simbolo',
				'subtotal' => false
			)
		),
		array(
			'name' => 'monto_comparativo',
			'field' => '=PRODUCT($Tarifa Comparativa %categoria_usuario%$,%duracion_usuario_numero%)',
			'title' => 'Monto Comparativo',
			'format' => 'number',
			'extras' => array(
				'attrs' => 'style="text-align:right;"',
				'symbol' => 'moneda_base_simbolo'
			)
		),
		array(
			'name' => 'total_comparativo',
			'field' => '=SUMIF(:id_cobro:,%id_cobro%,:monto_comparativo:)',
			'title' => 'Total Comparativo',
			'format' => 'number',
			'extras' => array(
				'attrs' => 'style="text-align:right;display:none"',
				'symbol' => 'moneda_base_simbolo',
				'inlinegroup_field' => 'id_cobro',
				'subtotal' => false
			)
		),
		array(
			'field' => '=PRODUCT(%monto_comparativo%,%monto_honorarios%)/%total_comparativo%',
			'title' => 'Aporte Comparativo',
			'format' => 'number',
			'extras' => array(
				'attrs' => 'style="text-align:right;display:none"',
				'symbol' => 'moneda_base_simbolo'
			)
		),
		array(
			'field' => '=$Factor Tarifa Comparativa %categoria_usuario%$',
			'title' => 'Factor Comparativo',
			'format' => 'number',
			'extras' => array(
				'subtotal' => false
			)
		),
		array(
			'field' => '=PRODUCT($Factor Tarifa Comparativa %categoria_usuario%$,%duracion_usuario_numero%)',
			'title' => 'Horas x Factor Comparativo',
			'format' => 'number',
			'extras' => array(
				'subtotal' => false
			)
		),
		array(
			'field' => '=$Tarifa Standard %categoria_usuario%$',
			'title' => 'Tarifa Standard',
			'format' => 'number',
			'extras' => array(
				'attrs' => 'style="text-align:right;"',
				'symbol' => 'moneda_base_simbolo',
				'subtotal' => false
			)
		),
		array(
			'name' => 'monto_standard',
			'field' => '=PRODUCT($Tarifa Standard %categoria_usuario%$,%duracion_usuario_numero%)',
			'title' => 'Monto Standard',
			'format' => 'number',
			'extras' => array(
				'attrs' => 'style="text-align:right;"',
				'symbol' => 'moneda_base_simbolo'
			)
		),
		array(
			'name' => 'total_standard',
			'field' => '=SUMIF(:id_cobro:,%id_cobro%,:monto_standard:)',
			'title' => 'Total Standard',
			'format' => 'number',
			'extras' => array(
				'attrs' => 'style="text-align:right;display:none"',
				'symbol' => 'moneda_base_simbolo',
				'inlinegroup_field' => 'id_cobro',
				'subtotal' => false
			)
		),
		array(
			'field' => '=PRODUCT(%monto_standard%,%monto_honorarios%)/%total_standard%',
			'title' => 'Aporte Standard',
			'format' => 'number',
			'extras' => array(
				'attrs' => 'style="text-align:right;display:none"',
				'symbol' => 'moneda_base_simbolo'
			)
		),
		array(
			'field' => '=$Factor Tarifa Standard %categoria_usuario%$',
			'title' => 'Factor Standard',
			'format' => 'number',
			'extras' => array(
				'subtotal' => false
			)
		),
		array(
			'field' => '=PRODUCT($Factor Tarifa Standard %categoria_usuario%$,%duracion_usuario_numero%)',
			'title' => 'Horas x Factor Standard',
			'format' => 'number',
			'extras' => array(
				'subtotal' => false
			)
		),
	);

	$SimpleReport->LoadConfigFromArray($config_reporte);

	if (count($where) > 0) {
		$where = implode(' AND ', $where);
	} else {
		$where = "1 > 0";
	}

	$query =
		"SELECT
			cobro.id_cobro,
			cobro.fecha_emision AS fecha,
			usuario_contrato.username,
			cliente.glosa_cliente,
			cobro.forma_cobro,
			tarifa.glosa_tarifa,
			moneda_cobro.id_moneda AS moneda_cobro_id,
			moneda_cobro.codigo AS moneda_cobro_codigo,
			moneda_cobro.simbolo AS moneda_cobro_simbolo,
			moneda_base.simbolo AS moneda_base_simbolo,
			cobro.monto * (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio) AS monto_honorarios,
			cobro.monto_thh_estandar * (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio) AS monto_honorarios_base,
			cobro.subtotal_gastos * (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio) AS monto_gastos,
			cobro.total_minutos / 60 AS total_minutos,
			prm_categoria_usuario.id_categoria_usuario AS id_categoria_usuario,
			prm_categoria_usuario.glosa_categoria AS categoria_usuario,
			usuario_trabajo.username AS usuario_categoria,
			SUM(TIME_TO_SEC(trabajo.duracion_cobrada) / 3600) AS duracion_usuario,
			SUM(TIME_TO_SEC(trabajo.duracion_cobrada) / 3600) AS duracion_usuario_numero,
			SUM(trabajo.monto_cobrado) * (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio) AS monto_usuario,
			SUM(trabajo.monto_cobrado) * (cobro_moneda_cobro.tipo_cambio / cobro_moneda_base.tipo_cambio) / SUM(TIME_TO_SEC(trabajo.duracion_cobrada) / 3600) AS tarifa_usuario
		FROM cobro
		INNER JOIN prm_moneda moneda_cobro ON moneda_cobro.id_moneda = cobro.id_moneda
		INNER JOIN prm_moneda moneda_base ON moneda_base.id_moneda = $moneda_mostrar
		INNER JOIN cobro_moneda cobro_moneda_cobro ON  cobro_moneda_cobro.id_cobro = cobro.id_cobro AND cobro_moneda_cobro.id_moneda = cobro.id_moneda
		INNER JOIN cobro_moneda cobro_moneda_base ON  cobro_moneda_base.id_cobro = cobro.id_cobro AND cobro_moneda_base.id_moneda = $moneda_mostrar
		INNER JOIN contrato ON contrato.id_contrato = cobro.id_contrato
		INNER JOIN usuario usuario_contrato ON usuario_contrato.id_usuario = contrato.id_usuario_responsable
		INNER JOIN tarifa ON tarifa.id_tarifa = contrato.id_tarifa
		INNER JOIN cliente ON cliente.codigo_cliente = cobro.codigo_cliente
		LEFT JOIN trabajo ON trabajo.id_cobro = cobro.id_cobro
		INNER JOIN usuario usuario_trabajo ON usuario_trabajo.id_usuario = trabajo.id_usuario
		INNER JOIN prm_categoria_usuario ON prm_categoria_usuario.id_categoria_usuario = usuario_trabajo.id_categoria_usuario
		WHERE cobro.estado IN ('PAGADO', 'FACTURADO')
		AND $where
		GROUP BY cobro.id_cobro, usuario_trabajo.id_usuario";

	 // echo $query;
	 //echo $query_adelantos;
	 //echo $query_gastos;
	 //echo $query_liquidaciones;
	 // exit;

	$statement = $Sesion->pdodbh->prepare($query);
	$statement->execute();
	$results = $statement->fetchAll(PDO::FETCH_ASSOC);

	$SimpleReport->LoadResults($results);

	if ($_REQUEST['opcion'] == 'xls') {
		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Spreadsheet');
		$writer->save('Reporte_comparativo_tarifas');
	}
}

$Pagina = new Pagina($Sesion);

$Pagina->titulo = __('Reporte Comparativo Tarifas');
$Pagina->PrintTop($popup);
?>
<style>
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
							<td align="right"><?php echo __('Forma de Tarificación'); ?></td>
							<td align="left" colspan="3">
			<?php // echo Html::SelectArrayDecente(array('TASA' => 'Tasas/HH', 'RETAINER' => 'Retainer', 'FLAT FEE' => 'Flat fee', 'CAP' => 'Cap', 'PROPORCIONAL' => 'Proporcional', 'HITOS' => 'Hitos'), "tipo_liquidacion", $tipo_liquidacion, '', 'Cualquiera') ?>
			<?php echo Html::SelectQuery($Sesion, "SELECT forma_cobro, descripcion FROM prm_forma_cobro", "tipo_liquidacion", $tipo_liquidacion, '', 'Cualquiera') ?>
							</td>
						</tr>
						<tr>
							<td align="right"><?php echo __('Fecha Desde') ?></td>
							<td nowrap align="left">
									<input class="fechadiff" type="text" name="fecha1" value="<?php echo $fecha1 ?>" id="fecha1" size="11" maxlength="10" />
							</td>
							<td nowrap align="left" colspan="2">
									&nbsp;&nbsp; <?php echo __('Fecha Hasta') ?>
									<input  class="fechadiff" type="text" name="fecha2" value="<?php echo $fecha2 ?>" id="fecha2" size="11" maxlength="10" />
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td colspan="3" align="left">
								<label>
									<input type="hidden" name="incluir_gastos" value="0" />
									<input type="checkbox" name="incluir_gastos" id="incluir_gastos" value="1" <?php echo $incluir_gastos ? 'checked="checked"' : '' ?> />
									<?php echo __('Incluir gastos en el cálculo'); ?>
								</label>
							</td>
						</tr>
						<tr>
							<td align="right">
								<label for="moneda_mostrar"><?php echo __('Moneda') ?></label>
							</td>
							<td colspan="2" align="left">
								<?php
								echo Html::SelectArray(Moneda::GetMonedas($Sesion), 'moneda_mostrar', $_REQUEST['moneda_mostrar']);
								?>
							</td>
						</tr>
						<tr>
							<td align="right">Tarifa Comparativa</td>
							<td colspan="3" align="left">
								<?php echo Html::SelectQuery($Sesion, "SELECT id_tarifa, glosa_tarifa FROM tarifa WHERE tarifa_flat IS NULL ORDER BY glosa_tarifa", "id_tarifa_comparativa", $id_tarifa_comparativa); ?>
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
		jQuery(document).ready(function () {
			jQuery('#boton_xls').click(function(){
				jQuery('#opcion').val('xls');
			});
			jQuery('#boton_buscar').click(function(){
				jQuery('#opcion').val('buscar');
			});
		});
</script>
<?php
if ($_REQUEST['opcion'] == 'buscar') {

	$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Html');
	echo $writer->save();
	// echo '<pre style="text-align: left; color: red;">' . $query_gastos . "</pre>";
	// echo '<pre style="text-align: left; color: blue;">' . $query_liquidaciones . "</pre>";
	// echo '<pre style="text-align: left; color: green;">' . $query_adelantos . "</pre>";
	// echo '<pre style="text-align: left; color: grey;">' . $query . "</pre>";

	// echo '<div style="text-align: right; font-size: 2em;">Saldo total: ' . $resultado . '</h1>';
}

//echo '<pre>' . print_r($query, true) . '</pre>';

$Pagina->PrintBottom();
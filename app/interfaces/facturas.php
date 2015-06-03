<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

$sesion = new Sesion(array('COB'));
$pagina = new Pagina($sesion);

$DocumentoLegalNumero = new DocumentoLegalNumero($sesion);
$factura = new Factura($sesion);

if ($id_factura != '') {
	$factura->Load($id_factura);
}

if ($opc == 'generar_factura') {
	if ($id_factura_grabada) {
		include dirname(__FILE__) . '/factura_doc.php';
		exit;
	} else {
		die(__('Factura no existe!'));
	}
} else if ($opc == 'generar_factura_pdf') {
	if ($id_factura_grabada) {
		$factura_pdf_datos = new FacturaPdfDatos($sesion);
		$factura_pdf_datos->generarFacturaPDF($id_factura_grabada);
		exit;
	} else {
		die(__('Factura no existe!'));
	}
}

$idioma_default = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
$idioma_default->Load(strtolower(Conf::GetConf($sesion, 'Idioma')));

global $factura;
($Slim = Slim::getInstance()) ? $Slim->applyHook('hook_factura_inicio') : false;

$orden = $factura->OrdenReporte($orden);
if ($opc == 'buscar' || $opc == 'generar_factura') {
	if ($exportar_excel || $archivo_contabilidad) {
		$results = $factura->DatosReporte($orden, $where, $numero, $fecha1, $fecha2
				, $tipo_documento_legal_buscado, $codigo_cliente, $codigo_cliente_secundario
				, $codigo_asunto, $codigo_asunto_secundario, $id_contrato, $id_estudio
				, $id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social
				, $descripcion_factura, $serie, $desde_asiento_contable, $opciones);

		if ($exportar_excel) {
			// Horrible workaround mientras se optimiza la funcion UtilesApp.php@ProcesaCobroIdMoneda
			set_time_limit(0);

			$factura->DownloadExcel($results);
		} else if ($archivo_contabilidad) {
			$data = array('Resultados' => $results);
			$Slim = Slim::getInstance() ? $Slim->applyHook('hook_facturas_genera_archivo_contabilidad', &$data) : false;
		}
		exit;
	}
}

$pagina->titulo = __('Revisar Documentos Tributarios');
$pagina->PrintTop();

$estudios_array = PrmEstudio::GetEstudios($sesion);
?>

<script type="text/javascript">
	function CrearNuevoDocumentoLegal() {
		var dl_url = 'agregar_factura.php?popup=1&id_documento_legal=' + $('tipo_documento_legal').value;
		if ($('codigo_cliente')) {
			dl_url += '&codigo_cliente=' + $('codigo_cliente').value
		}

		if ($('id_cobro')) {
			dl_url += '&id_cobro=' + $('id_cobro').value
			$('id_cobro').focus();
		}
		nuovaFinestra('Agregar_Factura', 730, 580, dl_url, 'top=100, left=155');//')	';
	}

	function ImprimirDocumento(id_factura) {
		var fecha1 = $('fecha1').value;
		var fecha2 = $('fecha2').value;
		var vurl = 'facturas.php?opc=generar_factura&id_factura_grabada=' + id_factura + '&fecha1=' + fecha1 + '&fecha2=' + fecha2;
		self.location.href = vurl;
	}

	function ImprimirPDF(id_factura) {
		var vurl = 'facturas.php?opc=generar_factura_pdf&id_factura_grabada=' + id_factura;
		self.location.href = vurl;
	}

	function Refrescar() {
		document.form_buscador.submit();
	}

	function BuscarFacturas(form, from) {
		if (!form) {
			var form = $('form_facturas');
		}

		switch (from) {
			case 'buscar':
				form.action = 'facturas.php?buscar=1';
				break;

			case 'exportar_excel':
				form.action = 'facturas.php?opc=buscar&exportar_excel=1';
				break;

			default:
				return false;
		}

		form.submit();
		return true;
	}

	function AgregarNuevo() {
		var urlo = "agregar_factura.php?popup=1";
		nuovaFinestra('Agregar_Factura', 730, 470, urlo, 'top=100, left=125');
	}

<?php ($Slim = Slim::getInstance()) ? $Slim->applyHook('hook_facturas_js') : false; ?>
</script>

<form method="post" name="form_facturas" id="form_facturas">
	<input type="hidden" name="opc" id="opc" value="buscar">
	<?php if (count($estudios_array) <= 1) { ?>
		<input type="hidden" name="id_estudio" value="<?php echo $estudios_array[0]['id_estudio']; ?>" />
	<?php } ?>
	<table width="90%">
		<tr>
			<td>
				<fieldset class="tb_base" style="width: 100%; border: 1px solid #BDBDBD;">
					<legend><?php echo __('Filtros'); ?></legend>
					<table border="0" width='720px'>
						<tr>
							<td align="right" width="20%">
								<?php echo __('Cliente'); ?>
							</td>
							<td colspan="3" align="left" nowrap>
								<?php UtilesApp::CampoCliente($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
							</td>
						</tr>
						<tr>
							<td align="right" width="20%">
								<?php echo __('Asunto'); ?>
							</td>
							<td colspan="3" align="left" nowrap>
								<?php UtilesApp::CampoAsunto($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
							</td>
						</tr>

						<?php ($Slim = Slim::getInstance()) ? $Slim->applyHook('hook_filtros_facturas') : false; ?>

						<tr>
							<td align="right">
								<?php echo __('Raz�n Social'); ?>
							</td>
							<td align="left" colspan="3" >
								<input type="text" name="razon_social" id="razon_social" value="<?php echo $razon_social; ?>" size="72">
							</td>
						</tr>
						<tr>
							<td align="right">
								<?php echo __('Descripci�n'); ?>
							</td>
							<td align="left" colspan="3" >
								<input type="text" name="descripcion_factura" id="descripcion_factura" value="<?php echo $descripcion_factura; ?>" size="72">
							</td>
						</tr>
						<tr>
							<td align="right">
								<?php echo __('Tipo de Documento'); ?>
							</td>
							<td align="left">
								<?php echo Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal", 'tipo_documento_legal_buscado', $tipo_documento_legal_buscado, '', 'Cualquiera', 150); ?>
							</td>
							<td align="right">
								<?php echo __('Grupo Ventas'); ?>
								<input type="checkbox" name="grupo_ventas" id="grupo_ventas" value="1" <?php echo $grupo_ventas ? 'checked' : ''; ?>>
							</td>
						</tr>
						<tr>
							<td align="right">
								<?php echo __('Estado'); ?>
							</td>
							<td align="left">
								<?php echo Html::SelectQuery($sesion, "SELECT id_estado, glosa FROM prm_estado_factura ORDER BY id_estado ASC", "id_estado", $id_estado, 'onchange="mostrarAccionesEstado(this.form)"', 'Cualquiera', "150"); ?>
							</td>
						</tr>
						<tr>
							<td align="right">
								<?php echo __('Moneda'); ?>
							</td>
							<td align="left">
								<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY glosa_moneda ASC", "id_moneda", $id_moneda, '', 'Cualquiera', "150"); ?>
							</td>
						</tr>

						<?php if (count($estudios_array) > 1) { ?>
							<tr>
								<td align="right"><?php echo __('Compan�a'); ?></td>
								<td align="left" width="18%">
									<?php echo Html::SelectArray($estudios_array, 'id_estudio', $id_estudio, '', 'Todas'); ?>
								</td>
							</tr>
						<?php } ?>

						<tr>
							<td align="right">
								<?php echo __('N� Factura'); ?>
							</td>
							<td align="left" width="18%" nowrap>
								<?php if (Conf::GetConf($sesion, 'NumeroFacturaConSerie')) {
									echo Html::SelectQuery($sesion, $DocumentoLegalNumero->SeriesQuery($id_estudio), 'serie', $serie, 'onchange="NumeroDocumentoLegal()"', 'Vacio', 60);
									?>
									<span style="vertical-align: center;">-</span>
								<?php } ?>
								<input onkeydown="if (event.keyCode == 13) BuscarFacturas(this.form, 'buscar');" type="text" id="numero" name="numero" size="15" value="<?php echo $numero; ?>" onchange="this.value = this.value.toUpperCase();">
							</td>
							<td align="right" width="18%">
								<?php echo __('N� Cobro'); ?>
							</td>
							<td align="left" width="44%">
								<input onkeydown="if (event.keyCode == 13) BuscarFacturas(this.form, 'buscar');" type="text" id="id_cobro" name="id_cobro" size="15" value="<?php echo $id_cobro; ?>">
							</td>
						</tr>
						<tr>
							<td align="right">
								<?php echo __('Fecha Inicio'); ?>
							</td>
							<td nowrap align=left>
								<input type="text" id="fecha1" class="fechadiff" name="fecha1" value="<?php echo $fecha1; ?>" id="fecha1" size="11" maxlength="10">
							</td>
							<td align="right">
								<?php echo __('Fecha Fin'); ?>
							</td>
							<td align="left" width="44%">
								<input type="text" id="fecha2" class="fechadiff" name="fecha2" value="<?php echo $fecha2; ?>" id="fecha2" size="11" maxlength="10">
							</td>
						</tr>
						<tr id="fila_botones">
							<td colspan="4" style="text-align:center;margin:auto;">
								<a name="boton_buscar" id="boton_buscar" class="btn botonizame" icon="find" onclick="BuscarFacturas(jQuery('#form_facturas').get(0), 'buscar')"><?php echo __('Buscar'); ?></a>
								<a name="boton_excel" id="boton_descarga" class="btn botonizame" icon="xls" onclick="BuscarFacturas(jQuery('#form_facturas').get(0), 'exportar_excel')"><?php echo __('Descargar Excel'); ?></a>
								<?php ($Slim = Slim::getInstance()) ? $Slim->applyHook('hook_factura_fin') : false; ?>
							</td>
						</tr>
					</table>
				</fieldset>
			</td>
		</tr>
	</table>
</form>

<?php
if ($opc == 'buscar' || $opc == 'generar_factura') {

	$where = '';

	// Obtengo el saldo de las facturas seg�n la query filtrada
	$saldos_monedas = $factura->SaldoReporte($orden, $where, $numero, $fecha1, $fecha2
			, $tipo_documento_legal_buscado, $codigo_cliente, $codigo_cliente_secundario
			, $codigo_asunto, $codigo_asunto_secundario, $id_contrato, $id_estudio
			, $id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social
			, $descripcion_factura, $serie, $desde_asiento_contable, $opciones);

	$formato_saldos = array();
	foreach ($saldos_monedas as $i => $saldo_moneda) {
		$formato_saldos[] = UtilesApp::PrintFormatoMoneda($sesion, $saldo_moneda['saldo'], $saldo_moneda['id_moneda'], $saldo_moneda['simbolo'], $saldo_moneda['cifras_decimales']);
	}
	if (count($formato_saldos) > 0) {
		$glosa_monto_saldo_total = "<strong>Saldo: " . implode(' | ', $formato_saldos) . "</strong>";
	}

	$SimpleReport = new SimpleReport($sesion);
	$config = $SimpleReport->LoadConfiguration('FACTURAS');

	$opciones['mostrar_pagos'] = true;
	$opciones['mostrar_fecha_ultimo_pago'] = true;
	$where = '';
	$search_query = $factura->QueryReporte(false, $where, $numero, $fecha1, $fecha2
			, $tipo_documento_legal_buscado, $codigo_cliente, $codigo_cliente_secundario
			, $codigo_asunto, $codigo_asunto_secundario, $id_contrato, $id_estudio
			, $id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social
			, $descripcion_factura, $serie, $desde_asiento_contable, $opciones);

	$x_pag = 25;
	$b = new Buscador($sesion, $search_query, 'Factura', $desde, $x_pag, $orden);
	$b->titulo = "Documentos Tributarios<br />$glosa_monto_saldo_total";
	$b->AgregarEncabezado('cliente', __('Destinatario Documento'), 'width="30%" align="left"', '', 'FormatoDestinatario', true);
	$b->AgregarEncabezado('fecha', __('Fecha Documento'), 'align="center"');
	$b->AgregarEncabezado('numero', __('Datos Documento'), 'width="10%" align="left"', '', 'FormatoDatos', true);

	if ($config->columns['encargado_comercial']->visible) {
		$b->AgregarEncabezado("encargado_comercial", __('Socio a cargo'), 'align="center"');
	}

	$b->AgregarEncabezado('id_cobro', __('N� Liquidaci�n'), 'align="center"', '', 'FormatoLiquidacion', true);
	$b->AgregarFuncion(__('Total'), 'FormatoTotal', 'align="left"');
	$b->AgregarFuncion(__('Pagos'), 'FormatoPagos', 'align="left"');
	$b->AgregarEncabezado('estado', __('Estado'), 'align="center"');
	$b->AgregarFuncion(__('Opciones'), 'Opciones', 'style="white-space:nowrap" align="right"');
	$b->color_mouse_over = '#bcff5c';
	$b->Imprimir();
}

function FormatoDestinatario($fila) {
	global $sesion;

	$html = '';
	if (!empty($fila->fields['glosa_cliente'])) {
		$html .= "<b>Cliente</b>: {$fila->fields['glosa_cliente']}<br />";
	}
	if (!empty($fila->fields['factura_rsocial'])) {
		$html .= "<b>Raz�n Social</b>: {$fila->fields['factura_rsocial']}<br />";
	}
	if (!empty($fila->fields['descripcion'])) {
		$html .= "<b>Descripci�n</b>: {$fila->fields['descripcion']}";
	}

	return $html;
}

function FormatoDatos($fila) {
	global $sesion;
	$html = '';
	if (!empty($fila->fields['tipo'])) {
		$html .= "<b>Tipo</b>: {$fila->fields['tipo']}<br />";
	}
	if (Conf::GetConf($sesion, 'NumeroFacturaConSerie') && !empty($fila->fields['serie_documento_legal'])) {
		$html .= "<b>Serie</b>: {$fila->fields['serie_documento_legal']}<br />";
	}

	$html .= "<b>N�mero</b>: {$fila->fields['numero']}";

	return $html;
}

function FormatoLiquidacion($fila) {
	return "<a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_Cobro',950,660,'cobros6.php?id_cobro={$fila->fields['id_cobro']}&amp;popup=1');\">{$fila->fields['id_cobro']}</a>";
}

function FormatoMoneda($sesion, $numero, $moneda) {
	return UtilesApp::PrintFormatoMoneda($sesion, $numero, $moneda, '', '', '&nbsp;');
}

function FormatoTotal($fila) {
	global $sesion;
	$subtotal = FormatoMoneda($sesion, $fila->fields['subtotal'] + $fila->fields['subtotal_gastos'], $fila->fields['id_moneda']);
	$iva = FormatoMoneda($sesion, $fila->fields['iva'], $fila->fields['id_moneda']);
	$total = FormatoMoneda($sesion, $fila->fields['total'], $fila->fields['id_moneda']);
	$glosa_iva = __('IVA');

	$html =<<<HTML
	<strong>Subtotal:</strong>&nbsp;$subtotal<br />
	<strong>$glosa_iva:</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$iva<br />
	<strong>Total:</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$total
HTML;

	return $html;
}

function FormatoPagos($fila) {
	global $sesion;
	$pagos = FormatoMoneda($sesion, $fila->fields['pagos'], $fila->fields['id_moneda']);
	$saldo = FormatoMoneda($sesion, $fila->fields['saldo'], $fila->fields['id_moneda']);
	$fecha_ultimo_pago = Utiles::sql2fecha($fila->fields['fecha_ultimo_pago'], "%d/%m/%Y");
	if ($fecha_ultimo_pago == 'No existe fecha') {
		$fecha_ultimo_pago = 'N/A';
	}

	$html =<<<HTML
	<strong>Pagos:</strong>&nbsp;&nbsp;&nbsp;&nbsp;$pagos<br />
	<strong>Saldo:</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$saldo<br />
	<strong>�lt.&nbsp;Pago:</strong>&nbsp;$fecha_ultimo_pago
HTML;

	return $html;
}

function Opciones($fila) {
	global $sesion;

	$boton_editar = '<a class="fl ui-button editar" href="javascript:void(0);" style="margin: 3px 1px;width: 18px;height: 18px;" onclick="nuovaFinestra(\'Editar_Factura\',730,700,\'agregar_factura.php?' . 'id_factura=' . $fila->fields['id_factura'] . '&codigo_cliente=' . $fila->fields['codigo_cliente'] . '&popup=1\');" title="Editar Factura"></a>';

	if (Conf::GetConf($sesion, 'ImprimirFacturaDoc')) {
		$boton_word = '<a class="fl ui-button doc" href="javascript:void(0);" style="margin: 3px 1px;width: 18px;height: 18px;" onclick="ImprimirDocumento(' . $fila->fields['id_factura'] . ');" title="Descargar Word"></a>';
	}

	if (Conf::GetConf($sesion, 'ImprimirFacturaPdf')) {
		$boton_pdf = '<a class="fl ui-button pdf" href="javascript:void(0);" style="margin: 3px 1px;width: 18px;height: 18px;" onclick="ImprimirPDF(' . $fila->fields['id_factura'] . ');" title="Descargar PDF"></a>';
	}

	$boton_log = '<a class="fl ui-icon lupa logdialog" href="javascript:void(0);" rel="factura" id="factura_' . $fila->fields['id_factura'] . '"></a>';

	return "$boton_editar $boton_word $boton_pdf $boton_log";
}

$pagina->PrintBottom();

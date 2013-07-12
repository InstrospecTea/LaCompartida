<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

$sesion = new Sesion(array('COB'));
$pagina = new Pagina($sesion);

$serienumero_documento = new DocumentoLegalNumero($sesion);

$factura = new Factura($sesion);

if ($id_factura != "") {
	$factura->Load($id_factura);
}

if ($opc == 'generar_factura') {
	// POR HACER
	// mejorar
	if ($id_factura_grabada) {
		include dirname(__FILE__) . '/factura_doc.php';
		die();
	} else {
		die("Error");
	}
	die('Listo');
} else if ($opc == 'generar_factura_pdf') {
	if ($id_factura_grabada) {
		$factura_pdf_datos = new FacturaPdfDatos($sesion);
		$factura_pdf_datos->generarFacturaPDF($id_factura_grabada);
		die();
	} else {
		die(__('Factura no existe!'));
	}
}

if ($exportar_excel) {
	// Es necesaria esta bestialidad para que no se caiga cuando es llamada desde otro lado.
//		$no_activo = !$activo;
//		$multiple = true;
//		require_once Conf::ServerDir().'/interfaces/facturas_listado_xls.php';
//		exit;

}



if ($archivo_contabilidad) {
	require_once Conf::ServerDir() . '/interfaces/facturas_contabilidad_txt.php';
	exit;
}


$idioma_default = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
$idioma_default->Load(strtolower(UtilesApp::GetConf($sesion, 'Idioma')));

global $factura;
($Slim=Slim::getInstance()) ?  $Slim->applyHook('hook_factura_inicio'):false;

if ($opc == 'buscar' || $opc == 'generar_factura') {

	if ($exportar_excel) {
		$results = $factura->DatosReporte($orden, $where, $numero, $fecha1, $fecha2
			, $tipo_documento_legal_buscado, $codigo_cliente,$codigo_cliente_secundario
			, $codigo_asunto,$codigo_asunto_secundario, $id_contrato, $id_cia
			, $id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social
			, $descripcion_factura, $serie, $desde_asiento_contable);

		$factura->DownloadExcel($results);
		die();
	}
}

$pagina->titulo = __('Revisar Documentos Tributarios');
$pagina->PrintTop();

?>
<script type="text/javascript">
	function CrearNuevoDocumentoLegal()
	{
		var dl_url = 'agregar_factura.php?popup=1&id_documento_legal='+$('tipo_documento_legal').value;
		if($('codigo_cliente')){
			dl_url += '&codigo_cliente='+$('codigo_cliente').value
		}
		if($('id_cobro')){
			dl_url += '&id_cobro='+$('id_cobro').value
			$('id_cobro').focus();
		}
		nuovaFinestra('Agregar_Factura',730,580,dl_url, 'top=100, left=155');')	';
	}




	function ImprimirDocumento( id_factura )
	{
		var fecha1=$('fecha1').value;
		var fecha2=$('fecha2').value;
		var vurl = 'facturas.php?opc=generar_factura&id_factura_grabada=' + id_factura + '&fecha1=' + fecha1 + '&fecha2=' + fecha2;

		self.location.href=vurl;
	}

	function ImprimirPDF( id_factura )
	{
		var vurl = 'facturas.php?opc=generar_factura_pdf&id_factura_grabada=' + id_factura;
		self.location.href=vurl;
	}

	function Refrescar()
	{
		BuscarFacturas('','buscar');
	}

	function BuscarFacturas( form, from )
	{
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

<?php if (UtilesApp::GetConf($sesion, 'DescargarArchivoContabilidad')) { ?>
			case 'archivo_contabilidad':
				form.action = 'facturas.php?archivo_contabilidad=1';
				break;
<?php } ?>
			default:
				return false;
			}

			form.submit();
			return true;
		}

		function AgregarNuevo()
		{
			var urlo = "agregar_factura.php?popup=1";
			nuovaFinestra('Agregar_Factura',730,470,urlo,'top=100, left=125');
		}
</script>

 <form method='post' name="form_facturas" id="form_facturas">
	<input type='hidden' name='opc' id='opc' value='buscar'>

	<?php
		echo "<table width=\"90%\"><tr><td>";
		$class_diseno = 'class="tb_base" style="width: 100%; border: 1px solid #BDBDBD;"';

	?>
	<fieldset class="tb_base" style="width: 100%; border: 1px solid #BDBDBD;">
		<legend><?php echo __('Filtros') ?></legend>
		<table style="border: 0px solid black" width='720px'>
			<tr>
				<td align=right width="20%">
					<?php echo __('Cliente') ?>
				</td>
				<td colspan="3" align=left nowrap>
<?php UtilesApp::CampoCliente($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
				</td>
			</tr>
			<tr>
				<td align='right' width="20%">
					<?php echo __('Asunto') ?>
				</td>
				<td colspan="3" align=left nowrap>
<?php UtilesApp::CampoAsunto($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
				</td>
			</tr>
			</tr>
				<?php ($Slim=Slim::getInstance()) ?  $Slim->applyHook('hook_filtros_facturas'):false; ?>

			<tr>
			<tr>
				<td align=right>
					<?php echo __('Razón Social') ?>
				</td>
				<td align=left colspan="3" >
					<input type="text" name="razon_social" id="razon_social" value="<?php echo $razon_social; ?>" size="72">
				</td>
			</tr>
			<tr>
				<td align=right>
					<?php echo __('Descripción') ?>
				</td>
				<td align=left colspan="3" >
					<input type="text" name="descripcion_factura" id="descripcion_factura" value="<?php echo $descripcion_factura; ?>" size="72">
				</td>
			</tr>
			<tr>
				<td align=right>
					<?php echo __('Tipo de Documento') ?>
				</td>
				<td align=left >
<?php echo Html::SelectQuery($sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal", 'tipo_documento_legal_buscado', $tipo_documento_legal_buscado, '', 'Cualquiera', 150); ?>
				</td>
				<td align=right>
<?php echo __('Grupo Ventas') ?>
					<input type=checkbox name=grupo_ventas id=grupo_ventas value=1 <?php echo $grupo_ventas ? 'checked' : '' ?>>
				</td>
			</tr>
			<tr>
				<td align=right>
					<?php echo __('Estado') ?>
				</td>
				<td align=left>
					<?php echo Html::SelectQuery($sesion, "SELECT id_estado, glosa FROM prm_estado_factura ORDER BY id_estado ASC", "id_estado", $id_estado, 'onchange="mostrarAccionesEstado(this.form)"', 'Cualquiera', "150"); ?>
				</td>
			</tr>
			<tr>
				<td align=right>
					<?php echo __('Moneda') ?>
				</td>
				<td align=left>
					<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY glosa_moneda ASC", "id_moneda", $id_moneda, '', 'Cualquiera', "150"); ?>
				</td>
			</tr>
			<tr>
				<td align=right>
					<?php echo __('N° Factura') ?>
				</td>
				<td align=left width="18%" nowrap>
					<?php if (UtilesApp::GetConf($sesion, 'NumeroFacturaConSerie')) { ?>
						<?php echo Html::SelectQuery($sesion, $serienumero_documento->SeriesQuery(), "serie", $serie, 'onchange="NumeroDocumentoLegal()"', "Vacio", 60); ?>
						<span style="vertical-align: center;">-</span>
<?php } ?>
					<input onkeydown="if(event.keyCode==13)BuscarFacturas(this.form,'buscar');" type="text" id="numero" name="numero" size="15" value="<?php echo $numero ?>" onchange="this.value=this.value.toUpperCase();">
				</td>
				<td align=right width="18%">
<?php echo __('N° Cobro') ?>
				</td>
				<td align=left width="44%">
					<input onkeydown="if(event.keyCode==13)BuscarFacturas(this.form,'buscar');" type="text" id="id_cobro" name="id_cobro" size="15" value="<?php echo $id_cobro ?>">
				</td>
			</tr>
<?php
if (method_exists('Conf', 'dbUser') && Conf::dbUser() == "rebaza") {
	?>
				<tr>
					<td align=right>
	<?php echo __('Companía') ?>
					</td>
					<td align=left width="18%">
						<select name="id_cia" id="id_cia" value="<?php echo $id_cia ?>">
							<option value="">Todos</option>
							<option value="1" <?php echo $id_cia == 1 ? 'selected' : '' ?>>Rebaza Alcazar</option>
							<option value="2" <?php echo $id_cia == 2 ? 'selected' : '' ?>>Acerta</option>
						</select>
					</td>
				</tr>
	<?php
}
?>
			<tr>
				<td align=right>
<?php echo __('Fecha Inicio') ?>
				</td>
				<td nowrap align=left>
					<input type="text" id="fecha1" class="fechadiff"  name="fecha1" value="<?php echo $fecha1 ?>" id="fecha1" size="11" maxlength="10" />

				</td>
				<td align=right>
<?php echo __('Fecha Fin') ?>
				</td>
				<td align=left width="44%">
					<input type="text" id="fecha2" class="fechadiff" name="fecha2" value="<?php echo $fecha2 ?>" id="fecha2" size="11" maxlength="10" />

				</td>
			</tr>
			<tr id="fila_botones">
				<td colspan="4" style="text-align:center;margin:auto;">
					<a name='boton_buscar' id='boton_buscar'  class="btn botonizame" icon="find"   onclick="BuscarFacturas(jQuery('#form_facturas').get(0),'buscar')" class=btn><?php echo __('Buscar') ?></a>

					<a    class="btn botonizame" id="boton_descarga" icon="xls" name="boton_excel" onclick="BuscarFacturas(jQuery('#form_facturas').get(0), 'exportar_excel')"><?php echo __('Descargar Excel'); ?></a>
				<?php ($Slim=Slim::getInstance()) ?  $Slim->applyHook('hook_factura_fin'):false;



if (UtilesApp::GetConf($sesion, 'DescargarArchivoContabilidad')) { ?>
						<input type="button" value="<?php echo __('Descargar Archivo Contabilidad'); ?>" class="btn" name="boton_contabilidad" onclick="BuscarFacturas(this.form, 'archivo_contabilidad')" />
						<br />
						<label>desde el asiento contable
							<input type="text" size="4" name="desde_asiento_contable" value="<?php echo $desde_asiento_contable; ?>" /></label>
<?php }  ?>
				</td>
			</tr>
		</table>
	</fieldset> </td></tr></table>

</form>


<?php

if ($opc == 'buscar' || $opc == 'generar_factura') {


	$monto_saldo_total = 0;
	$glosa_monto_saldo_total = '';
	$where_moneda = ' WHERE moneda_base = 1';
	if ($id_moneda > 0) {
		$where_moneda = 'WHERE id_moneda = ' . $id_moneda;
	}
	$query_moneda = 'SELECT id_moneda, simbolo, cifras_decimales, moneda_base, tipo_cambio FROM prm_moneda	' . $where_moneda . ' ORDER BY id_moneda ';
	$resp_moneda = mysql_query($query_moneda, $sesion->dbh) or Utiles::errorSQL($query_moneda, __FILE__, __LINE__, $sesion->dbh);
	$id_moneda_base = 0;

	while (list($id_moneda_tmp, $simbolo_moneda_tmp, $cifras_decimales_tmp, $moneda_base_tmp, $tipo_cambio_tmp) = mysql_fetch_array($resp_moneda)) {
		foreach ($results as $row) {
			$monto_saldo_total += UtilesApp::CambiarMoneda($row['saldo'], $row['tipo_cambio'], $row['cifras_decimales'], $tipo_cambio_tmp, $cifras_decimales_tmp);
		}
		$glosa_monto_saldo_total = '<b>' . __('Saldo') . ' ' . $simbolo_moneda_tmp . ' ' . number_format($monto_saldo_total, $cifras_decimales_tmp, $idioma_default->fields['separador_decimales'], $idioma_default->fields['separador_miles']) . "</b>";
	}
	// calcular el saldo en moneda base
	$SimpleReport = new SimpleReport($sesion);
	$config = $SimpleReport->LoadConfiguration('FACTURAS');

	$opciones['mostrar_pagos'] = true;
	$opciones['mostrar_fecha_ultimo_pago'] = true;

	$search_query = $factura->QueryReporte($orden, $where, $numero, $fecha1, $fecha2
			, $tipo_documento_legal_buscado, $codigo_cliente,$codigo_cliente_secundario
			, $codigo_asunto,$codigo_asunto_secundario, $id_contrato, $id_cia
			, $id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social
			, $descripcion_factura, $serie, $desde_asiento_contable, $opciones);

	$x_pag = 25;

	$b = new Buscador($sesion, $search_query, "Factura", $desde, $x_pag, $orden);
	$b->titulo = "Documentos Tributarios<br />$glosa_monto_saldo_total";
	$b->AgregarFuncion(__('Destinatario Documento'), 'FormatoDestinatario', "width='30%' align=left");
	$b->AgregarEncabezado("fecha", __('Fecha Documento'), "align=center");
	$b->AgregarFuncion(__('Datos Documento'), 'FormatoDatos', "width='10%' align=left");
	if ($config->columns['encargado_comercial']->visible) {
		$b->AgregarEncabezado("encargado_comercial", __('Socio a cargo'), "align=center");
	}
	$b->AgregarFuncion(__('Nº Liquidación'), 'FormatoLiquidacion', "align=center");
	$b->AgregarFuncion(__('IVA'), "FormatoIVA", "align=right");
	$b->AgregarFuncion(__('Pagos'), "FormatoPagos", "align=right");
	$b->AgregarFuncion(__('Saldo adeudado'), "FormatoSaldo", "align=right");
	$b->AgregarEncabezado("fecha_ultimo_pago", __('Fecha Último Pago'), "align=center");
	$b->AgregarEncabezado("estado", __('Estado'), "align=center");
	$b->AgregarFuncion(__('Opciones'), 'Opciones', "style='white-space:nowrap' align=right");
	$b->color_mouse_over = "#bcff5c";
	$b->Imprimir();
}

function FormatoDestinatario($fila) {
	global $sesion;

	$html = "";
	if (!empty($fila->fields['glosa_cliente'])) {
		$html .= "<b>Cliente</b>: {$fila->fields['glosa_cliente']}<br />";
	}
	if (!empty($fila->fields['factura_rsocial'])) {
		$html .= "<b>Razón Social</b>: {$fila->fields['factura_rsocial']}<br />";
	}
	if (!empty($fila->fields['descripcion'])) {
		$html .= "<b>Descripción</b>: {$fila->fields['descripcion']}";
	}

	return $html;
}

function FormatoDatos($fila) {
	global $sesion;

	$html = "";
	if (!empty($fila->fields['tipo'])) {
		$html .= "<b>Tipo</b>: {$fila->fields['tipo']}<br />";
	}
	if (Conf::GetConf($sesion, 'NumeroFacturaConSerie') && !empty($fila->fields['serie_documento_legal'])) {
		$serie = str_pad($fila->fields['serie_documento_legal'], 3, '0', STR_PAD_LEFT);
		$html .= "<b>Serie</b>: $serie<br />";
	}
	
	$html .= "<b>Número</b>: {$fila->fields['numero']}";

	return $html;
}

function FormatoLiquidacion($fila) {
	return "<a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_Cobro',950,660,'cobros6.php?id_cobro={$fila->fields['id_cobro']}&amp;popup=1');\">{$fila->fields['id_cobro']}</a>";
}

function FormatoMoneda($sesion, $numero, $moneda) {
	return UtilesApp::PrintFormatoMoneda($sesion, $numero, $moneda);
}

function FormatoIVA($fila) {
	global $sesion;
	return FormatoMoneda($sesion, $fila->fields['iva'], $fila->fields['id_moneda']);
}

function FormatoPagos($fila) {
	global $sesion;
	return FormatoMoneda($sesion, $fila->fields['pagos'], $fila->fields['id_moneda']);
}

function FormatoSaldo($fila) {
	global $sesion;
	return FormatoMoneda($sesion, $fila->fields['saldo'], $fila->fields['id_moneda']);
}

function Opciones($fila) {
	global $sesion;

	$boton_editar = '<a class="fl ui-button editar" href="javascript:void(0);" style="margin: 3px 1px;width: 18px;height: 18px;" onclick="nuovaFinestra(\'Editar_Factura\',730,700,\'agregar_factura.php?'
		. 'id_factura=' . $fila->fields['id_factura']
		. '&codigo_cliente=' . $fila->fields['codigo_cliente']
		. '&popup=1\');" title="Editar Factura">';
	
	if (Conf::GetConf($sesion, 'ImprimirFacturaDoc')) {
		$boton_word = '<a class="fl ui-button doc" href="javascript:void(0);" style="margin: 3px 1px;width: 18px;height: 18px;" onclick="ImprimirDocumento(' . $fila->fields['id_factura'] . ');" title="Descargar Word">';
	}

	if (Conf::GetConf($sesion, 'ImprimirFacturaPdf')) {
		$boton_pdf = '<a class="fl ui-button pdf" href="javascript:void(0);" style="margin: 3px 1px;width: 18px;height: 18px;" onclick="ImprimirPDF(' . $fila->fields['id_factura'] . ');" title="Descargar PDF">';
	}
	
	$boton_log = '<a class="fl ui-icon lupa logdialog" href="javascript:void(0);" rel="factura" id="factura_' . $fila->fields['id_factura'] . '">&nbsp;</a>';
	
	return "$boton_editar $boton_word $boton_pdf $boton_log";
}

$pagina->PrintBottom();

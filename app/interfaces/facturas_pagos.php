<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/classes/Funciones.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/Factura.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/DocumentoLegalNumero.php';

$Sesion = new Sesion(array('COB'));
$pagina = new Pagina($Sesion);

$factura = new Factura($Sesion);

$series_documento = new DocumentoLegalNumero($Sesion);

if ($id_factura != "") {
	$factura->Load($id_factura);
}

if ($opc == 'generar_factura') {
	// POR HACER
	// mejorar
	if ($id_factura_grabada) {
		include dirname(__FILE__) . '/factura_doc.php';
	} else {
		echo 'Error';
	}
	exit;
}
if ($exportar_excel) {
	// Es necesaria esta bestialidad para que no se caiga cuando es llamada desde otro lado.
//	$no_activo = !$activo;
//	$multiple = true;
//	require_once Conf::ServerDir() . '/interfaces/facturas_pagos_listado_xls.php';
//	exit;
}

$idioma_default = new Objeto($Sesion, '', '', 'prm_idioma', 'codigo_idioma');
$idioma_default->Load(strtolower(UtilesApp::GetConf($Sesion, 'Idioma')));

if ($opc == 'buscar' || $opc == 'generar_factura') {
	$FacturaPago = new FacturaPago($Sesion);

	if ($exportar_excel) {
		$results = $FacturaPago->DatosReporte($orden, $where, $id_concepto, $id_banco, $id_cuenta,
			$id_estado, $pago_retencion, $fecha1, $fecha2, $serie, $numero, $codigo_cliente_secundario,
			$tipo_documento_legal_buscado, $codigo_asunto, $id_cobro, $id_estado, $id_moneda, $grupo_ventas,
			$razon_social, $descripcion_factura);
		$FacturaPago->DownloadExcel($results);
	}

	$query = $FacturaPago->QueryReporte($orden, $where, $id_concepto, $id_banco, $id_cuenta,
		$id_estado, $pago_retencion, $fecha1, $fecha2, $serie, $numero, $codigo_cliente_secundario,
		$tipo_documento_legal_buscado, $codigo_asunto, $id_cobro, $id_estado, $id_moneda, $grupo_ventas,
		$razon_social, $descripcion_factura);

	$resp = mysql_query($query . ' LIMIT 0,12', $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
	$monto_saldo_total = 0;
	$glosa_monto_saldo_total = '';
	$where_moneda = ' WHERE moneda_base = 1';
	if ($id_moneda > 0) {
		$where_moneda = 'WHERE id_moneda = ' . $id_moneda;
	}
	$query_moneda = 'SELECT id_moneda, simbolo, cifras_decimales, moneda_base, tipo_cambio FROM prm_moneda	' . $where_moneda . ' ORDER BY id_moneda ';
	$resp_moneda = mysql_query($query_moneda, $Sesion->dbh) or Utiles::errorSQL($query_moneda, __FILE__, __LINE__, $Sesion->dbh);
	$id_moneda_base = 0;
	while (list($id_moneda_tmp, $simbolo_moneda_tmp, $cifras_decimales_tmp, $moneda_base_tmp, $tipo_cambio_tmp) = mysql_fetch_array($resp_moneda)) {
		while ($row = mysql_fetch_assoc($resp)) {
			$monto_saldo_total += UtilesApp::CambiarMoneda($row['saldo'], $row['tipo_cambio'], $row['cifras_decimales'], $tipo_cambio_tmp, $cifras_decimales_tmp);
		}
		$glosa_monto_saldo_total = '<b>' . __('Saldo') . ' ' . $simbolo_moneda_tmp . ' ' . number_format($monto_saldo_total, $cifras_decimales_tmp, $idioma_default->fields['separador_decimales'], $idioma_default->fields['separador_miles']) . "</b>";
	}
	// calcular el saldo en moneda base
//echo $query;

}

$pagina->titulo = __('Revisar Pago de Documentos Tributarios');
$pagina->PrintTop();

if ($opc == 'buscar' || $opc == 'generar_factura') {
	$x_pag = 12;
	$b = new Buscador($Sesion, $query, "Objeto", $desde, $x_pag, $orden);
	$b->nombre = "busc_facturas";
	$b->titulo = "Pago de Documentos Tributarios <br />" . $glosa_monto_saldo_total;
	$b->AgregarEncabezado("fecha_factura", __('Fecha factura'), "align=right nowrap");
	$b->AgregarEncabezado("tipo", __('Tipo'), "align=center width=40px");
	$b->AgregarFuncion(__('N°'), "NumeroFactura", "align='right' width='30px'");
	$b->AgregarEncabezado("factura_razon_social", __('Cliente'), "align=left width=40px");
	$b->AgregarEncabezado("encargado_comercial", __('Abogado'), "align=left width=20px");
	$b->AgregarEncabezado("estado", __('Estado'), "align=center");
	$b->AgregarFuncion(__('Cobro'), "Cobro", "align=center");
	$b->AgregarEncabezado("concepto_pago", __('Concepto Pago'), "align=center");
	$b->AgregarEncabezado("descripcion_pago", __('Descripción Pago'), "align=center");
	$b->AgregarEncabezado("nombre_banco", __('Banco'), "align=center");
	$b->AgregarEncabezado("numero_cuenta", __('Cuenta'), "align=center");
	$b->AgregarEncabezado("fecha_pago", __('Fecha pago'), "width=60px ");
	$b->AgregarFuncion("Monto Factura", "MontoTotalFactura", "align=right nowrap");
	//$b->AgregarFuncion("Monto Pago", "MontoTotalPago", "align=right nowrap");
	$b->AgregarFuncion("Monto Aporte", "MontoAporte", "align=right nowrap");

	$b->AgregarFuncion("Saldo Factura", "SaldoFactura", "align=right nowrap");
	$b->AgregarFuncion("Saldo Pago", "SaldoPago", "align=right nowrap");
	$b->AgregarFuncion(__('Opción'), "Opciones", "align=right nowrap");
	$b->color_mouse_over = "#bcff5c";
	//$b->funcionTR = "funcionTR";
}

function MontoTotalFactura(& $fila) {
	global $Sesion;

	$idioma = new Objeto($Sesion, '', '', 'prm_idioma', 'codigo_idioma');
	$idioma->Load($fila->fields['codigo_idioma']);

	return $fila->fields['simbolo_factura'] . ' ' . number_format($fila->fields['monto_factura'], $fila->fields['cifras_decimales_factura'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
}

function MontoTotalPago(& $fila) {
	global $Sesion;

	$idioma = new Objeto($Sesion, '', '', 'prm_idioma', 'codigo_idioma');
	$idioma->Load($fila->fields['codigo_idioma']);

	return $fila->fields['simbolo_pago'] . ' ' . number_format($fila->fields['monto_pago'], $fila->fields['cifras_decimales_pago'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
}

function MontoAporte(& $fila) {
	global $Sesion;

	$idioma = new Objeto($Sesion, '', '', 'prm_idioma', 'codigo_idioma');
	$idioma->Load($fila->fields['codigo_idioma']);

	return $fila->fields['simbolo_factura'] . ' ' . number_format($fila->fields['monto_aporte'], $fila->fields['cifras_decimales_factura'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
}

function NumeroFactura(& $fila) {
	global $Sesion;

	$factura_ = new Factura($Sesion);
	return $factura_->ObtenerNumero(null, $fila->fields['serie_documento_legal'], $fila->fields['numero']);
}

function SubTotal(& $fila) {
	global $idioma;
	return $fila->fields['honorarios'] > 0 ? $fila->fields['simbolo'] . ' ' . number_format($fila->fields['honorarios'], $fila->fields['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']) : '';
}

function SaldoFactura(& $fila) {
	global $Sesion;

	$idioma = new Objeto($Sesion, '', '', 'prm_idioma', 'codigo_idioma');
	$idioma->Load($fila->fields['codigo_idioma']);

	return $fila->fields['simbolo_factura'] . ' ' . number_format($fila->fields['saldo_factura'], $fila->fields['cifras_decimales_factura'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
}

function SaldoPago(& $fila) {
	global $Sesion;

	$idioma = new Objeto($Sesion, '', '', 'prm_idioma', 'codigo_idioma');
	$idioma->Load($fila->fields['codigo_idioma']);

	return $fila->fields['simbolo_pago'] . ' ' . number_format($fila->fields['saldo_pago'], $fila->fields['cifras_decimales_pago'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);
}

function Opciones(& $fila) {
	global $Sesion;
	$id_factura_pago = $fila->fields['id_factura_pago'];
	$codigo_cliente = $fila->fields['cliente_pago'];
	$html_opcion .= "<a href='javascript:void(0)' onclick=\"nuovaFinestra('Editar_Factura_Pago',730,580,'agregar_pago_factura.php?id_factura_pago=$id_factura_pago&codigo_cliente=$codigo_cliente&id_cobro={$fila->fields['id_cobro']}&popup=1');\" ><img src='" . Conf::ImgDir() . "/editar_on.gif' border=0 title=Editar></a>&nbsp;";
	$html_opcion .= "<a href='javascript:void(0)' onclick=\"ImprimirDocumentoPago(" . $id_factura_pago . ");\" ><img src='" . Conf::ImgDir() . "/pdf.gif' border=0 title=Imprimir></a>";

 		 $html_opcion .=UtilesApp::LogDialog($Sesion, 'factura_pago',$id_factura_pago);


	return $html_opcion;
}

function Cobro(& $fila) {
	return "<a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_" . __("Cobro") . "',1024,660,'cobros6.php?id_cobro=" . $fila->fields['id_cobro'] . "&popup=1');\">" . $fila->fields['id_cobro'] . "</a>";
}

function GlosaCliente(& $fila) {
	//por defecto se muestra la glosa del cliente
	// si el rut de la factura no es el mismo que el rut del cobro (que viene del contrato), se agrega entre parentesis la razon social de la factura
	$glosa_cliente = $fila->fields['glosa_cliente'];
	if ($fila->fields['mostrar_diferencia_razon_social'] != 'no') {
		$glosa_cliente .= "<br />(" . $fila->fields['mostrar_diferencia_razon_social'] . ")";
	}
	return $glosa_cliente;
}

function funcionTR(& $fila) {
	global $Sesion;
	static $i = 0;

	$idioma = new Objeto($Sesion, '', '', 'prm_idioma', 'codigo_idioma');
	if ($fila->fields['codigo_idioma']) {
		$idioma->Load($fila->fields['codigo_idioma']);
	} else {
		$idioma->Load(strtolower(UtilesApp::GetConf($Sesion, 'Idioma')));
	}

	if ($i % 2 == 0)
		$color = "#dddddd";
	else
		$color = "#ffffff";
	$formato_fechas = UtilesApp::ObtenerFormatoFecha($Sesion);

	$html .= "<tr id=\"t" . $fila->fields['id_factura'] . "\" bgcolor=$color style=\"border-right: 1px solid #409C0B; border-left: 1px solid #409C0B; border-bottom: 1px solid #409C0B;\">";
	$html .= "<td align=left>" . Utiles::sql2fecha($fila->fields['fecha'], $formato_fechas, '-') . "</td>";
	$html .= "<td align=left>" . $fila->fields['tipo'] . "</td>";
	$html .= "<td align=right>#" . NumeroFactura(& $fila, $Sesion) . "&nbsp;</td>";
	$html .= "<td align=left>" . GlosaCliente(& $fila) . "</td>";
	$html .= "<td align=right>" . Glosa_asuntos(& $fila, $Sesion) . "</td>";
	$html .= "<td align=left>" . $fila->fields['encargado_comercial'] . "</td>";
	//$html .= "<td align=left>".$fila->fields['descripcion']."</td>";
	if (method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'NuevoModuloFactura'))
		$html .= "<td align=center>" . $fila->fields['estado'] . "</td>";
	else
		$html .= "<td align=center>" . $fila->fields['anulado'] . "</td>";
	$html .= "<td align=center><a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_" . __("Cobro") . "',1024,660,'cobros6.php?id_cobro=" . $fila->fields['id_cobro'] . "&popup=1');\">" . $fila->fields['id_cobro'] . "</a></td>";

	$html .= "<td align=right >" . RetencionImpuestoPago(& $fila, $Sesion) . "</td>";
	$html .= "<td align=right >" . ConceptoPago(& $fila, $Sesion) . "</td>";
	$html .= "<td align=right >" . DescripcionPago(& $fila, $Sesion) . "</td>";
	$html .= "<td align=right >" . BancoPago(& $fila, $Sesion) . "</td>";
	$html .= "<td align=right nowrap>" . CuentaPago(& $fila, $Sesion) . "</td>";
	$html .= "<td align=right nowrap>" . Utiles::sql2fecha($fila->fields['fecha_pago'], $formato_fechas, '-') . "</td>";
	//$html .= "<td align=right nowrap>".SubTotal(& $fila)."</td>";
	//$html .= "<td align=right nowrap>".Iva(& $fila)."</td>";
	$html .= "<td align=right nowrap>" . MontoTotal(& $fila) . "</td>";
	//$html .= "<td align=right nowrap>".Pago(& $fila, $Sesion)."</td>";
	$html .= "<td align=right nowrap>" . MontoPago(& $fila) . "</td>";
	$html .= "<td align=right nowrap>" . SaldoPago(& $fila) . "</td>";
	$html .= "<td align=right nowrap>" . Saldo(& $fila) . "</td>";
	$html .= "<td align=center nowrap>" . Opciones(& $fila, $Sesion) . "</td>";
	$html .= "</tr>";

	$i++;
	return $html;
}
?>
<script type="text/javascript">
	function ImprimirDocumentoPago( id_factura_pago )
	{
		var vurl = "agregar_pago_factura.php?id_factura_pago="+id_factura_pago+"&popup=1&opcion=imprimir_voucher";
		self.location.href=vurl;
	}

	function Refrescar()
	{
		document.form_buscador.submit();
	}

	function BuscarFacturasPago( form, from )
	{
		if(!form)
			var form = $('form_facturas');
		if(from == 'buscar') {
			form.action = 'facturas_pagos.php?buscar=1';
		}
		else if(from == 'exportar_excel'){
			form.action = 'facturas_pagos.php?opc=buscar&exportar_excel=1';
		}
		else
			return false;
		form.submit();
		return true;
	}

	function AgregarNuevo()
	{
		var urlo = "agregar_factura.php?popup=1";
		nuovaFinestra('Agregar_Factura',730,470,urlo,'top=100, left=125');
	}

	function CargarCuenta( origen, destino, multiple )
	{
		var http = getXMLHTTP();
		if( multiple ){
			objOrigen = $(origen);
			seleccionados = "";
			for( i = 0; i < objOrigen.options.length; i++ ){
				if( objOrigen.options[i].selected ){
					if( seleccionados.length > 0 ){
						seleccionados += "::";
					}
					seleccionados += objOrigen.options[i].value;
				}
			}
			var url = 'ajax.php?accion=cargar_multiples_cuentas&id=' + seleccionados;
		} else {
			var url = 'ajax.php?accion=cargar_cuentas&id=' + $(origen).value;
		}

		loading("Actualizando campo");
		http.open('get', url);
		http.onreadystatechange = function()
		{
			if(http.readyState == 4)
			{
				var response = http.responseText;

				if( response == "~noexiste" ){
					$(destino).options.length = 0;
					alert( "Ústed no tiene cuentas en este banco." );
				} else {
					$(destino).options.length = 0;
					cuentas = response.split('//');

					for(var i=0;i<cuentas.length;i++)
					{
						valores = cuentas[i].split('|');

						var option = new Option();
						if( valores[0] == "Vacio" ) {
							option.value = '';
						} else {
							option.value = valores[0];
						}
						option.text = valores[1];

						try
						{
							$(destino).add(option);
						}
						catch(err)
						{
							$(destino).add(option,null);
						}
					}
				}
				offLoading();
			}
		};
		http.send(null);
	}

	function SetBanco( origen, destino )
	{
		var http = getXMLHTTP();
		var url = 'ajax.php?accion=buscar_banco&id=' + $(origen).value;

		loading("Actualizando campo");
		http.open('get', url);
		http.onreadystatechange = function()
		{
			if(http.readyState == 4)
			{
				var response = http.responseText;
				$(destino).value = response;
				offLoading();
			}
		};
		http.send(null);
	}
</script>


<form method='post' name="form_facturas" id="form_facturas">
	<input type='hidden' name='opc' id='opc' value='buscar'>
	<!-- Calendario DIV -->
	<div id="calendar-container" style="width:221px; position:absolute; display:none;">
		<div class="floating" id="calendar"></div>
	</div>
	<!-- Fin calendario DIV -->
<?php
if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'UsaDisenoNuevo') ) || ( method_exists('Conf', 'UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ))) {
	echo "<table width=\"90%\"><tr><td>";
	$class_diseno = 'class="tb_base" style="width: 100%; border: 1px solid #BDBDBD;"';
}
else
	$class_diseno = '';
?>
	<fieldset class="tb_base" style="width: 100%; border: 1px solid #BDBDBD;">
		<legend><?php echo __('Filtros') ?></legend>
		<table style="border: 0px solid black" width='720px'>
			<tr>
				<td align=right width="20%">
<?php echo __('Cliente') ?>
				</td>
				<td colspan="3" align=left nowrap>
<?php UtilesApp::CampoCliente($Sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
				</td>
			</tr>
			<tr>
				<td align='right' width="20%">
					<?php echo __('Asunto') ?>
				</td>
				<td colspan="3" align=left nowrap>
<?php UtilesApp::CampoAsunto($Sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario); ?>
				</td>
			</tr>
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
<?php echo __('Descripción Recaudación') ?>
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
<?php echo Html::SelectQuery($Sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal", 'tipo_documento_legal_buscado', $tipo_documento_legal_buscado, '', 'Cualquiera', 150); ?>
				</td>
				<td align=right width="25%">
<?php echo __('Grupo Ventas') ?>
				</td>
				<td align=left >
					<input type=checkbox name=grupo_ventas id=grupo_ventas value=1 <?php echo $grupo_ventas ? 'checked' : '' ?>>
				</td>
			</tr>
			<tr>
				<td align=right>
<?php echo __('Estado') ?>
				</td>
				<td align=left>
<?php
if (UtilesApp::GetConf($Sesion, 'SelectMultipleFacturasPago')) {
	echo Html::SelectQuery($Sesion, "SELECT id_estado, glosa FROM prm_estado_factura ORDER BY id_estado ASC", "id_estado[]", $id_estado, ' multiple size="5" onchange="mostrarAccionesEstado(this.form)"', 'Cualquiera', "190");
} else {
	echo Html::SelectQuery($Sesion, "SELECT id_estado, glosa FROM prm_estado_factura ORDER BY id_estado ASC", "id_estado", $id_estado, ' onchange="mostrarAccionesEstado(this.form)"', 'Cualquiera', "150");
}
?>
				</td>
			</tr>
			<tr>
				<td align=right>
					<?php echo __('Moneda') ?>
				</td>
				<td align=left>
<?php echo Html::SelectQuery($Sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY glosa_moneda ASC", "id_moneda", $id_moneda, '', 'Cualquiera', "150"); ?>
				</td>
			</tr>
			<tr>
				<td align=right>
					<?php echo __('N° Factura') ?>
				</td>
				<td align=left width="18%">
<?php if (UtilesApp::GetConf($Sesion, 'NumeroFacturaConSerie')) { ?>
						<?php echo Html::SelectQuery($Sesion, $series_documento->SeriesQuery(), "serie", $serie, '', "Vacio", 60); ?>
						<span style="vertical-align: center;">-</span>
<?php } ?>
					<input onkeydown="if(event.keyCode==13)BuscarFacturas(this.form,'buscar');" type="text" id="numero" name="numero" size="15" value="<?php echo $numero ?>" onchange="this.value=this.value.toUpperCase();">
				</td>
				<td alignelement=right width="18%">
					<?php echo __('N° Cobro') ?>
				</td>
				<td align=left width="44%">
					<input onkeydown="if(event.keyCode==13)BuscarFacturas(this.form,'buscar');" type="text" id="id_cobro" name="id_cobro" size="15" value="<?php echo $id_cobro ?>">
				</td>
			</tr>
			<tr>
				<td align=right>
<?php echo __('Concepto') ?>
				</td>
				<td align=left>
<?php
if (UtilesApp::GetConf($Sesion, 'SelectMultipleFacturasPago')) {
	echo Html::SelectQuery($Sesion, "SELECT id_concepto,glosa FROM prm_factura_pago_concepto ORDER BY orden", "id_concepto[]", $id_concepto, ' multiple size="5" ', 'Cualquiera', "190");
} else {
	echo Html::SelectQuery($Sesion, "SELECT id_concepto,glosa FROM prm_factura_pago_concepto ORDER BY orden", "id_concepto", $id_concepto, '', 'Cualquiera', "150");
}
?>
				</td>
					<?php if (method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'PagoRetencionImpuesto')) { ?>
					<td align=right>
						<?php echo __('Pago retención impuestos') ?>
					</td>
					<td align=left>
					<?php
					$pago_retencion_check = '';
					if ($pago_retencion)
						$pago_retencion_check = "checked='checked'";
					?>
						<input type="checkbox" name="pago_retencion" id="pago_retencion" value=1 <?php echo $pago_retencion_check ?> />
					</td>
					<?php } ?>
			</tr>
			<tr>
				<td align=right>
				<?php echo __('Banco') ?>
				</td>
				<td align=left>
<?php
if (UtilesApp::GetConf($Sesion, 'SelectMultipleFacturasPago')) {
	echo Html::SelectQuery($Sesion, "SELECT id_banco, nombre FROM prm_banco ORDER BY orden", "id_banco[]", $id_banco, ' multiple size="5" onchange="CargarCuenta(\'id_banco[]\',\'id_cuenta[]\', true);"', 'Cualquiera', "190");
} else {
	echo Html::SelectQuery($Sesion, "SELECT id_banco, nombre FROM prm_banco ORDER BY orden", "id_banco", $id_banco, ' onchange="CargarCuenta(\'id_banco\',\'id_cuenta\', false);"', 'Cualquiera', "190");
}
?>
				</td>
				<td align=right>
					<?php echo __('N° Cuenta') ?>
				</td>
				<td align=left>
<?php
if (!empty($id_banco)) {
	$where_banco = " WHERE cuenta_banco.id_banco = '$id_banco' ";
} else {
	$where_banco = " WHERE 1=2 ";
}
if (UtilesApp::GetConf($Sesion, 'SelectMultipleFacturasPago')) {
	echo Html::SelectQuery($Sesion, "SELECT cuenta_banco.id_cuenta
				, CONCAT( cuenta_banco.numero,
					 IF( prm_moneda.glosa_moneda IS NOT NULL , CONCAT(' (',prm_moneda.glosa_moneda,')'),  '' ) ) AS NUMERO
				FROM cuenta_banco
									LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cuenta_banco.id_moneda $where_banco ", "id_cuenta[]", $id_cuenta, ' multiple size="5" ', "Cualquiera", "150");
} else {
	echo Html::SelectQuery($Sesion, "SELECT cuenta_banco.id_cuenta
									, CONCAT( cuenta_banco.numero,
										 IF( prm_moneda.glosa_moneda IS NOT NULL , CONCAT(' (',prm_moneda.glosa_moneda,')'),  '' ) ) AS NUMERO
									FROM cuenta_banco
									LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cuenta_banco.id_moneda $where_banco ", "id_cuenta", $id_cuenta, 'onchange="SetBanco(\'id_cuenta\',\'id_banco\');"', "Cualquiera", "150");
}
?>
				</td>
			</tr>
			<tr>
				<td align=right>
					<?php echo __('Fecha inicio pago') ?>
				</td>
				<td nowrap align=left>
					<input type="text" id="fecha1" class="fechadiff" name="fecha1" value="<?php echo $fecha1 ?>" id="fecha1" size="11" maxlength="10" />

				</td>
				<td align=right>
<?php echo __('Fecha fin pago') ?>
				</td>
				<td nowrap align=left>
					<input type="text" id="fecha2" class="fechadiff"  name="fecha2" value="<?php echo $fecha2 ?>" id="fecha2" size="11" maxlength="10" />

				</td>
			</tr>
			<tr>
				<td colspan=3 align=right>
					<a name='boton_buscar' id='boton_buscar' icon='find' class="btn botonizame"   onclick="BuscarFacturasPago(jQuery('#form_facturas').get(0),'buscar')"  ><?php echo __('Buscar') ?></a>
				</td>
				<td align="right">
					<input type="button" value="<?php echo __('Descargar Excel'); ?>" class="btn botonizame" id="boton_descarga" name="boton_excel" onclick="BuscarFacturasPago(jQuery('#form_facturas').get(0), 'exportar_excel')">
				<?php ($Slim=Slim::getInstance('default',true)) ?  $Slim->applyHook('hook_factura_pago_fin'):false; ?>

				</td>
			</tr>
		</table>
	</fieldset>
<?php if (UtilesApp::GetConf($Sesion, 'UsaDisenoNuevo')) echo "</td></tr></table>"; ?>
</form>

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


</script>
<?php
if ($opc == 'buscar') {
	$b->Imprimir();
}


$pagina->PrintBottom();


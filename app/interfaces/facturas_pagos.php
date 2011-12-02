<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/classes/InputId.php';
require_once Conf::ServerDir() . '/classes/Funciones.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/Factura.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/Autocompletador.php';
require_once Conf::ServerDir() . '/classes/DocumentoLegalNumero.php';

$Sesion = new Sesion(array('COB'));
$pagina = new Pagina($Sesion);

$factura = new Factura($Sesion);

$series_documento = new DocumentoLegalNumero($sesion);

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
	$no_activo = !$activo;
	$multiple = true;
	require_once Conf::ServerDir() . '/interfaces/facturas_pagos_listado_xls.php';
	exit;
}

$idioma_default = new Objeto($Sesion, '', '', 'prm_idioma', 'codigo_idioma');
$idioma_default->Load(strtolower(UtilesApp::GetConf($Sesion, 'Idioma')));

$pagina->titulo = __('Revisar Pago de Documentos Tributarios');
$pagina->PrintTop();



if ($opc == 'buscar' || $opc == 'generar_factura') {

	if ($orden == '')
		$orden = 'fp.fecha DESC';

	if ($where == '') {
		$join = '';
		$where = 1;

		/*
		 * INICIO - obtener listado facturas con pago parcial o total
		 */
		$lista_facturas_con_pagos = '';
		$where = 1;
		
		if ( UtilesApp::GetConf($Sesion, 'SelectMultipleFacturasPago') ) {
			if ( isset($_REQUEST['id_concepto']) ) {
				$condiciones = "";
				foreach( $_REQUEST['id_concepto'] as $key => $value )
				{
					if( strlen( $condiciones ) > 0 ){
						$condiciones .= " OR ";
					}
					$condiciones .= " fp.id_concepto = '$value' ";
				}
				$where .= " AND ( $condiciones ) ";
			}
			if ( isset($_REQUEST['id_banco']) ) {
				$condiciones = "";
				foreach( $_REQUEST['id_banco'] as $key => $value )
				{
					if( strlen( $condiciones ) > 0 ){
						$condiciones .= " OR ";
					}
					$condiciones .= " fp.id_banco = '$value' ";
				}
				$where .= " AND ( $condiciones ) ";
			}
			if ( isset($_REQUEST['id_cuenta']) ) {
				$condiciones = "";
				foreach( $_REQUEST['id_cuenta'] as $key => $value )
				{
					if( strlen( $condiciones ) > 0 ){
						$condiciones .= " OR ";
					}
					$condiciones .= " fp.id_cuenta = '$value' ";
				}
				$where .= " AND ( $condiciones ) ";
			}
			if ( isset($_REQUEST['id_estado']) ) {
				$condiciones = "";
				foreach( $_REQUEST['id_estado'] as $key => $value )
				{
					if( strlen( $condiciones ) > 0 ){
						$condiciones .= " OR ";
					}
					$condiciones .= " factura.id_estado = '$value' ";
				}
				$where .= " AND ( $condiciones ) ";
			}
		} else {
			if ($id_concepto) {
				$where .= " AND fp.id_concepto = '$id_concepto' ";
			}
			if ($id_banco) {
				$where .= " AND fp.id_banco = '$id_banco' ";
			}
			if ($id_cuenta) {
				$where .= " AND fp.id_cuenta = '$id_cuenta' ";
			}			
			if ($id_estado) {
				$where .= " AND factura.id_estado = '$id_estado' ";
			}
		}
			
		if ($pago_retencion) {
			$where .= " AND fp.pago_retencion = '$pago_retencion' ";
		}
		if ($fecha1 && $fecha2) {
			$where .= " AND fp.fecha BETWEEN '" . Utiles::fecha2sql($fecha1) . " 00:00:00' AND '" . Utiles::fecha2sql($fecha2) . ' 23:59:59' . "' ";
		}
		else if ($fecha1) {
			$where .= " AND fp.fecha >= '" . Utiles::fecha2sql($fecha1) . ' 00:00:00' . "' ";
		}
		else if ($fecha2) {
			$where .= " AND fp.fecha <= '" . Utiles::fecha2sql($fecha2) . ' 23:59:59' . "' ";
		}

		if( !empty($serie) && $serie != -1 ){
			$where .= " AND '$serie' LIKE CONCAT('%',factura.serie_documento_legal) ";
		}
		if($numero != '')
				$where .= " AND factura.numero*1 = $numero*1 ";

		if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ) ) && $codigo_cliente_secundario) {
			$cliente = new Cliente($Sesion);
			$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
			$codigo_cliente = $cliente->fields['codigo_cliente'];
		}
		if ($tipo_documento_legal_buscado)
			$where .= " AND factura.id_documento_legal = '$tipo_documento_legal_buscado' ";

		if ($codigo_cliente) {
			$where .= " AND fp.codigo_cliente='$codigo_cliente' ";
		}
		if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ) ) && $codigo_cliente_secundario) {
			$asunto = new Asunto($Sesion);
			$asunto->LoadByCodigoSecundario($codigo_cliente_secundario);
			$id_contrato = $asunto->fields['id_contrato'];
		}
		if ($codigo_asunto) {
			$asunto = new Asunto($Sesion);
			$asunto->LoadByCodigo($codigo_asunto);
			$id_contrato = $asunto->fields['id_contrato'];
		}
		if ($id_contrato) {
			$where .= " AND cobro.id_contrato='$id_contrato' ";
		}
		if ($id_cobro) {
			$where .= " AND factura.id_cobro='$id_cobro' ";
		}
		if ($id_estado) {
			$where .= " AND factura.id_estado = '$id_estado' ";
		}
		if ($id_moneda) {
			$where .= " AND fp.id_moneda = '$id_moneda' ";
		}
		if ($grupo_ventas) {
			$where .= " AND prm_documento_legal.grupo = 'VENTAS' ";
		}
		if ($razon_social) {
			$where .= " AND factura.cliente LIKE '%$razon_social%'";
		}
		if ($descripcion_factura) {
			$where .= " AND (fp.descripcion LIKE '%$descripcion_factura%'";
			$where .= " OR factura.descripcion_subtotal_gastos LIKE '%$descripcion_factura%' ";
			$where .= " OR factura.descripcion_subtotal_gastos_sin_impuesto LIKE '%$descripcion_factura%')";
		}
	} else {
		$where = base64_decode($where);
	}

	$query = "SELECT SQL_CALC_FOUND_ROWS
				  factura.id_factura
				, factura.fecha as fecha_factura
				, factura.id_moneda
				, factura.honorarios
				, factura.iva
				, factura.total
				, factura.id_factura
				, factura.id_cobro
				, factura.numero
				, factura.serie_documento_legal
				, factura.cliente as factura_razon_social
				, fp.fecha as fecha_pago
				, fp.descripcion as descripcion_pago
				, fp.id_moneda AS id_moneda_factura_pago
				, fp.codigo_cliente as cliente_pago
				, fp.id_factura_pago
				, prm_documento_legal.codigo as tipo
				, factura.serie_documento_legal
				, cliente.glosa_cliente
                                , prm_banco.nombre as nombre_banco 
                                , cuenta_banco.numero as numero_cuenta 
				, co.glosa as concepto_pago
				, usuario.username AS encargado_comercial
				, prm_estado_factura.glosa as estado
				, cobro.codigo_idioma as codigo_idioma
				, moneda_factura.simbolo as simbolo_factura
				, moneda_factura.cifras_decimales as cifras_decimales_factura
				, moneda_factura.tipo_cambio as tipo_cambio_factura
				, moneda_pago.simbolo as simbolo_pago
				, moneda_pago.cifras_decimales as cifras_decimales_pago
				, moneda_pago.tipo_cambio as tipo_cambio_pago
				, -1 * ccfm2.saldo as saldo_factura
				, ccfm.saldo as saldo_pago
				, ccfm.monto_bruto as monto_pago
				, -1 * ccfm2.monto_bruto as monto_factura
				, ccfmn.monto AS monto_aporte
				, contrato.codigo_idioma
				, if(factura.RUT_cliente != contrato.rut,factura.cliente,'no' ) as mostrar_diferencia_razon_social
			FROM factura_pago AS fp
			JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
			JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
			LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
			LEFT JOIN factura ON ccfm2.id_factura = factura.id_factura
                        LEFT JOIN prm_banco ON fp.id_banco = prm_banco.id_banco 
                        LEFT JOIN cuenta_banco ON fp.id_cuenta = cuenta_banco.id_cuenta 
			LEFT JOIN cobro ON cobro.id_cobro=factura.id_cobro
			LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente
			LEFT JOIN contrato ON contrato.id_contrato=cobro.id_contrato
			LEFT JOIN usuario ON usuario.id_usuario=contrato.id_usuario_responsable
			LEFT JOIN prm_documento_legal ON (factura.id_documento_legal = prm_documento_legal.id_documento_legal)
			LEFT JOIN prm_moneda as moneda_factura ON moneda_factura.id_moneda=factura.id_moneda
			LEFT JOIN prm_moneda as moneda_pago ON moneda_pago.id_moneda = fp.id_moneda
			LEFT JOIN prm_factura_pago_concepto AS co ON fp.id_concepto = co.id_concepto
			LEFT JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
			WHERE $where";
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
	$b->AgregarFuncion("Monto Pago", "MontoTotalPago", "align=right nowrap");
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
	global $where;
	global $Sesion;

	$id_factura_pago = $fila->fields['id_factura_pago'];
	$codigo_cliente = $fila->fields['cliente_pago'];
	$html_opcion .= "<a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_Factura_Pago',730,580,'agregar_pago_factura.php?id_factura_pago=$id_factura_pago&codigo_cliente=$codigo_cliente&popup=1');\" ><img src='" . Conf::ImgDir() . "/editar_on.gif' border=0 title=Editar></a>&nbsp;";
	$html_opcion .= "<a href='javascript:void(0)' onclick=\"ImprimirDocumentoPago(" . $id_factura_pago . ");\" ><img src='" . Conf::ImgDir() . "/pdf.gif' border=0 title=Imprimir></a>";
	return $html_opcion;
}

function Cobro(& $fila) {
	global $Sesion;

	return "<a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_" . __("Cobro") . "',950,660,'cobros6.php?id_cobro=" . $fila->fields['id_cobro'] . "&popup=1');\">" . $fila->fields['id_cobro'] . "</a>";
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
	global $id_cobro;
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
	$glosa_tramite = $tramite->fields['glosa_tramite'];
	$html .= "<td align=left>" . Utiles::sql2fecha($fila->fields['fecha'], $formato_fechas, '-') . "</td>";
	$html .= "<td align=left>" . $fila->fields['tipo'] . "</td>";
	$factura_ = new Factura($Sesion);
	$html .= "<td align=right>#" . NumeroFactura(& $fila, $Sesion) . "&nbsp;</td>";
	$html .= "<td align=left>" . GlosaCliente(& $fila) . "</td>";
	$html .= "<td align=right>" . Glosa_asuntos(& $fila, $Sesion) . "</td>";
	$html .= "<td align=left>" . $fila->fields['encargado_comercial'] . "</td>";
	//$html .= "<td align=left>".$fila->fields['descripcion']."</td>";
	if (method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'NuevoModuloFactura'))
		$html .= "<td align=center>" . $fila->fields['estado'] . "</td>";
	else
		$html .= "<td align=center>" . $fila->fields['anulado'] . "</td>";
	$html .= "<td align=center><a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_" . __("Cobro") . "',750,660,'cobros6.php?id_cobro=" . $fila->fields['id_cobro'] . "&popup=1');\">" . $fila->fields['id_cobro'] . "</a></td>";

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
		BuscarFacturasPago('','buscar');
	}

	function BuscarFacturasPago( form, from )
	{
		if(!form)
			var form = $('form_facturas');
		if(from == 'buscar') {
			form.action = 'facturas_pagos.php?buscar=1';
		}
		else if(from == 'exportar_excel'){
			form.action = 'facturas_pagos.php?exportar_excel=1';
		}
		else
			return false;
		form.submit();
		return true;
	}

	function AgregarNuevo()
	{
		var urlo = "agregar_factura.php?popup=1";
		nuevaVentana('Agregar_Factura',730,470,urlo,'top=100, left=125');
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
						option.value = valores[0];
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

<?php echo Autocompletador::CSS(); ?>
<form method='post' name="form_facturas" id="form_facturas">
	<input type='hidden' name='opc' id='opc' value='buscar'>
	<!-- Calendario DIV -->
	<div id="calendar-container" style="width:221px; position:absolute; display:none;">
		<div class="floating" id="calendar"></div>
	</div>
	<!-- Fin calendario DIV -->
	<?
	if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'UsaDisenoNuevo') ) || ( method_exists('Conf', 'UsaDisenoNuevo') && Conf::UsaDisenoNuevo() ))) {
		echo "<table width=\"90%\"><tr><td>";
		$class_diseno = 'class="tb_base" style="width: 100%; border: 1px solid #BDBDBD;"';
	}
	else
		$class_diseno = '';
	?>
	<fieldset class="tb_base" style="width: 100%; border: 1px solid #BDBDBD;">
		<legend><?php echo  __('Filtros') ?></legend>
		<table style="border: 0px solid black" width='720px'>
			<tr>
				<td align=right width="20%">
<?php echo  __('Cliente') ?>
				</td>
				<td colspan="3" align=left nowrap>
					<?
					if (( method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'TipoSelectCliente') == 'autocompletador' ) || ( method_exists('Conf', 'TipoSelectCliente') && Conf::TipoSelectCliente() )) {
						if (( method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ))
							echo Autocompletador::ImprimirSelector($Sesion, '', $codigo_cliente_secundario);
						else
							echo Autocompletador::ImprimirSelector($Sesion, $codigo_cliente);
					}
					else {
						if (( method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() )) {
							echo InputId::Imprimir($Sesion, "cliente", "codigo_cliente_secundario", "glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario, "", "CargarSelect('codigo_cliente_secundario','codigo_asunto_secundario','cargar_asuntos',1);", 320, $codigo_asunto_secundario);
						}
						else {
							echo InputId::Imprimir($Sesion, "cliente", "codigo_cliente", "glosa_cliente", "codigo_cliente", $codigo_cliente, "", "CargarSelect('codigo_cliente','codigo_asunto','cargar_asuntos',1);", 320, $codigo_asunto);
						}
					}
					?>
				</td>
			</tr>
			<tr>
				<td align='right' width="20%">
	<?php echo  __('Asunto') ?>
				</td>
				<td colspan="3" align=left nowrap>
					<?
					if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ))) {
						echo InputId::Imprimir($Sesion, "asunto", "codigo_asunto_secundario", "glosa_asunto", "codigo_asunto_secundario", $codigo_asunto_secundario, "", "CargarSelectCliente(this.value);", 320, $codigo_cliente_secundario);
					}
					else {
						echo InputId::Imprimir($Sesion, "asunto", "codigo_asunto", "glosa_asunto", "codigo_asunto", $codigo_asunto, "", "CargarSelectCliente(this.value);", 320, $codigo_cliente);
					}
					?>
				</td>
			</tr>
			<tr>
				<td align=right>
					<?php echo  __('Razón Social') ?>
				</td>
				<td align=left colspan="3" >
					<input type="text" name="razon_social" id="razon_social" value="<?php echo  $razon_social; ?>" size="72">
				</td>
			</tr>
			<tr>
				<td align=right>
					<?php echo  __('Descripción Recaudación') ?>
				</td>
				<td align=left colspan="3" >
					<input type="text" name="descripcion_factura" id="descripcion_factura" value="<?php echo  $descripcion_factura; ?>" size="72">
				</td>
			</tr>
			<tr>
				<td align=right>
					<?php echo  __('Tipo de Documento') ?>
				</td>
				<td align=left >
<?php echo  Html::SelectQuery($Sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal", 'tipo_documento_legal_buscado', $tipo_documento_legal_buscado, '', 'Cualquiera', 150); ?>
				</td>
				<td align=right width="25%">
<?php echo  __('Grupo Ventas') ?>
				</td>
				<td align=left >
					<input type=checkbox name=grupo_ventas id=grupo_ventas value=1 <?php echo  $grupo_ventas ? 'checked' : '' ?>>
				</td>
			</tr>
			<tr>
				<td align=right>
					<?php echo  __('Estado') ?>
				</td>
				<td align=left>
					<?php 
						if ( UtilesApp::GetConf($Sesion, 'SelectMultipleFacturasPago') ) {
							echo Html::SelectQuery($Sesion, "SELECT id_estado, glosa FROM prm_estado_factura ORDER BY id_estado ASC", "id_estado[]", $id_estado, ' multiple size="4" onchange="mostrarAccionesEstado(this.form)"', 'Cualquiera', "190"); 
						} else {
							echo Html::SelectQuery($Sesion, "SELECT id_estado, glosa FROM prm_estado_factura ORDER BY id_estado ASC", "id_estado", $id_estado, ' onchange="mostrarAccionesEstado(this.form)"', 'Cualquiera', "150"); 
						}
					?>
				</td>
			</tr>
			<tr>
				<td align=right>
<?php echo  __('Moneda') ?>
				</td>
				<td align=left>
<?php echo  Html::SelectQuery($Sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY glosa_moneda ASC", "id_moneda", $id_moneda, '', 'Cualquiera', "150"); ?>
				</td>
			</tr>
			<tr>
				<td align=right>
<?php echo  __('N° Factura') ?>
				</td>
				<td align=left width="18%">
					<?php if (UtilesApp::GetConf($Sesion, 'NumeroFacturaConSerie')) { ?>
					<?php echo Html::SelectQuery($Sesion, $series_documento->SeriesQuery(), "serie", $serie, '', "Vacio", 60); ?>
					<span style="vertical-align: center;">-</span>
					<?php } ?>
					<input onkeydown="if(event.keyCode==13)BuscarFacturas(this.form,'buscar');" type="text" id="numero" name="numero" size="15" value="<?= $numero ?>" onchange="this.value=this.value.toUpperCase();">
				</td>
				<td alignelement=right width="18%">
<?php echo  __('N° Cobro') ?>
				</td>
				<td align=left width="44%">
					<input onkeydown="if(event.keyCode==13)BuscarFacturas(this.form,'buscar');" type="text" id="id_cobro" name="id_cobro" size="15" value="<?php echo  $id_cobro ?>">
				</td>
			</tr>
			<tr>
				<td align=right>
<?php echo  __('Concepto') ?>
				</td>
				<td align=left>
					<?php 
						if ( UtilesApp::GetConf($Sesion, 'SelectMultipleFacturasPago') ) {
							echo Html::SelectQuery($Sesion, "SELECT id_concepto,glosa FROM prm_factura_pago_concepto ORDER BY orden", "id_concepto[]", $id_concepto, ' multiple size="4" ', 'Cualquiera', "190"); 
						} else {
							echo Html::SelectQuery($Sesion, "SELECT id_concepto,glosa FROM prm_factura_pago_concepto ORDER BY orden", "id_concepto", $id_concepto, '', 'Cualquiera', "150"); 
						}
					?>
				</td>
					<?php if (method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'PagoRetencionImpuesto')) { ?>
					<td align=right>
	<?php echo  __('Pago retención impuestos') ?>
					</td>
					<td align=left>
						<?php
						$pago_retencion_check = '';
						if ($pago_retencion)
							$pago_retencion_check = "checked='checked'";
						?>
						<input type="checkbox" name="pago_retencion" id="pago_retencion" value=1 <?php echo  $pago_retencion_check ?> />
					</td>
					<?php } ?>
			</tr>
			<tr>
				<td align=right>
					<?php echo  __('Banco') ?>
				</td>
				<td align=left>
					<?php 
						if ( UtilesApp::GetConf($Sesion, 'SelectMultipleFacturasPago') ) {
							echo Html::SelectQuery($Sesion, "SELECT id_banco, nombre FROM prm_banco ORDER BY orden", "id_banco[]", $id_banco, ' multiple size="4" onchange="CargarCuenta(\'id_banco[]\',\'id_cuenta[]\', true);"', 'Cualquiera', "190"); 
						} else {
							echo Html::SelectQuery($Sesion, "SELECT id_banco, nombre FROM prm_banco ORDER BY orden", "id_banco", $id_banco, ' onchange="CargarCuenta(\'id_banco\',\'id_cuenta\', false);"', 'Cualquiera', "190"); 
						}
					?>
				</td>
				<td align=right>
<?php echo  __('N° Cuenta') ?>
				</td>
				<td align=left>
					<?php 
						if ( UtilesApp::GetConf($Sesion, 'SelectMultipleFacturasPago') ) {
							echo Html::SelectQuery($Sesion, "SELECT cuenta_banco.id_cuenta
				, CONCAT( cuenta_banco.numero,
					 IF( prm_moneda.glosa_moneda IS NOT NULL , CONCAT(' (',prm_moneda.glosa_moneda,')'),  '' ) ) AS NUMERO
				FROM cuenta_banco
									LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cuenta_banco.id_moneda", "id_cuenta[]", $id_cuenta, ' multiple size="4" ', "Cualquiera", "150"); 
						} else {
							echo Html::SelectQuery($Sesion, "SELECT cuenta_banco.id_cuenta
									, CONCAT( cuenta_banco.numero,
										 IF( prm_moneda.glosa_moneda IS NOT NULL , CONCAT(' (',prm_moneda.glosa_moneda,')'),  '' ) ) AS NUMERO
									FROM cuenta_banco
									LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cuenta_banco.id_moneda", "id_cuenta", $id_cuenta, 'onchange="SetBanco(\'id_cuenta\',\'id_banco\');"', "Cualquiera", "150"); 
						}
					?>
				</td>
			</tr>
			<tr>
				<td align=right>
					<?php echo  __('Fecha inicio pago') ?>
				</td>
				<td nowrap align=left>
					<input type="text" id="fecha1" name="fecha1" value="<?php echo  $fecha1 ?>" id="fecha1" size="11" maxlength="10" />
					<img src="<?php echo  Conf::ImgDir() ?>/calendar.gif" id="img_fecha1" style="cursor:pointer" />
				</td>
				<td align=right>
					<?php echo  __('Fecha fin pago') ?>
				</td>
				<td nowrap align=left>
					<input type="text" id="fecha2" name="fecha2" value="<?php echo  $fecha2 ?>" id="fecha2" size="11" maxlength="10" />
					<img src="<?php echo  Conf::ImgDir() ?>/calendar.gif" id="img_fecha2" style="cursor:pointer" />
				</td>
			</tr>
			<tr>
				<td colspan=3 align=right>
					<input name=boton_buscar id='boton_buscar' type=button value="<?php echo  __('Buscar') ?>" onclick="BuscarFacturasPago(this.form,'buscar')" class=btn>
				</td>
				<td align="right">
					<input type="button" value="<?php echo __('Descargar Excel'); ?>" class="btn" name="boton_excel" onclick="BuscarFacturasPago(this.form, 'exportar_excel')">
				</td>
			</tr>
		</table>
	</fieldset><?
if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'UsaDisenoNuevo') ) || ( method_exists('Conf', 'UsaDisenoNuevo') && Conf::UsaDisenoNuevo() )))
	echo "</td></tr></table>";
?>
</form>
<!--table style="border: 0px solid black" width='94%'>
	<tr>
		<td > &nbsp;</td>
		<td width=220px align="right" style='border: 1px solid #BDBDBD'>
			<b><?php echo  __('Nueva') ?>:</b>&nbsp;
					<?php echo  Html::SelectQuery($Sesion, "SELECT id_documento_legal, glosa FROM prm_documento_legal", 'tipo_documento_legal', '', '', '', 150); ?>
			<br>
			<span onclick="CrearNuevoDocumentoLegal()" >
				<img src="<?php echo  Conf::ImgDir() ?>/mas_16.gif" /><a href="javascript:void(0)"><?php echo  __('Agregar Documento Tributario') ?></a>
				<br>&nbsp;
			</span>
		</td>
	</tr>
</table-->
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
		nuevaVentana('Agregar_Factura',730,580,dl_url, 'top=100, left=155');')	';
	}


	Calendar.setup(
	{
		inputField	: "fecha1",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha1"		// ID of the button
	}
);
	Calendar.setup(
	{
		inputField	: "fecha2",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha2"		// ID of the button
	}
);
</script>
<?
if ($opc == 'buscar') {
	$b->Imprimir();
}

if (( method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'TipoSelectCliente') == 'autocompletador' ) || ( method_exists('Conf', 'TipoSelectCliente') && Conf::TipoSelectCliente() )) {
	echo(Autocompletador::Javascript($Sesion));
}
echo(InputId::Javascript($Sesion));
$pagina->PrintBottom();
?>

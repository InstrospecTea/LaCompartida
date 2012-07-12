<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/classes/PaginaCobro.php';
require_once Conf::ServerDir() . '/classes/Asunto.php';
require_once Conf::ServerDir() . '/classes/Cobro.php';
require_once Conf::ServerDir() . '/classes/CobroMoneda.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/../app/classes/Observacion.php';
require_once Conf::ServerDir() . '/../app/classes/Contrato.php';
require_once Conf::ServerDir() . '/../app/classes/Factura.php';
require_once Conf::ServerDir() . '/../app/classes/FacturaPago.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/../app/classes/Gasto.php';
require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/../app/classes/FacturaPdfDatos.php';

$sesion = new Sesion(array('COB'));
$pagina = new PaginaCobro($sesion);
if(!isset($opc))$opc='';
$cobro = new Cobro($sesion);
$contrato = new Contrato($sesion);
$documento_cobro = new Documento($sesion);
$factura = new Factura($sesion);
$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');




//Asumo que solo quiero refrescar cuando gardo el cobro
// Aparentemente "guardar cobro" no existe. Es sólo "guardar"
if ($refrescar && ($opc == 'guardar_cobro' || $opc == 'guardar')) {
	?>
	<script type="text/javascript">
		if (window.opener !== undefined && window.opener.Refrescar) {
			window.opener.Refrescar();        
		}
	</script>
	<?php
}

$factura_pago = new FacturaPago($sesion);
$enpdf = ($opc == 'grabar_documento_pdf') ? true : false;

if ($opc == 'eliminar_pago') {
	if ($eliminar_pago > 0) {
		$factura_pago->Load($eliminar_pago);
		if ($factura_pago->Eliminar()) {
			$pagina->addInfo(__('Pago borrado con éxito'));
		}
	}
}

if (!$cobro->Load($id_cobro)) {
	$pagina->FatalError(__('Cobro inválido'));
}

if($opc == 'descargar_ledes'){
	include dirname(__FILE__) . '/ledes.php';
	exit;
}

if ($opc == "eliminar_documento") {
	$documento_eliminado = new Documento($sesion);
	$documento_eliminado->Load($id_documento_eliminado);

	if (empty($documento_eliminado->fields['es_adelanto'])) {
		$documento_eliminado->EliminarNeteos();
		$query_p = "DELETE from cta_corriente WHERE cta_corriente.documento_pago = '" . $id_documento_eliminado . "' ";
		mysql_query($query_p, $sesion->dbh) or Utiles::errorSQL($query_p, __FILE__, __LINE__, $sesion->dbh);

		if ($documento_eliminado->Delete()) {
			$pagina->AddInfo(__('El documento ha sido eliminado satisfactoriamente'));
		}
	} else {
		if (UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) {
			$id_neteo = $documento_eliminado->ObtenerIdNeteo($id_cobro);
			$factura_pago->LoadByNeteoAdelanto($id_neteo);
			$factura_pago->Eliminar();
		} else {
			$documento_eliminado->EliminarNeteo($id_cobro);
		}
		$pagina->AddInfo(__('El pago ha sido eliminado satisfactoriamente'));
	}
	$cobro->CambiarEstadoSegunFacturas();
}

$moneda_total = new Moneda($sesion);
$moneda_total->Load($cobro->fields['opc_moneda_total']);

if (!$contrato->Load($cobro->fields['id_contrato'])) {
	$pagina->FatalError(__('Contrato inválido'));
}

$idioma->Load($contrato->fields['codigo_idioma']);

/* Antes de cargar el documento_cobro, es posible que se deje en 0 (anular emisión) o que se reinicie (cambio de estado desde incobrable) */
if ($opc == 'anular_emision') {
	$estado_anterior = $cobro->fields['estado'];

	$cobro->AnularEmision($estado);

	// Se ingresa la anotación en el historial

	if ($estado_anterior != 'COBRO INCOBRABLE') {
		$his = new Observacion($sesion);
		$his->Edit('fecha', date('Y-m-d H:i:s'));
		$his->Edit('comentario', __('COBRO INCOBRABLE'));
		$his->Edit('id_usuario', $sesion->usuario->fields['id_usuario']);
		$his->Edit('id_cobro', $cobro->fields['id_cobro']);

		if ($his->Write()) {
			$pagina->AddInfo(__('Historial ingresado'));
		}
	}
}

// Se reinicia el documento del cobro
if ($cobro->fields['estado'] == 'INCOBRABLE' && $opc == 'guardar' && $estado != 'INCOBRABLE') {
	$cobro->ReiniciarDocumento();
}

// Ahora si se puede cargar el documento actualizado.
$moneda_documento = new Moneda($sesion);
if ($documento_cobro->LoadByCobro($id_cobro)) {
	$moneda_documento->Load($documento_cobro->fields['id_moneda']);
} else {
	$moneda_documento->Load($cobro->fields['opc_moneda_total']);
}
$moneda = new Moneda($sesion);

if (!$fecha_pago && $opc != 'guardar') {
	$fecha_pago = $cobro->fields['fecha_cobro'];
}
/* Comprobaciones previas */

if ($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION') {
	$pagina->Redirect("cobros5.php?popup=1&id_cobro=" . $id_cobro . ($id_foco ? '&id_foco=' . $id_foco : ''));
}

if (!UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) {

    
	if ($factura->LoadByCobro($id_cobro)) {

		if ($opc == 'anular_factura') {
			/**
			 * ANULAR FACTURA 
			 */
			// Buscar nuevo numero por la factura y ocupalo dentro de la tabla cobros
			$cobro->Edit('documento', '');
			$cobro->Edit('facturado', 0);

			if ($cobro->fields['estado'] == 'FACTURADO') {
				$estado = 'EMITIDO';
				$cambiar_estado = true;
				$fecha_facturacion = '';
				$cobro->Edit('fecha_facturacion', $fecha_facturacion);
			}

			$cobro->Write();

			$factura->Edit('anulado', 1);
			if ($factura->Escribir()) {
				$pagina->AddInfo(__('Factura anulado.'));
			}
		}
		$id_factura = $factura->fields['id_factura'];
	} else if ($opc == 'facturar') {
		/**
		 * FACTURAR
		 */
		$moneda_factura = new Moneda($sesion);
		$moneda_factura->Load($documento_cobro->fields['id_moneda']);

		// Se genera una factura "base", esta puede ser modificada
		$factura = new Factura($sesion);

		if (UtilesApp::GetConf($sesion, 'UsaNumeracionAutomatica')) {
			if (UtilesApp::GetConf($sesion, 'PermitirFactura')) {
				$numero_documento = $factura->ObtieneNumeroFactura();
			} else {
				$numero_documento = $documento;
			}
			$factura->Edit('numero', $numero_documento);
		
		} else {

			if(!$documento) $documento=$documento_cobro->fields['id_documento'];

			$factura->Edit('numero', $documento);
		}

	$factura->Edit('fecha', date('Y-m-d'));
	$factura->Edit('cliente', $contrato->fields['factura_razon_social']);
	$factura->Edit('RUT_cliente', $contrato->fields['rut']);
	$factura->Edit('codigo_cliente', $cobro->fields['codigo_cliente']);
	$factura->Edit('direccion_cliente', $contrato->fields['factura_direccion']);

	if (UtilesApp::GetConf($sesion, 'CalculacionCyC')) {
		$factura->Edit('subtotal', number_format(!empty($documento_cobro->fields['subtotal_honorarios']) ? $documento_cobro->fields['subtotal_honorarios'] : '0', $moneda_factura->fields['cifras_decimales'], ".", ""));
		$factura->Edit('subtotal_gastos', number_format(!empty($documento_cobro->fields['subtotal_gastos']) ? $documento_cobro->fields['subtotal_gastos'] : '0', $moneda_factura->fields['cifras_decimales'], ".", ""));
		$factura->Edit('descuento_honorarios', number_format(!empty($documento_cobro->fields['descuento_honorarios']) ? $documento_cobro->fields['descuento_honorarios'] : '0', $moneda_factura->fields['cifras_decimales'], ".", ""));
		$factura->Edit('subtotal_sin_descuento', number_format(!empty($documento_cobro->fields['subtotal_sin_descuento']) ? $documento_cobro->fields['subtotal_sin_descuento'] : '0', $moneda_factura->fields['cifras_decimales'], ".", ""));
	} else {
		$factura->Edit('subtotal', number_format(($documento_cobro->fields['honorarios'] - $documento_cobro->fields['impuesto']), $moneda_factura->fields['cifras_decimales'], ".", ""));
	}

	$factura->Edit('iva', number_format($documento_cobro->fields['impuesto'], $moneda_factura->fields['cifras_decimales'], ".", ""));
	$factura->Edit('total', number_format($documento_cobro->fields['honorarios'] + $documento_cobro->fields['gastos'], $moneda_factura->fields['cifras_decimales'], ".", ""));

	$estado = 'FACTURADO';
	$cambiar_estado = false;

	//if ($cobro->fields['estado'] != 'FACTURADO') {
	if ($cobro->fields['estado'] == 'FACTURADO') {
		 $cambiar_estado = true;
	}
	$cobro->Edit('fecha_facturacion', date('Y-m-d H:i:s'));
	$cobro->Edit('facturado', 1);

	if (UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) {
		$query_lista_docLegalesActivos = "SELECT
                                            group_concat(idfactura) as listaFacturas
                                            ,group_concat(idNotaCredito) as listaNotaCredito
                                            FROM (
                                            SELECT
                                                if(id_documento_legal = 1, if(letra is not null , letra, id_factura), '') as idfactura
                                            ,if(id_documento_legal = 2, if(letra is not null , letra, id_factura), '') as idNotaCredito
                                            ,id_cobro
                                            FROM factura
                                            WHERE id_cobro = '" . $id_cobro . "'
                                            AND anulado = 1
                                            )zz
                                            GROUP BY id_cobro";
		$resp_lista_docLegalesActivos = mysql_query($query_lista_docLegalesActivos, $sesion->dbh) or Utiles::errorSQL($resp_lista_docLegalesActivos, __FILE__, __LINE__, $sesion->dbh);
		list($lista_facturasActivas, $lista_NotaCreditoActivas) = mysql_fetch_array($resp_lista_docLegalesActivos);
		$cobro->Edit('documento', $lista_facturasActivas);
		
		$cambiar_estado = false;

		//if ($cobro->fields['estado'] != 'FACTURADO') // en vez de comparar si es distinto, comprueba si es un estado previo a Facturado
		if (UtilesApp::ComparaEstadoCobro($sesion,$cobro->fields['estado'],'<','FACTURADO')) {
		$estado = 'FACTURADO';
		$cambiar_estado = true;
		}
		

		if (UtilesApp::GetConf($sesion, 'NotaCobroExtra')) {
			$cobro->Edit('nota_cobro', $lista_NotaCreditoActivas);
		}
	} else { // SIN MODULO FACTURA
		if (UtilesApp::GetConf($sesion, 'UsaNumeracionAutomatica')) {
			$cobro->Edit('documento', $numero_documento);
		} else {
			$cobro->Edit('documento', $documento);
		}

		if (UtilesApp::GetConf($sesion, 'NotaCobroExtra')) {
			$cobro->Edit('nota_cobro', $nota_cobro);
		}
	}

	$cobro->Write();

	// Fechas periodo
	$datefrom = strtotime($cobro->fields['fecha_ini'], 0);
	$dateto = strtotime($cobro->fields['fecha_fin'], 0);
	$difference = $dateto - $datefrom; //Diff segundos
	$months_difference = floor($difference / 2678400);

	while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom) + ($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
		$months_difference++;
	}

	$datediff = $months_difference;

	if ($cobro->fields['fecha_ini'] != '' && $cobro->fields['fecha_ini'] != '0000-00-00') {
		$texto_fecha = __('entre los meses de') . ' ' . Utiles::sql3fecha($cobro->fields['fecha_ini'], '%B') . ' ' . __('y') . ' ' . Utiles::sql3fecha($cobro->fields['fecha_fin'], '%B');
	} else {
		$texto_fecha = __('hasta el mes de') . ' ' . Utiles::sql3fecha($cobro->fields['fecha_fin'], '%B');
	}

	if ($lang == 'es') {
		$servicio_periodo = 'Honorarios por servicios profesionales prestados %fecha_ini% %fecha_fin%';
		$meses_org = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		$mes_corto = array('jan', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic');
		$date_format = 'j-M-y';
		$date_desde = 'desde';
		$date_hasta = 'hasta';
	} else {
		$servicio_periodo = 'For legal services rendered %fecha_ini% %fecha_fin%';
		$meses_org = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		$month_short = array('jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec');
		$date_format = 'M-d-y';
		$date_desde = 'from';
		$date_hasta = 'until';
	}

	// Fecha inicio
	if ($cobro->fields['fecha_ini'] && $cobro->fields['fecha_ini'] != '0000-00-00') {
		$servicio_periodo = str_replace('%fecha_ini%', $date_desde . ' ' . str_replace($meses_org, $month_short, date($date_format, strtotime($cobro->fields['fecha_ini']))), $servicio_periodo);
	} else {
		$servicio_periodo = str_replace('%fecha_ini%', '', $servicio_periodo);
	}

	// Fecha fin
	if ($cobro->fields['fecha_fin'] && $cobro->fields['fecha_fin'] != '0000-00-00') {
		$servicio_periodo = str_replace('%fecha_fin%', $date_desde . ' ' . str_replace($meses_org, $month_short, date($date_format, strtotime($cobro->fields['fecha_fin']))), $servicio_periodo);
	} else {
		$servicio_periodo = str_replace('%fecha_fin%', '', $servicio_periodo);
	}

	$fecha_diff = $datediff > 0 && $datediff < 12 ? $texto_fecha : __('durante el mes de') . ' ' . Utiles::sql3fecha($cobro->fields['fecha_fin'], '%B');
	$factura->Edit('descripcion', $servicio_periodo);
	$factura->Edit('id_cobro', $cobro->fields['id_cobro']);
	$factura->Edit('id_moneda', $documento_cobro->fields['id_moneda']);
	$factura->Edit('honorarios', $documento_cobro->fields['subtotal_sin_descuento']);
	$factura->Edit('gastos', $documento_cobro->fields['gastos']);

	if ($factura->Escribir()) {
		if ($id_cobro) {
			$documento = new Documento($sesion);
			$documento->LoadByCobro($id_cobro);

			$valores = array(
				$factura->fields['id_factura'],
				$id_cobro,
				$documento->fields['id_documento'],
				$factura->fields['subtotal'],
				$factura->fields['iva'],
				$documento->fields['id_moneda'],
				$documento->fields['id_moneda']
			);

			$query = "DELETE FROM factura_cobro WHERE id_factura = '" . $factura->fields['id_factura'] . "' AND id_cobro = '" . $id_cobro . "' ";
			$resp = mysql_query($query, $sesion->dbh) or   Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

			$query = "INSERT INTO factura_cobro (id_factura, id_cobro, id_documento, monto_factura, impuesto_factura, id_moneda_factura, id_moneda_documento)
                                                            VALUES ('" . implode("','", $valores) . "')";
			$resp = mysql_query($query, $sesion->dbh) or  Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		}
	}

	$id_factura = $factura->fields['id_factura'];
    }
}

if ($opc == 'guardar') {
	if (!UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) {
		if(!$estado) $estado = $cobro->fields['estado'];
		if ($facturado == 1) {
			if (!$cobro->fields['fecha_facturacion']) {
				$cobro->Edit('fecha_facturacion', date('Y-m-d H:i:s'));
			}
			$cobro->Edit('facturado', $facturado);
			if ($cobro->fields['estado'] == 'EMITIDO') {
				$estado = 'FACTURADO';
			}
		} else {
			$cobro->Edit('facturado', '0');
			if ($cobro->fields['estado'] == 'FACTURADO') {
				$estado = 'EMITIDO';
				$fecha_facturacion = '';
			}
		}
	}

	$cambiar_estado = false;

	if ($estado != $cobro->fields['estado']) {
		$cambiar_estado = true;
	}
       
	if($fecha_emision) $cobro->Edit('fecha_emision', $fecha_emision ? Utiles::fecha2sql($fecha_emision) : '');
	if(isset($fecha_envio)) $cobro->Edit('fecha_enviado_cliente', $fecha_envio ? Utiles::fecha2sql($fecha_envio) : '');
	if(isset($fecha_pago)) $cobro->Edit('fecha_cobro', $fecha_pago ? Utiles::fecha2sql($fecha_pago) : '');
	if(isset($fecha_pago_parcial)) $cobro->Edit('fecha_pago_parcial', $fecha_pago_parcial ? Utiles::fecha2sql($fecha_pago_parcial) : '');
	if(isset($fecha_facturacion)) $cobro->Edit('fecha_facturacion', $fecha_facturacion ? Utiles::fecha2sql($fecha_facturacion) : '');
	
	//al guardar el cobro verifica si hay que dejarlo como pagado, en concordancia al documento de deuda que lo respalda
	$documentocobro = new Documento($sesion);
	$documentocobro->LoadByCobro($cobro->fields['id_cobro']);
	$honorarios_pagados=($documentocobro->fields['honorarios_pagados']=='SI')? true:false;
	$gastos_pagados=($documentocobro->fields['gastos_pagados']=='SI')? true:false;
	$setpagos=$cobro->SetPagos($honorarios_pagados, $gastos_pagados);
	//echo 'Honorarios: '.$honorarios_pagados.' y gastos'. $gastos_pagados;
	// Ahora hay que revisar que no se haya pasado a PAGADO, cambiando estado a PAGADO (por Historial).
	if ($estado == 'PAGADO' && $cobro->fields['estado'] == 'PAGADO') {
		$cambiar_estado = false;
	}
	if($setpagos) $estado='PAGADO';
	
	$cobro->Edit('forma_envio', $forma_envio);

	if (!UtilesApp::GetConf($sesion, 'UsaNumeracionAutomatica')) {
		//$cobro->Edit('documento', $documentocobro->fields['id_documento']); Se comenta esta linea, se vuelve a como estaba en la revision 7215
        if($documento)        $cobro->Edit('documento', $documento);
		if (UtilesApp::GetConf($sesion, 'NotaCobroExtra')) {
			$cobro->Edit('nota_cobro', $nota_cobro);
		}
	}

	if ($email_cliente != $contrato->fields['email_contacto']) {
		$contrato->Edit('email_contacto', $email_cliente);
		$contrato->Write();
	}

	$cobro->Edit('se_esta_cobrando', $se_esta_cobrando);

	if ($opc_informar_contabilidad == 'informar') {
		$cobro->Edit('estado_contabilidad', 'PARA INFORMAR');
	} else if ($opc_informar_contabilidad == 'informar y facturar') {
		$cobro->Edit('estado_contabilidad', 'PARA INFORMAR Y FACTURAR');
	}

	$cobro->Write();
}


if ($cambiar_estado && $estado!='') {
	$estado_anterior = $cobro->fields['estado'];

	if ($estado == 'EMITIDO' && !$cobro->fields['fecha_emision']) {
		$cobro->Edit('fecha_emision', date('Y-m-d H:i:s'));
	}

	if ($estado == 'ENVIADO AL CLIENTE' && !$cobro->fields['fecha_enviado_cliente']) {
		$cobro->Edit('fecha_enviado_cliente', date('Y-m-d H:i:s'));
	}

	if ($estado == 'FACTURADO' && !$cobro->fields['fecha_facturacion']) {
		$cobro->Edit('fecha_facturacion', date('Y-m-d H:i:s'));
	}

	if ($estado == 'PAGO PARCIAL' && !$cobro->fields['fecha_pago_parcial']) {
		$cobro->Edit('fecha_pago_parcial', date('Y-m-d H:i:s'));
	}

	if ($estado == 'PAGADO' && !$cobro->fields['fecha_cobro']) {
		$cobro->Edit('fecha_cobro', date('Y-m-d H:i:s'));
	}

	if($estado) $cobro->Edit('estado', $estado);
	$cobro->Write();
	// Se ingresa la anotación en el historial
	if ($estado_anterior != $estado && $estado) { // no ingresa historial si no se recibe explícitamente un nuevo estado
		$his = new Observacion($sesion);
                
                if($ultimaobservacion=$his->UltimaObservacion($cobro->fields['id_cobro'])) {
                   // mail('ffigueroa@lemontech.cl',"Cambiando De $estado_anterior a $estado",json_encode($ultimaobservacion));
                
                    if($ultimaobservacion['comentario']!=__("COBRO $estado")) {
                        $his->Edit('fecha', date('Y-m-d H:i:s'));
                        $his->Edit('comentario', __("COBRO $estado"));
                        $his->Edit('id_usuario', $sesion->usuario->fields['id_usuario']);
                        $his->Edit('id_cobro', $cobro->fields['id_cobro']);

                        if ($his->Write()) {
                                $pagina->AddInfo(__('Historial ingresado'));
                        }
                    }
                }
	}
}

if ($opc == 'grabar_documento' || $opc == 'guardar ' || $opc == 'grabar_documento_pdf') {
	$cobro->Edit("opc_ver_detalles_por_hora", $opc_ver_detalles_por_hora);
	$cobro->Edit("opc_ver_modalidad", $opc_ver_modalidad);
	$cobro->Edit("opc_ver_profesional", $opc_ver_profesional);
	$cobro->Edit("opc_ver_profesional_iniciales", $opc_ver_profesional_iniciales);
	$cobro->Edit("opc_ver_profesional_categoria", $opc_ver_profesional_categoria);
	$cobro->Edit("opc_ver_profesional_tarifa", $opc_ver_profesional_tarifa);
	$cobro->Edit("opc_ver_profesional_importe", $opc_ver_profesional_importe);
	$cobro->Edit("opc_ver_gastos", $opc_ver_gastos);
	$cobro->Edit("opc_ver_concepto_gastos", $opc_ver_concepto_gastos);
	$cobro->Edit("opc_ver_morosidad", $opc_ver_morosidad);
	$cobro->Edit("opc_ver_resumen_cobro", $opc_ver_resumen_cobro);
	$cobro->Edit("opc_ver_detalles_por_hora_iniciales", $opc_ver_detalles_por_hora_iniciales);
	$cobro->Edit("opc_ver_detalles_por_hora_categoria", $opc_ver_detalles_por_hora_categoria);
	$cobro->Edit("opc_ver_detalles_por_hora_tarifa", $opc_ver_detalles_por_hora_tarifa);
	$cobro->Edit("opc_ver_detalles_por_hora_importe", $opc_ver_detalles_por_hora_importe);
	$cobro->Edit("opc_ver_descuento", $opc_ver_descuento);
	$cobro->Edit("opc_ver_tipo_cambio", $opc_ver_tipo_cambio);
	$cobro->Edit("opc_ver_numpag", $opc_ver_numpag);
	$cobro->Edit("opc_papel", $opc_papel);
	$cobro->Edit("opc_ver_solicitante", $opc_ver_solicitante);
	$cobro->Edit('opc_ver_carta', $opc_ver_carta);
	$cobro->Edit("opc_ver_asuntos_separados", $opc_ver_asuntos_separados);
	$cobro->Edit("opc_ver_horas_trabajadas", $opc_ver_horas_trabajadas);
	$cobro->Edit("opc_ver_cobrable", $opc_ver_cobrable);
       	$cobro->Edit("modalidad_calculo", $modalidad_calculo); // permite especificar el uso de Cobro->GenerarDocumento2 en vez de GenerarDocumento

// Opciones especificos para Vial Olivares
	$cobro->Edit("opc_restar_retainer", $opc_restar_retainer);
	$cobro->Edit("opc_ver_detalle_retainer", $opc_ver_detalle_retainer);
	$cobro->Edit("opc_ver_valor_hh_flat_fee", $opc_ver_valor_hh_flat_fee);
	$cobro->Edit('id_carta', $id_carta);
	$cobro->Edit('id_formato', $id_formato);
	$cobro->Edit('codigo_idioma', $lang);
	$cobro->Edit("opc_ver_columna_cobrable", $opc_ver_columna_cobrable);

	if ($cobro->Write()) {
		if ($opc == 'grabar_documento' || $opc == 'grabar_documento_pdf') {
			include dirname(__FILE__) . '/cobro_doc.php';
			exit;
		}
	}
} else if ($opc == 'grabar_documento_factura') {
	include dirname(__FILE__) . '/factura_doc.php';
	exit;
} else if ($opc == 'grabar_documento_factura_pdf') {
	$factura_pdf_datos = new FacturaPdfDatos($sesion);
	$factura_pdf_datos->generarFacturaPDF($id_factura_grabada);
} elseif ($opc == 'descargar_excel' || $opc == 'descargar_excel_especial' || $opc == 'descargar_excel_rentabilidad') {
	$cobro->Edit("opc_ver_detalles_por_hora", $opc_ver_detalles_por_hora);
	$cobro->Edit("opc_ver_modalidad", $opc_ver_modalidad);
	$cobro->Edit("opc_ver_profesional", $opc_ver_profesional);
	$cobro->Edit("opc_ver_profesional_iniciales", $opc_ver_profesional_iniciales);
	$cobro->Edit("opc_ver_profesional_categoria", $opc_ver_profesional_categoria);
	$cobro->Edit("opc_ver_profesional_tarifa", $opc_ver_profesional_tarifa);
	$cobro->Edit("opc_ver_profesional_importe", $opc_ver_profesional_importe);
	$cobro->Edit("opc_ver_gastos", $opc_ver_gastos);
	$cobro->Edit("opc_ver_concepto_gastos", $opc_ver_concepto_gastos);
	$cobro->Edit("opc_ver_morosidad", $opc_ver_morosidad);
	$cobro->Edit("opc_ver_resumen_cobro", $opc_ver_resumen_cobro);
	$cobro->Edit("opc_ver_detalles_por_hora_iniciales", $opc_ver_detalles_por_hora_iniciales);
	$cobro->Edit("opc_ver_detalles_por_hora_categoria", $opc_ver_detalles_por_hora_categoria);
	$cobro->Edit("opc_ver_detalles_por_hora_tarifa", $opc_ver_detalles_por_hora_tarifa);
	$cobro->Edit("opc_ver_detalles_por_hora_importe", $opc_ver_detalles_por_hora_importe);
	$cobro->Edit("opc_ver_valor_hh_flat_fee", $opc_ver_valor_hh_flat_fee);
	$cobro->Edit("opc_ver_descuento", $opc_ver_descuento);
	$cobro->Edit("opc_ver_tipo_cambio", $opc_ver_tipo_cambio);
	$cobro->Edit("opc_ver_numpag", $opc_ver_numpag);
	$cobro->Edit("opc_papel", $opc_papel);
	$cobro->Edit("opc_ver_solicitante", $opc_ver_solicitante);
	$cobro->Edit('opc_ver_carta', $opc_ver_carta);
	$cobro->Edit("opc_ver_asuntos_separados", $opc_ver_asuntos_separados);
	$cobro->Edit("opc_ver_horas_trabajadas", $opc_ver_horas_trabajadas);
	$cobro->Edit("opc_ver_cobrable", $opc_ver_cobrable);

	// Opciones especificos para Vial Olivares
	$cobro->Edit("opc_restar_retainer", $opc_restar_retainer);
	$cobro->Edit("opc_ver_detalle_retainer", $opc_ver_detalle_retainer);
	$cobro->Edit('id_carta', $id_carta);
	$cobro->Edit('id_formato', $id_formato);
	$cobro->Edit('codigo_idioma', $lang);

	$cobro->Edit("opc_ver_columna_cobrable", $opc_ver_columna_cobrable);

	if ($cobro->Write()) {
		$desde_cobros_emitidos = true;
		/*
		  if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'XLSFormatoEspecial') ) || ( method_exists('Conf', 'XLSFormatoEspecial') && Conf::XLSFormatoEspecial() ) )
		  require_once Conf::ServerDir().'/../app/interfaces/cobros_xls_formato_especial.php';
		  else
		  require_once Conf::ServerDir().'/../app/interfaces/cobros_xls.php';
		 */
		if ($opc == 'descargar_excel_especial') {
			require_once Conf::ServerDir() . '/../app/interfaces/' . UtilesApp::GetConf($sesion, 'XLSFormatoEspecial');
		} else if ($opc == 'descargar_excel_rentabilidad') {
			require_once Conf::ServerDir() . '/../app/interfaces/cobros_xls_rentabilidad.php';
		} else {
			require_once Conf::ServerDir() . '/../app/interfaces/cobros_xls.php';
		}
		exit;
	}
}

$cobro->Edit('etapa_cobro', '5');
$cobro->Write();
$moneda->Load($cobro->fields['id_moneda']);

$pagina->titulo = __('Imprimir') . ' ' . __('Cobro') . ' #' . $id_cobro . ' ' . __('para') . " " . Utiles::Glosa($sesion, $cobro->fields['codigo_cliente'], 'glosa_cliente', 'cliente', 'codigo_cliente');
$pagina->PrintTop($popup);

if ($popup) {
	$cobro->LoadAsuntos();
	$asunto = new Asunto($sesion);
	$glosa_asunto_titulo = '';
	$max_largo_titulo_asunto = 30;

	for ($x = 0; $x < count($cobro->asuntos); $x++) {
		$separador = ', ';
		if ($x == 0) {
			$separador_inicio = '  -  ';
		} else if ($x == (count($cobro->asuntos)) - 1) {
			$separador = '.';
		}

		if (count($cobro->asuntos) == 1) {
			$separador = '.';
		}

		$glosa_asunto_titulo .= Utiles::Glosa($sesion, $cobro->asuntos[$x], 'glosa_asunto', 'asunto', 'codigo_asunto') . $separador;
	}

	$glosa_asunto_alt = $glosa_asunto_titulo;
	$glosa_asunto_titulo = substr($glosa_asunto_titulo, 0, $max_largo_titulo_asunto);

	if (strlen($glosa_asunto_alt) > $max_largo_titulo_asunto) {
		$glosa_asunto_titulo = $glosa_asunto_titulo . '...';
	}

	$glosa_asunto_titulo = $separador_inicio . $glosa_asunto_titulo;
	// $glosa_asunto_titulo = Utiles::Glosa($sesion, $cobro->LoadAsuntos());
	?>
	<table width="100%" border="0" cellspacing="0" cellpadding="2">
		<tr>
			<td valign="top" align="left" class="titulo" bgcolor="<?php echo UtilesApp::GetConf($sesion, 'ColorTituloPagina'); ?>">
				<span title="<?php echo $glosa_asunto_alt; ?>"><?php echo __('Imprimir') . ' ' . __('Cobro') . ' #' . $id_cobro . ' ' . __('para') . " " . Utiles::Glosa($sesion, $cobro->fields['codigo_cliente'], 'glosa_cliente', 'cliente', 'codigo_cliente') . " " . $glosa_asunto_titulo; ?></span>
			</td>
		</tr>
	</table>
	<br />
	<?php
}

$pagina->PrintPasos($sesion, 5, '', $id_cobro, $cobro->fields['incluye_gastos'], $cobro->fields['incluye_honorarios']);

// Moneda base
$moneda_base = new Objeto($sesion, '', '', 'prm_moneda', 'id_moneda');
$moneda_base->Load($cobro->fields['id_moneda_base']);

// Tipo cambio segï¿½n moneda impresion (opc_moneda)
$cobro_moneda = new CobroMoneda($sesion);
$cobro_moneda->Load($cobro->fields['id_cobro']);
$tipo_cambio_moneda_total = $cobro_moneda->GetTipoCambio($id_cobro, $cobro->fields['opc_moneda_total'] != '' ? $cobro->fields['opc_moneda_total'] : 1);

/**
 * Contrato 
 */
if ($cobro->fields['id_contrato'] != '') {
	$contrato = new Contrato($sesion);
	$contrato->Load($cobro->fields['id_contrato']);
}
?>
<script type="text/javascript">
    
    var ciclo = self.setInterval("refrescaestado('estado_contabilidad')", 15000);
    jQuery(document).ready(function() {
        var Elidcobro = jQuery("#elidcobro").val();
       jQuery.post('ajax/cobros7.php', {id_cobro: Elidcobro, opc: 'cajafacturas'}, function(data) {
            jQuery("#cajafacturas").html(data);
        });
	   jQuery.post('ajax/cobros7.php', {id_cobro: Elidcobro, opc: 'listapagos'}, function(data) {
            jQuery("#lista_pagos").html(data);
        });
        jQuery("#elidcobro").click(function() {
            var Elidcobro=jQuery("#elidcobro").val();
            jQuery.post('ajax/cobros7.php', {id_cobro: Elidcobro, opc: 'listapagos'}, function(data) {
                jQuery("#lista_pagos").html(data);
            });
        });
     
        jQuery(".integracion").click(function() {
            var estado = jQuery(this).attr('rel').toUpperCase();           
         
            var Idcobro = jQuery("#elidcobro").val();
            var Opc = 'guardar';
            var Opcic = jQuery(this).attr('id');
            var Confirma = 0;
        
       
            if (estado == 'INFORMADO' && Opcic == 'parainfo') { 
                if (!confirm('El Cobro ya ha sido informado a Contabilidad. ¿Está seguro que quiere actualizar el número de Nota de venta?')) {
                    return false;
                }
                Confirma = 1;
            } else if (estado == 'INFORMADO PARA FACTURAR' || estado == 'INFORMADO Y FACTURADO' || estado == 'PARA INFORMAR Y FACTURAR') {
                if( !confirm('El Cobro ya ha sido informado a Contabilidad con la instrucción de facturar. ¿Está seguro que quiere informar nuevamente?') ) {
                    return false;
                }
                Confirma = 1;
            }
        
            jQuery.post('ajax/cobros7.php',{id_cobro: Idcobro, opc: Opc, opc_ic: Opcic, confirma: Confirma},function(data) {
                if (data.length > 0) {
                    var respuesta = data.split('|');
                    jQuery('#estado_contabilidad').html(respuesta[0]).attr('title',respuesta[1]);
                    jQuery("#retorno").html(respuesta[3]);
                    jQuery(".integracion").attr('rel',respuesta[2]);
                
                    if (respuesta[4].length > 0) {
                        jQuery("#nota_venta_contabilidad").html('Nota de venta: '+respuesta[4]).css('display','inline-block');    
                    } else {
                        jQuery("#nota_venta_contabilidad").fadeOut("slow");     
                    }
                }
            });
        });
    });
    
    function Refrescarse() 
    {
        var Elidcobro = jQuery("#elidcobro").val();
        jQuery.post('ajax/cobros7.php', {id_cobro: Elidcobro, opc: 'listapagos'}, function(data) {
            jQuery("#lista_pagos").html(data);
        });
		Refrescar();
    }

    function refrescaestado(id) 
    {
        jQuery('#' + id).css("opacity", "0.75");
        var Idcobro = jQuery("#elidcobro").val();
    
        jQuery.post('ajax/cobros7.php', {id_cobro: Idcobro, opc: 'refrescar'}, function(data) {
            if (data.length > 0) {
                var respuesta = data.split('|');
                jQuery("#retorno").html(respuesta[3]);
                jQuery(".integracion").attr('rel', respuesta[2]);
                jQuery('#' + id).html(respuesta[0]).attr('title', respuesta[1]).show();    
            
                if (respuesta[4].length > 0) {
                    jQuery("#nota_venta_contabilidad").html('Nota de venta: ' + respuesta[4]).css('display', 'inline-block');    
                } else {
                    jQuery("#nota_venta_contabilidad").fadeOut("slow");     
                }
            }
        });
    } 

    function Informar(form, valor, estado_contabilidad)
    {
        if (estado_contabilidad) {
            var estado = estado_contabilidad.toUpperCase();
            if (estado == 'INFORMADO' && valor == 'informar') { 
                if (!confirm('El Cobro ya ha sido informado a Contabilidad. ¿Está seguro que quiere actualizar el número de Nota de venta?')) {
                    return false;
                }
            } else if (estado == 'INFORMADO PARA FACTURAR' || estado == 'INFORMADO Y FACTURADO' || estado == 'PARA INFORMAR Y FACTURAR') {
                if (!confirm('El Cobro ya ha sido informado a Contabilidad con la instrucción de facturar. ¿Está seguro que quiere informar nuevamente?')) {
                    return false;
                }
            }
        }
        //form.opc_informar_contabilidad.value = valor;
        //ValidarTodo(form);
        return false;
        //PARA INFORMAR Y FACTURAR
    }       
    

    //document.observe('dom:loaded', function() {
    //    new Tip('c_edit', 'Continuar con el cobro', {offset: {x:-2, y:5}});
    //});

    function Refrescar()
    {
        self.location.href = 'cobros6.php?popup=<?php echo $_GET['popup']; ?>&opc=guardar&id_cobro=<?php echo $id_cobro; ?>&facturado=<?php echo intval($cobro->fields['facturado']); ?>';
    }

    function MostrarTipoCambio()
    {
        $('TipoCambioDocumento').show();
    }

    function CancelarDocumentoMoneda()
    {
        $('TipoCambioDocumento').hide();
    }

    function MontoValido(id_campo)
    {
        var monto_orig = document.getElementById(id_campo).value;
        var monto = monto_orig.replace('\,', '.');
        var arr_monto = monto.split('\.');
        var monto = arr_monto[0];
    
        for ($i = 1; $i < arr_monto.length - 1; $i++) {
            monto += arr_monto[$i];
        }
    
        if (arr_monto.length > 1) {
            monto += '.' + arr_monto[arr_monto.length - 1];
        }

        if (monto != monto_orig) {
            document.getElementById(id_campo).value = monto;
        }
    }

    function ActualizarDocumentoMoneda()
    {
        ids_monedas = $('ids_monedas_documento').value;
        arreglo_ids = ids_monedas.split(',');
        var tc = new Array();
    
        for (var i = 0; i< arreglo_ids.length; i++) {
            tc[i] = $('documento_moneda_' + arreglo_ids[i]).value;
        }
    
        $('contenedor_tipo_cambio').innerHTML =
            "<table width=510px><tr><td align=center><br><br><img src='<?php echo Conf::ImgDir() ?>/ajax_loader.gif'/><br><br></td></tr></table>";
    
        var http = getXMLHTTP();
        var url = root_dir + '/app/interfaces/ajax.php?accion=actualizar_documento_moneda&id_documento=<?php echo $documento_cobro->fields['id_documento'] ?>&ids_monedas=' + ids_monedas+'&tcs='+tc.join(',');
    
        http.open('get', url);
        http.onreadystatechange = function() {
            if(http.readyState == 4) {
                var response = http.responseText;
                if(response == 'EXITO') {
                    Refrescar();
                }
            }
        }
        http.send(null);
    }

    function AgregarPagoFactura()
    {
        var monto = 0;
        var lista_facturas = "";
    
        $$('[id^="saldo_"]').each(function(elem) {
            ids = elem.id.split('_');
            if ($('pagar_factura_' + ids[1]).checked) {
                lista_facturas += "," + ids[1];
                monto += (MontoFacturaMoneda(ids[1]) - 0);
            }
        });
        lista_facturas = lista_facturas.substr(1);
		if (lista_facturas) {
			var codigo_cliente = '<?php echo $cobro->fields['codigo_cliente']; ?>';
			nuevaVentana('Agregar_Pago', 630, 520, 'agregar_pago_factura.php?lista_facturas='+lista_facturas+'&id_moneda='+$('opc_moneda_total').value+'&monto_pago='+monto+'&codigo_cliente='+codigo_cliente+'&id_cobro=<?php echo $cobro->fields['id_cobro'] ?>&popup=1', 'top=100, left=155, scrollbars=yes');			
		} else {
			alert('Seleccione almenos un documento.');
		}
    }

    function showOpcionDetalle(id, bloqueDetalle)
    {
        if ($(id).checked) {
            $(bloqueDetalle).style.display = "table-row";
        } else {
            $(bloqueDetalle).style.display = "none";
        }
    }

    function EliminarPago(id_pago)
    {
        $('opc').value = 'eliminar_pago';
        $('eliminar_pago').value = id_pago;
        if ($('eliminar_pago').value == id_pago) {
            $('todo_cobro').submit();
        }
    }

    function MontoFacturaMoneda(id)
    {
        var monto = $('saldo_' + id).value;
        var tc1 = $('tipo_cambio_factura_' + id).value;
        var cd1 = $('cifras_decimales_factura_' + id).value;
        var tc2 = $('tipo_cambio_moneda_total').value;
        var cd2 = $('cifras_decimales_moneda_total').value;

        var resultado = (Math.pow(10, cd2) * (monto * tc1 / tc2)).round() / Math.pow(10, cd2);
        return resultado;
    }

    function trim(s)
    {
        var l = 0; var r = s.length - 1;
        while(l < s.length && s[l] == ' ') {
            l++;
        }
        while(r > l && s[r] == ' ') {
            r--;	
        }
        return s.substring(l, r + 1);
    }

    function ValidarTodo(form)
    {
        //Significa que estoy anulando la emisión
        if (form.estado.value == 'CREADO') { 
            if (!confirm('<?php echo __("¿Está seguro que requiere anular la emisión de este cobro?"); ?>')) {
                return false;
            } else {
                if (form.existe_factura.value == 1) {
                    alert('No se puede anular <?php echo __('un cobro'); ?> que tiene facturas asociados.');
                    form.estado.value = form.estado_original.value;
                    return false;
                } else {
                    form.action = 'cobros5.php?popup=1';
                    form.opc.value = 'anular_emision';
                    form.submit();
                    return false;
                }
            }
        }

        //Significa que estoy anulando la emisión
        if( form.estado.value == 'EN REVISION' ) { 

<?php if (UtilesApp::GetConf($sesion, "ObservacionReversarCobroPagado")) { ?>
				if (form.estado_original.value == 'PAGADO' && trim(form.estado_motivo.value) == "") {
					jQuery('#dialogomodal').html(jQuery('#div_motivo_cambio_estado').html());
					jQuery('#dialogomodal').dialog('open').dialog('option','title','Ingresar Motivo').dialog('option', 'height', '170');
					return false;
				}

				//Ingresa el mensaje del motivo del cambio de estado al historial
				var iframe = $$('iframe')[0];
				var doc_iframe = iframe.contentWindow.document;
				var historial_obs = doc_iframe.getElementById('historial_observaciones');
				historial_obs.value += form.estado_motivo.value;
				doc_iframe.getElementById('opcion').value = 'guardar';

				new Ajax.Request(iframe.src, {
					asynchronous: false,
					method: 'post',
					parameters: doc_iframe.getElementById('formulario').serialize(true),
					onSuccess: function(transport){},
					onFailure: function(e){}
				});
<?php } ?>

            if (form.existe_pago.value == 1) {
                var texto = '¡Este cobro tiene pagos asociados! ';
            } else if (form.existe_factura.value == 1) {
                var texto = '¡Este cobro tiene facturas asociadas! ';
            }
        
            if (!confirm(texto + '<?php echo __("¿Está seguro que requiere anular la emisión de este cobro?"); ?>')) {
                return false;
            } else {
                form.action = 'cobros5.php?popup=1';
                form.opc.value = 'anular_emision';
                form.submit();
                return false;
            }
        }

        //Significa que estoy dando como INCOBRABLE el cobro
        if (form.estado.value == 'INCOBRABLE') {
            if (!confirm('<?php echo __("¿Está seguro que desea definir ") . __("este cobro") . __(" como \"Incobrable\"?"); ?>')) {
                return false;
            } else {
                form.opc.value = 'anular_emision';
                form.submit();
                return false;
            }
        }

        // No se puede avanzar a PAGADO por aqui. Debe ser mediante Agregar Pago.
        if (form.todopagado.value=='NO' && form.estado.value == 'PAGADO' && form.estado_original.value != 'PAGADO' && (!form.existe_pago.value || form.existe_pago.value == 0)) {
            
	    alert('<?php echo __("No puede definir ") . __("el Cobro") . __(" como \"PAGADO\". Debe ingresar un documento de pago completo por el saldo pendiente."); ?>');
            return false;
        }

        //No se puede avanzar a PAGADO por aqui. Debe ser mediante Agregar Pago.
        if (form.estado.value == 'PAGO PARCIAL' && form.estado_original.value != 'PAGO PARCIAL' && ( !form.existe_pago.value || form.existe_pago.value == 0 )) { 
            alert('<?php echo __("No puede definir ") . __("el Cobro") . __(" como \"PAGO PARCIAL\". Debe ingresar un documento de pago.") ?>');
            return false;
        }

        if (form.estado.value != form.estado_original.value) {
            if(!confirm('<?php echo __("¿Está seguro de que desea modificar el estado del cobro?") ?>')) {
                return false;
            }
        }

        form.opc.value = 'guardar';
        form.submit();
        return true;
    }

    function AnularFactura(form, opcion)
    {
        if (!form) {
            var form = $('todo_cobro');
        }
        $('facturado').checked = false;
        form.opc.value = 'anular_factura';
        form.submit();
        return true;
    }

    function MostrarVerDocumentosPagos(id_factura)
    {
        $$('[id^="VerDocumentosPagos_"]').each(Element.hide);
        $('VerDocumentosPagos_' + id_factura).show();
    }

    function CancelarVerDocumentosPagos(id_factura)
    {
        $('VerDocumentosPagos_' + id_factura).hide();
    }
 
    function ValidarFactura(form, id_factura, opcion)
    {
<?php if (UtilesApp::GetConf($sesion, 'PermitirFactura')) { ?>
			if (!form) {
				var form = $('todo_cobro');
			}

			if (opcion == 'imprimir') {
	<?php if (UtilesApp::GetConf($sesion, 'ImprimirFacturaPdf') && !UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) { ?>
						nuovaFinestra('Imprimir_Factura',730,580,'agregar_factura.php?opc=generar_factura&id_cobro=<?php echo $id_cobro ?>&id_factura='+id_factura, 'top=500, left=500');
						//ValidarTodo(form);
	<?php } else { ?>
						form.opc.value='grabar_documento_factura';
						form.id_factura_grabada.value = id_factura;
	<?php } ?>
				} else if(opcion =='imprimir_pdf') {
					form.opc.value = 'grabar_documento_factura_pdf';
					form.id_factura_grabada.value = id_factura;
				} else {
					$('facturado').checked = true;
					form.opc.value = 'facturar';
				}
	        
				form.submit();
				return true;
<?php } else { ?>
			alert('Funcionalidad en desarrollo.');
			return false;
<?php } ?>
    }

    function AlertarPago(checked)
    {
        if(checked) {
            alert("Usted ha marcado un Pago sin especificar documento.\nSe recomienda utilizar la opción de 'Agregar Pago' para cancelar el cobro.");
        }
    }

    function ToggleDiv(divId)
    {
        var divObj = document.getElementById(divId);

        if(divObj) {
            if( divObj.style.display == 'none' ) {
                divObj.style.display = 'inline';
            } else {
                divObj.style.display = 'none';
            }
        }
    }

    // Desactiva/activa carta selector
    function ActivaCarta(check)
    {
        var form = $('todo_cobro');
        if (check) {
            form.id_carta.disabled = false;
        } else {
            form.id_carta.disabled = true;
        }
    }

    function VerDetalles(form)
    {
        if(window.console) console.log(form);
        form.opc.value = 'grabar_documento';
        form.submit();
        return true;
    }

    function VerDetallesPDF(form)
    {
        form.opc.value = 'grabar_documento_pdf';
        form.submit();
        return true;
    }

    function DescargarExcel(form, formato_especial)
    {
        if (formato_especial == 'especial') {
            form.opc.value = 'descargar_excel_especial';
        } else if (formato_especial == 'rentabilidad') {
            form.opc.value = 'descargar_excel_rentabilidad';
        } else {
            form.opc.value = 'descargar_excel';
        }
    
        form.submit();
        return true;
    }

    function DescargarLedes(form)
    {
		form.opc.value = 'descargar_ledes';
        form.submit();
        return true;
    }

    function UpdateCobro(valor, accion)
    {
        var form = $('cambio_estado');
        var id = form.id_cobro.value;

        var http = getXMLHTTP();
        http.open('get', 'ajax.php?accion='+accion+'&id_cobro='+id+'&valor='+valor, false);
        http.onreadystatechange = function() {
            if(http.readyState == 4) {
                var response = http.responseText;
            }
        };
        http.send(null);
    }

    function ShowGastos(show)
    {
        var tr_gastos = $('tr_gastos');
        var btnEstado = $('btnEstado');

        if(show) {
            tr_gastos.style.display = 'inline';
            btnEstado.style.display = 'none';
        } else {
            tr_gastos.style.display = 'none';
            btnEstado.style.display = 'inline';
        }
    }

    function RevisarPagado(estado)
    {
<?php if (UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) { ?>
			return true;
<?php } ?>
        
        if (estado == 'PAGADO') {
            if ($("estado_original").value=='INCOBRABLE') {
                alert("<?php echo __('No puede pasar a estado \'Pagado\'. Debe pasar por estado \'Emitido\' para recuperar los montos, luego agregar el pago.') ?>");
                $("estado").selectedIndex = 5;
                return false;
            }

            if ($('existe_pago').value == 1 || jQuery('#todopagado').val()=='SI') {
                return true;
            }

            if ($("estado_original").value=='EMITIDO' || $("estado_original").value == 'ENVIADO AL CLIENTE') {
                AgregarPago();
            }
        }

        if (estado != 'PAGADO' && estado != 'EN REVISION' && $('estado_original').value == 'PAGADO' && $('hay_pagos').value == 'si') {
            $("estado").selectedIndex = 4;
            alert('<?php echo __("No se puede salir de estado PAGADO. Debe eliminar los Documentos de Pago del listado."); ?>');
        }
    }

    function AgregarPago() {
        var urlo = "ingresar_documento_pago.php?popup=1&pago=true&id_cobro=<?php echo $cobro->fields['id_cobro'] . "&codigo_cliente=" . $cobro->fields['codigo_cliente']; ?>";

        return nuevaVentana('Ingreso',730,600,urlo,'top=100, left=125, scrollbars=yes');
    }

    function EditarPago(id)	{
<?php echo "var urlo = \"ingresar_documento_pago.php?popup=1&pago=true&id_cobro=" . $cobro->fields['id_cobro'] . "&id_documento=\"+id+\"&codigo_cliente=" . $cobro->fields['codigo_cliente'] . "\";"; ?>
		return nuevaVentana('Ingreso',730,600,urlo,'top=100, left=125, scrollbars=yes');
	}

	function EliminaDocumento(id_documento)
	{
		var form = $('todo_cobro');
		if (parseInt(id_documento) > 0 && confirm('¿Desea eliminar el pago #' + id_documento + '?') == true) {
			self.location.href = 'cobros6.php?popup=1&id_cobro=' + <?php echo $id_cobro; ?> + '&id_documento_eliminado=' + id_documento + '&opc=eliminar_documento';
		}
	}

	function AgregarFactura(idx) 
	{
<?php
if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
	$cliente_factura = new Cliente($sesion);
	$codigo_cliente_secundario_factura = $cliente_factura->CodigoACodigoSecundario($cobro->fields['codigo_cliente']);
	$url_agregar_factura = "agregar_factura.php?popup=1&id_cobro=" . $id_cobro . "&codigo_cliente_secundario=" . $codigo_cliente_secundario_factura;
} else {
	$url_agregar_factura = "agregar_factura.php?popup=1&id_cobro=" . $id_cobro . "&codigo_cliente=" . $cobro->fields['codigo_cliente'];
}

$query = "SELECT id_documento_legal, codigo FROM prm_documento_legal";
$resp = mysql_query($query, $sesion->dbh) or  Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
$id_tipo_documento = array();

while (list($id, $codigo) = mysql_fetch_array($resp)) {
	$id_tipo_documento[$codigo] = $id;
}
?>

        var honorarios = jQuery('#honorarios_' + idx).val()*1;
        var gastos_con_impuestos = jQuery('#gastos_con_impuestos_' + idx).val()*1;
        var gastos_sin_impuestos = jQuery('#gastos_sin_impuestos_' + idx).val()*1; 

        var honorarios_disp = jQuery('#honorarios_disponibles').val()*1;
        var gastos_con_impuestos_disp = jQuery('#gastos_con_iva_disponibles').val()*1; 
        var gastos_sin_impuestos_disp = jQuery('#gastos_sin_iva_disponibles').val()*1; 

        var honorarios_total = jQuery('#honorarios_total').val()*1;  
        var gastos_con_impuestos_total =jQuery('#gastos_con_iva_total').val()*1;  
        var gastos_sin_impuestos_total =jQuery('#gastos_sin_iva_total').val()*1;   

        var esCredito = $F('tipo_documento_legal_'+idx) == <?php echo!empty($id_tipo_documento['NC']) ? $id_tipo_documento['NC'] : 0; ?>;
        var esFactura = $F('tipo_documento_legal_'+idx) == <?php echo!empty($id_tipo_documento['FA']) ? $id_tipo_documento['FA'] : 1; ?>;
        var esDebito = $F('tipo_documento_legal_'+idx) == <?php echo!empty($id_tipo_documento['ND']) ? $id_tipo_documento['ND'] : 0; ?>;

        if (!honorarios && !gastos_con_impuestos && !gastos_sin_impuestos || 
            honorarios < 0 || gastos_con_impuestos < 0 || gastos_sin_impuestos < 0) {
            alert('Ingrese montos válidos para el documento legal');
            return false;
        }
    
       
        if (!esCredito && 
            (honorarios > honorarios_disp || gastos_con_impuestos > gastos_con_impuestos_disp || gastos_sin_impuestos > gastos_sin_impuestos_disp)) {
       
            if (!confirm('<?php echo __("Los montos ingresados superan el saldo a facturar") ?>')) {
                if (honorarios > honorarios_disp) {
                    $('honorarios_' + idx).focus();
                } else if (gastos_con_impuestos > gastos_con_impuestos_disp) {
                    $('gastos_con_impuestos_' + idx).focus();
                } else if (gastos_sin_impuestos > gastos_sin_impuestos_disp) {
                    $('gastos_sin_impuestos_' + idx).focus();
                }
            
                return false;
            }
        }

        nuovaFinestra('Agregar_Factura', 730, 580, '<?php echo $url_agregar_factura; ?>' +
            '&honorario=' + honorarios +
            '&gastos_con_iva=' + gastos_con_impuestos +
            '&gastos_sin_iva=' + gastos_sin_impuestos +
            '&honorario_disp=' + honorarios_disp +
            '&gastos_con_impuestos_disp=' + gastos_con_impuestos_disp +
            '&gastos_sin_impuestos_disp=' + gastos_sin_impuestos_disp +
            '&honorario_total=' + honorarios_total +
            '&gastos_con_impuestos_total=' + gastos_con_impuestos_total +
            '&gastos_sin_impuestos_total=' + gastos_sin_impuestos_total +
            '&id_documento_legal=' + $F('tipo_documento_legal_' + idx), 'top=100, left=155');
    }

    function Numero(texto) {
        var coma = texto.indexOf(',');
        var punto = texto.indexOf('.');
    
        if (coma >= 0 && punto >= 0) {
            texto = texto.replace(coma < punto ? /,/g : /\./g, '');
        } else if (coma >= 0) {
            texto = texto.replace(/,/g, '.');
        }
    
        return Number(texto);
    }

    function UsarAdelanto(honorarios, gastos){
        nuevaVentana('Adelantos', 730, 470, 'lista_adelantos.php?popup=1&id_cobro=<?php echo $id_cobro; ?>' +
            '&codigo_cliente=<?php echo $cobro->fields['codigo_cliente'] ?>&elegir_para_pago=1&mantener_ventana=1'+
            (honorarios ? '&pago_honorarios=1' : '')+
            (gastos ? '&pago_gastos=1' : '')+
            '&id_contrato=<?php echo $cobro->fields['id_contrato']; ?>',
        'top=\'100\', left=\'125\', scrollbars=\'yes\'');
    
        return false;
    }
</script>
<br />
<?php
$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro'], array(), 0, true);
$query = "SELECT count(*) FROM factura_cobro WHERE id_cobro = '" . $cobro->fields['id_cobro'] . "'";
$resp = mysql_query($query, $sesion->dbh) or  Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
list($numero_facturas_asociados) = mysql_fetch_array($resp);

$existe_factura = ($numero_facturas_asociados > 0) ? 1 : 0;

$query = "SELECT count(*) FROM documento WHERE id_cobro = '" . $cobro->fields['id_cobro'] . "' AND tipo_doc != 'N' and monto!=0";
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
list($numero_documentos_pagos_asociados) = mysql_fetch_array($resp);

$existe_pago = ($numero_documentos_pagos_asociados > 0) ? 1 : 0;
?>

<!-- Estado del Cobro -->
<div id="colmask"  >
    <div id="colleft"  >
        <form name="todo_cobro" id='todo_cobro' method="post" action="">
            <input type="hidden" name="existe_factura" id="existe_factura" value="<?php echo $existe_factura ?>" />
            <input type="hidden" name="existe_pago" id="existe_pago" value="<?php echo $existe_pago ?>" />
            <input type="hidden" name="id_cobro" value="<?php echo $cobro->fields['id_cobro'] ?>" />
            <input type="hidden" name="id_factura_grabada" value = 0 />
            <input type="hidden" name="estado_original" id="estado_original" value="<?php echo $cobro->fields['estado'] ?>" />
            <input type="hidden" name="honorarios_pagados_original" value="<?php echo $cobro->fields['honorarios_pagados'] ?>" />
            <input type="hidden" name="gastos_pagados_original" value="<?php echo $cobro->fields['gastos_pagados'] ?>" />
            <input type="hidden" name="eliminar_pago" id="eliminar_pago" value="" />
            <input type="hidden" name="opc" id="opc"/>
            <input type="hidden" name="opc_informar_contabilidad" id="opc_informar_contabilidad"/>
<input type="hidden" name="todopagado" value="<?php echo ($documento_cobro->fields['honorarios_pagados'] == 'SI' && $documento_cobro->fields['honorarios_pagados'] == 'SI')?'SI':'NO';?>" id="todopagado">

            <div id="tablacabecera"  style="width: 950px;">
                <!-- Calendario DIV -->
                <div id="calendar-container" style="width:221px; position:absolute; display:none;">
                    <div class="floating" id="calendar"></div>
                </div>
                <!-- Fin calendario DIV -->
				<?php

				function TArriba($celda, $estado) {
					if (($celda == 'BORRADOR' && ($estado == 'CREADO' || $estado == 'EN REVISION')) || $celda == $estado) {
						return "<td rowspan = '3' style=\"text-align:center; vertical-align: middle; border: 1px solid #5bde5b; background: #fefeaa ; font-size: 12px;\"> " . $estado . " </td>";
					} else {
						return "<td> </td>";
					}
				}

				function TMedio($celda, $estado) {
					if (($celda == 'BORRADOR' && ($estado == 'CREADO' || $estado == 'EN REVISION')) || $celda == $estado) {
						return " ";
					} else {
						return "<td  style=\"text-align:center; border: 1px solid #9f9f9f; background: #efefef; font-size: 9px;\"> " . $celda . "</td>";
					}
				}

				function TAbajo($celda, $estado) {
					if (($celda == 'BORRADOR' && ($estado == 'CREADO' || $estado == 'EN REVISION')) || $celda == $estado) {
						return " ";
					} else {
						return "<td> </td>";
					}
				}
				?>
                <table id="estadoscobro">
                    <tr style="height: 26px;  vertical-align: middle;" >
                        <td style="height: 26px;  vertical-align: top;" align=left nowrap>
                            <br />
							<?php echo'Estado '. __('Cobro') ?>:
                          <br /> <br/><?php echo __('Forma Cobro') ?>
		      <br/><?php echo $cobro->fields['forma_cobro'] ?> 
                        </td>
                        <td align="left" style="font-size: 11px; font-weight: bold;" >
                            <table cellpadding="3">
                                <tr height=3>
									<?php echo TArriba("BORRADOR", $cobro->fields['estado']); ?>
									<?php echo TArriba("EMITIDO", $cobro->fields['estado']); ?>
									<?php 
									if (UtilesApp::GetConf($sesion, 'EnviarAlClienteAntesDeFacturar')) {
										echo TArriba("ENVIADO AL CLIENTE", $cobro->fields['estado']);
										echo TArriba("FACTURADO", $cobro->fields['estado']);
									} else {
										echo TArriba("FACTURADO", $cobro->fields['estado']);
										echo TArriba("ENVIADO AL CLIENTE", $cobro->fields['estado']);
									} 
									?>
									<?php echo TArriba("PAGO PARCIAL", $cobro->fields['estado']); ?>
									<?php echo TArriba("PAGADO", $cobro->fields['estado']); ?>
                                </tr>
                                <tr height=13>
									<?php echo TMedio("BORRADOR", $cobro->fields['estado']); ?>
									<?php echo TMedio("EMITIDO", $cobro->fields['estado']); ?>
									<?php 
									if (UtilesApp::GetConf($sesion, 'EnviarAlClienteAntesDeFacturar')) {
										echo TMedio("ENVIADO AL CLIENTE", $cobro->fields['estado']);
										echo TMedio("FACTURADO", $cobro->fields['estado']);
									} else {
										echo TMedio("FACTURADO", $cobro->fields['estado']);
										echo TMedio("ENVIADO AL CLIENTE", $cobro->fields['estado']);
									} 
									?>
									<?php echo TMedio("PAGO PARCIAL", $cobro->fields['estado']); ?>
									<?php echo TMedio("PAGADO", $cobro->fields['estado']); ?>
                                </tr>
                                <tr height=3>
									<?php echo TAbajo("BORRADOR", $cobro->fields['estado']); ?>
									<?php echo TAbajo("EMITIDO", $cobro->fields['estado']); ?>
									<?php 
									if (UtilesApp::GetConf($sesion, 'EnviarAlClienteAntesDeFacturar')) {
										echo TAbajo("ENVIADO AL CLIENTE", $cobro->fields['estado']);
										echo TAbajo("FACTURADO", $cobro->fields['estado']);
									} else {
										echo TAbajo("FACTURADO", $cobro->fields['estado']);
										echo TAbajo("ENVIADO AL CLIENTE", $cobro->fields['estado']);
									} 
									?>
									<?php echo TAbajo("PAGO PARCIAL", $cobro->fields['estado']); ?>
									<?php echo TAbajo("PAGADO", $cobro->fields['estado']); ?>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="text" value="<?php echo Utiles::sql2date($cobro->fields['fecha_creacion']) ?>" size="11" disabled />
                                    </td>
                                    <td nowrap>
                                        <input type="text" value="<?php echo Utiles::sql2date($cobro->fields['fecha_emision']) ?>" name="fecha_emision" id="fecha_emision" size="11" maxlength="10" />
                                        <img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_emision" style="cursor:pointer" />
                                    </td>
								<?php if (UtilesApp::GetConf($sesion, 'EnviarAlClienteAntesDeFacturar')) { ?>
                                    <td nowrap>
                                        <input type="text" name="fecha_envio" value="<?php echo Utiles::sql2date($cobro->fields['fecha_enviado_cliente']); ?>" id="fecha_envio" size="11" maxlength="10" />
                                        <img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_envio" style="cursor:pointer" />
                                    </td>
                                    <td nowrap>
                                        <input type="text" name="fecha_facturacion" value="<?php echo Utiles::sql2date($cobro->fields['fecha_facturacion']); ?>" id="fecha_facturacion" size="11" maxlength="10" />
                                        <img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_facturado" style="cursor:pointer" />
                                    </td>
								<?php } else { ?>
									<td nowrap>
                                        <input type="text" name="fecha_facturacion" value="<?php echo Utiles::sql2date($cobro->fields['fecha_facturacion']); ?>" id="fecha_facturacion" size="11" maxlength="10" />
                                        <img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_facturado" style="cursor:pointer" />
                                    </td>
                                    <td nowrap>
                                        <input type="text" name="fecha_envio" value="<?php echo Utiles::sql2date($cobro->fields['fecha_enviado_cliente']); ?>" id="fecha_envio" size="11" maxlength="10" />
                                        <img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_envio" style="cursor:pointer" />
                                    </td>
								<?php } ?>
                                    <td nowrap>
                                        <input type="text" name="fecha_pago_parcial" value="<?php echo Utiles::sql2date($cobro->fields['fecha_pago_parcial']) ?>" id="fecha_pago_parcial" size="11" maxlength="10" />
                                        <img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_pago_parcial" style="cursor:pointer" />
                                    </td>
                                    <td nowrap>
										<?php
										if ($cobro->fields['estado'] != 'PAGADO') {
											$fecha_pago = '';
										}
										if (Utiles::sql2date($cobro->fields['fecha_cobro'])) {
											$fecha_pago = Utiles::sql2date($cobro->fields['fecha_cobro']);
										} else if ($cobro->fields['estado'] == 'PAGADO') {
											$fecha_pago = Utiles::sql2date($documento_cobro->FechaPagos());
										}
										?>
                                        <input type="text" name="fecha_pago" value="<?php echo (substr($fecha_pago, 0, 2) != '00' ? $fecha_pago : "") ?>" id="fecha_pago" size="11" maxlength="10" />
                                        <img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_pago" style="cursor:pointer" />
                                    </td>
                                </tr>
                            </table>
                        </td>
                   
                        <td align=left style="vertical-align: middle;" colspan="2">
                            <div style="float:left;text-align:center;width:150px;">
								<?php echo __('Cambiar a') ?>:
								<?php echo Html::SelectQuery($sesion, "SELECT codigo_estado_cobro FROM prm_estado_cobro ORDER BY orden", 'estado', $cobro->fields['estado'], 'onchange="RevisarPagado(this.value);"', '', 150); ?>
                                <input type="hidden" id="estado_motivo" name="estado_motivo" />
                            </div>
							<?php if (UtilesApp::GetConf($sesion, 'InformarContabilidad')) { ?>
								<div style="float:left;">
									<input type="button" class="integracion" id="parainfo" value="<?php echo __('Informar a Contabilidad') ?>" rel="<?php echo $cobro->fields['estado_contabilidad'] ?>" />
									&nbsp;
									<input type="button" class="integracion" id="parainfoyfacturar" value="<?php echo __('Informar y Facturar') ?>" rel="<?php echo $cobro->fields['estado_contabilidad'] ?>" />&nbsp;&nbsp;
									<?php
									switch ($cobro->fields['estado_contabilidad']) {
										case 'NO INFORMADO':
											$estado_c = __('Sin informar');
											$titulo_c = __('El Cobro no ha sido informado.');
											break;
										case 'PARA INFORMAR':
											$estado_c = __('Para informar');
											$titulo_c = __('El Cobro se ha informado a Contabilidad');
											break;
										case 'PARA INFORMAR Y FACTURAR':
											$estado_c = __('Para informar y facturar');
											$titulo_c = __('El Cobro se ha informado a Contabilidad con la instrucción de facturar.');
											break;
										case 'INFORMADO':
											$estado_c = __('Informado');
											$titulo_c = __('El Cobro ha sido requerido por Contabilidad');
											break;
										default:
											$estado_c = __('Informado para Facturar');
											$titulo_c = __('El Cobro ha sido requerido por Contabilidad, se ha indicado que debe facturarse');
											break;
									}
									?>

									<div id="estado_contabilidad" style="display:inline-block; padding:3px; border:1px solid grey; background-color:#EFEFEF;" title="<?php echo $titulo_c ?>">
										<?php echo $estado_c; ?>
									</div>
									&nbsp;&nbsp;
									<div id="nota_venta_contabilidad" style="display: <?php echo ($cobro->fields['nota_venta_contabilidad']) ? 'inline-block' : 'none'; ?>; padding:3px; border:1px solid grey; background-color:#EFEFEF;">
										<?php echo __('Nota de Venta') ?>: <?php echo $cobro->fields['nota_venta_contabilidad']; ?>
									</div>
								</div>
							<?php
							} else {
								echo "&nbsp;";
							}
							?>
                        </td>
                    </tr>
                </table>
                <!-- Fin del Estado del cobro -->

                <!-- Fin Cabecera -->
              
								<table id="cajafacturas" >
								 
								</table>
						
                       </div>
<br class="clearfix"/>
<div id="historial">
                            <table cellspacing="0" cellpadding="3" style='width:670px;'>
                                <tr>
                                    <td width="220px"  >
                                        <span style="background:#EEE;padding:4px;display:block;font-weight: bold; font-size: 11px;"><?php echo __('Se está cobrando:') ?></span>
                                    
										<?php
										$se_esta_cobrando = __('Periodo');
										$se_esta_cobrando .=': ';
										if ($cobro->fields['fecha_ini'] != '0000-00-00') {
											$se_esta_cobrando_fecha_ini = Utiles::sql2date($cobro->fields['fecha_ini']);
											$se_esta_cobrando .=__('Desde') . ': ' . $se_esta_cobrando_fecha_ini . ' ';
										}
										if ($cobro->fields['fecha_fin'] != '0000-00-00') {
											$se_esta_cobrando_fecha_fin = Utiles::sql2date($cobro->fields['fecha_fin']);
											$se_esta_cobrando .=__('Hasta') . ': ' . $se_esta_cobrando_fecha_fin;
										}

										if ($cobro->fields['se_esta_cobrando']) {
											$se_esta_cobrando = $cobro->fields['se_esta_cobrando'];
										}

										if (UtilesApp::GetConf($sesion, 'SeEstaCobrandoEspecial')) {
											$lineas = 'rows="6"';
											$columnas = 'cols="25"';
										} else {
											$lineas = 'rows="3"';
											$columnas = "";
										}
										?>
                                        <textarea style="width:220px;" name="se_esta_cobrando" <?php echo $lineas . ' ' . $columnas; ?> id="se_esta_cobrando"><?php echo $se_esta_cobrando; ?></textarea>
                                    </td>
                             <td style="text-align:center;">
                         <?php if (!UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) { ?>
						
								<table     cellspacing="0" cellpadding="2" style="text-align:center;margin:auto; border: 0px solid #bfbfcf;">
									<tr id="nofac1row">
										
										<td align="center" id="Ver Factura">
	<?php if (!UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) { ?>
												<table border="0" cellspacing="0" cellpadding="2" style="border: 1px solid #bfbfcf;" width="220" height=100>
													<tr style="height: 26px;">
														<td align="left" bgcolor="#dfdfdf" style="font-size: 11px; font-weight: bold; vertical-align: middle;" colspan=3>
															<img src="<?php echo Conf::ImgDir() ?>/imprimir_16.gif" border="0" alt="Imprimir"/> <?php echo __('Ver Factura') ?>
														</td>
													</tr>
													<tr>
														<td align="left" colspan=3>
													<?php echo __('Facturado') ?>: <input type="checkbox" name=facturado id=facturado value=1 <?php echo $cobro->fields['facturado'] ? 'checked' : '' ?> >
														</td>
													</tr>
															<?php if (UtilesApp::GetConf($sesion, 'NotaCobroExtra')) { ?>
														<tr>
															<td align="left" colspan=3>
														<?php echo __('Nota Cobro') ?>: <input name='nota_cobro' size='5' value='<?php echo $cobro->fields['nota_cobro'] ?>'>
															</td>
														</tr>
															<?php } ?>
													<tr>
														<td align="left" colspan=3>
															<?php if (UtilesApp::GetConf($sesion, 'UsaNumeracionAutomatica')) { ?>
																<?php echo __('Factura N°') ?>: <input name='documento' size='5' value='<?php echo $cobro->fields['documento'] ?>'>
																<?php if (UtilesApp::GetConf($sesion, 'PermitirFactura') && !empty($id_factura)) { ?>
																	<a href='javascript:void(0)' onclick="nuovaFinestra('Editar_Factura',730,580,'agregar_factura.php?id_factura=<?php echo $id_factura ?>&popup=1', 'top=100, left=155');" ><img src='<?php echo Conf::ImgDir() ?>/editar_on.gif' border=0 title=Editar></a>
																	<?php } ?>
																<?php } else { ?>
																<?php echo __('Factura N°') ?>: <input name='documento' size='5' value='<?php echo $cobro->fields['documento'] ?>'>
																<?php if (UtilesApp::GetConf($sesion, 'PermitirFactura') && !empty($id_factura)) { ?>
																	<a href='javascript:void(0)' onclick="nuovaFinestra('Editar_Factura',730,580,'agregar_factura.php?id_factura=<?php echo $id_factura ?>&popup=1', 'top=100, left=155');" ><img src='<?php echo Conf::ImgDir() ?>/editar_on.gif' border=0 title=Editar></a>
			<?php } ?>
													<?php } ?>
														</td>
													</tr>
		<?php if (!$cobro->TieneFacturaActivoAsociado() && UtilesApp::GetConf($sesion, 'PermitirFactura')) { ?>
														<tr>
															<td align="center" colspan=3>
																<a href='#' onclick="ValidarFactura(this.form,'','generar');" >Generar Factura</a>
															</td>
														</tr>
		<?php } else if (UtilesApp::GetConf($sesion, 'PermitirFactura')) { ?>
														<tr>
															<td align="center" colspan=3>
																<input type="button" class="btn" value="<?php echo __('Descargar'); ?>" onclick="ValidarFactura(this.form,<?php echo $id_factura ?>,'imprimir');">
																<?php if ($factura->fields['anulado'] == 0) { ?>
																	<input type="button" class="btn" value="<?php echo __('Anular Factura') ?>" onclick="AnularFactura(this.form,'anular');">
														<?php } ?>
															</td>
														</tr>
												<?php } ?>
												</table>
	<?php } ?>
										</td>
									</tr>                                                     
								</table>
<?php } ?>
							 <br /><br /><div id="retorno" style="display:none;" >Caja Respuesta</div>
                            <a style="margin:auto;display:block;" href="#" id="enviar" class="btn botonizame" icon="ui-icon-save"  setwidth="220" onclick="ValidarTodo(jQuery(this).closest('form').get(0)); "><?php echo __('Guardar Cambios') ?></a>
							 </td>
                      </tr>
                              <tr>
                        <!-- Submit -->
                        <td   colspan="2"   ><div style=" clear:both;margin:auto;">
    <iframe src="historial_cobro.php?id_cobro=<?php echo $id_cobro ?>&popup=1"  style="width:100%;height:450px;border: none;" frameborder=0></iframe>
</div></td>
                    </tr>
                </table>
                <!-- Fin FORM unica -->
            </div> <!-- FIN TABLA CABECERA (la que tiene el avance del cobro, la lista de facturas, etc) -->

            <div id="cobro6colderecha">
                <!-- Imprimir -->
                <table border="0" cellspacing="0" cellpadding="2" style="border: 1px solid #bfbfcf;width:100%;height:100px;">
                    <tr>
                        <td align="left" style="font-size: 11px; font-weight: bold; vertical-align: middle;">
                            <input type="hidden" name="guardar_opciones" value="1" />
                            <img src="<?php echo Conf::ImgDir() ?>/imprimir_16.gif" border="0" alt="Imprimir"/> <?php echo __('Imprimir') ?>
                        </td>
                        <td align="right"  style="vertical-align: middle;">
                            <a href="javascript:void(0);" style="color: #990000; font-size: 9px; font-weight: normal;" onclick="ToggleDiv('doc_opciones');"><?php echo __('opciones') ?></a>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2">
                            <div id="doc_opciones" style="display: none; position: relative;">
                                <table border="0" cellspacing="0" cellpadding="2" style="font-size: 10px;">
                                    <tr>
                                        <td colspan="2">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_asuntos_separados" id="opc_ver_asuntos_separados" value="1" <?php echo $cobro->fields['opc_ver_asuntos_separados'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_asuntos_separados"><?php echo __('Ver asuntos por separado') ?></label></td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_resumen_cobro" id="opc_ver_resumen_cobro" value="1" <?php echo $cobro->fields['opc_ver_resumen_cobro'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_resumen_cobro"><?php echo __('Mostrar resumen del cobro') ?></label></td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_modalidad" id="opc_ver_modalidad" value="1" <?php echo $cobro->fields['opc_ver_modalidad'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_modalidad"><?php echo __('Mostrar modalidad del cobro') ?></label></td>
                                    </tr>
									<?php
									if ($cobro->fields['opc_ver_profesional'] == 1) {
										$display_detalle_profesional = "style='display: table_row;'";
									} else {
										$display_detalle_profesional = "style='display: none;'";
									}
									?>
                                    <tr>
                                        <td align="right">
                                            <input type="checkbox" name="opc_ver_profesional" id="opc_ver_profesional" value="1" <?php echo $cobro->fields['opc_ver_profesional'] == '1' ? 'checked' : '' ?> onchange="showOpcionDetalle( this.id, 'tr_detalle_profesional');">
                                        </td>
                                        <td align="left" style="font-size: 10px;">
                                            <label for="opc_ver_profesional"><?php echo __('Mostrar detalle por profesional') ?></label>
                                        </td>
                                    </tr>
                                    <tr id="tr_detalle_profesional" <?php echo $display_detalle_profesional ?> >
                                        <td />
                                        <td align="left" colspan="2" style="font-size: 10px;">
                                            <table width="100%">
                                                <tr>
                                                    <td width="40%" align="left">
                                                        <input type="checkbox" name="opc_ver_profesional_iniciales" id="opc_ver_profesional_iniciales" value="1" <?php echo $cobro->fields['opc_ver_profesional_iniciales'] == '1' ? 'checked' : '' ?>>
                                                        <label for="opc_ver_profesional_iniciales"><?php echo __('Iniciales') ?></label>
                                                    </td>
                                                    <td width="60%" align="left">
                                                        <input type="checkbox" name="opc_ver_profesional_categoria" id="opc_ver_profesional_categoria" value="1" <?php echo $cobro->fields['opc_ver_profesional_categoria'] == '1' ? 'checked' : '' ?>>
                                                        <label for="opc_ver_profesional_categoria"><?php echo __('Categoría') ?></label>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="40%" align="left">
                                                        <input type="checkbox" name="opc_ver_profesional_tarifa" id="opc_ver_profesional_tarifa" value="1" <?php echo $cobro->fields['opc_ver_profesional_tarifa'] == '1' ? 'checked' : '' ?>>
                                                        <label for="opc_ver_profesional_tarifa"><?php echo __('Tarifa') ?></label>
                                                    </td>
                                                    <td width="60%" align="left">
                                                        <input type="checkbox" name="opc_ver_profesional_importe" id="opc_ver_profesional_importe" value="1" <?php echo $cobro->fields['opc_ver_profesional_importe'] == '1' ? 'checked' : '' ?>>
                                                        <label for="opc_ver_profesional_importe"><?php echo __('Importe') ?></label>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
									<?php
									if ($cobro->fields['opc_ver_detalles_por_hora'] == 1) {
										$display_detalle_por_hora = "style='display: table-row;'";
									} else {
										$display_detalle_por_hora = "style='display: none;'";
									}
									?>
                                    <tr>
                                        <td align="right">
                                            <input type="checkbox" name="opc_ver_detalles_por_hora" id="opc_ver_detalles_por_hora" value="1" <?php echo $cobro->fields['opc_ver_detalles_por_hora'] == '1' ? 'checked' : '' ?> onchange="showOpcionDetalle( this.id, 'tr_detalle_por_hora');">
                                        </td>
                                        <td align="left" colspan="2" style="font-size: 10px;">
                                            <label for="opc_ver_detalles_por_hora"><?php echo __('Mostrar detalle por hora') ?></label>
                                        </td>
                                    </tr>
                                    <tr id="tr_detalle_por_hora" <?php echo $display_detalle_por_hora ?> >
                                        <td/>
                                        <td align="left" colspan="2" style="font-size: 10px;">
                                            <table width="100%">
                                                <tr>
                                                    <td width="40%" align="left">																	
                                                        <input type="checkbox" name="opc_ver_detalles_por_hora_iniciales" id="opc_ver_detalles_por_hora_iniciales" value="1" <?php echo $cobro->fields['opc_ver_detalles_por_hora_iniciales'] == '1' ? 'checked' : '' ?>>
                                                        <label for="opc_ver_detalles_por_hora_iniciales"><?php echo __('Iniciales') ?></label>
                                                    </td>
                                                    <td width="60%" align="left">
                                                        <input type="checkbox" name="opc_ver_detalles_por_hora_categoria" id="opc_ver_detalles_por_hora_categoria" value="1" <?php echo $cobro->fields['opc_ver_detalles_por_hora_categoria'] == '1' ? 'checked' : '' ?>>
                                                        <label for="opc_ver_detalles_por_hora_categoria"><?php echo __('Categoría') ?></label>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="40%" align="left">
                                                        <input type="checkbox" name="opc_ver_detalles_por_hora_tarifa" id="opc_ver_detalles_por_hora_tarifa" value="1" <?php echo $cobro->fields['opc_ver_detalles_por_hora_tarifa'] == '1' ? 'checked' : '' ?>>
                                                        <label for="opc_ver_detalles_por_hora_tarifa"><?php echo __('Tarifa') ?></label>
                                                    </td>
                                                    <td width="60%" align="left">
                                                        <input type="checkbox" name="opc_ver_detalles_por_hora_importe" id="opc_ver_detalles_por_hora_importe" value="1" <?php echo $cobro->fields['opc_ver_detalles_por_hora_importe'] == '1' ? 'checked' : '' ?>>
                                                        <label for="opc_ver_detalles_por_hora_importe"><?php echo __('Importe') ?></label>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_gastos" id="opc_ver_gastos" value="1" <?php echo $cobro->fields['opc_ver_gastos'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_gastos"><?php echo __('Mostrar gastos del cobro') ?></label></td>
                                    </tr>
<?php if (UtilesApp::GetConf($sesion, 'PrmGastos')) { ?>
										<tr>
											<td align="right"><input type="checkbox" name="opc_ver_concepto_gastos" id="opc_ver_concepto_gastos" value="1" <?php echo $cobro->fields['opc_ver_concepto_gastos'] == '1' ? 'checked' : '' ?>></td>
											<td align="left" style="font-size: 10px;"><label for="opc_ver_concepto_gastos"><?php echo __('Mostrar concepto de gastos') ?></label></td>
										</tr>
<?php } ?>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_morosidad" id="opc_ver_morosidad" value="1" <?php echo $cobro->fields['opc_ver_morosidad'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_morosidad"><?php echo __('Mostrar saldo adeudado') ?></label></td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_tipo_cambio" id="opc_ver_tipo_cambio" value="1" <?php echo $cobro->fields['opc_ver_tipo_cambio'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_tipo_cambio"><?php echo __('Mostrar tipos de cambio') ?></label></td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_descuento" id="opc_ver_descuento" value="1" <?php echo $cobro->fields['opc_ver_descuento'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_descuento"><?php echo __('Mostrar el descuento del cobro') ?></label></td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_numpag" id="opc_ver_numpag" value="1" <?php echo $cobro->fields['opc_ver_numpag'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_numpag"><?php echo __('Mostrar números de página') ?></label></td>
                                    </tr>
                                    <tr>        
                                        <td align="right"><input type="checkbox" name="opc_ver_columna_cobrable" id="opc_ver_columna_cobrable" value="1" <?php echo $cobro->fields['opc_ver_columna_cobrable'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_numpag"><?php echo __('Mostrar columna cobrable') ?></label></td>
                                    </tr>
									<?php
									if (UtilesApp::GetConf($sesion, 'OrdenadoPor')) {
										$solicitante = UtilesApp::GetConf($sesion, 'OrdenadoPor');
									} else {
										$solicitante = 2;
									}

									if ($solicitante == 0) {  // no mostrar
										echo '<input type="hidden" name="opc_ver_solicitante" id="opc_ver_solicitante" value="0" />';
									} else if ($solicitante == 1) { // obligatorio
										?>
										<tr>
											<td align="right"><input type="checkbox" name="opc_ver_solicitante" id="opc_ver_solicitante" value="1" <?php echo $cobro->fields['opc_ver_solicitante'] == '1' ? 'checked="checked"' : '' ?>></td>
											<td align="left" style="font-size: 10px;"><label for="opc_ver_solicitante"><?php echo __('Mostrar solicitante') ?></label></td>
										</tr>
<?php } else if ($solicitante == 2) { // opcional   ?>
										<tr>
											<td align="right"><input type="checkbox" name="opc_ver_solicitante" id="opc_ver_solicitante" value="1" <?php echo $cobro->fields['opc_ver_solicitante'] == '1' ? 'checked="checked"' : '' ?>></td>
											<td align="left" style="font-size: 10px;"><label for="opc_ver_solicitante"><?php echo __('Mostrar solicitante') ?></label></td>
										</tr>
<?php } ?>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_horas_trabajadas" id="opc_ver_horas_trabajadas" value="1" <?php echo $cobro->fields['opc_ver_horas_trabajadas'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_horas_trabajadas"><?php echo __('Mostrar horas trabajadas') ?></label></td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_cobrable" id="opc_ver_cobrable" value="1" <?php echo $cobro->fields['opc_ver_cobrable'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_cobrable"><?php echo __('Mostrar trabajos no visibles') ?></label></td>
                                    </tr>
<?php if (UtilesApp::GetConf($sesion, 'ResumenProfesionalVial')) { ?>
										<tr>
											<td align="right"><input type="checkbox" name="opc_restar_retainer" id="opc_restar_retainer" value="1" onclick="ActivaCarta(this.checked)" <?php echo $cobro->fields['opc_restar_retainer'] == '1' ? 'checked="checked"' : '' ?>></td>
											<td align="left" style="font-size: 10px;"><label for="opc_restar_retainer"><?php echo __('Restar valor retainer') ?></label></td>
										</tr>
										<tr>
											<td align="right"><input type="checkbox" name="opc_ver_detalle_retainer" id="opc_ver_detalle_retainer" value="1" onclick="ActivaCarta(this.checked)" <?php echo $cobro->fields['opc_ver_detalle_retainer'] == '1' ? 'checked="checked"' : '' ?>></td>
											<td align="left" style="font-size: 10px;"><label for="opc_ver_detalle_retainer"><?php echo __('Mostrar detalle retainer') ?></label></td>
										</tr>
<?php } ?>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_valor_hh_flat_fee" id="opc_ver_valor_hh_flat_fee" value="1"  <?php echo $cobro->fields['opc_ver_valor_hh_flat_fee'] == '1' ? 'checked="checked"' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_valor_hh_flat_fee"><?php echo __('Mostrar valor HH en caso de flat fee') ?></label></td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="modalidad_calculo" id="modalidad_calculo" value="1"  <?php echo $cobro->fields['modalidad_calculo'] == '1' ? 'checked="checked"' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="modalidad_calculo" title="Activa etiquetas avanzadas (adelantos, pagos, hitos)"><?php echo  __('Desglose Extendido'); ?></label></td>
                                    </tr>
                                    <tr>
                                        <td align="right"><input type="checkbox" name="opc_ver_carta" id="opc_ver_carta" value="1" onclick="ActivaCarta(this.checked)" <?php echo $cobro->fields['opc_ver_carta'] == '1' ? 'checked' : '' ?>></td>
                                        <td align="left" style="font-size: 10px;"><label for="opc_ver_carta"><?php echo __('Mostrar Carta') ?></label></td>
                                    </tr>
                                    <tr>
                                        <td align="right">&nbsp;</td>
                                        <td align="left" style="font-size: 10px;">
<?php echo __('Formato de Carta Cobro') ?>:
<?php echo Html::SelectQuery($sesion, "SELECT carta.id_carta, carta.descripcion FROM carta ORDER BY id_carta", "id_carta", $cobro->fields['id_carta'] ? $cobro->fields['id_carta'] : $contrato->fields['id_carta'], ($cobro->fields['opc_ver_carta'] == '1' ? '' : 'disabled') . ' class="wide"'); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">&nbsp;</td>
                                        <td align="left" style="font-size: 10px;">
<?php echo __('Formato Detalle Carta Cobro') ?>:
<?php echo Html::SelectQuery($sesion, "SELECT cobro_rtf.id_formato, cobro_rtf.descripcion FROM cobro_rtf ORDER BY cobro_rtf.id_formato", "id_formato", $cobro->fields['id_formato'] ? $cobro->fields['id_formato'] : $contrato->fields['id_formato'], 'class="wide"', 'Seleccione'); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">&nbsp;</td>
                                        <td align="left" style="font-size: 10px;">
											<?php echo __('Tamaño del papel') ?>:
											<?php
											if ($cobro->fields['opc_papel'] == '' && UtilesApp::GetConf($sesion, 'PapelPorDefecto')) {
												$cobro->fields['opc_papel'] = UtilesApp::GetConf($sesion, 'PapelPorDefecto');
											}
											?>
                                            <select name="opc_papel">
                                                <option value="LETTER" <?php echo $cobro->fields['opc_papel'] == 'LETTER' ? 'selected="selected"' : '' ?>><?php echo __('Carta'); ?></option>
                                                <option value="LEGAL" <?php echo $cobro->fields['opc_papel'] == 'LEGAL' ? 'selected="selected"' : '' ?>><?php echo __('Oficio'); ?></option>
                                                <option value="A4" <?php echo $cobro->fields['opc_papel'] == 'A4' ? 'selected="selected"' : '' ?>><?php echo __('A4'); ?></option>
                                                <option value="A5" <?php echo $cobro->fields['opc_papel'] == 'A5' ? 'selected="selected"' : '' ?>><?php echo __('A5'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">&nbsp;</td>
                                        <td align="left" style="font-size: 10px;">
<?php echo __('Mostrar total en') ?>:
<?php echo Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", 'opc_moneda_total_view', $cobro->fields['opc_moneda_total'] != '' ? $cobro->fields['opc_moneda_total'] : '1', 'disabled', '', '60') ?>
                                            <input type="hidden" name="opc_moneda_total" id="opc_moneda_total" value="<?php echo $cobro->fields['opc_moneda_total'] ? $cobro->fields['opc_moneda_total'] : '1' ?>">
                                            <input type="hidden" name="tipo_cambio_moneda_total" id="tipo_cambio_moneda_total" value="<?php echo $moneda_total->fields['tipo_cambio'] ?>">
                                            <input type="hidden" name="cifras_decimales_moneda_total" id="cifras_decimales_moneda_total" value="<?php echo $moneda_total->fields['cifras_decimales'] ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="right">&nbsp;</td>
                                        <td align="left" style="font-size: 10px;">
<?php echo __('Tipo de cambio') ?>: <input type="text" name="opc_moneda_total_tipo_cambio_view" disabled value="<?php echo $tipo_cambio_moneda_total > 0 ? $tipo_cambio_moneda_total : $moneda_base->fields['tipo_cambio'] ?>" size="8">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">&nbsp;</td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" colspan="2">
							<?php echo __('Idioma') ?>: <?php echo Html::SelectQuery($sesion, "SELECT codigo_idioma,glosa_idioma FROM prm_idioma ORDER BY glosa_idioma", "lang", $cobro->fields['codigo_idioma'] != '' ? $cobro->fields['codigo_idioma'] : $contrato->fields['codigo_idioma'], '', '', 80); ?>
                            <br />
                                                        <br />    <a class="btn botonizame" icon="ui-icon-doc" setwidth="185" onclick="return VerDetalles(jQuery('#todo_cobro').get(0));" ><?php echo __('Descargar Archivo') ?> Word</a>
<?php if (UtilesApp::GetConf($sesion, 'MostrarBotonCobroPDF')) { ?>
								<br class="clearfix vpx" /><a class="btn botonizame"  icon="ui-icon-pdf"  setwidth="185" onclick="return VerDetallesPDF(jQuery('#todo_cobro').get(0));"><?php echo __('Descargar Archivo') ?> PDF</a>
<?php }  
if (!UtilesApp::GetConf($sesion, 'EsconderExcelCobroModificable')) { ?>
								<br class="clearfix vpx"/><a class="btn botonizame" icon="xls" setwidth="185" onclick="return DescargarExcel(jQuery('#todo_cobro').get(0)); "><?php echo __('descargar_excel_modificable') ?></a>
<?php }  
if (UtilesApp::GetConf($sesion, 'ExcelRentabilidadFlatFee')) { ?>
								<br class="clearfix vpx" /><a class="btn botonizame" icon="xls" setwidth="185" onclick="return DescargarExcel(jQuery('#todo_cobro').get(0), 'rentabilidad'); "><?php echo __('Excel rentabilidad') ?> </a>							
<?php } 
if (UtilesApp::GetConf($sesion, 'XLSFormatoEspecial') != '' && UtilesApp::GetConf($sesion, 'XLSFormatoEspecial') != 'cobros_xls.php') { ?>
                                                             <br class="clearfix vpx" /><a class="btn botonizame" icon="xls" setwidth="185" onclick="return DescargarExcel(jQuery('#todo_cobro').get(0), 'especial');"><?php echo __('Descargar Excel Cobro') ?></a>
<?php } 
if (UtilesApp::GetConf($sesion, 'ExportacionLedes')) { ?>
								 <br class="clearfix vpx" /><a class="btn botonizame"   setwidth="185" onclick="return DescargarLedes(jQuery('#todo_cobro').get(0));"><?php echo __('Descargar LEDES') ?> </a>
							<?php } ?>
                        </td>
                    </tr>
                </table>
                <!-- fin Imprimir -->

                <br />
                <!-- Envío del Cobro -->
                <table  border="0" cellspacing="0" cellpadding="2" style="height:100px; width:100%; border: 1px solid #bfbfcf;">
                    <tr style="height: 26px;">
                        <td align="left" colspan=2 bgcolor="#dfdfdf" style="font-size: 11px; font-weight: bold; vertical-align: middle;">
                            <img src="<?php echo Conf::ImgDir() ?>/cobro.png" border="0" alt="Imprimir"/> <?php echo __('Envío al Cliente') ?>
                        </td>
                    </tr>
                    <tr>
                        <td align=left><?php echo __('Forma Envío') ?>:</td>
                        <td align=left>
                            <select name="forma_envio" id="forma_envio" style='width: 80px;'>
                                <option value='CARTA' <?php echo $cobro->fields['forma_envio'] == 'CARTA' ? 'selected' : '' ?> > Carta </option>
                                <option value='E-MAIL' <?php echo $cobro->fields['forma_envio'] == 'E-MAIL' ? 'selected' : '' ?> > E-mail </option>
                                <option value='OTRA' <?php echo $cobro->fields['forma_envio'] == 'OTRA' ? 'selected' : '' ?> > Otra </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td colspan=2 align=left><?php echo __('Email Cliente') ?>:</td>
                    </tr>
                    <tr>
                        <td colspan=2 align=right>
                            <input type="text" name="email_cliente" id="email_cliente" style="width:98%;" maxlength="50" value = <?php echo $contrato->fields['email_contacto'] ?> />
                        </td>
                    </tr>
                </table>
                <br />
                <!-- fin Envío del Cobro -->
                <input type="hidden" id="elidcobro" value="<?php echo $cobro->fields['id_cobro']; ?>" />
                <table border="0" cellspacing="0" cellpadding="2" style="border: 1px solid #bfbfcf;margin-top:10px;width:100%">
                    <tbody id="lista_pagos"></tbody>
                </table>
                <!-- fin Pago -->
            </div>
        </form>
    </div>
</div>

 


<div id="TipoCambioDocumento" style="display:none; left: 100px; top: 300px; background-color: white; position:absolute; z-index: 4;">
    <fieldset style="background-color:white;">
        <legend><?php echo __('Tipo de Cambio') ?></legend>
        <div id="contenedor_tipo_cambio">
            <div style="padding-top:5px; padding-bottom:5px;">&nbsp;<img src="<?php echo Conf::ImgDir() ?>/alerta_16.gif" title="Alerta" />&nbsp;&nbsp;<?php echo __('Este tipo de cambio sólo afecta al Documento de Cobro en los Reportes. No modifica la Carta de Cobro.') ?></div>
            <table style='border-collapse:collapse;'  cellpadding='3'>
				<?php
				$query = "SELECT id_documento, prm_moneda.id_moneda, glosa_moneda, documento_moneda.tipo_cambio
            FROM documento_moneda
            JOIN prm_moneda ON documento_moneda.id_moneda = prm_moneda.id_moneda
            WHERE id_documento = '" . $documento_cobro->fields['id_documento'] . "'";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
				$num_monedas = 0;
				$ids_monedas = array();
				while (list($id_documento, $id_moneda, $glosa_moneda, $tipo_cambio) = mysql_fetch_array($resp)) {
					?>
					<td>
						<span><b><?php echo $glosa_moneda ?></b></span><br>
						<input type='text' size=9 id='documento_moneda_<?php echo $id_moneda ?>' name='documento_moneda_<?php echo $id_moneda ?>' value='<?php echo $tipo_cambio ?>' />
					</td>
					<?php
					$num_monedas++;
					$ids_monedas[] = $id_moneda;
				}
				?>
                <tr>
                    <td colspan=<?php echo $num_monedas ?> align=center>
                        <input type="button" onclick="ActualizarDocumentoMoneda($('todo_cobro'))" value="<?php echo __('Guardar') ?>" />
                        <input type="button" onclick="CancelarDocumentoMoneda()" value="<?php echo __('Cancelar') ?>" />
                        <input type=hidden id="ids_monedas_documento" value="<?php echo implode(',', $ids_monedas) ?>"/>
                    </td>
                </tr>
            </table>
        </div>
    </fieldset>
</div>

<div id="div_motivo_cambio_estado" style="display:none">
    <center>
        <textarea style="width:98%;height:80px;margin-top:20px;" id="text_motivo_cambio_estado" name="text_motivo_cambio_estado" placeholder="Por favor ingrese el motivo para cambiar el estado de 'PAGADO' a 'EN REVISIÓN'"></textarea>
        <button onclick="jQuery('#estado_motivo').val(jQuery('[name=\'text_motivo_cambio_estado\']')[1].value);jQuery('#enviar').click()" >Guardar</button>
    </center>
</div>

<!-- Fin Tipo Cambio -->
<script type="text/javascript">
    Calendar.setup({
        inputField  : "fecha_pago",     // ID of the input field
        ifFormat    : "%d-%m-%Y",       // the date format
        button      : "img_fecha_pago"  // ID of the button
    });
    
    Calendar.setup({
        inputField  : "fecha_emision",      // ID of the input field
        ifFormat    : "%d-%m-%Y",           // the date format
        button      : "img_fecha_emision"   // ID of the button
    });
    
    Calendar.setup({
        inputField  : "fecha_facturacion",  // ID of the input field
        ifFormat    : "%d-%m-%Y",           // the date format
        button      : "img_fecha_facturado" // ID of the button
    });
    
    Calendar.setup({
        inputField  : "fecha_envio",    // ID of the input field
        ifFormat    : "%d-%m-%Y",       // the date format
        button      : "img_fecha_envio" // ID of the button
    });
    
    Calendar.setup({
        inputField  : "fecha_pago_parcial",     // ID of the input field
        ifFormat    : "%d-%m-%Y",               // the date format
        button      : "img_fecha_pago_parcial"  // ID of the button
    });

<?php if ($cobro->fields['estado'] == "PAGADO" && UtilesApp::GetConf($sesion, "ObservacionReversarCobroPagado")) { ?>
		$("estado").value == "EN REVISION" ? $("estado_motivo").show() : $("estado_motivo").hide();
		$("estado").observe("change", function() {
			this.value == "EN REVISION" ? $("estado_motivo").show() : $("estado_motivo").hide();
		});
<?php } ?>
</script>
<?php
$pagina->PrintBottom($popup);


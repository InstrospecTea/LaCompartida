<?php
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/classes/PaginaCobro.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
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

$cobro = new Cobro($sesion);
$contrato = new Contrato($sesion);
$documento_cobro = new Documento($sesion);
$factura = new Factura($sesion);
$idioma = new Objeto($sesion, '', '', 'prm_idioma', 'codigo_idioma');
$factura_pago = new FacturaPago($sesion);
$id_cobro=$_POST['id_cobro'];
$opc=$_POST['opc'];
$confirma=$_POST['confirma'];
$opc_informar_contabilidad=$_POST['opc_ic'];

if (!$cobro->Load($id_cobro))
	$pagina->FatalError(__('Cobro inválido'));


if ($opc=='refrescar') {
switch($cobro->fields['estado_contabilidad']):
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
    case 'INFORMADO Y FACTURADO':
        $estado_c = __('Informado y Facturado');
        $titulo_c = __('El Cobro ha sido requerido por Contabilidad, se confirma que debe facturarse');
    break;
    default:
        $estado_c=$cobro->fields['estado_contabilidad'];
endswitch;
echo $estado_c.'|'.$titulo_c.'|'.$cobro->fields['estado_contabilidad'].'|Estado cobro refrescado|'.$cobro->fields['nota_venta_contabilidad'];




} else if ($opc == 'guardar' && ($opc_informar_contabilidad == 'parainfo' OR $opc_informar_contabilidad == 'parainfoyfacturar')) {

	if($cobro->fields['estado_contabilidad']=='INFORMADO' && $opc_informar_contabilidad == 'parainfo' && $confirma==0) {
            $mensajeinterno='Se ignora la orden porque el webservice cambia el estado a INFORMADO';  
        } else if($cobro->fields['estado_contabilidad']=='INFORMADO Y FACTURADO' && $opc_informar_contabilidad == 'parainfoyfacturar' && $confirma==0) {
              $mensajeinterno='Se ignora la orden porque el webservice cambia el estado a INFORMADO Y FACTURADO';
        } else if ($cobro->fields['estado_contabilidad']=='PARA INFORMAR' && $opc_informar_contabilidad == 'parainfo' &&  $confirma==0) {
             $mensajeinterno='Nada cambia';
        } else if ($cobro->fields['estado_contabilidad']=='PARA INFORMAR Y FACTURAR' && $opc_informar_contabilidad == 'parainfoyfacturar' && $confirma==0) {
             $mensajeinterno='Nada cambia';
        } else {
                if($opc_informar_contabilidad == 'parainfo') {
                        $cobro->Edit('estado_contabilidad','PARA INFORMAR');
                } else if($opc_informar_contabilidad == 'parainfoyfacturar') {
			$cobro->Edit('estado_contabilidad','PARA INFORMAR Y FACTURAR');
                }
                
	$cobro->Write();
        $mensajeinterno='Cambio Realizado';
        }
   switch($cobro->fields['estado_contabilidad']):
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
    case 'INFORMADO Y FACTURADO':
        $estado_c = __('Informado para Facturar');
        $titulo_c = __('El Cobro ha sido requerido por Contabilidad, se ha indicado que debe facturarse');
    break;
    default:
        $estado_c=$cobro->fields['estado_contabilidad'];
endswitch;
echo $estado_c.'|'.$titulo_c.'|'.$cobro->fields['estado_contabilidad'].'|'.$mensajeinterno.'|'.$cobro->fields['nota_venta_contabilidad'];

        
}


die();












if ($opc == 'eliminar_pago') {
	if ($eliminar_pago > 0) {
		$factura_pago->Load($eliminar_pago);
		if ($factura_pago->Eliminar())
			$pagina->addInfo(__('Pago borrado con éxito'));
	}
}



if ($opc == "eliminar_documento") {
	$documento_eliminado = new Documento($sesion);
	$documento_eliminado->Load($id_documento_eliminado);

	if (empty($documento_eliminado->fields['es_adelanto'])) {
		$documento_eliminado->EliminarNeteos();
		$query_p = "DELETE from cta_corriente WHERE cta_corriente.documento_pago = '" . $id_documento_eliminado . "' ";
		mysql_query($query_p, $sesion->dbh) or Utiles::errorSQL($query_p, __FILE__, __LINE__, $sesion->dbh);

		if ($documento_eliminado->Delete())
			$pagina->AddInfo(__('El documento ha sido eliminado satisfactoriamente'));
	}
	else {
		if(UtilesApp::GetConf($sesion, 'NuevoModuloFactura')){
			$id_neteo = $documento_eliminado->ObtenerIdNeteo($id_cobro);
			$factura_pago->LoadByNeteoAdelanto($id_neteo);
			$factura_pago->Eliminar();
		}
		else{
			$documento_eliminado->EliminarNeteo($id_cobro);
		}
		$pagina->AddInfo(__('El pago ha sido eliminado satisfactoriamente'));
	}
        $cobro->CambiarEstadoSegunFacturas();
}

$moneda_total = new Moneda($sesion);
$moneda_total->Load($cobro->fields['opc_moneda_total']);

if (!$contrato->Load($cobro->fields['id_contrato']))
	$pagina->FatalError(__('Contrato inválido'));

$idioma->Load($contrato->fields['codigo_idioma']);

/* Antes de cargar el documento_cobro, es posible que se deje en 0 (anular emisión) o que se reinicie (cambio de estado desde incobrable) */
if ($opc == 'anular_emision') {
	$estado_anterior = $cobro->fields['estado'];
	
	$cobro->AnularEmision($estado);

	#Se ingresa la anotación en el historial
	
					
	if ( $estado_anterior != 'COBRO INCOBRABLE' ) {
		$his = new Observacion($sesion);
		$his->Edit('fecha', date('Y-m-d H:i:s'));
		$his->Edit('comentario', __('COBRO INCOBRABLE'));
		$his->Edit('id_usuario', $sesion->usuario->fields['id_usuario']);
		$his->Edit('id_cobro', $cobro->fields['id_cobro']);
		if ($his->Write())
			$pagina->AddInfo(__('Historial ingresado'));
	}
}
//Se reinicia el documento del cobro
if ($cobro->fields['estado'] == 'INCOBRABLE' && $opc == 'guardar' && $estado != 'INCOBRABLE') {
	$cobro->ReiniciarDocumento();
}

//Ahora si se puede cargar el documento actualizado.
if ($documento_cobro->LoadByCobro($id_cobro)) {
	$moneda_documento = new Moneda($sesion);
	$moneda_documento->Load($documento_cobro->fields['id_moneda']);
}
        else {
                $moneda_documento = new Moneda($sesion);
		$moneda_documento->Load($cobro->fields['opc_moneda_total']);
        }
$moneda = new Moneda($sesion);

if (!$fecha_pago && $opc != 'guardar')
	$fecha_pago = $cobro->fields['fecha_cobro'];
/* Comprobaciones previas */

if ($cobro->fields['estado'] == 'CREADO' || $cobro->fields['estado'] == 'EN REVISION')
	$pagina->Redirect("cobros5.php?popup=1&id_cobro=" . $id_cobro . ($id_foco ? '&id_foco=' . $id_foco : ''));

if (!UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) {
	if ($factura->LoadByCobro($id_cobro)) {
		if ($opc == 'anular_factura') {
			#Buscar nuevo numero por la factura y ocupalo dentro de la tabla cobros
			$cobro->Edit('documento', '');
			$cobro->Edit('facturado', 0);
			if( $cobro->fields['estado'] == 'FACTURADO' ) {
				$estado = 'EMITIDO';
				$cambiar_estado = true;
				$fecha_facturacion = '';
				$cobro->Edit('fecha_facturacion', $fecha_facturacion);
			}
			$cobro->Write();
			$factura->Edit('anulado', 1);
			if ($factura->Escribir())
				$pagina->AddInfo(__('Factura anulado.'));
		}
		$id_factura = $factura->fields['id_factura'];
	}
	elseif ($opc == 'facturar') {
		$moneda_factura = new Moneda($sesion);
		$moneda_factura->Load($documento_cobro->fields['id_moneda']);

		# Se genera una factura "base", esta puede ser modificada
		$factura = new Factura($sesion);
		if (( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsaNumeracionAutomatica') ) || ( method_exists('Conf', 'UsaNumeracionAutomatica') && Conf::UsaNumeracionAutomatica() )) {
			if (UtilesApp::GetConf($sesion, 'PermitirFactura'))
				$numero_documento = $factura->ObtieneNumeroFactura();
			else
				$numero_documento = $documento;
			$factura->Edit('numero', $numero_documento);
		}
		else
			$factura->Edit('numero', $documento);
		$factura->Edit('fecha', date('Y-m-d'));
		$factura->Edit('cliente', $contrato->fields['factura_razon_social']);
		$factura->Edit('RUT_cliente', $contrato->fields['rut']);
		$factura->Edit('codigo_cliente', $cobro->fields['codigo_cliente']);
		$factura->Edit('direccion_cliente', $contrato->fields['factura_direccion']);
		if (( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'CalculacionCyC') ) || ( method_exists('Conf', 'CalculacionCyC') && Conf::CalculacionCyC() )) {
			$factura->Edit('subtotal', number_format(!empty($documento_cobro->fields['subtotal_honorarios']) ? $documento_cobro->fields['subtotal_honorarios'] : '0', $moneda_factura->fields['cifras_decimales'], ".", ""));
			$factura->Edit('subtotal_gastos', number_format(!empty($documento_cobro->fields['subtotal_gastos']) ? $documento_cobro->fields['subtotal_gastos'] : '0', $moneda_factura->fields['cifras_decimales'], ".", ""));
			$factura->Edit('descuento_honorarios', number_format(!empty($documento_cobro->fields['descuento_honorarios']) ? $documento_cobro->fields['descuento_honorarios'] : '0' , $moneda_factura->fields['cifras_decimales'], ".", ""));
			$factura->Edit('subtotal_sin_descuento', number_format(!empty($documento_cobro->fields['subtotal_sin_descuento']) ? $documento_cobro->fields['subtotal_sin_descuento'] : '0', $moneda_factura->fields['cifras_decimales'], ".", ""));
		}
		else {
                                $factura->Edit('subtotal',number_format(($documento_cobro->fields['honorarios']-$documento_cobro->fields['impuesto']),$moneda_factura->fields['cifras_decimales'],".","")); 
		}
                $factura->Edit('iva',number_format($documento_cobro->fields['impuesto'],$moneda_factura->fields['cifras_decimales'],".","")); 
                $factura->Edit('total',number_format($documento_cobro->fields['honorarios']+$documento_cobro->fields['gastos'],$moneda_factura->fields['cifras_decimales'],".","")); 

		$estado = 'FACTURADO';
		$cambiar_estado = false;
        if( $cobro->fields['estado'] != 'FACTURADO' ) {
            $cambiar_estado = true;
        }
		$cobro->Edit('fecha_facturacion',date('Y-m-d H:i:s'));
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
			$estado = 'FACTURADO';
                        $cambiar_estado = false;
                        if( $cobro->fields['estado'] != 'FACTURADO' ) {
                            $cambiar_estado = true;
                        }
                        if (( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'NotaCobroExtra') ) || ( method_exists('Conf', 'NotaCobroExtra') && Conf::NotaCobroExtra() ))
				$cobro->Edit('nota_cobro', $lista_NotaCreditoActivas);
		}
		else {
			if (( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsaNumeracionAutomatica') ) || ( method_exists('Conf', 'UsaNumeracionAutomatica') && Conf::UsaNumeracionAutomatica() )) {
				$cobro->Edit('documento', $numero_documento);
			}
			else
				$cobro->Edit('documento', $documento);
			if (( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'NotaCobroExtra') ) || ( method_exists('Conf', 'NotaCobroExtra') && Conf::NotaCobroExtra() ))
				$cobro->Edit('nota_cobro', $nota_cobro);
		}
		$cobro->Write();

		#Fechas periodo
		$datefrom = strtotime($cobro->fields['fecha_ini'], 0);
		$dateto = strtotime($cobro->fields['fecha_fin'], 0);
		$difference = $dateto - $datefrom; //Dif segundos
		$months_difference = floor($difference / 2678400);
		while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom) + ($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
			$months_difference++;
		}

		$datediff = $months_difference;

		if ($cobro->fields['fecha_ini'] != '' && $cobro->fields['fecha_ini'] != '0000-00-00')
			$texto_fecha = __('entre los meses de') . ' ' . Utiles::sql3fecha($cobro->fields['fecha_ini'], '%B') . ' ' . __('y') . ' ' . Utiles::sql3fecha($cobro->fields['fecha_fin'], '%B');
		else
			$texto_fecha = __('hasta el mes de') . ' ' . Utiles::sql3fecha($cobro->fields['fecha_fin'], '%B');

		if ($lang == 'es') {
			$servicio_periodo = 'Honorarios por servicios profesionales prestados %fecha_ini% %fecha_fin%';
			$meses_org = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
			$mes_corto = array('jan', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic');
			if ($cobro->fields['fecha_ini'] && $cobro->fields['fecha_ini'] != '0000-00-00')
				$servicio_periodo = str_replace('%fecha_ini%', 'desde ' . str_replace($meses_org, $mes_corto, date('j-M-y', strtotime($cobro->fields['fecha_ini']))), $servicio_periodo);
			else
				$servicio_periodo = str_replace('%fecha_ini%', '', $servicio_periodo);
			if ($cobro->fields['fecha_fin'] && $cobro->fields['fecha_fin'] != '0000-00-00')
				$servicio_periodo = str_replace('%fecha_fin%', 'hasta ' . str_replace($meses_org, $mes_corto, date('j-M-y', strtotime($cobro->fields['fecha_fin']))), $servicio_periodo);
			else
				$servicio_periodo = str_replace('%fecha_fin%', '', $servicio_periodo);
		}
		else {
			$servicio_periodo = 'For legal services rendered %fecha_ini% %fecha_fin%';
			$meses_org = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
			$month_short = array('jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec');
			if ($cobro->fields['fecha_ini'] && $cobro->fields['fecha_ini'] != '0000-00-00')
				$servicio_periodo = str_replace('%fecha_ini%', 'from ' . str_replace($meses_org, $month_short, date('M-d-y', strtotime($cobro->fields['fecha_ini']))), $servicio_periodo);
			else
				$servicio_periodo = str_replace('%fecha_ini%', '', $servicio_periodo);
			if ($cobro->fields['fecha_fin'] && $cobro->fields['fecha_fin'] != '0000-00-00')
				$servicio_periodo = str_replace('%fecha_fin%', 'until ' . str_replace($meses_org, $month_short, date('M-d-y', strtotime($cobro->fields['fecha_fin']))), $servicio_periodo);
			else
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
					$documento->fieñds['id_moneda']
				);

				$query = "DELETE FROM factura_cobro WHERE id_factura = '" . $factura->fields['id_factura'] . "' AND id_cobro = '" . $id_cobro . "' ";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

				$query = "INSERT INTO factura_cobro (id_factura, id_cobro, id_documento, monto_factura, impuesto_factura, id_moneda_factura, id_moneda_documento)
										VALUES ('" . implode("','", $valores) . "')";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			}
		}
		$id_factura = $factura->fields['id_factura'];
	}
}


if ($opc == 'guardar') {
	if( !UtilesApp::GetConf($sesion,'NuevoModuloFactura') ) {
		if ($facturado == 1 ) {
			if( !$cobro->fields['fecha_facturacion'] )
				$cobro->Edit('fecha_facturacion', date('Y-m-d H:i:s'));
			$cobro->Edit('facturado', $facturado );
			if( $cobro->fields['estado'] == 'EMITIDO' )	
				$estado = 'FACTURADO';
		} else {
			$cobro->Edit('facturado', '0');
			if( $cobro->fields['estado'] == 'FACTURADO' ) {
				$estado = 'EMITIDO';
				$fecha_facturacion = '';
			}
		}
	}
	$cambiar_estado = false;
	if ($estado != $cobro->fields['estado'])
		$cambiar_estado = true;
       

           
           
        $cobro->Edit('fecha_emision', $fecha_emision ? Utiles::fecha2sql($fecha_emision) : '');
	$cobro->Edit('fecha_enviado_cliente', $fecha_envio ? Utiles::fecha2sql($fecha_envio) : '');
	$cobro->Edit('fecha_cobro', $fecha_pago ? Utiles::fecha2sql($fecha_pago) : '');
        $cobro->Edit('fecha_pago_parcial', $fecha_pago_parcial ? Utiles::fecha2sql($fecha_pago_parcial) : '');
        $cobro->Edit('fecha_facturacion', $fecha_facturacion ? Utiles::fecha2sql($fecha_facturacion) : '');

	$cobro->SetPagos($honorarios_pagados, $gastos_pagados);
	//Ahora hay que revisar que no se haya pasado a PAGADO, cambiando estado a PAGADO (por Historial).
	if ($estado == 'PAGADO' && $cobro->fields['estado'] == 'PAGADO')
		$cambiar_estado = false;

	$cobro->Edit('forma_envio', $forma_envio);

	if ( !UtilesApp::GetConf($sesion,'UsaNumeracionAutomatica') ) {
		if( UtilesApp::GetConf($sesion,'PermitirFactura') && !UtilesApp::GetConf($sesion,'NuevoModuloFactura') ) {
			$factura->Edit('numero', $documento);
			$factura->Escribir();
		}
		$cobro->Edit('documento', $documento);
		if (( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'NotaCobroExtra') ) || ( method_exists('Conf', 'NotaCobroExtra') && Conf::NotaCobroExtra() ))
			$cobro->Edit('nota_cobro', $nota_cobro);
	}


	if ($email_cliente != $contrato->fields['email_contacto']) {
		$contrato->Edit('email_contacto', $email_cliente);
		$contrato->Write();
	}

	$cobro->Edit('se_esta_cobrando', $se_esta_cobrando);

	
	$cobro->Write();
}



if ($cambiar_estado) {
	$estado_anterior = $cobro->fields['estado'];
		
	
        if ($estado == 'EMITIDO' && !$cobro->fields['fecha_emision'])
		
            $cobro->Edit('fecha_emision', date('Y-m-d H:i:s'));
	if ($estado == 'ENVIADO AL CLIENTE' && !$cobro->fields['fecha_enviado_cliente'])
		$cobro->Edit('fecha_enviado_cliente', date('Y-m-d H:i:s'));
	if ($estado == 'FACTURADO' && !$cobro->fields['fecha_facturacion'])
		$cobro->Edit('fecha_facturacion', date('Y-m-d H:i:s'));
	if ($estado == 'PAGO PARCIAL' && !$cobro->fields['fecha_pago_parcial'])
		$cobro->Edit('fecha_pago_parcial', date('Y-m-d H:i:s'));
	if ($estado == 'PAGADO' && !$cobro->fields['fecha_cobro'])
		$cobro->Edit('fecha_cobro', date('Y-m-d H:i:s'));
	$cobro->Edit('estado', $estado);
	$cobro->Write();
        
	#Se ingresa la anotación en el historial
	
	if ( $estado_anterior != $estado ) {
		$his = new Observacion($sesion);
		$his->Edit('fecha', date('Y-m-d H:i:s'));
		$his->Edit('comentario', __("COBRO $estado"));
		$his->Edit('id_usuario', $sesion->usuario->fields['id_usuario']);
		$his->Edit('id_cobro', $cobro->fields['id_cobro']);
		if ($his->Write())
			$pagina->AddInfo(__('Historial ingresado'));
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
		$cobro->Edit("opc_ver_concepto_gastos",$opc_ver_concepto_gastos);
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
	// Opciones especificos para Vial Olivares
	$cobro->Edit("opc_restar_retainer", $opc_restar_retainer);
	$cobro->Edit("opc_ver_detalle_retainer", $opc_ver_detalle_retainer);
	$cobro->Edit("opc_ver_valor_hh_flat_fee", $opc_ver_valor_hh_flat_fee);
	$cobro->Edit('id_carta', $id_carta);
	$cobro->Edit('id_formato', $id_formato);
	$cobro->Edit('codigo_idioma', $lang);
                $cobro->Edit("opc_ver_columna_cobrable",$opc_ver_columna_cobrable); 
                
	if ($cobro->Write()) {
		if ($opc == 'grabar_documento' || $opc == 'grabar_documento_pdf') {
			include dirname(__FILE__) . '/cobro_doc.php';
			exit;
		}
	}
}
elseif ($opc == 'grabar_documento_factura') {
	include dirname(__FILE__) . '/factura_doc.php';
	exit;
}
else if ($opc == 'grabar_documento_factura_pdf') {
	$factura_pdf_datos = new FacturaPdfDatos($sesion);
	$factura_pdf_datos->generarFacturaPDF($id_factura_grabada);
}
elseif ($opc == 'descargar_excel' || $opc == 'descargar_excel_especial') {
	$cobro->Edit("opc_ver_detalles_por_hora", $opc_ver_detalles_por_hora);
	$cobro->Edit("opc_ver_modalidad", $opc_ver_modalidad);
	$cobro->Edit("opc_ver_profesional", $opc_ver_profesional);
	$cobro->Edit("opc_ver_profesional_iniciales", $opc_ver_profesional_iniciales);
	$cobro->Edit("opc_ver_profesional_categoria", $opc_ver_profesional_categoria);
	$cobro->Edit("opc_ver_profesional_tarifa", $opc_ver_profesional_tarifa);
	$cobro->Edit("opc_ver_profesional_importe", $opc_ver_profesional_importe);
	$cobro->Edit("opc_ver_gastos", $opc_ver_gastos);
		$cobro->Edit("opc_ver_concepto_gastos",$opc_ver_concepto_gastos);
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
		}
		else {
			if (UtilesApp::GetConf($sesion, 'XLSFormatoEspecial') != "cobros_xls_formato_especial.php") {
				require_once Conf::ServerDir() . '/../app/interfaces/cobros_xls.php';
			}
			else {
				require_once Conf::ServerDir() . '/../app/interfaces/' . UtilesApp::GetConf($sesion, 'XLSFormatoEspecial');
			}
		}
		exit;
	}
	$desde_cobros_emitidos = true;
	if (( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'XLSFormatoEspecial') ) || ( method_exists('Conf', 'XLSFormatoEspecial') && Conf::XLSFormatoEspecial() ))
		require_once Conf::ServerDir() . '/../app/interfaces/cobros_xls_formato_especial.php';
	else
		require_once Conf::ServerDir() . '/../app/interfaces/cobros_xls.php';
	exit;
}
$cobro->Edit('etapa_cobro', '5');
$cobro->Write();
$moneda->Load($cobro->fields['id_moneda']);




#Moneda base
$moneda_base = new Objeto($sesion, '', '', 'prm_moneda', 'id_moneda');
$moneda_base->Load($cobro->fields['id_moneda_base']);

/* Tipo cambio segï¿½n moneda impresion (opc_moneda) */
$cobro_moneda = new CobroMoneda($sesion);
$cobro_moneda->Load($cobro->fields['id_cobro']);
$tipo_cambio_moneda_total = $cobro_moneda->GetTipoCambio($id_cobro, $cobro->fields['opc_moneda_total'] != '' ? $cobro->fields['opc_moneda_total'] : 1);

## Contrato
if ($cobro->fields['id_contrato'] != '') {
	$contrato = new Contrato($sesion);
	$contrato->Load($cobro->fields['id_contrato']);
}
?>
<?php

require_once("../app/conf.php");
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/../app/classes/Cobro.php';
require_once Conf::ServerDir() . '/../app/classes/Moneda.php';
require_once Conf::ServerDir() . '/../app/classes/Reporte.php';

apache_setenv("force-response-1.0", "TRUE");
apache_setenv("downgrade-1.0", "TRUE"); #Esto es lo más importante

$Sesion = new Sesion();
$ns = "urn:TimeTracking";

if (UtilesApp::GetConf($Sesion, 'NuevaLibreriaNusoap')) {
	require_once("lib2/nusoap.php");
} else {
	require_once("lib/nusoap.php");
}
#First we must include our NuSOAP library and define the namespace of the service. It is usually recommended that you designate a distinctive URI for each one of your Web services.


$server = new soap_server();
$server->configureWSDL('IntegracionSAPWebServices', $ns);
$server->wsdl->schemaTargetNamespace = $ns;

$server->wsdl->addComplexType(
		'UsuarioCobro', 'complexType', 'struct', 'all', '', array(
	'username' => array('name' => 'username', 'type' => 'xsd:string'),
	'valor' => array('name' => 'valor', 'type' => 'xsd:decimal'),
	'horas_trabajadas' => array('name' => 'horas_trabajadas', 'type' => 'xsd:decimal'),
	'horas_cobradas' => array('name' => 'horas_cobradas', 'type' => 'xsd:decimal'),
	'ListaAsuntos' => array('name' => 'ListaAsuntos', 'type' => 'tns:ListaAsuntos')
));

$server->wsdl->addComplexType(
		'Asunto', 'complexType', 'struct', 'all', '', array(
	'codigo' => array('name' => 'codigo', 'type' => 'xsd:string'),
	'codigo_secundario' => array('name' => 'codigo_secundario', 'type' => 'xsd:string'),
	'glosa' => array('name' => 'glosa', 'type' => 'xsd:string'),
	'valor' => array('name' => 'valor', 'type' => 'xsd:decimal'),
	'horas_trabajadas' => array('name' => 'horas_trabajadas', 'type' => 'xsd:decimal'),
	'horas_cobradas' => array('name' => 'horas_cobradas', 'type' => 'xsd:decimal'),
));

$server->wsdl->addComplexType(
		'FacturaCobro', 'complexType', 'struct', 'all', '', array(
	'codigo_factura_lemontech' => array('name' => 'codigo_factura_lemontech', 'type' => 'xsd:integer'),
	'codigo_factura_asociada_lemontech' => array('name' => 'codigo_factura_asociada_lemontech', 'type' => 'xsd:integer'),
	'comprobante_erp' => array('name' => 'comprobante_erp', 'type' => 'xsd:string'),
	'condicion_pago' => array('name' => 'condicion_pago', 'type' => 'xsd:integer'),
	'serie' => array('name' => 'serie', 'type' => 'xsd:string'),
	'numero' => array('name' => 'numero', 'type' => 'xsd:integer'),
	'tipo' => array('name' => 'tipo', 'type' => 'xsd:string'),
	'codigo_cliente' => array('name' => 'codigo_cliente', 'type' => 'xsd:string'),
	'honorarios' => array('name' => 'honorarios', 'type' => 'xsd:decimal'),
	'gastos_sin_iva' => array('name' => 'gastos_sin_iva', 'type' => 'xsd:decimal'),
	'gastos_con_iva' => array('name' => 'gastos_con_iva', 'type' => 'xsd:decimal'),
	'impuestos' => array('name' => 'impuestos', 'type' => 'xsd:decimal'),
	'total' => array('name' => 'total', 'type' => 'xsd:decimal'),
	'estado' => array('name' => 'estado', 'type' => 'xsd:string'),
	'saldo' => array('name' => 'saldo', 'type' => 'xsd:decimal'),
	'cliente' => array('name' => 'cliente', 'type' => 'xsd:string'),
	'rut_cliente' => array('name' => 'rut_cliente', 'type' => 'xsd:string'),
	'direccion_cliente' => array('name' => 'direccion_cliente', 'type' => 'xsd:string'),
	'fecha' => array('name' => 'fecha', 'type' => 'xsd:string'),
	'descripcion' => array('name' => 'descripcion', 'type' => 'xsd:string'),
	'ListaUsuariosFactura' => array('name' => 'ListaUsuariosFactura', 'type' => 'tns:ListaUsuariosFactura'),
	'ListaPagos' => array('name' => 'ListaPagos', 'type' => 'tns:ListaPagos'),
));

$server->wsdl->addComplexType(
		'UsuarioFactura', 'complexType', 'struct', 'all', '', array(
	'username' => array('name' => 'username', 'type' => 'xsd:string'),
	'valor' => array('name' => 'valor', 'type' => 'xsd:decimal')
));

$server->wsdl->addComplexType(
		'ListaUsuariosFactura', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:UsuarioFactura[]')), 'tns:UsuarioFactura'
);

$server->wsdl->addComplexType(
		'ListaPagos', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Pago[]')), 'tns:Pago'
);

$server->wsdl->addComplexType(
		'Pago', 'complexType', 'struct', 'all', '', array(
	'id' => array('name' => 'id', 'type' => 'xsd:integer'),
	'fecha' => array('name' => 'fecha', 'type' => 'xsd:string'),
	'monto' => array('name' => 'monto', 'type' => 'xsd:decimal'),
	'moneda' => array('name' => 'moneda', 'type' => 'xsd:string'),
	'monto_pagado' => array('name' => 'saldo_pago', 'type' => 'xsd:decimal'),
	'tipo_documento' => array('name' => 'tipo_documento', 'type' => 'xsd:string'),
	'numero_documento' => array('name' => 'numero_documento', 'type' => 'xsd:integer'),
	'numero_cheque' => array('name' => 'numero_cheque', 'type' => 'xsd:integer'),
	'descripcion' => array('name' => 'descripcion', 'type' => 'xsd:string'),
	'banco' => array('name' => 'banco', 'type' => 'xsd:string'),
	'cuenta' => array('name' => 'cuenta', 'type' => 'xsd:string'),
	'pago_retencion' => array('name' => 'pago_retencion', 'type' => 'xsd:boolean'),
	'concepto' => array('name' => 'concepto', 'type' => 'xsd:string')
));

$server->wsdl->addComplexType(
		'ListaDocumentosPagos', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:DocumentoPago[]')), 'tns:DocumentoPago'
);

$server->wsdl->addComplexType(
		'DocumentoPago', 'complexType', 'struct', 'all', '', array(
	'id' => array('name' => 'id', 'type' => 'xsd:integer'),
	'fecha' => array('name' => 'fecha', 'type' => 'xsd:string'),
	'monto' => array('name' => 'monto', 'type' => 'xsd:decimal'),
	'moneda' => array('name' => 'moneda', 'type' => 'xsd:string'),
	'saldo_pago' => array('name' => 'monto_pagado', 'type' => 'xsd:decimal'),
	'tipo_documento' => array('name' => 'tipo_documento', 'type' => 'xsd:string'),
	'numero_documento' => array('name' => 'numero_documento', 'type' => 'xsd:integer'),
	'numero_cheque' => array('name' => 'numero_cheque', 'type' => 'xsd:integer'),
	'descripcion' => array('name' => 'descripcion', 'type' => 'xsd:string'),
	'banco' => array('name' => 'banco', 'type' => 'xsd:string'),
	'cuenta' => array('name' => 'cuenta', 'type' => 'xsd:string'),
	'pago_retencion' => array('name' => 'pago_retencion', 'type' => 'xsd:boolean'),
	'concepto' => array('name' => 'concepto', 'type' => 'xsd:string'),
	'es_adelanto' => array('name' => 'es_adelanto', 'type' => 'xsd:boolean'),
	'ListaFacturasPagadas' => array('name' => 'ListaFacturasPagadas', 'type' => 'tns:ListaFacturasPagadas')
));

$server->wsdl->addComplexType(
		'ListaFacturasPagadas', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:FacturaPagada[]')), 'tns:FacturaPagada'
);

$server->wsdl->addComplexType(
		'FacturaPagada', 'complexType', 'struct', 'all', '', array(
	'id_cobro' => array('name' => 'id_cobro', 'type' => 'xsd:integer'),
	'codigo_factura_lemontech' => array('name' => 'codigo_factura_lemontech', 'type' => 'xsd:integer'),
	'comprobante_erp' => array('name' => 'comprobante_erp', 'type' => 'xsd:string'),
	'serie' => array('name' => 'serie', 'type' => 'xsd:string'),
	'numero' => array('name' => 'numero', 'type' => 'xsd:integer'),
	'tipo' => array('name' => 'tipo', 'type' => 'xsd:string'),
	'codigo_cliente' => array('name' => 'codigo_cliente', 'type' => 'xsd:string'),
	'total' => array('name' => 'total', 'type' => 'xsd:decimal'),
	'estado' => array('name' => 'estado', 'type' => 'xsd:string'),
	'saldo' => array('name' => 'saldo', 'type' => 'xsd:decimal'),
	'cliente' => array('name' => 'cliente', 'type' => 'xsd:string'),
	'fecha' => array('name' => 'fecha', 'type' => 'xsd:string'),
	'descripcion' => array('name' => 'descripcion', 'type' => 'xsd:string'),
	'monto_pago' => array('name' => 'monto_pago', 'type' => 'xsd:decimal')
));

$server->wsdl->addComplexType(
		'ListaAsuntos', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Asunto[]')), 'tns:Asunto'
);

$server->wsdl->addComplexType(
		'ListaUsuariosCobro', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:UsuarioCobro[]')), 'tns:UsuarioCobro'
);

$server->wsdl->addComplexType(
		'ListaFacturasCobro', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:FacturaCobro[]')), 'tns:FacturaCobro'
);

$server->wsdl->addComplexType(
		'DatosCobro', 'complexType', 'struct', 'all', '', array(
	'id_cobro' => array('name' => 'id_cobro', 'type' => 'xsd:integer'),
	'nota_venta' => array('name' => 'nota_venta', 'type' => 'xsd:integer'),
	'descripcion' => array('name' => 'descripcion', 'type' => 'xsd:string'),
	'encargado_comercial' => array('name' => 'encargado_comercial', 'type' => 'xsd:string'),
	'encargado_secundario' => array('name' => 'encargado_secundario', 'type' => 'xsd:string'),
	'codigo_cliente' => array('name' => 'codigo_cliente', 'type' => 'xsd:string'),
	'estado' => array('name' => 'estado', 'type' => 'xsd:string'),
	'moneda' => array('name' => 'moneda', 'type' => 'xsd:string'),
	'fecha_ini' => array('name' => 'fecha_ini', 'type' => 'xsd:string'),
	'fecha_fin' => array('name' => 'fecha_fin', 'type' => 'xsd:string'),
	'rut' => array('name' => 'rut', 'type' => 'xsd:string'),
	'razon_social' => array('name' => 'razon_social', 'type' => 'xsd:string'),
	'direccion' => array('name' => 'direccion', 'type' => 'xsd:string'),
	'total_honorarios_sin_iva' => array('name' => 'total_honorarios_sin_iva', 'type' => 'xsd:decimal'),
	'total_gastos_sin_iva' => array('name' => 'total_gastos_sin_iva', 'type' => 'xsd:decimal'),
	'total_honorarios' => array('name' => 'total_honorarios', 'type' => 'xsd:decimal'),
	'total_gastos' => array('name' => 'total_gastos', 'type' => 'xsd:decimal'),
	'fecha_emision' => array('name' => 'fecha_emision', 'type' => 'xsd:string'),
	'glosa_carta' => array('name' => 'glosa_carta', 'type' => 'xsd:string'),
	'numero_factura' => array('name' => 'numero_factura', 'type' => 'xsd:string'),
	'timestamp' => array('name' => 'timestamp', 'type' => 'xsd:integer'),
	'facturar' => array('name' => 'facturar', 'type' => 'xsd:string'),
	'ListaAsuntos' => array('name' => 'ListaAsuntos', 'type' => 'tns:ListaAsuntos'),
	'ListaUsuariosCobro' => array('name' => 'ListaUsuariosCobro', 'type' => 'tns:ListaUsuariosCobro'),
	'ListaFacturasCobro' => array('name' => 'ListaFacturasCobro', 'type' => 'tns:ListaFacturasCobro')
));

$server->wsdl->addComplexType(
		'ListaCobros', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:DatosCobro[]')), 'tns:DatosCobro'
);

$server->wsdl->addComplexType(
	'Gasto', 'complexType', 'struct', 'all', '', array(
		'id' => array('name' => 'id', 'type' => 'xsd:integer'),
		'usuario_ingresa' => array('name' => 'usuario_ingreso', 'type' => 'xsd:string'),
		'usuario_ordena' => array('name' => 'usuario_orden', 'type' => 'xsd:string'),
		'codigo_cliente' => array('name' => 'codigo_cliente', 'type' => 'xsd:string'),
		'codigo_asunto' => array('name' => 'codigo_asunto', 'type' => 'xsd:string'),
		'descripcion' => array('name' => 'descripcion', 'type' => 'xsd:string'),
		'glosa_gasto' => array('name' => 'glosa_gasto', 'type' => 'xsd:string'),
		'ingreso' => array('name' => 'ingreso', 'type' => 'xsd:decimal'),
		'egreso' => array('name' => 'egreso', 'type' => 'xsd:decimal'),
		'codigo_moneda' => array('name' => 'moneda', 'type' => 'xsd:string'),
		'fecha' => array('name' => 'fecha', 'type' => 'xsd:string'),
		'cobrable' => array('name' => 'cobrable', 'type' => 'xsd:boolean'),
		'monto_cobrable' => array('name' => 'monto_cobrable', 'type' => 'xsd:decimal'),
		'impuesto' => array('name' => 'impuesto', 'type' => 'xsd:decimal'),
		'numero_documento' => array('name' => 'numero_documento', 'type' => 'xsd:string'),
		'numero_ot' => array('name' => 'numero_ot', 'type' => 'xsd:string'),
		'con_impuesto' => array('name' => 'con_impuesto', 'type' => 'xsd:boolean'),
		'codigo_proveedor' => array('name' => 'codigo_proveedor', 'type' => 'xsd:string'),
		'nombre_proveedor' => array('name' => 'nombre_proveedor', 'type' => 'xsd:string'),
		'tipo_documento_asociado' => array('name' => 'tipo_documento_asociado', 'type' => 'xsd:string'),
		'codigo_documento_asociado' => array('name' => 'numero_documento_asociado', 'type' => 'xsd:string')
));

$server->wsdl->addComplexType(
		'ListaGastos', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:Gasto[]')), 'tns:Gasto'
);

$server->wsdl->addComplexType(
		'DatosResultado', 'complexType', 'struct', 'all', '', array(
	'id_cobro' => array('name' => 'id_cobro', 'type' => 'xsd:integer'),
	'resultado' => array('name' => 'resultado', 'type' => 'xsd:string'),
	'ResultadoFacturas' => array('name' => 'ResultadoFacturas', 'type' => 'tns:ResultadoFacturas'),
	'ResultadoPagos' => array('name' => 'ResultadoPagos', 'type' => 'tns:ResultadoPagos')
));

$server->wsdl->addComplexType(
		'DatosResultadoFactura', 'complexType', 'struct', 'all', '', array(
	'codigo_factura_lemontech' => array('name' => 'codigo_factura_lemontech', 'type' => 'xsd:integer'),
	'resultado_factura' => array('name' => 'resultado_factura', 'type' => 'xsd:string')
));

$server->wsdl->addComplexType(
		'DatosResultadoPago', 'complexType', 'struct', 'all', '', array(
	'id_pago' => array('name' => 'id_pago', 'type' => 'xsd:integer'),
	'resultado_pago' => array('name' => 'resultado_pago', 'type' => 'xsd:string')
));

$server->wsdl->addComplexType(
		'ResultadoCobros', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:DatosResultado[]')), 'tns:DatosResultado'
);

$server->wsdl->addComplexType(
		'ResultadoFacturas', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:DatosResultadoFactura[]')), 'tns:DatosResultadoFactura'
);

$server->wsdl->addComplexType(
		'ResultadoPagos', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:DatosResultadoPago[]')), 'tns:DatosResultadoPago'
);

$server->wsdl->addComplexType(
		'ResultadoDocumentosPagos', 'complexType', 'array', '', 'SOAP-ENC:Array', array(), array(array('ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:DatosResultado[]')), 'tns:DatosResultado'
);

$server->register('ListaCobrosFacturados', array('usuario' => 'xsd:string', 'password' => 'xsd:string', 'timestamp' => 'xsd:integer'), array('lista_cobros_emitidos' => 'tns:ListaCobros'), $ns);

function ListaCobrosFacturados($usuario, $password, $timestamp) {
	$Sesion = new Sesion();
	$time = mktime();

	//Mapeo usernames a centro_de_costos
	$username_centro_de_costo = array();
	if (UtilesApp::VerificarPasswordWebServices($usuario, $password)) {
		$lista_cobros = array();

		$query_timestamp = "";
		if ($timestamp) {
			$query_timestamp = " OR cobro.id_cobro IN (SELECT DISTINCT id_cobro FROM log_contabilidad WHERE timestamp >= " . intval(mysql_real_escape_string($timestamp)) . " ) ";
		}
		$query = "SELECT cobro.id_cobro,
										cobro.codigo_cliente,
										cobro.estado,
										cobro.opc_moneda_total,
										cobro.fecha_ini,
										cobro.fecha_fin,
										contrato.factura_razon_social,
										contrato.factura_direccion,
										contrato.rut,
										cobro.monto_subtotal,
										cobro.subtotal_gastos,
										cobro.descuento,
										cobro.monto,
										cobro.monto_gastos,
										cobro.fecha_emision,
										cobro_moneda_tarifa.tipo_cambio as tipo_cambio_moneda,
										cobro.tipo_cambio_moneda_base,
										cobro.nota_venta_contabilidad,
										cobro.se_esta_cobrando,
										usuario.username AS encargado_comercial,
										usuario_secundario.username AS encargado_secundario,
										cobro.estado_contabilidad,
										prm_moneda.cifras_decimales,
										cobro_moneda_mt.tipo_cambio as tipo_cambio_moneda_total,
										prm_moneda_total.cifras_decimales as cifras_decimales_total,
										prm_moneda_total.codigo as codigo,
										carta.descripcion as glosa_carta,
										cobro.documento
										FROM cobro
										JOIN cobro_moneda as cobro_moneda_mt ON cobro_moneda_mt.id_cobro = cobro.id_cobro AND cobro_moneda_mt.id_moneda = cobro.opc_moneda_total
										JOIN cobro_moneda as cobro_moneda_tarifa ON cobro_moneda_tarifa.id_cobro = cobro.id_cobro AND cobro_moneda_tarifa.id_moneda = cobro.id_moneda
										LEFT JOIN prm_moneda ON prm_moneda.id_moneda=cobro.id_moneda
										LEFT JOIN prm_moneda AS prm_moneda_total ON prm_moneda_total.id_moneda = cobro.opc_moneda_total
										LEFT JOIN carta ON carta.id_carta=cobro.id_carta
										LEFT JOIN contrato ON contrato.id_contrato=cobro.id_contrato
										LEFT JOIN usuario ON contrato.id_usuario_responsable = usuario.id_usuario
										LEFT JOIN usuario AS usuario_secundario ON contrato.id_usuario_secundario = usuario_secundario.id_usuario
										WHERE cobro.estado_contabilidad IN ('PARA INFORMAR','PARA INFORMAR Y FACTURAR')
										$query_timestamp
				GROUP BY cobro.id_cobro";

		if (!($resp = mysql_query($query, $Sesion->dbh))) {
			return new soap_fault('Client', '', 'Error SQL.' . $query, '');
		}

		while ($temp = mysql_fetch_array($resp)) {
			$id_cobro = $temp['id_cobro'];

			$cobro = array();

			$cobro['id_cobro'] = $id_cobro;
			$cobro['nota_venta'] = $temp['nota_venta_contabilidad'];
			$cobro['descripcion'] = $temp['se_esta_cobrando'];
			$cobro['encargado_comercial'] = $temp['encargado_comercial'];
			$cobro['encargado_secundario'] = $temp['encargado_secundario'];
			$cobro['codigo_cliente'] = $temp['codigo_cliente'];
			$cobro['estado'] = $temp['estado'];
			$cobro['moneda'] = $temp['codigo'];

			$cobro['fecha_ini'] = $temp['fecha_ini'];
			$cobro['fecha_fin'] = $temp['fecha_fin'];
			$cobro['razon_social'] = $temp['factura_razon_social'];
			$cobro['direccion'] = $temp['factura_direccion'];
			$cobro['rut'] = $temp['rut'];

			$cobro['timestamp'] = $time;
			$cobro['facturar'] = 'NO';

			if ($temp['estado_contabilidad'] == 'PARA INFORMAR Y FACTURAR' ||
					$temp['estado_contabilidad'] == 'INFORMADO Y FACTURADO') {
				$cobro['facturar'] = 'SI';
			}

			/* INICIA CALCULO DE MONTOS DEL COBRO. VER cobros6.php */
			$c = new Cobro($Sesion);
			$c->Load($id_cobro);
			$x_resultados = UtilesApp::ProcesaCobroIdMoneda($Sesion, $c->fields['id_cobro'], array(), 0, true);

			/* FIN CALCULO DE MONTOS DEL COBRO */

			//calculo del monto sin iva
			$cobro['total_honorarios_sin_iva'] = number_format($x_resultados['monto_honorarios'][$c->fields['opc_moneda_total']], 2, '.', '');

			//monto del cobro
			$cobro['total_honorarios'] = number_format($x_resultados['monto'][$c->fields['opc_moneda_total']], 2, '.', '');

			//gastos sin iva
			$cobro['total_gastos_sin_iva'] = number_format($temp['subtotal_gastos'], $temp['cifras_decimales_total'], '.', '');
			//gastos con iva
			$cobro['total_gastos'] = number_format($temp['monto_gastos'], $temp['cifras_decimales_total'], '.', '');

			$cobro['fecha_emision'] = $temp['fecha_emision'];

			$cobro['glosa_carta'] = $temp['glosa_carta'];
			$cobro['numero_factura'] = $temp['documento'];



			/* Se crea una instancia de Reporte para ver el peso de cada usuario */
			$reporte = new Reporte($Sesion);
			$reporte->id_moneda = $temp['opc_moneda_total'];
			$reporte->setTipoDato('valor_cobrado');
			$reporte->setVista('id_cobro-username');
			$reporte->addFiltro('cobro', 'id_cobro', $id_cobro);
			$reporte->Query();
			$r = $reporte->toArray();

			/* Se obtienen además las horas de cada usuario. */
			$reporte->setTipoDato('horas_cobrables');
			$reporte->Query();
			$r_cobradas = $reporte->toArray();

			$reporte->setTipoDato('horas_trabajadas');
			$reporte->Query();
			$r_trabajadas = $reporte->toArray();

			$total_cobrado = $r[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro]['valor'];

			// Asuntos
			$query_asuntos = "SELECT
				a.codigo_asunto AS codigo,
				a.codigo_asunto_secundario AS codigo_secundario,
				a.glosa_asunto AS glosa,
				u.username,
				SUM(TIME_TO_SEC(t.duracion)) / 3600.0 AS horas_trabajadas,
				SUM(TIME_TO_SEC(t.duracion_cobrada)) / 3600.0 AS horas_cobradas
				FROM trabajo t
				INNER JOIN asunto a ON a.codigo_asunto = t.codigo_asunto
				INNER JOIN usuario u ON u.id_usuario = t.id_usuario
				WHERE t.id_cobro = '$id_cobro' AND t.cobrable = 1
				GROUP BY t.id_usuario";
			$stmt = $Sesion->pdodbh->prepare($query_asuntos);
			$stmt->execute();

			$asuntos = array();
			$asuntos_array = array();
			$participacion_asunto = array();

			while ($r_asunto = $stmt->fetch()) {
				$cod_asunto = $r_asunto['codigo'];

				if (!array_key_exists($cod_asunto, $asuntos_array)) {
					$asuntos_array[$cod_asunto] = array(
						'codigo' => $cod_asunto,
						'codigo_secundario' => $r_asunto['codigo_secundario'],
						'glosa' => $r_asunto['glosa'],
						'valor' => 0.0
					);
				}

				if (!array_key_exists('horas_trabajadas', $asuntos_array[$cod_asunto])) {
					$asuntos_array[$cod_asunto]['horas_trabajadas'] = 0;
					$asuntos_array[$cod_asunto]['horas_cobradas'] = 0;
				}

				$asuntos_array[$cod_asunto]['horas_trabajadas'] += $r_asunto['horas_trabajadas'];
				$asuntos_array[$cod_asunto]['horas_cobradas'] += $r_asunto['horas_cobradas'];

				$participacion_asunto[$cod_asunto][$r_asunto['username']]['horas_trabajadas'] = number_format($r_asunto['horas_trabajadas'], 2, '.', '');
				$participacion_asunto[$cod_asunto][$r_asunto['username']]['horas_cobradas'] = number_format($r_asunto['horas_cobradas'], 2, '.', '');
			}

			$total_horas_cobradas = $r_cobradas[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro]['valor'];

			foreach ($asuntos_array as $cod_asunto => $valores) {
				$valores['valor'] = ($valores['horas_cobradas'] / $total_horas_cobradas) * $total_cobrado;

				// Formateo
				$valores['valor'] = number_format($valores['valor'], 2, '.', '');
				$valores['horas_trabajadas'] = number_format($valores['horas_trabajadas'], 2, '.', '');
				$valores['horas_cobradas'] = number_format($valores['horas_cobradas'], 2, '.', '');

				$asuntos[] = $valores;
			}

			$cobro['ListaAsuntos'] = $asuntos;

			$usuarios_cobro = array();

			if (is_array($r[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro])) {
				foreach ($r[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro] as $key => $dato) {
					if (is_array($dato)) {
						$usuario_cobro = array();
						$usuario_cobro['username'] = $key;
						if (!isset($username_centro_de_costo[$key])) {
							$usuario_temp = new UsuarioExt($Sesion);
							$usuario_temp->LoadByNick($key);
							if ($usuario_temp->Loaded()) {
								$username_centro_de_costo[$key] = $usuario_temp->fields['centro_de_costo'];
							} else {
								$username_centro_de_costo[$key] = '';
							}
						}
						$usuario_cobro['centro_de_costo'] = $username_centro_de_costo[$key];
						$usuario_cobro['valor'] = number_format($dato['valor'], 2, '.', '');
						$usuario_cobro['horas_trabajadas'] = number_format($r_trabajadas[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro][$key]['valor'], 2, '.', '');
						$usuario_cobro['horas_cobradas'] = number_format($r_cobradas[$id_cobro][$id_cobro][$id_cobro][$id_cobro][$id_cobro][$key]['valor'], 2, '.', '');


						if ($total_cobrado) {
							$usuario_cobro['peso'] = $dato['valor'] / $total_cobrado;
						} else {
							$usuario_cobro['peso'] = 0;
						}

						// Listado de asuntos para el usuario
						$usuario_asuntos = array();
						foreach ($asuntos as $as) {
							$cod_asunto = $as['codigo'];

							$as['horas_trabajadas'] = $participacion_asunto[$cod_asunto][$key]['horas_trabajadas'];
							$as['horas_cobradas'] = $participacion_asunto[$cod_asunto][$key]['horas_cobradas'];

							$as['valor'] = ($as['horas_cobradas'] / $usuario_cobro['horas_cobradas']) * $usuario_cobro['valor'];
							$as['valor'] = number_format($as['valor'], 2, '.', '');

							$usuario_asuntos[] = $as;
						}

						$usuario_cobro['ListaAsuntos'] = $usuario_asuntos;

						$usuarios_cobro[] = $usuario_cobro;
					}
				}
			}
			$cobro['ListaUsuariosCobro'] = $usuarios_cobro;

			//Actualizo los datos:
			$nuevo_estado = 'INFORMADO';
			if ($cobro['facturar'] == 'SI') {
				$nuevo_estado = 'INFORMADO Y FACTURADO';
			}

			$query_actualiza = "UPDATE cobro SET fecha_contabilidad = NOW(), estado_contabilidad = '" . $nuevo_estado . "' WHERE id_cobro = '" . $id_cobro . "'";
			$respuesta = mysql_query($query_actualiza, $Sesion->dbh) or Utiles::errorSQL($query_actualiza, __FILE__, __LINE__, $Sesion->dbh);
			$query_ingresa = "INSERT INTO log_contabilidad (id_cobro,timestamp) VALUES (" . $id_cobro . "," . $time . ");";
			$respuesta_in = mysql_query($query_ingresa, $Sesion->dbh) or Utiles::errorSQL($query_ingresa, __FILE__, __LINE__, $Sesion->dbh);



			$query_facturas = " SELECT
																factura.id_factura,
																factura.id_factura_padre,
																factura.codigo_cliente,
																factura.comprobante_erp,
																factura.condicion_pago,
																SUM(factura_cobro.monto_factura) as monto_factura,
																factura.numero,
																prm_documento_legal.glosa as tipo,
																prm_estado_factura.glosa,
																prm_estado_factura.codigo,
																factura.subtotal_sin_descuento,
																honorarios,
																ccfm.saldo as saldo,
																subtotal_gastos,
																subtotal_gastos_sin_impuesto,
																iva,
																prm_documento_legal.codigo as cod_tipo,
																cliente,
																RUT_cliente,
																direccion_cliente,
																fecha,
																descripcion,
																factura.id_moneda,
																pm.tipo_cambio,
																pm.cifras_decimales,
																prm_moneda.codigo as codigo_moneda_factura,
																factura.serie_documento_legal as serie
												FROM factura
												JOIN prm_moneda AS pm ON factura.id_moneda = pm.id_moneda
												LEFT JOIN cta_cte_fact_mvto AS ccfm ON factura.id_factura = ccfm.id_factura
												JOIN prm_documento_legal ON factura.id_documento_legal = prm_documento_legal.id_documento_legal
												JOIN prm_estado_factura ON factura.id_estado = prm_estado_factura.id_estado
												JOIN prm_moneda ON prm_moneda.id_moneda = factura.id_moneda
												LEFT JOIN factura_cobro ON factura_cobro.id_factura = factura.id_factura
												WHERE factura.id_cobro = '" . $id_cobro . "'
												GROUP BY factura.id_factura";
			$respu = mysql_query($query_facturas, $Sesion->dbh) or Utiles::errorSQL($query_facturas, __FILE__, __LINE__, $Sesion->dbh);

			$facturas_cobro = Array();
			while (list( $id_factura, $id_factura_padre, $codigo_cliente_factura, $comprobante_erp, $condicion_pago, $monto, $numero, $tipo, $estado, $cod_estado, $subtotal_honorarios, $honorarios, $saldo, $subtotal_gastos, $subtotal_gastos_sin_impuesto, $impuesto, $cod_tipo, $cliente, $RUT_cliente, $direccion_cliente, $fecha, $descripcion, $id_moneda_factura, $tipo_cambio_factura, $cifras_decimales_factura, $codigo_moneda_factura, $serie ) = mysql_fetch_array($respu)) {

				$mult = $cod_tipo == 'NC' ? 1 : -1;

				// Si el documento no esta anulado, lo cuento para el saldo disponible a facturar (notas de credito suman, los demas restan)
				if ($cod_estado != 'A') {
					$saldo_honorarios += $subtotal_honorarios * $mult;
					$saldo_gastos_con_impuestos += $subtotal_gastos * $mult;
					$saldo_gastos_sin_impuestos += $subtotal_gastos_sin_impuesto * $mult;
				}

				$factura_cobro['id_factura'] = $id_factura;
				$factura_cobro['codigo_cliente'] = $codigo_cliente_factura;
				$factura_cobro['codigo_factura_lemontech'] = $id_factura;
				$factura_cobro['codigo_factura_asociada_lemontech'] = $id_factura_padre;
				$factura_cobro['comprobante_erp'] = $comprobante_erp;
				$factura_cobro['condicion_pago'] = $condicion_pago;
				$factura_cobro['tipo'] = $tipo;
				$factura_cobro['numero'] = $numero;
				$factura_cobro['honorarios'] = number_format($subtotal_honorarios, 2, '.', '');
				$factura_cobro['gastos_sin_iva'] = number_format($subtotal_gastos_sin_impuesto, 2, '.', '');
				$factura_cobro['gastos_con_iva'] = number_format($subtotal_gastos, 2, '.', '');
				$factura_cobro['impuestos'] = number_format($impuesto, 2, '.', '');
				$factura_cobro['total'] = number_format($subtotal_honorarios + $subtotal_gastos + $subtotal_gastos_sin_impuesto + $impuesto, 2, '.', '');
				$factura_cobro['estado'] = $estado;
				$factura_cobro['saldo'] = number_format($saldo, 2, '.', '');

				$factura_cobro['cliente'] = $cliente;
				$factura_cobro['rut_cliente'] = $RUT_cliente;
				$factura_cobro['direccion_cliente'] = $direccion_cliente;
				$factura_cobro['fecha'] = $fecha;
				$factura_cobro['descripcion'] = $descripcion;
				$factura_cobro['moneda'] = $codigo_moneda_factura;

				if (UtilesApp::GetConf($Sesion, 'NumeroFacturaConSerie')) {
					$serie = $serie ? $serie : '001';
					$factura_cobro['serie'] = str_pad($serie, 3, '0', STR_PAD_LEFT);
				} else {
					$factura_cobro['serie'] = $serie;
				}

				$uc = $usuarios_cobro;
				$factura_cobro['ListaUsuariosFactura'] = $uc;
				foreach ($factura_cobro['ListaUsuariosFactura'] as $key => $user) {
					$factura_cobro['ListaUsuariosFactura'][$key]['valor'] = number_format($user['peso'] * $factura_cobro['honorarios'], 2, '.', '');
				}

				// Incluir los pagos
				$query_pagos = "SELECT
						fp.id_factura_pago AS id,
						fp.fecha,
						fp.monto,
						prm_moneda.codigo AS moneda,
						ccfmn.monto_pago AS monto_pagado,
						fp.tipo_doc AS tipo_documento,
						fp.nro_documento AS numero_documento,
						fp.nro_cheque AS numero_cheque,
						fp.descripcion,
						prm_banco.nombre AS banco,
						cuenta_banco.numero AS cuenta,
						fp.pago_retencion,
						pfpc.glosa AS concepto
					FROM factura_pago fp
					LEFT JOIN cta_cte_fact_mvto ccfm_pago ON ccfm_pago.id_factura_pago = fp.id_factura_pago
					LEFT JOIN cta_cte_fact_mvto_neteo ccfmn ON ccfmn.id_mvto_pago = ccfm_pago.id_cta_cte_mvto
					LEFT JOIN cta_cte_fact_mvto ccfm_deuda ON ccfm_deuda.id_cta_cte_mvto = ccfmn.id_mvto_deuda
					LEFT JOIN factura f ON f.id_factura = ccfm_deuda.id_factura

					LEFT JOIN prm_moneda ON prm_moneda.id_moneda = fp.id_moneda
					LEFT JOIN prm_banco ON prm_banco.id_banco = fp.id_banco
					LEFT JOIN cuenta_banco ON cuenta_banco.id_cuenta = fp.id_cuenta
					LEFT JOIN prm_factura_pago_concepto pfpc ON pfpc.id_concepto = fp.id_concepto
					WHERE
						f.id_factura = $id_factura";

				$result_pagos = mysql_query($query_pagos, $Sesion->dbh) or Utiles::errorSQL($query_pagos, __FILE__, __LINE__, $Sesion->dbh);
				$facturas_pagos = array();
				while ($fp = mysql_fetch_assoc($result_pagos)) {
					$facturas_pagos[] = $fp;
				}

				$factura_cobro['ListaPagos'] = $facturas_pagos;

				$facturas_cobro[] = $factura_cobro;
			}
			$cobro['ListaFacturasCobro'] = $facturas_cobro;

			$lista_cobros[] = $cobro;
		}

		return new soapval('lista_cobros_emitidos', 'ListaCobros', $lista_cobros);
	}
	return new soap_fault('Client', '', 'Usuario o contraseña incorrecta.', '');
}

$server->register('InformarNotaVenta', array('usuario' => 'xsd:string', 'password' => 'xsd:string', 'lista_cobros' => 'tns:ListaCobros'), array('resultados' => 'tns:ResultadoCobros'), $ns);

class PaginaFalsa {

	function PaginaFalsa() {
		$this->info = array();
		$this->error = array();
	}

	function AddError($error_msg) {
		$this->error[] = $error_msg;
	}

	function AddInfo($info_msg) {
		$this->info[] = $info_msg;
	}

	function Output() {
		$out = '';
		if ($this->info)
			$out .= 'Info: ' . join(',', $this->info);
		if ($this->error)
			$out .= 'Error: ' . join(',', $this->error);
		if (!$out)
			return 'Nada';
		return $out;
	}

}

//Wrapper para agregar_pago_factura.php (esto impide que se pisen variables). Todo outbut se guarda en out.
function AgregarPagoFactura($p, $Sesion) {
	$opcion = 'guardar';
	$desde_webservice = true;
	$usuario = $p['usuario'];
	$password = $p['password'];

	$id_contabilidad = $p['id_contabilidad'];
	$cliente = $p['cliente'];
	$id_cobro = $p['id_cobro'];

	$id_factura = $p['id_factura'];

	$fecha = $p['fecha'];
	//Se le pasa un objeto $pagina a Documento, que usa los metodos AddError y AddInfo.
	$pagina = new PaginaFalsa();

	$codigo_cliente_factura = $p['codigo_cliente_factura'];
	$monto = $p['monto'];
	$id_moneda = $p['id_moneda'];
	$monto_moneda_cobro = $p['monto_moneda_cobro'];
	$id_moneda_cobro = $p['id_moneda_cobro'];
	$tipo_doc = $p['tipo_doc'];
	$numero_doc = $p['numero_doc'];
	$numero_cheque = $p['numero_cheque'];
	$glosa_documento = $p['glosa_documento'];
	$id_banco = $p['id_banco'];
	$id_cuenta = $p['id_cuenta'];
	$pago_retencion = $p['pago_retencion'];
	$id_concepto = $p['id_concepto'];

	$query = "SELECT id_moneda, glosa_moneda, tipo_cambio FROM prm_moneda";
	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);

	$ids_monedas_factura_pago = array();
	$tipo_cambios_factura_pago = array();
	while (list($id_moneda_db, $glosa_moneda, $tipo_cambio) = mysql_fetch_array($resp)) {
		$ids_monedas_factura_pago[] = $id_moneda_db;
		$tipo_cambios_factura_pago[] = $tipo_cambio;
	}
	$ids_monedas_factura_pago = join(',', $ids_monedas_factura_pago);
	$tipo_cambios_factura_pago = join(',', $tipo_cambios_factura_pago);

	ob_start();
	require Conf::ServerDir() . '/../app/interfaces/agregar_pago_factura.php';
	$out = ob_get_clean();
	return $resultado;
}

//Wrapper para agregar_factura.php (esto impide que se pisen variables como $factura)
function AgregarFactura($p) {
	$opcion = 'guardar';
	$desde_webservice = true;
	$usuario = $p['usuario'];
	$password = $p['password'];

	$cliente = $p['cliente'];
	$monto_honorarios_legales = $p['monto_honorarios_legales'];
	$monto_gastos_con_iva = $p['monto_gastos_con_iva'];
	$monto_gastos_sin_iva = $p['monto_gastos_sin_iva'];

	$comprobante_erp = $p['comprobante_erp'];
	$condicion_pago = $p['condicion_pago'];

	$monto_neto = $p['monto_neto'];
	$porcentaje_impuesto = $p['porcentaje_impuesto'];
	$iva = $p['iva'];
	//(total = monto_neto+iva)
	$id_factura_padre = $p['id_factura_padre'];
	$fecha = $p['fecha'];
	$RUT_cliente = $p['RUT_cliente'];
	$direccion_cliente = $p['direccion_cliente'];
	$codigo_cliente = $p['codigo_cliente'];
	$id_cobro = $p['id_cobro'];
	$id_documento_legal = $p['id_documento_legal'];
	$numero = $p['numero'];
	$serie = $p['serie'];
	$id_estado = $p['id_estado'];
	$id_moneda_factura = $p['id_moneda_factura'];
	//Para Conf::GetConf($Sesion,'DesgloseFactura')=='con_desglose'
	$descripcion_honorarios_legales = $p['descripcion_honorarios_legales'];
	$monto_honorarios_legales = $p['monto_honorarios_legales'];
	$descripcion_gastos_con_iva = $p['descripcion_gastos_con_iva'];
	$monto_gastos_con_iva = $p['monto_gastos_con_iva'];
	$descripcion_gastos_sin_iva = $p['descripcion_gastos_sin_iva'];
	$monto_gastos_sin_iva = $p['monto_gastos_sin_iva'];
	$total = $p['total'];
	$iva_hidden = $p['iva'];

	$descripcion = $p['descripcion'];
	$letra = $p['letra'];

	$codigo_tipo_doc = $p['codigo_tipo_doc'];

	ob_start();
	require Conf::ServerDir() . '/../app/interfaces/agregar_factura.php';
	$out = ob_get_clean();
	//echo 'out:('.$out.')';
	return $resultado;
}

function InformarNotaVenta($usuario, $password, $lista_cobros) {
	$Sesion = new Sesion();
	$time = mktime();


	//Cargo los tipos de documento legal
	$query_tipos_documentos_legales = "SELECT id_documento_legal, codigo, glosa FROM prm_documento_legal WHERE 1";
	$resp_tipos_documentos_legales = mysql_query($query_tipos_documentos_legales, $Sesion->dbh) or Utiles::errorSQL($query_tipos_documentos_legales, __FILE__, __LINE__, $Sesion->dbh);
	$tipo_documento_legal = array();
	$helper_tipo_documento_legal = array();
	while (list( $id_documento_legal, $codigo, $glosa ) = mysql_fetch_array($resp_tipos_documentos_legales)) {
		$tipo_documento_legal[$id_documento_legal] = array('codigo' => $codigo, 'glosa' => $glosa);
		$helper_tipo_documento_legal[] = $codigo . ' (' . $glosa . ')';
	}
	$helper_tipo_documento_legal = implode(', ', $helper_tipo_documento_legal);
	//Para revisar campos de Pagos:
	//Cargo los tipos de bancos
	$query_bancos = "SELECT id_banco, nombre FROM prm_banco";
	$resp_bancos = mysql_query($query_bancos, $Sesion->dbh) or Utiles::errorSQL($query_bancos, __FILE__, __LINE__, $Sesion->dbh);
	$banco = array();
	$helper_banco = array();
	while (list( $id_banco, $nombre ) = mysql_fetch_array($resp_bancos))
		$banco[$id_banco] = $nombre;
	$helper_banco = implode(', ', $banco);
	//Cargo los conceptos
	$query_conceptos = "SELECT id_concepto,glosa FROM prm_factura_pago_concepto ORDER BY orden";
	$resp_conceptos = mysql_query($query_conceptos, $Sesion->dbh) or Utiles::errorSQL($query_conceptos, __FILE__, __LINE__, $Sesion->dbh);
	$concepto = array();
	$helper_concepto = array();
	while (list( $id_concepto, $glosa ) = mysql_fetch_array($resp_conceptos))
		$concepto[$id_concepto] = $glosa;
	$helper_concepto = implode(', ', $concepto);
	//Cargo los tipos (de documentos de pago)
	$tipo_pago = array(
		'T' => 'Transferencia',
		'E' => 'Efectivo',
		'C' => 'Cheque',
		'O' => 'Otro');
	$helper_tipo_pago = implode(', ', $tipo_pago);
	//Cargo las monedas (su codigo: CLP, USD, etc).
	$query_monedas = "SELECT id_moneda,codigo FROM prm_moneda";
	$resp_monedas = mysql_query($query_monedas, $Sesion->dbh) or Utiles::errorSQL($query_monedas, __FILE__, __LINE__, $Sesion->dbh);
	$lista_ids_moneda = array();
	$lista_codigos_moneda = array();
	$helper_moneda = array();
	while (list( $id_moneda, $codigo ) = mysql_fetch_array($resp_monedas)) {
		$lista_moneda[$id_moneda] = $codigo;
	}
	$helper_moneda = implode(', ', $lista_moneda);


	if (UtilesApp::VerificarPasswordWebServices($usuario, $password)) {
		$resultado_cobros = array();

		//Por cada cobro entregado, puede actualizar su nota_venta y sus facturas.
		foreach ($lista_cobros as $datos_cobro) {
			$cobro = new Cobro($Sesion);
			$id_cobro = intval(mysql_real_escape_string($datos_cobro['id_cobro']));
			$nota_venta = mysql_real_escape_string($datos_cobro['nota_venta']);
			$moneda = new Moneda($Sesion);
			$moneda->Load($cobro->fields['opc_moneda_total']);
			$decimales = $moneda->fields['cifras_decimales'];

			if ($cobro->Load($id_cobro)) {
				$cobro->Edit('nota_venta_contabilidad', $nota_venta);
				if ($cobro->Write()) {
					$r = 'Ingresado';
				} else {
					$r = 'Error de Ingreso';
				}
				$resultado_facturas = array();
				//Me pueden entregar facturas para actualizar
				if ($datos_cobro['ListaFacturasCobro'])
					if (!empty($datos_cobro['ListaFacturasCobro']))
						foreach ($datos_cobro['ListaFacturasCobro'] as $datos_factura) {
							$factura = new Factura($Sesion);
							if ($datos_factura['codigo_factura_lemontech']) {
								$id_factura = intval(mysql_real_escape_string($datos_factura['codigo_factura_lemontech']));
								if ($factura->Load($id_factura)) {
									if ($factura->fields['id_cobro'] != $id_cobro) {
										$resultado_facturas[] = array('codigo_factura_lemontech' => $id_factura, 'resultado_factura' => 'Error: la factura indicada no pertenece al cobro');
									} else {
										$factura->Edit('numero', $datos_factura['numero']);
										$factura->Edit('serie_documento_legal', $datos_factura['serie']);
										$factura->Edit('comprobante_erp', $datos_factura['comprobante_erp']);
										if ($factura->Write()) {
											$resultado_facturas[] = array('codigo_factura_lemontech' => $id_factura, 'resultado_factura' => 'Numero ingresado');
										} else {
											$resultado_facturas[] = array('codigo_factura_lemontech' => $id_factura, 'resultado_factura' => 'Error de ingreso');
										}
									}
								} else {
									$resultado_facturas[] = array('codigo_factura_lemontech' => $id_factura, 'resultado_factura' => 'Error: no existe factura');
									$id_factura = '';
								}
							} else {
								//Agregar nueva factura
								//$id_cobro
								$codigo_cliente = $cobro->fields['codigo_cliente'];

								$tipo = '';
								$id_tipo = '';
								//Recorro los tipos para encontrar el indicado.
								foreach ($tipo_documento_legal as $id_tdl => $tdl) {
									if ($datos_factura['tipo'] == $tdl['glosa'] || $datos_factura['tipo'] == $tdl['codigo']) {
										$id_tipo = $id_tdl;
										$tipo = $tdl['codigo'];
										break;
									}
								}
								if (!$tipo) {
									$resultado_facturas[] = array('codigo_factura_lemontech' => $id_factura, 'resultado_factura' => "Error en nueva factura: tipo no reconocido. Los tipos de documento legal son: $helper_tipo_documento_legal");
								} else {
									//parametros que se pasan a la pagina
									$p = array();

									$p['usuario'] = $usuario;
									$p['password'] = $password;

									$p['comprobante_erp'] = mysql_real_escape_string($datos_factura['comprobante_erp']);
									$p['condicion_pago'] = intval($datos_factura['condicion_pago']);

									$p['cliente'] = mysql_real_escape_string($datos_factura['cliente']);
									$p['monto_honorarios_legales'] = round(floatval($datos_factura['honorarios']), $decimales);
									$p['monto_gastos_con_iva'] = round(floatval($datos_factura['gastos_con_iva']), $decimales);
									$p['monto_gastos_sin_iva'] = round(floatval($datos_factura['gastos_sin_iva']), $decimales);
									$p['decimales'] = $decimales;

									$p['monto_neto'] = $p['monto_honorarios_legales'] + $p['monto_gastos_con_iva'] + $p['monto_gastos_sin_iva'];
									$p['porcentaje_impuesto'] = $cobro->fields['porcentaje_impuesto'];
									$p['porcentaje_impuesto_gastos'] = $cobro->fields['porcentaje_impuesto_gastos'];

									//$monto_impuesto_honorarios = $p['monto_honorarios_legales']*($p['porcentaje_impuesto']/100);
									//$monto_impuesto_gastos = $p['monto_gastos_con_iva']*($p['porcentaje_impuesto_gastos']/100);
									//$p['iva'] = $monto_impuesto_honorarios + $monto_impuesto_gastos;
									$p['iva'] = $datos_factura['impuestos'];

									$p['id_factura_padre'] = null;
									$p['fecha'] = mysql_real_escape_string($datos_factura['fecha']);
									$p['RUT_cliente'] = mysql_real_escape_string($datos_factura['rut_cliente']);
									$p['direccion_cliente'] = mysql_real_escape_string($datos_factura['direccion_cliente']);


									$p['codigo_cliente'] = null;
									if ($datos_factura['codigo_cliente'])
										$p['codigo_cliente'] = mysql_real_escape_string($datos_factura['codigo_cliente']);

									$p['id_cobro'] = $id_cobro;
									$p['id_documento_legal'] = $id_tipo;
									$p['codigo_tipo_doc'] = $tipo;
									$p['serie'] = intval($datos_factura['serie']);
									$p['numero'] = mysql_real_escape_string($datos_factura['numero']);
									$p['id_estado'] = 1;
									$p['id_moneda_factura'] = $cobro->fields['opc_moneda_total'];
									//Para Conf::GetConf($Sesion,'DesgloseFactura')=='con_desglose'
									/*
										$p['descripcion_honorarios_legales'];
										$p['monto_honorarios_legales'];
										$p['descripcion_gastos_con_iva'];
										$p['monto_gastos_con_iva'];
										$p['descripcion_gastos_sin_iva'];
										$p['monto_gastos_sin_iva'];
										$p['total'];
										$p['iva_hidden'];
									 */
									$p['total'] = $p['monto_neto'] + $p['iva'];
									$p['descripcion'] = $datos_factura['descripcion'];
									$p['letra'] = '';

									$resultado = AgregarFactura($p);

									if ($resultado['id_factura']) {
										$id_factura = $resultado['id_factura'];
										$resultado_facturas[] = array('codigo_factura_lemontech' => $id_factura, 'resultado_factura' => 'Factura guardada con éxito');
									}
									else
										$resultado_facturas[] = array('codigo_factura_lemontech' => '-', 'resultado_factura' => $resultado['error']);
								}
								//Ingresar nueva factura?
							}
							//Terminó ingreso de factura correcto, ingreso pago.
							if ($datos_factura['ListaPagos']) {
								foreach ($datos_factura['ListaPagos'] as $datos_pago) {
									//print_r($datos_pago);

									if (!$id_factura) {
										$resultado_pagos[] = array('id_pago' => $datos_pago['id'], 'resultado_pago' => "Error en nuevo pago: No se existe la factura.");
										break;
									}

									$error_pago = '';

									$id_contabilidad = intval($datos_pago['id']);
									$cuenta = mysql_real_escape_string($datos_pago['cuenta']);

									//Reviso si existe (y obtengo) la moneda del pago.
									if (!in_array($datos_pago['moneda'], $lista_moneda))
										$error_pago = 'debe ingresar una moneda del listado: ' . $helper_moneda . '.';
									else {
										$id_moneda_pago = array_keys($lista_moneda, $datos_pago['moneda']);
										$id_moneda_pago = $id_moneda_pago[0];

										//Reviso el banco
										if (!in_array($datos_pago['banco'], $banco)) {
											$error_pago = 'debe ingresar un banco del listado: ' . $helper_banco . '.';
										} else {
											$id_banco = array_keys($banco, $datos_pago['banco']);
											$id_banco = $id_banco[0];

											//Reviso que la cuenta exista para la moneda.
											$query_cuenta =
													"SELECT cuenta_banco.id_cuenta, cuenta_banco.numero AS NUMERO, CONCAT( cuenta_banco.numero, IF( prm_moneda.glosa_moneda IS NOT NULL , CONCAT('(',prm_moneda.codigo,')'),  '' ) ) AS NUMERO_MONEDA,
											cuenta_banco.id_moneda
											FROM cuenta_banco
											LEFT JOIN prm_moneda ON prm_moneda.id_moneda = cuenta_banco.id_moneda
											WHERE id_banco = '$id_banco'";
											$resp_cuenta = mysql_query($query_cuenta, $Sesion->dbh) or Utiles::errorSQL($query_cuenta, __FILE__, __LINE__, $Sesion->dbh);

											$id_cuenta_encontrada = '';
											$otras_cuentas_banco = array();
											while (list( $id_cuenta, $numero, $numero_moneda, $id_moneda_cuenta) = mysql_fetch_array($resp_cuenta)) {
												if ($numero == $cuenta && ($id_moneda_cuenta == $id_moneda_pago || !$id_moneda_cuenta))
													$id_cuenta_encontrada = $id_cuenta;
												else
													$otras_cuentas_banco[] = $numero_moneda;
											}
											if (!$id_cuenta_encontrada) {
												$error_pago = "no existe la cuenta N° $cuenta ($datos_pago[moneda]), $datos_pago[banco] posee las siguientes cuentas: " . join(', ', $otras_cuentas_banco);
											}
										}
									}//Fin revision moneda

									if (!in_array($datos_pago['concepto'], $concepto))
										$error_pago = 'debe ingresar un concepto del listado: ' . $helper_concepto . '.';
									else {
										$id_concepto = array_keys($concepto, $datos_pago['concepto']);
										$id_concepto = $id_concepto[0];
									}

									if (!in_array($datos_pago['tipo_documento'], $tipo_pago))
										$error_pago = 'debe ingresar un tipo de documento del listado: ' . $helper_tipo_pago . '.';

									if (!$id_contabilidad)
										$error_pago = 'debe ingresar el id del pago.';

									if ($error_pago) {
										$resultado_pagos[] = array('id_pago' => $datos_pago['id'], 'resultado_pago' => "Error en nuevo pago: $error_pago");
									} else {
										$p = array(); //parametros

										$p['usuario'] = $usuario;
										$p['password'] = $password;

										$p['codigo_cliente_factura'] = mysql_real_escape_string($cobro->fields['codigo_cliente']);
										//Monto del documento
										$p['monto'] = floatval($datos_pago['monto']);
										//Lo que paga:
										$p['monto_moneda_cobro'] = floatval($datos_pago['monto_pagado']);
										$p['id_moneda'] = $id_moneda_pago;

										$p['id_moneda_cobro'] = $cobro->fields['opc_moneda_total'];
										$p['tipo_doc'] = mysql_real_escape_string($datos_pago['tipo_documento']);
										$p['numero_doc'] = intval(mysql_real_escape_string($datos_pago['numero_documento']));
										$p['numero_cheque'] = intval(mysql_real_escape_string($datos_pago['numero_cheque']));
										$p['glosa_documento'] = mysql_real_escape_string($datos_pago['descripcion']);
										$p['id_banco'] = $id_banco;
										$p['id_cuenta'] = $id_cuenta_encontrada;
										if ($datos_pago['pago_retencion'])
											$p['pago_retencion'] = true;
										$p['id_concepto'] = $id_concepto;
										$p['id_factura'] = $id_factura;
										$p['id_contabilidad'] = $id_contabilidad;

										$resultado_pagos[] = AgregarPagoFactura($p, $Sesion);
									}
								}
							}
						}
			}
			else {
				$r = 'Error: no existe cobro';
			}

			$resultado_cobros[] = array('id_cobro' => $id_cobro, 'resultado' => $r, 'ResultadoFacturas' => $resultado_facturas, 'ResultadoPagos' => $resultado_pagos);
		}
		return $resultado_cobros;
	}
	return new soap_fault('Client', '', 'Usuario o contraseña incorrecta.', '');
}

$server->register('ListaDocumentosPagos', array('usuario' => 'xsd:string', 'password' => 'xsd:string', 'timestamp' => 'xsd:integer'), array('lista_documentos_pagos' => 'tns:ListaDocumentosPagos'), $ns);

function ListaDocumentosPagos($usuario, $password, $timestamp) {
	$Sesion = new Sesion();
	$time = mktime();

	//Mapeo usernames a centro_de_costos
	$username_centro_de_costo = array();
	if (!UtilesApp::VerificarPasswordWebServices($usuario, $password)) {
		return new soap_fault('Client', '', 'Usuario o contraseña incorrecta.', '');
	}

	/**
	 * Esta query une los pagos adelantados y los pagos directos a las facturas
	 */
	$query_pagos = "
			(SELECT
				fp.id_factura_pago AS id,
				fp.fecha,
				fp.monto,
				0 AS saldo_pago,
				prm_moneda.codigo AS moneda,
				fp.tipo_doc AS tipo_documento,
				fp.nro_documento AS numero_documento,
				fp.nro_cheque AS numero_cheque,
				fp.descripcion,
				prm_banco.nombre AS banco,
				cuenta_banco.glosa AS cuenta,
				fp.pago_retencion,
				pfpc.glosa AS concepto,
				dp.id_factura_pago,
				dp.es_adelanto
			FROM documento dp
			LEFT JOIN factura_pago fp ON fp.id_factura_pago = dp.id_factura_pago
			LEFT JOIN prm_moneda ON prm_moneda.id_moneda = fp.id_moneda
			LEFT JOIN prm_banco ON prm_banco.id_banco = fp.id_banco
			LEFT JOIN cuenta_banco ON cuenta_banco.id_cuenta = fp.id_cuenta
			LEFT JOIN prm_factura_pago_concepto pfpc ON pfpc.id_concepto = fp.id_concepto
			WHERE
				dp.tipo_doc <> 'N'
				AND dp.es_adelanto = 0
				AND dp.id_factura_pago IS NOT NULL
				AND dp.fecha >= FROM_UNIXTIME($timestamp)
		)
		UNION
		(
			SELECT
				dp.id_documento AS id,
				dp.fecha,
				-1 * dp.monto AS monto,
				-1 * dp.saldo_pago AS saldo_pago,
				prm_moneda.codigo AS moneda,
				dp.tipo_doc AS tipo_documento,
				dp.numero_doc AS numero_documento,
				dp.numero_cheque AS numero_cheque,
				dp.glosa_documento AS descripcion,
				prm_banco.nombre AS banco,
				cuenta_banco.glosa AS cuenta,
				dp.pago_retencion,
				'' AS concepto,
				0 AS id_factura_pago,
				dp.es_adelanto
			FROM documento dp
			LEFT JOIN prm_moneda ON prm_moneda.id_moneda = dp.id_moneda
			LEFT JOIN prm_banco ON prm_banco.id_banco = dp.id_banco
			LEFT JOIN cuenta_banco ON cuenta_banco.id_cuenta = dp.id_cuenta
			WHERE
				dp.tipo_doc <> 'N'
				AND dp.es_adelanto = 1
				AND dp.fecha >= FROM_UNIXTIME($timestamp)
		)";

	$result_pagos = mysql_query($query_pagos, $Sesion->dbh) or Utiles::errorSQL($query_pagos, __FILE__, __LINE__, $Sesion->dbh);
	$documentos_pagos = array();
	while ($dp = mysql_fetch_assoc($result_pagos)) {

		$query_facturas_pagadas = "
				SELECT
					f.id_cobro,
					f.id_factura AS codigo_factura_lemontech,
					f.codigo_cliente,
					f.comprobante_erp,
					f.condicion_pago,
					f.serie_documento_legal AS serie,
					f.numero,
					prm_documento_legal.glosa AS tipo,
					prm_estado_factura.glosa,
					prm_estado_factura.codigo,
					f.subtotal_sin_descuento,
					f.honorarios,
					movimiento_factura.saldo AS saldo,
					f.subtotal_gastos AS gastos_con_iva,
					f.subtotal_gastos_sin_impuesto AS gastos_sin_iva,
					f.iva AS impuestos,
					f.cliente,
					f.RUT_cliente AS rut_cliente,
					f.direccion_cliente,
					f.fecha,
					f.descripcion,
					movimiento_neteo.monto_pago
				FROM factura_pago fp
					LEFT JOIN cta_cte_fact_mvto movimiento_pago ON movimiento_pago.id_factura_pago = fp.id_factura_pago
						LEFT JOIN cta_cte_fact_mvto_neteo movimiento_neteo ON movimiento_neteo.id_mvto_pago = movimiento_pago.id_cta_cte_mvto
					LEFT JOIN cta_cte_fact_mvto movimiento_factura ON movimiento_factura.id_cta_cte_mvto = movimiento_neteo.id_mvto_deuda
				LEFT JOIN factura f ON f.id_factura = movimiento_factura.id_factura
					";

		if ($dp['es_adelanto']) {
			$query_facturas_pagadas .= " JOIN neteo_documento ON neteo_documento.id_neteo_documento = fp.id_neteo_documento_adelanto ";
		}

		$query_facturas_pagadas .= "
				JOIN prm_documento_legal ON f.id_documento_legal = prm_documento_legal.id_documento_legal
				JOIN prm_estado_factura ON f.id_estado = prm_estado_factura.id_estado
				WHERE ";

		if ($dp['es_adelanto']) {
			$query_facturas_pagadas .= " neteo_documento.id_documento_pago = {$dp['id']} ";
		} else {
			$query_facturas_pagadas .= " fp.id_factura_pago = {$dp['id_factura_pago']} ";
		}

		$query_facturas_pagadas .= " GROUP BY f.id_factura";

		$result_facturas_pagadas = mysql_query($query_facturas_pagadas, $Sesion->dbh) or Utiles::errorSQL($query_facturas_pagadas, __FILE__, __LINE__, $Sesion->dbh);
		$facturas_pagadas = array();
		while ($fp = mysql_fetch_assoc($result_facturas_pagadas)) {
			$facturas_pagadas[] = $fp;
		}

		$dp['ListaFacturasPagadas'] = $facturas_pagadas;

		$documentos_pagos[] = $dp;
	}

	return $documentos_pagos;

}

$server->register(
	'ListaGastos',
	array('usuario' => 'xsd:string', 'password' => 'xsd:string', 'timestamp' => 'xsd:integer'),
	array('lista_gastos' => 'tns:ListaGastos'),
	$ns);

function ListaGastos($usuario, $password, $timestamp) {
	$Sesion = new Sesion();
	$time = mktime();

	//Mapeo usernames a centro_de_costos
	$username_centro_de_costo = array();
	if (!UtilesApp::VerificarPasswordWebServices($usuario, $password)) {
		return new soap_fault('Client', '', 'Usuario o contraseña incorrecta.', '');
	}

	$Gasto = new Gasto($Sesion);
	$where = " fecha >= FROM_UNIXTIME($timestamp) ";
	$query_gastos = $Gasto->SearchQuery($Sesion, $where);

	$result_gastos = $Sesion->pdodbh->query($query_gastos)->fetchAll(PDO::FETCH_ASSOC);

	$factor_impuesto = Conf::GetConf($Sesion, 'ValorImpuestoGastos') / 100;

	$gastos = array();

	foreach ($result_gastos as $gasto) {
		$con_impuesto = $gasto['con_impuesto'] == 'SI';
		$impuesto = $gasto['monto_cobrable'] * ($con_impuesto ? $factor_impuesto : 0);
		$impuesto = number_format($impuesto, 2, '.', '');

		$gasto_ws = array(
			'id' => $gasto['id_movimiento'],
			'usuario_ingresa' => '' . $gasto['usuarername_ingresa'],
			'usuario_ordena' => '' . $gasto['usuarername_ordena'],
			'codigo_cliente' => '' . $gasto['codigo_cliente'],
			'codigo_asunto' => '' . $gasto['codigo_asunto'],
			'descripcion' => '' . $gasto['descripcion'],
			'glosa_gasto' => '' . $gasto['tipo'],
			'ingreso' => $gasto['ingreso'],
			'egreso' => $gasto['egreso'],
			'codigo_moneda' => '' . $gasto['codigo_moneda'],
			'fecha' => '' . $gasto['fecha'],
			'cobrable' => $gasto['cobrable'] == 'SI',
			'monto_cobrable' => $gasto['monto_cobrable'],
			'impuesto' => $impuesto,
			'numero_documento' => '' . $gasto['numero_documento'],
			'numero_ot' => '' . $gasto['numero_ot'],
			'con_impuesto' => $con_impuesto,
			'codigo_proveedor' => '' . $gasto['rut_proveedor'],
			'nombre_proveedor' => '' . $gasto['nombre_proveedor'],
			'tipo_documento_asociado' => '' . $gasto['tipo_documento_asociado'],
			'codigo_documento_asociado' => '' . $gasto['codigo_documento_asociado'],
		);

		$gastos[] = $gasto_ws;
	}

	return $gastos;

}

#Then we invoke the service using the following line of code:

$server->service($HTTP_RAW_POST_DATA);
#In fact, appending "?wsdl" to the end of any PHP NuSOAP server file will dynamically produce WSDL code. Here's how our CanadaTaxCalculator Web service is described using WSDL:
<?php

require_once(dirname(__FILE__) . '/../conf.php');
ini_set('soap.wsdl_cache_enabled', 0);

class WsFacturacionSatcom extends WsFacturacion {
	protected $url = '';

	public function __construct($url) {
		$this->url = $url;

		$this->Client = new SoapClient(
			"{$this->url}?WSDL",
			array(
				"trace" => TRUE,
				"exception" => 0,
				"soap_version" => SOAP_1_2
			)
		);
	}

	private function setHeaders($action) {
		$prefix = 'http://tempuri.org/IBridge/';
		$this->Client->__setSoapHeaders(
			array(
				new SoapHeader('http://www.w3.org/2005/08/addressing', 'Action', $prefix . $action),
				new SoapHeader('http://www.w3.org/2005/08/addressing', 'To', $this->url)
			)
		);
	}

	public function emitirFactura($factura) {
		$id_comprobante = 0;
		$documento_xml = $this->crearXML($factura);

		$ProcesarComprobante = new stdClass();
		$ProcesarComprobante->strRequerimiento = $documento_xml;
		$ProcesarComprobante->Comprimido = false;

		try {
			$this->setHeaders('ProcesarComprobante');
			$respuesta = $this->Client->ProcesarComprobante($ProcesarComprobante);

			$estado_proceso = $respuesta->ProcesarComprobanteResult->EstadoProceso;

			if ($estado_proceso == 'Autorizado') {
				$id_comprobante = $respuesta->ProcesarComprobanteResult->IdComprobanteSAT;
			} else if ($estado_proceso == 'DuplicadoSatcom') {
				$this->setError(1, 'El número de documento se encuentra duplicado en Satcom');
			} else {
				// Error, NoAutorizado, ErrorEstructuraXml
				Log::write($documento_xml, 'FacturacionElectronicaSatcom');
				Log::write($respuesta->ProcesarComprobanteResult->MensajeError, 'FacturacionElectronicaSatcom');
				$this->setError(1, "Ocurrió un error inesperado con Satcom [$estado_proceso]");
			}
		} catch(SoapFault $fault) {
			Log::write($documento_xml, 'FacturacionElectronicaSatcom');
			Log::write(print_r($fault, true), 'FacturacionElectronicaSatcom');
			$this->setError(1, 'Ocurrió un error inesperado con Satcom');
		}

		return $id_comprobante;
	}

	private function crearXML($factura) {
		$requerimiento = new SimpleXMLElement('<Requerimiento/>');

		$requerimiento->addChild('Codigo', '01');
		$requerimiento->addChild('Descripcion', 'FACTURA');

		// NumeroDocumento debe contener al menos 9 dígitos
		$requerimiento->addChild('NumeroDocumento', $factura->fields['numero']);

		$requerimiento->addChild('FechaEmision', str_replace(' ', 'T', $factura->fields['fecha_creacion']));
		// $requerimiento->addChild('PeridodoFiscal');

		$impuestos = $requerimiento->addChild('Impuestos');
		$impuesto = $impuestos->addChild('Impuesto');
		$impuesto->addChild('CodigoImpuesto', 2);
		$impuesto->addChild('Impuesto', 'IVA');
		$impuesto->addChild('CodigoPorcentaje', 2);
		$impuesto->addChild('Porcentaje', $factura->fields['porcentaje_impuesto']);
		$impuesto->addChild('BaseImponible', $factura->fields['subtotal']);
		$impuesto->addChild('Valor', $factura->fields['iva']);

		$informacion_adicional = $requerimiento->addChild('InformacionAdicional');
		$campo = $informacion_adicional->addChild('Campo');
		$campo->addAttribute('id', 1);
		$campo->addChild('Descripcion', 'Honorarios legales');
		$campo->addChild('Valor', $factura->fields['descripcion']);

		// $requerimiento->addChild('Reprocesos', 0);
		// $requerimiento->addChild('EstadoComprobante', 'Autorizado');
		$requerimiento->addChild('ClaveAcceso', $factura->fields['ClaveAcceso']);
		// $requerimiento->addChild('Propina', 0.90);
		// $requerimiento->addChild('NumeroAutorizacion', 1502201622075405917148990014338182163);
		// $requerimiento->addChild('FechaAutorizacion', date('Y-m-d\TH:i:s'));
		// $requerimiento->addChild('IdRequerimiento', 504579351630773);
		// $requerimiento->addChild('CodigoEmisor', 12);
		$requerimiento->addChild('RucEmisor', $factura->fields['RucEmisor']);
		// $requerimiento->addChild('TipoEmision', 1);
		$requerimiento->addChild('Establecimiento', $factura->fields['Establecimiento']);
		$requerimiento->addChild('Punto', $factura->fields['Punto']);
		$requerimiento->addChild('Moneda', 'DOLAR');
		$requerimiento->addChild('Ambiente', 2);
		// $requerimiento->addChild('Version', '');
		$requerimiento->addChild('TotalConImpuestos', $factura->fields['total']);
		$requerimiento->addChild('TotalSinImpuestos', $factura->fields['subtotal']);
		// $requerimiento->addChild('TotalRetencion', 0);

		$cliente = $requerimiento->addChild('Cliente');
		$cliente->addChild('RazonSocial', $factura->fields['cliente']);

		/**
		 * TipoIdentificacion
		 * 04 - RUC (Ej. 1790011062001)
		 * 05 - Cedula (Ej. 1715625198)
		 * 06 - Pasaporte (Ej. ECU12345)
		 * 07 - Consumidor Final (9999999999999)
		 * 08 - Identificación del Exterior (COL-A1234565-2563245)
		 */
		$cliente->addChild('TipoIdentificacion', '04');

		$cliente->addChild('NumeroIdentificacion', $factura->fields['RUT_cliente']);
		// $cliente->addChild('email', '');
		// $cliente->addChild('Telefono', '');

		$detalles = $requerimiento->addChild('Detalles');
		$detalle = $detalles->addChild('Detalle');
		$detalle->addAttribute('id', 1);
		$detalle->addChild('Cantidad', 1);
		$detalle->addChild('Descuento', '0.00');
		$detalle->addChild('SubTotal', $factura->fields['subtotal']);

		$producto = $detalle->addChild('Producto');
		$producto->addChild('Codigo', 1);
		$producto->addChild('CodigoAuxiliar', 1);
		$producto->addChild('Descripcion', 'Honorarios legales');
		$producto->addChild('ValorUnitario', $factura->fields['honorarios']);

		$impuestos = $detalle->addChild('Impuestos');
		$impuesto = $impuestos->addChild('Impuesto');
		$impuesto->addChild('CodigoImpuesto', 2);
		$impuesto->addChild('Impuesto', 'IVA');
		$impuesto->addChild('CodigoPorcentaje', 2);
		$impuesto->addChild('Porcentaje', $factura->fields['porcentaje_impuesto']);
		$impuesto->addChild('BaseImponible', $factura->fields['subtotal']);
		$impuesto->addChild('Valor', $factura->fields['iva']);

		return $requerimiento->asXML();
	}

	public function obtenerPdf($id_comprobante) {
		$pdf = '';

		$ConsultaPDFAutorizadoByID = new stdClass();
		$ConsultaPDFAutorizadoByID->IdComprobante = $id_comprobante;

		try {
			$this->setHeaders('ConsultaPDFAutorizadoByID');
			$respuesta = $this->Client->ConsultaPDFAutorizadoByID((array) $ConsultaPDFAutorizadoByID);
			$pdf = $respuesta->ConsultaPDFAutorizadoByIDResult;
		} catch(Exception $ex) {
			Log::write($id_comprobante, 'FacturacionElectronicaSatcom');
			Log::write($ex->getMessage(), 'FacturacionElectronicaSatcom');
			$this->setError(1, 'Ocurrió un error inesperado con Satcom');
		}

		return $pdf;
	}
}

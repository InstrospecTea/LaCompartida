<?php

require_once(dirname(__FILE__) . '/../conf.php');
ini_set('soap.wsdl_cache_enabled', 0);

class WsFacturacionSatcom extends WsFacturacion {
	protected $url = 'http://190.108.68.38:9010/Bridge/WcfBridge.svc';
	protected $action = 'http://tempuri.org/IBridge/ProcesarComprobante';

	public function __construct() {
		$this->Client = new SoapClient(
			"{$this->url}?WSDL",
			array(
				"trace" => TRUE,
				"exception" => 0,
				"soap_version" => SOAP_1_2
			)
		);
	}

	public function emitirFactura($factura) {
		$documento_xml = $this->crearXML($factura);

		$id_comprobante = 0;

		$this->Client->__setSoapHeaders(
			array(
				new SoapHeader('http://www.w3.org/2005/08/addressing', 'Action', $this->action),
				new SoapHeader('http://www.w3.org/2005/08/addressing', 'To', $this->url)
			)
		);

		$ProcesarComprobante = new stdClass();
		$ProcesarComprobante->strRequerimiento = $documento_xml;
		$ProcesarComprobante->Comprimido = false;

		try {
			$respuesta = $this->Client->ProcesarComprobante($ProcesarComprobante);

			if ($respuesta->ProcesarComprobanteResult->EstadoProceso != 'Error') {
				$id_comprobante = $respuesta->ProcesarComprobanteResult->IdComprobanteSAT;
			} else {
				Log::write($documento_xml, 'FacturacionElectronicaSatcom');
				Log::write($respuesta->ProcesarComprobanteResult->MensajeError, 'FacturacionElectronicaSatcom');
				$this->setError(1, 'Ocurrió un error inesperado en la generación del documento con Satcom');
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
		$requerimiento->addChild('NumeroDocumento', $factura->fields['numero']);
		$requerimiento->addChild('FechaEmision', str_replace(' ', 'T', $factura->fields['fecha_creacion']));
		// $requerimiento->addChild('PeridodoFiscal');

		$impuestos = $requerimiento->addChild('Impuestos');
		$impuesto = $impuestos->addChild('Impuesto');
		$impuesto->addChild('CodigoImpuesto', 2);
		$impuesto->addChild('Impuesto', 'IVA');
		$impuesto->addChild('CodigoPorcentaje', 2);
		$impuesto->addChild('Porcentaje', 12);
		$impuesto->addChild('BaseImponible', '9.02');
		$impuesto->addChild('Valor', '1.08');

		$informacion_adicional = $requerimiento->addChild('InformacionAdicional');
		$campo = $informacion_adicional->addChild('Campo');
		$campo->addAttribute('id', 1);
		$campo->addChild('Descripcion', 'CAJERO');
		$campo->addChild('Valor', '509. Elizabet Garcia');

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
		$requerimiento->addChild('TotalConImpuestos', '11.00');
		$requerimiento->addChild('TotalSinImpuestos', '9.02');
		// $requerimiento->addChild('TotalRetencion', 0);

		$cliente = $requerimiento->addChild('Cliente');
		$cliente->addChild('RazonSocial', 'CONSUMIDOR FINAL');
		$cliente->addChild('TipoIdentificacion', '07');
		$cliente->addChild('NumeroIdentificacion', 9999999999999);
		// $cliente->addChild('email', '');
		// $cliente->addChild('Telefono', '');

		$detalles = $requerimiento->addChild('Detalles');
		$detalle = $detalles->addChild('Detalle');
		$detalle->addAttribute('id', 1);
		$detalle->addChild('Cantidad', 1);
		$detalle->addChild('Descuento', '0.00');
		$detalle->addChild('SubTotal', '9.02');

		$producto = $detalle->addChild('Producto');
		$producto->addChild('Codigo', 1008665);
		$producto->addChild('CodigoAuxiliar', 1);
		$producto->addChild('Descripcion', 'Piz Jamon');
		$producto->addChild('ValorUnitario', '9.02');

		$impuestos = $detalle->addChild('Impuestos');
		$impuesto = $impuestos->addChild('Impuesto');
		$impuesto->addChild('CodigoImpuesto', 2);
		$impuesto->addChild('Impuesto', 'IVA');
		$impuesto->addChild('CodigoPorcentaje', 2);
		$impuesto->addChild('Porcentaje', 12);
		$impuesto->addChild('BaseImponible', '9.02');
		$impuesto->addChild('Valor', '1.08');

		return $requerimiento->asXML();
	}

	public function obtenerPdf() {
		$pdf = '';

		return $pdf;
	}
}

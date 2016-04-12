<?php

require_once(dirname(__FILE__) . '/../conf.php');
ini_set('soap.wsdl_cache_enabled', 0);

class WsFacturacionSatcom extends WsFacturacion {
	protected $url = 'http://190.108.68.38:9010/Bridge/WcfBridge.svc';

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

	public function emitirFactura($dataFactura) {
		$xml = '<![CDATA[<?xml version="1.0" encoding="utf-8"?><Requerimiento></Requerimiento>]]>';
		$actionHeader = array();

		try {
			$actionHeader[] = new SoapHeader(
				'http://www.w3.org/2005/08/addressing',
				'Action',
				'http://tempuri.org/IBridge/ProcesarComprobante'
			);

			$actionHeader[] = new SoapHeader(
				'http://www.w3.org/2005/08/addressing',
				'To',
				$this->url
			);

			$this->Client->__setSoapHeaders($actionHeader);

			$ProcesarComprobante  = new stdClass();
			$ProcesarComprobante->strRequerimiento = $xml;
			$ProcesarComprobante->Comprimido = false;

			$response = $this->Client->ProcesarComprobante($ProcesarComprobante);
		} catch(SoapFault $fault) {
			echo $fault;
			echo $this->Client->__getLastRequest();
		}

		var_dump($response); exit;
	}
}

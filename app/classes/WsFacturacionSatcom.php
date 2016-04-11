<?php

require_once(dirname(__FILE__) . '/../conf.php');
ini_set('soap.wsdl_cache_enabled', 0);

class WsFacturacionSatcom extends WsFacturacion {
	protected $url = 'http://190.108.68.38:9005/SatcomWS.asmx';

	public function __construct() {
		$this->Client = new SoapClient($this->url, array('trace' => 1));
	}

	public function emitirFactura($dataFactura) {
		$x = $this->Client->recibeInfo();
		var_dump($x); exit;
	}
}

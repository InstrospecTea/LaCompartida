<?php

require_once(dirname(__FILE__) . '/../conf.php');

class WsFacturacionMateriaSoftware {
	protected $client;

	public function __construct($url) {
		$this->client = new \GuzzleHttp\Client(['base_url' => $url]);
	}

	private function getHeaders() {
		return [
			'content-type' => 'application/json',
			'Accept' => 'application/json',
			'Authorization' => ''
		];
	}

	public function emitirFactura(Factura $factura) {

		try {
			$respuesta = $this->client->request(
				'POST',
				'/documento',
				$this->getHeaders(),
				$this->getBody($factura)
			);
			print_r($respuesta);
			exit;
		} catch(SoapFault $fault) {
			Log::write($documento_xml, 'FacturacionElectronicaMateriaSoftware');
			Log::write(print_r($fault, true), 'FacturacionElectronicaMateriaSoftware');
			$this->setError(1, 'Ocurrió un error inesperado con Materia Software');
		}

		return $id_comprobante;
	}

	private function getBody($factura) {
		return '{}';
	}

	public function obtenerPdf($id_comprobante) {
		$pdf = '';

		try {

		} catch(Exception $ex) {
			Log::write($id_comprobante, 'FacturacionElectronicaMateriaSoftware');
			Log::write($ex->getMessage(), 'FacturacionElectronicaMateriaSoftware');
			$this->setError(1, 'Ocurrió un error inesperado con Materia Software');
		}

		return $pdf;
	}
}

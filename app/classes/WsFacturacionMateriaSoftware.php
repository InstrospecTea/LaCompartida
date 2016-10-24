<?php

require_once(dirname(__FILE__) . '/../conf.php');

class WsFacturacionMateriaSoftware extends WsFacturacion {

	private $default_message_error;
	private $token;

	public function __construct($url, $token) {
		$this->Client = new \GuzzleHttp\Client();

		$this->url = $url;
		$this->token = $token;
		$this->default_message_error = 'Ocurrió un error inesperado con Materia Software: ';
	}

	private function getHeaders() {
		return [
			'content-type' => 'application/json',
			'Accept' => 'application/json',
			'Authorization' => $this->token
		];
	}

	public function emitirFactura(Factura $Factura) {
		$factura_emitida = '';

		try {
			$respuesta = $this->Client->request(
				'POST',
				"{$this->url}/documento",
				[
					'headers' => $this->getHeaders(),
					'body' => $this->getBody($Factura)
				]
			);

			$factura_emitida = $respuesta->getBody();

		} catch (GuzzleHttp\Exception\ServerException $e) {
			$response = $e->getResponse();
			$json_response = json_decode($response->getBody()->getContents());
			$this->setError(1, $this->default_message_error . $json_response->ExceptionMessage);
		} catch(GuzzleHttp\Exception\ClientException $e) {
			$response = $e->getResponse();
			$json_response = json_decode($response->getBody()->getContents());
			$this->setError(1, $this->default_message_error . $json_response->ExceptionMessage);
		}

		return $factura_emitida;
	}

	private function getBody(&$Factura) {
		$body = [
			'Cliente' => [
				'NumeroDeDocumento' => (string) $Factura->fields['numero'],
				'Nombre' => (string) $Factura->fields['cliente'],
				'Email' => '',
				'DireccionCompleta' => utf8_encode("{$Factura->fields['direccion_cliente']}, {$Factura->fields['comuna_cliente']}"),
				'TipoDocumento' => 0
			],
			'IsExportacion' => true,
			'ThirdPartyUniqueIdentifier' => (string) $Factura->fields['numero'],
			'TipoDeCambio' => 0,
			'Documento' => [
				'Serie' => (string) $Factura->fields['serie_documento_legal'],
				'TipoDeDocumento' => 10,
				'Descripcion' => (string) $Factura->fields['glosa']
			],
			'FechaDeVencimiento' => "{$Factura->fields['fecha_vencimiento']}T00:00:00",
			'DetraccionPercent' => 0,
			'TotalDetraccion' => 0,
			'OverrideTotalValorVentaOperacionesGravadas' => 0,
			'OverrideTotalValorVentaOperacionesExoneradas' => 0,
			'OverrideTotalValorVentaOperacionesInafectas' => 0,
			'OverrideTotalDescuentos' => 0,
			'OverrideTotalBonificaciones' => 0,
			'OverrideTotalIGV' => 0,
			'OverrideTotalISC' => 0,
			'OverrideTotalGlobal' => 0,
			'MonedaISOCode' => '',
			'Items' => [
				[
					'IsService' => true,
					'Codigo' => '',
					'Descripcion' => (string) utf8_encode($Factura->fields['descripcion']),
					'DescuentoAmount' => 0,
					'ValorReferencial' => 0,
					'ValorUnitario' => (double) $Factura->fields['monto_neto'],
					'IGVDeLinea' => 0,
					'ISCDeLinea' => 0,
					'PrecioUnitario' => 0,
					'PrecioUnitarioReferencial' => 0,
					'Quantity' => 1,
					'TipoAfectacionIGV' => 0,
					'TotalConImpuestos' => (double) $Factura->fields['total'],
					'TotalSinImpuestos' => (double) $Factura->fields['monto_neto'],
					'Unidad' => '',
					'LoteID' => ''
				],
			]
		];

		TTB\Debug::pr(json_encode($body, JSON_PRETTY_PRINT));

		return json_encode($body);
	}

	public function obtenerPdf($id_comprobante) {
		$pdf = '';

		try {
		} catch(Exception $ex) {
			$this->setError(1, $this->default_message_error);
		}

		return $pdf;
	}
}

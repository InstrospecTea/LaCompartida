<?php

require_once(dirname(__FILE__) . '/../conf.php');

class WsFacturacionMateriaSoftware extends WsFacturacion {

	private $default_message_error;
	private $token;
	private $body_invoice;

	public function __construct($url, $token) {
		$this->url = $url;
		$this->token = $token;
		$this->default_message_error = 'Ocurrió un error inesperado con Materia Software: ';
		$this->body_invoice = null;
	}

	private function getHeaders() {
		return [
			'Accept: application/json',
			'Content-Type: application/json',
			"Authorization: {$this->token}"
		];
	}

	public function documento(Factura $Factura, Moneda $Moneda, PrmDocumentoLegal $DocumentoLegal, Contrato $Contrato) {
		$this->generateBodyInvoice($Factura, $Moneda, $DocumentoLegal, $Contrato);

		$response = $this->sendData('POST', "{$this->url}/documento", json_encode($this->body_invoice));
		$documento = json_decode($response);

		// ocurrió un error
		if (isset($documento->Message)) {
			$this->setCurlError($documento);
		}

		return $documento;
	}

	public function GetStatus($serie, $correlativo) {
		$documento = null;

		$response = $this->sendData('GET', "{$this->url}/documento/GetStatus?serie={$serie}&correlativo={$correlativo}");
		$documento = json_decode($response);

		// ocurrió un error
		if (isset($documento->Message)) {
			$this->setCurlError($documento);
		}

		return $documento;
	}

	public function PutAnular($serie, $correlativo) {
		$response = $this->sendData('PUT', "{$this->url}/documento/PutAnular?serie={$serie}&correlativo={$correlativo}");
		$documento = json_decode($response);

		// ocurrió un error
		if (isset($documento->Message)) {
			$this->setCurlError($documento);
		}

		return $documento;
	}

	private function generateBodyInvoice(&$Factura, &$Moneda, &$DocumentoLegal, &$Contrato) {
		$this->body_invoice = [
			'Cliente' => [
				'NumeroDeDocumento' => (string) $Factura->fields['RUT_cliente'],
				'Nombre' => (string) utf8_encode($Factura->fields['cliente']),
				// 'Email' => '',
				'DireccionCompleta' => utf8_encode("{$Factura->fields['direccion_cliente']}, {$Factura->fields['comuna_cliente']}"),
				'TipoDocumento' => $Contrato->fields['extranjero'] == '1' ? 0 : 6
			],
			// 'IsExportacion' => true,
			'ThirdPartyUniqueIdentifier' => (string) $Factura->fields['id_factura'],
			// 'TipoDeCambio' => 0,
			'Documento' => [
				'Serie' => (string) $Factura->fields['serie_documento_legal'],
				// 'Correlativo' => 1,
				'TipoDeDocumento' => (int) $DocumentoLegal->fields['codigo_dte'],
				'Descripcion' => (string) utf8_encode(substr($Factura->fields['glosa'], 0, 250))
			],
			'FechaDeVencimiento' => "{$Factura->fields['fecha_vencimiento']}T00:00:00",
			// 'DetraccionPercent' => 0,
			// 'TotalDetraccion' => 0,
			// 'OverrideTotalValorVentaOperacionesGravadas' => 0,
			// 'OverrideTotalValorVentaOperacionesExoneradas' => 0,
			// 'OverrideTotalValorVentaOperacionesInafectas' => 0,
			// 'OverrideTotalDescuentos' => 0,
			// 'OverrideTotalBonificaciones' => 0,
			// 'OverrideTotalIGV' => 0,
			// 'OverrideTotalISC' => 0,
			// 'OverrideTotalGlobal' => 0,
			'MonedaISOCode' => (string) $Moneda->fields['codigo'],
			'Items' => [
				[
					'IsService' => true,
					'Codigo' => '',
					'Descripcion' => (string) utf8_encode($Factura->fields['descripcion']),
					'DescuentoAmount' => 0.0,
					// 'ValorReferencial' => 0,
					'ValorUnitario' => (double) $Factura->fields['subtotal'],
					'IGVDeLinea' => (float) $Factura->fields['iva'],
					'ISCDeLinea' => 0.0,
					'PrecioUnitario' => (double) $Factura->fields['total'],
					'Quantity' => 1,
					'TipoAfectacionIGV' => $Contrato->fields['extranjero'] == '1' ? 40 :10,
					'TotalConImpuestos' => (double) $Factura->fields['total'],
					'TotalSinImpuestos' => (double) $Factura->fields['subtotal'],
					'Unidad' => 'UN',
					// 'LoteID' => '',
					// 'LoteEXP' => ''
				],
			]
		];
	}

	public function getBodyInvoice() {
		return $this->body_invoice;
	}

	private function sendData($method, $url, $post = '') {
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}

		$response = curl_exec($ch);
		curl_close($ch);

		return $response;
	}

	private function setCurlError($response) {
		// Error de servicio (como se genera el request)
		if (isset($response->MessageDetail)) {
			$this->setError(1, $this->default_message_error . $response->MessageDetail);
		}

		// Error de consumo (regla de negocio)
		if (isset($response->ExceptionMessage)) {
			$this->setError(2, $this->default_message_error . $response->ExceptionMessage);
		}
	}
}

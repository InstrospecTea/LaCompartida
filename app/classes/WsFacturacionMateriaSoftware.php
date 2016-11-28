<?php

require_once(dirname(__FILE__) . '/../conf.php');

class WsFacturacionMateriaSoftware extends WsFacturacion {

	private $default_message_error;
	private $token;
	private $body_invoice;

	public function __construct($url, $token) {
		$this->Client = new \GuzzleHttp\Client();

		$this->url = $url;
		$this->token = $token;
		$this->default_message_error = 'Ocurrió un error inesperado con Materia Software: ';
		$this->body_invoice = null;
	}

	private function getHeaders() {
		return [
			'content-type' => 'application/json',
			'Accept' => 'application/json',
			'Authorization' => $this->token
		];
	}

	public function documento(Factura $Factura, Moneda $Moneda, PrmDocumentoLegal $DocumentoLegal, Contrato $Contrato) {
		$documento = null;

		try {
			$this->generateBodyInvoice($Factura, $Moneda, $DocumentoLegal, $Contrato);

			$respuesta = $this->Client->request(
				'POST',
				"{$this->url}/documento",
				[
					'headers' => $this->getHeaders(),
					'body' => json_encode($this->body_invoice)
				]
			);

			$documento = json_decode($respuesta->getBody());
		} catch (GuzzleHttp\Exception\ServerException $e) {
			$response = $e->getResponse();
			$json_response = json_decode($response->getBody()->getContents());
			$this->setError(1, $this->default_message_error . $json_response->ExceptionMessage);
		} catch(GuzzleHttp\Exception\ClientException $e) {
			$response = $e->getResponse();
			$json_response = json_decode($response->getBody()->getContents());
			$this->setError(2, $this->default_message_error . $json_response->ExceptionMessage);
		}

		return $documento;
	}

	public function GetStatus($serie, $correlativo) {
		$documento = null;

		try {
			$respuesta = $this->Client->request(
				'GET',
				"{$this->url}/documento/GetStatus?serie={$serie}&correlativo={$correlativo}",
				['headers' => $this->getHeaders()]
			);

			$documento = json_decode($respuesta->getBody());
		} catch (GuzzleHttp\Exception\ServerException $e) {
			$response = $e->getResponse();
			$json_response = json_decode($response->getBody()->getContents());
			$this->setError(1, $this->default_message_error . $json_response->ExceptionMessage);
		} catch(GuzzleHttp\Exception\ClientException $e) {
			$response = $e->getResponse();
			$json_response = json_decode($response->getBody()->getContents());
			$this->setError(2, $this->default_message_error . $json_response->ExceptionMessage);
		}

		return $documento;
	}

	public function PutAnular($serie, $correlativo) {
		$documento = null;

		try {
			$respuesta = $this->Client->request(
				'PUT',
				"{$this->url}/documento/PutAnular?serie={$serie}&correlativo={$correlativo}",
				['headers' => $this->getHeaders()]
			);

			$documento = json_decode($respuesta->getBody());
		} catch (GuzzleHttp\Exception\ServerException $e) {
			$response = $e->getResponse();
			$json_response = json_decode($response->getBody()->getContents());
			$this->setError(1, $this->default_message_error . $json_response->ExceptionMessage);
		} catch(GuzzleHttp\Exception\ClientException $e) {
			$response = $e->getResponse();
			$json_response = json_decode($response->getBody()->getContents());
			$this->setError(2, $this->default_message_error . $json_response->ExceptionMessage);
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
}

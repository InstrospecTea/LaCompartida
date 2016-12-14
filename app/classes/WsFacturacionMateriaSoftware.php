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

	public function documento(Factura $Factura, Moneda $Moneda, PrmDocumentoLegal $DocumentoLegal, PrmTipoDocumentoIdentidad $TipoDocumentoIdentidad) {
		$this->generateBodyInvoice($Factura, $Moneda, $DocumentoLegal, $TipoDocumentoIdentidad);

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

	public function getanular($serie, $correlativo) {
		$response = $this->sendData('GET', "{$this->url}/documento/getanular?serie={$serie}&correlativo={$correlativo}");
		$documento = json_decode($response);

		// ocurrió un error
		if (isset($documento->Message)) {
			$this->setCurlError($documento);
		}

		return $documento;
	}

	private function generateBodyInvoice(&$Factura, &$Moneda, &$DocumentoLegal, &$TipoDocumentoIdentidad) {
		$porcentaje_impuesto = (int) $Factura->fields['porcentaje_impuesto'];
		$total = (double) $Factura->fields['total'];
		$subtotal = (double) $Factura->fields['subtotal'];
		$iva = (float) $Factura->fields['iva'];

		// si la factura corresponde a un gasto
		if (empty($iva) && empty($subtotal)) {
			$iva = ($total * $porcentaje_impuesto) / 100;
			$subtotal = $total - $iva;
		}

		$this->body_invoice = [
			'Cliente' => [
				'NumeroDeDocumento' => (string) $Factura->fields['RUT_cliente'],
				'Nombre' => (string) utf8_encode($Factura->fields['cliente']),
				// 'Email' => '',
				'DireccionCompleta' => utf8_encode("{$Factura->fields['direccion_cliente']}, {$Factura->fields['comuna_cliente']}"),
				'TipoDocumento' => $TipoDocumentoIdentidad->fields['codigo_dte']
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
					'ValorUnitario' => $subtotal,
					'IGVDeLinea' => $iva,
					'ISCDeLinea' => 0.0,
					'PrecioUnitario' => $total,
					'Quantity' => 1,
					'TipoAfectacionIGV' => empty($iva) && $TipoDocumentoIdentidad->fields['codigo_dte'] == '0' ? 40 : 10,
					'TotalConImpuestos' => $total,
					'TotalSinImpuestos' => $subtotal,
					'Unidad' => 'UN',
					// 'LoteID' => '',
					// 'LoteEXP' => ''
				],
			]
		];

		if (!empty($Factura->fields['id_factura_padre'])) {
			$FacturaPadre = new Factura($Factura->sesion);
			$FacturaPadre->Load($Factura->fields['id_factura_padre']);

			if ($FacturaPadre->loaded() && !empty($FacturaPadre->fields['dte_url_pdf'])) {
				$documento = json_decode($FacturaPadre->fields['dte_url_pdf']);
				$this->body_invoice['Documento']['OriginalDocumentSerie'] = $documento->Serie;
				$this->body_invoice['Documento']['OriginalDocumentCorrelativo'] = $documento->Correlativo;
			}
		}

		$Referencia = new PrmCodigo($Factura->sesion);
		$Referencia->LoadById($Factura->fields['dte_codigo_referencia']);
		$codigoReferencia = $Referencia->Loaded() ? $Referencia->fields['codigo'] : '01';

		if ($DocumentoLegal->fields['codigo'] == 'NC') {
			$this->body_invoice['Documento']['NotaDeCreditoTypeCode'] = $codigoReferencia;
		} else if ($DocumentoLegal->fields['codigo'] == 'ND') {
			$this->body_invoice['Documento']['NotaDeDebitoTypeCode'] = $codigoReferencia;
		}
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

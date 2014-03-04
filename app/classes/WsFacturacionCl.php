<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class WsFacturacioCl {

	protected $tipoCodigo;
	protected $ValorCodigo;
	protected $url = 'http://192.168.1.1:8088/wsplano.asmx?wsdl';
	protected $Client;

	public function __construct() {
		 $this->Client = new SoapClient($this->url);
	}

	/**
	 *
	 * @param type $dataFactura datos de la factura
	 * @param type $afecta indica si la factura es afecta, defaul false
	 */
	public function emitirFactura($dataFactura, $afecta = false) {
		$documento = array(
			'Encabezado' => array(
				'IdDoc' => array(
					'TipoDTE' => $afecta ? 34 : 33,
					'Folio' => 0,
					'FchEmis' => $dataFactura['fecha_emision']
				),
				'Emisor' => array(
					'RUTEmisor' => $dataFactura['emisor']['rut'],
					'RznSoc' => $dataFactura['emisor']['razon_social'],
					'GiroEmis' => $dataFactura['emisor']['giro'],
					'Acteco' => $dataFactura['emisor']['codigo_actividad'],
					'DirOrigen' => $dataFactura['emisor']['direccion'],
					'CmnaOrigen' => $dataFactura['emisor']['comuna'],
					'CiudadOrigen' => $dataFactura['emisor']['cuidad'],
				),
				'Receptor' => array(
					'RUTRecep' => $dataFactura['receptor']['rut'],
					'RznSocRecep' => $dataFactura['receptor']['razon_social'],
					'GiroRecep' => $dataFactura['receptor']['giro'],
					'DirRecep' => $dataFactura['receptor']['direccion'],
					'CmnaRecep' => $dataFactura['receptor']['comuna'],
					'CiudadRecep' => $dataFactura['receptor']['cuidad']
				),
				'Totales' => array(
					'MntNeto' => $dataFactura['monto_neto'],
					'TasaIVA' => $dataFactura['tasa_iva'],
					'IVA' => $dataFactura['monto_iva'],
					'MntTotal' => $dataFactura['monto_total']
				)
			)
		);

		$documento['Detalle'] = array();
		$lin = 0;
		foreach ($dataFactura['detalle'] as $detalle) {
			$documento['Detalle'][] = array(
				'NroLinDet' => ++$lin,
				'CdgItem' => array(
					'TpoCodigo' => $this->tipoCodigo,
					'VlrCodigo' => $this->ValorCodigo
				),
				'NmbItem' => $detalle['descripcion'],
				'QtyItem' => $detalle['cantidad'],
				'PrcItem' => $detalle['precio_unitario'],
				'MontoItem' => $detalle['cantidad'] * $detalle['precio_unitario']
			);
		}

		return $this->enviarDocumento($documento);
	}

	private function enviarDocumento($arrayFactura) {
		$respuesta = $this->Client->enviar($arrayFactura);
		return self::parseRepply($respuesta);
	}

	private static function parseRepply($respuesta) {
		$result->codigo = 201;
		$result->descripcion = '';
		$result->timbrefiscal = '';
		$result->documentopdf = '';
		if (error()) {
			$result->descripcion = '';
		}
	}

}
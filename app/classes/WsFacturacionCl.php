<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class WsFacturacioCl {

	protected $tipoCodigo;
	protected $ValorCodigo;

	public function emitirFactura($dataFactura) {
		$documento = array(
			'Encabezado' => array(
				'IdDoc' => array(
					'TipoDTE' => 34,
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

		$this->enviarDocumento($documento);
	}

	private function enviarDocumento($arrayFactura) {
		$respuesta = $this->Client->enviar($arrayFactura);
		return $respuesta;
	}

}
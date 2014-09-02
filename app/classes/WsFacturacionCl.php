<?php

require_once(dirname(__FILE__) . '/../conf.php');
ini_set('soap.wsdl_cache_enabled', 0);

class WsFacturacionCl extends WsFacturacion {

	protected $url = 'http://ws.facturacion.cl/WSDS/wsplano.asmx?wsdl';
	protected $usuario;
	protected $password;
	protected $rut;

	public function __construct($rut, $usuario, $password) {
		parent::__construct();
		$this->isOnline();
		$this->rut = $rut;
		$this->usuario = $usuario;
		$this->password = $password;
	}

	/**
	 *
	 * @param type $dataFactura datos de la factura
	 */
	public function emitirFactura($dataFactura) {
		$afecto = $dataFactura['afecto'];
		$documento = array(
			'Encabezado' => array(
				'IdDoc' => array(
					'TipoDTE' => $dataFactura['tipo_dte'],
					'Folio' => $dataFactura['folio'],
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
					'MntNeto' => $afecto ? $dataFactura['monto_neto'] : 0,
					'MntExe' => $afecto ? 0 : $dataFactura['monto_neto'],
					'TasaIVA' => $afecto ? $dataFactura['tasa_iva'] : 0,
					'IVA' => $afecto ? $dataFactura['monto_iva'] : 0,
					'MntTotal' => $afecto ? $dataFactura['monto_total'] : $dataFactura['monto_neto']
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
		Log::write(print_r($documento, true), 'FacturacionElectronicaCl');
		return $this->enviarDocumento($documento);
	}

	public function anularFactura($folio, $tipo) {
		return $this->anularDocumento('V', $folio, $tipo);
	}

	public function getXmlDte($documento) {
		$params = array(
			'login' => $this->getLogin(),
			'tpomov' => base64_encode(substr($documento['Operacion'], 0, 1)),
			'folio' => base64_encode($documento['Folio']),
			'tipo' => base64_encode($documento['TipoDte'])
		);
		try {
			$xml64 = $this->Client->getXMLDte($params);
		} catch (SoapFault $sf) {
			$xml64 = base64_encode('');
		}
		return base64_decode($xml64);
	}

	public function getPdfUrl($documento, $original = false) {
		$params = array(
			'login' => $this->getLogin(),
			'tpomov' => base64_encode(substr($documento['Operacion'], 0, 1)),
			'folio' => base64_encode($documento['Folio']),
			'tipo' => base64_encode($documento['TipoDte']),
			'cedible' => base64_encode($original ? 'False' : 'True')
		);
		try {
			$respuesta = $this->Client->ObtenerLink($params);
			$sxmle = new SimpleXMLElement($respuesta->ObtenerLinkResult);
			$xml = self::XML2Array($sxmle);
			$url64 = $xml['Mensaje'];
		} catch (SoapFault $sf) {
			$url64 = '';
			$this->setError(1, $sf->getMessage());
		}
		return base64_decode($url64);
	}

	private function enviarDocumento($datosDocumento) {
		$xmlDocumento = self::crearXML($datosDocumento);
		$login = $this->getLogin();
		$params = array(
			'login' => $login,
			'file' => base64_encode($xmlDocumento),
			'formato' => 2
		);
		$respuesta = $this->Client->Procesar($params);
		$sxmle = new SimpleXMLElement($respuesta->ProcesarResult);
		$xml = self::XML2Array($sxmle);
		if ($xml['Resultado'] == 'True') {
			$xml['Detalle']['Documento']['urlPDF'] = $this->getPdfUrl($xml['Detalle']['Documento']);
			$xml['Detalle']['Documento']['xmlDTE'] = $this->getXmlDte($xml['Detalle']['Documento']);
		} else {
			$this->setError(1, $xml['Mensaje'] . ' - ' . $xml['Detalle']['Documento']['Error']);
		}
		return $xml;
	}

	private function anularDocumento($tpomov, $folio, $tipo) {
		$login = $this->getLogin();
		$params = array(
			'login' => $login,
			'tpomov' => base64_encode($tpomov),
			'folio' => base64_encode($folio),
			'tipo' => base64_encode($tipo)
		);
		$respuesta = $this->Client->EliminarDoc($params);
		Log::write(print_r($respuesta, true), 'FacturacionElectronicaCl');
		$sxmle = new SimpleXMLElement($respuesta->EliminarDocResult);
		$xml = self::XML2Array($sxmle);
		if ($xml['Mensaje']['Resultado'] != 'OK') {
			$this->setError(1, $xml['Mensaje']['Resultado']);
		}
		return $xml;
	}

	private function getLogin() {
		$login = array(
			'Rut' => base64_encode($this->rut),
			'Usuario' => base64_encode($this->usuario),
			'Clave' => base64_encode($this->password),
			'Puerto' => base64_encode(0)
		);

		return $login;
	}

	private function isOnline() {
		$respuesta = $this->Client->Online();
		if ($respuesta-OnlineResult !== 1) {
			$this->setError('ServiceUnavailable', 'Servicio temporalmente fuera de servicio, re-intente mas tarde.');
		}
	}

	private static function crearXML($data) {
		$xml = new SimpleXMLElement('<DTE/>');
		$node = $xml->addChild('Documento');
		$data = UtilesApp::utf8izar($data);
		self::array_to_xml($data, $node);
		return $xml->asXML();
	}

}

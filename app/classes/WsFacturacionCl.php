<?php

require_once(dirname(__FILE__) . '/../conf.php');
ini_set('soap.wsdl_cache_enabled', 0);

class WsFacturacionCl {

	protected $tipoCodigo;
	protected $ValorCodigo;
	protected $url = 'http://cps.localhost/ttb/web_services/ws_dummy_facturacion_cl.php?wsdl';
	protected $Client;
	protected $usuario;
	protected $password;
	protected $rut;
	protected $errorCode;
	protected $errorMessage;

	public function __construct($rut, $usuario, $password) {
		$this->Client = new SoapClient($this->url, array('trace' => 1));
		$this->isOnline();
		$this->rut = $rut;
		$this->usuario = $usuario;
		$this->password = $password;
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
					'MntNeto' => $dataFactura['monto_neto'],
					'TasaIVA' => $afecta ? $dataFactura['tasa_iva'] : 0,
					'IVA' => $afecta ? $dataFactura['monto_iva'] : 0,
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

	public function anularFactura($folio, $afecta = false) {
		$tipo = $afecta ? 34 : 33;
		return $this->anularDocumento('V', $folio, $tipo);
	}

	public function getXmlDte($documento) {
		$login = $this->getLogin();
		$tpomov = substr($documento['Operacion'], 0, 1);
		$folio = $documento['Folio'];
		$tipo = $documento['TipoDte'];
		$xml64 = $this->Client->getXMLDte($login, $tpomov, $folio, $tipo);
		return base64_decode($xml64);
	}

	public function getPdfUrl($documento, $original = false) {
		$login = $this->getLogin();
		$tpomov = substr($documento['Operacion'], 0, 1);
		$folio = $documento['Folio'];
		$tipo = $documento['TipoDte'];
		$cedible = $original ? 'False' : 'True';

		$respuesta = $this->Client->ObtenerLink($login, $tpomov, $folio, $tipo, $cedible);
		$sxmle = new SimpleXMLElement($respuesta);
		$xml = self::XML2Array($sxmle);
		$url64 = $xml['Mensaje'];
		return base64_decode($url64);
	}

	public function hasError() {
		return !is_null($this->errorCode);
	}

	public function getErrorCode() {
		return $this->errorCode;
	}

	public function getErrorMessage() {
		return $this->errorMessage;
	}

	private function setError($code, $message) {
		$this->errorCode = $code;
		$this->errorMessage = $message;
	}

	private function enviarDocumento($datosDocumento) {
		$xmlDocumento = self::crearXML($datosDocumento);
		$login = $this->getLogin();
		$respuesta = $this->Client->Procesar($login, base64_encode($xmlDocumento), 2);
		$sxmle = new SimpleXMLElement($respuesta);
		$xml = self::XML2Array($sxmle);
		if ($xml['Resultado'] == 'True') {
			$xml['Detalle']['Documento']['urlPDF'] = $this->getPdfUrl($xml['Detalle']['Documento']);
			$xml['Detalle']['Documento']['xmlDTE'] = $this->getXmlDte($xml['Detalle']['Documento']);
		} else {
			$this->setError(1, $xml['Mensaje']);
		}
		return $xml;
	}

	private function anularDocumento($tpomov, $folio, $tipo) {
		$login = $this->getLogin();
		$respuesta = $this->Client->EliminarDoc($login, $tpomov, $folio, $tipo);
		$sxmle = new SimpleXMLElement($respuesta);
		$xml = self::XML2Array($sxmle);
		if ($xml['Resultado'] != 'True') {
			$this->setError(1, $xml['Mensaje']);
		}
		return $xml;
	}

	private function getLogin() {
		$login = array(
			'Rut' => $this->rut,
			'Usuario' => $this->usuario,
			'Clave' => $this->password,
			'Puerto' => 0
		);

		return $login;
	}

	private function isOnline() {
		$respuesta = $this->Client->Online();
		if ($respuesta != 'Online=1') {
			$this->setError('ServiceUnavailable', 'Servicio temporalmente fuera de servicio, re-intente mas tarde.');
		}
	}

	private static function crearXML($data) {
		$xml = new SimpleXMLElement('<DTE/>');
		$node = $xml->addChild('Documento');
		self::array_to_xml($data, $node);
		return $xml->asXML();
	}

	private static function array_to_xml($array, SimpleXMLElement &$xml) {
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				if (!is_numeric($key)) {
					$subnode = $xml->addChild("$key");
					self::array_to_xml($value, $subnode);
				} else {
					self::array_to_xml($value, $xml);
				}
			} else {
				$xml->addChild("$key", "$value");
			}
		}
	}

	private static function XML2Array(SimpleXMLElement $parent) {
		$array = array();

		foreach ($parent as $name => $element) {
			($node = & $array[$name]) && (1 === count($node) ? $node = array($node) : 1) && $node = & $node[];
			$node = $element->count() ? self::XML2Array($element) : trim($element);
		}

		return $array;
	}

}

/**
 * Test directo
 */
//$t = new WsFacturacionCl();
//$dataFactura = array();
//$dataFactura['fecha_emision'] = date('Y-m-d');
//$dataFactura['emisor'] = array();
//$dataFactura['emisor']['rut'] = '14176614-7';
//$dataFactura['emisor']['razon_social'] = 'Claudio Peralta';
//$dataFactura['emisor']['giro'] = '';
//$dataFactura['emisor']['codigo_actividad'] = '';
//$dataFactura['emisor']['direccion'] = '';
//$dataFactura['emisor']['comuna'] = '';
//$dataFactura['emisor']['cuidad'] = '';
//$dataFactura['receptor'] = array();
//$dataFactura['receptor']['rut'] = '';
//$dataFactura['receptor']['razon_social'] = '';
//$dataFactura['receptor']['giro'] = '';
//$dataFactura['receptor']['direccion'] = '';
//$dataFactura['receptor']['comuna'] = '';
//$dataFactura['receptor']['cuidad'] = '';
//$dataFactura['monto_neto'] = 25000;
//$dataFactura['tasa_iva'] = 19;
//$dataFactura['monto_iva'] = 25000 * .19;
//$dataFactura['monto_total'] = 25000 * 1.19;
//$dataFactura['detalle'] = array(array(
//		'descripcion' => 'Producto o servicio',
//		'cantidad' => 1,
//		'precio_unitario' => 25000
//		));
//
//$r = $t->emitirFactura($dataFactura);
//pr($r);

// $sesion, $correos, $subject, $body, $envia_admin = true, $id_archivo_anexo = NULL)


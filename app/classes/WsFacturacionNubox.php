<?php

require_once(dirname(__FILE__) . '/../conf.php');

ini_set('soap.wsdl_cache_enabled', 0);

class WsFacturacionNubox {

	protected $tipoCodigo;
	protected $ValorCodigo;
	protected $url = 'https://www.servipyme.cl/ServiFacturaCert/WebServices/ArchivoDeVentasElectronicas.asmx?WSDL';
	protected $url_login = 'https://www.servipyme.cl/Perfilamiento/Ws/Autenticador.asmx?WSDL';
	protected $Client;
	protected $rutCliente;
	protected $rutUsuario;
	protected $contrasena;
	protected $sistema;
	protected $numeroSerie;
	protected $token;
	protected $errorCode;
	protected $errorMessage;

	public function __construct($rutCliente, $login) {
		$this->rutCliente = $rutCliente;
		$this->rutUsuario = $login['rutUsuario'];
		$this->contrasena = $login['contrasena'];
		$this->sistema = $login['sistema'];
		$this->numeroSerie = $login['numeroSerie'];
		if ($this->login()) {
			$this->Client = new SoapClient($this->url, array('trace' => 1, 'use' => SOAP_LITERAL));
		}
	}

	/**
	 *
	 * @param type $dataFactura datos de la factura
	 */
	public function emitirFactura($archivo, $opcionFolios, $opcionRutClienteExiste, $opcionRutClienteNoExiste) {
		$datos = array(
			'token' => $this->token,
			'archivo' => $archivo,
			'opcionFolios' => $opcionFolios,
			'opcionRutClienteExiste' => $opcionRutClienteExiste,
			'opcionRutClienteNoExiste' => $opcionRutClienteNoExiste
		);
		pr($datos);
		Log::write(print_r($datos, true), 'FacturacionElectronicaNubox');
		$respuesta = $this->Client->CargarYEmitir($datos);
		pr($this->Client->__getLastRequest());
		return $respuesta;
		exit;
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

	private function login() {
		$login = array(
			'rutCliente' => $this->rutCliente,
			'rutUsuario' => $this->rutUsuario,
			'contrasena' => $this->contrasena,
			'sistema' => $this->sistema,
			'numeroSerie' => $this->numeroSerie
		);

		try {
			$loginClient = new SoapClient($this->url_login, array('trace' => 1));
			$resultado = $loginClient->Autenticar($login);
			pr($loginClient->__getLastRequest());
			$this->token = $resultado->AutenticarResult;
		} catch (Exception $se) {
			$this->setError(530, __('Acceso denegado.'));
			return false;
		}
		return true;
	}

	private static function crearXML($data) {
		$xml = new SimpleXMLElement('<DTE/>');
		$node = $xml->addChild('Documento');
		$data = UtilesApp::utf8izar($data);
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

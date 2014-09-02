<?php

require_once(dirname(__FILE__) . '/../conf.php');

ini_set('soap.wsdl_cache_enabled', 0);

class WsFacturacionNubox extends WsFacturacion{

	protected $url = 'https://www.servipyme.cl/ServiFacturaCert/WebServices/ArchivoDeVentasElectronicas.asmx?WSDL';
	protected $url_login = 'https://www.servipyme.cl/Perfilamiento/Ws/Autenticador.asmx?WSDL';
	protected $rutCliente;
	protected $rutUsuario;
	protected $contrasena;
	protected $sistema;
	protected $numeroSerie;
	protected $token;

	public function __construct($rutCliente, $login) {
		$this->rutCliente = $rutCliente;
		$this->rutUsuario = $login['rutUsuario'];
		$this->contrasena = $login['contrasena'];
		$this->sistema = $login['sistema'];
		$this->numeroSerie = $login['numeroSerie'];
		if ($this->login()) {
			parent::__construct();
		}
	}

	/**
	 *
	 * @param string $archivo contenido CSV que se enviará
	 * @param int $opcionFolios
	 * @param int $opcionRutClienteExiste
	 * @param int $opcionRutClienteNoExiste
	 * @return array
	 * @throws Exception
	 */
	public function emitirFactura($archivo, $opcionFolios, $opcionRutClienteExiste, $opcionRutClienteNoExiste) {
		$datos = array(
			'token' => $this->token,
			'archivo' => $archivo,
			'opcionFolios' => $opcionFolios,
			'opcionRutClienteExiste' => $opcionRutClienteExiste,
			'opcionRutClienteNoExiste' => $opcionRutClienteNoExiste
		);
		Log::write(print_r($datos, true), 'FacturacionElectronicaNubox');
		try {
			try {
				$respuesta = $this->Client->CargarYEmitir($datos);
			} catch (SoapFault $sf) {
				throw new Exception('Acurrió un error al generar el documento.');
			}

			$sxmle = new SimpleXMLElement($respuesta->CargarYEmitirResult->any);
			$xml = self::XML2Array($sxmle);
			if ($xml['Resultado'] != 'OK') {
				throw new Exception('Acurrió un error al generar el documento.');
			}
			return $xml['Documentos']['Documento']['_attributes'] + array('Identificador' => $xml['Identificador']);
		} catch (Exception $ex) {
			$this->setError(1, $ex->getMessage());
		}
	}

	public function getPdf($id) {
		$datos = array(
			'token' => $this->token,
			'identificadorArchivo' => $id,
		);
		$pdf = null;
		try {
			$pdf = $this->Client->ObtenerPDF($datos);
		} catch (SoapFault $sf) {
			$this->setError(1, utf8_decode($sf->getMessage()));
		}
		return $pdf;
	}

	/**
	 * Obtiene un hash de acceso desde el WS de Nubox
	 * @return boolean
	 */
	private function login() {
		$login = array(
			'rutCliente' => $this->rutCliente,
			'rutUsuario' => $this->rutUsuario,
			'contrasena' => $this->contrasena,
			'sistema' => $this->sistema,
			'numeroSerie' => $this->numeroSerie
		);

		try {
			$loginClient = new SoapClient($this->url_login, array('trace' => 0));
			$resultado = $loginClient->Autenticar($login);
			$this->token = $resultado->AutenticarResult;
		} catch (Exception $se) {
			$this->setError(530, __('Acceso denegado.'));
			return false;
		}
		return true;
	}

}

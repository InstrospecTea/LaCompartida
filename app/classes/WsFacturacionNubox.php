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
		$this->url = is_null($login['url']) ? $this->url : $login['url'];
		$this->url_login = is_null($login['url_login']) ? $this->url_login : $login['url'];
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
	public function emitirFactura($archivo, $opcionFolios, $opcionRutClienteExiste, $opcionRutClienteNoExiste, $archivoReferencias = null) {
		$referencias = !is_null($archivoReferencias);
		$datos = array(
			'token' => $this->token,
			'archivo' => $archivo,
			'opcionFolios' => $opcionFolios,
			'opcionRutClienteExiste' => $opcionRutClienteExiste,
			'opcionRutClienteNoExiste' => $opcionRutClienteNoExiste
		);

		if ($referencias) {
			$datos['archivoReferencias'] = $archivoReferencias;
		}

		try {
			try {
				$respuesta = $this->Client->CargarYEmitir2($datos);
			} catch (SoapFault $sf) {
				Log::write($sf->__toString(), 'FacturacionElectronicaNubox');
				throw new Exception('Ocurrió un error al generar el documento.');
			}
			$sxmle = new SimpleXMLElement($respuesta->CargarYEmitir2Result->any);
			$xml = self::XML2Array($sxmle);
			if ($xml['Resultado'] != 'OK') {
				Log::write($xml['Descripcion'], 'FacturacionElectronicaNubox');
				throw new Exception($this->extraerError($xml));
			}
			return $xml['Documentos']['Documento']['_attributes'] + array('Identificador' => $xml['Identificador']);
		} catch (Exception $ex) {
			Log::write($ex->__toString(), 'FacturacionElectronicaNubox');
			$this->setError(1, $ex->getMessage());
		}
	}

	private function extraerError($result) {
		if ($result['Resultado'] == 'C1') {
			$error = __('Ocurrió un error al generar el documento. Por favor verifique que todos los datos del Documento sean correctos.');
		} else {
			$error = __('Ocurrió un error al emitir el documento.');
		}
		$error .= "\n \nInformaciï¿½n de Nubox:\n";
		$pattern = '/Errores encontrados:\nLinea (.?).(.*)Fin fase/si';
		preg_match_all($pattern, $result['Descripcion'], $error_description);
		$error .= utf8_decode($error_description[2][0]);
		return $error;
	}

	public function getPdf($id) {
		Log::write("Getting file for dte_url_pdf: {$id}", 'FacturacionElectronicaNubox');
		Log::write("Using this auth token: {$this->token}", 'FacturacionElectronicaNubox');
		$datos = array(
			'token' => $this->token,
			'identificadorArchivo' => $id,
		);
		$pdf = null;
		try {
			$pdf = $this->Client->ObtenerPDF($datos)->ObtenerPDFResult;
		} catch (SoapFault $sf) {
			$this->setError(1, __("Nubox: El archivo no se puede descargar en este momento; Por favor intente más tarde. DTE ID: {$id}"));
			Log::write($sf->getMessage(), 'FacturacionElectronicaNubox');
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
			/* Debido a la actualización de OpenSSL a la versión 3
			// Por parte de los prestadores de servicio (Nubox), y la actualización
			// de la librería en los servidores de producción *(OpenSSL 1.0.2d 9 Jul 2015)*
			// se necesita crear un contexto de flujo, con el cual se utilice de forma
			// explícita un cipher de ssl (RC4-SHA). Si no se agrega este contexto en la
			// instancia de SoapClient utilizada, este simplemente rechaza la
			// petición, indicando que no puede consultar al servidor.
			// Leer: http://php.net/manual/es/class.soapclient.php#115736
			//
			// A partir de PHP 5.5.0 existe la posibilidad de pasarle el parámetro
			// `ssl_method = SOAP_SSL_METHOD_SSLv3`... pero eso queda para el futuro,
			// cuando TTB utilice PHP 5.5.x :)
			*/
			$stream_context = stream_context_create(array(
				'ssl' => array(
					'ciphers' => 'RC4-SHA'
				)
			));

			$opts = array(
				'trace'          => 0,
				'stream_context' => $stream_context,
			);

			$loginClient = new SoapClient($this->url_login, $opts);

			$resultado   = $loginClient->Autenticar($login);
			$this->token = $resultado->AutenticarResult;

		} catch (Exception $se) {
			$this->setError(530, __('Acceso denegado.'));
			return false;
		}
		return true;
	}

}

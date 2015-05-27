<?php

require_once(dirname(__FILE__) . '/../conf.php');
ini_set('soap.wsdl_cache_enabled', 0);

class WsFacturacion {

	protected $tipoCodigo;
	protected $ValorCodigo;
	protected $url;
	protected $Client;
	protected $errorCode;
	protected $errorMessage;

	public function __construct() {
		$this->Client = new SoapClient($this->url, array('trace' => 1));
	}

	/**
	 * Verifica si ha ocurrido un error
	 * @return boolean
	 */
	public function hasError() {
		return !is_null($this->errorCode);
	}

	/**
	 * Obtiene el codgo del error
	 * @return variant
	 */
	public function getErrorCode() {
		return $this->errorCode;
	}

	/**
	 * Obtiene el mensaje del error
	 * @return string
	 */
	public function getErrorMessage() {
		return $this->errorMessage;
	}

	/**
	 * Registra error en la clase.
	 * @param type $code
	 * @param type $message
	 */
	protected function setError($code, $message) {
		$this->errorCode = $code;
		$this->errorMessage = $message;
	}

	/**
	 * Convierte un Array en un String XML
	 * @param array $array
	 * @param SimpleXMLElement $xml
	 */
	protected static function array_to_xml($array, SimpleXMLElement &$xml) {
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

	/**
	 * Convierte un String XML en un Array
	 * @param SimpleXMLElement $parent
	 * @return array
	 */
	protected static function XML2Array(SimpleXMLElement $parent) {
		$array = array();
		foreach ($parent as $name => $element) {
			if (is_a($element, 'SimpleXMLElement') && $element->attributes()) {
				$attributes = (array) $element->attributes();
				$node = array('_attributes' => $attributes['@attributes']);
			} else {
				$node = $element->count() ? self::XML2Array($element) : trim($element);
			}
			$array[$name] = $node;
		}

		return $array;
	}

}
